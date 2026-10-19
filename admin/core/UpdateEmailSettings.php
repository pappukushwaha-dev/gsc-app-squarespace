<?php
// admin/core/UpdateEmailSettings.php — Email settings updater for Squarespace
declare(strict_types=1);

require_once __DIR__ . "/../../includes/config.php";

class UpdateEmailSettings
{
    public static function update(string $key, string $value): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO admin_settings (key_name, value, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()
        ");
        return $stmt->execute([$key, $value]);
    }

    public static function get(string $key): ?string
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT value FROM admin_settings WHERE key_name = ? LIMIT 1
        ");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row["value"] : null;
    }
}

