<?php
// includes/plan_guard.php — Plan/trial guard
declare(strict_types=1);

if (!function_exists('checkTrialStatus')) {
    function checkTrialStatus(string $instanceId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT status, expires_on FROM app_free_trials
            WHERE instance_id = ? AND status = 'active'
            ORDER BY started_at DESC LIMIT 1
        ");
        $stmt->execute([$instanceId]);
        $trial = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trial) {
            return ['active' => false, 'expired' => true];
        }

        $expired = strtotime($trial['expires_on']) < time();
        return ['active' => !$expired, 'expired' => $expired, 'expires_on' => $trial['expires_on']];
    }
}
