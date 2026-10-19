<?php
// includes/google/get_account.php — Get Google account for Squarespace instance
// Same pattern as Wix/Shopify includes/google/get_account.php

declare(strict_types=1);

if (!function_exists('getGoogleAccountByUser')) {
    function getGoogleAccountByUser(string $instanceId): ?array
    {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT id, instance_id, access_token, refresh_token, token_expires_at, connected
            FROM google_accounts
            WHERE instance_id = ?
            LIMIT 1
        ");
        $stmt->execute([$instanceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $row;
    }
}

if (!function_exists('getGoogleAccountById')) {
    function getGoogleAccountById(int $accountId): ?array
    {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT id, instance_id, access_token, refresh_token, token_expires_at, connected
            FROM google_accounts
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$accountId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $row;
    }
}

echo 'get_account_loaded';
