<?php
// admin/core/TicketsAdmin.php — Admin ticket manager for Squarespace
declare(strict_types=1);

require_once __DIR__ . "/../../includes/config.php";

class TicketsAdmin
{
    public static function getAllTickets(): array
    {
        global $pdo;
        $stmt = $pdo->query("
            SELECT id, customer_name, email, subject, message, status, created_at
            FROM support_tickets
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getTicketById(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT * FROM support_tickets WHERE id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function updateTicketStatus(int $id, string $status): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?
        ");
        return $stmt->execute([$status, $id]);
    }

    public static function getTicketCount(): int
    {
        global $pdo;
        return (int) $pdo->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn();
    }
}

