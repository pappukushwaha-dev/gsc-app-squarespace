<?php
// includes/google/token_manager.php — Ensure valid Google access token
// Same pattern as Wix/Shopify includes/google/token_manager.php

declare(strict_types=1);

if (!function_exists('ensureAccessToken')) {
    function ensureAccessToken(int $accountId, int $minValidSeconds = 120): array
    {
        global $pdo;

        $account = getGoogleAccountById($accountId);
        if (!$account) {
            return ['access_token' => null, 'reason' => 'account_not_found'];
        }

        $accessToken  = $account['access_token']  ?? null;
        $refreshToken = $account['refresh_token'] ?? null;
        $expiresAt    = $account['token_expires_at'] ?? null;

        // Check if token still valid
        if ($accessToken && $expiresAt) {
            $expiresTs = strtotime($expiresAt);
            if ($expiresTs && (time() + $minValidSeconds) < $expiresTs) {
                return ['access_token' => $accessToken, 'reason' => 'valid'];
            }
        }

        // Refresh if we have a refresh token
        if (!$refreshToken) {
            return ['access_token' => $accessToken, 'reason' => 'no_refresh_token'];
        }

        $stmt = $pdo->prepare("SELECT client_id, client_secret FROM google_settings LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['access_token' => null, 'reason' => 'google_settings_missing'];
        }

        $clientId     = $row['client_id'];
        $clientSecret = $row['client_secret'];
        if (defined('ENCRYPTION_KEY') && ENCRYPTION_KEY !== '') {
            $decrypted = decrypt($clientSecret, ENCRYPTION_KEY);
            if ($decrypted !== null && $decrypted !== '') {
                $clientSecret = $decrypted;
            }
        }

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS     => http_build_query([
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
            ]),
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $http     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = is_string($response) ? json_decode($response, true) : null;
        if ($http < 200 || $http >= 300 || !is_array($json) || empty($json['access_token'])) {
            return ['access_token' => null, 'reason' => 'refresh_failed_http_' . $http];
        }

        $newAccessToken = $json['access_token'];
        $newExpiresAt   = isset($json['expires_in'])
            ? date('Y-m-d H:i:s', time() + (int) $json['expires_in'])
            : null;

        try {
            $pdo->prepare("
                UPDATE google_accounts
                SET access_token = :at,
                    token_expires_at = :exp,
                    updated_at = NOW()
                WHERE id = :id
            ")->execute([
                ':at'  => $newAccessToken,
                ':exp' => $newExpiresAt,
                ':id'  => $accountId,
            ]);
        } catch (\Throwable) {
            // Refresh worked; persistence failure only costs us a re-refresh next call.
        }

        return ['access_token' => $newAccessToken, 'reason' => 'refreshed'];
    }
}

echo 'token_manager_loaded';
