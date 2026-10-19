<?php
// admin/ticket-view.php — View Ticket

declare(strict_types=1);

$title = "View Ticket";

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
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Ticket #<?= $id ?></h1>
            <p class="text-neutral-600 dark:text-neutral-400"><?= htmlspecialchars($ticket["subject"] ?? "") ?></p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-medium
            <?= ($ticket["status"] ?? "") === "open" ? "bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-400" : "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-400" ?>">
            <?= ucfirst(htmlspecialchars($ticket["status"] ?? "open")) ?>
        </span>
    </div>

    <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Customer</p>
                <p class="text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($ticket["customer_name"] ?? $ticket["email"] ?? "") ?></p>
            </div>
            <div>
                <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Email</p>
                <p class="text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($ticket["email"] ?? "") ?></p>
            </div>
            <div>
                <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Platform</p>
                <p class="text-neutral-800 dark:text-neutral-200">Squarespace</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Created</p>
                <p class="text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($ticket["created_at"] ?? "") ?></p>
            </div>
        </div>

        <div class="mb-6">
            <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase mb-2">Message</p>
            <div class="bg-neutral-50 dark:bg-neutral-800/50 p-4 rounded border border-neutral-200 dark:border-neutral-700">
                <?= nl2br(htmlspecialchars($ticket["message"] ?? "")) ?>
            </div>
        </div>

        <div class="flex justify-end">
            <a href="tickets.php" class="btn btn-outline-primary px-6 py-2">Back to Tickets</a>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../partials/layouts/layoutBottom.php"; ?>

