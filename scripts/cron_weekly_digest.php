<?php
// scripts/cron_weekly_digest.php — Weekly digest email cron
// Same pattern as Wix/Shopify cron_weekly_digest.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';

global $pdo;

echo "=== Squarespace Cron Weekly Digest ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Get active sites with digest_last_sent_at older than 6 days (or never sent)
$stmt = $pdo->prepare("
    SELECT ss.instance_id, ss.site_url, ss.site_display_name, ss.digest_last_sent_at
    FROM SquarespaceSite ss
    WHERE ss.is_active = 1
      AND ss.event_type = 'APPINSTALLED'
      AND (
        ss.digest_last_sent_at IS NULL
        OR ss.digest_last_sent_at < DATE_SUB(NOW(), INTERVAL 6 DAY)
      )
    LIMIT 100
");
$stmt->execute();
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($sites) . " sites needing weekly digest.\n\n";

foreach ($sites as $site) {
    $instanceId = $site['instance_id'];

    // Insert weekly digest email log
    $pdo->prepare("
        INSERT INTO encharge_email_logs
            (instance_id, email_type, website_url, payload, is_payload_generated, created_at)
        VALUES (?, 'weekly_digest', ?, ?, 'skipped', NOW())
    ")->execute([
        $instanceId,
        $site['site_url'],
        json_encode(['site_name' => $site['site_display_name']]),
    ]);

    // Update digest_last_sent_at
    $pdo->prepare("
        UPDATE SquarespaceSite
        SET digest_last_sent_at = NOW(), updated_at = NOW()
        WHERE instance_id = ?
    ")->execute([$instanceId]);

    echo "Weekly digest queued for $instanceId\n";
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
