<?php
// admin/ticket-detail.php — Ticket Detail

declare(strict_types=1);

$title = "Ticket Detail";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/credentials.php";

if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) {
    header("Location: ../sign-in.php");
    exit;
}

$id = (int)($_GET["id"] ?? 0);
$ticket = null;
$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header("Location: tickets.php?error=not_found");
    exit;
}
?>
<?php include __DIR__ . "/../partials/layouts/layoutTop.php"; ?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Ticket Detail #<?= $id ?></h1>
        <p class="text-neutral-600 dark:text-neutral-400">Manage support ticket details.</p>
    </div>

    <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-neutral-800 dark:text-white mb-2">Ticket Information</h2>
            <table class="w-full text-left">
                <tr>
                    <td class="w-1/4 text-neutral-500 dark:text-neutral-400 text-sm font-medium">Subject</td>
                    <td class="text-neutral-800 dark:text-neutral-200 text-sm"><?= htmlspecialchars($ticket["subject"] ?? "") ?></td>
                </tr>
                <tr>
                    <td class="text-neutral-500 dark:text-neutral-400 text-sm font-medium">Customer</td>
                    <td class="text-neutral-800 dark:text-neutral-200 text-sm"><?= htmlspecialchars($ticket["customer_name"] ?? $ticket["email"] ?? "") ?></td>
                </tr>
                <tr>
                    <td class="text-neutral-500 dark:text-neutral-400 text-sm font-medium">Email</td>
                    <td class="text-neutral-800 dark:text-neutral-200 text-sm"><?= htmlspecialchars($ticket["email"] ?? "") ?></td>
                </tr>
                <tr>
                    <td class="text-neutral-500 dark:text-neutral-400 text-sm font-medium">Status</td>
                    <td class="text-neutral-800 dark:text-neutral-200 text-sm">
                        <span class="px-2 py-1 rounded-full
                            <?= ($ticket["status"] ?? "") === "open" ? "bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-400" : "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-400" ?>
                            text-xs">
                            <?= ucfirst(htmlspecialchars($ticket["status"] ?? "open")) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="text-neutral-500 dark:text-neutral-400 text-sm font-medium">Platform</td>
                    <td class="text-neutral-800 dark:text-neutral-200 text-sm">Squarespace</td>
                </tr>
            </table>
        </div>

        <div class="mb-6">
            <h2 class="text-lg font-semibold text-neutral-800 dark:text-white mb-2">Message</h2>
            <div class="bg-neutral-50 dark:bg-neutral-800/50 p-4 rounded border border-neutral-200 dark:border-neutral-700">
                <?= nl2br(htmlspecialchars($ticket["message"] ?? "")) ?>
            </div>
        </div>

        <div class="flex justify-between">
            <a href="tickets.php" class="btn btn-outline-primary px-6 py-2">Back to Tickets</a>
            <a href="ticket-view.php?id=<?= $id ?>" class="btn btn-cstm-primary px-6 py-2">View Ticket</a>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../partials/layouts/layoutBottom.php"; ?>

