<?php
// admin/core/InactiveStores.php — Inactive stores manager for Squarespace
declare(strict_types=1);

require_once __DIR__ . "/../../includes/config.php";

class InactiveStores
{
    public static function getInactiveStores(): array
    {
        global $pdo;
        $stmt = $pdo->query("
            SELECT instance_id, site_url, site_display_name, domain, event_type, created_at
            FROM SquarespaceSite
            WHERE is_active = 0
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getInactiveStoreCount(): int
    {
        global $pdo;
        return (int) $pdo->query("SELECT COUNT(*) FROM SquarespaceSite WHERE is_active = 0")->fetchColumn();
    }
}

