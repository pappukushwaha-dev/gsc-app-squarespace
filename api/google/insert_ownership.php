<?php
require_once __DIR__ . '/init_client.php';
require_once __DIR__ . '/../../includes/google/load_token.php';
require_once __DIR__ . '/../../lib/google/verification.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ==========================================================
   INSTANCE (SHOPIFY) - Match setup-wizard.php order
========================================================== */
$shopId = $_SESSION['shop_id']
    ?? $_SESSION['shop_id']
    ?? $_GET['shop_id']
    ?? $_GET['shopId']
    ?? null;

// Clean and validate instance ID
$shopId = $shopId ? trim((string)$shopId) : null;
$userId = getUserIdByInstance($shopId);

$domain = $_POST['domain'] ?? '';
$method = $_POST['method'] ?? 'META';

try {
    $client = google_init_client($userId);
    $done   = gsc_insert_ownership($client, $domain, $method);

    echo json_encode(['success' => $done]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
