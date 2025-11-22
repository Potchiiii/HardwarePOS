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
    $orderId = $data['order_id'] ?? null;
    if (!$orderId) { http_response_code(400); echo json_encode(['error'=>'order_id required']); exit(); }

    // Check status
    $check = $pdo->prepare("SELECT status FROM purchase_orders WHERE id = ?");
    $check->execute([$orderId]);
    $o = $check->fetch();
    if (!$o) { http_response_code(404); echo json_encode(['error'=>'Order not found']); exit(); }
    if ($o['status'] === 'received') {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete an order that has been received']);
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM purchase_orders WHERE id = ?");
    $result = $stmt->execute([$orderId]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete order']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
