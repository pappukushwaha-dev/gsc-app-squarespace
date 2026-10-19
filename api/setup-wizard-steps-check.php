<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = [
    'success' => false,
    'redirect' => null,
    'message' => ''
];

$shopId = $_GET['shop_id'] ?? ($_SESSION['shop_id'] ?? null);
$shopId = is_string($shopId) ? trim($shopId) : null;

if (!$shopId) {
    $response['message'] = 'Instance ID missing';
    echo json_encode($response);
    exit;
}

$_SESSION['shop_id'] = $shopId;

try {
    $stmt = $pdo->prepare("
        SELECT step1, step2
        FROM setup_wizard_status
        WHERE shop_id = ?
        LIMIT 1
    ");
    $stmt->execute([$shopId]);
    $wizard = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($wizard && (int)$wizard['step1'] === 1 && (int)$wizard['step2'] === 1) {
        // ✅ Setup completed
        $response['redirect'] = 'dashboard.php?shop_id=' . urlencode($shopId);
    } else {
        // ❌ Setup not completed or no record
        $response['redirect'] = 'setup-wizard.php?shop_id=' . urlencode($shopId);
    }

    $response['success'] = true;
    echo json_encode($response);

} catch (PDOException $e) {
    $response['message'] = $e->getMessage();
    echo json_encode($response);
}
