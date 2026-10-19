<?php
// includes/google/get_squarespace_site.php — Get Squarespace site by instance
declare(strict_types=1);

if (!function_exists('getSquarespaceSiteByInstance')) {
    function getSquarespaceSiteByInstance(string $shopId): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT * FROM SquarespaceSite WHERE shop_id = ? LIMIT 1
        ");
        $stmt->execute([$shopId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
