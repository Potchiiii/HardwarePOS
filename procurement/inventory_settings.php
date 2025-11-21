<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'procurement') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

// Get all inventory items
$items = $pdo->query("
    SELECT * FROM inventory 
    ORDER BY name ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Settings | Procurement</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            display: flex;
        }

        .content {
            margin-left: 260px;
            padding: 40px;
            flex: 1;
        }

        h2 {
            margin-bottom: 30px;
            font-size: 32px;
            color: #333;
            font-weight: 600;
        }

        .search-box {
            margin-bottom: 25px;
        }

        .search-box input {
            width: 100%;
            max-width: 400px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        thead th {
            background-color: #34495e;
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #ecf0f1;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
            color: #333;
        }

        .threshold-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .threshold-input {
            width: 100px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
        }

        .threshold-input.changed {
            border-color: #f39c12;
            background-color: #fffbf0;
        }

        .btn-save {
            padding: 8px 16px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
        }

        .btn-save:hover {
            background: #27ae60;
        }

        .btn-save:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        .stock-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .stock-status.low {
            background-color: #ffcccc;
            color: #c0392b;
        }

        .stock-status.ok {
            background-color: #d4edda;
            color: #155724;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            display: block;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
        }

        .info-box p {
            margin: 0;
            color: #1565c0;
            font-size: 14px;
        }

        .stat-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
        }

        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <?php include 'includesProc/sidebar.php'; ?>

    <div class="content">
        <h2>Inventory Settings</h2>

        <div class="info-box">
            <p><i class="fas fa-info-circle"></i> Set low stock thresholds for each item. When an item's quantity falls below its threshold, it will appear in the Low Stock Items section.</p>
        </div>

        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-value" style="color: #3498db;"><?= count($items) ?></div>
                <div class="stat-label">Total Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #e74c3c;"><?= count(array_filter($items, fn($i) => $i['quantity'] < $i['low_threshold'])) ?></div>
                <div class="stat-label">Items Below Threshold</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #2ecc71;"><?= count(array_filter($items, fn($i) => $i['quantity'] >= $i['low_threshold'])) ?></div>
                <div class="stat-label">Items In Good Stock</div>
            </div>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search items by name or brand...">
        </div>

        <?php if (empty($items)): ?>
        <div class="empty-state">
            <i class="fas fa-boxes"></i>
            <h3>No Inventory Items</h3>
            <p>No items found in inventory.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Brand</th>
                    <th>Current Stock</th>
                    <th>Low Stock Threshold</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="itemsTable">
                <?php foreach ($items as $item): ?>
                <tr data-name="<?= strtolower($item['name']) ?>" data-brand="<?= strtolower($item['brand'] ?? '') ?>" data-item-id="<?= $item['id'] ?>">
                    <td>
                        <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($item['brand'] ?? '-') ?></td>
                    <td><?= $item['quantity'] ?> units</td>
                    <td>
                        <div class="threshold-input-group">
                            <input type="number" class="threshold-input" value="<?= $item['low_threshold'] ?>" min="1" data-original="<?= $item['low_threshold'] ?>">
                            <span>units</span>
                        </div>
                    </td>
                    <td>
                        <span class="stock-status <?= $item['quantity'] < $item['low_threshold'] ? 'low' : 'ok' ?>">
                            <?= $item['quantity'] < $item['low_threshold'] ? 'Low Stock' : 'Good Stock' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn-save" onclick="saveThreshold(this, <?= $item['id'] ?>)" disabled>Save</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <script src="../assets/third_party/sweetalert.min.js"></script>
    <script>
        // Enable save button when threshold changes
        document.querySelectorAll('.threshold-input').forEach(input => {
            input.addEventListener('input', function() {
                const saveBtn = this.closest('tr').querySelector('.btn-save');
                const isChanged = this.value !== this.dataset.original;
                saveBtn.disabled = !isChanged;
                if (isChanged) {
                    this.classList.add('changed');
                } else {
                    this.classList.remove('changed');
                }
            });
        });

        async function saveThreshold(btn, itemId) {
            const input = btn.closest('tr').querySelector('.threshold-input');
            const newThreshold = input.value;

            try {
                const response = await fetch('update_threshold.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: itemId, low_threshold: newThreshold })
                });

                if (!response.ok) throw new Error('Failed to update threshold');

                input.dataset.original = newThreshold;
                input.classList.remove('changed');
                btn.disabled = true;
                
                swal('Success', 'Threshold updated!', 'success');
            } catch (error) {
                console.error('Error:', error);
                swal('Error', 'Failed to update threshold', 'error');
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('#itemsTable tr').forEach(row => {
                const name = row.dataset.name;
                const brand = row.dataset.brand;
                if (name.includes(searchTerm) || brand.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function logout() {
            swal({
                title: "Logout Confirmation",
                text: "Are you sure you want to logout?",
                icon: "warning",
                buttons: ["Cancel", "Logout"],
                dangerMode: true,
            }).then((willLogout) => {
                if (willLogout) {
                    window.location.href = "../includes/logout.php";
                }
            });
        }
    </script>
</body>
</html>
