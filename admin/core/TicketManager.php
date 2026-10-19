<?php
// admin/core/TicketManager.php — Ticket manager for Squarespace
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class TicketManager
{
    public static function getTickets(string $shopId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT id, ticket_number, subject, status, priority, created_at
            FROM tickets
            WHERE shop_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$shopId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createTicket(string $shopId, string $subject, string $message): bool
    {
        global $pdo;
        $ticketNumber = 'TKT-' . strtoupper(uniqid());
        $stmt = $pdo->prepare("
            INSERT INTO tickets (shop_id, ticket_number, subject, status, priority, created_at)
            VALUES (?, ?, ?, 'open', 'normal', NOW())
        ");
        return $stmt->execute([$shopId, $ticketNumber, $subject]);
    }
}
