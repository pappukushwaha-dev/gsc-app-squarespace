<?php
// api/google/verify_domain.php — Verify Squarespace domain in GSC
// Same pattern as Wix/Shopify api/google/verify_domain.php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/credentials.php';
require_once __DIR__ . '/../../includes/google/get_account.php';
require_once __DIR__ . '/../../includes/google/token_manager.php';

global $pdo;

/* ==========================================================
   INPUT
========================================================== */
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$shopId = $_SESSION['shop_id'] ?? ($input['shopId'] ?? null);

if (!$shopId) {
    echo json_encode(['success' => false, 'error' => 'missing_shop_id']);
    exit;
}

/* ==========================================================
   LOAD EXISTING PROPERTY + TOKEN
========================================================== */
$stmt = $pdo->prepare("
    SELECT site_url, meta_token
    FROM gsc_domain_verifications
    WHERE shop_id = ?
    LIMIT 1
");
$stmt->execute([$shopId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['site_url']) || empty($row['meta_token'])) {
    echo json_encode(['success' => false, 'error' => 'property_or_token_missing']);
    exit;
}

$siteUrl   = $row['site_url'];
$metaToken = $row['meta_token'];

/* ==========================================================
   GOOGLE ACCOUNT
========================================================== */
$googleAccount = getGoogleAccountByUser($shopId);

if (
    !$googleAccount ||
    $googleAccount['connected'] != 1
) {
    echo json_encode(['success' => false, 'error' => 'google_not_connected']);
    exit;
}

$tokenResult = ensureAccessToken((int)$googleAccount['id'], 120);
$accessToken = $tokenResult['access_token'] ?? null;

if (!$accessToken) {
    echo json_encode(['success' => false, 'error' => 'google_token_failed']);
    exit;
}

/* ==========================================================
   CALL GOOGLE SITE VERIFICATION API
========================================================== */
// Google site verification: https://developers.google.com/webmaster-tools/search-console-api-original/v3/verification/resource
$verifyUrl = 'https://www.googleapis.com/webmasters/v3/webmasters/verifications';

$ch = curl_init($verifyUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json",
    ],
    CURLOPT_POSTFIELDS     => json_encode(['site' => $siteUrl]),
    CURLOPT_TIMEOUT        => 15,
]);

$response = curl_exec($ch);
$http     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json = is_string($response) ? json_decode($response, true) : null;

if ($http >= 200 && $http < 300 && is_array($json)) {
    // Verification succeeded
    $pdo->prepare("
        UPDATE gsc_domain_verifications
        SET verification_status      = 'verified',
            verification_verified_at = NOW(),
            verification_checked_at  = NOW()
        WHERE shop_id = ?
    ")->execute([$shopId]);

    echo json_encode([
        'success' => true,
        'verified' => true,
        'site'     => $siteUrl,
    ]);
    exit;
}

// Verification not yet successful (meta tag not found, etc.)
echo json_encode([
    'success' => true,
    'verified' => false,
    'error'   => $json['error']['message'] ?? 'verification_not_complete',
    'http'    => $http,
]);
exit;
