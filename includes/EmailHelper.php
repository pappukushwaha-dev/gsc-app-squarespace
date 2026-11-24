<?php
/**
 * includes/EmailHelper.php  (shared by every GSC platform build:
 * Shopify, Wix, Ecwid, BigCommerce, Squarespace, WordPress, HighLevel)
 *
 * Transactional-email helper: renders a template from the
 * `email_templates` table and sends it through the provider configured
 * in Admin → Settings → Email Settings (stored in `admin_settings`).
 *
 * PROVIDER (admin_settings.email_provider)
 *   'smtp'   PHPMailer over smtp_host/smtp_port/smtp_tls|smtp_ssl with
 *            smtp_username/smtp_password stored ENCRYPTED (AES-256-CBC,
 *            base64(iv . ciphertext)) by admin/core/UpdateEmailSettings.php
 *            using includes/credentials.php encrypt() + ENCRYPTION_KEY.
 *   'brevo'  Brevo transactional API (brevo_api_key, plaintext); only
 *            usable where the Brevo client is installed in vendor/.
 *   'api'    legacy value: SMTP when SMTP is configured, else Brevo.
 *
 * API:
 *   sendEmailUsingTemplate($pdo, $templateName, $toEmail,
 *       $placeholders = [], $attachments = [], $senderEmail, $senderName)
 *   sendRawTestEmail($pdo, $toEmail, $subject, $bodyHtml, $placeholders)
 *
 * Throws RuntimeException on configuration/template/send failures —
 * callers convert that into their own JSON/HTML error handling.
 */

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\ApiException;
use GuzzleHttp\Client;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('getAdminSetting')) {
    /**
     * Single setting value from admin_settings (plaintext — the admin
     * email-settings tab stores Brevo credentials unencrypted).
     */
    function getAdminSetting(PDO $pdo, string $keyName)
    {
        $stmt = $pdo->prepare("
            SELECT value
            FROM admin_settings
            WHERE key_name = :key
            LIMIT 1
        ");
        $stmt->execute([':key' => $keyName]);
        return $stmt->fetchColumn();
    }
}

if (!function_exists('gscAdminCryptoKeys')) {
    /**
     * Candidate AES keys for admin_settings secrets, most likely first:
     * the ENCRYPTION_KEY constant (includes/credentials.php, when loaded)
     * then every derivation of ENCRYPTION_SECRET the platform builds use
     * (base64:-decoded, raw, sha256). Builds whose credentials.php falls
     * back to a default secret when .env has none get that default too.
     */
    function gscAdminCryptoKeys(): array
    {
        $keys = [];
        if (defined('ENCRYPTION_KEY') && (string)ENCRYPTION_KEY !== '') {
            $keys[] = (string)ENCRYPTION_KEY;
        }
        $secret = trim((string)($_ENV['ENCRYPTION_SECRET'] ?? getenv('ENCRYPTION_SECRET') ?: ''));
        if ($secret === '') {
            $secret = 'your-32-character-secret-key-here!!';
        }
        if (strpos($secret, 'base64:') === 0) {
            $keys[] = (string)base64_decode(substr($secret, 7));
        }
        $keys[] = $secret;
        $keys[] = hash('sha256', $secret, true);
        return array_values(array_unique($keys));
    }
}

if (!function_exists('gscDecryptAdminValue')) {
    /**
     * AES-256-CBC decrypt of an admin_settings smtp_username /
     * smtp_password value (base64(iv . ciphertext)). Tries every
     * candidate key; returns null when none decrypts cleanly.
     */
    function gscDecryptAdminValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $raw = base64_decode($value, true);
        if ($raw === false || strlen($raw) <= 16) {
            return null;
        }
        $iv = substr($raw, 0, 16);
        $ct = substr($raw, 16);
        foreach (gscAdminCryptoKeys() as $key) {
            $plain = @openssl_decrypt($ct, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            if ($plain !== false && $plain !== '' && mb_check_encoding($plain, 'UTF-8')
                && !preg_match('/[\x00-\x08\x0E-\x1F]/', $plain)) {
                return $plain;
            }
        }
        return null;
    }
}

if (!function_exists('createSmtpMailerFromSettings')) {
    /**
     * PHPMailer configured from admin_settings smtp_* keys (Postmark
     * in production: smtp.postmarkapp.com:587 STARTTLS, encrypted
     * username/password).
     */
    function createSmtpMailerFromSettings(PDO $pdo): PHPMailer
    {
        $mail = new PHPMailer(true);

        $host = getAdminSetting($pdo, 'smtp_host') ?: 'smtp.postmarkapp.com';
        $port = (int) (getAdminSetting($pdo, 'smtp_port') ?: 587);

        $smtpSslFlag = getAdminSetting($pdo, 'smtp_ssl'); // non-empty means SSL
        $smtpTlsFlag = getAdminSetting($pdo, 'smtp_tls'); // non-empty means TLS

        if (!empty($smtpTlsFlag)) {
            $sslType = 'tls';
        } elseif (!empty($smtpSslFlag)) {
            $sslType = 'ssl';
        } else {
            $sslType = '';
        }

        $username = gscDecryptAdminValue(getAdminSetting($pdo, 'smtp_username'));
        $password = gscDecryptAdminValue(getAdminSetting($pdo, 'smtp_password'));

        if (empty($host) || empty($username) || empty($password)) {
            throw new RuntimeException('SMTP configuration incomplete in admin_settings '
                . '(smtp_host/smtp_username/smtp_password missing or ENCRYPTION_SECRET mismatch).');
        }

        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';
        $mail->isHTML(true);

        if ($sslType === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($sslType === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = false;
        }

        return $mail;
    }
}

if (!function_exists('gscEmailUseSmtp')) {
    /**
     * Provider decision:
     *   'smtp'                    → SMTP
     *   'brevo'                   → Brevo
     *   'api' / missing / other   → SMTP when smtp_host + smtp_username
     *                               are set, otherwise Brevo.
     */
    function gscEmailUseSmtp(PDO $pdo): bool
    {
        $provider = trim((string)(getAdminSetting($pdo, 'email_provider') ?: ''));
        if ($provider === 'smtp') {
            return true;
        }
        if ($provider === 'brevo') {
            return false;
        }
        return (string)getAdminSetting($pdo, 'smtp_host') !== ''
            && (string)getAdminSetting($pdo, 'smtp_username') !== '';
    }
}

if (!function_exists('gscBrevoAvailable')) {
    function gscBrevoAvailable(): void
    {
        if (!class_exists(TransactionalEmailsApi::class)) {
            throw new RuntimeException('Brevo client is not installed on this app — set Email Provider to SMTP in Admin → Settings → Email Settings.');
        }
    }
}

if (!function_exists('sendEmailUsingTemplate')) {
    /**
     * Render an email_templates row (placeholders substituted) and send
     * it to $toEmail through the configured provider. $attachments is a
     * map of server path → display name; Brevo paths get them base64
     * embedded, the SMTP path attaches from disk.
     *
     * @throws RuntimeException on missing provider config or template
     */
    function sendEmailUsingTemplate(
        PDO $pdo,
        string $templateName,
        string $toEmail,
        array $placeholders = [],
        array $attachments = [],
        string $senderEmail = 'noreply@example.com',
        string $senderName = 'My App'
    ) {
        $dbSenderEmail = getAdminSetting($pdo, 'sender_email');
        $dbSenderName  = getAdminSetting($pdo, 'sender_name');

        if (!empty($dbSenderEmail)) {
            $senderEmail = $dbSenderEmail;
        }
        if (!empty($dbSenderName)) {
            $senderName = $dbSenderName;
        }

        $stmt = $pdo->prepare("
            SELECT subject, body
            FROM email_templates
            WHERE name = :name
            LIMIT 1
        ");
        $stmt->execute([':name' => $templateName]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            throw new RuntimeException("Email template '{$templateName}' not found in email_templates.");
        }

        $subject = strtr($template['subject'], $placeholders);
        $body    = strtr($template['body'], $placeholders);

        // SMTP via PHPMailer (optional provider)
        if (gscEmailUseSmtp($pdo)) {
            try {
                $mail = createSmtpMailerFromSettings($pdo);

                $mail->setFrom($senderEmail, $senderName);
                $mail->addAddress($toEmail);

                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->AltBody = strip_tags($body);

                foreach ($attachments as $filePath => $fileName) {
                    if (file_exists($filePath)) {
                        $mail->addAttachment($filePath, $fileName);
                    }
                }

                $mail->send();

                return true;
            } catch (PHPMailerException $e) {
                throw new RuntimeException('sendEmailUsingTemplate(): SMTP (PHPMailer) error: ' . $e->getMessage());
            }
        }

        // Brevo transactional API
        gscBrevoAvailable();
        $apiKey = getAdminSetting($pdo, 'brevo_api_key');
        if (!$apiKey) {
            throw new RuntimeException("Brevo API Key missing in admin_settings (key_name = 'brevo_api_key').");
        }

        $brevoAttachments = [];
        foreach ($attachments as $filePath => $fileName) {
            if (is_string($filePath) && is_file($filePath)) {
                $brevoAttachments[] = [
                    'name'    => (string)$fileName,
                    'content' => base64_encode((string)file_get_contents($filePath)),
                ];
            }
        }

        $config      = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $apiInstance = new TransactionalEmailsApi(new Client(), $config);

        $email = new SendSmtpEmail([
            'to'          => [['email' => $toEmail]],
            'subject'     => $subject,
            'htmlContent' => $body,
            'sender'      => [
                'email' => $senderEmail,
                'name'  => $senderName,
            ],
        ]);
        if ($brevoAttachments) {
            $email['attachment'] = $brevoAttachments;
        }

        try {
            return $apiInstance->sendTransacEmail($email);
        } catch (ApiException $e) {
            throw new RuntimeException('sendEmailUsingTemplate(): Brevo ApiException: ' . $e->getMessage());
        }
    }
}

if (!function_exists('sendRawTestEmail')) {
    /**
     * Raw HTML email for the admin template editor's "send test" —
     * subject/body come from the editor, not the DB template.
     */
    function sendRawTestEmail(
        PDO $pdo,
        string $toEmail,
        string $subject,
        string $bodyHtml,
        array $placeholders = []
    ) {
        $senderEmail = getAdminSetting($pdo, 'sender_email') ?: 'noreply@example.com';
        $senderName  = getAdminSetting($pdo, 'sender_name') ?: 'My App';

        if (!empty($placeholders)) {
            $subject  = strtr($subject, $placeholders);
            $bodyHtml = strtr($bodyHtml, $placeholders);
        }

        if (gscEmailUseSmtp($pdo)) {
            try {
                $mail = createSmtpMailerFromSettings($pdo);

                $mail->setFrom($senderEmail, $senderName);
                $mail->addAddress($toEmail);

                $mail->Subject = $subject;
                $mail->Body    = $bodyHtml;
                $mail->AltBody = strip_tags($bodyHtml);

                $mail->send();
                return true;
            } catch (PHPMailerException $e) {
                throw new RuntimeException('sendRawTestEmail(): SMTP (PHPMailer) error: ' . $e->getMessage());
            }
        }

        gscBrevoAvailable();
        $apiKey = getAdminSetting($pdo, 'brevo_api_key');
        if (!$apiKey) {
            throw new RuntimeException("Brevo API Key missing in admin_settings (key_name = 'brevo_api_key').");
        }

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $api    = new TransactionalEmailsApi(new Client(), $config);

        $email = new SendSmtpEmail([
            'to'          => [['email' => $toEmail]],
            'subject'     => $subject,
            'htmlContent' => $bodyHtml,
            'sender'      => [
                'email' => $senderEmail,
                'name'  => $senderName,
            ],
        ]);

        try {
            return $api->sendTransacEmail($email);
        } catch (ApiException $e) {
            throw new RuntimeException('sendRawTestEmail(): Brevo ApiException: ' . $e->getMessage());
        }
    }
}
