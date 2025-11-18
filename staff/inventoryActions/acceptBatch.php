<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$batch_id = isset($data['batch_id']) ? trim($data['batch_id']) : '';
$item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;
$quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;

if (!$batch_id || $item_id <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Update batch status to 'received'
    $stmt = $pdo->prepare("UPDATE batches SET status = 'received', checked_by = :user_id, checked_at = NOW() WHERE batch_id = :batch_id");
    $stmt->execute([':batch_id' => $batch_id, ':user_id' => $_SESSION['user_id']]);

    // Update inventory with the batch_id and add quantity
    $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + :qty, batch_id = :batch_id WHERE id = :item_id");
    $stmt->execute([
        ':qty' => $quantity,
        ':batch_id' => $batch_id,
        ':item_id' => $item_id
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Batch accepted']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
