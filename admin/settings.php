<?php
// admin/settings.php — Settings page (General + Google + Stripe)
// Same pattern as Wix/Shopify admin/settings.php

declare(strict_types=1);

$title = 'Settings';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';

// Auth check
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: ../sign-in.php');
    exit;
}

$activeTab = $_GET['tab'] ?? 'general';

// --- Load existing settings ---
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM admin_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['value'];
}

// Decrypt sensitive values for display
$googleClientId     = $settings['google_client_id']     ?? '';
$googleClientSecret = $settings['google_client_secret'] ?? '';
$googleRedirectUri  = $settings['google_redirect_uri']  ?? '';
$stripeApiKey       = $settings['stripe_api_key']       ?? '';
$stripeSecretKey    = $settings['stripe_secret_api_key'] ?? '';
$stripeWebhookSecret= $settings['stripe_webhook_secret'] ?? '';
$stripePortalKey    = $settings['stripe_portal_config_key'] ?? '';

if (defined('ENCRYPTION_KEY') && ENCRYPTION_KEY !== '') {
    $googleClientSecret = decrypt($googleClientSecret, ENCRYPTION_KEY) ?? $googleClientSecret;
    $stripeSecretKey    = decrypt($stripeSecretKey, ENCRYPTION_KEY) ?? $stripeSecretKey;
    $stripeWebhookSecret= decrypt($stripeWebhookSecret, ENCRYPTION_KEY) ?? $stripeWebhookSecret;
    $stripePortalKey    = decrypt($stripePortalKey, ENCRYPTION_KEY) ?? $stripePortalKey;
}
?>
<?php include __DIR__ . '/../partials/layouts/layoutTopnew.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Settings</h1>
    <p class="text-neutral-600 dark:text-neutral-400">Configure your Squarespace GSC app settings.</p>
</div>

<!-- Settings Tabs -->
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <nav class="flex border-b border-neutral-200 dark:border-neutral-700">
        <button onclick="location.href='?tab=general'"
            class="px-6 py-4 text-sm font-medium
                <?= $activeTab === 'general' ? 'border-b-2 border-cstm-primary text-cstm-primary' : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300' ?>
            ">General</button>
        <button onclick="location.href='?tab=google'"
            class="px-6 py-4 text-sm font-medium
                <?= $activeTab === 'google' ? 'border-b-2 border-cstm-primary text-cstm-primary' : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300' ?>
            ">Google</button>
        <button onclick="location.href='?tab=stripe'"
            class="px-6 py-4 text-sm font-medium
                <?= $activeTab === 'stripe' ? 'border-b-2 border-cstm-primary text-cstm-primary' : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300' ?>
            ">Stripe</button>
    </nav>

    <!-- General Tab -->
    <?php if ($activeTab === 'general'): ?>
    <div class="p-6">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white mb-4">General Settings</h2>
        <form action="../admin/core/UpdateGeneralSettings.php" method="POST" class="space-y-4 max-w-md">
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">App Name</label>
                <input type="text" name="app_name" value="Google Search Console — Squarespace"
                    class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Platform</label>
                <input type="text" name="platform" value="squarespace" readonly
                    class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white bg-neutral-100 dark:bg-neutral-800">
            </div>
            <button type="submit" class="btn-cstm-primary px-4 py-2 text-white rounded hover:bg-blue-600">Save General Settings</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Google Tab -->
    <?php if ($activeTab === 'google'): ?>
    <div class="p-6">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white mb-4">Google Search Console Settings</h2>
        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">Configure your Google Cloud OAuth credentials.</p>
        <form action="../admin/core/UpdateGoogleSettings.php" method="POST" class="space-y-4 max-w-lg">
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Client ID</label>
                <input type="text" name="client_id" value="<?= htmlspecialchars($googleClientId) ?>"
                    class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Client Secret</label>
                <input type="text" name="client_secret" value="<?= htmlspecialchars($googleClientSecret) ?>"
                    class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Redirect URI</label>
                <input type="text" name="redirect_uri" value="<?= htmlspecialchars($googleRedirectUri ?: 'https://your-domain.com/gsc-app-squarespace/google_oauth_callback.php') ?>"
                    class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white" required>
            </div>
            <button type="submit" class="btn-cstm-primary px-4 py-2 text-white rounded hover:bg-blue-600">Save Google Settings</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Stripe Tab -->
    <?php if ($activeTab === 'stripe'): ?>
    <div class="p-6">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white mb-4">Stripe Payment Settings</h2>
        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">Configure Stripe for subscription payments.</p>
        <form action="../admin/core/Update_stripe_Integration.php" method="POST" class="space-y-4 max-w-lg">
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Stripe API Key (Publishable)</label>
                <input type="text" name="stripe_api_key" value="<?= htmlspecialchars($stripeApiKey) ?>"
                    class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Stripe Secret API Key</label>
                <input type="text" name="stripe_secret_api_key" value="<?= htmlspecialchars($stripeSecretKey) ?>"
                    class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Stripe Webhook Secret</label>
                <input type="text" name="stripe_webhook_secret" value="<?= htmlspecialchars($stripeWebhookSecret) ?>"
                    class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Stripe Portal Config Key</label>
                <input type="text" name="stripe_portal_config_key" value="<?= htmlspecialchars($stripePortalKey) ?>"
                    class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white" required>
            </div>
            <button type="submit" class="btn-cstm-primary px-4 py-2 text-white rounded hover:bg-blue-600">Save Stripe Settings</button>
        </form>
    </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../partials/layouts/layoutBottom.php'; ?>
