<?php
// webhook/app-installed.php — Squarespace app install webhook
// Same pattern as Wix webhook/app-installed.php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';

global $pdo;

// Squarespace sends webhook payload via POST
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$instanceId = $input['instanceId'] ?? $input['site_id'] ?? $input['instance_id'] ?? null;
$siteUrl    = $input['siteUrl'] ?? $input['site_url'] ?? null;
$siteName   = $input['siteName'] ?? $input['site_name'] ?? null;
$domain     = $input['domain'] ?? null;
$eventType  = $input['event_type'] ?? 'APPINSTALLED';

if (!$instanceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'missing_instance_id']);
    exit;
}

// Upsert SquarespaceSite record
$check = $pdo->prepare("SELECT id FROM SquarespaceSite WHERE instance_id = ? LIMIT 1");
$check->execute([$instanceId]);
$exists = $check->fetchColumn();

if ($exists) {
    $stmt = $pdo->prepare("
        UPDATE SquarespaceSite
        SET site_url = :url, site_display_name = :name, domain = :dom,
            event_type = :evt, is_active = 1, updated_at = NOW()
        WHERE instance_id = :iid
    ");
    $stmt->execute([
        ':url'  => $siteUrl,
        ':name' => $siteName,
        ':dom'  => $domain,
        ':evt'  => $eventType,
        ':iid'  => $instanceId,
    ]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO SquarespaceSite
            (instance_id, site_id, site_url, site_display_name, domain, event_type, is_active, created_at)
        VALUES (:iid, :sid, :url, :name, :dom, :evt, 1, NOW())
    ");
    $stmt->execute([
        ':iid'  => $instanceId,
        ':sid'  => $instanceId,
        ':url'  => $siteUrl,
        ':name' => $siteName,
        ':dom'  => $domain,
        ':evt'  => $eventType,
    ]);
}

echo json_encode([
    'success' => true,
    'instance_id' => $instanceId,
    'message' => 'App installed',
]);
exit;
