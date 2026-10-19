<?php
// admin/core/StoreExport.php — Store export manager for Squarespace
declare(strict_types=1);

require_once __DIR__ . "/../../includes/config.php";

class StoreExport
{
    public static function exportStoresCsv(): string
    {
        global $pdo;
        $stmt = $pdo->query("
            SELECT instance_id, site_url, site_display_name, domain, is_active, event_type, created_at
            FROM SquarespaceSite
            ORDER BY created_at DESC
        ");
        $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ["instance_id", "site_url", "site_display_name", "domain", "is_active", "event_type", "created_at"];
        $output = fopen("php://temp", "r+");
        fputcsv($output, $headers);
        foreach ($stores as $store) {
            fputcsv($output, $store);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }
}

