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
                            // Determine whether file has header row and build column map
                            $first = $rows[0];
                            $firstLower = array_map(function($v){ return strtolower(trim((string)$v)); }, $first);
                            $hasHeader = false;
                            $expectedFields = [
                                'date' => ['date'],
                                'item' => ['item','item code','sku'],
                                'amount' => ['amount','total','price'],
                                'cashier' => ['cashier','cashier name','cashier_name'],
                                'item_name' => ['item name','item_name','name','product'],
                                'brand' => ['brand','manufacturer','maker']
                            ];
                            $colMap = []; // field => index

                            // detect header if any known label appears
                            foreach ($firstLower as $idx => $val) {
                                foreach ($expectedFields as $field => $aliases) {
                                    foreach ($aliases as $a) {
                                        if ($val === $a) {
                                            $colMap[$field] = $idx;
                                            $hasHeader = true;
                                        }
                                    }
                                }
                            }

                            // If header detected, remove it from rows start index
                            $start = $hasHeader ? 1 : 0;

                            // If no header, assume order: date, item, amount, cashier, item_name, brand (if present)
                            if (!$hasHeader) {
                                // map sequentially up to available columns
                                $sequential = ['date','item','amount','cashier','item_name','brand'];
                                for ($i = 0; $i < min(count($first), count($sequential)); $i++) {
                                    $colMap[$sequential[$i]] = $i;
                                }
                            } else {
                                // for any expected field not found in header, attempt to assume sequential positions
                                $sequential = ['date','item','amount','cashier','item_name','brand'];
                                $nextIdx = 0;
                                foreach ($sequential as $field) {
                                    if (!isset($colMap[$field])) {
                                        // find next unused index in header row
                                        while (in_array($nextIdx, $colMap)) $nextIdx++;
                                        if ($nextIdx < count($first)) {
                                            $colMap[$field] = $nextIdx++;
                                        }
                                    }
                                }
                            }

                            // Prepare DB insert (adjust column names to your DB schema)
                            $pdo->beginTransaction();
                            try {
                                // First create the inventory_logs entry
                                $logStmt = $pdo->prepare("INSERT INTO inventory_logs (user_id, sale_date, total_amount) VALUES (?, ?, ?)");
                                
                                // Get the current user's ID from session
                                $userId = $_SESSION['user_id'];
                                $currentDate = date('Y-m-d H:i:s');
                                
                                // Calculate total amount from the rows
                                $totalAmount = 0;
                                for ($i = $start; $i < count($rows); $i++) {
                                    $r = $rows[$i];
                                    $get = function($field) use ($r, $colMap) {
                                        if (!isset($colMap[$field])) return '';
                                        $idx = $colMap[$field];
                                        return isset($r[$idx]) ? trim((string)$r[$idx]) : '';
                                    };
                                    
                                    $amountRaw = $get('amount');
                                    if ($amountRaw !== '') {
                                        $normalized = preg_replace('/[^\d\.\-]/', '', $amountRaw);
                                        if ($normalized !== '' && is_numeric($normalized)) {
                                            $totalAmount += (float)$normalized;
                                        }
                                    }
                                }

                                // Insert main log entry
                                if (!$logStmt->execute([$userId, $currentDate, $totalAmount])) {
                                    throw new Exception("Failed to create log entry");
                                }
                                
                                // Get the ID of the newly created log
                                $logId = $pdo->lastInsertId();
                                
                                // Prepare statement for log items
                                $itemStmt = $pdo->prepare("INSERT INTO inventory_log_items (sale_id, inventory_id, quantity, price) VALUES (?, ?, ?, ?)");
                                
                                $inserted = 0;
                                for ($i = $start; $i < count($rows); $i++) {
                                    $r = $rows[$i];
                                    
                                    $item = $get('item'); // This should be your inventory_id
                                    $amountRaw = $get('amount');
                                    
                                    // Skip empty rows
                                    if (empty($item) || empty($amountRaw)) {
                                        continue;
                                    }

                                    // Parse amount
                                    $amount = 0.0;
                                    if ($amountRaw !== '') {
                                        $normalized = preg_replace('/[^\d\.\-]/', '', $amountRaw);
                                        if ($normalized !== '' && is_numeric($normalized)) {
                                            $amount = (float)$normalized;
                                        }
                                    }

                                    // Default quantity to 1 if not specified
                                    $quantity = 1;
                                    
                                    // Insert the log item
                                    if (!$itemStmt->execute([
                                        $logId,         // sale_id
                                        $item,          // inventory_id
                                        $quantity,      // quantity
                                        $amount         // price
                                    ])) {
                                        throw new Exception("Insert failed on row " . ($i+1));
                                    }
                                    $inserted++;
                                }
                                
                                $pdo->commit();
                                $messages[] = "Upload complete. Inserted {$inserted} rows.";
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
            <p class="text-sm text-gray-500 mt-2">Expected columns (order not required if header present): date, item, amount, cashier, item name, brand. Header row will be detected and used to map columns.</p>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Upload</button>
    </form>
</div>
</body>
</html>
