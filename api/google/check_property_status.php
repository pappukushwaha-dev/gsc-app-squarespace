<?php
// api/google/check_property_status.php — Check if GSC property is verified
// Same pattern as Wix/Shopify api/google/check_property_status.php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/credentials.php';
require_once __DIR__ . '/../../includes/google/get_account.php';
require_once __DIR__ . '/../../includes/google/token_manager.php';

$instanceId = $_GET['instanceId'] ?? null;
if (!$instanceId) {
    echo json_encode(['success' => false, 'error' => 'Missing instanceId']);
    exit;
}

$google = getGoogleAccountByUser($instanceId);
if (
    !$google ||
    (empty($google['access_token']) && empty($google['refresh_token']))
) {
    echo json_encode(['success' => false, 'error' => 'account_needs_reconnect']);
    exit;
}

$accountId   = (int) $google['id'];
$tokenResult = ensureAccessToken($accountId, 120);
$accessToken = $tokenResult['access_token'] ?? $google['access_token'];

if (empty($accessToken)) {
    echo json_encode(['success' => false, 'error' => 'account_needs_reconnect']);
    exit;
}

// Domain from DB
$stmt = $pdo->prepare("SELECT site_url FROM gsc_domain_verifications WHERE instance_id = ? LIMIT 1");
$stmt->execute([$instanceId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Domain record not found']);
    exit;
}

$raw = trim((string)$row['site_url']);
if (!preg_match('~^https?://~i', $raw)) $raw = 'https://' . $raw;

$parts = parse_url($raw);
$host  = strtolower(preg_replace('/^www\./i', '', $parts['host'] ?? ''));
$path  = rtrim($parts['path'] ?? '', '/');

if ($host === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid site_url']);
    exit;
}

$wantKey = $host . $path;

/**
 * Reduce a Search Console property to host+path shape.
 */
function gscPropertyKey(string $url): ?string
{
    if (stripos($url, 'sc-domain:') === 0) {
        return preg_replace('/^www\./i', '', strtolower(substr($url, 10)));
    }
    $p = parse_url($url);
    $h = strtolower(preg_replace('/^www\./i', '', $p['host'] ?? ''));
    $pa = rtrim($p['path'] ?? '', '/');
    return $h === '' ? null : ($h . $pa);
}

// List all sites in GSC
$apiUrl = 'https://www.googleapis.com/webmasters/v3/sites';

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$accessToken}"],
    CURLOPT_TIMEOUT        => 15,
]);
$response = curl_exec($ch);
$http     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json = is_string($response) ? json_decode($response, true) : null;
$sites = $json['siteEntry'] ?? [];

$propertyFound = false;
$verified      = false;
$matchedSite   = null;

foreach ($sites as $site) {
    $url  = $site['siteUrl'] ?? '';
    $perm = $site['permissionLevel'] ?? '';

    $siteKey = gscPropertyKey($url);
    if ($siteKey === null || $siteKey !== $wantKey) {
        continue;
    }

    $propertyFound = true;
    $matchedSite   = ['siteUrl' => $url, 'permissionLevel' => $perm];

    if ($perm !== 'siteUnverifiedUser') {
        $verified = true;
        break;
    }
}

// If verified, update DB and start backfill
if ($verified) {
    $verificationData = null;

    // Try to fetch meta verification token
    try {
        $metaUrl = 'https://www.googleapis.com/webmasters/v3/webmasters/verifications';
        $ch2 = curl_init($metaUrl);
        curl_setopt_array($ch2, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$accessToken}",
                "Content-Type: application/json",
            ],
            CURLOPT_POSTFIELDS     => json_encode(['site' => $raw]),
            CURLOPT_TIMEOUT        => 15,
        ]);
        $metaResponse = curl_exec($ch2);
        $metaHttp     = (int) curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        $metaJson = is_string($metaResponse) ? json_decode($metaResponse, true) : null;
        if ($metaHttp >= 200 && $metaHttp < 300 && is_array($metaJson)) {
            $verificationData = [
                'token'  => $metaJson['token'] ?? null,
                'meta_tag' => $metaJson['metaTag'] ?? null,
            ];
        }
    } catch (\Throwable) {
        // ignore
    }

    $metaToken = $verificationData['token'] ?? null;
    $metaTag   = $verificationData['meta_tag'] ?? null;

    $pdo->prepare("
        UPDATE gsc_domain_verifications
        SET verification_status      = 'verified',
            verification_verified_at = NOW(),
            verification_checked_at  = NOW(),
            meta_token               = ?,
            meta_tag                 = ?
        WHERE instance_id = ?
    ")->execute([$metaToken, $metaTag, $instanceId]);

    // Start backfill
    require_once __DIR__ . '/../../includes/google/maybe_start_backfill.php';
    if (function_exists('gsc_maybe_start_backfill')) {
        gsc_maybe_start_backfill($pdo, $instanceId);
    }
} else {
    $pdo->prepare("
        UPDATE gsc_domain_verifications
        SET verification_checked_at = NOW()
        WHERE instance_id = ?
    ")->execute([$instanceId]);
}

echo json_encode([
    'success'        => true,
    'property_found' => $propertyFound,
    'verified'       => $verified,
    'matched_site'   => $matchedSite,
    'host'           => $host,
    'path'           => $path,
    'matched_on'     => $wantKey,
], JSON_UNESCAPED_SLASHES);
exit;
