<?php
// admin/active-stores.php — Active stores list
declare(strict_types=1);

$title = 'Active Stores';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core/AuthController.php';
require_once __DIR__ . '/core/StoreManager.php';

AdminAuthController::requireAuth();

$stores = StoreManager::getAllStores();
$activeStores = array_filter($stores, fn($s) => !empty($s['is_active']));
?>
<?php include __DIR__ . '/../partials/layouts/layoutTop.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold">Active Stores</h1>
</div>
<table class="w-full text-left border-collapse">
    <thead>
        <tr class="border-b">
            <th class="px-4 py-2">Store ID</th>
            <th class="px-4 py-2">Site URL</th>
            <th class="px-4 py-2">Display Name</th>
            <th class="px-4 py-2">Created</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($activeStores as $store): ?>
        <tr class="border-b">
            <td class="px-4 py-2 font-mono text-sm"><?= htmlspecialchars($store['instance_id']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($store['site_url']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($store['site_display_name'] ?? '-') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($store['created_at'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../partials/layouts/layoutBottom.php'; ?>
