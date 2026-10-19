<?php
// admin/email-templates.php — Email Templates List
// Same pattern as Shopify/Wix admin email-templates.php

declare(strict_types=1);

$title = "Email Templates";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/credentials.php";

// Auth check
if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) {
    header("Location: ../sign-in.php");
    exit;
}

$templates = [];
$stmt = $pdo->prepare("
    SELECT id, name, subject, category, created_at
    FROM email_templates
    ORDER BY created_at DESC
");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$templates) {
    $templates = [];
}

$categories = [];
$stmt = $pdo->prepare("SELECT DISTINCT category FROM email_templates ORDER BY category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<?php include __DIR__ . "/../partials/layouts/layoutTop.php"; ?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Email Templates</h1>
        <p class="text-neutral-600 dark:text-neutral-400">Manage email templates for Squarespace GSC app notifications.</p>
    </div>
    <a href="email-template-add.php" class="btn btn-cstm-primary px-4 py-2 text-sm">
        <iconify-icon icon="heroicons:plus" class="w-4 h-4 mr-2"></iconify-icon>
        Add Template
    </a>
</div>

<?php if (empty($templates)): ?>
<div class="bg-white dark:bg-neutral-800 p-8 rounded-lg border border-neutral-200 dark:border-neutral-700 text-center">
    <p class="text-neutral-500 dark:text-neutral-400">No email templates found. Create your first template.</p>
</div>
<?php else: ?>
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white">Templates</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Name</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Subject</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Category</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Created</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $tpl): ?>
                <tr class="border-b border-neutral-100 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200 font-medium"><?= htmlspecialchars($tpl["name"] ?? "-") ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($tpl["subject"] ?? "-") ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200">
                        <span class="px-2 py-1 bg-neutral-100 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200 rounded text-xs">
                            <?= htmlspecialchars($tpl["category"] ?? "general") ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400"><?= htmlspecialchars($tpl["created_at"] ?? "-") ?></td>
                    <td class="px-6 py-4 text-right">
                        <a href="email-template-edit.php?id=<?= (int)($tpl["id"] ?? 0) ?>" class="text-cstm-primary hover:underline text-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . "/../partials/layouts/layoutBottom.php"; ?>

