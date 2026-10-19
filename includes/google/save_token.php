<?php
// includes/google/save_token.php — Save Google OAuth token
declare(strict_types=1);

if (!function_exists('saveGoogleToken')) {
    function saveGoogleToken(string $instanceId, string $accessToken, ?string $refreshToken, ?int $expiresIn): void
    {
        global $pdo;
        $expiresAt = $expiresIn ? date('Y-m-d H:i:s', time() + (int)$expiresIn) : null;

        $check = $pdo->prepare("SELECT id FROM google_accounts WHERE instance_id = ? LIMIT 1");
        $check->execute([$instanceId]);
        $exists = $check->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("
                UPDATE google_accounts
                SET access_token = ?, refresh_token = ?, token_expires_at = ?, connected = 1, updated_at = NOW()
                WHERE instance_id = ?
            ");
            $stmt->execute([$accessToken, $refreshToken, $expiresAt, $instanceId]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO google_accounts (instance_id, access_token, refresh_token, token_expires_at, connected, created_at)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$instanceId, $accessToken, $refreshToken, $expiresAt]);
        }
    }
}
