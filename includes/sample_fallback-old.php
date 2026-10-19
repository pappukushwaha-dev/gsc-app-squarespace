<?php
/**
 * includes/sample_fallback.php
 *
 * index.php me SIRF EK JAGAH include karna hai — saare curl fetch ke BAAD,
 * aur `?>` (HTML shuru hone) se PEHLE.
 *
 * RULE:
 *   Chart me asli data aa gaya   -> kahin bhi sample nahi, banner bhi nahi.
 *   Chart me asli data nahi aaya -> saare khali section sample se bhar do.
 *
 * Yaani chart hi decide karta hai ki dashboard sample mode me hai ya nahi.
 */

require_once __DIR__ . '/sample_data.php';

// Google shuru ke dino me 1-2 din ka aadha-adhura data bhejta hai. Utne se
// chart khali jaisa dikhta hai, isliye kam se kam itne din chahiye.
$GSC_MIN_CHART_DAYS = 7;

// Kaun kaun se section sample dikha rahe hain
$gscSampleSections = [];

$chartDays = count($chartData['dates'] ?? []);
$gscChartHasRealData = ($chartDays >= $GSC_MIN_CHART_DAYS);

if (!$gscChartHasRealData) {

    // ------------------------------------------------------------- CHART
    $chartData = gsc_sample_chart();
    $kpi       = gsc_sample_kpi();
    $gscSampleSections[] = 'chart';

    // --------------------------------------------------- BRAND KEYWORDS
    if (!isset($brandSplit) || !is_array($brandSplit)) {
        $brandSplit = [];
    }

    if (gsc_needs_sample($brandSplit['brand_keywords'] ?? [])) {
        $brandSplit['brand_keywords'] = gsc_sample_brand_keywords();
        $gscSampleSections[] = 'brand';
    }

    if (gsc_needs_sample($brandSplit['non_brand_keywords'] ?? [])) {
        $brandSplit['non_brand_keywords'] = gsc_sample_non_brand_keywords();
        $gscSampleSections[] = 'non_brand';
    }

    if (empty($brandSplit['branded_clicks']) && empty($brandSplit['non_branded_clicks'])) {
        $brandSplit = array_merge($brandSplit, gsc_sample_brand_split());
    }

    // ----------------------------------------------------- QUERY BY PAGE
    if (gsc_needs_sample($queryByPage ?? [])) {
        $queryByPage = gsc_sample_query_by_page();
        $gscSampleSections[] = 'query_by_page';
    }

    // ------------------------------------------------------- PAGES REPORT
    if (gsc_needs_sample($pagesReport ?? [])) {
        $pagesReportAll = gsc_sample_pages();
        $pagesReport    = array_slice($pagesReportAll, 0, 6);
        $gscSampleSections[] = 'pages';
    }

    // ------------------------------- OVERVIEW / COUNTRIES / DEVICES
    if (gsc_needs_sample($overviewReport ?? [])) {
        $overviewReport = gsc_sample_non_brand_keywords();
        $gscSampleSections[] = 'overview';
    }

    if (gsc_needs_sample($countriesReport ?? [])) {
        $countriesReport = gsc_sample_countries();
        $gscSampleSections[] = 'countries';
    }

    if (gsc_needs_sample($devicesReport ?? [])) {
        $devicesReport = gsc_sample_devices();
        $gscSampleSections[] = 'devices';
    }

    // ---------------------------------------------------- INTENT BUCKETS
    if (!isset($ecommerceIntentData) || !is_array($ecommerceIntentData)) {
        $ecommerceIntentData = [];
    }
    foreach (gsc_sample_ecommerce_intent() as $bucket => $sampleRows) {
        if (gsc_needs_sample($ecommerceIntentData[$bucket] ?? [])) {
            $ecommerceIntentData[$bucket] = $sampleRows;
            $gscSampleSections[] = 'ecom_' . $bucket;
        }
    }

    if (!isset($intentData) || !is_array($intentData)) {
        $intentData = [];
    }
    foreach (gsc_sample_search_intent() as $bucket => $sampleRows) {
        if (gsc_needs_sample($intentData[$bucket] ?? [])) {
            $intentData[$bucket] = $sampleRows;
            $gscSampleSections[] = 'intent_' . $bucket;
        }
    }

    // ------------------------------------------------------- ALL DATA TAB
    if (gsc_needs_sample($positionBands ?? [])) {
        $positionBands = gsc_sample_position_bands();
        $gscSampleSections[] = 'position_bands';
    }

    if (gsc_needs_sample($strikingDistance ?? [])) {
        $strikingDistance = gsc_sample_striking_distance();
        $gscSampleSections[] = 'striking_distance';
    }

    if (gsc_needs_sample($cannibalization ?? [])) {
        $cannibalization = gsc_sample_cannibalization();
        $gscSampleSections[] = 'cannibalization';
    }

    if (gsc_needs_sample($searchAppearanceRows ?? [])) {
        $searchAppearanceRows = gsc_sample_search_appearance();
        $gscSampleSections[] = 'search_appearance';
    }

    if (gsc_needs_sample($sitemapStatus ?? [])) {
        $sitemapStatus = gsc_sample_sitemap_status();
        $gscSampleSections[] = 'sitemap_status';
    }
}

// -------------------------------------------------------------------------
// Global flag — banner isse decide hota hai
// -------------------------------------------------------------------------
$gscShowingSample = !empty($gscSampleSections);

/**
 * Kya ye section sample data dikha raha hai?
 * Use: gsc_is_sample('brand'), gsc_is_sample('countries') ...
 */
function gsc_is_sample(string $section): bool
{
    global $gscSampleSections;
    return in_array($section, $gscSampleSections ?? [], true);
}

/**
 * Dashboard ke upar dikhne wala banner.
 * index.php me sirf `<?= gsc_sample_banner() ?>` likhna hai.
 */
function gsc_sample_banner(): string
{
    global $gscShowingSample;

    if (!$gscShowingSample) return '';

    return '
    <div class="flex items-start gap-3 rounded-lg border border-warning-200
                bg-warning-50 dark:bg-warning-900/20 dark:border-warning-800
                px-4 py-3 mb-4">
        <iconify-icon icon="solar:info-circle-bold"
            class="text-xl text-warning-600 shrink-0 mt-0.5"></iconify-icon>
        <div>
            <p class="font-medium text-warning-800 dark:text-warning-300 mb-0.5">
                You are viewing sample data
            </p>
            <p class="text-sm text-warning-700 dark:text-warning-400">
                Google Search Console needs up to 48 hours to collect your first
                report. Everything below is example data so you can explore the
                dashboard — it will be replaced by your real numbers automatically.
            </p>
        </div>
    </div>';
}