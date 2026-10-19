<?php
// admin/core/UpdateWixIntegration.php — Wix integration updater for Squarespace
declare(strict_types=1);

require_once __DIR__ . "/../../includes/config.php";

class UpdateWixIntegration
{
    public static function updateIntegration(string $instanceId, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE SquarespaceSite
            SET shop_data = ?, updated_at = NOW()
            WHERE instance_id = ?
        ");
        return $stmt->execute([json_encode($data), $instanceId]);
    }

    public static function getIntegration(string $instanceId): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT shop_data FROM SquarespaceSite WHERE instance_id = ? LIMIT 1
        ");
        $stmt->execute([$instanceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row["shop_data"])) {
            return json_decode($row["shop_data"], true);
        }
        return null;
    }
}

