<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once '../db.php';

try {
    $itemId = $_POST['itemId'] ?? null;
    $name = $_POST['itemName'];
    $brand = $_POST['brand'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $lowThreshold = $_POST['lowThreshold'];
    $wholeSale = $_POST['whole_sale'] ?? null;
    $perKilo = $_POST['per_kilo'] ?? null;
    $perLength = $_POST['per_length'] ?? null;
    
    // Handle image upload
    $image_url = null;
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === 0) {
        $file = $_FILES['productImage'];

        // Security: Validate file type and size
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.');
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext; // Generate unique filename
        $upload_path = '../assets/product_images/' . $filename;
        
        // Check if directory exists, if not create it
        if (!file_exists('../assets/product_images')) {
            // Use more secure permissions
            mkdir('../assets/product_images', 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $image_url = 'assets/product_images/' . $filename;
        }
    }

    if ($itemId) {
        // Update existing item
        if ($image_url) {
            // If new image uploaded, update image path
            $stmt = $pdo->prepare("UPDATE inventory SET name = ?, brand = ?, category = ?, 
                                 quantity = ?, price = ?, low_threshold = ?, image_url = ?,
                                 whole_sale = ?, per_kilo = ?, per_length = ?
                                 WHERE id = ?");
            $stmt->execute([$name, $brand, $category, $quantity, $price, $lowThreshold, 
                          $image_url, $wholeSale, $perKilo, $perLength, $itemId]);
        } else {
            // Keep existing image
            $stmt = $pdo->prepare("UPDATE inventory SET name = ?, brand = ?, category = ?, 
                                 quantity = ?, price = ?, low_threshold = ?,
                                 whole_sale = ?, per_kilo = ?, per_length = ?
                                 WHERE id = ?");
            $stmt->execute([$name, $brand, $category, $quantity, $price, $lowThreshold, 
                          $wholeSale, $perKilo, $perLength, $itemId]);
        }
    } else {
        // Insert new item
        $stmt = $pdo->prepare("INSERT INTO inventory (name, brand, category, quantity, price, 
                             low_threshold, image_url, whole_sale, per_kilo, per_length) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $brand, $category, $quantity, $price, $lowThreshold, $image_url,
                      $wholeSale, $perKilo, $perLength]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}