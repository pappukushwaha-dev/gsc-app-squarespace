<?php
// admin/core/PaidStores.php — Paid stores manager for Squarespace
declare(strict_types=1);

require_once __DIR__ . "/../../includes/config.php";

class PaidStores
{
    public static function getPaidStores(): array
    {
        global $pdo;
        $stmt = $pdo->query("
            SELECT s.instance_id, s.site_url, s.site_display_name, s.created_at,
                   a.plan_name, a.status as sub_status
            FROM SquarespaceSite s
            LEFT JOIN app_subscriptions a ON a.instance_id = s.instance_id
            WHERE a.status = "active"
            ORDER BY s.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPaidStoreCount(): int
    {
        global $pdo;
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT s.instance_id)
            FROM SquarespaceSite s
            INNER JOIN app_subscriptions a ON a.instance_id = s.instance_id
            WHERE a.status = "active"
        ");
        return (int) $stmt->fetchColumn();
    }
}

