<?php
// admin/our-apps.php — Other apps page
declare(strict_types=1);

$title = 'Our Apps';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core/AuthController.php';
require_once __DIR__ . '/core/OtherAppsManager.php';

AdminAuthController::requireAuth();

$apps = OtherAppsManager::getAll();
?>
<?php include __DIR__ . '/../partials/layouts/layoutTop.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold">Our Other Apps</h1>
</div>
<table class="w-full text-left border-collapse">
    <thead>
        <tr class="border-b">
            <th class="px-4 py-2">App Name</th>
            <th class="px-4 py-2">Platform</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($apps as $app): ?>
        <tr class="border-b">
            <td class="px-4 py-2"><?= htmlspecialchars($app['name']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($app['platform']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../partials/layouts/layoutBottom.php'; ?>
