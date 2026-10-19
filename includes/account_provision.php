<?php

declare(strict_types=1);

/**
 * account_provision.php — Squarespace registration core.
 *
 * The platform has NO marketplace app: users register here directly.
 * One code path owns everything:
 *
 *   squarespaceVerifySite()  — fetches the submitted URL and proves it
 *                              really is a Squarespace site (data-site-id,
 *                              siteId JS config, or *.squarespace.com
 *                              internalUrl markers).
 *   provisionInstance()      — creates the SquarespaceSite row (the
 *                              instance) for a verified site.
 *   createMagicToken()       — single-use login token (hashed at rest).
 *   sendMagicLink()          — emails the login link.
 *   findInstanceByEmail()    — returning-user lookup.
 *
 * All user-input rejections throw AccountProvisionException with a
 * user-safe message; network calls are bounded; nothing echoes.
 */

/* ---------- user-input rejection ---------- */

class AccountProvisionException extends RuntimeException {}

/* ---------- helpers ---------- */

if (!function_exists('normalizeDomain')) {
    function normalizeDomain(string $website): string
    {
        $website = trim(strtolower($website));
        if ($website === '') {
            throw new AccountProvisionException('Please enter your website URL');
        }
        if (!preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }
        $parts = parse_url($website);
        if (empty($parts['host']) || !str_contains($parts['host'], '.')) {
            throw new AccountProvisionException('Please enter a valid website URL');
        }
        return preg_replace('/^www\./i', '', $parts['host']);
    }
}

if (!function_exists('extractMeta')) {
    function extractMeta(string $html): array
    {
        $title = '';
        if (preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m)) {
            $title = trim(html_entity_decode($m[1]));
        }
        $description = '';
        if (preg_match('#<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']#is', $html, $m)
            || preg_match('#<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\']#is', $html, $m)) {
            $description = trim(html_entity_decode($m[1]));
        }
        return ['title' => $title, 'description' => $description];
    }
}

/* ---------- Squarespace detection ---------- */

if (!function_exists('squarespaceVerifySite')) {
    /**
     * Fetches the site and proves it runs on Squarespace.
     *
     * Markers, best first:
     *   1. data-site-id="..."            (every Squarespace page body tag)
     *   2. "siteId":"..."                (Static.SQUARESPACE_CONTEXT js)
     *   3. internalUrl: *.squarespace.com (built-site config)
     *   4. <meta name="generator" content="Squarespace">
     *
     * @return array{site_id:?string, title:string, description:string, final_url:string}
     * @throws AccountProvisionException when the site is unreachable or
     *         shows no Squarespace markers.
     */
    function squarespaceVerifySite(string $domain): array
    {
        $siteUrl = 'https://' . $domain;

        $ch = curl_init($siteUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; MakkPressGSC/1.0)',
        ]);
        $html    = (string) curl_exec($ch);
        $http    = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $final   = (string) (curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $siteUrl);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($html === '' || $http < 200 || $http >= 400) {
            error_log('squarespaceVerifySite failed: http=' . $http . ' err=' . $curlErr);
            throw new AccountProvisionException('We could not reach your website. Check the URL and try again.');
        }

        // marker 1 + 2: the two reliable Squarespace fingerprints
        $detectedSiteId = null;
        if (preg_match('/data-site-id="([a-z0-9]+)"/i', $html, $m)) {
            $detectedSiteId = $m[1];
        }
        if (!$detectedSiteId && preg_match('/"siteId"\s*:\s*"([a-z0-9]+)"/i', $html, $m)) {
            $detectedSiteId = $m[1];
        }

        // marker 3: the built-site internal URL
        $isInternal = (bool) preg_match('#https://([a-z0-9\-]+)\.squarespace\.com#i', $html);

        // marker 4: the generator meta
        $isGenerator = (bool) preg_match('#<meta[^>]+name=["\']generator["\'][^>]+content=["\'][^"\']*Squarespace#i', $html);

        if (!$detectedSiteId && !$isInternal && !$isGenerator) {
            throw new AccountProvisionException('This website does not appear to be built on Squarespace. Enter the Squarespace site you want to connect.');
        }

        $meta = extractMeta($html);

        return [
            'site_id'     => $detectedSiteId,
            'title'       => $meta['title'] ?: $domain,
            'description' => $meta['description'] ?? '',
            'final_url'   => $final,
        ];
    }
}

/* ---------- instance provisioning ---------- */

if (!function_exists('gscUuidV4')) {
    function gscUuidV4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}

