<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

// Fetch expired items from purchase_orders
// Only show items that have an expiry date (NOT NULL) and are expired
$stmt = $pdo->query(
    "SELECT 
        po.id as order_id,
        po.item_id,
        po.quantity as quantity_remaining,
        po.expiry_date,
        i.name as item_name,
        i.brand,
        po.order_number
     FROM purchase_orders po
     JOIN inventory i ON po.item_id = i.id
     WHERE po.expiry_date IS NOT NULL 
     AND po.expiry_date < CURDATE()
     AND po.quantity > 0
     ORDER BY po.expiry_date ASC"
);
$expiredItems = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expired Items | Hardware Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="../assets/third_party/sweetalert.min.js"></script>
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
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .stat-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        thead th {
            background: #34495e;
            color: #fff;
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
            background: #f8f9fa;
            transition: background 0.2s;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #ffe5e5;
            color: #c0392b;
        }

        .action-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.2s;
        }

        .action-btn.confirm {
            background: #2ecc71;
            color: #fff;
        }

        .action-btn.confirm:hover {
            background: #27ae60;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .action-btn:disabled {
            background: #ecf0f1;
            color: #7f8c8d;
            cursor: default;
        }

        .confirm-all-btn {
            background: #2ecc71;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.2s;
        }

        .confirm-all-btn:hover {
            background: #27ae60;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .confirm-all-btn:disabled {
            background: #ecf0f1;
            color: #7f8c8d;
            cursor: default;
        }

        .date-expired {
            color: #e74c3c;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'includesStaff/sidebar.php'; ?>
    <div class="content">
        <h2> Expired Items Management</h2>
        <p class="subtitle">Track and confirm removal of expired inventory items</p>

        <?php 
            $totalExpired = count($expiredItems);
            $totalExpiredQty = array_sum(array_column($expiredItems, 'quantity_remaining'));
        ?>

        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-value"><?= $totalExpired ?></div>
                <div class="stat-label">Expired Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($totalExpiredQty, 2) ?></div>
                <div class="stat-label">Total Quantity</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $totalExpired > 0 ? 'Pending' : 'Clear' ?></div>
                <div class="stat-label">Status</div>
            </div>
        </div>

        <?php if (empty($expiredItems)): ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>No Expired Items</h3>
            <p>All inventory items are within their expiry dates.</p>
        </div>
        <?php else: ?>
        <button class="confirm-all-btn" onclick="confirmAllExpired()">
            <i class="fas fa-check"></i> Confirm All Removed
        </button>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Item Name</th>
                    <th>Brand</th>
                    <th>Quantity</th>
                    <th>Expiry Date</th>
                    <th>Days Expired</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($expiredItems as $item): ?>
                    <?php 
                        $expiryDate = new DateTime($item['expiry_date']);
                        $today = new DateTime();
                        $daysExpired = $today->diff($expiryDate)->days;
                    ?>
                    <tr data-order-id="<?= $item['order_id'] ?>" data-item-id="<?= $item['item_id'] ?>" data-quantity="<?= $item['quantity_remaining'] ?>">
                        <td><strong><?= htmlspecialchars($item['order_number']) ?></strong></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['brand'] ?? '-') ?></td>
                        <td><?= number_format($item['quantity_remaining'], 2) ?></td>
                        <td class="date-expired"><?= date('M d, Y', strtotime($item['expiry_date'])) ?></td>
                        <td><span class="badge"><?= $daysExpired ?> days ago</span></td>
                        <td>
                            <button class="action-btn confirm" onclick="confirmItemRemoved(<?= $item['order_id'] ?>, <?= $item['item_id'] ?>, <?= $item['quantity_remaining'] ?>)">
                                <i class="fas fa-check"></i> Confirm Removed
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <script src="../assets/third_party/tailwind.min.js"></script>
    <script>
        async function confirmItemRemoved(orderId, itemId, quantity) {
            if (!confirm(`Confirm removal of ${quantity} units? This will reduce inventory.`)) return;

            try {
                const response = await fetch('staffActions/confirm_expired_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        item_id: itemId,
                        quantity: quantity
                    })
                });

                if (!response.ok) throw new Error('Failed to confirm removal');
                
                const data = await response.json();
                if (data.success) {
                    swal('✓ Success', `Removed ${quantity} units from inventory`, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    swal('Error', data.error || 'Failed to confirm removal', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                swal('Error', error.message || 'Failed to confirm removal', 'error');
            }
        }

        async function confirmAllExpired() {
            const rows = document.querySelectorAll('tbody tr');
            if (rows.length === 0) {
                swal('Info', 'No expired items to confirm', 'info');
                return;
            }

            if (!confirm(`Confirm removal of all ${rows.length} expired items?`)) return;

            try {
                const items = Array.from(rows).map(row => ({
                    order_id: parseInt(row.dataset.orderId),
                    item_id: parseInt(row.dataset.itemId),
                    quantity: parseFloat(row.dataset.quantity)
                }));

                const response = await fetch('staffActions/confirm_all_expired_items.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items })
                });

                if (!response.ok) throw new Error('Failed to confirm all removals');
                
                const data = await response.json();
                if (data.success) {
                    swal('✓ Success', `Confirmed removal of all expired items`, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    swal('Error', data.error || 'Failed to confirm removals', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                swal('Error', error.message || 'Failed to confirm removals', 'error');
            }
        }
    </script>
</body>
</html>
