<?php
// admin/core/Stats.php — Stats for Squarespace dashboard
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class Stats
{
    public static function getOverviewStats(string $instanceId): array
    {
        global $pdo;

        $totalStores = (int) $pdo->query("SELECT COUNT(*) FROM SquarespaceSite")->fetchColumn();
        $activeStores = (int) $pdo->query("SELECT COUNT(*) FROM SquarespaceSite WHERE is_active = 1")->fetchColumn();
        $verifiedSites = (int) $pdo->query("SELECT COUNT(*) FROM gsc_domain_verifications WHERE verification_status = 'verified'")->fetchColumn();
        $googleConnected = (int) $pdo->query("SELECT COUNT(*) FROM google_accounts WHERE connected = 1")->fetchColumn();

        return [
            'total_stores' => $totalStores,
            'active_stores' => $activeStores,
            'verified_sites' => $verifiedSites,
            'google_connected' => $googleConnected,
        ];
    }
}
