<?php
// includes/google/refresh_token.php — Refresh Google access token
declare(strict_types=1);

if (!function_exists('refreshGoogleToken')) {
    function refreshGoogleToken(string $instanceId): ?string
    {
        global $pdo;

        $account = getGoogleAccountByUser($instanceId);
        if (!$account || empty($account['refresh_token'])) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT client_id, client_secret FROM google_settings LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

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
                'refresh_token' => $account['refresh_token'],
                'grant_type'    => 'refresh_token',
            ]),
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $http     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = is_string($response) ? json_decode($response, true) : null;
        if ($http < 200 || $http >= 300 || !is_array($json) || empty($json['access_token'])) {
            return null;
        }

        $newAccessToken = $json['access_token'];
        $newExpiresAt   = isset($json['expires_in'])
            ? date('Y-m-d H:i:s', time() + (int) $json['expires_in'])
            : null;

        $pdo->prepare("
            UPDATE google_accounts
            SET access_token = ?, token_expires_at = ?, updated_at = NOW()
            WHERE instance_id = ?
        ")->execute([$newAccessToken, $newExpiresAt, $instanceId]);

        return $newAccessToken;
    }
}
