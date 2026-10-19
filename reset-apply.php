<?php
// reset-apply.php — Reset / Apply Changes
declare(strict_types=1);
$title = "Reset Apply";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) { header("Location: sign-in.php"); exit; }
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = trim($_POST["action"] ?? "");
    if ($action === "reset") { $message = "Settings reset successfully."; }
    elseif ($action === "apply") { $message = "Changes applied successfully."; }
}
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="max-w-md mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Reset / Apply</h1><p class="text-neutral-600 dark:text-neutral-400">Reset or apply configuration changes.</p></div>
    <?php if ($message): ?><div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
        <form method="POST" class="space-y-4">
            <button type="submit" name="action" value="reset" class="btn btn-outline-primary w-full md:w-auto px-6 py-2">Reset Settings</button>
            <button type="submit" name="action" value="apply" class="btn btn-cstm-primary w-full md:w-auto px-6 py-2">Apply Changes</button>
        </form>
    </div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

