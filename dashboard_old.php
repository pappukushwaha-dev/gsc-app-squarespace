<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/google/get_account.php';
require_once __DIR__ . '/includes/plan_guard.php';

$planAccess = getPlanAccessForPage();
$hasPremiumAccess = $planAccess['allowed'] ?? false;
$shopIdFromUrl = $_GET['shop_id'] ?? $_GET['shop_id'] ?? null;
$shopIdFromSession = $_SESSION['shop_id'] ?? $_SESSION['shop_id'] ?? null;
$shopId = $shopIdFromUrl ?: $shopIdFromSession;
$shopId = $shopId ? trim((string)$shopId) : null;

if (empty($shopId)) {
    header("Location: sign-in.php");
    exit;
}

// If shopId came from URL OR differs from session, validate and update session
$needsUpdate = false;
if ($shopIdFromUrl && ($shopIdFromUrl !== $shopIdFromSession)) {
    $needsUpdate = true;
} elseif (!isset($_SESSION['shop_id'])) {
    $needsUpdate = true;
} elseif ($shopId !== $_SESSION['shop_id']) {
    $needsUpdate = true;
}

if ($needsUpdate) {
    $stmt = $pdo->prepare("
        SELECT shop_id, domain AS shop_domain, site_display_name AS shop_name
        FROM SquarespaceSite
        WHERE shop_id = ?
          AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$shopId]);
    $shopData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shopData) {
        // Invalid instance ID
        header("Location: sign-in.php");
        exit;
    }

    // Update session variables with new instance
    $_SESSION['shop_id'] = $shopId;
    $_SESSION['shop_id'] = $shopId;
    $_SESSION['shop'] = $shopData['shop_domain'];
} else {
    // Session already matches - ensure both keys are set for compatibility
    $_SESSION['shop_id'] = $shopId;
    $_SESSION['shop_id'] = $shopId;
}

// Get shop domain for building Shopify pricing URL
$shop = $_SESSION['shop'] ?? null;
if (!$shop && isset($shopData)) {
    $shop = $shopData['shop_domain'] ?? null;
}


/* ==========================================================
   BUILD BILLING URL (BIGCOMMERCE)
========================================================== */
$billingUrl = 'pricing.php';

/* ==========================================================
   PAGE META
========================================================== */
$title    = 'Search Console Dashboard';
$subTitle = 'Overview';

// -------------------------------------------------------------------------
// 2. STATUS CHECKS
// -------------------------------------------------------------------------
$INTENT_REGEX = [
    'informational' => '/\b(who|what|where|when|why|how|can|is|are|do|does)\b/i',
    'commercial' => '/\b(buy|purchase|order|checkout|shop|online|store|sale|discount|coupon|promo|code|clearance|deals?|cheap|affordable|price|cost|get)\b/i',
    'transactional' => '/\b(buy|purchase|order|checkout|subscribe|sign up|signup|register|download|install|book|booking|trial|free trial)\b/i',
    'navigational' => '/\b(login|sign in|signin|account|dashboard|portal|official|website|homepage|contact|support|helpdesk)\b/i'
];
$ECOMMERCE_REGEX = [
    'ready_to_buy' => '/\b(buy|price|cost|discount|deal|order|hire|book|service near me)\b/i',
    'product_research' => '/\b(best|top|compare|comparison|vs|review|reviews|options|types|brands)\b/i',
    'trust_comparison' => '/\b(rating|ratings|testimonials|before after|case study|pros cons|experience)\b/i',
    'post_purchase' => '/\b(repair|replacement|support|return|refund|warranty|tracking|service)\b/i'
];
/* ==========================================================
   A. GOOGLE CONNECTION
========================================================== */
$googleAccount = getGoogleAccountByShop($shopId);
$isConnected   = (
    $googleAccount &&
    !empty($googleAccount['access_token']) &&
    (int)$googleAccount['connected'] === 1
);

/* ==========================================================
   B. DOMAIN VERIFICATION
========================================================== */
$stmt = $pdo->prepare("
    SELECT site_url, verification_status
    FROM gsc_domain_verifications
    WHERE shop_id = ?
    LIMIT 1
");
$stmt->execute([$shopId]);
$siteInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$verifiedSiteUrl = $siteInfo['site_url'] ?? null;
$isVerified      = (($siteInfo['verification_status'] ?? '') === 'verified');

/* ==========================================================
   C. SITEMAP STATUS
========================================================== */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM sitemaps 
    WHERE shop_id = ?
");
$stmt->execute([$shopId]);
$hasSitemap = ((int)$stmt->fetchColumn() > 0);

/* ==========================================================
   SETUP WIZARD COMPLETION GUARD
========================================================== */

// If setup is NOT complete, force user into setup wizard
if (!$isConnected || !$isVerified || !$hasSitemap) {

    // Decide which step to send user to
    if (!$isConnected) {
        $redirectStep = 1;
    } elseif (!$isVerified) {
        $redirectStep = 2;
    } else {
        $redirectStep = 3;
    }

    header("Location: setup-wizard.php?step={$redirectStep}");
    exit;
}

/* ==========================================================
   TIMEZONE (FROM SHOP)
========================================================== */
$stmt = $pdo->prepare("
    SELECT shop_data
    FROM SquarespaceSite
    WHERE shop_id = ?
    LIMIT 1
");
$stmt->execute([$shopId]);
$shopRow = $stmt->fetch(PDO::FETCH_ASSOC);

$countryCode = 'US';
if (!empty($shopRow['shop_data'])) {
    $shopData = json_decode($shopRow['shop_data'], true);
    $countryCode = $shopData['country'] ?? 'US';
}

$countryToTimezone = [
    'US' => 'America/New_York',
    'IN' => 'Asia/Kolkata',
    'GB' => 'Europe/London',
    'AU' => 'Australia/Sydney',
    'CA' => 'America/Toronto',
    'DE' => 'Europe/Berlin',
    'FR' => 'Europe/Paris',
    'AE' => 'Asia/Dubai',
];
$userTimezone = $countryToTimezone[$countryCode] ?? 'UTC';

/* ==========================================================
   D. SITEMAP HISTORY
========================================================== */
$siteUrl     = $verifiedSiteUrl ? rtrim($verifiedSiteUrl, '/') : null;
$sitemapUrl  = $siteUrl ? $siteUrl . '/sitemap.xml' : null;

$stmt = $pdo->prepare("
    SELECT status, http_code, submitted_at, google_response
    FROM sitemap_submission_logs
    WHERE shop_id = ?
      AND status = 'Success'
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$shopId]);

$history = array_map(static fn($row) => [
    'status'          => $row['status'],
    'http_code'       => $row['http_code'],
    'submitted_at'    => $row['submitted_at'],
    'google_response' => $row['google_response'],
], $stmt->fetchAll(PDO::FETCH_ASSOC));


/* ==========================================================
   E. DATA VISIBILITY FLAG
========================================================== */
$canShowData = ($isConnected && $isVerified);

// -------------------------------------------------------------------------
// 4. FETCH GSC CHART DATA (INTERNAL API)
// -------------------------------------------------------------------------
$chartData = [
    'dates'        => [],
    'clicks'       => [],
    'impressions'  => [],
];

$kpi = [
    'clicks'      => 0,
    'impressions' => 0,
    'ctr'         => 0,
    'position'    => 0,
];

if ($canShowData) {

    $payload = json_encode([
        'shopId' => $shopId,
        'range'      => '30days',
        'type'       => 'dates'
    ]);

    // Build API URL using APP_BASE constant or fallback to relative path
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (defined('APP_BASE')) {
        $basePath = rtrim(APP_BASE, '/');
    } else {
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim($scriptPath, '/');
    }
    $apiUrl = $protocol . '://' . $host . $basePath . '/api/google/get_gsc_report.php';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Dashboard API call error (dates): " . $curlError);
    }

    if ($httpCode !== 200) {
        error_log("Dashboard API call HTTP error (dates): " . $httpCode . " - Response: " . substr($response, 0, 500));
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Dashboard API JSON decode error (dates): " . json_last_error_msg() . " - Response: " . substr($response, 0, 500));
    }

    if (!empty($data['success']) && !empty($data['rows'])) {

        foreach ($data['rows'] as $row) {

            $date = $row['keys'][0] ?? null;
            if (!$date) {
                continue;
            }

            $clicks = (int)($row['clicks'] ?? 0);
            $impr   = (int)($row['impressions'] ?? 0);

            $chartData['dates'][]       = $date;
            $chartData['clicks'][]      = $clicks;
            $chartData['impressions'][] = $impr;

            $kpi['clicks']      += $clicks;
            $kpi['impressions'] += $impr;
            $kpi['ctr']         += (float)($row['ctr'] ?? 0);
            $kpi['position']    += (float)($row['position'] ?? 0);
        }

        $count = count($data['rows']);
        if ($count > 0) {
            $kpi['ctr']      = round($kpi['ctr'] / $count, 2);
            $kpi['position'] = round($kpi['position'] / $count, 1);
        }
    }
}
// -------------------------------------------------------------------------
// FETCH TOP PAGES (DETAILED REPORT)
// -------------------------------------------------------------------------
// -------------------------------------------------------------------------
// FETCH BRAND vs NON-BRAND (KEYWORDS)
// -------------------------------------------------------------------------
$brandSplit = [
    'branded_clicks' => 0,
    'non_branded_clicks' => 0,
    'branded_impr' => 0,
    'non_branded_impr' => 0,
    'brand_keywords' => [],
    'non_brand_keywords' => [],
];
if ($canShowData && $hasPremiumAccess) {

    $payload = json_encode([
        'shopId' => $shopId,
        'range'      => '30days',
        'type'       => 'keywords'
    ]);

    // Build API URL using APP_BASE constant or fallback to relative path
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (defined('APP_BASE')) {
        $basePath = rtrim(APP_BASE, '/');
    } else {
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim($scriptPath, '/');
    }
    $apiUrl = $protocol . '://' . $host . $basePath . '/api/google/get_gsc_report.php';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Dashboard API call error (keywords): " . $curlError);
    }

    if ($httpCode !== 200) {
        error_log("Dashboard API call HTTP error (keywords): " . $httpCode . " - Response: " . substr($response, 0, 500));
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Dashboard API JSON decode error (keywords): " . json_last_error_msg() . " - Response: " . substr($response, 0, 500));
    }

    if (!empty($data['success'])) {
        $brandSplit['branded_clicks']     = $data['metrics']['brand_split']['branded_clicks'] ?? 0;
        $brandSplit['non_branded_clicks'] = $data['metrics']['brand_split']['non_branded_clicks'] ?? 0;
        $brandSplit['brand_keywords']     = $data['extra']['brand_keywords'] ?? [];
        $brandSplit['non_brand_keywords'] = $data['extra']['non_brand_keywords'] ?? [];
        $brandSplit['branded_impr']     = $data['metrics']['brand_split']['branded_impr'] ?? 0;
        $brandSplit['non_branded_impr'] = $data['metrics']['brand_split']['non_branded_impr'] ?? 0;
    }
}
// Initialize intentData for all users (so locked state can be shown)
$intentData = [
    'informational' => [],
    'commercial' => [],
    'transactional' => [],
    'navigational' => []
];

