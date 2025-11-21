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
    echo json_encode(['error' => 'Invalid item id']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT batch_id, quantity FROM batches WHERE item_id = :item_id AND status = 'received' AND quantity > 0 ORDER BY created_at DESC");
    $stmt->execute([':item_id' => $item_id]);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($batches);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

?>
