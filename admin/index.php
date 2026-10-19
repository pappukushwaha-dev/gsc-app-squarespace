<?php
// admin/index.php — Squarespace Admin Dashboard
// Same pattern as Wix/Shopify admin/index.php

declare(strict_types=1);

$title = 'Admin Dashboard';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';

// Auth check
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: ../sign-in.php');
    exit;
}

// Fetch stores list
$stores = [];
$stmt = $pdo->prepare("
    SELECT shop_id, site_url, site_display_name, is_active, event_type, created_at
    FROM SquarespaceSite
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute();
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch stats
$totalStores = $pdo->query("SELECT COUNT(*) FROM SquarespaceSite")->fetchColumn();
$activeStores = $pdo->query("SELECT COUNT(*) FROM SquarespaceSite WHERE is_active = 1")->fetchColumn();
$verifiedSites = $pdo->query("SELECT COUNT(*) FROM gsc_domain_verifications WHERE verification_status = 'verified'")->fetchColumn();
$googleConnected = $pdo->query("SELECT COUNT(*) FROM google_accounts WHERE connected = 1")->fetchColumn();
?>
<?php include __DIR__ . '/../partials/layouts/layoutTopnew.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Admin Dashboard</h1>
    <p class="text-neutral-600 dark:text-neutral-400">Manage your Squarespace GSC apps.</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Total Stores</div>
        <div class="text-3xl font-bold text-neutral-800 dark:text-white mt-1"><?= $totalStores ?></div>
    </div>
    <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Active Stores</div>
        <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1"><?= $activeStores ?></div>
    </div>
    <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Verified Sites</div>
        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?= $verifiedSites ?></div>
    </div>
    <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Google Connected</div>
        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-1"><?= $googleConnected ?></div>
    </div>
</div>

<!-- Stores Table -->
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white">Stores</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Site ID</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Site URL</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Display Name</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Event</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stores)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                        No stores found. Install the Squarespace app to get started.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($stores as $store): ?>
                <tr class="border-b border-neutral-100 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700/50">
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200 font-mono"><?= htmlspecialchars($store['shop_id'] ?? '') ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200">
                        <a href="<?= htmlspecialchars($store['site_url'] ?? '#') ?>" target="_blank" class="text-cstm-primary hover:underline"><?= htmlspecialchars($store['site_url'] ?? '') ?></a>
                    </td>
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($store['site_display_name'] ?? '-') ?></td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 rounded-full
                            <?= $store['is_active'] ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-400' ?>
                        ">
                            <?= $store['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400"><?= htmlspecialchars($store['event_type'] ?? '-') ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400"><?= htmlspecialchars($store['created_at'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../partials/layouts/layoutBottom.php'; ?>
