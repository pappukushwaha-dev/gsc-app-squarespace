<?php
// admin/mail-logs.php — Mail Logs

declare(strict_types=1);

$title = "Mail Logs";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/credentials.php";

if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) {
    header("Location: ../sign-in.php");
    exit;
}

$logs = [];
$stmt = $pdo->prepare("
    SELECT id, recipient, subject, status, created_at
    FROM email_logs
    ORDER BY created_at DESC
    LIMIT 100
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
?>
<?php include __DIR__ . "/../partials/layouts/layoutTop.php"; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Mail Logs</h1>
    <p class="text-neutral-600 dark:text-neutral-400">Email sending history for Squarespace GSC app.</p>
</div>

<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white">Email Logs (<?= count($logs) ?>)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Recipient</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Subject</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Sent At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">No mail logs found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr class="border-b border-neutral-100 dark:border-neutral-700">
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($log["recipient"] ?? "") ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($log["subject"] ?? "") ?></td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1
                            <?= ($log["status"] ?? "") === "sent" ? "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-400" : "bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-400" ?>
                            rounded-full text-xs">
                            <?= ucfirst(htmlspecialchars($log["status"] ?? "unknown")) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400"><?= htmlspecialchars($log["created_at"] ?? "") ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . "/../partials/layouts/layoutBottom.php"; ?>