// Only populate intent data if we have premium access and keywords data
if ($canShowData && $hasPremiumAccess && (!empty($brandSplit['brand_keywords']) || !empty($brandSplit['non_brand_keywords']))) {
    $allKeywords = array_merge(
        $brandSplit['brand_keywords'] ?? [],
        $brandSplit['non_brand_keywords'] ?? []
    );

    foreach ($allKeywords as $row) {
        $query = strtolower($row['keys'][0] ?? '');

        foreach ($INTENT_REGEX as $intent => $pattern) {
            if (preg_match($pattern, $query)) {
                $intentData[$intent][] = $row;
                break; // stop at first matched intent
            }
        }
    }

    /* OPTIONAL: sort by clicks */
    foreach ($intentData as $k => $rows) {
        usort($rows, fn($a, $b) => $b['clicks'] <=> $a['clicks']);
        $intentData[$k] = $rows;
    }
}
// -------------------------------------------------------------------------
// FETCH QUERY BY PAGE (QP / CANNIBALIZATION)
// -------------------------------------------------------------------------
$queryByPage = [];

if ($canShowData && $hasPremiumAccess) {

    $payload = json_encode([
        'shopId' => $shopId,
        'range'      => '30days',
        'type'       => 'qp'
    ]);

    // Build API URL using APP_BASE constant or fallback to relative path
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (defined('APP_BASE')) {
        $basePath = rtrim(APP_BASE, '/');
    } else {
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim($scriptPath, '/');
    }
    $apiUrl = $protocol . '://' . $host . $basePath . '/api/google/get_gsc_report.php';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Dashboard API call error (qp): " . $curlError);
    }

    if ($httpCode !== 200) {
        error_log("Dashboard API call HTTP error (qp): " . $httpCode . " - Response: " . substr($response, 0, 500));
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Dashboard API JSON decode error (qp): " . json_last_error_msg() . " - Response: " . substr($response, 0, 500));
    }

    if (!empty($data['success'])) {
        $queryByPage = $data['rows'] ?? [];
    }
}

// -------------------------------------------------------------------------
// FETCH TOP PAGES (DETAILED REPORT)
// -------------------------------------------------------------------------
$pagesReport = [];

if ($canShowData) {

    $payload = json_encode([
        'shopId' => $shopId,
        'range'      => '30days',
        'type'       => 'pages'
    ]);

    // Build API URL using APP_BASE constant or fallback to relative path
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (defined('APP_BASE')) {
        $basePath = rtrim(APP_BASE, '/');
    } else {
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim($scriptPath, '/');
    }
    $apiUrl = $protocol . '://' . $host . $basePath . '/api/google/get_gsc_report.php';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Dashboard API call error (pages): " . $curlError);
    }

    if ($httpCode !== 200) {
        error_log("Dashboard API call HTTP error (pages): " . $httpCode . " - Response: " . substr($response, 0, 500));
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Dashboard API JSON decode error (pages): " . json_last_error_msg() . " - Response: " . substr($response, 0, 500));
    }

    if (!empty($data['success']) && !empty($data['rows'])) {
        $pagesReport = $data['rows'];
    }

    // ✅ ADD HERE (IMPORTANT)
    $pagesReportAll = $pagesReport;          // full rows
    $pagesReport    = array_slice($pagesReport, 0, 6); // only 6 rows
}


// -------------------------------------------------------------------------
// FETCH COUNTRIES REPORT
// -------------------------------------------------------------------------
$countriesReport = [];

if ($canShowData) {

    $payload = json_encode([
        'shopId' => $shopId,
        'range'      => '30days',
        'type'       => 'countries'
    ]);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if (defined('APP_BASE')) {
        $basePath = rtrim(APP_BASE, '/');
    } else {
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim($scriptPath, '/');
    }

    $apiUrl = $protocol . '://' . $host . $basePath . '/api/google/get_gsc_report.php';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!empty($data['success'])) {
        $countriesReport = $data['rows'] ?? [];
    }
}

// -------------------------------------------------------------------------
// FETCH TOP QUERIES (OVERVIEW)
// -------------------------------------------------------------------------
$queriesReport = [];

if ($canShowData) {

    $payload = json_encode([
        'shopId' => $shopId,
        'range'      => '30days',
        'type'       => 'queries'
    ]);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if (defined('APP_BASE')) {
        $basePath = rtrim(APP_BASE, '/');
    } else {
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim($scriptPath, '/');
    }

    $apiUrl = $protocol . '://' . $host . $basePath . '/api/google/get_gsc_report.php';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!empty($data['success'])) {
        $queriesReport = $data['rows'] ?? [];
    }
}
// -------------------------------------------------------------------------
// FETCH DEVICES REPORT
// -------------------------------------------------------------------------
$devicesReport = [];

