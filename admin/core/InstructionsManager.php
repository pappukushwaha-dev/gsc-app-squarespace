<?php
// admin/core/InstructionsManager.php — Instructions manager
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class InstructionsManager
{
    public static function getAll(): array
    {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM setup_instructions ORDER BY step_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
