<?php
// scripts/refresh_all_tokens.php — Refresh Google access tokens for all Squarespace sites
// Same pattern as Wix/Shopify scripts/refresh_all_tokens.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';
require_once __DIR__ . '/../includes/google/get_account.php';
require_once __DIR__ . '/../includes/google/token_manager.php';

global $pdo;

echo "=== Squarespace Refresh All Tokens ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Get all google_accounts for Squarespace
$stmt = $pdo->query("
    SELECT id, instance_id FROM google_accounts
    WHERE connected = 1
");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($accounts) . " Google accounts.\n\n";

$refreshed = 0;
$failed = 0;

foreach ($accounts as $account) {
    $accountId = (int) $account['id'];
    $instanceId = $account['instance_id'];

    $result = ensureAccessToken($accountId, 120);

    if (!empty($result['access_token'])) {
        $refreshed++;
        echo "✓ Refreshed token for $instanceId\n";
    } else {
        $failed++;
        echo "✗ Failed to refresh token for $instanceId: " . $result['reason'] . "\n";
    }
}

echo "\nSummary: $refreshed refreshed, $failed failed.\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
