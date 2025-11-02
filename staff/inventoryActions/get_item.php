<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once '../../db.php';

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        throw new Exception('Item ID is required');
    }

    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception('Item not found');
    }

    // Return item data as JSON
    header('Content-Type: application/json');
    echo json_encode($item);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}