<?php
// api/google/submit_sitemap.php — Submit sitemap to GSC for Squarespace

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/credentials.php';
require_once __DIR__ . '/../../includes/google/get_account.php';
require_once __DIR__ . '/../../includes/google/token_manager.php';

$data = json_decode(file_get_contents('php://input'), true) ?: [];

$shopId = $data['shopId'] ?? $_SESSION['shop_id'] ?? null;
$sitemapUrl = $data['sitemap_url'] ?? null;

if (!$shopId || !$sitemapUrl) {
    echo json_encode(['success' => false, 'error' => 'missing_parameters']);
    exit;
}

$google = getGoogleAccountByUser($shopId);

if (
    !$google ||
    $google['connected'] != 1 ||
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

// Normalize sitemap URL
if (!preg_match('~^https?://~i', $sitemapUrl)) {
    $sitemapUrl = 'https://' . $sitemapUrl;
}

// Submit sitemap to GSC: PUT /webmasters/v3/sites/{siteUrl}/sitemaps/{sitemapUrl}
$siteUrl = null;
$stmt = $pdo->prepare("SELECT site_url FROM gsc_domain_verifications WHERE shop_id = ? LIMIT 1");
$stmt->execute([$shopId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $siteUrl = $row['site_url'];
}

if (!$siteUrl) {
    echo json_encode(['success' => false, 'error' => 'site_url_not_found']);
    exit;
}

$apiUrl = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($siteUrl) . '/sitemaps/' . rawurlencode($sitemapUrl);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'PUT',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json",
    ],
    CURLOPT_TIMEOUT        => 15,
]);

$response = curl_exec($ch);
$http     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http >= 200 && $http < 300) {
    echo json_encode(['success' => true, 'message' => 'Sitemap submitted successfully']);
    exit;
}

echo json_encode([
    'success' => false,
    'error'   => 'google_api_error',
    'http'    => $http,
    'message' => substr((string) $response, 0, 200),
]);
exit;
