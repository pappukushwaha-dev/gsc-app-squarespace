<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/EmailHelper.php';
require_once __DIR__ . '/../includes/account_provision.php';

// normalizeDomain / extractMeta / extractPhoneNumber / extractFavicon / resolveUrl
// and the account-creation body now live in includes/account_provision.php so the
// OAuth website step shares one code path. This endpoint parses input, owns the
// transaction + HTTP response, and delegates Squarespace account creation.

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (
        empty($data['email']) ||
        empty($data['website']) ||
        empty($data['platform'])
    ) {
        echo json_encode(['ok' => false, 'message' => 'Invalid request']);
        exit;
    }

    $email    = strtolower(trim($data['email']));
    $platform = strtolower(trim($data['platform']));
    $website  = normalizeDomain($data['website']);
    $pdo->beginTransaction();

    // build name from email
    $localPart = explode('@', $email)[0];
    $localPart = str_replace(['.', '_', '-'], ' ', $localPart);
    $name = ucwords(trim($localPart));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'message' => 'Invalid email']);
        exit;
    }

    /* ================= NON-SQUARESPACE ================= */
    if ($platform !== 'squarespace') {
        $stmt = $pdo->prepare("
            INSERT INTO other_platform (platform, website, name, email)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$platform, $website, $name, $email]);
        $pdo->commit();
        echo json_encode([
            'ok' => true,
            'installUrl' => 'https://www.wix.com/app-market/json-ld-schema',
            'message' => 'Your details have been saved. You can install the app from the Wix App Market.'
        ]);
        exit;
    }

    /* ================= SQUARESPACE ================= */
    try {
        $res = createSquarespaceAccount($pdo, $email, $name, $website);
    } catch (AccountProvisionException $e) {
        // user-input rejection — same messages, same HTTP 200 as the old inline echo/exit
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(200);
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        exit;
    }

    /* ---------- MAGIC LINK PREP ---------- */
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['activeUserId'] = $res['user_id'];
    $_SESSION['email'] = $email;

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'email' => $email,
        'magic_link_required' => true
    ]);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage() // Isse exact error pata chalega
    ]);
}
