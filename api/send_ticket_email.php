<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/EmailHelper.php';

// Support inbox for new tickets = Admin → Settings → Email Settings →
// "Sender Email" (falls back to admin_email). Change it there, not here.
$ticketAdminEmail = trim((string)(getAdminSetting($pdo, 'sender_email') ?: getAdminSetting($pdo, 'admin_email') ?: ''));

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// -------------------- Basic validation --------------------
$ticketId   = (int)($input['ticket_id'] ?? 0);
$message    = trim((string)($input['message'] ?? ''));
$ownerEmail = trim((string)($input['owner_email'] ?? ''));
$websiteUrl = trim((string)($input['website_url'] ?? ''));

if ($ticketId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

// Only the store that just created this ticket may trigger its mail, once. 2026-11-23
require_once '/var/www/html/_shared/ticket_guard.php';
ticket_mail_guard($pdo, $ticketId, $ownerEmail);
$ownerEmailValid = (bool)filter_var($ownerEmail, FILTER_VALIDATE_EMAIL);

// -------------------- Build attachments (FROM DB) --------------------
$attachments = [];

$stmt = $pdo->prepare("
    SELECT stored_path, original_name
    FROM ticket_attachments
    WHERE ticket_id = :ticket_id
");
$stmt->execute([
    ':ticket_id' => $ticketId
]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['stored_path']) && file_exists($row['stored_path'])) {
        $attachments[$row['stored_path']] = $row['original_name'];
    }
}

// -------------------- Placeholders --------------------
$placeholdersUser = [
    '{{ticket_number}}' => (string)$ticketId,
    '{{user_name}}'     => $ownerEmail,
];

$placeholdersAdmin = [
    '{{User_Email}}'                => $ownerEmailValid ? $ownerEmail : 'N/A',
    '{{Website_URL}}'               => $websiteUrl !== '' ? $websiteUrl : 'N/A',
    '{{User_Message_Body}}'         => nl2br(htmlspecialchars($message)),
    '{{Attachment_Link_or_Status}}' => !empty($attachments)
        ? 'Attached to this email'
        : 'No attachment',
];

// -------------------- Send CUSTOMER email --------------------
$customerOk  = false;
$customerErr = null;
if ($ownerEmailValid) {
    try {
        sendEmailUsingTemplate($pdo, 'Ticket Created', $ownerEmail, $placeholdersUser);
        $customerOk = true;
    } catch (Throwable $e) {
        $customerErr = $e->getMessage();
        error_log("send_ticket_email.php customer email failed for ticket {$ticketId}: " . $customerErr);
    }
} else {
    $customerErr = 'Owner email missing or invalid; skipped customer notification';
}

// -------------------- Send ADMIN email (never blocked by the customer one) --------------------
$adminOk  = false;
$adminErr = null;
try {
    sendEmailUsingTemplate($pdo, 'Ticket Created for admin', $ticketAdminEmail, $placeholdersAdmin, $attachments);
    $adminOk = true;
} catch (Throwable $e) {
    $adminErr = $e->getMessage();
    error_log("send_ticket_email.php admin email failed for ticket {$ticketId}: " . $adminErr);
}

echo json_encode([
    'success'            => $adminOk,
    'customer_email_ok'  => $customerOk,
    'admin_email_ok'     => $adminOk,
    'customer_email_err' => $customerErr,
    'admin_email_err'    => $adminErr,
]);
