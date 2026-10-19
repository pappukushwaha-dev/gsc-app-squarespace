<?php
// admin/stores.php — Stores management page
declare(strict_types=1);

$title = 'Stores';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core/AuthController.php';
require_once __DIR__ . '/core/StoreManager.php';

AdminAuthController::requireAuth();

$stores = StoreManager::getAllStores();
?>
<?php include __DIR__ . '/../partials/layouts/layoutTopnew.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold">Stores</h1>
</div>
<table class="w-full text-left border-collapse">
    <thead>
        <tr class="border-b">
            <th class="px-4 py-2">Site ID</th>
            <th class="px-4 py-2">Site URL</th>
            <th class="px-4 py-2">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($stores as $store): ?>
        <tr class="border-b">
            <td class="px-4 py-2 font-mono text-sm"><?= htmlspecialchars($store['shop_id']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($store['site_url']) ?></td>
            <td class="px-4 py-2"><?= $store['is_active'] ? 'Active' : 'Inactive' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../partials/layouts/layoutBottom.php'; ?>
