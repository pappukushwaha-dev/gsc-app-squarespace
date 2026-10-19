<?php
// admin/tickets.php — Tickets management page
declare(strict_types=1);

$title = 'Tickets';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core/AuthController.php';
require_once __DIR__ . '/core/TicketManager.php';

AdminAuthController::requireAuth();

$instanceId = $_SESSION['instanceid'] ?? 'default';
$tickets = TicketManager::getTickets($instanceId);
?>
<?php include __DIR__ . '/../partials/layouts/layoutTopnew.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold">Support Tickets</h1>
</div>
<table class="w-full text-left border-collapse">
    <thead>
        <tr class="border-b">
            <th class="px-4 py-2">Ticket #</th>
            <th class="px-4 py-2">Subject</th>
            <th class="px-4 py-2">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tickets as $ticket): ?>
        <tr class="border-b">
            <td class="px-4 py-2"><?= htmlspecialchars($ticket['ticket_number']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($ticket['subject']) ?></td>
            <td class="px-4 py-2"><?= ucfirst($ticket['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../partials/layouts/layoutBottom.php'; ?>
