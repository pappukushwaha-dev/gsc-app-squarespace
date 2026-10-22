<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/config.php';

/*
|--------------------------------------------------------------------------
| Entry point
|--------------------------------------------------------------------------
| Logged-in shops go straight to the dashboard; everyone else lands on
| sign-in. (This file previously held a copy-pasted Shopify webhook
| router that 401'd every visit.)
|--------------------------------------------------------------------------
*/

$shopId = $_SESSION['shop_id'] ?? $_GET['shop_id'] ?? null;

if (!$shopId) {
    header('Location: sign-in.php');
    exit;
}

header('Location: dashboard.php?shop_id=' . urlencode((string) $shopId));
exit;
