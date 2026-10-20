<?php
declare(strict_types=1);

/**
 * Shopify replacement for getWixSiteByInstance
 * Keeps SAME return structure
 */
function getSquarespaceSiteByInstance(string $shopId): ?array
{
    if ($shopId === '') {
        return null;
    }

    // USE PDO FROM GLOBAL SCOPE (already loaded)
    global $pdo;

    if (!isset($pdo) || !$pdo instanceof PDO) {
        error_log('❌ PDO not available in getSquarespaceSiteByInstance');
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                shop_id,
                domain,
                site_display_name AS shop_name,
                owner_email       AS email
            FROM SquarespaceSite
            WHERE shop_id = ?
              AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$shopId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            error_log('❌ SquarespaceSite not found for instance: ' . $shopId);
            return null;
        }

        return [
            'shop_id' => $row['shop_id'],
           'site_url' => 'https://' . rtrim($row['domain'], '/') . '/',

            'site_name'   => $row['shop_name'] ?? 'Shop',
            'email'       => $row['email'] ?? null,
            'platform'    => 'shopify',
        ];

    } catch (Throwable $e) {
        error_log('❌ getSquarespaceSiteByInstance error: ' . $e->getMessage());
        return null;
    }
}
