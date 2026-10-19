<?php
// step-one.php — Squarespace site URL entry + Squarespace verification
// User must enter valid Squarespace site URL before Google connect is enabled
// site_id from Squarespace verification becomes the instance_id equivalent

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/credentials.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message      = '';
$messageType  = 'info'; // info | success | error
$siteVerified = false;
$siteId       = '';
$siteUrl      = '';
$siteName     = '';

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUrl = trim($_POST['website_url'] ?? '');

    if ($inputUrl === '') {
        $message     = 'Please enter your Squarespace website URL.';
        $messageType = 'error';
    } else {
        // Normalize URL (same as example/account_provision.php normalizeDomain)
        $website = trim(strtolower($inputUrl));
        if (!preg_match('#^https?://#', $website)) {
            $website = 'http://' . $website;
        }
        $parts = parse_url($website);
        if (empty($parts['host'])) {
            $message     = 'Invalid website URL.';
            $messageType = 'error';
        } else {
            $host = preg_replace('/^www\./', '', $parts['host']);
            $siteUrl = 'https://' . $host . '/';

            // --- Verify site is Squarespace ---
            // Fetch homepage HTML and check for Squarespace markers
            $html = @file_get_contents($siteUrl);
            $isSquarespace = false;
            $detectedSiteId = '';

            if ($html !== false) {
                // Squarespace markers in HTML
                if (preg_match('/squarespace/i', $html)) {
                    $isSquarespace = true;
                }
                // Try to extract a site ID from meta tags or JSON-LD
                if (preg_match('/<meta[^>]+name=["\']generator["\'][^>]+content=["\']([^"\']*squarespace[^"\']*)["\']/i', $html, $m)) {
                    $detectedSiteId = $m[1];
                }
                // Fallback: use host as site_id if no specific ID found
                if ($detectedSiteId === '') {
                    $detectedSiteId = $host;
                }
            }

            if (!$isSquarespace) {
                $message     = 'This website does not appear to be a Squarespace site. Please enter a valid Squarespace URL.';
                $messageType = 'error';
            } else {
                $siteVerified = true;
                $siteId    = $detectedSiteId;
                $siteName  = $host;

                // --- Save / update SquarespaceSite record ---
                $check = $pdo->prepare("SELECT id FROM SquarespaceSite WHERE instance_id = :iid LIMIT 1");
                $check->execute([':iid' => $siteId]);
                $exists = $check->fetchColumn();

                if ($exists) {
                    $stmt = $pdo->prepare("
                        UPDATE SquarespaceSite
                        SET site_url = :url, site_display_name = :name, event_type = 'APPINSTALLED', updated_at = NOW()
                        WHERE instance_id = :iid
                    ");
                    $stmt->execute([
                        ':url'  => $siteUrl,
                        ':name' => $siteName,
                        ':iid'  => $siteId,
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO SquarespaceSite
                            (instance_id, site_id, site_url, site_display_name, domain, event_type, is_active, created_at)
                        VALUES (:iid, :sid, :url, :name, :dom, 'APPINSTALLED', 1, NOW())
                    ");
                    $stmt->execute([
                        ':iid'  => $siteId,
                        ':sid'  => $siteId,
                        ':url'  => $siteUrl,
                        ':name' => $siteName,
                        ':dom'  => $host,
                    ]);
                }

                // Store in session as instance_id equivalent
                $_SESSION['instanceid']        = $siteId;
                $_SESSION['instance_id']       = $siteId;
                $_SESSION['squarespace_site_id'] = $siteId;
                $_SESSION['squarespace_site_url'] = $siteUrl;

                $message     = 'Squarespace site verified successfully! You can now connect with Google.';
                $messageType = 'success';
            }
        }
    }
}

// --- If already verified in session ---
if (isset($_SESSION['instanceid']) && !$siteVerified) {
    $siteVerified = true;
    $siteId       = $_SESSION['instanceid'];
    $siteUrl      = $_SESSION['squarespace_site_url'] ?? '';
    $stmt = $pdo->prepare("SELECT site_display_name FROM SquarespaceSite WHERE instance_id = :iid LIMIT 1");
    $stmt->execute([':iid' => $siteId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $siteName = $row['site_display_name'] ?? '';
}
?>
<style>
.s1x {
    --s1-ink:     #0f172a;
    --s1-ink-2:   #475569;
    --s1-ink-3:   #94a3b8;
    --s1-line:    #e6eaf2;
    --s1-blue:    #3b5bfd;
    --s1-surface: #ffffff;
}
.dark .s1x {
    --s1-ink:     #f1f5f9;
    --s1-surface: #262626;
}
.s1x-shell {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr);
    max-width: 940px;
    margin: 0 auto;
    border: 1px solid var(--s1-line);
    border-radius: 20px;
    overflow: hidden;
    background: var(--s1-surface);
    box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 8px 28px rgba(15,23,42,.06);
}
.s1x-left {
    padding: 32px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    border-right: 1px solid var(--s1-line);
}
.s1x-right {
    padding: 32px 40px;
}
.s1x h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--s1-ink);
    margin: 0 0 8px 0;
}
.s1x p.sub {
    color: var(--s1-ink-2);
    margin: 0 0 24px 0;
    font-size: 0.95rem;
}
.form-group {
    margin-bottom: 16px;
}
.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--s1-ink-2);
    margin-bottom: 6px;
}
.form-group input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--s1-line);
    border-radius: 8px;
    font-size: 0.95rem;
    color: var(--s1-ink);
    background: #fff;
}
.dark .form-group input {
    background: #1f2937;
    border-color: #374151;
    color: #f1f5f9;
}
.btn-connect {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: var(--s1-blue);
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}
.btn-connect:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.btn-connect:not(:disabled):hover {
    background: #2a4ed9;
}
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 0.9rem;
}
.alert-success {
    background: #ecfdf5;
    border: 1px solid #86efac;
    color: #15803d;
}
.alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
}
.alert-info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
}
.dark .alert-success { background: #064e3b; border-color: #16a34a; color: #86efac; }
.dark .alert-error { background: #450a0a; border-color: #dc2626; color: #fca5a5; }
.dark .alert-info { background: #1e3a8a; border-color: #2563eb; color: #93c5fd; }
.verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #dcfce7;
    color: #15803d;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 16px;
    border: 1px solid #86efac;
}
.dark .verified-badge { background: #065f46; color: #86efac; border-color: #16a34a; }
.site-info {
    background: #f8fafc;
    border: 1px solid var(--s1-line);
    border-radius: 8px;
    padding: 16px;
    margin-top: 20px;
}
.dark .site-info { background: #111827; border-color: #374151; }
.site-info p { margin: 4px 0; font-size: 0.9rem; color: var(--s1-ink-2); }
.site-info .label { font-weight: 600; color: var(--s1-ink-3); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
</style>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="s1x-shell">
    <div class="s1x-left">
        <h2>Connect Your Squarespace Site</h2>
        <p class="sub">Enter your Squarespace website URL so we can verify it and connect it to Google Search Console.</p>

        <?php if (!$siteVerified): ?>
        <form method="POST" action="step-one.php">
            <div class="form-group">
                <label for="website_url">Website URL</label>
                <input type="url" id="website_url" name="website_url"
                    placeholder="https://yoursite.squarespace.com"
                    value="<?= htmlspecialchars($_POST['website_url'] ?? '') ?>" required>
            </div>
            <button type="submit" class="btn-connect">Verify Squarespace Site</button>
        </form>
        <p style="margin-top:16px; font-size:0.85rem; color:var(--s1-ink-3);">
            Make sure your site is published on Squarespace before verifying.
        </p>
        <?php else: ?>
        <div class="verified-badge">
            <span>&#10003;</span> Site Verified
        </div>
        <div class="site-info">
            <p><span class="label">Site ID</span><br><?= htmlspecialchars($siteId) ?></p>
            <p><span class="label">Site URL</span><br><?= htmlspecialchars($siteUrl) ?></p>
            <p><span class="label">Platform</span><br>Squarespace</p>
        </div>
        <div style="margin-top:24px; text-align:center;">
            <a href="connect_google.php?return=setup-wizard.php?step=2"
                class="btn-connect" style="background:#6b7280;">
                Connect with Google
            </a>
        </div>
        <p style="margin-top:12px; font-size:0.85rem; color:var(--s1-ink-3); text-align:center;">
            Once connected, you can verify your domain and submit your sitemap.
        </p>
        <?php endif; ?>
    </div>
    <div class="s1x-right">
        <div style="font-size:0.875rem; color:var(--s1-ink-2); line-height:1.6;">
            <p><strong>What happens next?</strong></p>
            <ol style="margin:12px 0; padding-left:20px;">
                <li>We verify your site is on Squarespace.</li>
                <li>You connect your Google account (OAuth).</li>
                <li>We create and verify your Google Search Console property.</li>
                <li>You submit your sitemap and start tracking.</li>
            </ol>
            <p style="margin-top:16px;"><strong>Note:</strong> Your Squarespace <code>site_id</code> will be used as your instance ID across the dashboard — just like Wix, Shopify, and other platforms.</p>
        </div>
    </div>
</div>

