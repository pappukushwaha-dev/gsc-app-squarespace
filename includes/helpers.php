<?php
// includes/helpers.php — Common helper functions
declare(strict_types=1);

if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('isSquarespaceUrl')) {
    function isSquarespaceUrl(string $url): bool
    {
        $html = @file_get_contents($url);
        return $html !== false && preg_match('/squarespace/i', $html);
    }
}

if (!function_exists('normalizeDomain')) {
    function normalizeDomain(string $website): string
    {
        $website = trim(strtolower($website));
        if (!preg_match('#^https?://#', $website)) {
            $website = 'http://' . $website;
        }
        $parts = parse_url($website);
        if (empty($parts['host'])) {
            throw new Exception('Invalid website URL');
        }
        return preg_replace('/^www\./', '', $parts['host']);
    }
}
