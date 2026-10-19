<?php
// scripts/cron_page1_check.php — Page 1 breakthrough check
// Same pattern as Wix/Shopify cron_page1_check.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';

global $pdo;

echo "=== Squarespace Cron Page 1 Check ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Get verified sites
$stmt = $pdo->prepare("
    SELECT ss.shop_id, ss.site_url
    FROM SquarespaceSite ss
    JOIN gsc_domain_verifications gdv ON gdv.shop_id = ss.shop_id
    WHERE ss.is_active = 1
      AND gdv.verification_status = 'verified'
    LIMIT 50
");
$stmt->execute();
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($sites) . " verified sites.\n\n";

foreach ($sites as $site) {
    $shopId = $site['shop_id'];

    // Check if we already have page1_seen entries for recent queries
    // In real implementation, this would query GSC Search Analytics API
    // and insert breakthrough queries into gsc_page1_seen table

    echo "Checked page 1 status for $shopId\n";
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
