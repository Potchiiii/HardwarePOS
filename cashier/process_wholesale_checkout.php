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

    // Insert items and handle wholesale deductions
    $itemStmt = $pdo->prepare("INSERT INTO inventory_log_items (sale_id, inventory_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($cart as $item) {
        // Get product details including wholesale deduction info
        $productStmt = $pdo->prepare("SELECT per_unit, per_length, per_kilo, wholesale_deduction_units, wholesale_deduction_meters, wholesale_deduction_kilos FROM inventory WHERE id = ?");
        $productStmt->execute([$item['id']]);
        $product = $productStmt->fetch();
        
        if (!$product) {
            throw new Exception("Product not found: " . $item['id']);
        }
        
        // Determine the deduction amount based on product type
        $deductionAmount = 0;
        
        if ($item['priceType'] === 'wholesale') {
            // For wholesale, use the configured deduction amount
            if ($product['per_unit']) {
                $deductionAmount = $product['wholesale_deduction_units'] ?? 1;
            } elseif ($product['per_length']) {
                $deductionAmount = $product['wholesale_deduction_meters'] ?? 1;
            } elseif ($product['per_kilo']) {
                $deductionAmount = $product['wholesale_deduction_kilos'] ?? 1;
            } else {
                $deductionAmount = 1; // fallback
            }
            
            // Multiply by quantity if it's a multi-unit wholesale
            $deductionAmount = $deductionAmount * $item['quantity'];
        } else {
            // For retail, use the quantity as-is
            $deductionAmount = $item['quantity'];
        }
        
        // Insert the sale item
        $itemStmt->execute([$saleId, $item['id'], $item['quantity'], $item['price']]);
        
        // Update inventory with the calculated deduction
        $updateStockStmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
        $updateStockStmt->execute([$deductionAmount, $item['id'], $deductionAmount]);
        
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
?>
