<?php
// admin/core/CancelPlanManager.php — Cancel plan manager
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class CancelPlanManager
{
    public static function cancelSubscription(string $instanceId): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE SquarespaceSite SET is_active = 0, event_type = 'CANCELLED', updated_at = NOW() WHERE instance_id = ?
        ");
        return $stmt->execute([$instanceId]);
    }
}
