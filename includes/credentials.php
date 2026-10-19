<?php

declare(strict_types=1);

/**
 * Squarespace GSC App — includes/credentials.php
 * AES-256-CBC encrypt/decrypt + DB-backed Stripe credentials.
 * Same pattern as the WordPress/Wix/Shopify credentials.php.
 *
 * NOTE: no debug echoes here — this file loads on every page.
 */

if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', '');
}

if (!function_exists('encrypt')) {
    function encrypt(string $plain, string $key): string
    {
        if ($key === '') {
            return $plain; // fallback plaintext if no key configured
        }
        $method = 'aes-256-cbc';
        $keyBytes = strlen($key) < 32 ? str_pad($key, 32, "\0") : substr($key, 0, 32);
        $iv = openssl_random_pseudo_bytes(16);
        $cipher = openssl_encrypt($plain, $method, $keyBytes, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            return $plain;
        }
        return base64_encode($iv . $cipher);
    }
}

if (!function_exists('decrypt')) {
    function decrypt(string $cipher, string $key): ?string
    {
        if ($key === '') {
            return $cipher; // assume plaintext
        }
        $method = 'aes-256-cbc';
        $keyBytes = strlen($key) < 32 ? str_pad($key, 32, "\0") : substr($key, 0, 32);
        $data = base64_decode($cipher, true);
        if ($data === false || strlen($data) <= 16) {
            return $cipher; // not base64 or too short -> plaintext
        }
        $iv = substr($data, 0, 16);
        $raw = substr($data, 16);
        $plain = openssl_decrypt($raw, $method, $keyBytes, OPENSSL_RAW_DATA, $iv);
        return ($plain !== false && $plain !== '') ? $plain : null;
    }
}

if (!function_exists('get_stripe_credentials')) {
    /**
     * Stripe credentials live ENCRYPTED in admin_settings (saved from the
     * admin panel's Stripe Integration form) — the .env carries no Stripe
     * values, same as the WordPress app.
     *
     * @return array{publishable_key:?string, secret_key:?string, webhook_secret:?string, portal_config:?string}
     */
    function get_stripe_credentials(PDO $pdo): array
    {
        $stmt = $pdo->prepare("
            SELECT key_name, value
            FROM admin_settings
            WHERE key_name IN (
                'stripe_api_key',
                'stripe_secret_api_key',
                'stripe_webhook_secret',
                'stripe_portal_config_key'
            )
        ");
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $dec = fn(string $k): ?string => isset($rows[$k])
            ? decrypt((string) $rows[$k], ENCRYPTION_KEY)
            : null;

        return [
            'publishable_key' => $dec('stripe_api_key'),
            'secret_key'      => $dec('stripe_secret_api_key'),
            'webhook_secret'  => $dec('stripe_webhook_secret'),
            'portal_config'   => $dec('stripe_portal_config_key'),
        ];
    }
}
