<?php
// admin/core/OtherAppsManager.php — Other apps manager
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class OtherAppsManager
{
    public static function getAll(): array
    {
        return [
            ['name' => 'Wix GSC App', 'platform' => 'wix'],
            ['name' => 'Shopify GSC App', 'platform' => 'shopify'],
            ['name' => 'BigCommerce GSC App', 'platform' => 'bigcommerce'],
        ];
    }
}
