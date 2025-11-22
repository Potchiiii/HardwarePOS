<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit();
}
require_once '../db.php';

// Ensure extended processing columns exist on notifications table (idempotent).
try {
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_by INT(11) NULL");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_at DATETIME NULL");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_notes TEXT NULL");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_added_qty INT(11) NULL");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_defective_qty INT(11) NULL");
} catch (Exception $e) {
    // best-effort; if this fails, later queries may fail and will be reported as errors
}

try{
    $data = json_decode(file_get_contents('php://input'), true);
    $nid = $data['notification_id'] ?? null;
    if (!$nid) { http_response_code(400); echo json_encode(['error'=>'notification_id required']); exit(); }

    // fetch notification
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ? FOR UPDATE");
    $stmt->execute([$nid]);
    $n = $stmt->fetch();
    if (!$n) { http_response_code(404); echo json_encode(['error'=>'Notification not found']); exit(); }
    if ($n['processed']) { http_response_code(400); echo json_encode(['error'=>'Already processed']); exit(); }

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

    $pdo->beginTransaction();
    try {
        // Debug: log what we're about to do
        error_log("process_notification: orderId=$orderId, itemId=$itemId, toAdd=$toAdd, received=$received, defective=$defective");
        
        // update inventory if we resolved an item and there is good quantity to add
        if (!empty($itemId) && $toAdd > 0) {
            $stmtUp = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
            $result = $stmtUp->execute([$toAdd, $itemId]);
            error_log("Inventory update result: " . ($result ? 'success' : 'failed'));
        } else {
            error_log("Inventory update skipped: itemId empty? " . (empty($itemId) ? 'yes' : 'no') . ", toAdd > 0? " . ($toAdd > 0 ? 'yes' : 'no'));
        }

        // record receipt in receipts tables (create if not exist)
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS receipt_logs (id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT, order_id INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, notes TEXT)"); } catch (Exception $e) {}
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS receipt_items (id INT AUTO_INCREMENT PRIMARY KEY, receipt_id INT, inventory_id INT NULL, order_id INT NULL, quantity INT, defective INT, notes TEXT)"); } catch (Exception $e) {}

        $stmtR = $pdo->prepare("INSERT INTO receipt_logs (staff_id, order_id, notes) VALUES (?, ?, ?)");
        $stmtR->execute([$_SESSION['user_id'], $orderId, $data['notes'] ?? null]);
        $rid = $pdo->lastInsertId();

        $stmtRI = $pdo->prepare("INSERT INTO receipt_items (receipt_id, inventory_id, order_id, quantity, defective, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtRI->execute([$rid, $itemId, $orderId, $received, $defective, $data['notes'] ?? null]);

        // mark notification processed
        $upd = $pdo->prepare("UPDATE notifications SET processed = 1, processed_by = ?, processed_at = NOW(), processed_notes = ?, processed_added_qty = ?, processed_defective_qty = ? WHERE id = ?");
        $upd->execute([$_SESSION['user_id'], $data['notes'] ?? null, $toAdd, $defective, $nid]);

        $pdo->commit();
        echo json_encode(['success'=>true, 'receipt_id'=>$rid]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500); echo json_encode(['error'=>$e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error'=>$e->getMessage()]);
}
?>
