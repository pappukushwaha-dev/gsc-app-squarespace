<?php
// includes/google/generate_meta.php — Generate Google verification meta tag
declare(strict_types=1);

if (!function_exists('generateVerificationMetaTag')) {
    function generateVerificationMetaTag(string $token): string
    {
        return '<meta name="google-site-verification" content="' . htmlspecialchars($token) . '" />';
    }
}
