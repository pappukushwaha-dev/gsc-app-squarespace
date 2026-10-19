<?php

declare(strict_types=1);

/**
 * register.php — public registration.
 *
 * The platform has no marketplace app: this page IS the install flow.
 * A visitor submits their email + Squarespace site URL, we prove the
 * site really runs on Squarespace, create the instance, and email a
 * single-use magic link that signs them straight into the dashboard.
 */

declare(strict_types=1);

$title       = 'Connect your Squarespace site';
$subTitle    = 'Register';

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/account_provision.php';

$errorMessage = '';
$successEmail  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = strtolower(trim((string) ($_POST['email'] ?? '')));
    $website = trim((string) ($_POST['website'] ?? ''));

    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AccountProvisionException('Please enter a valid email address');
        }

        $domain   = normalizeDomain($website);
        $verified = squarespaceVerifySite($domain);
        $res      = provisionInstance($pdo, $email, $domain, $verified);

        $token = createMagicToken($pdo, $email, $res['instance_id']);
        if (!sendMagicLink($email, $token)) {
            throw new AccountProvisionException('We could not send the sign-in email right now. Please try again in a minute.');
        }

        $successEmail = $email;
    } catch (AccountProvisionException $e) {
        $errorMessage = $e->getMessage();
    } catch (Throwable $e) {
        error_log('register: ' . $e->getMessage());
        $errorMessage = 'Something went wrong on our side. Please try again.';
    }
}

require_once __DIR__ . '/partials/layouts/layoutTopnew.php';
?>

<div class="min-h-[70vh] flex items-center justify-center py-10 px-4">
    <div class="w-full max-w-lg">
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 shadow-sm p-8">
            <div class="text-center mb-6">
                <div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-cstm-primary-10 flex items-center justify-center">
                    <iconify-icon icon="arcticons:squarespace" class="text-3xl text-cstm-primary"></iconify-icon>
                </div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Connect your Squarespace site</h1>
                <p class="text-sm text-gray-500 mt-2">
                    Enter your email and your Squarespace website — we verify it, set up Google Search Console,
                    and sign you in. No password needed.
                </p>
            </div>

            <?php if ($successEmail !== ''): ?>
                <div class="rounded-xl border border-green-200 bg-green-50 p-5 text-center">
                    <iconify-icon icon="mdi:email-check-outline" class="text-3xl text-green-600"></iconify-icon>
                    <h2 class="font-bold text-green-700 mt-2">Check your email</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        We sent a sign-in link to <strong><?= htmlspecialchars($successEmail) ?></strong>.
                        It works once and expires in 30 minutes.
                    </p>
                </div>
            <?php else: ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3 mb-4">
                        <?= htmlspecialchars($errorMessage) ?>
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
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Squarespace website</label>
                        <input type="text" name="website" required
                               value="<?= htmlspecialchars((string) ($_POST['website'] ?? '')) ?>"
                               placeholder="yourstore.com"
                               class="w-full px-4 py-2.5 border border-neutral-300 dark:border-neutral-600 rounded-lg dark:bg-neutral-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-cstm-primary">
                        <p class="text-xs text-gray-500 mt-1">We check that this site is built on Squarespace before creating your dashboard.</p>
                    </div>
                    <button type="submit" class="w-full py-3 rounded-lg bg-cstm-primary text-white font-semibold hover:opacity-90 transition">
                        Verify &amp; Create my dashboard
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-gray-500">
                    Already registered?
                    <a href="sign-in.php" class="text-cstm-primary font-medium hover:underline">Sign in with email</a>
                </p>
            <?php endif; ?>
        </div>

        <p class="text-center text-xs text-gray-400 mt-4">
            By continuing you agree that we access your site's public pages to verify Squarespace hosting and set up Search Console.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layouts/layoutBottom.php'; ?>
