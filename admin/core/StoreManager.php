<?php
// admin/core/StoreManager.php — Store manager for Squarespace
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class StoreManager
{
    public static function getAllStores(): array
    {
        global $pdo;
        $stmt = $pdo->query("
            SELECT instance_id, site_url, site_display_name, is_active, event_type, created_at
            FROM SquarespaceSite
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getStoreByInstance(string $instanceId): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT * FROM SquarespaceSite WHERE instance_id = ? LIMIT 1
        ");
        $stmt->execute([$instanceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function updateStoreStatus(string $instanceId, bool $isActive): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE SquarespaceSite SET is_active = ?, updated_at = NOW() WHERE instance_id = ?
        ");
        return $stmt->execute([$isActive ? 1 : 0, $instanceId]);
    }
}
