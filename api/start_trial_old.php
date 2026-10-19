<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("Database connection missing.");
    }

    // Read body (JSON or form/query)
    $raw  = file_get_contents("php://input");
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        $body = array_merge($_GET, $_POST);
    }
$shopId = $_SESSION['shop_id'] ?? $_SESSION['shop_id'] ?? ($_GET['shop_id'] ?? null);

    if ($shopId === "") {
        echo json_encode([
            "success" => false,
            "error"   => "shop_id missing"
        ]);
        exit;
    }

    // --------------------------------------------------
    // STEP 1: CHECK IF INSTANCE HAS PAID SUBSCRIPTION
    // app_subscriptions(id, shop_id, plan_name, billing_period, status,
    //                  started_at, cancelled_at, expires_on, raw_event, updated_at)
    //
    // Assumption:
    //  - status = 'active' means paid
    //  - expires_on is NULL or in the future
    // --------------------------------------------------

 $paidStmt = $pdo->prepare("
    SELECT id
    FROM app_subscriptions
    WHERE shop_id = :i
      AND status = 'active'
      AND plan_name != 'Free'
      AND (expires_on IS NULL OR expires_on > NOW())
    LIMIT 1
");

    $paidStmt->execute([':i' => $shopId]);
    $hasPaid = (bool)$paidStmt->fetch(PDO::FETCH_ASSOC);

    if ($hasPaid) {
        echo json_encode([
            "success" => true,
            "skipped" => true,
            "reason"  => "instance_has_paid_plan"
        ]);
        exit;
    }

    // --------------------------------------------------
    // STEP 2: ACTIVATE / REFRESH FREE TRIAL (14 DAYS)
    // app_free_trials(id, shop_id, status, started_at, expires_on,
    //                cancelled_at, converted_at, raw_event, created_at, updated_at)
    //
    // Requires UNIQUE KEY on shop_id in app_free_trials
    // --------------------------------------------------

    $now     = new DateTimeImmutable("now", new DateTimeZone("UTC"));
    $expires = $now->modify("+14 days");

    $startedAt = $now->format("Y-m-d H:i:s");
    $expiresOn = $expires->format("Y-m-d H:i:s");
    $createdAt = $startedAt;
    $updatedAt = $startedAt;

    $insert = $pdo->prepare("
        INSERT INTO app_free_trials (
            shop_id,
            status,
            started_at,
            expires_on,
            cancelled_at,
            converted_at,
            raw_event,
            created_at,
            updated_at
        ) VALUES (
            :shop_id,
            'active',
            :started_at,
            :expires_on,
            NULL,
            NULL,
            :raw_event,
            :created_at,
            :updated_at
        )
        ON DUPLICATE KEY UPDATE
            status     = 'active',
            started_at = VALUES(started_at),
            expires_on = VALUES(expires_on),
            raw_event  = VALUES(raw_event),
            updated_at = VALUES(updated_at)
    ");

    $insert->execute([
        ":shop_id" => $shopId,
        ":started_at"  => $startedAt,
        ":expires_on"  => $expiresOn,
        ":raw_event"   => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ":created_at"  => $createdAt,
        ":updated_at"  => $updatedAt,
    ]);

    echo json_encode([
        "success"     => true,
        "skipped"     => false,
        "shop_id" => $shopId,
        "started_at"  => $startedAt,
        "expires_on"  => $expiresOn
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error"   => $e->getMessage()
    ]);
}