if ($canShowData) {

    $payload = json_encode([
        'shopId' => $shopId,
        'range'      => '30days',
        'type'       => 'devices'
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!empty($data['success'])) {
        $devicesReport = $data['rows'] ?? [];
    }
}
function countryNameFromAlpha3($code)
{
    $map = [
        'usa' => 'United States',
        'afg' => 'Afghanistan',
        'ago' => 'Angola',
        'alb' => 'Albania',
        'are' => 'United Arab Emirates',
        'arg' => 'Argentina',
        'arm' => 'Armenia',
        'atg' => 'Antigua and Barbuda',
        'aus' => 'Australia',
        'aut' => 'Austria',
        'ind' => 'India',
        'gbr' => 'United Kingdom',
        'can' => 'Canada',
        'deu' => 'Germany',
        'fra' => 'France',
        'ita' => 'Italy',
        'esp' => 'Spain',
        'jpn' => 'Japan'
    ];

    $code = strtolower($code);
    return $map[$code] ?? strtoupper($code);
}
?>

<?php include './partials/layouts/layoutTop.php' ?>
<script>
    window.gscChartData = <?php echo json_encode($chartData); ?>;
</script>
<script>
    window.topPagesData = <?= json_encode($pagesReportAll ?? []); ?>;
</script>

<style>
    /* Improved GSC dropdown styles */
    #gsc-range-toggle {
        border: 1px solid rgba(15, 23, 42, 0.06);
    }

    #gsc-range-menu {
        box-shadow: 0 8px 20px rgba(2, 6, 23, 0.06);
    }

    #gsc-range-menu .gsc-range-item {
        background: transparent;
        border: none;
        width: 100%;
    }

    #gsc-range-menu .gsc-range-item:focus {
        outline: none;
    }

    #gsc-range-menu .input {
        border: 1px solid rgba(15, 23, 42, 0.06);
        background: transparent;
    }

    @media (max-width: 640px) {
        #gsc-range-menu {
            width: 92vw;
            left: 0;
            right: 0;
        }
    }

    .tab-btn {
        padding-bottom: 10px;
        color: #6b7280;
        /* border-bottom: 2px solid transparent; */
    }

    .tab-btn:hover {
        color: #2563eb;
    }

    .active-tab {
        color: #2563eb;
        border-color: #2563eb;
    }
</style>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-4 mt-2">

    <div class="xl:col-span-4">
        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 dark:bg-neutral-800 rounded-lg mb-2">
            <div class="card-body px-5 py-3 flex justify-between items-center">
                <div>
                    <p class="font-medium text-neutral-900 dark:text-white mb-1">Google Account</p>
                    <h6 class="mb-0 dark:text-white <?= $isConnected ? 'text-success-600' : 'text-neutral-500' ?>">
                        <?= $isConnected ? 'Connected' : 'Not Connected' ?>
                    </h6>
                </div>
                <div class="w-[50px] h-[50px] <?= $isConnected ? 'bg-success-600' : 'bg-gray-400' ?> rounded-full flex justify-center items-center">
                    <iconify-icon icon="dashicons:google" class="text-white text-2xl"></iconify-icon>
                </div>
            </div>
        </div>

        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 dark:bg-neutral-800 rounded-lg mb-2">
            <div class="card-body px-5 py-3 flex justify-between items-center">
                <div>
                    <p class="font-medium text-neutral-900 dark:text-white mb-1">Domain Verification</p>
                    <h6 class="mb-0 dark:text-white <?= $isVerified ? 'text-purple-600' : 'text-neutral-500' ?>">
                        <?= $isVerified ? 'Verified' : 'Verification Pending' ?>
                    </h6>
                </div>
                <div class="w-[50px] h-[50px] bg-purple-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="fa-solid:award" class="text-white text-2xl"></iconify-icon>
                </div>
            </div>
        </div>

        <div class="card shadow-none border border-gray-200 dark:border-neutral-600 dark:bg-neutral-800 rounded-lg">
            <div class="card-body px-5 py-3 flex justify-between items-center">
                <div>
                    <p class="font-medium text-neutral-900 dark:text-white mb-1">Sitemap</p>
                    <h6 class="mb-0 dark:text-white <?= $hasSitemap ? 'text-info-600' : 'text-neutral-500' ?>">
                        <?= $hasSitemap ? 'Submitted' : 'Not Submitted' ?>
                    </h6>
                </div>
                <div class="w-[50px] h-[50px] bg-info-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="fluent:document-one-page-multiple-20-filled" class="text-white text-2xl"></iconify-icon>
                </div>
            </div>
        </div>
    </div>
    <div class="xl:col-span-8 h-full h-[285px] flex items-center justify-center rounded-lg bg-cstm-primary-gradient-light drk-bg-cstm-primary-gradient-dark border border-gray-200 dark:border-neutral-600 p-3">
        <div class="card h-full dark:bg-neutral-800 border border-gray-200 dark:border-neutral-600 relative w-full max-w-xl mx-auto">
            <div class="card-body p-6">
                <div class="flex justify-between items-center mb-4">
                    <h6 class="font-semibold text-lg flex items-center gap-2 text-neutral-900 dark:text-white">
                        <iconify-icon icon="solar:chart-2-bold" class="text-xl text-cstm-primary"></iconify-icon>
                        Traffic Performance (30 Days)
                    </h6>
                </div>
                <div id="gsc-chart-controls" class="flex flex-wrap items-center gap-4 mb-4">
                    <div class="relative" style="min-width:240px;">
                        <button id="gsc-range-toggle" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border bg-white dark:bg-neutral-800 text-sm shadow-sm" type="button">
                            <span id="gsc-range-toggle-label" class="font-medium">Last 30 days</span>
                            <svg class="w-4 h-4 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.06a.75.75 0 111.08 1.04l-4.25 4.658a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div id="gsc-range-menu" class="hidden absolute left-0 mt-2 max-w-[220px] rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 shadow-lg z-50">
                            <div class="p-3">
                                <div class="flex flex-col gap-2">
                                    <button class="gsc-range-item text-left px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-neutral-700" data-range="7">Last 7 days</button>
                                    <button class="gsc-range-item text-left px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-neutral-700" data-range="30">Last 30 days</button>
                                    <button class="gsc-range-item text-left px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-neutral-700" data-range="90">Last 90 days</button>
                                    <button class="gsc-range-item text-left px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-neutral-700" data-range="365">Last year</button>
                                </div>
                                <hr class="my-3 border-gray-100 dark:border-neutral-700">
                                <div class="text-xs text-neutral-500 mb-2">Or choose custom range</div>
                                <div class=" items-center gap-2">
                                    <label class="sr-only" for="gsc-start">Start date</label>
                                    <input id="gsc-start" type="date" class="input input-sm px-3 py-2 rounded-md border w-full mb-2" />
                                    <label class="sr-only" for="gsc-end">End date</label>
                                    <input id="gsc-end" type="date" class="input input-sm px-3 py-2 rounded-md border w-full" />
                                </div>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button id="gsc-range-cancel" class="btn btn-sm px-3 py-1 rounded-md">Cancel</button>
                                    <button id="gsc-custom-apply" class="btn btn-sm px-3 py-1 rounded-md bg-cstm-primary text-white">Apply</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ml-auto text-sm text-neutral-500">Showing <span id="gsc-range-label" class="font-medium">Last 30 days</span></div>
                </div>
                <div id="chart-area" class="h-[285px]"></div>

            </div>
        </div>
    </div>
</div>

<div class="card h-full rounded-lg border-0 mt-4">
    <div class="card-body">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-1 capitalize">Submission History</h3>

        <div class="cstm-scroll-sm overflow-x-auto">
            <table class="table bordered-table sm-table mb-0 table-auto text-sm">
                <thead>
                    <tr>
                        <th class="w-1/3">Site Map Url</th>
                        <th class="!text-center w-1/3">Status</th>
                        <th class="w-1/3">Date</th>
                    </tr>
                </thead>
                <tbody id="submission-history-tbody">
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-10 text-neutral-500">No sitemap submissions yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="submission-history-pagination" class="card-footer bg-transparent border-t border-gray-200 dark:border-neutral-600 p-3 flex justify-end items-center gap-2" style="display: none;">
        </div>

    </div>
</div>




