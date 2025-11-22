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
    // Check current order
    $stmtCheck = $pdo->prepare("SELECT * FROM purchase_orders WHERE id = ?");
    $stmtCheck->execute([$data['order_id']]);
    $order = $stmtCheck->fetch();
    if (!$order) { http_response_code(404); echo json_encode(['error'=>'Order not found']); exit(); }

    // If already received, do not allow changes
    if ($order['status'] === 'received') {
        http_response_code(400);
        echo json_encode(['error' => 'Order already received and cannot be modified']);
        exit();
    }

    $newStatus = $data['status'];

    // If marking as received, notify inventory staff but DO NOT touch stock here.
    // Inventory will be adjusted only when staff process the notification.
    if ($newStatus === 'received') {
        // Procurement cannot declare actual received or defective quantities; use ordered quantity only.
        $receivedQty = intval($order['quantity']);
        $defectiveQty = 0;
        if ($receivedQty < 0) $receivedQty = 0;

        // Begin transaction
        $pdo->beginTransaction();
        try {
            // Update order status and append received notes
            $notes = $order['notes'] ?? '';
            if (!empty($data['received_notes'])) {
                $notes .= "\n[RECEIVED NOTES] " . $data['received_notes'];
            }
            $stmt = $pdo->prepare("UPDATE purchase_orders SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$newStatus, $notes, $data['order_id']]);

            // Insert notification to staff so they can inspect and record defects.
            // Use only columns that exist in the base schema from hardwareinventory (6).sql.
            $message = "Marked as received by procurement. Expected/received: " . $receivedQty . ". Initial defective reported: " . $defectiveQty . ".";
            $stmtNotif = $pdo->prepare("INSERT INTO notifications (order_number, item_name, quantity, message, created_by, sent_to_staff) VALUES (?, ?, ?, ?, ?, 1)");
            $stmtNotif->execute([$order['order_number'], $order['item_name'], $receivedQty, $message, $_SESSION['user_id']]);

            $pdo->commit();
            echo json_encode(['success' => true]);
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    } else {
        // Normal status update (not received)
        $stmt = $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?");
        $result = $stmt->execute([$newStatus, $data['order_id']]);
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update order']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
