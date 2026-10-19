<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/google/get_account.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ==========================================================
   INSTANCE (SHOPIFY) - Match setup-wizard.php order
========================================================== */
$shopId = $_SESSION['shop_id']
    ?? $_SESSION['shop_id']
    ?? $_GET['shop_id']
    ?? $_GET['shopId']
    ?? null;

// Clean and validate instance ID
$shopId = $shopId ? trim((string)$shopId) : null;

if (empty($shopId)) {
    // Try to redirect to sign-in like setup-wizard.php does
    header("Location: sign-in.php");
    exit;
}

// Ensure it's stored in session for consistency (both keys)
$_SESSION['shop_id'] = $shopId;
$_SESSION['shop_id'] = $shopId;

/* ==========================================================
   GOOGLE ACCOUNT (SHOPIFY)
========================================================== */
require_once __DIR__ . '/includes/google/token_manager.php';

$google = getGoogleAccountByInstance($shopId);
$isConnected = ($google && !empty($google['access_token']));

// Check if token is valid/expired
$tokenValid = false;
$tokenError = null;

if ($isConnected && !empty($google['id'])) {
    $accountId = (int)$google['id'];
    $tokenResult = ensureAccessToken($accountId, 120);
    
    if ($tokenResult['success']) {
        $tokenValid = true;
    } else {
        $tokenError = $tokenResult['error'] ?? 'token_invalid';
        // If token refresh failed, mark as needs reconnect
        if (in_array($tokenError, ['invalid_grant', 'account_disconnected', 'refresh_failed'])) {
            $isConnected = false;
        }
    }
}

if (!$isConnected || !$tokenValid) {
    // Don't die - show the form with reconnect option
    $showReconnect = true;
} else {
    $showReconnect = false;
}

