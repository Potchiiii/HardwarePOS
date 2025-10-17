<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

try {
    // Get image path before deleting
    $stmt = $pdo->prepare("SELECT image_url FROM inventory WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->execute([$id]);

    // Delete image file if exists
    if ($item['image_url'] && file_exists("../{$item['image_url']}")) {
        unlink("../{$item['image_url']}");
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}