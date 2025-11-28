<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success'=>false, 'error'=>'Unauthorized']);
    exit();
}

require_once '../db.php';

// Ensure extended processing columns exist on notifications table (idempotent, outside transaction).
@$pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed TINYINT(1) DEFAULT 0");
@$pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_by INT(11) NULL");
@$pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_at DATETIME NULL");
@$pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_notes TEXT NULL");
@$pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_added_qty INT(11) NULL");
@$pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_defective_qty INT(11) NULL");

// Create receipt tables outside of transaction
@$pdo->exec("CREATE TABLE IF NOT EXISTS receipt_logs (id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT, order_id INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, notes TEXT)");
@$pdo->exec("CREATE TABLE IF NOT EXISTS receipt_items (id INT AUTO_INCREMENT PRIMARY KEY, receipt_id INT, inventory_id INT NULL, order_id INT NULL, quantity INT, defective INT, notes TEXT)");

$inTransaction = false;
try{
    $data = json_decode(file_get_contents('php://input'), true);
    $nid = $data['notification_id'] ?? null;
    if (!$nid) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success'=>false, 'error'=>'notification_id required']);
        exit();
    }

    // fetch notification (without FOR UPDATE lock to avoid transaction issues)
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->execute([$nid]);
    $n = $stmt->fetch();
    if (!$n) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success'=>false, 'error'=>'Notification not found']);
        exit();
    }
    if ($n['processed']) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success'=>false, 'error'=>'Already processed']);
        exit();
    }

    $received = isset($data['received_qty']) ? intval($data['received_qty']) : intval($n['quantity']);
    $defective = isset($data['defective_qty']) ? intval($data['defective_qty']) : 0;
    if ($defective < 0) $defective = 0;
    if ($received < 0) $received = 0;
    $toAdd = $received - $defective;
    if ($toAdd < 0) $toAdd = 0;

    // Try to resolve order and inventory item from order_number in notifications table.
    $orderId = null;
    $itemId  = null;
    if (!empty($n['order_number'])) {
        $stmtOrder = $pdo->prepare("SELECT id, item_id FROM purchase_orders WHERE order_number = ? LIMIT 1");
        $stmtOrder->execute([$n['order_number']]);
        $orderRow = $stmtOrder->fetch();
        if ($orderRow) {
            $orderId = $orderRow['id'];
            $itemId  = $orderRow['item_id'];
        }
    }

    // Start transaction for data updates
    $pdo->beginTransaction();
    $inTransaction = true;
    
    // update inventory if we resolved an item and there is good quantity to add
    if (!empty($itemId) && $toAdd > 0) {
        $stmtUp = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
        $stmtUp->execute([$toAdd, $itemId]);
    }

    // Insert into receipt logs
    $stmtR = $pdo->prepare("INSERT INTO receipt_logs (staff_id, order_id, notes) VALUES (?, ?, ?)");
    $stmtR->execute([$_SESSION['user_id'], $orderId, $data['notes'] ?? null]);
    $rid = $pdo->lastInsertId();

    // Insert into receipt items
    $stmtRI = $pdo->prepare("INSERT INTO receipt_items (receipt_id, inventory_id, order_id, quantity, defective, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtRI->execute([$rid, $itemId, $orderId, $received, $defective, $data['notes'] ?? null]);

    // Update purchase_orders with expiry date if provided
    if (!empty($orderId) && isset($data['expiry_date']) && !empty($data['expiry_date'])) {
        $updateExpiryStmt = $pdo->prepare("UPDATE purchase_orders SET expiry_date = ? WHERE id = ?");
        $updateExpiryStmt->execute([$data['expiry_date'], $orderId]);
    }

    // mark notification processed
    $upd = $pdo->prepare("UPDATE notifications SET processed = 1, processed_by = ?, processed_at = NOW(), processed_notes = ?, processed_added_qty = ?, processed_defective_qty = ? WHERE id = ?");
    $upd->execute([$_SESSION['user_id'], $data['notes'] ?? null, $toAdd, $defective, $nid]);

    $pdo->commit();
    $inTransaction = false;
    
    ob_end_clean();
    echo json_encode(['success'=>true, 'receipt_id'=>$rid]);
} catch (Exception $e) {
    if ($inTransaction) {
        $pdo->rollBack();
    }
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
?>
