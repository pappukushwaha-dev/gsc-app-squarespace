<?php

declare(strict_types=1);

/**
 * magic-login.php — consumes a magic link.
 *
 * The emailed link (?token=...) is single-use and valid for 30 minutes.
 * A valid click marks the site verified, starts the dashboard session
 * (both session spellings — pages read different ones), and redirects
 * to the dashboard, which forwards to the setup wizard until setup is
 * finished.
 */

declare(strict_types=1);

$title    = 'Signing you in';
$subTitle = 'Magic link';

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/account_provision.php';

$token = trim((string) ($_GET['token'] ?? ''));
$row   = $token !== '' ? consumeMagicToken($pdo, $token) : null;

if (!$row) {
    http_response_code(400);
    $title = 'Link expired';
    require_once __DIR__ . '/partials/layouts/layoutTopnew.php';
    ?>
    <div class="min-h-[60vh] flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-2xl p-8">
            <iconify-icon icon="mdi:link-variant-off" class="text-4xl text-red-500"></iconify-icon>
            <h1 class="text-xl font-bold text-neutral-900 dark:text-white mt-3">This link has expired</h1>
            <p class="text-sm text-gray-500 mt-2">Magic links work once and expire after 30 minutes.</p>
            <a href="sign-in.php" class="inline-block mt-5 px-6 py-2.5 rounded-lg bg-cstm-primary text-white font-semibold">Send a new link</a>
        </div>
    </div>
    <?php require_once __DIR__ . '/partials/layouts/layoutBottom.php'; ?>
    <?php exit; ?>
<?php }

// mark the site verified the moment the owner proves email ownership
if ((int) ($row['is_verified'] ?? 0) !== 1) {
    $pdo->prepare("UPDATE SquarespaceSite SET is_verified = 1 WHERE shop_id = ?")
        ->execute([(string) $row['shop_id']]);
}

// dashboard session — both spellings, read and written across the app.
// user_id is the session key the dashboard's auth check looks for.
$_SESSION['user_id']     = (string) $row['shop_id'];
$_SESSION['shop_id']  = (string) $row['shop_id'];
$_SESSION['email']       = (string) ($row['email'] ?? '');
$_SESSION['domain']      = (string) ($row['domain'] ?? '');

header('Location: dashboard.php?shop_id=' . urlencode((string) $row['shop_id']));
exit;
