<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$batch_id = isset($data['batch_id']) ? trim($data['batch_id']) : '';

if (!$batch_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid batch ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM batches WHERE batch_id = :batch_id");
    $stmt->execute([':batch_id' => $batch_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
