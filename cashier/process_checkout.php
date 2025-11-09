<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'cashier') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['cart']) || !is_array($data['cart'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

try {
    $pdo->beginTransaction();

    $userId = $_SESSION['user_id'];
    $cart = $data['cart'];
    $totalAmount = 0;

    foreach ($cart as $item) {
        $totalAmount += $item['price'] * $item['quantity'];
    }

    // Insert into inventory_logs
    $stmt = $pdo->prepare("INSERT INTO inventory_logs (user_id, total_amount) VALUES (?, ?)");
    $stmt->execute([$userId, $totalAmount]);
    $saleId = $pdo->lastInsertId();

    // Insert items
    $itemStmt = $pdo->prepare("INSERT INTO inventory_log_items (sale_id, inventory_id, quantity, price) VALUES (?, ?, ?, ?)");
    $updateStockStmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");

    foreach ($cart as $item) {
        $itemStmt->execute([$saleId, $item['id'], $item['quantity'], $item['price']]);
        $updateStockStmt->execute([$item['quantity'], $item['id'], $item['quantity']]);
        // Check if the update was successful
        if ($updateStockStmt->rowCount() === 0) {
            throw new Exception("Insufficient stock for item ID: " . $item['id']);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'sale_id' => $saleId]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
}