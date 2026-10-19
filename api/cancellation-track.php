<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['shop_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing shop_id']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM cancellation_sessions WHERE shop_id = :id LIMIT 1");
    $check->execute([':id' => $data['shop_id']]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $sql = "
            UPDATE cancellation_sessions SET
                current_step = :current_step,
                join_reason = :join_reason,
                join_extra = :join_extra,
                mirror_screen_shown = :mirror,
                leave_reason = :leave_reason,
                leave_extra = :leave_extra,
                offer_type = :offer_type,
                offer_selected = :offer_selected,
                call_scheduled = :call_scheduled,
                review_shown = :review,
                final_action = :final_action,
                updated_at = NOW()
            WHERE shop_id = :shop_id
        ";
    } else {
        $sql = "
            INSERT INTO cancellation_sessions
            (shop_id, current_step, join_reason, join_extra, mirror_screen_shown,
             leave_reason, leave_extra, offer_type, offer_selected, call_scheduled,
             review_shown, final_action, created_at, updated_at)
            VALUES
            (:shop_id, :current_step, :join_reason, :join_extra, :mirror,
             :leave_reason, :leave_extra, :offer_type, :offer_selected, :call_scheduled,
             :review, :final_action, NOW(), NOW())
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':shop_id'     => $data['shop_id'],
        ':current_step'    => $data['current_step'],
        ':join_reason'     => $data['join_reason'],
        ':join_extra'      => $data['join_extra'],
        ':mirror'          => $data['mirror_screen_shown'],
        ':leave_reason'    => $data['leave_reason'],
        ':leave_extra'     => $data['leave_extra'],
        ':offer_type'      => $data['offer_type'],
        ':offer_selected'  => $data['offer_selected'],
        ':call_scheduled'  => $data['call_scheduled'],
        ':review'          => $data['review_shown'],
        ':final_action'    => $data['final_action']
    ]);

    echo json_encode(['success' => true, 'message' => 'Progress saved']);
    exit;

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
