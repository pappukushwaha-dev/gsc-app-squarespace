<?php
// includes/google/get_squarespace_site.php — Get Squarespace site by instance
declare(strict_types=1);

if (!function_exists('getSquarespaceSiteByInstance')) {
    function getSquarespaceSiteByInstance(string $instanceId): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT * FROM SquarespaceSite WHERE instance_id = ? LIMIT 1
        ");
        $stmt->execute([$instanceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
