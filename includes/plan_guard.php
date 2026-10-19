<?php
// includes/plan_guard.php — trial + paid plan state for a shop.
// shop_id is the account key across this platform.

declare(strict_types=1);

if (!function_exists('checkTrialStatus')) {
    /** Active 24h/free trial state, if any. */
    function checkTrialStatus(string $shopId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT status, expires_on FROM app_free_trials
            WHERE shop_id = ? AND status = 'active'
            ORDER BY started_at DESC LIMIT 1
        ");
        $stmt->execute([$shopId]);
        $trial = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trial) {
            return ['active' => false, 'expired' => true];
        }

        $expired = strtotime($trial['expires_on']) < time();
        return ['active' => !$expired, 'expired' => $expired, 'expires_on' => $trial['expires_on']];
    }
}

if (!function_exists('getPaidPlan')) {
    /**
     * The shop's active paid subscription row, or null.
     * Active = status active AND (no expiry OR expiry in the future)
     * AND it is not the free plan.
     */
    function getPaidPlan(string $shopId): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT plan_name, billing_period, status, price, expires_on
            FROM app_subscriptions
            WHERE shop_id = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$shopId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sub) {
            return null;
        }

        $notFree  = strtolower((string) $sub['billing_period']) !== 'free';
        $isActive = strtolower((string) $sub['status']) === 'active';
        $notExpired = empty($sub['expires_on']) || strtotime((string) $sub['expires_on']) > time();

        return ($isActive && $notFree && $notExpired) ? $sub : null;
    }
}

if (!function_exists('isPremium')) {
    /** Premium = an active paid subscription OR an unexpired trial. */
    function isPremium(string $shopId): bool
    {
        if (getPaidPlan($shopId) !== null) {
            return true;
        }
        $trial = checkTrialStatus($shopId);
        return (bool) ($trial['active'] ?? false);
    }
}
