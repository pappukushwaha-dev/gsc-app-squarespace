<?php
require_once __DIR__ . '/includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* --------------------------------
   Get shop_id (authoritative)
-------------------------------- */
$shopId =
    $_SESSION['shop_id']
    ?? $_SESSION['shop_id']
    ?? null;

if (!$shopId) {
    header("Location: sign-in.php");
    exit;
}

/* --------------------------------
   Get shop domain from DB
-------------------------------- */
$stmt = $pdo->prepare("
    SELECT domain AS shop_domain
    FROM SquarespaceSite
    WHERE shop_id = ?
    LIMIT 1
");
$stmt->execute([$shopId]);
$shopDomain = $stmt->fetchColumn();

if (!$shopDomain) {
    http_response_code(404);
    exit('Shop not found');
}

/* --------------------------------
   Build Shopify Pricing URL
-------------------------------- */
$storeHandle = str_replace('.myshopify.com', '', $shopDomain);

/**
 * MUST match the App Handle
 * Shopify Partner Dashboard → App setup
 */
$appHandle = 'google-search-console-1';

$pricingUrl = "https://admin.shopify.com/store/{$storeHandle}/charges/{$appHandle}/pricing_plans";

/* --------------------------------
   Redirect
-------------------------------- */
header("Location: $pricingUrl");
exit;
