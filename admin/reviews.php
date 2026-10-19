<?php
// admin/reviews.php — Reviews page
declare(strict_types=1);

$title = 'Reviews';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core/AuthController.php';
require_once __DIR__ . '/core/ReviewManager.php';

AdminAuthController::requireAuth();

$reviews = ReviewManager::getAll();
?>
<?php include __DIR__ . '/../partials/layouts/layoutTop.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold">Reviews</h1>
</div>
<table class="w-full text-left border-collapse">
    <thead>
        <tr class="border-b">
            <th class="px-4 py-2">User</th>
            <th class="px-4 py-2">Rating</th>
            <th class="px-4 py-2">Comment</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reviews as $review): ?>
        <tr class="border-b">
            <td class="px-4 py-2"><?= htmlspecialchars($review['user_name'] ?? 'Anonymous') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($review['rating'] ?? '0') ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($review['comment'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../partials/layouts/layoutBottom.php'; ?>