if (!function_exists('provisionInstance')) {
    /**
     * Creates the SquarespaceSite instance row for a verified site.
     * Re-registering the same domain returns the SAME instance (idempotent,
     * like every family app) — the existing row is reactivated and its
     * squarespace site_id refreshed.
     *
     * @return array{shop_id:string, created:bool}
     */
    function provisionInstance(PDO $pdo, string $email, string $domain, array $verified): array
    {
        $shopId = gscUuidV4();
        $siteUrl    = 'https://' . $domain;

        $stmt = $pdo->prepare("SELECT id, shop_id, is_active FROM SquarespaceSite WHERE domain = ? LIMIT 1");
        $stmt->execute([$domain]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $upd = $pdo->prepare("
                UPDATE SquarespaceSite
                SET owner_email = :email,
                    site_url = :url,
                    site_id = COALESCE(:sid, site_id),
                    site_display_name = :name,
                    is_active = 1,
                    is_verified = 1,
                    site_verified_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
            ");
            $upd->execute([
                ':email' => $email,
                ':url'   => $siteUrl,
                ':sid'   => $verified['site_id'],
                ':name'  => $verified['title'],
                ':id'    => (int) $existing['id'],
            ]);
            return ['shop_id' => (string) $existing['shop_id'], 'created' => false];
        }

        $ins = $pdo->prepare("
            INSERT INTO SquarespaceSite
                (shop_id, site_id, site_url, site_display_name, domain,
                 owner_email, is_active, is_verified, site_verified_at, created_at, updated_at)
            VALUES
                (:iid, :sid, :url, :name, :dom, :email, 1, 1, NOW(), NOW(), NOW())
        ");
        $ins->execute([
            ':iid'  => $shopId,
            ':sid'  => $verified['site_id'],
            ':url'  => $siteUrl,
            ':name' => $verified['title'],
            ':dom'  => $domain,
            ':email' => $email,
        ]);

        return ['shop_id' => $shopId, 'created' => true];
    }
}

/* ---------- magic tokens ---------- */

if (!function_exists('createMagicToken')) {
    /**
     * Stores a single-use token (hash at rest, 30-minute expiry) and
     * returns the plaintext token for the emailed link.
     */
    function createMagicToken(PDO $pdo, string $email, string $shopId): string
    {
        $token = bin2hex(random_bytes(24));
        $stmt = $pdo->prepare("
            INSERT INTO gsc_magic_links (email, shop_id, token_hash, purpose, expires_at)
            VALUES (?, ?, ?, 'login', DATE_ADD(NOW(), INTERVAL 30 MINUTE))
        ");
        $stmt->execute([$email, $shopId !== '' ? $shopId : null, hash('sha256', $token)]);
        return $token;
    }
}

if (!function_exists('sendMagicLink')) {
    function sendMagicLink(string $email, string $token): bool
    {
        $link = APP_URL . '/magic-login.php?token=' . urlencode($token);
        $subject = 'Your ' . APP_NAME . ' sign-in link';
        $body = '<p>Click the link below to sign in. It works once and expires in 30 minutes.</p>'
              . '<p><a href="' . htmlspecialchars($link) . '" style="display:inline-block;padding:12px 22px;'
              . 'background:#4c2bd4;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;">'
              . 'Sign in to ' . htmlspecialchars(APP_NAME) . '</a></p>'
              . '<p style="color:#888;font-size:12px;">If you did not request this, ignore this email.</p>'
              . '<p style="color:#888;font-size:12px;">' . htmlspecialchars($link) . '</p>';
        return Mailer::send($email, $subject, $body);
    }
}

if (!function_exists('findInstanceByEmail')) {
    /** Returning-user lookup: the active instance for this email. */
    function findInstanceByEmail(PDO $pdo, string $email): ?array
    {
        $stmt = $pdo->prepare("
            SELECT shop_id, domain, site_url, site_display_name, is_verified
            FROM SquarespaceSite
            WHERE owner_email = ? AND is_active = 1
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('consumeMagicToken')) {
    /**
     * Single-use consume: returns the instance row when the token is
     * valid, unused and unexpired; null otherwise.
     */
    function consumeMagicToken(PDO $pdo, string $token): ?array
    {
        $hash = hash('sha256', $token);
        $stmt = $pdo->prepare("
            SELECT ml.id, ml.email, ml.shop_id
            FROM gsc_magic_links ml
            WHERE ml.token_hash = ?
              AND ml.used_at IS NULL
              AND ml.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$hash]);
        $link = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$link) {
            return null;
        }

        $pdo->prepare("UPDATE gsc_magic_links SET used_at = NOW() WHERE id = ?")
            ->execute([(int) $link['id']]);

        $stmt = $pdo->prepare("
            SELECT shop_id, domain, site_url, site_display_name, owner_email AS email, is_verified
            FROM SquarespaceSite
            WHERE shop_id = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([(string) $link['shop_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
