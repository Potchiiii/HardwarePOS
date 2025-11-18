<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../db.php';

$batch_id = isset($_GET['batch_id']) ? trim($_GET['batch_id']) : '';

if (!$batch_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid batch ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM batches WHERE batch_id = :batch_id");
    $stmt->execute([':batch_id' => $batch_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$batch) {
        http_response_code(404);
        echo json_encode(['error' => 'Batch not found']);
        exit;
    }

    echo json_encode($batch);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
