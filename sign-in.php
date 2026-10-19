<?php

declare(strict_types=1);

/**
 * sign-in.php — returning users sign in with their email.
 * A one-time magic link is emailed; no passwords anywhere.
 */

declare(strict_types=1);

$title    = 'Sign in';
$subTitle = 'Sign in';

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/account_provision.php';

$message     = '';
$messageType = 'info';
$sentTo      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        $instance = findInstanceByEmail($pdo, $email);
        if (!$instance) {
            $message = 'No dashboard found for this email. Register your Squarespace site first.';
            $messageType = 'error';
        } else {
            $token = createMagicToken($pdo, $email, (string) $instance['instance_id']);
            if (sendMagicLink($email, $token)) {
                $sentTo = $email;
            } else {
                $message = 'We could not send the email right now. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

require_once __DIR__ . '/partials/layouts/layoutTopnew.php';
?>

<div class="min-h-[70vh] flex items-center justify-center py-10 px-4">
    <div class="w-full max-w-md">
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 shadow-sm p-8">
            <div class="text-center mb-6">
                <iconify-icon icon="mdi:login-variant" class="text-4xl text-cstm-primary"></iconify-icon>
                <h1 class="text-xl font-bold text-neutral-900 dark:text-white mt-2">Sign in to your dashboard</h1>
                <p class="text-sm text-gray-500 mt-1">We email you a one-time sign-in link. No password.</p>
            </div>

            <?php if ($sentTo !== ''): ?>
                <div class="rounded-xl border border-green-200 bg-green-50 p-5 text-center">
                    <iconify-icon icon="mdi:email-check-outline" class="text-3xl text-green-600"></iconify-icon>
                    <p class="text-sm text-gray-700 mt-2">Sign-in link sent to <strong><?= htmlspecialchars($sentTo) ?></strong>.</p>
                </div>
            <?php else: ?>
                <?php if ($message !== ''): ?>
                    <div class="rounded-lg border <?= $messageType === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-blue-200 bg-blue-50 text-blue-700' ?> text-sm px-4 py-3 mb-4">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Email</label>
                        <input type="email" name="email" required
                               value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>"
                               placeholder="you@example.com"
                               class="w-full px-4 py-2.5 border border-neutral-300 dark:border-neutral-600 rounded-lg dark:bg-neutral-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-cstm-primary">
                    </div>
                    <button type="submit" class="w-full py-3 rounded-lg bg-cstm-primary text-white font-semibold hover:opacity-90 transition">
                        Email me a sign-in link
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-gray-500">
                    New here?
                    <a href="register.php" class="text-cstm-primary font-medium hover:underline">Register your Squarespace site</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layouts/layoutBottom.php'; ?>
