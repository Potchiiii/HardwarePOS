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
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (order_number, item_name, quantity, message, created_by) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $data['order_number'],
        $data['item_name'],
        $data['quantity'],
        $data['message'],
        $_SESSION['user_id']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create notification']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
