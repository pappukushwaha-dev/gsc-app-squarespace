<?php
require_once __DIR__ . '/init_client.php';

header("Content-Type: application/json");

session_start();

try {
    $client = google_client(); // loads credentials from DB

    // ---------------------------------------------------
    // 🔹 Resolve shop_id (BigCommerce store_hash)
    // ---------------------------------------------------
    $shopId =
        $_SESSION['shop_id']
        ?? $_GET['shopId']
        ?? null;

    if (!$shopId) {
        echo json_encode([
            'success' => false,
            'error'   => 'Missing instance ID'
        ]);
        exit;
    }

    // ---------------------------------------------------
    // 🔹 Pass shop_id via OAuth state
    // ---------------------------------------------------
$_SESSION['google_oauth_instance'] = $shopId;
$client->setState($shopId);

    // ---------------------------------------------------
    // Google OAuth Settings
    // ---------------------------------------------------
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->setApprovalPrompt('force');
    $client->setIncludeGrantedScopes(true);

    $authUrl = $client->createAuthUrl();

    echo json_encode([
        'success' => true,
        'authUrl' => $authUrl
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
