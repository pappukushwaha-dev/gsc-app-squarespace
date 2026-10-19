<?php
// admin/core/StoreApi.php — Store API for Squarespace
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(1, (int)($_GET['limit'] ?? 10));
$search = trim($_GET['search'] ?? '');

$offset = ($page - 1) * $limit;

$where = [];
$binds = [];

$where[] = "ws.site_url IS NOT NULL AND TRIM(ws.site_url) <> ''";
$where[] = "(LOWER(IFNULL(ws.site_url,'')) NOT LIKE '%makkpress%')";

if ($search !== '') {
    $where[] = "(ws.site_url LIKE ? OR ws.site_display_name LIKE ?)";
    $like = "%{$search}%";
    array_push($binds, $like, $like);
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt FROM SquarespaceSite $whereSql
");
foreach ($binds as $i => $b) {
    $stmt->bindValue(($i + 1), $b, PDO::PARAM_STR);
}
$stmt->execute();
$total = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT instance_id, site_url, site_display_name, is_active, event_type, created_at
    FROM SquarespaceSite $whereSql
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($binds as $i => $b) {
    $stmt->bindValue(($i + 1), $b, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
    'stores' => $stores,
]);
