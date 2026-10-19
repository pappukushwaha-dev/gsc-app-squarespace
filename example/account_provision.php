<?php

declare(strict_types=1);

/**
 * account_provision.php
 *
 * Shared account-creation body for the Squarespace signup path. Extracted from
 * api/register.php so both register (magic-link flow) and the OAuth website step
 * create accounts through ONE code path.
 *
 * createSquarespaceAccount() does NOT begin/commit a transaction and does NOT emit
 * JSON — the caller owns the transaction and the HTTP response. User-input
 * rejections that were previously `echo json_encode(...); exit;` inline in
 * register.php are raised here as AccountProvisionException carrying the SAME
 * message string; callers map it back to {ok:false, message} at HTTP 200.
 *
 * The scrape helpers (normalizeDomain / extractPhoneNumber / resolveUrl /
 * extractFavicon / extractMeta) are moved here verbatim from register.php so there
 * is exactly one copy. They are function_exists-guarded to avoid redeclaration.
 */

require_once __DIR__ . '/proxy-fetch.php';

/**
 * User-input rejection during account provisioning. Callers catch this and echo
 * {ok:false, message:$e->getMessage()} at HTTP 200 (NOT a 500). The message
 * strings are contractual — reproduced verbatim from the old inline responses.
 */
class AccountProvisionException extends Exception {}

/* ---------- normalize domain ---------- */
if (!function_exists('normalizeDomain')) {
    function normalizeDomain(string $website): string
    {
        $website = trim(strtolower($website));
        if (!preg_match('#^https?://#', $website)) {
            $website = 'http://' . $website;
        }
        $parts = parse_url($website);
        if (empty($parts['host'])) {
            throw new Exception('Invalid website URL');
        }
        return preg_replace('/^www\./', '', $parts['host']);
    }
}

