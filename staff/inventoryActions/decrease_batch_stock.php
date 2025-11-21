<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$batch_id = isset($data['batch_id']) ? trim($data['batch_id']) : '';
$item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;
$amount = isset($data['amount']) ? (int)$data['amount'] : 0;

if (!$batch_id || $item_id <= 0 || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Ensure batch has enough quantity
    $stmt = $pdo->prepare("SELECT quantity FROM batches WHERE batch_id = :batch_id AND item_id = :item_id FOR UPDATE");
    $stmt->execute([':batch_id' => $batch_id, ':item_id' => $item_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$batch) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Batch not found']);
        exit;
    }

    if ($batch['quantity'] < $amount) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Not enough quantity in batch']);
        exit;
    }

    // Reduce batch quantity
    $stmt = $pdo->prepare("UPDATE batches SET quantity = quantity - :amt WHERE batch_id = :batch_id AND item_id = :item_id");
    $stmt->execute([':amt' => $amount, ':batch_id' => $batch_id, ':item_id' => $item_id]);

    // Reduce inventory total quantity
    $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - :amt WHERE id = :item_id");
    $stmt->execute([':amt' => $amount, ':item_id' => $item_id]);

    // Get updated values
    $stmt = $pdo->prepare("SELECT quantity, low_threshold FROM inventory WHERE id = :id");
    $stmt->execute([':id' => $item_id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT quantity FROM batches WHERE batch_id = :batch_id AND item_id = :item_id");
    $stmt->execute([':batch_id' => $batch_id, ':item_id' => $item_id]);
    $batchAfter = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    $newInvQty = (int)($inv['quantity'] ?? 0);
    $lowThreshold = isset($inv['low_threshold']) ? (int)$inv['low_threshold'] : 0;

    echo json_encode([
        'success' => true,
        'new_inventory_quantity' => $newInvQty,
        'new_batch_quantity' => (int)($batchAfter['quantity'] ?? 0),
        'isLow' => ($lowThreshold > 0) ? ($newInvQty < $lowThreshold) : false
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: '.$e->getMessage()]);
}

?>
