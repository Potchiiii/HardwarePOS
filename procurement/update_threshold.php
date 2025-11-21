<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'procurement') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['item_id']) || !isset($data['low_threshold'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }
    
    // Validate threshold is a positive integer
    if (!is_numeric($data['low_threshold']) || $data['low_threshold'] < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Threshold must be a positive number']);
        exit();
    }
    
    $stmt = $pdo->prepare("
        UPDATE inventory 
        SET low_threshold = ? 
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        intval($data['low_threshold']),
        intval($data['item_id'])
    ]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update threshold']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
