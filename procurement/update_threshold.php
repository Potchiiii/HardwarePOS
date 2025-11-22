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
    
    if (!isset($data['item_id']) || !isset($data['low_threshold'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields', 'received' => $data]);
        exit();
    }
    
    // Validate threshold is a positive integer
    if (!is_numeric($data['low_threshold']) || $data['low_threshold'] < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Threshold must be a positive number', 'value' => $data['low_threshold']]);
        exit();
    }
    
    $stmt = $pdo->prepare("
        UPDATE inventory 
        SET low_threshold = ? 
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        intval($data['low_threshold']),
        intval($data['item_id'])
    ]);
    
    if ($result) {
        // Verify the update was applied
        $verify = $pdo->prepare("SELECT low_threshold FROM inventory WHERE id = ?");
        $verify->execute([intval($data['item_id'])]);
        $row = $verify->fetch();
        echo json_encode(['success' => true, 'saved_value' => $row['low_threshold']]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update threshold', 'rows_affected' => $stmt->rowCount()]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'type' => get_class($e)]);
}
?>
