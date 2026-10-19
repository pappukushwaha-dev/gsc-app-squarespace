<?php
// admin/core/UpdateGeneralSettings.php — General settings updater for Squarespace
declare(strict_types=1);

require_once __DIR__ . "/../../includes/config.php";

class UpdateGeneralSettings
{
    public static function update(array $settings): bool
    {
        global $pdo;
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO admin_settings (key_name, value, created_at, updated_at)
                VALUES (?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()
            ");
            $stmt->execute([$key, $value]);
        }
        return true;
    }

    public static function getAll(): array
    {
        global $pdo;
        $stmt = $pdo->query("SELECT key_name, value FROM admin_settings");
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row["key_name"]] = $row["value"];
        }
        return $result;
    }
}

