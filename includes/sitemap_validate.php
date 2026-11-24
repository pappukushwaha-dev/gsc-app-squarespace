<?php
declare(strict_types=1);

/**
 * Sitemap URL check before it is submitted to Google (2026-11-24, QA S3-02).
 *
 * Google's sitemaps.submit API accepts ANY path on the property, so "/abc",
 * "/", "/robots.txt" or a blog post URL used to show as "Accepted". This
 * fetches the URL first and refuses it when it clearly is not a sitemap.
 *
 *  - Rejected: not http(s), not on the verified property, empty path,
 *    404/410, or a 200 response that is not a sitemap (HTML page etc.).
 *  - Allowed: XML sitemap / sitemap index, RSS, Atom, gzip, plain-text URL
 *    list. When the site blocks our server (401/403/429/5xx, timeout) the
 *    URL is passed through so a real sitemap is never blocked; Google
 *    decides.
 *
 * @return array{ok: bool, message: string}
 */
function gscValidateSitemapUrl(string $sitemapUrl, string $siteForApi): array
{
    $fail = static function (string $msg): array {
        return ['ok' => false, 'message' => $msg];
    };

    $parts  = parse_url(trim($sitemapUrl));
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host   = strtolower((string) ($parts['host'] ?? ''));
    $path   = (string) ($parts['path'] ?? '');
    if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
        return $fail('Please enter a valid sitemap URL, for example sitemap.xml.');
    }
    // Shown in messages: only safe characters, because one UI renders the
    // message as HTML and the other as plain text.
    $shown = '/' . ltrim((string) preg_replace('#[^A-Za-z0-9._/-]#', '', $path), '/');
    if ($path === '' || $path === '/') {
        return $fail('Please enter the sitemap file name, for example sitemap.xml.');
    }

    // Must be on the verified property (URL-prefix or sc-domain:).
    $prop = trim($siteForApi);
    if (stripos($prop, 'sc-domain:') === 0) {
        $propHost = strtolower(substr($prop, 10));
        $onProp   = $host === $propHost || substr($host, -strlen('.' . $propHost)) === '.' . $propHost;
    } else {
        $propHost = strtolower((string) parse_url($prop, PHP_URL_HOST));
        $strip    = static fn(string $h): string => (string) preg_replace('/^www\./', '', $h);
        $onProp   = $propHost !== '' && $strip($host) === $strip($propHost);
    }
    if (!$onProp) {
        return $fail('The sitemap must be on your verified website (' . preg_replace('/[^a-z0-9.-]/', '', $propHost) . ').');
    }

    // Fetch it. Redirects are followed by hand so every hop is checked to be
    // a public address and the connection is pinned to that checked IP.
    $body  = '';
    $limit = 262144; // read at most 256 KB
    $http  = 0;
    $ctype = '';
    $url   = $sitemapUrl;
    for ($hop = 0; $hop <= 3; $hop++) {
        $u  = parse_url($url);
        $sc = strtolower((string) ($u['scheme'] ?? ''));
        $h  = strtolower((string) ($u['host'] ?? ''));
        if (!in_array($sc, ['http', 'https'], true) || $h === '') {
            return ['ok' => true, 'message' => 'unchecked_redirect'];
        }
        $ips = @gethostbynamel($h) ?: [];
        if (!$ips) {
            return ['ok' => true, 'message' => 'dns_unresolved'];
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $fail('This sitemap URL cannot be checked. Please use your public website address.');
            }
        }
        $port = (int) ($u['port'] ?? ($sc === 'https' ? 443 : 80));
        $body = '';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_RESOLVE        => [$h . ':' . $port . ':' . $ips[0]],
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_ENCODING       => '',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Makkpress-GSC-SitemapCheck/1.0; +https://makkpressapps.com)',
            CURLOPT_HTTPHEADER     => ['Accept: application/xml,text/xml,application/rss+xml,text/plain;q=0.9,*/*;q=0.5'],
            CURLOPT_WRITEFUNCTION  => static function ($c, string $chunk) use (&$body, $limit): int {
                $body .= $chunk;
                return strlen($body) >= $limit ? 0 : strlen($chunk); // 0 = stop reading
            },
        ]);
        curl_exec($ch);
        $http  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype = strtolower((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        $next  = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        if ($http >= 300 && $http < 400 && $next !== '') {
            $url = $next;
            continue;
        }
        break;
    }

    if ($http === 404 || $http === 410) {
        return $fail("We couldn't find a sitemap at {$shown} (your site returned \"page not found\"). Check the file name and try again.");
    }
    if ($http < 200 || $http >= 300) {
        // Timeout, blocked bot, server error: don't block a possibly real sitemap.
        return ['ok' => true, 'message' => 'unchecked_http_' . $http];
    }

    if (strncmp($body, "\x1f\x8b", 2) === 0) { // .xml.gz served without Content-Encoding
        $gz = @gzdecode($body);
        if ($gz === false) {
            return ['ok' => true, 'message' => 'gzip_partial'];
        }
        $body = $gz;
    }
    $head = strtolower(substr(ltrim($body, "\xEF\xBB\xBF \t\r\n"), 0, $limit));

    if (preg_match('/<(?:[a-z0-9_-]+:)?(urlset|sitemapindex)[\s>]/', $head)
        || preg_match('/<rss[\s>]/', $head)
        || preg_match('/<feed[\s>]/', $head)) {
        return ['ok' => true, 'message' => 'ok'];
    }
    // Text sitemap: one absolute URL per line.
    if (strpos($ctype, 'text/plain') !== false || !preg_match('/[<>]/', substr($head, 0, 2000))) {
        $lines = array_values(array_filter(array_map('trim', explode("\n", substr($head, 0, 20000))), 'strlen'));
        if ($lines && count(array_filter($lines, static fn($l) => preg_match('#^https?://\S+$#', $l))) === count($lines)) {
            return ['ok' => true, 'message' => 'ok_text'];
        }
    }

    if (strpos($ctype, 'html') !== false || preg_match('/<(!doctype html|html)[\s>]/', $head)) {
        return $fail("{$shown} is a web page, not a sitemap. A sitemap is an XML file such as sitemap.xml.");
    }
    return $fail("{$shown} is not a valid sitemap. A sitemap must be an XML file (urlset or sitemapindex), for example sitemap.xml.");
}
