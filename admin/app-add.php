<?php
// admin/app-add.php — Add new app/store
declare(strict_types=1);

$title = 'Add App';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core/AuthController.php';

AdminAuthController::requireAuth();
?>
<?php include __DIR__ . '/../partials/layouts/layoutTop.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold">Add New Store</h1>
</div>
<form class="max-w-md space-y-4" method="POST" action="../admin/core/StoreApi.php">
    <div>
        <label class="block text-sm font-medium mb-1">Site URL</label>
        <input type="url" name="site_url" required class="w-full px-4 py-2 border rounded dark:bg-neutral-700 dark:text-white">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Display Name</label>
        <input type="text" name="site_display_name" class="w-full px-4 py-2 border rounded dark:bg-neutral-700 dark:text-white">
    </div>
    <button type="submit" class="w-full py-3 bg-cstm-primary text-white rounded font-semibold hover:bg-blue-600">Add Store</button>
</form>
<?php include __DIR__ . '/../partials/layouts/layoutBottom.php'; ?>
