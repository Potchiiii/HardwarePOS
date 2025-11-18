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
$item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;
$quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
$item_name = isset($data['item_name']) ? trim($data['item_name']) : '';
$brand = isset($data['brand']) ? trim($data['brand']) : '';
$notes = isset($data['notes']) ? trim($data['notes']) : '';
$isEdit = isset($data['batch_id']) && !empty($data['batch_id']);

if (!$batch_id || $item_id <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    // Check if batch already exists
    $checkStmt = $pdo->prepare("SELECT id FROM batches WHERE batch_id = :batch_id");
    $checkStmt->execute([':batch_id' => $batch_id]);
    $exists = $checkStmt->fetch();

    if ($exists && !$isEdit) {
        http_response_code(400);
        echo json_encode(['error' => 'Batch ID already exists']);
        exit;
    }

    if ($isEdit) {
        // Update existing batch
        $stmt = $pdo->prepare("UPDATE batches SET item_id = :item_id, item_name = :item_name, brand = :brand, quantity = :quantity, notes = :notes WHERE batch_id = :batch_id");
        $stmt->execute([
            ':item_id' => $item_id,
            ':item_name' => $item_name,
            ':brand' => $brand,
            ':quantity' => $quantity,
            ':notes' => $notes,
            ':batch_id' => $batch_id
        ]);
    } else {
        // Create new batch
        $stmt = $pdo->prepare("INSERT INTO batches (batch_id, item_id, item_name, brand, quantity, status, created_by, notes) VALUES (:batch_id, :item_id, :item_name, :brand, :quantity, 'pending', :created_by, :notes)");
        $stmt->execute([
            ':batch_id' => $batch_id,
            ':item_id' => $item_id,
            ':item_name' => $item_name,
            ':brand' => $brand,
            ':quantity' => $quantity,
            ':created_by' => $_SESSION['user_id'],
            ':notes' => $notes
        ]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
