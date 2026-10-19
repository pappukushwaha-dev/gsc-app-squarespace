<?php
// scripts/cron_payload_queue.php — Process email payload queue for Squarespace
// Same pattern as Wix/Shopify/BigCommerce cron_payload_queue.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';

global $pdo;

echo "=== Squarespace Cron Payload Queue ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Get pending payload items
$stmt = $pdo->prepare("
    SELECT id, shop_id, email_type, website_url, payload, payload_attempts
    FROM encharge_email_logs
    WHERE is_payload_generated IN ('no', 'skipped')
      AND payload_attempts < 5
    ORDER BY created_at ASC
    LIMIT 50
");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    echo "No pending payload items.\n";
    exit(0);
}

echo "Found " . count($items) << " items to process.\n\n";

foreach ($items as $item) {
    $id = $item['id'];
    $shopId = $item['shop_id'];
    
    // Mark as processing
    $pdo->prepare("
        UPDATE encharge_email_logs
        SET payload_attempts = payload_attempts + 1
        WHERE id = ?
    ")->execute([$id]);

    // Build payload (in real implementation, call Encharge API)
    $payload = json_encode([
        'shop_id' => $shopId,
        'email_type'  => $item['email_type'],
        'website_url' => $item['website_url'],
        'platform'    => 'squarespace',
        'generated_at'=> date('Y-m-d H:i:s'),
    ]);

    // Mark as generated (or failed)
    $pdo->prepare("
        UPDATE encharge_email_logs
        SET is_payload_generated = 'yes',
            payload = :payload,
            payload_done_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ")->execute([
        ':payload' => $payload,
        ':id'      => $id,
    ]);

    echo "Processed item #$id (instance: $shopId)\n";
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
