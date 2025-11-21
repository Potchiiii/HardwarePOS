<?php
// file already created earlier; this is a duplicate safe-check placeholder
// Keep existing get_suppliers.php if present.
?>
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit();
}
require_once '../db.php';

try {
    $stmt = $pdo->query("SELECT id, name, contact_person, phone, email FROM suppliers ORDER BY name ASC");
    $suppliers = $stmt->fetchAll();
    header('Content-Type: application/json');
    echo json_encode($suppliers);
} catch (Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
