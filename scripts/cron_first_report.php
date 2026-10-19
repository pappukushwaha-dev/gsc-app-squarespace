<?php
// scripts/cron_first_report.php — Send first report after verification
// Same pattern as Wix/Shopify cron_first_report.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';

global $pdo;

echo "=== Squarespace Cron First Report ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Find sites that were recently verified but haven't received first report
$stmt = $pdo->prepare("
    SELECT gdv.shop_id, gdv.site_url, ss.site_display_name
    FROM gsc_domain_verifications gdv
    JOIN SquarespaceSite ss ON ss.shop_id = gdv.shop_id
    WHERE gdv.verification_status = 'verified'
      AND gdv.verification_verified_at IS NOT NULL
    ORDER BY gdv.verification_verified_at DESC
    LIMIT 20
");
$stmt->execute();
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($sites) . " verified sites.\n\n";

foreach ($sites as $site) {
    $shopId = $site['shop_id'];
    $siteUrl    = $site['site_url'];
    $siteName   = $site['site_display_name'];

    // Check if first report already sent (simple check via encharge_email_logs)
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM encharge_email_logs
        WHERE shop_id = ? AND email_type = 'first_report'
        LIMIT 1
    ");
    $check->execute([$shopId]);
    $alreadySent = $check->fetchColumn() > 0;

    if ($alreadySent) {
        echo "Skipping $shopId — first report already sent.\n";
        continue;
    }

    // Insert first report email log
    $pdo->prepare("
        INSERT INTO encharge_email_logs
            (shop_id, email_type, website_url, payload, is_payload_generated, created_at)
        VALUES (?, 'first_report', ?, ?, 'skipped', NOW())
    ")->execute([
        $shopId,
        $siteUrl,
        json_encode(['site_name' => $siteName, 'site_url' => $siteUrl]),
    ]);

    echo "First report queued for $shopId ($siteName)\n";
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
