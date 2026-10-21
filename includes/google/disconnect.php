<?php
// /bigcommerce/googlesearchconsole/includes/google/disconnect.php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config.php';

// Track the disconnect action (activity-logs).
require_once __DIR__ . '/../activity-logger.php';
log_activity('disconnect');

header('Content-Type: application/json');

// ✅ SINGLE SOURCE OF TRUTH
$shopId = $_SESSION['shop_id']
    ?? $_SESSION['shop_id']
    ?? null;;

if (!$shopId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Missing instance ID'
    ]);
    exit;
}

try {
    /** @var PDO $pdo */
    global $pdo;

    $pdo->beginTransaction();

    /* --------------------------------------------------
       1. Delete Google OAuth account
    -------------------------------------------------- */
    $stmt = $pdo->prepare("
        DELETE FROM google_accounts 
        WHERE shop_id = ?
    ");
    $stmt->execute([$shopId]);

    /* --------------------------------------------------
       2. Remove domain verification
    -------------------------------------------------- */
    $stmt = $pdo->prepare("
        DELETE FROM gsc_domain_verifications 
        WHERE shop_id = ?
    ");
    $stmt->execute([$shopId]);

    /* --------------------------------------------------
       3. Remove sitemaps
    -------------------------------------------------- */
    $stmt = $pdo->prepare("
        DELETE FROM sitemaps 
        WHERE shop_id = ?
    ");
    $stmt->execute([$shopId]);

    /* --------------------------------------------------
       4. Remove sitemap submission logs
    -------------------------------------------------- */
    $stmt = $pdo->prepare("
        DELETE FROM sitemap_submission_logs 
        WHERE shop_id = ?
    ");
    $stmt->execute([$shopId]);

    /* --------------------------------------------------
       5. Reset setup wizard progress
    -------------------------------------------------- */
    $stmt = $pdo->prepare("
        UPDATE setup_wizard_status
        SET step1 = 0, step2 = 0, step3 = 0
        WHERE shop_id = ?
    ");
    $stmt->execute([$shopId]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Disconnect failed'
        // 'debug' => $e->getMessage() // enable only for dev
    ]);
}
