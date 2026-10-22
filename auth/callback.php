<?php
/* ============================================================
   Squarespace app — Google OAuth callback
   Google redirects here with ?code= & ?state= (state = shop_id,
   set by connect_google.php). We exchange the code at Google's
   token endpoint, save access + refresh tokens, mark the store
   connected, and send the user back to the setup wizard.

   (The previous version of this file was an Ecwid callback
   copy-paste: it exchanged the code at my.ecwid.com, fetched the
   store profile from app.ecwid.com, wrote to columns that do not
   exist on SquarespaceSite, and never saved the refresh_token —
   Google connect could never work.)
============================================================ */

ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED);

session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';

/* ==========================================================
   1. Params — Google returns ?code= & ?state= (state = shop_id)
========================================================== */

$code   = $_GET['code'] ?? '';
$shopId = $_GET['state'] ?? '';

if ($code === '' || $shopId === '') {
    http_response_code(400);
    exit('Missing authorization parameters.');
}

/* ==========================================================
   2. Google app credentials (google_settings — admin panel)
========================================================== */

$stmt = $pdo->prepare('SELECT client_id, client_secret, redirect_uri FROM google_settings LIMIT 1');
$stmt->execute();
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app || empty($app['client_id']) || empty($app['client_secret']) || empty($app['redirect_uri'])) {
    http_response_code(500);
    exit('Google credentials not configured. Set them in the admin panel first.');
}

/* client_secret is stored AES-encrypted with ENCRYPTION_KEY */
$client_secret = decrypt($app['client_secret'], ENCRYPTION_KEY);
if ($client_secret === false || $client_secret === null || $client_secret === '') {
    http_response_code(500);
    exit('Client secret could not be decrypted.');
}

/* ==========================================================
   3. Exchange the authorization code at Google's endpoint
========================================================== */

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'client_id'     => $app['client_id'],
        'client_secret' => $client_secret,
        'code'          => $code,
        'redirect_uri'  => $app['redirect_uri'],
        'grant_type'    => 'authorization_code',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
]);
$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$token = json_decode((string) $response, true);
if ($httpCode !== 200 || empty($token['access_token'])) {
    http_response_code(500);
    exit('Token exchange failed: ' . substr((string) ($token['error_description'] ?? $response), 0, 200));
}

$accessToken  = $token['access_token'];
/* offline access: Google returns refresh_token on the first consent;
   on re-consents it may omit it, so keep any stored value. */
$refreshToken = $token['refresh_token'] ?? null;
$expiresIn    = (int) ($token['expires_in'] ?? 3600);
$expiresAt    = date('Y-m-d H:i:s', time() + $expiresIn);

/* ==========================================================
   4. Connected Google account email (for display/MCP)
========================================================== */

$googleEmail = null;
$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer $accessToken"],
    CURLOPT_TIMEOUT        => 15,
]);
$ui = json_decode((string) curl_exec($ch), true);
curl_close($ch);
if (is_array($ui) && !empty($ui['email'])) {
    $googleEmail = (string) $ui['email'];
}

/* ==========================================================
   5. Persist google_accounts (the app + MCP read this)
========================================================== */

$chk = $pdo->prepare('SELECT id FROM google_accounts WHERE shop_id = :sid LIMIT 1');
$chk->execute([':sid' => $shopId]);
$exists = $chk->fetchColumn();

if ($exists) {
    $upd = $pdo->prepare("
        UPDATE google_accounts
        SET access_token    = :at,
            refresh_token   = COALESCE(NULLIF(:rt, ''), refresh_token),
            email           = COALESCE(:em, email),
            connected       = 1,
            token_expires_at = :exp,
            updated_at      = NOW()
        WHERE shop_id = :sid
    ");
    $upd->execute([
        ':at'  => $accessToken,
        ':rt'  => $refreshToken,
        ':em'  => $googleEmail,
        ':exp' => $expiresAt,
        ':sid' => $shopId,
    ]);
} else {
    $ins = $pdo->prepare("
        INSERT INTO google_accounts
            (shop_id, email, access_token, refresh_token, connected, token_expires_at, created_at, updated_at)
        VALUES
            (:sid, :em, :at, :rt, 1, :exp, NOW(), NOW())
    ");
    $ins->execute([
        ':sid' => $shopId,
        ':em'  => $googleEmail,
        ':at'  => $accessToken,
        ':rt'  => $refreshToken,
        ':exp' => $expiresAt,
    ]);
}

/* ==========================================================
   6. Mark the SquarespaceSite row installed/active
========================================================== */

$upd = $pdo->prepare("
    UPDATE SquarespaceSite
    SET access_token = :at,
        is_active    = 1,
        is_verified  = 1,
        event_type   = 'install',
        updated_at   = NOW()
    WHERE shop_id = :sid
");
$upd->execute([':at' => $accessToken, ':sid' => $shopId]);

/* ==========================================================
   7. Session + back to the setup wizard
========================================================== */

$_SESSION['shop_id'] = $shopId;

header('Location: setup-wizard.php');
exit;
