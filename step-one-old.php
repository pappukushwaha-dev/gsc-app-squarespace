<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php'; // ✅ explicit DB include
require_once __DIR__ . '/includes/google/get_account.php';



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* ==========================================================
   INSTANCE (SHOPIFY)
========================================================== */
$shopId =
    $_SESSION['shop_id']
    ?? $_GET['shopId']
    ?? null;
/* ==========================================================
   GOOGLE ACCOUNT (SHOPIFY)
========================================================== */
$googleAccount = null;
$isConnected   = false;

if ($shopId) {
    $googleAccount = getGoogleAccountByShop($shopId);

    $isConnected = (
        $googleAccount &&
        (
            !empty($googleAccount['access_token']) ||
            !empty($googleAccount['refresh_token'])
        )
    );
}


/* ==========================================================
   LOAD SITE URL (SHOPIFY — SINGLE SOURCE OF TRUTH)
========================================================== */
$siteUrl = null;

if ($shopId) {
    $stmt = $pdo->prepare("
        SELECT domain
        FROM SquarespaceSite
        WHERE shop_id = ?
          AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$shopId]);
    $shopDomain = $stmt->fetchColumn();

    if ($shopDomain) {
        $siteUrl = 'https://' . rtrim($shopDomain, '/') . '/';

        $siteDomain = $shopDomain; // clean domain without protocol

    }
    $siteDomain = $siteDomain ?? null;
}



?>


<div id="step1-container" class="space-y-12">

    <!-- Main Card -->
    <div class="max-w-2xl mx-auto bg-cstm-primary-gradient-light drk-bg-cstm-primary-gradient-dark dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-600 relative overflow-hidden p-6">

        <!-- Top gradient -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-cstm-primary drk-bg-cstm-primary-gradient-dark"></div>

        <div class="p-8 md:p-10 flex flex-col items-center text-center">

            <?php if (!$isConnected): ?>
                <!-- ======================= NOT CONNECTED ======================= -->
                <p>It appears that this website is not linked to the current Google Account. Please connect the Google Account associated with the website.</p>
                <div class="relative mb-8 group mt-4">
                    <div class="absolute inset-0 bg-blue-100 dark:bg-blue-900/30 rounded-full scale-125 blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <div class="relative bg-white dark:bg-neutral-700 p-5 rounded-full shadow-md border border-neutral-100 dark:border-neutral-600 ring-4 ring-neutral-50 dark:ring-neutral-700/50 inline-flex mb-6">
                        <img src="https://www.gstatic.com/images/branding/product/1x/googleg_48dp.png"
                             class="w-12 h-12"
                             alt="Google">
                    </div>
                    <div>
                        <h3 class="font-bold text-xl text-cstm-primary">Google Search Console</h3>
                        <p class="text-sm text-gray-500">Get visibility into your search performance</p>
                    </div>
                </div>

                <!-- Website URL display (step 1) -->
                <div class="w-full max-w-md mx-auto mb-6 text-left">
                    <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 mb-1">
                        Website URL
                    </label>

                   <input
    type="text"
    id="websiteUrlInput"
    value="<?= htmlspecialchars($siteDomain ?? '') ?>"
    placeholder="example.com or https://example.com"
    class="w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-200 border border-neutral-200 dark:border-neutral-600 focus:ring-2 focus:ring-cstm-primary focus:outline-none"
