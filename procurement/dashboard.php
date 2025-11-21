<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'procurement') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

// Get statistics
$lowStockCount = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity < low_threshold")->fetch()['count'];
$purchaseOrdersCount = $pdo->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'pending'")->fetch()['count'];
$totalInventoryItems = $pdo->query("SELECT COUNT(*) as count FROM inventory")->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Procurement Dashboard | Hardware Store</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3498db;
        }

        .stat-card.danger {
            border-left-color: #e74c3c;
        }

        .stat-card.warning {
            border-left-color: #f39c12;
        }

        .stat-card.success {
            border-left-color: #2ecc71;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 40px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: white;
            border: 2px solid #3498db;
            color: #3498db;
            text-decoration: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }

        .action-btn:hover {
            background: #3498db;
            color: white;
            transform: translateY(-2px);
        }

        .recent-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .recent-section h3 {
            margin-bottom: 20px;
            font-size: 18px;
            color: #333;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'includesProc/sidebar.php'; ?>

    <div class="content">
        <h2>Procurement Dashboard</h2>

        <div class="stats-grid">
            <div class="stat-card danger">
                <div class="stat-label">Low Stock Items</div>
                <div class="stat-value"><?= $lowStockCount ?></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-label">Pending Orders</div>
                <div class="stat-value"><?= $purchaseOrdersCount ?></div>
            </div>
            <div class="stat-card success">
                <div class="stat-label">Total Inventory Items</div>
                <div class="stat-value"><?= $totalInventoryItems ?></div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="low_stock.php" class="action-btn">
                <i class="fas fa-exclamation-triangle"></i>
                View Low Stock
            </a>
            <a href="purchase_orders.php" class="action-btn">
                <i class="fas fa-plus"></i>
                Create Order
            </a>
            <a href="purchase_orders.php" class="action-btn">
                <i class="fas fa-list"></i>
                View Orders
            </a>
            <a href="notify_staff.php" class="action-btn">
                <i class="fas fa-bell"></i>
                Notify Staff
            </a>
        </div>

        <div class="recent-section">
            <h3>Getting Started</h3>
            <div class="empty-state">
                <i class="fas fa-info-circle"></i>
                <p>Use the quick action buttons above to manage procurement:</p>
                <ul style="text-align: left; display: inline-block; margin-top: 15px;">
                    <li style="margin-bottom: 10px;"><strong>View Low Stock:</strong> See items that need ordering</li>
                    <li style="margin-bottom: 10px;"><strong>Create Order:</strong> Create new purchase orders</li>
                    <li style="margin-bottom: 10px;"><strong>View Orders:</strong> Manage order status</li>
                    <li><strong>Notify Staff:</strong> Alert inventory staff when stock arrives</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="../assets/third_party/tailwind.min.js"></script>
    <script src="../assets/third_party/sweetalert.min.js"></script>
    <script>
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
