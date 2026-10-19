<?php
// api/google/create_property.php — Create GSC URL-prefix property for Squarespace
// Same pattern as Wix/Shopify api/google/create_property.php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/credentials.php';
require_once __DIR__ . '/../../includes/google/get_account.php';
require_once __DIR__ . '/../../includes/google/token_manager.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ==========================================================
   READ INPUT
========================================================== */
$data = json_decode(file_get_contents('php://input'), true) ?: [];

$instanceId = $data['instanceId'] ?? $_SESSION['instanceid'] ?? null;
$domain     = $data['domain'] ?? null;

if (!$instanceId || !$domain) {
    echo json_encode(['success' => false, 'error' => 'missing_parameters']);
    exit;
}

/* ==========================================================
   NORMALIZE PROPERTY (URL-PREFIX)
========================================================== */
$domain = strtolower(trim($domain));

if (!preg_match('~^https?://~i', $domain)) {
    $domain = 'https://' . $domain;
}

$property = rtrim($domain, '/') . '/';

/* ==========================================================
   LOAD GOOGLE ACCOUNT
========================================================== */
$google = getGoogleAccountByUser($instanceId);

if (
    !$google ||
    $google['connected'] != 1 ||
    (empty($google['access_token']) && empty($google['refresh_token']))
) {
    echo json_encode(['success' => false, 'error' => 'account_needs_reconnect']);
    exit;
}

/* ==========================================================
   ENSURE ACCESS TOKEN
========================================================== */
$accountId   = (int) $google['id'];
$tokenResult = ensureAccessToken($accountId, 120);
$accessToken = $tokenResult['access_token'] ?? $google['access_token'];

if (empty($accessToken)) {
    echo json_encode(['success' => false, 'error' => 'account_needs_reconnect']);
    exit;
}

/* ==========================================================
   CREATE URL-PREFIX PROPERTY
========================================================== */
$apiUrl = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($property);

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
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['success' => false, 'error' => 'network_error', 'message' => $curlErr]);
    exit;
}

/* ==========================================================
   AUTH FAILURE
========================================================== */
if ($http === 401 || $http === 403) {
    echo json_encode(['success' => false, 'error' => 'account_needs_reconnect']);
    exit;
}

/* ==========================================================
   SUCCESS / ALREADY EXISTS
========================================================== */
if ($http >= 200 && $http < 300) {
    echo json_encode(['success' => true, 'property' => $property]);
    exit;
}

if ($http === 409) {
    echo json_encode(['success' => true, 'property' => $property, 'note' => 'already_exists']);
    exit;
}

/* ==========================================================
   FALLBACK ERROR
========================================================== */
echo json_encode([
    'success' => false,
    'error'   => 'google_api_error',
    'http'    => $http,
    'message' => substr((string) $response, 0, 200),
]);
exit;
