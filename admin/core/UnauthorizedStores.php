<?php
// admin/core/UnauthorizedStores.php — Unauthorized stores manager for Squarespace
declare(strict_types=1);

require_once __DIR__ . "/../../includes/config.php";

class UnauthorizedStores
{
    public static function getUnauthorizedStores(): array
    {
        global $pdo;
        $stmt = $pdo->query("
            SELECT s.instance_id, s.site_url, s.site_display_name, s.created_at
            FROM SquarespaceSite s
            LEFT JOIN app_subscriptions a ON a.instance_id = s.instance_id
            WHERE a.status != 'active' OR a.status IS NULL
            ORDER BY s.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUnauthorizedCount(): int
    {
        global $pdo;
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT s.instance_id)
            FROM SquarespaceSite s
            LEFT JOIN app_subscriptions a ON a.instance_id = s.instance_id
            WHERE a.status != 'active' OR a.status IS NULL
        ");
        return (int) $stmt->fetchColumn();
    }
}