<div class="card h-full rounded-lg border-0 mt-4 relative overflow-x-hidden">
    <div class="card-body">

        <div class="border-b border-gray-200 flex justify-between py-2">
            <nav class="flex flex-wrap text-sm font-medium">
                <button class="tab-btn flex items-center gap-2 text-base shrink-0 py-2.5 px-4 active-tab" data-tab="tab-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Ecommerce/Growth Engine
                </button>
                <button class="tab-btn flex items-center gap-2 text-base shrink-0 py-2.5 px-4" data-tab="tab-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h6l6 6-6 6-6-6V7z" />
                        <circle cx="9.5" cy="9.5" r="1" fill="currentColor" />
                    </svg>

                    Brand & Non-Brand keywords</button>
                <button class="tab-btn flex items-center gap-2 text-base shrink-0 py-2.5 px-4" data-tab="tab-3">

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>


                    Top Pages</button>
                <button class="tab-btn flex items-center gap-2 text-base shrink-0 py-2.5 px-4" data-tab="tab-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-3m3 3v-5m3 5v-7" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 3h8l4 4v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                    </svg>

                    Pages Report
                </button>
                <button class="tab-btn flex items-center gap-2 text-base shrink-0 py-2.5 px-4" data-tab="tab-5">
                    <!-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                    </svg> -->

                    <span data-tooltip-target="tooltip-information-query" class="primary_tooltip_btn  ml-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                        </svg>
                    </span>
                    <div id="tooltip-information-query" role="tooltip" class="tooltip invisible absolute z-20 rounded-lg bg-gray-100 text-gray-900 px-3 py-2 text-sm shadow max-w-xs">
                        Lead Generator/Business
                    </div>
                    Lead Generator/Business
                </button>
                <button class="tab-btn flex items-center gap-2 text-base shrink-0 py-2.5 px-4" data-tab="tab-6">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 2a10 10 0 100 20 10 10 0 000-20z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2 12h20M12 2a15 15 0 010 20M12 2a15 15 0 000 20" />
                    </svg>
                    Countries
                </button>

                <button class="tab-btn flex items-center gap-2 text-base shrink-0 py-2.5 px-4" data-tab="tab-7">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 21h6l-.75-4M4 3h16a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2z" />
                    </svg>
                    Devices
                </button>
                <button class="tab-btn flex items-center gap-2 text-base shrink-0 py-2.5 px-4" data-tab="tab-8">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Overview
                </button>
            </nav>
        </div>

        <div id="tab-loader" class="hidden flex items-center justify-center py-16">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-transparent"></div>
        </div>
        <div id="tab-2" class="tab-content hidden">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mt-2">
                <div class="xl:col-span-6">
                    <div class="card border border-gray-200 rounded-lg h-full">
                        <div class="card-body">
                            <h3 class="text-lg font-semibold mb-4 flex items-center gap-1 capitalize">Brand keywords
                                <span data-tooltip-target="tooltip-Brand-keywords" class="primary_tooltip_btn  ml-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                    </svg>
                                </span>
                                <div id="tooltip-Brand-keywords" role="tooltip" class="tooltip invisible absolute z-20 rounded-lg bg-gray-100 text-gray-900 px-3 py-2 text-sm shadow max-w-xs">
                                    brand keywords
                                </div>
                            </h3>

                            <?php if (!$hasPremiumAccess): ?>
                                <div class="flex flex-col items-center justify-center text-center
                                                bg-gray-50 dark:bg-neutral-700 rounded-lg
                                                border border-dashed border-gray-300 dark:border-neutral-600 p-6">

                                    <div class="w-12 h-12 bg-neutral-200 dark:bg-neutral-700 rounded-full flex items-center justify-center mb-3 mx-auto">
                                        <iconify-icon icon="solar:lock-keyhole-bold-duotone" class="text-2xl text-neutral-400"></iconify-icon>
                                    </div>

                                    <h6 class="text-xl font-medium text-gray-900 dark:text-white">
                                        Brand Keywords Locked
                                    </h6>

                                    <p class="text-sm text-gray-500 mt-1 mb-4">
                                        Upgrade your plan to view branded search queries.
                                    </p>

                                    <button
                                        type="button"
                                        class="open-upgrade-modal btn btn-cstm-primary flex items-center justify-center gap-1">
                                        <iconify-icon icon="mdi:crown-outline" class="text-lg"></iconify-icon>
                                        Upgrade Plan
                                    </button>
                                </div>

                            <?php elseif (empty($brandSplit['brand_keywords'])): ?>
                                <p class="text-sm text-gray-500 text-center py-6">
                                    No branded keywords found
                                </p>
                            <?php else: ?>

                                <div class="overflow-x-auto">
                                    <table class="table table-sm w-full">
                                        <thead>
                                            <tr>
                                                <th>Keyword</th>
                                                <th class="text-right">Impressions</th>
                                                <th class="text-right">Clicks</th>
                                                <th class="text-right">Position</th>
                                            </tr>
                                        </thead>
                                        <?php if ($hasPremiumAccess): ?>
                                            <tbody>
                                                <?php foreach (array_slice($brandSplit['brand_keywords'], 0, 6) as $row): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['keys'][0]) ?></td>
                                                        <td class="text-right"><?= (int)$row['impressions'] ?></td>
                                                        <td class="text-right"><?= (int)$row['clicks'] ?></td>
                                                        <td class="text-right"><?= number_format($row['position'], 1) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            <?php endif; ?>
                            <div class="text-sm text-gray-500 mt-3">
                                Showing top 6 results ·
                                <a href="gsc-report.php?tab=keywords" class="text-blue-600 hover:underline">
                                    View full report →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="xl:col-span-6">
                    <!-- NON-BRAND KEYWORDS -->
                    <div class="card border border-gray-200 rounded-lg h-full">
                        <div class="card-body">
                            <h3 class="text-lg font-semibold mb-4 flex items-center gap-1 capitalize">Non-brand keywords
                                <span data-tooltip-target="tooltip-Non-Brand-keywords" class="primary_tooltip_btn  ml-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                    </svg>
                                </span>
                                <div id="tooltip-Non-Brand-keywords" role="tooltip" class="tooltip invisible absolute z-20 rounded-lg bg-gray-100 text-gray-900 px-3 py-2 text-sm shadow max-w-xs">
                                    Non-brand keywords
                                </div>
                            </h3>

                            <?php if (!$hasPremiumAccess): ?>
                                <div class="flex flex-col items-center justify-center text-center
                                        bg-gray-50 dark:bg-neutral-700 rounded-lg
                                        border border-dashed border-gray-300 dark:border-neutral-600 p-6">

                                    <div class="w-12 h-12 bg-neutral-200 dark:bg-neutral-700 rounded-full flex items-center justify-center mb-3 mx-auto">
                                        <iconify-icon icon="solar:lock-keyhole-bold-duotone" class="text-2xl text-neutral-400"></iconify-icon>
                                    </div>

                                    <h6 class="text-xl font-medium text-gray-900 dark:text-white">
                                        Non-Brand Keywords Locked
                                    </h6>

                                    <p class="text-sm text-gray-500 mt-1 mb-4">
                                        Upgrade your plan to view non-brand search queries.
                                    </p>

                                    <button
                                        type="button"
                                        class="open-upgrade-modal btn btn-cstm-primary flex items-center justify-center gap-1">
                                        <iconify-icon icon="mdi:crown-outline" class="text-lg"></iconify-icon>
                                        Upgrade Plan
                                    </button>
                                </div>

                            <?php elseif (empty($brandSplit['non_brand_keywords'])): ?>
                                <p class="text-sm text-gray-500 text-center py-6">
                                    No non-brand keywords found
                                </p>
                            <?php else: ?>

                                <div class="overflow-x-auto">
                                    <table class="table table-sm w-full">
                                        <thead>
                                            <tr>
                                                <th>Keyword</th>
                                                <th class="text-right">Impressions</th>
                                                <th class="text-right">Clicks</th>
                                                <th class="text-right">Position</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($brandSplit['non_brand_keywords'], 0, 6) as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['keys'][0]) ?></td>
                                                    <td class="text-right"><?= (int)$row['impressions'] ?></td>
                                                    <td class="text-right"><?= (int)$row['clicks'] ?></td>
                                                    <td class="text-right"><?= number_format($row['position'], 1) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <div class="text-sm text-gray-500 mt-3">
                                Showing top 6 results ·
                                <a href="gsc-report.php?tab=keywords" class="text-blue-600 hover:underline">
                                    View full report →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div id="tab-3" class="tab-content hidden">
            <div class="grid grid-cols-1 gap-6 mt-2">

                <!-- <?php if ($hasPremiumAccess && !empty($queryByPage)): ?> -->

                <!-- PREMIUM CONTENT -->
                <div class="card h-full border border-gray-200 dark:border-neutral-600">
                    <div class="card-body">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-1 capitalize">Query by Page</h3>
                        <div class="overflow-x-auto">
                            <table class="table table-sm w-full">
                                <thead>
                                    <tr>
                                        <th>Query</th>
                                        <th>Page</th>
                                        <th class="text-right">Clicks</th>
                                        <th class="text-right">Impr.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($queryByPage, 0, 6) as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['keys'][0]) ?></td>
                                            <td class="truncate max-w-md">
                                                <a href="<?= htmlspecialchars($row['keys'][1]) ?>" target="_blank"
                                                    class="text-blue-600 hover:underline">
                                                    <?= htmlspecialchars($row['keys'][1]) ?>
                                                </a>
                                            </td>
                                            <td class="text-right"><?= (int)$row['clicks'] ?></td>
                                            <td class="text-right"><?= (int)$row['impressions'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-sm text-gray-500 mt-3">
                            Showing top 6 results ·
                            <a href="gsc-report.php?tab=qp" class="text-blue-600 hover:underline">
                                View full report →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- <?php else: ?>

                    <div class="card border border-dashed border-gray-300">
                        <div class="card-body text-center py-10 dark:bg-neutral-700">
                            <div class="w-12 h-12 bg-neutral-200 dark:bg-neutral-700 rounded-full flex items-center justify-center mb-3 mx-auto">
                                <iconify-icon icon="solar:lock-keyhole-bold-duotone" class="text-2xl text-neutral-400"></iconify-icon>
                            </div>
                            <h3 class="text-xl font-semibold mb-2">
                                Query by Page is a Premium Feature
                            </h3>
                            <p class="text-gray-500 mb-4">
                                Upgrade to unlock query-level page insights and detailed performance data.
                            </p>
                            <a href="upgrade.php"
                                class="open-upgrade-modal btn btn-cstm-primary">
                                Upgrade to Premium
                            </a>
                        </div>
                    </div>

                <?php endif; ?> -->

            </div>
        </div>

        <div id="tab-4" class="tab-content hidden">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mt-2">

                <div class="xl:col-span-8">
                    <div class="card h-full flex flex-col dark:bg-neutral-800 border border-gray-200 dark:border-neutral-600 relative overflow-hidden">
                        <div class="card-body p-6">

                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold">Top Pages Report <span data-tooltip-target="tooltip-Top-Page-report" class="primary_tooltip_btn  ml-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                        </svg>
                                    </span>
                                    <div id="tooltip-Top-Page-report" role="tooltip" class="tooltip invisible absolute z-20 rounded-lg bg-gray-100 text-gray-900 px-3 py-2 text-sm shadow max-w-xs">
                                        Top Pages Report
                                    </div>
                                </h3>
                                <span class="text-sm text-gray-500">
                                    <?= min(6, count($pagesReportAll ?? $pagesReport)) ?> Rows
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="table table-sm w-full">
                                    <thead>
                                        <tr>
                                            <th>Page URL</th>
                                            <th class="text-right">Clicks</th>
                                            <th class="text-right">Impr.</th>
                                            <th class="text-right">CTR</th>
                                            <th class="text-right">Pos</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php if (!$hasPremiumAccess): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-10 text-gray-500">

                                                    <div class="flex flex-col items-center justify-center gap-2">
                                                        <div class="w-12 h-12 bg-neutral-200 dark:bg-neutral-700 rounded-full flex items-center justify-center mb-3 mx-auto">
                                                            <iconify-icon icon="solar:lock-keyhole-bold-duotone" class="text-2xl text-neutral-400"></iconify-icon>
                                                        </div>

                                                        <div class="text-xl font-medium text-gray-900 dark:text-white">
                                                            Top Pages Locked
                                                        </div>

                                                        <div class="text-sm text-gray-500 mb-3">
                                                            Upgrade your plan to view top-performing pages.
                                                        </div>

                                                        <!-- ✅ UPGRADE BUTTON -->
                                                        <button
                                                            type="button"
                                                            class="open-upgrade-modal btn btn-cstm-primary flex items-center justify-center gap-1">
                                                            <iconify-icon icon="mdi:crown-outline" class="text-lg"></iconify-icon>
                                                            Upgrade Plan
                                                        </button>

                                                    </div>

                                                </td>
                                            </tr>


                                        <?php elseif (empty($pagesReport)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-gray-500 py-6">
                                                    No data available
                                                </td>
                                            </tr>

                                        <?php else: ?>

                                            <?php foreach ($pagesReport as $row): ?>
                                                <tr>
                                                    <td class="max-w-md truncate">
                                                        <a href="<?= htmlspecialchars($row['keys'][0]) ?>"
                                                            target="_blank"
                                                            class="text-blue-600 hover:underline truncate max-w-[394px]">
                                                            <?= htmlspecialchars($row['keys'][0]) ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-right"><?= (int)$row['clicks'] ?></td>
                                                    <td class="text-right"><?= (int)$row['impressions'] ?></td>
                                                    <td class="text-right"><?= number_format($row['ctr'], 2) ?>%</td>
                                                    <td class="text-right"><?= number_format($row['position'], 1) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                    </tbody>
                                </table>
                            </div>

                            <div class="text-sm text-gray-500 mt-4">
                                Showing 1 to <?= count($pagesReport) ?> of <?= count($pagesReportAll ?? $pagesReport) ?> entries
                                <a href="gsc-report.php?tab=keywords" class="text-blue-600 hover:underline">
                                    View full report →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-4">
                    <div class="card h-full dark:bg-neutral-800 border border-gray-200 dark:border-neutral-600">
                        <div class="card-body p-6">

                            <!-- Header -->
                            <div class="flex items-center justify-between mb-6">
                                <h6 class="font-bold text-lg text-neutral-900 dark:text-white flex items-center gap-2">
                                    <iconify-icon icon="fluent:data-histogram-20-filled"
                                        class="text-xl text-primary-600"></iconify-icon>
                                    Analysis
                                    <span data-tooltip-target="tooltip-Analysis" class="primary_tooltip_btn  ml-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                        </svg>
                                    </span>
                                    <div id="tooltip-Analysis" role="tooltip" class="tooltip invisible absolute z-20 rounded-lg bg-gray-100 text-gray-900 px-3 py-2 text-sm shadow max-w-xs">
                                        Analysis
                                    </div>
                                </h6>

                                <?php if (!$hasPremiumAccess): ?>
                                    <span class="text-xs text-primary-600 font-medium flex items-center gap-1">
                                        <iconify-icon icon="mdi:lock-outline"></iconify-icon>
                                        Locked
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- KPI LIST -->
                            <div class="space-y-3">

                                <!-- Total Clicks -->
                                <div class="flex items-center justify-between
                                                p-4 rounded-xl border border-gray-200 dark:border-neutral-700
                                                bg-white dark:bg-neutral-900">

                                    <div>
                                        <p class="text-sm text-gray-500">Total Clicks</p>
                                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                            <?= $hasPremiumAccess ? number_format($kpi['clicks']) : '—' ?>
                                        </h3>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <iconify-icon icon="mdi:cursor-default-click-outline"
                                            class="text-xl text-blue-500"></iconify-icon>

                                        <?php if (!$hasPremiumAccess): ?>
                                            <iconify-icon icon="mdi:lock-outline"
                                                class="text-lg text-primary-600"></iconify-icon>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Total Impressions -->
                                <div class="flex items-center justify-between
                                                p-4 rounded-xl border border-gray-200 dark:border-neutral-700
                                                bg-white dark:bg-neutral-900">

                                    <div>
                                        <p class="text-sm text-gray-500">Total Impressions</p>
                                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                            <?= $hasPremiumAccess ? number_format($kpi['impressions']) : '—' ?>
                                        </h3>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <iconify-icon icon="mdi:eye-outline"
                                            class="text-xl text-green-500"></iconify-icon>

                                        <?php if (!$hasPremiumAccess): ?>
                                            <iconify-icon icon="mdi:lock-outline"
                                                class="text-lg text-primary-600"></iconify-icon>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Avg CTR -->
                                <div class="flex items-center justify-between
                                                p-4 rounded-xl border border-gray-200 dark:border-neutral-700
                                                bg-white dark:bg-neutral-900">

                                    <div>
                                        <p class="text-sm text-gray-500">Avg CTR</p>
                                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                            <?= $hasPremiumAccess ? $kpi['ctr'] . '%' : '—' ?>
                                        </h3>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <iconify-icon icon="mdi:percent-outline"
                                            class="text-xl text-purple-500"></iconify-icon>

                                        <?php if (!$hasPremiumAccess): ?>
                                            <iconify-icon icon="mdi:lock-outline"
                                                class="text-lg text-primary-600"></iconify-icon>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Avg Position -->
                                <div class="flex items-center justify-between
                                                p-4 rounded-xl border border-gray-200 dark:border-neutral-700
                                                bg-white dark:bg-neutral-900">

                                    <div>
                                        <p class="text-sm text-gray-500">Avg Position</p>
                                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                            <?= $hasPremiumAccess ? $kpi['position'] : '—' ?>
                                        </h3>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <iconify-icon icon="mdi:trophy-outline"
                                            class="text-xl text-primary-600"></iconify-icon>

                                        <?php if (!$hasPremiumAccess): ?>
                                            <iconify-icon icon="mdi:lock-outline"
                                                class="text-lg text-primary-600"></iconify-icon>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA -->
                            <?php if (!$hasPremiumAccess): ?>
                                <div class="mt-6">
                                    <button
                                        type="button"
                                        class="open-upgrade-modal btn btn-cstm-primary flex items-center justify-center gap-1">
                                        <iconify-icon icon="mdi:crown-outline" class="text-lg"></iconify-icon>
                                        Upgrade Plan
                                    </button>
                                </div>
                            <?php endif; ?>

                            <p class="text-xs text-gray-400 italic text-center mt-4">
                                Data based on selected range (Default: 30 days).
                            </p>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-5" class="tab-content hidden">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mt-2">



                <!-- LEFT: INTENT TABLES -->
                <?php foreach (['informational', 'commercial', 'transactional', 'navigational'] as $intent): ?>
                    <div class="xl:col-span-6">
                        <div class="card h-full border border-gray-200 dark:border-neutral-600">
                            <div class="card-body p-6">

                                <h3 class="text-lg font-semibold mb-4 flex items-center gap-1 capitalize capitalize">
                                    <?= ucfirst($intent) ?> Query Information
                                    <span data-tooltip-target="tooltip-<?= ucfirst($intent) ?>" class="primary_tooltip_btn  ml-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                        </svg>
                                    </span>
                                    <div id="tooltip-<?= ucfirst($intent) ?>" role="tooltip" class="tooltip invisible absolute z-20 rounded-lg bg-gray-100 text-gray-900 px-3 py-2 text-sm shadow max-w-xs">
                                        <?= ucfirst($intent) ?> Query Information
                                    </div>
                                </h3>

                                <?php if (!$hasPremiumAccess): ?>
                                    <div class="flex flex-col items-center justify-center text-center
                                                bg-gray-50 dark:bg-neutral-700 rounded-lg
                                                border border-dashed border-gray-300 dark:border-neutral-600 p-6">
                                        <div class="w-12 h-12 bg-neutral-200 dark:bg-neutral-700 rounded-full flex items-center justify-center mb-3">
                                            <iconify-icon icon="solar:lock-keyhole-bold-duotone"
                                                class="text-2xl text-neutral-400"></iconify-icon>
                                        </div>
                                        <h4 class="text-xl font-medium">Query Information Locked</h4>
                                        <p class="text-sm text-gray-500 mt-2 mb-4">
                                            Upgrade your plan to unlock information-based insights.
                                        </p>
                                        <button class="open-upgrade-modal btn btn-cstm-primary flex items-center justify-center gap-1">
                                            <iconify-icon icon="mdi:crown-outline" class="text-lg"></iconify-icon>
                                            Upgrade Plan
                                        </button>
                                    </div>

                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="table table-sm w-full">
                                            <thead>
                                                <tr>
                                                    <th>Query</th>
                                                    <th class="text-right">Clicks</th>
                                                    <th class="text-right">Impr.</th>
                                                    <th class="text-right">Pos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($intentData[$intent])): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-gray-500 py-4">
                                                            No <?= $intent ?> queries found
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach (array_slice($intentData[$intent], 0, 5) as $row): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($row['keys'][0]) ?></td>
                                                            <td class="text-right"><?= (int)$row['clicks'] ?></td>
                                                            <td class="text-right"><?= (int)$row['impressions'] ?></td>
                                                            <td class="text-right"><?= number_format($row['position'], 1) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                <div class="text-sm text-gray-500 mt-3">
                                    Showing top 6 results ·
                                    <a href="gsc-report.php?tab=keywords" class="text-blue-600 hover:underline">
                                        View full report →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>


            </div>
        </div>
        <div id="tab-6" class="tab-content hidden">
            <div class="card border border-gray-200 rounded-lg mt-2">
                <div class="card-body p-6">

                    <h3 class="text-lg font-semibold mb-4">Traffic by Country</h3>

                    <div class="overflow-x-auto">

                        <table class="table table-sm w-full">
                            <thead>
                                <tr>
                                    <th>Country</th>
                                    <th>Clicks</th>
                                    <th>Impressions</th>
                                    <th>CTR</th>
                                    <th>Position</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach (array_slice($countriesReport, 0, 5) as $row): ?>
                                    <tr>
                                        <td><?= countryNameFromAlpha3($row['keys'][0]) ?></td>
                                        <td><?= (int)$row['clicks'] ?></td>
                                        <td><?= (int)$row['impressions'] ?></td>
                                        <td><?= number_format($row['ctr'], 2) ?>%</td>
                                        <td><?= number_format($row['position'], 1) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-sm text-gray-500 mt-3">
                        Showing top 6 results ·
                        <a href="gsc-report.php?tab=keywords" class="text-blue-600 hover:underline">
                            View full report →
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-7" class="tab-content hidden">
            <div class="card border border-gray-200 rounded-lg mt-2">
                <div class="card-body p-6">

                    <h3 class="text-lg font-semibold mb-4">Traffic by Device</h3>

                    <div class="overflow-x-auto">

                        <table class="table table-sm w-full">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Clicks</th>
                                    <th>Impressions</th>
                                    <th>CTR</th>
                                    <th>Position</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($devicesReport as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['keys'][0]) ?></td>
                                        <td><?= (int)$row['clicks'] ?></td>
                                        <td><?= (int)$row['impressions'] ?></td>
                                        <td><?= number_format($row['ctr'], 2) ?>%</td>
                                        <td><?= number_format($row['position'], 1) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-sm text-gray-500 mt-3">
                        Showing top 6 results ·
                        <a href="gsc-report.php?tab=keywords" class="text-blue-600 hover:underline">
                            View full report →
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-8" class="tab-content hidden">

            <div class="card border border-gray-200 mt-2">
                <div class="card-body">

                    <h3 class="text-lg font-semibold mb-4">Top Queries Overview</h3>

                    <div class="overflow-x-auto">
                        <table class="table table-sm w-full">

                            <thead>
                                <tr>
                                    <th>Query</th>
                                    <th class="text-right">Clicks</th>
                                    <th class="text-right">Impressions</th>
                                    <th class="text-right">Position</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if (empty($queriesReport)): ?>

                                    <tr>
                                        <td colspan="4" class="text-center text-gray-500 py-6">
                                            No query data found
                                        </td>
                                    </tr>

                                <?php else: ?>

                                    <?php foreach (array_slice($queriesReport, 0, 6) as $row): ?>

                                        <tr>
                                            <td><?= htmlspecialchars($row['keys'][0]) ?></td>
                                            <td class="text-right"><?= (int)$row['clicks'] ?></td>
                                            <td class="text-right"><?= (int)$row['impressions'] ?></td>
                                            <td class="text-right"><?= number_format($row['position'], 1) ?></td>
                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>

                    <div class="text-sm text-gray-500 mt-3">
                        Showing top 6 results ·
                        <a href="gsc-report.php?tab=queries" class="text-blue-600 hover:underline">
                            View full report →
                        </a>
                    </div>

                </div>
            </div>

        </div>
        <!-- TAB 5: COMMERCIAL/ECOMMERCE QUERY -->
        <div id="tab-1" class="tab-content">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mt-2">
                <div class="xl:col-span-12">
                    <div class="card h-full border border-gray-200 dark:border-neutral-600">
                        <div class="card-body p-6">
                            <h3 class="text-lg font-semibold mb-4 flex items-center gap-1 capitalize">
                                Lead Generator/Business Queries
                                <span data-tooltip-target="tooltip-Ecommerce-Query" class="primary_tooltip_btn  ml-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                    </svg>
                                </span>
                                <div id="tooltip-Ecommerce-Query" role="tooltip" class="tooltip invisible absolute z-20 rounded-lg bg-gray-100 text-gray-900 px-3 py-2 text-sm shadow max-w-xs">
                                    Lead Generator/Business
                                </div>
                            </h3>

                            <?php if (!$hasPremiumAccess): ?>
                                <div class="flex flex-col items-center justify-center text-center bg-gray-50 dark:bg-neutral-700 rounded-lg border border-dashed border-gray-300 dark:border-neutral-600 p-6">
                                    <div class="w-12 h-12 bg-neutral-200 dark:bg-neutral-700 rounded-full flex items-center justify-center mb-3">
                                        <iconify-icon icon="solar:lock-keyhole-bold-duotone" class="text-2xl text-neutral-400"></iconify-icon>
                                    </div>
                                    <h4 class="text-xl font-medium text-gray-900 dark:text-white">Commercial Query Locked</h4>
                                    <p class="text-sm text-gray-500 mt-2 mb-4">
                                        Upgrade your plan to unlock commercial/ecommerce query insights.
                                    </p>
                                    <button class="open-upgrade-modal btn btn-cstm-primary flex items-center justify-center gap-1">
                                        <iconify-icon icon="mdi:crown-outline" class="text-lg"></iconify-icon>
                                        Upgrade Plan
                                    </button>
                                </div>

                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    <?php
                                    $sections = [
                                        'ready_to_buy' => [
                                            'title' => 'Ready to Buy',
                                            'sub' => 'Users ready to purchase. These queries usually convert the best and drive direct revenue.'
                                        ],

                                        'product_research' => [
                                            'title' => 'Product Research',
                                            'sub' => 'Users comparing products or solutions. They know the problem but are evaluating options.'
                                        ],

                                        'trust_comparison' => [
                                            'title' => 'Trust & Comparison',
                                            'sub' => 'Users looking for reviews, comparisons, or proof before making a decision.'
                                        ],

                                        'post_purchase' => [
                                            'title' => 'Post Purchase',
                                            'sub' => 'Existing customers searching for support, tracking, returns, or other post-purchase help.'
                                        ]
                                    ];

                                    foreach ($sections as $key => $section):
                                    ?>

                                        <div class="card border border-gray-200 rounded-lg">
                                            <div class="card-body">
                                                <h3 class="text-lg font-semibold flex items-center gap-1 capitalize capitalize">
                                                    <?= $section['title'] ?>
                                                </h3>

                                                <p class="text-sm text-gray-500 mb-4">
                                                    <?= $section['sub'] ?>
                                                </p>
                                                <div class="overflow-x-auto">
                                                    <table class="table table-sm w-full">
                                                        <thead>
                                                            <tr>
                                                                <th>Query</th>
                                                                <th class="text-right">Clicks</th>
                                                                <th class="text-right">Impr.</th>
                                                                <th class="text-right">Pos</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>

                                                            <?php if (empty($ecommerceQueries[$key])): ?>

                                                                <tr>
                                                                    <td colspan="4" class="text-center text-gray-500 py-4">
                                                                        No queries found
                                                                    </td>
                                                                </tr>

                                                            <?php else: ?>

                                                                <?php foreach (array_slice($ecommerceQueries[$key], 0, 5) as $row): ?>

                                                                    <tr>
                                                                        <td><?= htmlspecialchars($row['keys'][0]) ?></td>
                                                                        <td class="text-right"><?= (int)$row['clicks'] ?></td>
                                                                        <td class="text-right"><?= (int)$row['impressions'] ?></td>
                                                                        <td class="text-right"><?= number_format($row['position'], 1) ?></td>
                                                                    </tr>

                                                                <?php endforeach; ?>

                                                            <?php endif; ?>

                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="text-sm text-gray-500 mt-3">
                                                    Showing top 6 results ·
                                                    <a href="gsc-report.php?tab=keywords" class="text-blue-600 hover:underline">
                                                        View full report →
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>

                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end items-center mt-4">
            <a href="gsc-report.php?tab=keywords" class="h-10 btn btn-cstm-primary flex items-center gap-2 px-3">
                <iconify-icon icon="mdi:poll"></iconify-icon>
                <span>View Details Reports</span>
            </a>
        </div>

    </div>
</div>



<div class="card h-full rounded-lg border-0 mt-4 card-body p-6">

    <div class="card h-full rounded-lg border-0 mb-8 mt-8 card-body p-6">
        <div class="flex justify-start items-start">
            <h3 class="text-xl font-semibold mb-6 flex items-center gap-2">
                <i class="fa-solid fa-shapes text-cstm-primary"></i>
                Our Other Apps
            </h3>
        </div>

        <!-- ✅ SINGLE GRID ONLY -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" id="otherAppsContainer">
            <p class="text-sm text-gray-500">Loading apps...</p>
        </div>
    </div>

</div>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Pagination Logic
        const allHistoryData = <?= json_encode($history) ?>;
        const historyTbody = document.getElementById('submission-history-tbody');
        const paginationContainer = document.getElementById('submission-history-pagination');
        const sitemapUrlDisplay = "<?= htmlspecialchars($sitemapUrl ?? 'NA') ?>";
        const userTimezone = "<?= htmlspecialchars($userTimezone ?? 'UTC') ?>";

        // ONLY RUN PAGINATION IF TABLE EXISTS
        if (historyTbody && paginationContainer) {
            const ITEMS_PER_PAGE = 10;
            let currentPage = 1;

            // Convert PHP datetime to user's timezone format
            function formatDate(datetime) {
                if (!datetime) return '';

                // Create UTC date object from the database datetime string
                const utcDate = new Date(datetime + ' UTC');

                // Format according to user's timezone
                const options = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                    timeZone: userTimezone
                };

                return utcDate.toLocaleString('en-US', options);
            }

            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function renderHistoryPage(page) {
                currentPage = page;
                historyTbody.innerHTML = '';

                if (allHistoryData.length === 0) {
                    historyTbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-neutral-500">No sitemap submissions yet.</td></tr>';
                    paginationContainer.style.display = 'none';
                    return;
                }

                const totalPages = Math.ceil(allHistoryData.length / ITEMS_PER_PAGE);
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;

                const start = (page - 1) * ITEMS_PER_PAGE;
                const paginatedItems = allHistoryData.slice(start, start + ITEMS_PER_PAGE);

                paginatedItems.forEach(log => {
                    const statusBadge = log.status === 'Success' ?
                        '<span class="badge badge-success text-xs bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 rounded-full px-2 py-1 font-semibold">Success</span>' :
                        '<span class="badge badge-danger text-xs bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 rounded-full px-2 py-1 font-semibold">Failed</span>';

                    const response = log.google_response ? escapeHtml(log.google_response.substring(0, 60)) + '...' : 'N/A';

                    historyTbody.innerHTML += `
                    <tr>
                        <td>${escapeHtml(sitemapUrlDisplay)}</td>
                        <td class="!text-center">${statusBadge}</td>
                       
                        <td>${escapeHtml(formatDate(log.submitted_at))}</td>
                        
                        
                    </tr>
                `;
                });

                setupPagination();
            }

            function setupPagination() {
                paginationContainer.innerHTML = '';
                const totalPages = Math.ceil(allHistoryData.length / ITEMS_PER_PAGE);

                if (totalPages <= 1) {
                    paginationContainer.style.display = 'none';
                    return;
                }

                paginationContainer.style.display = 'flex';

                // Previous Button
                const isFirstPage = currentPage === 1;
                paginationContainer.innerHTML += `<button data-page="${currentPage - 1}" class="px-3 py-1 rounded-md text-sm font-medium hover:bg-gray-200 dark:hover:bg-neutral-600 ${isFirstPage ? 'opacity-50 cursor-not-allowed' : ''}" ${isFirstPage ? 'disabled' : ''}>Prev</button>`;

                // Page Number Buttons
                for (let i = 1; i <= totalPages; i++) {
                    const isActive = i === currentPage;
                    paginationContainer.innerHTML += `<button data-page="${i}" class="px-3 py-1 rounded-md text-sm font-medium ${isActive ? 'bg-cstm-primary text-white' : 'bg-gray-100 dark:bg-neutral-700'}">${i}</button>`;
                }

                // Next Button
                const isLastPage = currentPage === totalPages;
                paginationContainer.innerHTML += `<button data-page="${currentPage + 1}" class="px-3 py-1 rounded-md text-sm font-medium hover:bg-gray-200 dark:hover:bg-neutral-600 ${isLastPage ? 'opacity-50 cursor-not-allowed' : ''}" ${isLastPage ? 'disabled' : ''}>Next</button>`;

                paginationContainer.querySelectorAll('button').forEach(button => {
                    button.addEventListener('click', () => renderHistoryPage(parseInt(button.dataset.page)));
                });
            }

            // Initial render
            renderHistoryPage(1);
        }
    })
