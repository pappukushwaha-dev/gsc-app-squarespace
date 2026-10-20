<?php
// connect_google.php — start the Google OAuth flow for this shop.
// Credentials: google_settings table (admin panel) with .env fallback
// (both resolved inside includes/credentials.php).

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/credentials.php';

// config.php already started the session; no separate session manager
// is needed on the public side.

$shopId = (string) ($_SESSION['shop_id'] ?? $_GET['shop_id'] ?? '');
if ($shopId === '') {
    header('Location: sign-in.php');
    exit;
}

if ($googleClientId === '' || $googleClientSecret === '' || $googleRedirectUri === '') {
    http_response_code(500);
    echo 'Google sign-in is not configured yet. Set it in the admin panel (Settings -> Google) first.';
    exit;
}

$params = [
    'client_id'     => $googleClientId,
    'redirect_uri'  => $googleRedirectUri,
    'response_type' => 'code',
    'scope'         => GSC_SCOPES,
    'access_type'   => 'offline',
    'prompt'        => 'consent',
    'state'         => $shopId, // returned untouched by Google; callback maps tokens to this shop
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