if (!function_exists('extractPhoneNumber')) {
    function extractPhoneNumber(string $html): ?string
    {
        // tel: link
        if (preg_match('/href=["\']tel:([^"\']+)["\']/i', $html, $m)) {
            return trim($m[1]);
        }

        // JSON-LD telephone
        if (preg_match('/"telephone"\s*:\s*"([^"]+)"/i', $html, $m)) {
            return trim($m[1]);
        }

        // Fallback regex
        if (preg_match('/(\+?\d[\d\s\-\(\)]{7,}\d)/', $html, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}

if (!function_exists('resolveUrl')) {
    function resolveUrl(string $base, string $relative): string
    {
        if (preg_match('#^https?://#i', $relative)) {
            return $relative;
        }

        if (str_starts_with($relative, '//')) {
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $relative;
        }

        $parsed = parse_url($base);
        $scheme = $parsed['scheme'] ?? 'https';
        $host   = $parsed['host'] ?? '';
        $path   = isset($parsed['path']) ? rtrim(dirname($parsed['path']), '/') : '';

        if (str_starts_with($relative, '/')) {
            return $scheme . '://' . $host . $relative;
        }

        return $scheme . '://' . $host . $path . '/' . $relative;
    }
}

if (!function_exists('extractFavicon')) {
    function extractFavicon(string $html, string $siteUrl): string
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Look for any rel containing "icon"
        $nodes = $xpath->query('//link[contains(translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"), "icon")]');

        if ($nodes->length > 0) {
            $href = trim($nodes->item(0)->getAttribute('href'));
            if ($href !== '') {
                return resolveUrl($siteUrl, $href);
            }
        }

        // Fallback to /favicon.ico
        $parts = parse_url($siteUrl);
        if (!empty($parts['scheme']) && !empty($parts['host'])) {
            return $parts['scheme'] . '://' . $parts['host'] . '/favicon.ico';
        }

        return '';
    }
}

if (!function_exists('extractMeta')) {
    function extractMeta(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $titleNode = $xpath->query('//title')->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : '';

        $description = '';

        foreach ($xpath->query('//meta') as $tag) {
            $name = strtolower($tag->getAttribute('name'));
            $property = strtolower($tag->getAttribute('property'));
            $content = trim($tag->getAttribute('content'));

            if ($name === 'description' && !$description) {
                $description = $content;
            }

            if ($property === 'og:description' && !$description) {
                $description = $content;
            }
        }

        return [
            'title' => $title,
            'description' => $description
        ];
    }
}

/**
 * Create a Squarespace account (User + UserSite + scriptKey + selected_site + the
 * three profile tables), scraping the site through the CF proxy to detect siteId.
 *
 * Runs INSIDE the caller's transaction (caller does beginTransaction/commit).
 * Does NOT touch $_SESSION and does NOT emit output.
 *
 * @param PDO    $pdo
 * @param string $email    caller-normalized email; re-lowercased defensively here
 * @param string $name     display name; derived from email if empty (OAuth path)
 * @param string $website  host (already normalizeDomain()'d by the caller)
 * @return array{user_id:int, user_site_id:int, site_id:string}
 * @throws AccountProvisionException  on the four user-input rejections (verbatim messages)
 * @throws Throwable                  on genuinely unexpected failures (caller → 500)
 */
function createSquarespaceAccount(PDO $pdo, string $email, string $name, string $website): array
{
    $email = strtolower(trim($email));

    // name-from-email fallback (register.php passes a derived name; OAuth may pass a real one)
    if (trim($name) === '') {
        $localPart = explode('@', $email)[0];
        $localPart = str_replace(['.', '_', '-'], ' ', $localPart);
        $name = ucwords(trim($localPart));
    }

    // email exists?
    $stmt = $pdo->prepare("SELECT id FROM User WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new AccountProvisionException('Email already exists');
    }

    $siteDomain = preg_replace('#^https?://#i', '', $website);
    $siteDomain = preg_replace('#^www\.#i', '', $siteDomain);
    $siteDomain = rtrim($siteDomain, '/');

    $siteUrl = 'https://' . $siteDomain;

    $result = proxy_fetch($siteUrl, [
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);

    $html     = $result['body'];
    $httpCode = $result['code'];

    if (!$html || $httpCode < 200 || $httpCode >= 400) {
        throw new AccountProvisionException('Please add the Squarespace website URL');
    }

    $meta = extractMeta($html);
    $phone = extractPhoneNumber($html);
    $favicon = extractFavicon($html, $siteUrl);

    $title = $meta['title'] ?: $siteDomain;
    $description = $meta['description'] ?? '';

    $detectedSiteId = null;

    /* 3.1 data-site-id (BEST) */
    if (preg_match('/data-site-id="([a-z0-9]+)"/i', $html, $m)) {
        $detectedSiteId = $m[1];
    }

    /* 3.2 JS siteId */
    if (!$detectedSiteId && preg_match('/"siteId"\s*:\s*"([a-z0-9]+)"/i', $html, $m)) {
        $detectedSiteId = $m[1];
    }

    /* 3.3 Old internalUrl */
    if (
        !$detectedSiteId &&
        preg_match('/"internalUrl"\s*:\s*"https:\/\/([a-z0-9\-]+)\.squarespace\.com"/i', $html, $m)
    ) {
        $detectedSiteId = $m[1];
    }

    if (!$detectedSiteId) {
        throw new AccountProvisionException('Unable to verify Squarespace website');
    }

    // prevent duplicate site
    $stmt = $pdo->prepare("
        SELECT
            us.userId,
            us.scriptKey,
            u.is_verified AS user_verified
        FROM UserSite us
        JOIN User u ON u.id = us.userId
        WHERE us.siteId = ?
        AND us.scriptKey IS NOT NULL
        AND u.is_verified = 1
        LIMIT 1
    ");
    $stmt->execute([$detectedSiteId]);
    $site = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($site) {
        throw new AccountProvisionException('This site is already verified');
    }

    // create user
    $stmt = $pdo->prepare("
        INSERT INTO User (name, email)
        VALUES (?, ?)
    ");
    $stmt->execute([
        $name,
        $email
    ]);

    $userId = (int)$pdo->lastInsertId();

    // insert site
    $stmt = $pdo->prepare("
        INSERT INTO UserSite (userId, siteId, siteDomain, scriptGeneratedAt)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $detectedSiteId, $website]);

    $userSiteId = (int)$pdo->lastInsertId();
    if ($userSiteId <= 0) {
        throw new Exception('UserSite insert failed');
    }

    // secure scriptKey
    $scriptKey = 'sjs-' . $userSiteId;
    $pdo->prepare("
        UPDATE UserSite SET scriptKey = ? WHERE id = ?
    ")->execute([$scriptKey, $userSiteId]);

    // set selected site
    $pdo->prepare("
        UPDATE User SET selected_site = ? WHERE id = ?
    ")->execute([$userSiteId, $userId]);

    // ---------------------------------- Local business
    $stmt = $pdo->prepare("
    INSERT INTO user_local_business
    (user_id, user_site_id, business_name, phone, created_at, updated_at)
    VALUES (?, ?, ?, ?, NOW(), NOW())
");
    $stmt->execute([
        $userId,
        $userSiteId,
        $title,
        $phone
    ]);

    // --------------------------------- Organization
    $stmt = $pdo->prepare("
    INSERT INTO user_organization_details
    (user_id, user_site_id, organization_name, organization_url, description, phone, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
");
    $stmt->execute([
        $userId,
        $userSiteId,
        $title,
        $siteUrl,
        $description,
        $phone
    ]);

    $stmt = $pdo->prepare("
    INSERT INTO user_knowledge_graph
    (user_id, user_site_id, entity_type, name, website_url, logo_url, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
");
    $stmt->execute([
        $userId,
        $userSiteId,
        'organization',      // or 'local_business' if you prefer
        $title,
        $siteUrl,
        $favicon
    ]);

    // fresh account is unverified until the magic link is consumed
    $pdo->prepare("
    UPDATE User
    SET is_verified = 0
    WHERE id = ?
")->execute([$userId]);

    // ---------------------------------- 24-hour free trial (new-signup cohort)
    // Every newly provisioned account gets a 24h full-access trial. The mere
    // PRESENCE of this row is what distinguishes the post-launch trial cohort
    // from legacy free users (who have no row) - all trial gating keys off it
    // (see includes/plan_guard.php::planGuardTrialState). user_id / site_id are
    // stored as strings to match the app_free_trials column types and the
    // lookups in plan_guard.php / fix-cap.php / subscription-status.php.
    //
    // The 24h COUNTDOWN, however, is not meant to start at registration - it
    // starts when the user actually begins ("first audit": the first time they
    // lock in a schema mode in the setup wizard). So the row is seeded PENDING
    // (raw_event.kind = 'signup_24h_pending') with a placeholder 24h window that
    // keeps the account fully unlocked and out of the pricing redirect while they
    // reach step 1. api/update-step.php re-anchors started_at / expires_on to a
    // fresh 24h the moment they pick auto/manual, and flips kind to 'signup_24h'.
    // Non-fatal: a trial-insert failure must NEVER break account creation.
    try {
        $pdo->prepare("
            INSERT INTO app_free_trials
                (user_id, site_id, status, started_at, expires_on, raw_event, created_at, updated_at)
            VALUES
                (?, ?, 'active', NOW(3), DATE_ADD(NOW(3), INTERVAL 24 HOUR), ?, NOW(3), NOW(3))
        ")->execute([
            (string) $userId,
            (string) $userSiteId,
            json_encode(['kind' => 'signup_24h_pending', 'trial_hours' => 24]),
        ]);
    } catch (Throwable $e) {
        error_log('[24h-trial] insert failed for user ' . $userId . ' / site ' . $userSiteId . ': ' . $e->getMessage());
    }

    return [
        'user_id'      => $userId,
        'user_site_id' => $userSiteId,
        'site_id'      => $detectedSiteId,
    ];
}
