<?php

class HighLevelTokenManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns a valid access token.
     * Automatically refreshes if expired (5 min buffer).
     */
    public function getValidAccessToken(string $shopId): ?string
    {
        $stmt = $this->pdo->prepare("
           SELECT access_token, refresh_token, expires_at, is_active
FROM SquarespaceSite 
WHERE shop_id = ?
LIMIT 1
        ");
        $stmt->execute([$shopId]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);

   if (!$site || empty($site['is_active'])) {
    return null;
}

        $accessToken  = $site['access_token']
            ? decrypt($site['access_token'], ENCRYPTION_KEY)
            : null;

        $refreshToken = $site['refresh_token']
            ? decrypt($site['refresh_token'], ENCRYPTION_KEY)
            : null;

        $expiresAt = $site['expires_at']
            ? strtotime($site['expires_at'])
            : null;

        // Still valid (5 minute buffer)
        if ($accessToken && $expiresAt && $expiresAt > time() + 300) {
            return $accessToken;
        }

        if (!$refreshToken) {
            return null;
        }

        return $this->refreshAccessToken($shopId);
    }

    /**
     * Concurrency-safe refresh with row locking.
     */
    private function refreshAccessToken(string $shopId): ?string
    {
        try {

            $this->pdo->beginTransaction();

            // Lock row to prevent concurrent refresh
            $stmt = $this->pdo->prepare("
                SELECT access_token, refresh_token, expires_at, is_active
FROM SquarespaceSite 
WHERE shop_id = ?
FOR UPDATE
            ");
            $stmt->execute([$shopId]);
            $site = $stmt->fetch(PDO::FETCH_ASSOC);

   if (!$site || empty($site['is_active'])) {
    $this->pdo->rollBack();
    return null;
}

            $accessToken  = $site['access_token']
                ? decrypt($site['access_token'], ENCRYPTION_KEY)
                : null;

            $refreshToken = $site['refresh_token']
                ? decrypt($site['refresh_token'], ENCRYPTION_KEY)
                : null;

            $expiresAt = $site['expires_at']
                ? strtotime($site['expires_at'])
                : null;

            // Double-check: maybe another request refreshed already
            if ($accessToken && $expiresAt && $expiresAt > time() + 300) {
                $this->pdo->commit();
                return $accessToken;
            }

            if (!$refreshToken) {
                $this->pdo->rollBack();
                return null;
            }

            $oauth = $this->getOAuth();
            $new   = $oauth->refreshToken($refreshToken);

            if (
                empty($new['access_token']) ||
                empty($new['refresh_token']) ||
                empty($new['expires_in'])
            ) {
                throw new Exception("Invalid refresh response from HighLevel.");
            }

            $newAccess  = encrypt($new['access_token'], ENCRYPTION_KEY);
            $newRefresh = encrypt($new['refresh_token'], ENCRYPTION_KEY);
            $newExpiry  = date(
                'Y-m-d H:i:s',
                time() + (int)$new['expires_in']
            );

            $stmt = $this->pdo->prepare("
                UPDATE SquarespaceSite 
                SET access_token = ?, 
                    refresh_token = ?, 
                    refresh_token_id = ?, 
                    expires_at = ?,
                    updated_at = NOW()
                WHERE shop_id = ?
            ");

            $stmt->execute([
                $newAccess,
                $newRefresh,
                $new['refreshTokenId'] ?? null,
                $newExpiry,
                $shopId
            ]);

            $this->pdo->commit();

            return $new['access_token'];

        } catch (Throwable $e) {

    if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
    }

    error_log("HighLevel Token Refresh Error: " . $e->getMessage());

    // Mark instance inactive if refresh fails
    try {
        $this->invalidateInstance($shopId);
    } catch (Throwable $ignored) {
        // avoid cascading failure
    }

    return null;
}
    }

    /**
     * Optional: mark instance inactive if refresh permanently fails.
     */
    private function invalidateInstance(string $shopId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE SquarespaceSite 
            SET is_active = 0,
                updated_at = NOW()
            WHERE shop_id = ?
        ");
        $stmt->execute([$shopId]);
    }

    /**
     * Returns OAuth client.
     */
    private function getOAuth(): HighLevelOAuth
    {
        $stmt = $this->pdo->query("
            SELECT client_id, client_secret 
            FROM ghl_settings 
            LIMIT 1
        ");

        $ghl = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ghl) {
            throw new Exception("GHL credentials missing.");
        }

        return new HighLevelOAuth(
            $ghl['client_id'],
            decrypt($ghl['client_secret'], ENCRYPTION_KEY)
        );
    }
}