/>
                </div>

                <button id="connectGoogleBtn"
                        class="w-full group relative flex items-center justify-center gap-3 bg-white dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-neutral-600 dark:hover:bg-neutral-600 font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md">
                    <span class="text-lg">Connect Your Domain</span>
                </button>

                <div class="mt-6 flex items-center justify-center gap-2 text-neutral-400 text-xs">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Secure, encrypted Google authentication</span>
                </div>

            <?php else: ?>
                <!-- ======================= CONNECTED VIEW ======================= -->

                <!-- Avatar / Status -->
                <div class="relative mb-4">
                    <div class="absolute inset-0 bg-success-100 dark:bg-success-600/25 rounded-full blur-lg animate-pulse"></div>
                    <div class="relative bg-white dark:bg-neutral-800 p-2 rounded-full border-2 border-success-100 dark:border-success-600 shadow-sm inline-block">
                        <?php if (!empty($googleAccount['picture'])): ?>
                            <img src="<?= htmlspecialchars($googleAccount['picture']) ?>" alt="User" class="w-20 h-20 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-20 h-20 rounded-full bg-success-100 dark:bg-success-600/25 text-success-600 flex items-center justify-center text-3xl font-bold">
                                <?= strtoupper(substr($googleAccount['email'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>

                        <div class="absolute top-1 right-1 bg-success-600 text-white p-1 rounded-full border-4 border-white dark:border-neutral-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <h3 class="text-xl font-bold text-neutral-900 dark:text-white">
                    Account Connected!
                </h3>

                <div class="mt-3 mb-4 inline-flex items-center gap-2 px-4 py-2 bg-neutral-50 dark:bg-neutral-700 rounded-lg border border-neutral-100 dark:border-neutral-600 text-sm">
                    <img src="https://www.gstatic.com/images/branding/product/1x/googleg_48dp.png" class="w-4 h-4" alt="">
                    <span class="font-medium text-neutral-700 dark:text-neutral-200">
                        <?= htmlspecialchars($googleAccount['email']) ?>
                    </span>
                </div>

                <!-- Show website URL here as well -->
<input
    type="text"
    id="websiteUrlInput"
    value="<?= htmlspecialchars($siteDomain ?? '') ?>"
    class="w-full px-4 py-3 rounded-xl bg-neutral-50 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-200 border border-neutral-200 dark:border-neutral-600 focus:ring-2 focus:ring-cstm-primary focus:outline-none"
/>

                <div class="w-full space-y-3">

                    <a href="?step=2"
                       id="continueSetupBtn"
                       class="btn btn-cstm-primary w-full rounded-xl text-lg font-semibold shadow-lg transition-all flex items-center justify-center gap-2 group mt-4">
                        <span>Continue Setup</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>

                    <button id="disconnectGoogleBtn"
                            class="w-full py-3 text-sm font-medium text-neutral-400 hover:text-danger-500 transition-colors">
                        Disconnect this account
                    </button>
                </div>

            <?php endif; ?>
        </div>
    </div>


    <!-- Disconnect Confirmation Modal -->
    <div id="disconnect-modal-overlay" class="fixed inset-0 bg-cstm-black-40 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300"></div>

    <div id="disconnect-modal" class="fixed top-1/2 left-1/2 z-99 w-full max-w-2xl bg-white dark:bg-neutral-800 rounded-2xl shadow-xl p-0 hidden opacity-0 scale-95 transition-all duration-300 -translate-x-1/2 -translate-y-1/2 overflow-hidden border border-neutral-200 dark:border-neutral-600 p-10">

        <div class="p-8 text-center">
            <div class="mx-auto w-16 h-16 bg-danger-100 dark:bg-danger-900/30 text-danger-600 rounded-full flex items-center justify-center mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>

            <h3 class="text-xl font-bold text-neutral-900 dark:text-white mb-2">Disconnect Account?</h3>

            <p class="text-neutral-500 dark:text-neutral-400 text-sm leading-relaxed mb-8">
                This will remove your Google Search Console connection. You'll need to reconnect to verify your domain.
            </p>

            <div class="flex items-center justify-center gap-3">
                <button id="cancelDisconnect" class="btn btn-cstm-muted">
                    Cancel
                </button>
                <button id="confirmDisconnect" class="btn btn-cstm-danger">
                    Disconnect
                </button>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Continue Setup (already working)
        var continueBtn = document.getElementById('continueSetupBtn');
        if (continueBtn) {
            continueBtn.addEventListener('click', function(e) {
                e.preventDefault();

                continueBtn.disabled = true;
                continueBtn.classList.add('opacity-70', 'cursor-wait');

                var formData = new FormData();
                formData.append('step', '1'); // Mark step 1 as completed

                fetch(window.APP_BASE + "/api/update-step.php", {
                    method: 'POST',
                    body: formData
                })
                .catch(function() {})
                .finally(function() {
                    window.location.href = '?step=2';
                });
            });
        }

        // ===== Disconnect Flow =====
        var disconnectBtn = document.getElementById('disconnectGoogleBtn');
        var modal = document.getElementById('disconnect-modal');
        var overlay = document.getElementById('disconnect-modal-overlay');
        var cancelDisconnect = document.getElementById('cancelDisconnect');
        var confirmDisconnect = document.getElementById('confirmDisconnect');

        if (disconnectBtn && modal && overlay && cancelDisconnect && confirmDisconnect) {
            // Open modal
            disconnectBtn.addEventListener('click', function () {
               modal.classList.remove('hidden');
overlay.classList.remove('hidden');

requestAnimationFrame(function () {
    modal.classList.remove('opacity-0', 'scale-95');
    overlay.classList.remove('opacity-0');
});
            });

            // Close modal
            cancelDisconnect.addEventListener('click', function () {
                modal.classList.add('opacity-0', 'scale-95');
                overlay.classList.add('opacity-0');
                setTimeout(function () {
                    modal.classList.add('hidden');
                    overlay.classList.add('hidden');
                }, 200);
            });

            // Confirm disconnect
            confirmDisconnect.addEventListener('click', function () {
                confirmDisconnect.disabled = true;

               fetch(window.APP_BASE + "/includes/google/disconnect.php", {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                .then(function (res) { return res.json(); })
                .then(function () {
                    window.location.href = window.APP_BASE + "/setup-wizard.php?step=1";
                })
                .catch(function () {
                    window.location.href = window.APP_BASE + "/setup-wizard.php?step=1";
                });
            });
        }
    });
</script>
