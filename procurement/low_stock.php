<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'procurement') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

// Get low stock items
$lowStockItems = $pdo->query("
    SELECT * FROM inventory 
    WHERE quantity <= low_threshold 
    ORDER BY quantity ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Low Stock Items | Procurement</title>
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

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }

        .search-box {
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
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

        .low-stock {
            background-color: #ffe5e5 !important;
        }

        .critical {
            background-color: #ffcccc !important;
        }

        .quantity-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background-color: #e74c3c;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .action-btn.order {
            background-color: #2ecc71;
            color: white;
        }

        .action-btn.order:hover {
            background-color: #27ae60;
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

        .threshold-bar {
            background-color: #ecf0f1;
            border-radius: 4px;
            height: 8px;
            overflow: hidden;
        }

        .threshold-fill {
            background-color: #e74c3c;
            height: 100%;
            transition: width 0.3s ease;
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
            color: #e74c3c;
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
        <h2>Low Stock Items</h2>

        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-value"><?= count($lowStockItems) ?></div>
                <div class="stat-label">Items Below Threshold</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($lowStockItems, fn($i) => $i['quantity'] == 0)) ?></div>
                <div class="stat-label">Out of Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($lowStockItems, fn($i) => $i['quantity'] > 0 && $i['quantity'] < 5)) ?></div>
                <div class="stat-label">Critical Stock</div>
            </div>
        </div>

        <div class="action-bar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search items by name or brand...">
            </div>
        </div>

        <?php if (empty($lowStockItems)): ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>All Items In Stock</h3>
            <p>Great! All inventory items are above their low stock threshold.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Brand</th>
                    <th>Current Stock</th>
                    <th>Threshold</th>
                    <th>Stock Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="itemsTable">
                <?php foreach ($lowStockItems as $item): ?>
                <tr class="<?= $item['quantity'] == 0 ? 'critical' : 'low-stock' ?>" data-name="<?= strtolower($item['name']) ?>" data-brand="<?= strtolower($item['brand'] ?? '') ?>" data-item-id="<?= $item['id'] ?>">
                    <td>
                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                    </td>
                    <td><?= htmlspecialchars($item['brand'] ?? '-') ?></td>
                    <td><?= $item['quantity'] ?> units</td>
                    <td><?= $item['low_threshold'] ?></td>
                    <td>
                        <div class="threshold-bar">
                            <div class="threshold-fill" style="width: <?= ($item['quantity'] / $item['low_threshold'] * 100) ?>%"></div>
                        </div>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn order" onclick="createOrder(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name']) ?>', '<?= htmlspecialchars($item['brand'] ?? '') ?>')">
                                <i class="fas fa-plus"></i> Order
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Modal for editing threshold -->
    <div id="thresholdModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <h3 style="margin-bottom: 20px;">Set Low Stock Threshold</h3>
            <p style="margin-bottom: 20px; color: #666;">Item: <strong id="thresholdItemName"></strong></p>
            <form id="thresholdForm">
                <input type="hidden" id="thresholdItemId" name="item_id">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Threshold Quantity *</label>
                    <p style="font-size: 12px; color: #999; margin-bottom: 10px;">Items with stock below this amount will appear in Low Stock alerts</p>
                    <input type="number" id="thresholdValue" name="low_threshold" min="1" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeThresholdModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">Update Threshold</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for creating order -->
    <div id="orderModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <h3 style="margin-bottom: 20px;">Create Purchase Order</h3>
            <form id="orderForm">
                <input type="hidden" id="itemId" name="item_id">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Item Name</label>
                    <input type="text" id="itemName" readonly style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Brand</label>
                    <input type="text" id="itemBrand" readonly style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Order Quantity *</label>
                    <input type="number" id="orderQty" name="quantity" min="1" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Supplier</label>
                    <select id="orderSupplier" name="supplier_id" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <option value="">-- Select supplier --</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Notes</label>
                    <textarea id="orderNotes" name="notes" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeOrderModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 10px 20px; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">Create Order</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/third_party/sweetalert.min.js"></script>
    <script>
        function createOrder(itemId, itemName, brand) {
            document.getElementById('itemId').value = itemId;
            document.getElementById('itemName').value = itemName;
            document.getElementById('itemBrand').value = brand;
            document.getElementById('orderQty').value = '';
            document.getElementById('orderNotes').value = '';
            // load suppliers before showing modal
            populateSuppliers();
            document.getElementById('orderModal').style.display = 'flex';
        }

        function closeOrderModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        // populate suppliers on modal open
        async function populateSuppliers() {
            try {
                const res = await fetch('get_suppliers.php');
                if (!res.ok) return;
                const list = await res.json();
                const sel = document.getElementById('orderSupplier');
                sel.innerHTML = '<option value="">-- Select supplier --</option>';
                list.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name + (s.contact_person ? ' ('+s.contact_person+')' : '');
                    sel.appendChild(opt);
                });
            } catch(err){ console.error('Failed to load suppliers', err); }
        }

        document.getElementById('orderForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            // ensure item_id is present (ordering existing item)
            // form includes `item_id`, `quantity`, `supplier_id`, `notes`

            try {
                const response = await fetch('create_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (!response.ok) throw new Error('Failed to create order');

                closeOrderModal();
                swal('Success', 'Purchase order created!', 'success').then(() => {
                    window.location.reload();
                });
            } catch (error) {
                console.error('Error:', error);
                swal('Error', 'Failed to create order', 'error');
            }
        });

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

        // Threshold management
        function editThreshold(itemId, currentThreshold, itemName) {
            document.getElementById('thresholdItemId').value = itemId;
            document.getElementById('thresholdItemName').textContent = itemName;
            document.getElementById('thresholdValue').value = currentThreshold;
            document.getElementById('thresholdModal').style.display = 'flex';
        }

        function closeThresholdModal() {
            document.getElementById('thresholdModal').style.display = 'none';
        }

        document.getElementById('thresholdForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const itemId = document.getElementById('thresholdItemId').value;
            const newThreshold = document.getElementById('thresholdValue').value;

            try {
                const response = await fetch('update_threshold.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: itemId, low_threshold: newThreshold })
                });

                if (!response.ok) throw new Error('Failed to update threshold');

                closeThresholdModal();
                swal('Success', 'Threshold updated!', 'success').then(() => {
                    window.location.reload();
                });
            } catch (error) {
                console.error('Error:', error);
                swal('Error', 'Failed to update threshold', 'error');
            }
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