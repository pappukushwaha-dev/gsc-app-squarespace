<?php

declare(strict_types=1);

/**
 * Squarespace GSC App — includes/credentials.php
 * AES-256-CBC encrypt/decrypt for Google credentials, Stripe keys, etc.
 * Same pattern as Wix/Shopify/BigCommerce credentials.php.
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

echo 'credentials_loaded';
