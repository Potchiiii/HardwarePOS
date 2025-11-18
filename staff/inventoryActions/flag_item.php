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

$id = isset($data['id']) ? (int)$data['id'] : 0;
$flag = isset($data['flag']) ? trim($data['flag']) : '';

$allowed = ['good', 'damaged', 'expired'];
if ($id <= 0 || !in_array($flag, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE inventory SET `condition` = :flag WHERE id = :id");
    $stmt->execute([':flag' => $flag, ':id' => $id]);

    // return the updated value
    echo json_encode(['condition' => $flag, 'success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
}
