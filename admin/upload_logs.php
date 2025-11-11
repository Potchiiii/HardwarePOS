<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
include '../db.php';
include 'includes/sidebar.php';

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['sales_log']) || $_FILES['sales_log']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "No file uploaded or upload error.";
    } else {
        $allowed = ['csv', 'xls', 'xlsx'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        $file = $_FILES['sales_log'];
        if ($file['size'] > $maxSize) {
            $errors[] = "File too large. Max 5MB.";
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $errors[] = "Unsupported file type. Use CSV or XLSX/XLS.";
            } else {
                $uploadDir = __DIR__ . '/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $target = $uploadDir . '/' . time() . '_' . basename($file['name']);
                if (!move_uploaded_file($file['tmp_name'], $target)) {
                    $errors[] = "Failed to move uploaded file.";
                } else {
                    // Parse file and insert into DB
                    $rows = [];
                    if ($ext === 'csv') {
                        if (($handle = fopen($target, 'r')) !== false) {
                            while (($data = fgetcsv($handle, 0, ",")) !== false) {
                                // Skip entirely empty rows
                                $allEmpty = true;
                                foreach ($data as $c) { if (trim($c) !== '') { $allEmpty = false; break; } }
                                if ($allEmpty) continue;
                                $rows[] = $data;
                            }
                            fclose($handle);
                        } else {
                            $errors[] = "Unable to open CSV file.";
                        }
                    } else { // xls/xlsx
                        // ensure autoloader is loaded before using PhpSpreadsheet
                        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                            require_once __DIR__ . '/../vendor/autoload.php';
                            try {
                                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($target);
                                $sheet = $spreadsheet->getActiveSheet();
                                foreach ($sheet->getRowIterator() as $rowIdx => $row) {
                                    $cellIterator = $row->getCellIterator();
                                    $cellIterator->setIterateOnlyExistingCells(false);
                                    $rowData = [];
                                    foreach ($cellIterator as $cell) {
                                        $rowData[] = trim((string)$cell->getValue());
                                    }
                                    // Skip empty rows
                                    $allEmpty = true;
                                    foreach ($rowData as $c) { if ($c !== '') { $allEmpty = false; break; } }
                                    if ($allEmpty) continue;
                                    $rows[] = $rowData;
                                }
                            } catch (Exception $e) {
                                $errors[] = "Failed to read spreadsheet: " . $e->getMessage();
                            }
                        } else {
                            $errors[] = "Spreadsheet upload (XLS/XLSX) requires phpoffice/phpspreadsheet. Run composer require phpoffice/phpspreadsheet and ensure vendor/autoload.php is present.";
                        }
                    }

                    if (empty($errors)) {
                        if (count($rows) === 0) {
                            $errors[] = "No rows found in the uploaded file.";
                        } else {
                            // Expected format: date, item_name, brand, amount, cashier
                            $first = $rows[0];
                            $firstLower = array_map(function($v){ return strtolower(trim((string)$v)); }, $first);
                            $hasHeader = false;
                            
                            // Check if first row is a header
                            foreach ($firstLower as $val) {
                                if (in_array($val, ['date', 'item_name', 'amount', 'cashier', 'brand'])) {
                                    $hasHeader = true;
                                    break;
                                }
                            }
                            
                            $start = $hasHeader ? 1 : 0;

                            // Process logbook: date, item_name, brand, amount, cashier
                            $pdo->beginTransaction();
                            try {
                                $inserted = 0;
                                $skipped = 0;
                                
                                // Prepare lookup statements
                                $lookupStmt = $pdo->prepare("SELECT id FROM inventory WHERE name = ? AND brand = ? LIMIT 1");
                                $userLookupStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                                $logStmt = $pdo->prepare("INSERT INTO inventory_logs (user_id, sale_date, total_amount) VALUES (?, ?, ?)");
                                $itemStmt = $pdo->prepare("INSERT INTO inventory_log_items (sale_id, inventory_id, quantity, price) VALUES (?, ?, ?, ?)");
                                
                                for ($i = $start; $i < count($rows); $i++) {
                                    $r = $rows[$i];
                                    if (count($r) < 4) {
                                        $skipped++;
                                        continue;
                                    }
                                    
                                    // Parse columns: date, item_name, brand, amount, cashier
                                    $dateRaw = trim((string)($r[0] ?? ''));
                                    $itemName = trim((string)($r[1] ?? ''));
                                    $brand = trim((string)($r[2] ?? ''));
                                    $amountRaw = trim((string)($r[3] ?? ''));
                                    $cashier = trim((string)($r[4] ?? ''));
                                    
                                    if (empty($itemName) || empty($amountRaw)) {
                                        $skipped++;
                                        continue;
                                    }
                                    
                                    // Parse date
                                    $saleDate = date('Y-m-d H:i:s');
                                    if (!empty($dateRaw)) {
                                        try {
                                            $dt = new DateTime($dateRaw);
                                            $saleDate = $dt->format('Y-m-d H:i:s');
                                        } catch (Exception $e) {}
                                    }
                                    
                                    // Parse amount (remove currency symbols and commas)
                                    $amount = (float)preg_replace('/[^\d\.\-]/', '', $amountRaw);
                                    
                                    // Lookup inventory by exact name and brand match
                                    $lookupStmt->execute([$itemName, $brand]);
                                    $inventoryRow = $lookupStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if (!$inventoryRow) {
                                        $skipped++;
                                        continue;
                                    }
                                    
                                    $inventoryId = $inventoryRow['id'];
                                    
                                    // Lookup cashier user ID by username from spreadsheet
                                    $cashierUserId = $_SESSION['user_id']; // Default to current user
                                    if (!empty($cashier)) {
                                        $userLookupStmt->execute([$cashier]);
                                        $userRow = $userLookupStmt->fetch(PDO::FETCH_ASSOC);
                                        if ($userRow) {
                                            $cashierUserId = $userRow['id'];
                                        }
                                    }
                                    
                                    // Create inventory_logs entry for each row
                                    $logStmt->execute([$cashierUserId, $saleDate, $amount]);
                                    $logId = $pdo->lastInsertId();
                                    
                                    // Create inventory_log_items entry (quantity defaults to 1)
                                    $itemStmt->execute([$logId, $inventoryId, 1, $amount]);
                                    
                                    $inserted++;
                                }
                                
                                $pdo->commit();
                                $msg = "Upload complete. Inserted {$inserted} rows";
                                if ($skipped > 0) $msg .= ", skipped {$skipped} rows (item/brand not found in inventory)";
                                $messages[] = $msg;
                            } catch (Exception $e) {
                                $pdo->rollBack();
                                $errors[] = "Database error: " . $e->getMessage();
                            }
                        } // End of if (count($rows) === 0)
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Logs</title>
    <link href="../assets/third_party/tailwind.min.js" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="ml-56 p-6">
    <h1 class="text-2xl font-bold mb-6">Upload Sales Logs</h1>

    <?php if (!empty($messages)): ?>
        <div class="mb-4">
            <?php foreach ($messages as $m): ?>
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded mb-2"><?php echo htmlspecialchars($m); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="mb-4">
            <?php foreach ($errors as $e): ?>
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-2 rounded mb-2"><?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="#" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow-md">
        <div class="mb-4">
            <label for="sales_log" class="block text-gray-700 font-semibold mb-2">Select Sales Log File (CSV or XLSX):</label>
            <input type="file" name="sales_log" id="sales_log" accept=".csv,.xlsx,.xls" required class="border border-gray-300 p-2 rounded w-full">
            <p class="text-sm text-gray-500 mt-2">
                <strong>Expected columns:</strong> date | item_name | brand | amount | cashier<br>
                <em>Header row is optional. Item name and brand must exactly match items in your inventory.</em>
            </p>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Upload</button>
    </form>
</div>
</body>
</html>