/* ==========================================================
   DOMAIN FROM VERIFICATION TABLE
========================================================== */
$stmt = $pdo->prepare("
    SELECT site_url
    FROM gsc_domain_verifications
    WHERE shop_id = ?
    LIMIT 1
");
$stmt->execute([$shopId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

/* ==========================================================
   BUILD DEFAULT SITEMAP URL
========================================================== */
$storedSiteUrl = isset($row['site_url']) && is_string($row['site_url'])
    ? trim($row['site_url'])
    : '';

$siteUrl = null;

if ($storedSiteUrl !== '') {
    if (stripos($storedSiteUrl, 'sc-domain:') === 0) {
        // sc-domain:example.com → https://example.com/
        $host = substr($storedSiteUrl, strlen('sc-domain:'));
        $host = preg_replace('#^www\.#i', '', $host);
        $siteUrl = 'https://' . $host . '/';
    } else {
        $siteUrl = rtrim($storedSiteUrl, '/') . '/';
    }
}

$sitemapDefault = $siteUrl ? $siteUrl . 'sitemap.xml' : '';
?>

<script>
window.showAlert = function (message, type = 'success') {
    const colors = {
        success: '#16a34a',
        error: '#dc2626',
        warning: '#f59e0b'
    };

    const n = document.createElement('div');
    n.textContent = message;
    n.style.position = 'fixed';
    n.style.right = '12px';
    n.style.top = '12px';
    n.style.padding = '10px 14px';
    n.style.background = colors[type] || colors.success;
    n.style.color = '#fff';
    n.style.borderRadius = '8px';
    n.style.zIndex = 9999;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3000);
};
</script>



<?php include __DIR__ . '/partials/review-popup.php';?>
<style>
    #confetti-container {
        z-index: 100;
        overflow: visible !important;
    }

    /* Fade-in animation for success section */
    #sitemap-success-section {
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.7s ease-out, transform 0.7s ease-out;
    }

    #sitemap-success-section.show {
        opacity: 1;
        transform: translateY(0);
    }

    /* Confetti burst (subtle) */
    .confetti {
        position: absolute;
        top: 0;
        left: 50%;
        width: 6px;
        height: 6px;
        background: var(--cstm-primary, #22c55e);
        border-radius: 50%;
        animation: fall 2.5s linear infinite;
        z-index: 999;
    }

    @keyframes fall {
        0% {
            transform: translateY(0) rotate(0deg);
            opacity: 1;
        }

        100% {
            transform: translateY(400px) rotate(360deg);
            opacity: 0;
        }
    }
</style>

<div class="space-y-14 py-4">

    <div id="sitemap-submission-form">
        <div class="max-w-2xl mx-auto bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-600 relative overflow-hidden p-6">
            <div class="absolute top-0 left-0 right-0 h-1 bg-cstm-primary drk-bg-cstm-primary-gradient-dark"></div>

            <div class="space-y-3">

                <div>
                    <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200 mb-2">
                        Sitemap URL
                    </label>

                    <input type="text"
                           id="sitemapInput"
                           value="<?= htmlspecialchars((string)($sitemapDefault ?? '')) ?>"
                           class="w-full px-4 py-3 rounded-xl bg-neutral-100 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-200 border border-neutral-300 dark:border-neutral-600">
                </div>

                <div id="sitemapStatusBox"
                     class="hidden rounded-xl p-4 text-sm font-medium border"></div>

                <!-- RECONNECT GOOGLE SECTION -->
                <?php if ($showReconnect): ?>
                <div id="reconnectGoogleWrapper" class="mt-4">
                    <div class="rounded-xl p-4 border border-danger-200 bg-danger-100 text-danger-600 mb-4">
                        <p class="text-sm font-medium">
                            Your Google connection has expired or permissions were revoked. Please reconnect.
                        </p>
                    </div>
                    <button id="reconnectGoogleBtn"
                            class="w-full group relative flex items-center justify-center gap-3 bg-white dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-neutral-600 dark:hover:bg-neutral-600 font-semibold py-3 px-5 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md">
                        <img src="https://www.gstatic.com/images/branding/product/1x/googleg_48dp.png"
                             class="w-5 h-5" alt="Google">
                        <span>Reconnect Google Account</span>
                    </button>

                    <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-400 text-center">
                        If your connection expired or you skipped some permissions earlier, reconnect and ensure you allow all requested access.
                    </p>
                </div>
                <?php else: ?>
                <div id="reconnectGoogleWrapper" class="hidden mt-4">
                    <button id="reconnectGoogleBtn"
                            class="w-full group relative flex items-center justify-center gap-3 bg-white dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-neutral-600 dark:hover:bg-neutral-600 font-semibold py-3 px-5 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md">
                        <img src="https://www.gstatic.com/images/branding/product/1x/googleg_48dp.png"
                             class="w-5 h-5" alt="Google">
                        <span>Reconnect Google Account</span>
                    </button>

                    <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-400 text-center">
                        If your connection expired or you skipped some permissions earlier, reconnect and ensure you allow all requested access.
                    </p>
                </div>
                <?php endif; ?>

                <button id="submitSitemapBtn"
                        data-instance="<?= htmlspecialchars($shopId) ?>"
                        class="btn btn-cstm-primary w-full flex items-center justify-center gap-3 mt-4 <?= $showReconnect ? 'opacity-50 cursor-not-allowed' : '' ?>"
                        <?= $showReconnect ? 'disabled' : '' ?>>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M5 13l4 4L19 7" />
                    </svg>
                    Submit Sitemap to Google
                </button>

                <div class="mt-10 hidden">
                    <h4 class="text-lg font-semibold mb-3 text-neutral-700 dark:text-neutral-200">
                        Submission History
                    </h4>

                    <div id="sitemapHistory" class="text-sm text-neutral-600 dark:text-neutral-300">
                        Loading...
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="sitemap-success-section" class="relative p-8 text-center hidden">
        <div id="confetti-container" class="absolute inset-0 pointer-events-none"></div>

        <div class="mx-auto rounded-2xl p-10 relative z-10 bg-white dark:bg-neutral-800">
            <h1 class="text-3xl font-extrabold text-green-600 mb-3 flex items-center justify-center gap-2">
                🎉 Congratulations!
            </h1>
            <p class="text-lg text-neutral-700 dark:text-neutral-300 mb-8">
                Your sitemap has been successfully submitted to Google Search Console.
            </p>

            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="dashboard.php" id="finishBtn"
                   class="btn btn-cstm-primary flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Go to Dashboard
                </a>
                <a href="sitemap.php" target="_blank"
                   class="btn btn-cstm-muted flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Sitemap Management
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Reconnect Google Modal -->
<div id="reconnect-modal-overlay"
     class="fixed inset-0 bg-cstm-black-40 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300"></div>

<div id="reconnect-modal"
     class="fixed top-1/2 left-1/2 z-50 bg-white dark:bg-neutral-800 rounded-2xl shadow-xl hidden opacity-0 scale-95 transition-all duration-300 -translate-x-1/2 -translate-y-1/2 overflow-hidden border border-neutral-200 dark:border-neutral-600">

    <div class="p-5 text-center">
        <div class="mx-auto w-16 h-16 bg-success-100 dark:bg-success-900/30 text-success-600 rounded-full flex items-center justify-center mb-6">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h3 class="text-xl font-bold text-neutral-900 dark:text-white mb-3">
            Reconnect Your Account &amp; Grant Permissions
        </h3>
        <p class="text-neutral-500 dark:text-neutral-400 text-sm leading-relaxed mb-6">
            It looks like your account connection has expired, or some required permissions were not granted.<br><br>
            Please reconnect your account and ensure <strong>all requested permissions are allowed</strong>.
        </p>
        <p class="text-neutral-500 dark:text-neutral-400 text-sm leading-relaxed mb-6">
           To submit sitemaps, your Google account must have <strong>full access</strong> to this property in Google Search Console.<br><br>
        </p>

        <ul class="text-left text-sm text-neutral-600 dark:text-neutral-300 mb-6 space-y-2 max-w-md mx-auto">
            <li>Select the correct Google account that owns this website in Search Console.</li>
            <li>Click <strong>Allow</strong> for all requested permissions (do not uncheck anything).</li>
            <li>After the connection is successful, return here and submit your sitemap again.</li>
        </ul>

        <div class="flex items-center justify-center gap-3">
            <button id="cancelReconnect"
                    class="btn btn-cstm-muted">
                Cancel
            </button>
            <button id="confirmReconnect"
                    class="btn btn-cstm-primary flex items-center gap-2">
                <img src="https://www.gstatic.com/images/branding/product/1x/googleg_48dp.png"
                     class="w-5 h-5" alt="">
                <span>Continue to Google</span>
            </button>
        </div>
    </div>
</div>
