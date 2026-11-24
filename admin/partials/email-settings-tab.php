<?php
// admin/partials/email-settings-tab.php
//
// Admin → Settings → "Email Settings" tab panel (included by
// admin/settings.php). Shows the stored provider + sender + SMTP
// settings; SMTP username/password are encrypted in admin_settings and
// shown here decrypted. Saved by admin/core/UpdateEmailSettings.php.

require_once __DIR__ . '/../../includes/EmailHelper.php';

$emailCfg = [];
try {
    $emailCfg = $pdo->query("
        SELECT key_name, value FROM admin_settings
        WHERE key_name IN ('email_provider','sender_name','sender_email','brevo_api_key',
                           'smtp_username','smtp_password','smtp_host','smtp_port','smtp_ssl','smtp_tls')
    ")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Throwable $e) {
    error_log('[email-settings-tab] load failed: ' . $e->getMessage());
}

$emailCfgProvider = ($emailCfg['email_provider'] ?? '') === 'brevo' ? 'brevo' : 'smtp';
$emailCfgUser     = (string)(gscDecryptAdminValue($emailCfg['smtp_username'] ?? '') ?? '');
$emailCfgPass     = (string)(gscDecryptAdminValue($emailCfg['smtp_password'] ?? '') ?? '');
$emailCfgSecurity = !empty($emailCfg['smtp_tls']) ? 'tls' : (!empty($emailCfg['smtp_ssl']) ? 'ssl' : '');
$emailCfgUndecryptable = (!empty($emailCfg['smtp_username']) && $emailCfgUser === '')
    || (!empty($emailCfg['smtp_password']) && $emailCfgPass === '');

$emailCfgFlash = null;
if (($_GET['tab'] ?? '') === 'email-settings' && isset($_GET['status'], $_GET['message'])) {
    $emailCfgFlash = [
        'ok'  => $_GET['status'] === 'success',
        'msg' => (string)$_GET['message'],
    ];
}
$escEmail = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$lbl = 'inline-block font-semibold text-neutral-600 dark:text-neutral-200 text-sm mb-2';
?>
<div class="hidden" id="email-settings" role="tabpanel" aria-labelledby="email-settings-tab">
    <?php if ($emailCfgFlash): ?>
        <div class="mb-4 rounded-lg px-4 py-3 text-sm <?= $emailCfgFlash['ok'] ? 'bg-success-100 text-success-700' : 'bg-danger-100 text-danger-700' ?>">
            <?= $escEmail($emailCfgFlash['msg']) ?>
        </div>
    <?php endif; ?>
    <?php if ($emailCfgUndecryptable): ?>
        <div class="mb-4 rounded-lg px-4 py-3 text-sm bg-warning-100 text-warning-700">
            The saved SMTP username/password could not be decrypted. Enter them again and Save.
        </div>
    <?php endif; ?>

    <form action="./core/UpdateEmailSettings.php" class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-2" method="POST">
        <div>
            <div class="mb-2">
                <label for="emailProvider" class="<?= $lbl ?>">Email Provider <span class="text-danger-600">*</span></label>
                <select name="email_provider" id="emailProvider" class="form-control rounded-lg">
                    <option value="smtp" <?= $emailCfgProvider === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                    <option value="brevo" <?= $emailCfgProvider === 'brevo' ? 'selected' : '' ?>>Brevo (API)</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 mb-2">
                <div>
                    <label for="senderName" class="<?= $lbl ?>">Sender Name <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control rounded-lg" id="senderName" name="senderName" value="<?= $escEmail($emailCfg['sender_name'] ?? '') ?>" placeholder="Enter Sender Name">
                </div>
                <div>
                    <label for="senderEmail" class="<?= $lbl ?>">Sender Email <span class="text-danger-600">*</span></label>
                    <input type="email" class="form-control rounded-lg" id="senderEmail" name="senderEmail" value="<?= $escEmail($emailCfg['sender_email'] ?? '') ?>" placeholder="Enter Sender Email">
                </div>
            </div>

            <div id="apiFields" class="grid grid-cols-1 gap-x-6 gap-y-2 mb-2 <?= $emailCfgProvider === 'brevo' ? '' : 'hidden' ?>">
                <div>
                    <label for="brevoApiKey" class="<?= $lbl ?>">Brevo API Key <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control rounded-lg" id="brevoApiKey" name="brevoApiKey" value="<?= $escEmail($emailCfg['brevo_api_key'] ?? '') ?>" placeholder="Enter Brevo API Key">
                </div>
            </div>

            <div id="smtpFields" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 mb-2 <?= $emailCfgProvider === 'smtp' ? '' : 'hidden' ?>">
                <div>
                    <label for="smtpUsername" class="<?= $lbl ?>">SMTP Username <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control rounded-lg" id="smtpUsername" name="smtp_username" value="<?= $escEmail($emailCfgUser) ?>" placeholder="SMTP Username" autocomplete="off">
                </div>
                <div>
                    <label for="smtpPassword" class="<?= $lbl ?>">SMTP Password <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control rounded-lg" id="smtpPassword" name="smtp_password" value="<?= $escEmail($emailCfgPass) ?>" placeholder="SMTP Password" autocomplete="off">
                </div>
                <div>
                    <label for="smtpHost" class="<?= $lbl ?>">SMTP Host <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control rounded-lg" id="smtpHost" name="smtp_host" value="<?= $escEmail($emailCfg['smtp_host'] ?? '') ?>" placeholder="smtp.postmarkapp.com">
                </div>
                <div>
                    <label for="smtpPort" class="<?= $lbl ?>">SMTP Port <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control rounded-lg" id="smtpPort" name="smtp_port" value="<?= $escEmail($emailCfg['smtp_port'] ?? '') ?>" placeholder="587">
                </div>
                <div class="md:col-span-2">
                    <label for="smtpSecurity" class="<?= $lbl ?>">Security (SSL/TLS)</label>
                    <select class="form-control rounded-lg" id="smtpSecurity" name="smtp_security">
                        <option value="">None</option>
                        <option value="ssl" <?= $emailCfgSecurity === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        <option value="tls" <?= $emailCfgSecurity === 'tls' ? 'selected' : '' ?>>TLS</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-center gap-3 mt-8">
                <button type="reset" class="btn btn-cstm-muted">Cancel</button>
                <button type="submit" name="action" value="save" class="btn btn-cstm-primary">Save</button>
            </div>
        </div>

        <div class="lg:border-l border-gray-200 lg:pl-5 dark:border-gray-600">
            <label for="testEmail" class="<?= $lbl ?>">Send Test Email</label>
            <div class="flex flex-col items-center gap-3">
                <input type="email" class="form-control rounded-lg" id="testEmail" name="testEmail" placeholder="Enter email address">
                <button type="submit" name="action" value="test" class="btn btn-cstm-primary">Send</button>
            </div>
            <p class="text-xs text-neutral-500 mt-2">The test uses the saved settings — Save first after changing them.</p>
        </div>
    </form>
</div>
<script>
(function () {
    var sel = document.getElementById('emailProvider');
    var api = document.getElementById('apiFields');
    var smtp = document.getElementById('smtpFields');
    function sync() {
        if (!sel || !api || !smtp) return;
        var isSmtp = sel.value === 'smtp';
        smtp.classList.toggle('hidden', !isSmtp);
        api.classList.toggle('hidden', isSmtp);
        smtp.style.display = '';
        api.style.display = '';
    }
    if (sel) sel.addEventListener('change', sync);
    document.addEventListener('DOMContentLoaded', function () {
        sync();
        if (new URLSearchParams(location.search).get('tab') === 'email-settings') {
            var btn = document.getElementById('email-settings-tab');
            if (btn) btn.click();
        }
    });
})();
</script>
