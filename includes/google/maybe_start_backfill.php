<?php
// includes/google/maybe_start_backfill.php — Start GSC data backfill after verification
// Same pattern as Wix/Shopify includes/google/maybe_start_backfill.php

declare(strict_types=1);

if (!function_exists('gsc_maybe_start_backfill')) {
    function gsc_maybe_start_backfill($pdo, string $instanceId): void
    {
        // Insert a backfill job so cron_payload_queue.php can pick it up
        try {
            $stmt = $pdo->prepare("
                INSERT INTO encharge_email_logs
                    (instance_id, email_type, website_url, payload, is_payload_generated, created_at)
                VALUES (?, 'backfill_started', '', '{}', 'skipped', NOW())
            ");
            $stmt->execute([$instanceId]);
        } catch (\Throwable $e) {
            error_log("[backfill] insert failed for instance $instanceId: " . $e->getMessage());
        }
    }
}

echo 'backfill_loaded';
