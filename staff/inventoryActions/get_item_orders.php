<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../db.php';

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

try {
    $sql = "
        SELECT po.id AS order_id,
               po.order_number,
               ri.quantity,
               ri.defective
        FROM receipt_items ri
        INNER JOIN purchase_orders po ON po.id = ri.order_id
        WHERE ri.inventory_id = :item_id
        ORDER BY po.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':item_id' => $item_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows ?: []);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