</script>
<?php
$script = "
window.gscChartData = " . json_encode($chartData) . ";
";
?>
<!-- =======================
   UPGRADE PLAN MODAL
======================= -->
<div id="upgradeModal"
    class="fixed inset-0 bg-black/40 bg-cstm-black-40 backdrop-blur-sm shadow-lg z-50 hidden items-center justify-center">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40"></div>

    <!-- Modal Box -->
    <div class="relative bg-white dark:bg-neutral-800 rounded-xl
                max-w-lg mx-4 p-6 shadow-xl">

        <!-- Close -->
        <button id="closeUpgradeModal"
            class="absolute top-3 right-4 text-gray-400 hover:text-gray-600">
            ✕
        </button>

        <h2 class="text-xl font-semibold mb-2 text-neutral-900 dark:text-white">
            🚀 Unlock Premium Search Console Features
        </h2>

        <p class="text-sm text-gray-500 mb-4">
            Upgrade your plan to unlock advanced Google Search Console analytics and SEO insights.
        </p>

        <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300 mb-6">
            <li>✅ Brand vs Non-Brand keyword analysis & performance split</li>
            <li>✅ Query by Page reports (identify keyword cannibalization)</li>
            <li>✅ Top-performing pages with detailed metrics (clicks, impressions, CTR, position)</li>
            <li>✅ Intent-based query analysis (Informational, Commercial, Transactional, Navigational)</li>
            <li>✅ Full search analytics dashboard with extended date ranges</li>
            <li>✅ Advanced performance tracking & ranking insights</li>
        </ul>

        <div class="flex justify-end gap-3">
            <button id="cancelUpgrade"
                class="px-4 py-2 rounded-md text-sm
                           border border-gray-300 dark:border-neutral-600">
                Cancel
            </button>

            <!-- FINAL ACTION -->
            <a href="<?= htmlspecialchars($billingUrl) ?>"
                class="px-5 py-2 rounded-md text-sm font-medium
                      bg-cstm-primary text-white hover:bg-cstm-primary/90"
                target="_top">
                Upgrade Plan
            </a>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const modal = document.getElementById("upgradeModal");

        // Open modal
        document.querySelectorAll(".open-upgrade-modal").forEach(btn => {
            btn.addEventListener("click", () => {
                modal.classList.remove("hidden");
                modal.classList.add("flex");
            });
        });

        // Close modal
        document.getElementById("closeUpgradeModal")?.addEventListener("click", closeModal);
        document.getElementById("cancelUpgrade")?.addEventListener("click", closeModal);

        // Close when clicking backdrop
        modal.addEventListener("click", (e) => {
            if (e.target === modal) closeModal();
        });

        function closeModal() {
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }
    });
