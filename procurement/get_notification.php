<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit(); }
require_once '../db.php';
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
$stmt->execute([$id]);
$n = $stmt->fetch();
if (!$n) { http_response_code(404); echo json_encode(['error'=>'Not found']); exit(); }
header('Content-Type: application/json');
echo json_encode($n);
?>
