<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$change = $data['change'] ?? 0;

try {
    $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
    $stmt->execute([$change, $id]);
    
    // Get updated quantity and threshold
    $stmt = $pdo->prepare("SELECT quantity, low_threshold FROM inventory WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'quantity' => $result['quantity'],
        'isLow' => $result['quantity'] < $result['low_threshold']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}