</script>

<script>
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');
    const loader = document.getElementById('tab-loader');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {

            // Active tab style
            tabs.forEach(t => t.classList.remove('active-tab'));
            tab.classList.add('active-tab');

            // Hide all content
            contents.forEach(c => c.classList.add('hidden'));

            // Show loader
            loader.classList.remove('hidden');

            // Simulate loading delay
            setTimeout(() => {
                loader.classList.add('hidden');
                document.getElementById(tab.dataset.tab).classList.remove('hidden');
            }, 600); // adjust delay if needed
        });
    });
</script>
<script>
    document.querySelectorAll('.scroll-tabs').forEach(tabs => {

        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;
        let rafId = null;

        /* ---------- WHEEL (smooth) ---------- */
        tabs.addEventListener('wheel', (e) => {
            e.preventDefault();

            if (rafId) return;

            rafId = requestAnimationFrame(() => {
                tabs.scrollLeft += e.deltaY * 0.8;
                rafId = null;
            });
        }, {
            passive: false
        });

        /* ---------- DRAG (smooth) ----------- */
        tabs.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX;
            scrollLeft = tabs.scrollLeft;
            tabs.classList.add('cursor-grabbing');
        });

        window.addEventListener('mouseup', () => {
            isDown = false;
            tabs.classList.remove('cursor-grabbing');
        });

        tabs.addEventListener('mousemove', (e) => {
            if (!isDown) return;

            if (rafId) return;

            rafId = requestAnimationFrame(() => {
                const dx = e.pageX - startX;
                tabs.scrollLeft = scrollLeft - dx;
                rafId = null;
            });
        });

        tabs.addEventListener('mouseleave', () => {
            isDown = false;
            tabs.classList.remove('cursor-grabbing');
        });

    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const container = document.getElementById('otherAppsContainer');
        const shopId = '<?php echo $shopId; ?>';

        if (!shopId) {
            container.innerHTML = '<p class="text-sm text-gray-500">Instance ID missing</p>';
            return;
        }

        fetch(`api/index-apps.php?shop_id=${encodeURIComponent(shopId)}`)
            .then(res => res.json())
            .then(res => {

                if (!res.success || !res.data || res.data.length === 0) {
                    container.innerHTML = '<p class="text-sm text-gray-500">No apps available.</p>';
                    return;
                }

                container.innerHTML = '';

                res.data.forEach(app => {

                    const link = app.button_link ? app.button_link : '#';
                    const image = app.image_url ? app.image_url : '/assets/default-app.png';
                    const description = app.description ? app.description : '';

                    container.innerHTML += `
                <div class="lg:col-span-6">
                    <a href="${link}" target="_blank" class="block h-full">
                        <div class="w-full card border border-gray-200 dark:border-neutral-600
                                    rounded-xl overflow-hidden flex items-start p-4
                                    hover:shadow-md transition-shadow dark:bg-neutral-800 h-full">

                            <div class="flex-shrink-0 mr-4 h-16 w-16 bg-gray-50 rounded-full
                                        flex items-center justify-center border border-gray-200">
                                <img src="${image}" alt="App Icon"
                                     class="h-10 w-10 object-contain">
                            </div>

                            <div class="flex-grow">
                                <div class="flex items-start justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 leading-tight">
                                        ${app.title}
                                    </h3>

                                    ${app.app_price ? `
                                    <span class="text-xs font-medium text-cstm-primary
                                                 bg-cstm-primary-20 px-2 py-0.5 rounded-full ml-4">
                                        ${app.app_price}
                                    </span>` : ''}
                                </div>

                                <p class="text-sm text-gray-600 mt-1 mb-3">
                                    ${description}
                                </p>
                            </div>
                        </div>
                    </a>
                </div>`;
                });

            })
            .catch(err => {
                console.error(err);
                container.innerHTML =
                    '<p class="text-sm text-red-500">Failed to load apps.</p>';
            });

    });
</script>
<?php include './partials/layouts/layoutBottom.php' ?>