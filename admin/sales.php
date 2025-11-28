<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once '../db.php';

// Fetch sales data with items and cashier name (excluding negative transactions)
$sql = "SELECT 
            s.id AS sale_id, 
            s.sale_date, 
            s.total_amount, 
            u.username AS cashier,
            GROUP_CONCAT(CONCAT(i.name, ' x', si.quantity) SEPARATOR ', ') AS items,
            s.id AS invoice_no
        FROM inventory_logs s
        JOIN users u ON s.user_id = u.id
        JOIN inventory_log_items si ON s.id = si.sale_id
        JOIN inventory i ON si.inventory_id = i.id
        WHERE s.total_amount > 0
        GROUP BY s.id
        ORDER BY s.sale_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales | Hardware Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }
        body {
            display: flex;
            background-color: #f9f9f9;
            color: #333;
            min-height: 100vh;
        }
        .content {
            margin-left: 220px;
            padding: 30px;
            flex: 1;
        }
        h2.page-title {
            margin-bottom: 20px;
            font-size: 24px;
        }
        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        input[type="text"], input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #2980b9;
            color: #fff;
            font-weight: normal;
        }
        tr:hover {
            background: #f5f5f5;
            transition: background-color 0.2s;
        }
        .amount {
            font-weight: 500;
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .pagination button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .pagination button:hover {
            background: #f5f5f5;
        }
        .pagination button.active {
            background: #2980b9;
            color: white;
            border-color: #2980b9;
        }
        .download-btn {
            background: #27ae60;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            margin-left: auto;
        }
        .download-btn:hover {
            background: #219a52;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 24px;
            border-radius: 8px;
            width: 320px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #333;
        }
        .modal select {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 20px;
        }
        .modal-actions button {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        .btn-download {
            background: #27ae60;
            color: white;
        }
        .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="content">
    <h2 class="page-title">Sales Transactions</h2>

    <div class="controls">
        <input type="text" placeholder="Search transaction or items...">
        <input type="date" value="2025-07-01">
        <span>to</span>
        <input type="date" value="2025-07-26">
        <button class="download-btn" onclick="showModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
            </svg>
            Download Sales
        </button>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Transaction</th>
                <th>Items</th>
                <th>Amount (₱)</th>
                <th>Cashier</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($sales as $sale): ?>
            <tr>
                <td><?= date("Y‑m‑d", strtotime($sale['sale_date'])) ?></td>
                <td>TXN‑<?= str_pad($sale['sale_id'], 5, '0', STR_PAD_LEFT) ?></td>
                <td><?= htmlspecialchars($sale['items']) ?></td>
                <td class="amount">₱<?= number_format($sale['total_amount'], 2) ?></td>
                <td><?= htmlspecialchars($sale['cashier']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pagination">
        <button>Previous</button>
        <button class="active">1</button>
        <button>2</button>
        <button>3</button>
        <button>Next</button>
    </div>

    <div class="modal" id="downloadModal">
        <div class="modal-content">
            <div class="modal-title">Download Sales Report</div>
            <label for="monthSelect">Select Month:</label>
            <select id="monthSelect">
                <option value="">Select Month</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>"><?= date("F", mktime(0, 0, 0, $m, 1)) ?></option>
                <?php endfor; ?>
            </select>
            <label for="yearSelect">Select Year:</label>
            <select id="yearSelect">
                <option value="">Select Year</option>
                <?php for ($y = 2025; $y >= 2023; $y--): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="hideModal()">Cancel</button>
                <button class="btn-download" onclick="handleDownload()">Download</button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewInvoice(invoiceId) {
        alert('Viewing invoice: ' + invoiceId);
    }
    function showModal() {
        document.getElementById('downloadModal').style.display = 'flex';
    }
    function hideModal() {
        document.getElementById('downloadModal').style.display = 'none';
    }
    function handleDownload() {
        const month = document.getElementById('monthSelect').value;
        const year = document.getElementById('yearSelect').value;
        if (!month || !year) {
            alert('Please select both month and year');
            return;
        }
        hideModal();
        window.location.href = `download_logs.php?month=${month}&year=${year}`;
    }
</script>
</body>
</html>