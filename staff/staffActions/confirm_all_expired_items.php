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

if (!isset($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid items data']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Log the removal
    $logStmt = $pdo->prepare("INSERT INTO inventory_logs (user_id, total_amount) VALUES (?, 0)");
    $logStmt->execute([$_SESSION['user_id']]);
    $logId = $pdo->lastInsertId();

    $updateOrderStmt = $pdo->prepare("UPDATE purchase_orders SET quantity = 0 WHERE id = ?");
    $updateInventoryStmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
    $logItemStmt = $pdo->prepare("INSERT INTO inventory_log_items (sale_id, inventory_id, quantity, price) VALUES (?, ?, ?, 0)");

    foreach ($data['items'] as $item) {
        $orderId = $item['order_id'];
        $itemId = $item['item_id'];
        $quantity = $item['quantity'];

        // Update purchase_orders
        $updateOrderStmt->execute([$orderId]);

        // Reduce inventory quantity
        $updateInventoryStmt->execute([$quantity, $itemId]);

        // Log each item removal
        $logItemStmt->execute([$logId, $itemId, -$quantity]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'All expired items confirmed and removed']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
}
?>
