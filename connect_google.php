<?php
// connect_google.php — Start Google OAuth for Squarespace
// Same pattern as BigCommerce/Wix connect_google.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/credentials.php';

$gscAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth';

$params = [
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => GSC_SCOPES,
    'access_type'   => 'offline',
    'prompt'        => 'consent',
    'state'         => $_SESSION['instanceid'] ?? '',
];

$authUrl = $gscAuthUrl . '?' . http_build_query($params);

$returnTo = isset($_GET['return']) ? $_GET['return'] : '/dashboard.php';
?>
<!doctype html>
<meta charset="utf-8">
<title>Connect Google — Squarespace</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; background: #f8fafc; }
    .card { max-width: 400px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    h1 { font-size: 1.5rem; margin: 0 0 8px 0; color: #111827; }
    p { color: #6b7280; margin: 0 0 24px 0; }
    .btn { display: inline-block; padding: 12px 24px; background: #3b5bfd; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; }
    .btn:hover { background: #2a4ed9; }
</style>
<div class="card">
    <h1>Connect Google Account</h1>
    <p>Authorize Google Search Console access for your Squarespace site.</p>
    <script>
        (function () {
            var url = <?= json_encode($authUrl) ?>;
            var w = window.open(url, "_blank", "width=520,height=680,noopener");
            if (!w) {
                window.top.location.href = url;
            } else {
                var i = setInterval(function () {
                    if (w.closed) {
                        clearInterval(i);
                        try { window.top.postMessage({type:"googleConnected"}, "*"); } catch(e){}
                        window.location.replace(<?= json_encode($returnTo) ?>);
                    }
                }, 700);
            }
        })();
    </script>
    <p>If you are not redirected automatically,
       <a href="<?= htmlspecialchars($authUrl) ?>" target="_blank" rel="noopener">click here</a>.
    </p>
</div>
