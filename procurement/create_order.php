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
    // Determine if handling multipart form data (file upload) or JSON
    $data = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (stripos($contentType, 'multipart/form-data') !== false) {
        // Handle multipart form data (file upload)
        $data = $_POST;
        // Will handle file upload separately
    } else {
        // Handle JSON
        $data = json_decode(file_get_contents('php://input'), true);
    }
    
    // Generate order number
    $orderNumber = 'ORD-' . date('Y-m-d-') . uniqid();

    // Handle image upload if provided
    $imageUrl = null;
    if (!empty($_FILES['product_image']['tmp_name'])) {
        $uploadDir = '../assets/product_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['product_image'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid image type']);
            exit();
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . strtolower($ext);
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload image']);
            exit();
        }
        
        $imageUrl = 'assets/product_images/' . $filename;
    }

    // If ordering a new product not in inventory, insert it first
    $itemId = $data['item_id'] ?? null;
    if (empty($itemId)) {
        // Check if new product data is provided (either as new_product JSON or as POST fields)
        $productName = $data['product_name'] ?? ($data['item_name'] ?? 'New Product');
        $productBrand = $data['brand'] ?? null;
        $productCategory = $data['category'] ?? 'General';
        $productThreshold = $data['low_threshold'] ?? 10;
        
        if (!empty($productName) && ($productName !== 'New Product' || !empty($productBrand) || !empty($imageUrl))) {
            // Insert new product into inventory WITHOUT pricing (prices will be set separately)
            $stmtIns = $pdo->prepare("INSERT INTO inventory (name, brand, category, quantity, low_threshold, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([
                $productName,
                $productBrand,
                $productCategory,
                0,
                $productThreshold,
                $imageUrl
            ]);
            $itemId = $pdo->lastInsertId();
        }
    }

    // Derive item_name and brand from inventory if not explicitly provided
    $itemName = $data['item_name'] ?? '';
    $brand    = $data['brand'] ?? '';
    if ($itemId && ($itemName === '' || $brand === '')) {
        $stmtInv = $pdo->prepare("SELECT name, brand FROM inventory WHERE id = ? LIMIT 1");
        $stmtInv->execute([$itemId]);
        $inv = $stmtInv->fetch();
        if ($inv) {
            if ($itemName === '') $itemName = $inv['name'];
            if ($brand === '')    $brand    = $inv['brand'];
        }
    }

    $stmt = $pdo->prepare("INSERT INTO purchase_orders (order_number, item_id, item_name, brand, quantity, created_by, notes, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $orderNumber,
        $itemId,
        $itemName,
        $brand,
        $data['quantity'],
        $_SESSION['user_id'],
        $data['notes'] ?? '',
        $data['supplier_id'] ?? null
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'order_number' => $orderNumber]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create order']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
