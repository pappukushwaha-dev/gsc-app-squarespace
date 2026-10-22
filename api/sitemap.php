<?php
/**
 * GoHighLevel Sitemap API
 * URL:
 * /api/sitemap.php?shop_id=XXXX
 */

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

header('Content-Type: application/json');

// ===============================
// 1️⃣ Include DB Connection
// ===============================
// require_once __DIR__ . '/../../includes/config.php';
// require_once __DIR__ . '/../../includes/db.php';

// ===============================
// 2️⃣ Validate shop_id
// ===============================
if (!isset($_GET['shop_id']) || empty($_GET['shop_id'])) {
    echo json_encode([
        "status" => false,
        "message" => "shop_id is required"
    ]);
    exit;
}

$shop_id = trim($_GET['shop_id']);

// ===============================
// 3️⃣ Get Domain from Database
// ⚠️ CHANGE TABLE NAME IF NEEDED
// ===============================
try {

    $stmt = $pdo->prepare("SELECT domain FROM SquarespaceSite WHERE shop_id = ?");
    $stmt->execute([$shop_id]);
    $site = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$site) {
        echo json_encode([
            "status" => false,
            "message" => "Invalid shop_id"
        ]);
        exit;
    }

    $domain = rtrim($site['domain'], '/');

} catch (Exception $e) {
    echo json_encode([
        "status" => false,
        "message" => "Database error",
        "error" => $e->getMessage()
    ]);
    exit;
}

// ===============================
// 4️⃣ Fetch XML via cURL
// ===============================
function fetchXml($url)
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'GHL Sitemap API'
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return false;
    }

    return $response;
}

// ===============================
// 5️⃣ Crawl Sitemap (Recursive)
// ===============================
function crawlSitemap($sitemapUrl, &$collectedUrls = [])
{
    $xmlContent = fetchXml($sitemapUrl);

    if (!$xmlContent) return;

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlContent);

    if (!$xml) return;

    $rootName = $xml->getName();

    // Normal sitemap
    if ($rootName === 'urlset') {
        foreach ($xml->url as $url) {
            $loc = trim((string)$url->loc);
            if (!empty($loc)) {
                $collectedUrls[] = $loc;
            }
        }
    }

    // Sitemap index
    if ($rootName === 'sitemapindex') {
        foreach ($xml->sitemap as $sitemap) {
            $childSitemap = trim((string)$sitemap->loc);
            if (!empty($childSitemap)) {
                crawlSitemap($childSitemap, $collectedUrls);
            }
        }
    }
}

// ===============================
// 6️⃣ Start Crawling
// ===============================
$sitemapUrl = $domain . "/sitemap.xml";

$allUrls = [];
crawlSitemap($sitemapUrl, $allUrls);

$allUrls = array_values(array_unique($allUrls));

// ===============================
// 7️⃣ Return JSON Response
// ===============================
// First successful sitemap submit -> review trigger (marked once).
$showReview = false;
try {
    require_once __DIR__ . '/../includes/review-triggers.php';
    $showReview = review_mark_trigger($pdo, (string) $shop_id, 'sitemap_submitted');
} catch (Throwable $e) { /* silent */ }

echo json_encode([
    "status" => true,
    "show_review" => $showReview,
    "shop_id" => $shop_id,
    "domain" => $domain,
    "total_urls" => count($allUrls),
    "urls" => $allUrls
], JSON_PRETTY_PRINT);

exit;