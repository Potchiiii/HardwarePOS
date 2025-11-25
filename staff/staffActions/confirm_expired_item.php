<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../../db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['item_id']) || !isset($data['quantity'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$orderId = $data['order_id'];
$itemId = $data['item_id'];
$quantity = $data['quantity'];

try {
    $pdo->beginTransaction();

    // Update purchase_orders to set quantity to 0
    $updateOrderStmt = $pdo->prepare("UPDATE purchase_orders SET quantity = 0 WHERE id = ?");
    $updateOrderStmt->execute([$orderId]);

    // Reduce inventory quantity
    $updateInventoryStmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
    $updateInventoryStmt->execute([$quantity, $itemId]);

    // Log the removal
    $logStmt = $pdo->prepare("INSERT INTO inventory_logs (user_id, total_amount) VALUES (?, 0)");
    $logStmt->execute([$_SESSION['user_id']]);
    $logId = $pdo->lastInsertId();

    $logItemStmt = $pdo->prepare("INSERT INTO inventory_log_items (sale_id, inventory_id, quantity, price) VALUES (?, ?, ?, 0)");
    $logItemStmt->execute([$logId, $itemId, -$quantity]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Expired item confirmed and removed']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
}
?>
