<?php
// admin/core/StoreManagerold.php — Legacy store manager for Squarespace
declare(strict_types=1);

require_once __DIR__ . "/../../includes/config.php";

class StoreManagerold
{
    public static function getAllStoresOld(): array
    {
        global $pdo;
        $stmt = $pdo->query("
            SELECT instance_id, site_url, site_display_name, is_active, event_type, created_at
            FROM SquarespaceSite
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getStoreCount(): int
    {
        global $pdo;
        return (int) $pdo->query("SELECT COUNT(*) FROM SquarespaceSite")->fetchColumn();
    }
}

