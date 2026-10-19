<?php
// admin/test-stores.php — Test stores page
declare(strict_types=1);

$title = 'Test Stores';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core/AuthController.php';

AdminAuthController::requireAuth();
?>
<?php include __DIR__ . '/../partials/layouts/layoutTop.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold">Test Stores</h1>
</div>
<div class="max-w-md bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
    <p class="text-neutral-600 dark:text-neutral-400">Test stores management comes here.</p>
</div>
<?php include __DIR__ . '/../partials/layouts/layoutBottom.php'; ?>
