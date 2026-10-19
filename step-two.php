<?php
// step-two.php — Squarespace domain verification
// Same pattern as Wix step-two.php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/credentials.php';
require_once __DIR__ . '/includes/google/get_account.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$instanceId = $_SESSION['instanceid'] ?? null;
if (!$instanceId) {
    die("Instance ID missing");
}

// Get Google account
$googleAccount = $instanceId ? getGoogleAccountByUser($instanceId) : null;
$isConnected = (
    $googleAccount &&
    $googleAccount['connected'] == 1 &&
    (
        !empty($googleAccount['access_token']) ||
        !empty($googleAccount['refresh_token'])
    )
);

// -----------------------------------------------------
// 1. GET SITE URL FROM SquarespaceSite TABLE
// -----------------------------------------------------
$stmt = $pdo->prepare("
    SELECT site_url, site_display_name
    FROM SquarespaceSite
    WHERE instance_id = ?
    LIMIT 1
");
$stmt->execute([$instanceId]);
$siteRow = $stmt->fetch(PDO::FETCH_ASSOC);
$siteUrl = $siteRow['site_url'] ?? null;

$normalizedSiteUrl = null;
if ($siteUrl) {
    $normalizedSiteUrl = rtrim(trim($siteUrl), '/') . '/';
}

// HARD GUARD
if (!$normalizedSiteUrl) {
    die("Invalid Squarespace site URL");
}

// -----------------------------------------------------
// 2. LOAD OR CREATE VERIFICATION RECORD
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
    $stmt->execute([$instanceId, $normalizedSiteUrl]);

    $domain = [
        'instance_id'         => $instanceId,
        'site_url'            => $normalizedSiteUrl,
        'meta_token'          => null,
        'meta_tag'            => null,
        'verification_status' => 'pending'
    ];
}

$metaTag   = $domain['meta_tag'] ?? '';
$metaToken = $domain['meta_token'] ?? '';
$status    = $domain['verification_status'];

