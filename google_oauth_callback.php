<?php
// google_oauth_callback.php — Google OAuth callback for Squarespace
// Same pattern as BigCommerce/Wix google_oauth_callback.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/credentials.php';

global $pdo;

/* ==========================================================
   1. Resolve INSTANCE ID from OAuth state (Squarespace)
========================================================== */
$instanceId = $_GET['state'] ?? null;

if (!$instanceId) {
    die('Missing instance parameter.');
}

$_SESSION['instance_id'] = $instanceId;

/* ==========================================================
   2. Validate OAuth code
========================================================== */
if (!isset($_GET['code'])) {
    die("Missing 'code' parameter.");
}

$code = $_GET['code'];

/* ==========================================================
   3. Load Google credentials from DB
========================================================== */
$stmt = $pdo->prepare("SELECT client_id, client_secret, redirect_uri FROM google_settings LIMIT 1");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Google credentials missing.");
}

$clientId        = $row['client_id'];
$clientSecretEnc = $row['client_secret'];
$redirectUri     = $row['redirect_uri'];

/* ==========================================================
   4. Decrypt client secret
========================================================== */
$clientSecret = null;

if (!empty($clientSecretEnc)) {
    if (!defined('ENCRYPTION_KEY')) {
        $clientSecret = $clientSecretEnc;
    } else {
        $decrypted = decrypt($clientSecretEnc, ENCRYPTION_KEY);
        if ($decrypted !== false && $decrypted !== null && $decrypted !== '') {
            $clientSecret = $decrypted;
        } else {
            if (strpos($clientSecretEnc, 'GOCSPX-') === 0 || strlen($clientSecretEnc) > 20) {
                $clientSecret = $clientSecretEnc;
            } else {
                die("Failed to decrypt client secret.");
            }
        }
    }
}

/* ==========================================================
   5. Exchange code for tokens
========================================================== */
$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

try {
    $token = $client->fetchAccessTokenWithAuthCode($code);
} catch (Exception $e) {
    die("Token exchange failed: " . $e->getMessage());
}

if (isset($token['error'])) {
    die("Token error: " . $token['error']);
}

/* ==========================================================
   6. Save tokens to google_accounts table
========================================================== */
$accessToken  = $token['access_token'] ?? null;
$refreshToken = $token['refresh_token'] ?? null;
$expiresIn    = $token['expires_in'] ?? null;
$expiresAt    = $expiresIn ? date('Y-m-d H:i:s', time() + (int)$expiresIn) : null;

// Check if google_accounts record exists for this instance
$check = $pdo->prepare("SELECT id FROM google_accounts WHERE instance_id = :iid LIMIT 1");
$check->execute([':iid' => $instanceId]);
$exists = $check->fetchColumn();

if ($exists) {
    $stmt = $pdo->prepare("
        UPDATE google_accounts
        SET access_token = :at,
            refresh_token = :rt,
            token_expires_at = :exp,
            connected = 1,
            updated_at = NOW()
        WHERE instance_id = :iid
    ");
    $stmt->execute([
        ':at'  => $accessToken,
        ':rt'  => $refreshToken,
        ':exp' => $expiresAt,
        ':iid' => $instanceId,
    ]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO google_accounts
            (instance_id, access_token, refresh_token, token_expires_at, connected, created_at)
        VALUES (:iid, :at, :rt, :exp, 1, NOW())
    ");
    $stmt->execute([
        ':iid' => $instanceId,
        ':at'  => $accessToken,
        ':rt'  => $refreshToken,
        ':exp' => $expiresAt,
    ]);
}

$_SESSION['google_connected'] = true;

header('Location: setup-wizard.php?step=2');
exit;
