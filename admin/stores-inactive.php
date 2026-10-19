<?php
// admin/stores-inactive.php — Inactive Stores

declare(strict_types=1);

$title = "Inactive Stores";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/credentials.php";

if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) {
    header("Location: ../sign-in.php");
    exit;
}

$stores = [];
$stmt = $pdo->prepare("
    SELECT instance_id, site_url, site_display_name, domain, event_type, created_at
    FROM SquarespaceSite
    WHERE is_active = 0
    ORDER BY created_at DESC
");
$stmt->execute();
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
?>
<?php include __DIR__ . "/../partials/layouts/layoutTop.php"; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Inactive Stores</h1>
    <p class="text-neutral-600 dark:text-neutral-400">Inactive Squarespace sites.</p>
</div>

<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white">Inactive Stores (<?= count($stores) ?>)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Site ID</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Site URL</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Display Name</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Domain</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Event</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stores)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">No inactive stores found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($stores as $store): ?>
                <tr class="border-b border-neutral-100 dark:border-neutral-700">
                    <td class="px-6 py-4 text-sm font-mono text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($store["instance_id"] ?? "") ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($store["site_url"] ?? "") ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($store["site_display_name"] ?? "-") ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($store["domain"] ?? "-") ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400"><?= htmlspecialchars($store["event_type"] ?? "-") ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400"><?= htmlspecialchars($store["created_at"] ?? "") ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . "/../partials/layouts/layoutBottom.php"; ?>

