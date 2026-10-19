<?php
// admin/core/Faq.php — FAQ manager
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class Faq
{
    public static function getAll(): array
    {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM faq ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
