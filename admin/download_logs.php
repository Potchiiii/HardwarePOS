<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Validate input
$month = $_GET['month'] ?? null;
$year = $_GET['year'] ?? null;

if (!$month || !$year) {
    die('Month and year are required.');
}

// Convert numeric month to month name
$monthName = date('F', mktime(0, 0, 0, (int)$month, 1));

// Fetch sales data grouped by invoice
$sql = "SELECT 
            s.sale_date,
            s.id AS sale_id,
            i.name AS item_name,
            i.brand AS brand_name,
            si.quantity,
            si.price,
            (si.quantity * si.price) AS amount
        FROM inventory_logs s
        JOIN inventory_log_items si ON s.id = si.sale_id
        JOIN inventory i ON si.inventory_id = i.id
        WHERE MONTH(s.sale_date) = :month AND YEAR(s.sale_date) = :year
        ORDER BY s.sale_date ASC, s.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':month' => $month,
    ':year' => $year
]);
$salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group data by sale_id
$groupedSales = [];
foreach ($salesData as $row) {
    $saleId = $row['sale_id'];
    if (!isset($groupedSales[$saleId])) {
        $groupedSales[$saleId] = [
            'date' => $row['sale_date'],
            'items' => []
        ];
    }
    $groupedSales[$saleId]['items'][] = $row;
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Sales Report");

// Set headers
$headers = ['Date', 'Invoice ID', 'Item Name', 'Brand', 'Qty', 'Price', 'Amount'];
$sheet->fromArray($headers, NULL, 'A1');

// Style header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

// Populate data
$row = 2;
$grandTotal = 0;

foreach ($groupedSales as $saleId => $sale) {
    $date = date('Y-m-d', strtotime($sale['date']));
    $invoice = 'INV-' . str_pad($saleId, 5, '0', STR_PAD_LEFT);

    foreach ($sale['items'] as $index => $item) {
        $sheet->setCellValue("A{$row}", $date);
        $sheet->setCellValue("B{$row}", $invoice);
        $sheet->setCellValue("C{$row}", $item['item_name']);
        $sheet->setCellValue("D{$row}", $item['brand_name']);
        $sheet->setCellValue("E{$row}", $item['quantity']);
        $sheet->setCellValue("F{$row}", $item['price']);
        $sheet->setCellValue("G{$row}", $item['amount']);
        $grandTotal += $item['amount'];
        $row++;
        // Clear repeating values for grouped display
        $date = $invoice = '';
    }
}

// Format Amount and Price columns
$sheet->getStyle("F2:G" . ($row - 1))
    ->getNumberFormat()
    ->setFormatCode('"₱"#,##0.00_-');

// Apply styling to data rows
$dataStyle = [
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle("A2:G" . ($row - 1))->applyFromArray($dataStyle);

// Grand total row
$sheet->setCellValue("F{$row}", 'GRAND TOTAL:');
$sheet->setCellValue("G{$row}", $grandTotal);
$sheet->getStyle("F{$row}:G{$row}")->getFont()->setBold(true);
$sheet->getStyle("G{$row}")
    ->getNumberFormat()
    ->setFormatCode('"₱"#,##0.00_-');

// Auto-size columns
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set download headers
$filename = "{$monthName}_{$year}_Sales_Report.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
