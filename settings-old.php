<?php
// settings-old.php — Old Settings (Legacy)
declare(strict_types=1);
$title = "Settings (Legacy)";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) { header("Location: sign-in.php"); exit; }
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Settings (Legacy)</h1><p class="text-neutral-600 dark:text-neutral-400">Legacy settings page. Please use the main Settings page.</p></div>
<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-6"><strong>Notice:</strong> This is the old settings page. Some features may not work correctly.<a href="settings.php" class="text-yellow-700 underline ml-2">Go to Settings</a></div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

