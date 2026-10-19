<?php
// magic-login.php — Magic Login
declare(strict_types=1);
$title = "Magic Login";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="max-w-md mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Magic Login</h1><p class="text-neutral-600 dark:text-neutral-400">Sign in with a magic link sent to your email.</p></div>
    <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
        <form method="POST" class="space-y-4">
            <div><label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Email</label><input type="email" name="email" required placeholder="you@example.com" class="w-full px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-cstm-primary"></div>
            <button type="submit" class="btn btn-cstm-primary w-full px-6 py-2">Send Magic Link</button>
        </form>
        <p class="mt-4 text-center text-sm text-neutral-600 dark:text-neutral-400">Already have an account? <a href="sign-in.php" class="text-cstm-primary hover:underline">Sign in</a></p>
    </div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

