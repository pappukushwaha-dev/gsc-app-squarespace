<?php
// admin/core/ReviewManager.php — Review manager
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class ReviewManager
{
    public static function getAll(): array
    {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
