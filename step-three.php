<?php
// step-three.php — Squarespace sitemap submission
// Same pattern as Wix step-three.php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/credentials.php';
require_once __DIR__ . '/includes/google/get_account.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['show_review_modal'] = true;

$instanceId = $_SESSION['instanceid'] ?? null;
if (!$instanceId) {
    die("Missing instance ID");
}

// Google Account
$google = getGoogleAccountByUser($instanceId);
$isConnected = ($google && !empty($google['access_token']));

if (!$isConnected) {
    die("<div class='p-10 text-center text-red-600'>Google account not connected.</div>");
}

// Domain from verification table
$stmt = $pdo->prepare("SELECT site_url FROM gsc_domain_verifications WHERE instance_id = ?");
$stmt->execute([$instanceId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Build a usable website URL for default sitemap
$storedSiteUrl = $row['site_url'] ?? '';
$siteUrl = null;

if ($storedSiteUrl) {
    $storedSiteUrl = trim($storedSiteUrl);
    if (stripos($storedSiteUrl, 'sc-domain:') === 0) {
        $host = substr($storedSiteUrl, strlen('sc-domain:'));
        $host = preg_replace('#^www\.#i', '', $host);
        $siteUrl = 'https://' . $host . '/';
    } else {
        $siteUrl = rtrim($storedSiteUrl, '/') . '/';
    }
}

$sitemapDefault = $siteUrl ? $siteUrl . "sitemap.xml" : "";

// Optional: trial expiry
$trialExpiresOnStep3 = null;
try {
    $stmt = $pdo->prepare("
        SELECT started_at, expires_on
        FROM app_free_trials
        WHERE instance_id = ? AND status = 'active'
        ORDER BY started_at DESC
        LIMIT 1
    ");
    $stmt->execute([$instanceId]);
    $trial = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($trial && !empty($trial['expires_on'])) {
        $trialExpiresOnStep3 = $trial['expires_on'];
    }
} catch (Throwable $e) {
    // ignore
}

// -----------------------------------------------------
// LOAD OR CREATE VERIFICATION RECORD
// -----------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM gsc_domain_verifications WHERE instance_id = ?");
$stmt->execute([$instanceId]);
$domain = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$domain) {
    $stmt = $pdo->prepare("
        INSERT INTO gsc_domain_verifications
            (instance_id, site_url, verification_status)
        VALUES (?, ?, 'pending')
    ");
    $stmt->execute([$instanceId, $siteUrl ?? '']);

    $domain = [
        'instance_id'         => $instanceId,
        'site_url'            => $siteUrl ?? '',
        'meta_token'          => null,
        'meta_tag'            => null,
        'verification_status' => 'pending'
    ];
}

$status = $domain['verification_status'] ?? 'pending';
?>
<style>
.s3x {
    max-width: 700px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.s3x-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 32px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.dark .s3x-card { background: #1f2937; border-color: #374151; }
.s3x h2 { font-size: 1.5rem; font-weight: 700; margin: 0 0 8px 0; color: #111827; }
.dark .s3x h2 { color: #f1f5f9; }
.s3x p.sub { color: #6b7280; margin: 0 0 24px 0; }
.dark .s3x p.sub { color: #9ca3af; }
.s3x .form-group { margin-bottom: 16px; }
.s3x label { display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
.dark .s3x label { color: #e5e7eb; }
.s3x input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    background: #fff;
    color: #111827;
}
.dark .s3x input { background: #374151; border-color: #4b5563; color: #f1f5f9; }
.s3x .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: #3b5bfd;
    color: #fff;
}
.s3x .btn:hover { background: #2a4ed9; }
.s3x .btn:disabled { opacity: 0.6; cursor: not-allowed; }
.s3x .alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 0.9rem;
}
.s3x .alert-success { background: #ecfdf5; border: 1px solid #86efac; color: #15803d; }
.s3x .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.s3x .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
.dark .s3x .alert-success { background: #064e3b; border-color: #16a34a; color: #86efac; }
.dark .s3x .alert-error { background: #450a0a; border-color: #dc2626; color: #fca5a5; }
.dark .s3x .alert-info { background: #1e3a8a; border-color: #2563eb; color: #93c5fd; }
.s3x .hint {
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 8px;
}
.dark .s3x .hint { color: #9ca3af; }
</style>

<?php if ($status === 'verified'): ?>
<div class="s3x">
    <div class="s3x-card">
        <h2>Submit Your Sitemap</h2>
        <p class="sub">Your Squarespace domain is verified. Submit your sitemap to start tracking performance in Google Search Console.</p>

        <div class="alert alert-info">
            <strong>Default sitemap:</strong> <code><?= htmlspecialchars($sitemapDefault) ?></code><br>
            Squarespace automatically generates sitemap.xml at the root of your domain.
        </div>

        <form method="POST" action="api/google/submit_sitemap.php">
            <div class="form-group">
                <label for="sitemap_url">Sitemap URL</label>
                <input type="url" id="sitemap_url" name="sitemap_url"
                    value="<?= htmlspecialchars($sitemapDefault) ?>" required>
                <p class="hint">Enter the full sitemap URL (usually ends with sitemap.xml)</p>
            </div>
            <input type="hidden" name="instanceId" value="<?= $instanceId ?>">
            <button type="submit" class="btn">Submit Sitemap</button>
        </form>

        <div style="margin-top:24px; text-align:center;">
            <a href="dashboard.php" class="btn" style="background:#6b7280; display:inline-block; text-decoration:none;">
                Go to Dashboard
            </a>
        </div>
    </div>
</div>
<?php else: ?>
<div class="s3x">
    <div class="s3x-card">
        <h2>Domain Not Verified</h2>
        <p class="sub">Please complete domain verification in Step 2 before submitting your sitemap.</p>
        <div style="text-align:center; margin-top:24px;">
            <a href="setup-wizard.php?step=2" class="btn">Go to Step 2 — Verify Domain</a>
        </div>
    </div>
</div>
<?php endif; ?>
