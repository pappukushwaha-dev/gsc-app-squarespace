<?php
// admin/core/StoreApiold.php — Legacy store API for Squarespace
declare(strict_types=1);

require_once __DIR__ . "/../../includes/config.php";

class StoreApiold
{
    public static function getStoreApi(string $shopId): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT * FROM SquarespaceSite WHERE shop_id = ? LIMIT 1
        ");
        $stmt->execute([$shopId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

