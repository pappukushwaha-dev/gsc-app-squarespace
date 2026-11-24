<?php
// admin/core/UpdateEmailSettings.php
//
// Admin → Settings → Email Settings (save + send test).
// SMTP username/password are stored ENCRYPTED in admin_settings with
// includes/credentials.php encrypt() + ENCRYPTION_KEY — the same pair
// admin/settings.php uses to decrypt them for display, and that
// includes/EmailHelper.php (gscDecryptAdminValue) reads when sending.

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../../includes/credentials.php';
require_once __DIR__ . '/../../includes/EmailHelper.php';

SessionManager::startDatabaseSession();

if (!AuthController::isAuthenticated()) {
    header('Location: ../sign-in.php');
    exit;
}

function emailSettingsRedirect(string $status, string $message): void
{
    header('Location: ../settings.php?tab=email-settings&status=' . urlencode($status)
        . '&message=' . urlencode($message) . '#email-settings');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../settings.php?tab=email-settings#email-settings');
    exit;
}

$action        = (string)($_POST['action'] ?? '');
$emailProvider = (string)($_POST['email_provider'] ?? 'smtp');
$senderName    = trim((string)($_POST['senderName'] ?? ''));
$senderEmail   = trim((string)($_POST['senderEmail'] ?? ''));
$brevoApiKey   = trim((string)($_POST['brevoApiKey'] ?? ''));
$smtpUser      = trim((string)($_POST['smtp_username'] ?? ''));
$smtpPass      = trim((string)($_POST['smtp_password'] ?? ''));
$smtpHost      = trim((string)($_POST['smtp_host'] ?? ''));
$smtpPort      = trim((string)($_POST['smtp_port'] ?? ''));
$smtpSecurity  = (string)($_POST['smtp_security'] ?? '');
$testEmail     = trim((string)($_POST['testEmail'] ?? ''));

if (!in_array($emailProvider, ['smtp', 'brevo'], true)) {
    $emailProvider = 'smtp';
}
if (!in_array($smtpSecurity, ['', 'ssl', 'tls'], true)) {
    $smtpSecurity = '';
}

/* ---------------- TEST EMAIL (uses the SAVED settings) ---------------- */
if ($action === 'test') {
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        emailSettingsRedirect('error', 'Enter a valid email address for the test.');
    }
    try {
        sendRawTestEmail(
            $pdo,
            $testEmail,
            'Test Email from Admin Panel',
            '<p>This is a test email sent from the admin panel email settings.</p>'
        );
        emailSettingsRedirect('success', 'Test email sent successfully to ' . $testEmail);
    } catch (Throwable $e) {
        error_log('[UpdateEmailSettings] test failed: ' . $e->getMessage());
        emailSettingsRedirect('error', 'Test email failed: ' . $e->getMessage());
    }
}

if ($action !== 'save') {
    emailSettingsRedirect('error', 'Invalid action.');
}

/* ---------------- VALIDATE ---------------- */
if ($senderName === '' || !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
    emailSettingsRedirect('error', 'Sender Name and a valid Sender Email are required.');
}
if ($emailProvider === 'smtp' && ($smtpUser === '' || $smtpPass === '' || $smtpHost === '' || !ctype_digit($smtpPort))) {
    emailSettingsRedirect('error', 'SMTP Username, Password, Host and a numeric Port are required.');
}
if ($emailProvider === 'brevo' && $brevoApiKey === '') {
    emailSettingsRedirect('error', 'Brevo API Key is required.');
}

/* ---------------- SAVE ---------------- */
try {
    $pdo->beginTransaction();

    $save = $pdo->prepare("
        INSERT INTO admin_settings (key_name, value)
        VALUES (:key_name, :value)
        ON DUPLICATE KEY UPDATE value = VALUES(value)
    ");
    $put = static function (string $key, string $value) use ($save): void {
        $save->execute([':key_name' => $key, ':value' => $value]);
    };

    $put('email_provider', $emailProvider);
    $put('sender_name', $senderName);
    $put('sender_email', $senderEmail);
    $put('brevo_api_key', $brevoApiKey);

    $put('smtp_username', $smtpUser !== '' ? encrypt($smtpUser, ENCRYPTION_KEY) : '');
    $put('smtp_password', $smtpPass !== '' ? encrypt($smtpPass, ENCRYPTION_KEY) : '');
    $put('smtp_host', $smtpHost);
    $put('smtp_port', $smtpPort);

    // Security is stored as two flags (smtp_ssl / smtp_tls), one set.
    $pdo->prepare("DELETE FROM admin_settings WHERE key_name IN ('smtp_ssl','smtp_tls')")->execute();
    if ($smtpSecurity === 'ssl') {
        $put('smtp_ssl', 'ssl');
    } elseif ($smtpSecurity === 'tls') {
        $put('smtp_tls', 'tls');
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UpdateEmailSettings] save failed: ' . $e->getMessage());
    emailSettingsRedirect('error', 'Email settings update failed.');
}

emailSettingsRedirect('success', 'Email settings updated successfully.');
