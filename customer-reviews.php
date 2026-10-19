<?php
// customer-reviews.php — Customer Reviews
declare(strict_types=1);
$title = "Customer Reviews";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) { header("Location: sign-in.php"); exit; }
$reviews = [];
$stmt = $pdo->prepare("SELECT id, customer_name, rating, comment, created_at FROM customer_reviews ORDER BY created_at DESC LIMIT 50");
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Customer Reviews</h1><p class="text-neutral-600 dark:text-neutral-400">Customer feedback for Squarespace GSC app.</p></div>
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700"><h2 class="text-lg font-semibold text-neutral-800 dark:text-white">Reviews (<?= count($reviews) ?>)</h2></div>
    <div class="overflow-x-auto"><table class="w-full text-left"><thead><tr class="border-b border-neutral-200 dark:border-neutral-700"><th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Customer</th><th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Rating</th><th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Comment</th><th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Date</th></tr></thead><tbody>
        <?php if (empty($reviews)): ?><tr><td colspan="4" class="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">No reviews found.</td></tr>
        <?php else: ?>
        <?php foreach ($reviews as $review): ?><tr class="border-b border-neutral-100 dark:border-neutral-700"><td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($review["customer_name"] ?? "Anonymous") ?></td><td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= str_repeat("★", (int)($review["rating"] ?? 0)) . str_repeat("☆", 5 - (int)($review["rating"] ?? 0)) ?></td><td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($review["comment"] ?? "") ?></td><td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400"><?= htmlspecialchars($review["created_at"] ?? "") ?></td></tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody></table></div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

