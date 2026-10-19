<?php
// includes/db.php — shared PDO handle (config.php already connects).
// Kept for family parity: pages include either file; this guard makes
// a double include harmless instead of opening a second connection.

declare(strict_types=1);

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require_once __DIR__ . '/config.php';
}
