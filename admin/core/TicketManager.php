<?php
// admin/core/TicketManager.php — Ticket manager for Squarespace
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class TicketManager
{
    public static function getTickets(string $instanceId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT id, ticket_number, subject, status, priority, created_at
            FROM tickets
            WHERE instance_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createTicket(string $instanceId, string $subject, string $message): bool
    {
        global $pdo;
        $ticketNumber = 'TKT-' . strtoupper(uniqid());
        $stmt = $pdo->prepare("
            INSERT INTO tickets (instance_id, ticket_number, subject, status, priority, created_at)
            VALUES (?, ?, ?, 'open', 'normal', NOW())
        ");
        return $stmt->execute([$instanceId, $ticketNumber, $subject]);
    }
}
