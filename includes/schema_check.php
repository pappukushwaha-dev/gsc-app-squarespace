<?php
// includes/schema_check.php — Schema check utility
declare(strict_types=1);

if (!function_exists('checkSchemaTable')) {
    function checkSchemaTable(string $tableName): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        ");
        $stmt->execute([$tableName]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
