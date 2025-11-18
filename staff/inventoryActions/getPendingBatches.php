<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../db.php';

try {
    $stmt = $pdo->query("SELECT * FROM batches WHERE status = 'pending' ORDER BY created_at DESC");
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($batches);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
