<?php
// admin/core/HeaderData.php — Header data for admin
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class HeaderData
{
    public static function get(): array
    {
        return [
            'app_name' => 'Google Search Console — Squarespace',
            'platform' => 'squarespace',
            'version' => '1.0.0',
        ];
    }
}
