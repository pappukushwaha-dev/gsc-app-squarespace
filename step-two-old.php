<?php
require_once __DIR__ . '/includes/config.php';

/* ==========================================================
   SESSION
========================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$shopId = $_SESSION['shop_id']
    ?? $_SESSION['shop_id']
    ?? null;

if (!$shopId) {
    die("Instance ID missing");
}

/* ==========================================================
   1. GET SITE URL FROM SquarespaceSite
========================================================== */
$stmt = $pdo->prepare("
    SELECT domain
    FROM SquarespaceSite
    WHERE shop_id = ?
      AND is_active = 1
    LIMIT 1
");
$stmt->execute([$shopId]);
$shopDomain = $stmt->fetchColumn();

if (!$shopDomain) {
    die("Shop not found or uninstalled");
}

$normalizedDomain = strtolower(trim($shopDomain));
$siteUrl = 'https://' . rtrim($normalizedDomain, '/');

// Detect Squarespace Instant Site (Squarespace-hosted) vs embedded storefront.
// Instant Site domains end in .company.site, .ecwid.com, or .ecwid.site.
$isInstantSite = (bool)preg_match('/\.(company\.site|ecwid\.com|ecwid\.site)$/i', $normalizedDomain);



/* ==========================================================
   2. LOAD OR CREATE VERIFICATION RECORD
========================================================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM gsc_domain_verifications
    WHERE shop_id = ?
    LIMIT 1
");
$stmt->execute([$shopId]);
$domain = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$domain) {
    $stmt = $pdo->prepare("
        INSERT INTO gsc_domain_verifications
            (shop_id, site_url, verification_status, verification_method)
        VALUES (?, ?, 'pending', 'meta')
    ");
    $stmt->execute([
        $shopId,
        $siteUrl
    ]);

    $domain = [
        'shop_id'         => $shopId,
        'site_url'            => $siteUrl,
        'verification_status' => 'pending',
        'verification_method' => 'meta'
    ];
}


/* ==========================================================
   3. NORMALIZE VALUES FOR UI
========================================================== */
$metaToken = $domain['meta_token'] ?? '';

$status    = $domain['verification_status'] ?? 'pending';


?>


<div id="step2-container" class="space-y-14">
    <div class="max-w-2xl mx-auto bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-600 relative overflow-hidden p-6">

        <div class="absolute top-0 left-0 right-0 h-1 bg-cstm-primary drk-bg-cstm-primary-gradient-dark"></div>

        <div class="space-y-3 mt-2">

            <!-- Site URL -->
            <div>
                <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200 mb-2">
                    Website URL
                </label>
                <input type="text"
                    value="<?= htmlspecialchars($siteUrl) ?>"
                    readonly
                    class="w-full px-4 py-3 rounded-xl bg-neutral-100 dark:bg-neutral-700 
                              text-neutral-600 dark:text-neutral-200 border border-neutral-300 
                              dark:border-neutral-600 cursor-not-allowed">
            </div>


            <!-- Meta Tag -->
            <div id="metaTagContainer"
                class="<?= empty($metaToken) ? 'hidden' : '' ?>">
                <div class="flex justify-between items-center mt-3">
    <span class="text-xs text-neutral-500">
        Add this tag inside your Site &lt;head&gt;
    </span>

    <button
        type="button"
        onclick="openPopup()"
        class="text-sm font-medium text-blue-600 hover:underline">
        View Installation Instructions
    </button>
</div>
                <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200 mb-2">
                    Google Verification Meta Tag
                </label>

                <textarea readonly
                    class="w-full px-4 py-3 rounded-xl bg-neutral-100 dark:bg-neutral-700 
               text-neutral-600 dark:text-neutral-200 border border-neutral-300 
               dark:border-neutral-600 cursor-not-allowed h-24"><?php if ($metaToken): ?>
                <meta name="google-site-verification" content="<?= htmlspecialchars($metaToken) ?>">
                <?php endif; ?></textarea>
            </div>


            <!-- STATUS BOX -->
            <div id="verifyStatusBox"
                class="hidden rounded-xl p-4 text-sm font-medium border"></div>

            <?php if ($status === 'verified'): ?>
                <div class="text-center mt-10">
                    <div class="text-success-600 text-lg font-bold mb-3 bg-success-100 rounded-lg p-4 text-start">
                        <span class="bg-success-600 text-white rounded-full px-2 py-1 text-sm">✔</span> Domain Successfully Verified
                        <p class="text-success-600 text-xs pl-10">Your domain has been verified and is ready to use with Google Search Console.</p>
                    </div>
                    <a href="setup-wizard.php?step=3" id="continueStep2Btn"
                        class="btn btn-cstm-primary">
                        Continue →
                    </a>
                </div>
            <?php else: ?>


                <!-- PROPERTY MISSING -->
                <div id="propertyMissingBox" class="hidden border border-yellow-300 bg-yellow-50 text-yellow-800 rounded-xl p-4 mb-4">
                    <h3 class="font-semibold text-lg mb-2">Search Console property not found</h3>
                    <p class="text-sm mb-3">
                        To continue, we need to create a Google Search Console property for your domain.
                        This allows sitemap submission and performance tracking.
                    </p>
                    <button
                        id="createPropertyBtn"
                        class="btn btn-primary">
                        Create Search Console Property
                    </button>
                </div>



                <!-- GENERATE TOKEN BUTTON -->
                <div id="generateTokenWrapper" class="<?= empty($metaToken) ? '' : 'hidden' ?>">
                    <button id="generateTokenBtn"
                        class="btn btn-cstm-primary w-full rounded-xl text-lg font-semibold shadow-lg transition-all flex items-center justify-center gap-2 group">
                        Generate Google Verification Token
                    </button>
                </div>

                <!-- VERIFY BUTTON -->
                <div id="verifyWrapper" class="<?= empty($metaToken) ? 'hidden' : '' ?>">
                    <button id="verifyDomainBtn"
                        data-instance="<?= $shopId ?>"
                        data-domain="<?= htmlspecialchars($normalizedDomain) ?>"
                        data-metatoken="<?= htmlspecialchars($metaToken) ?>"
                        class="btn btn-cstm-primary py-3 text-lg w-full mt-6 rounded-xl
               flex items-center justify-center gap-3 hover:shadow-xl transition-all">


                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 13l4 4L19 7" />
                        </svg>

                        Verify Domain
                    </button>
                </div>



                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-2 text-center">
                    <?php if ($isInstantSite): ?>
                        Paste the meta tag in your Squarespace Control Panel (Website &rarr; SEO), then click Verify Domain.
                    <?php else: ?>
                        Add the meta tag to your website&rsquo;s <code>&lt;head&gt;</code> &mdash; exact steps depend on your platform &mdash; then click Verify Domain.
                    <?php endif; ?>
                </p>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- POPUP OVERLAY -->
<!-- META TAG POPUP -->
<div id="metaPopup"
    class="fixed inset-0 bg-cstm-black-40 backdrop-blur-sm flex items-center justify-center z-50 hidden">

    <div class="bg-white dark:bg-neutral-900 rounded-2xl shadow-xl max-w-2xl w-full p-6 relative">

        <!-- HEADER -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-neutral-800 dark:text-neutral-100">
                Google Verification Meta Tag
            </h2>

            <button onclick="closePopup()" class="text-xl leading-none">
                ✕
            </button>
        </div>

        <?php if (!empty($metaToken)): ?>
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200 mb-1">
                Verification Meta Tag
            </label>
            <p class="text-xs text-neutral-500 mb-2">
                <?php if ($isInstantSite): ?>
                    Paste this tag into Squarespace &rarr; <b>Website &rarr; SEO &rarr; Header meta tags and site verification</b>.
                <?php else: ?>
                    Paste this tag inside the <code>&lt;head&gt;</code> of your website. Exact steps vary by platform &mdash; see the guide below.
                <?php endif; ?>
            </p>
            <div class="relative">
                <button onclick="copyMetaTag('popupMetaTagText')"
                    class="absolute top-3 right-3 text-xs px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white z-10">
                    Copy
                </button>
                <textarea id="popupMetaTagText" readonly
                    class="w-full px-4 py-3 pr-20 rounded-xl bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-neutral-600 h-24 resize-none font-mono text-sm"><meta name="google-site-verification" content="<?= htmlspecialchars($metaToken) ?>"></textarea>
            </div>

            <span id="copySuccess"
                class="hidden absolute -top-3 right-0 text-xs bg-success-100 text-success-600 px-3 py-1 rounded-lg">
                Copied!
            </span>
        <?php endif; ?>

        <div class="rounded-xl p-4 text-sm font-medium border block border-neutral-200 bg-neutral-50 text-neutral-700 mt-4">
            <h2 class="text-lg font-semibold text-neutral-800 dark:text-neutral-100 mb-2">
                <?php if ($isInstantSite): ?>
                    How to add this in Squarespace Instant Site
                <?php else: ?>
                    How to add the meta tag to your website
                <?php endif; ?>
            </h2>

            <?php if ($isInstantSite): ?>
                <ul class="space-y-2">
                    <li class="flex gap-2"><span class="font-bold">1.</span> Open your <b>Squarespace Control Panel</b>.</li>
                    <li class="flex gap-2"><span class="font-bold">2.</span> In the left sidebar, open <b>Website &rarr; SEO</b>.</li>
                    <li class="flex gap-2"><span class="font-bold">3.</span> Scroll to <b>SEO settings &rarr; Header meta tags and site verification</b>.</li>
                    <li class="flex gap-2"><span class="font-bold">4.</span> Click <b>Edit</b>.</li>
                    <li class="flex gap-2"><span class="font-bold">5.</span> Paste the full meta tag (above) into the textarea, then <b>Save</b>.</li>
                    <li class="flex gap-2"><span class="font-bold">6.</span> Return here and click <b>Verify Domain</b>.</li>
                </ul>
            <?php else: ?>
                <div class="rounded-lg border border-blue-200 bg-blue-50 text-blue-900 p-3 mb-3 text-xs leading-relaxed">
                    <p class="font-semibold mb-1">Heads up &mdash; steps depend on your website builder.</p>
                    <p>
                        Squarespace can be embedded in almost any platform (Wix, WordPress, Squarespace, Shopify, Webflow, custom HTML, etc.),
                        so the exact place to edit your site&rsquo;s <code>&lt;head&gt;</code> isn&rsquo;t the same for everyone.
                        If you&rsquo;re not sure which builder hosts your store, check Squarespace&rsquo;s guide:
                        <a href="https://support.ecwid.com/hc/en-us/articles/115004678945"
                           target="_blank" rel="noopener"
                           class="underline font-medium">
                            How to add Squarespace to your website
                        </a>.
                    </p>
                </div>

                <ul class="space-y-2">
                    <li class="flex gap-2"><span class="font-bold">1.</span> Copy the <b>Verification Meta Tag</b> above.</li>
                    <li class="flex gap-2"><span class="font-bold">2.</span> Open the editor for whichever platform hosts your website (Wix, WordPress, Squarespace, custom HTML, etc.).</li>
                    <li class="flex gap-2"><span class="font-bold">3.</span> Find where you can edit the <b><code>&lt;head&gt;</code></b> section of every page. A few common examples (your platform may differ):
                        <ul class="ml-4 mt-1 list-disc text-xs text-neutral-600 space-y-1">
                            <li><b>Wix:</b> Settings &rarr; Custom Code &rarr; Add Custom Code &rarr; Head.</li>
                            <li><b>WordPress:</b> use a Header/Footer plugin or your theme&rsquo;s <code>header.php</code>.</li>
                            <li><b>Squarespace:</b> Settings &rarr; Advanced &rarr; Code Injection &rarr; Header.</li>
                        </ul>
                        If your platform isn&rsquo;t listed, check its own documentation for &ldquo;custom code in head&rdquo; or &ldquo;site verification&rdquo;.
                    </li>
                    <li class="flex gap-2"><span class="font-bold">4.</span> Paste the meta tag inside the <code>&lt;head&gt;</code> and save/publish.</li>
                    <li class="flex gap-2"><span class="font-bold">5.</span> Return here and click <b>Verify Domain</b>.</li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>

function openPopup() {
    document.getElementById('metaPopup').classList.remove('hidden');
}
    function closePopup() {
        document.getElementById('metaPopup').classList.add('hidden');
    }

    function copyMetaTag(targetId) {
        const id = targetId || 'popupMetaTagText';
        const el = document.getElementById(id);
        if (!el) return;
        navigator.clipboard.writeText(el.value);

        const success = document.getElementById('copySuccess');
        if (success) {
            success.classList.remove('hidden');
            setTimeout(() => success.classList.add('hidden'), 1500);
        }
    }

</script>