// -----------------------------------------------------
// 3. SERVER-SIDE CHECK via check_property_status.php
// -----------------------------------------------------
// (Runs on page load; updates DB if property is verified)
$checkUrl = __DIR__ . '/api/google/check_property_status.php?instanceId=' . urlencode($instanceId);
$checkResult = @file_get_contents($checkUrl);
if ($checkResult !== false) {
    $checkData = json_decode($checkResult, true);
    if ($checkData && !empty($checkData['verified'])) {
        $status = 'verified';
    }
}
?>
<style>
.s1x {
    --s1-ink:     #0f172a;
    --s1-ink-2:   #475569;
    --s1-surface: #ffffff;
}
.dark .s1x {
    --s1-ink:     #f1f5f9;
    --s1-surface: #262626;
}
.s1x-shell {
    max-width: 940px;
    margin: 0 auto;
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}
.btn-cstm-primary {
    background: #3b5bfd;
    color: #fff;
}
.btn-cstm-primary:hover:not(:disabled) {
    background: #2a4ed9;
}
.btn-cstm-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.btn-outline-primary {
    background: #fff;
    border: 1px solid #3b5bfd;
    color: #3b5bfd;
}
.btn-outline-primary:hover {
    background: #f0f5ff;
}
.hidden { display: none !important; }
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
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
.token-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    margin-top: 12px;
}
.dark .token-box { background: #111827; border-color: #374151; }
.token-box textarea {
    width: 100%;
    font-family: ui-monospace, SF Mono, monospace;
    font-size: 0.85rem;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    resize: vertical;
}
.dark .token-box textarea { background: #1f2937; border-color: #374151; color: #f1f5f9; }
.copy-btn {
    margin-top: 8px;
    padding: 6px 12px;
    font-size: 0.85rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
}
.dark .copy-btn { background: #1f2937; border-color: #374151; color: #f1f5f9; }
</style>

<?php if ($status === 'verified'): ?>
<div class="alert alert-success">
    <strong>Verified!</strong> Your Squarespace domain is verified in Google Search Console.
</div>
<div style="text-align:center;">
    <a href="setup-wizard.php?step=3"
        class="btn btn-cstm-primary">Next: Submit Sitemap <span>&#8594;</span></a>
</div>
<?php else: ?>
<div class="alert alert-info">
    <strong>Step 2 of 3 — Verify Your Domain</strong><br>
    Your Squarespace site: <code><?= htmlspecialchars($normalizedSiteUrl) ?></code>
</div>

<?php if (!$isConnected): ?>
<div class="alert alert-error">
    Google account not connected. Please go back to Step 1 and connect with Google first.
</div>
<div style="text-align:center; margin-top:20px;">
    <a href="setup-wizard.php?step=1" class="btn btn-outline-primary">Back to Step 1</a>
</div>
<?php else: ?>
    <!-- CREATE PROPERTY BUTTON — shows when property doesn't exist in GSC -->
    <?php if ($status !== 'verified'): ?>
    <div style="margin-bottom: 24px;">
        <button id="createPropertyBtn"
            data-instance="<?= $instanceId ?>"
            data-domain="<?= htmlspecialchars($normalizedSiteUrl) ?>"
            class="btn btn-cstm-primary w-full">
            <span>&#9998;</span> Create Property Automatically
        </button>
        <p style="margin-top:8px; font-size:0.85rem; color:var(--s1-ink-2);">
            This creates a URL-prefix property in Google Search Console for your Squarespace site.
        </p>
    </div>
    <?php endif; ?>

    <!-- VERIFY DOMAIN BUTTON — shows after property created -->
    <div id="verifyWrapper" class="<?= empty($metaToken) ? 'hidden' : '' ?>">
        <div style="margin-bottom: 16px;">
            <h3 style="font-size:1rem; font-weight:600; margin-bottom:8px;">Google Verification Token</h3>
            <div class="token-box">
                <textarea id="verificationToken" rows="3" readonly><?= htmlspecialchars($metaToken ?? $metaTag) ?></textarea>
                <button class="copy-btn" id="copyVerificationToken">Copy Token</button>
            </div>
        </div>
        <button id="verifyDomainBtn"
            data-instance="<?= $instanceId ?>"
            data-domain="<?= htmlspecialchars($normalizedSiteUrl) ?>"
            data-token="<?= htmlspecialchars($metaToken) ?>"
            class="btn btn-cstm-primary w-full">
            <span>&#10003;</span> Verify Domain Automatically
        </button>
        <p style="margin-top:8px; font-size:0.85rem; color:var(--s1-ink-2);">
            We will add the verification meta tag to your Squarespace site header automatically.
            Or manually add this meta tag in Squarespace: Settings → Advanced → Code Injection → Header.
        </p>
    </div>

    <div id="domain_status" data-status="<?= $status ?>"></div>

    <script>
    document.getElementById("createPropertyBtn")?.addEventListener("click", async function() {
        const btn = this;
        const instanceId = btn.dataset.instance;
        const domain = btn.dataset.domain;

        btn.disabled = true;
        btn.textContent = "Creating Property...";

        try {
            const res = await fetch("api/google/create_property.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ instanceId, domain })
            });
            const data = await res.json();

            if (data.success) {
                btn.textContent = "Property Created!";
                btn.style.background = "#16a34a";
                setTimeout(() => location.reload(), 800);
            } else {
                btn.disabled = false;
                btn.textContent = "Create Property Automatically";
                alert("Error: " + (data.error || "Unknown error"));
            }
        } catch (e) {
            btn.disabled = false;
            btn.textContent = "Create Property Automatically";
            alert("Network error: " + e.message);
        }
    });

    document.getElementById("verifyDomainBtn")?.addEventListener("click", async function() {
        const btn = this;
        const instanceId = btn.dataset.instance;
        const domain = btn.dataset.domain;
        const token = btn.dataset.token;

        btn.disabled = true;
        btn.textContent = "Verifying...";

        try {
            const res = await fetch("api/google/verify_domain.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ instanceId, domain, meta_token: token })
            });
            const data = await res.json();

            if (data.success) {
                btn.textContent = "Verified!";
                btn.style.background = "#16a34a";
                setTimeout(() => location.reload(), 800);
            } else {
                btn.disabled = false;
                btn.textContent = "Verify Domain Automatically";
                alert("Error: " + (data.error || "Unknown error"));
            }
        } catch (e) {
            btn.disabled = false;
            btn.textContent = "Verify Domain Automatically";
            alert("Network error: " + e.message);
        }
    });

    document.getElementById("copyVerificationToken")?.addEventListener("click", async function() {
        const textarea = document.getElementById("verificationToken");
        try {
            await navigator.clipboard.writeText(textarea.value);
            this.textContent = "Copied!";
            setTimeout(() => { this.textContent = "Copy Token"; }, 1800);
        } catch (err) {
            alert("Failed to copy.");
        }
    });
    </script>
<?php endif; ?>
<?php endif; ?>
