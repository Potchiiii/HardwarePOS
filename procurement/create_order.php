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
    
    // Generate order number
    $orderNumber = 'ORD-' . date('Y-m-d-') . uniqid();

    // If ordering a new product not in inventory, insert it first
    $itemId = $data['item_id'] ?? null;
    if (empty($itemId) && !empty($data['new_product']) && is_array($data['new_product'])) {
        // expected fields: name, brand, initial_quantity (optional), low_threshold (optional), price (optional)
        $np = $data['new_product'];
        $stmtIns = $pdo->prepare("INSERT INTO inventory (name, brand, quantity, low_threshold, price) VALUES (?, ?, ?, ?, ?)");
        $stmtIns->execute([
            $np['name'] ?? ($data['item_name'] ?? 'New Product'),
            $np['brand'] ?? ($data['brand'] ?? null),
            $np['initial_quantity'] ?? 0,
            $np['low_threshold'] ?? 1,
            $np['price'] ?? null
        ]);
        $itemId = $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("INSERT INTO purchase_orders (order_number, item_id, item_name, brand, quantity, created_by, notes, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $orderNumber,
        $itemId,
        $data['item_name'] ?? '',
        $data['brand'] ?? '',
        $data['quantity'],
        $_SESSION['user_id'],
        $data['notes'] ?? '',
        $data['supplier_id'] ?? null
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'order_number' => $orderNumber]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create order']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
