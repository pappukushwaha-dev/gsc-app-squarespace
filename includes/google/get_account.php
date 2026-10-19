<?php
declare(strict_types=1);

/**
 * Shopify-native Google account fetcher
 * Uses shop_id (NOT user / JWT)
 */
function getGoogleAccountByInstance(string $shopId): ?array
{
    if ($shopId === '') {
        return null;
    }

    require_once __DIR__ . '/../config.php';
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM google_accounts
            WHERE shop_id = :shop_id
            LIMIT 1
        ");

        $stmt->execute([
            ':shop_id' => $shopId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

return [
    'id'            => (int)$row['id'],
    'shop_id'   => (string)$row['shop_id'],
    'email'         => $row['email'] ?? null,
    'picture'       => $row['picture'] ?? null,
    'access_token'  => $row['access_token'] ?? null,
    'refresh_token' => $row['refresh_token'] ?? null,

    // ✅ FIX: correct expiry column
    'expires_at'    => $row['token_expires_at'] ?? null,

    // ✅ FIX: expose OAuth scopes
    'scope'         => $row['scope'] ?? null,

    'connected'     => (int)($row['connected'] ?? 1),
    'last_error'    => $row['last_error'] ?? null,
];


    } catch (Throwable $e) {
        error_log('getGoogleAccountByInstance error: ' . $e->getMessage());
        return null;
    }
}

/**
 * 🔁 BACKWARD COMPATIBILITY
 * Old code may still call this.
 * Do NOT remove until migration is complete.
 */
function getGoogleAccountByShop(string $shopId): ?array
{
    return getGoogleAccountByInstance($shopId);
}


function getGoogleAccountByUser(string $shopId): ?array
{
    if ($shopId === '') {
        return null;
    }

    require_once __DIR__ . '/../config.php';
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM google_accounts
            WHERE shop_id = :shop_id
            LIMIT 1
        ");

        $stmt->execute([
            ':shop_id' => $shopId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

return [
    'id'            => (int)$row['id'],
    'shop_id'   => (string)$row['shop_id'],
    'email'         => $row['email'] ?? null,
    'picture'       => $row['picture'] ?? null,
    'access_token'  => $row['access_token'] ?? null,
    'refresh_token' => $row['refresh_token'] ?? null,

    // ✅ FIX: correct expiry column
    'expires_at'    => $row['token_expires_at'] ?? null,

    // ✅ FIX: expose OAuth scopes
    'scope'         => $row['scope'] ?? null,

    'connected'     => (int)($row['connected'] ?? 1),
    'last_error'    => $row['last_error'] ?? null,
];


    } catch (Throwable $e) {
        error_log('getGoogleAccountByInstance error: ' . $e->getMessage());
        die('dd');
        return null;
    }
}