<?php
// includes/google/load_token.php — Load Google access token
declare(strict_types=1);

if (!function_exists('loadGoogleToken')) {
    function loadGoogleToken(string $shopId): ?string
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT access_token, token_expires_at FROM google_accounts WHERE shop_id = ? LIMIT 1
        ");
        $stmt->execute([$shopId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['access_token'])) {
            return null;
        }

        if ($row['token_expires_at'] && strtotime($row['token_expires_at']) < time()) {
            return null;
        }

        return $row['access_token'];
    }
}
