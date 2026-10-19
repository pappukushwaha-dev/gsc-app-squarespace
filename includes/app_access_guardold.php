<?php
declare(strict_types=1);

/**
 * App Access Guard
 * ----------------
 * Allows app access if ANY ACTIVE subscription exists
 * (including FREE plans).
 */

function requireAppAccess(PDO $pdo, string $shopId): void
{
    $stmt = $pdo->prepare("
        SELECT status
        FROM app_subscriptions
        WHERE shop_id = ?
        ORDER BY started_at DESC
        LIMIT 1
    ");
    $stmt->execute([$shopId]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    // ✅ FREE or PAID is OK — only status matters
    if ($sub && strtoupper($sub['status']) === 'ACTIVE') {
        return;
    }

    // ❌ No active subscription → send to pricing
    $shop = $_SESSION['shop'] ?? $_GET['shop'] ?? null;
    if ($shop) {
        $storeHandle = str_replace('.myshopify.com', '', $shop);
        $appHandle   = 'google-search-console-1';

        header("Location: https://admin.shopify.com/store/{$storeHandle}/charges/{$appHandle}/pricing_plans");
        exit;
    }

    exit('No active subscription');
}
