<?php
// admin/store-view.php — View single store details
declare(strict_types=1);

$title = 'Store View';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core/AuthController.php';
require_once __DIR__ . '/core/StoreManager.php';

AdminAuthController::requireAuth();

$instanceId = $_GET['id'] ?? null;
$store = $instanceId ? StoreManager::getStoreByInstance($instanceId) : null;
?>
<?php include __DIR__ . '/../partials/layouts/layoutTop.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold">Store Details</h1>
</div>
<?php if ($store): ?>
<div class="max-w-md bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
    <p class="font-bold">Site ID: <?= htmlspecialchars($store['instance_id']) ?></p>
    <p class="font-bold">Site URL: <?= htmlspecialchars($store['site_url']) ?></p>
    <p class="font-bold">Display Name: <?= htmlspecialchars($store['site_display_name'] ?? '-') ?></p>
    <p class="font-bold">Status: <?= $store['is_active'] ? 'Active' : 'Inactive' ?></p>
</div>
<?php else: ?>
<div class="max-w-md bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
    <p class="text-red-600 dark:text-red-400">Store not found.</p>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../partials/layouts/layoutBottom.php'; ?>
