<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'procurement') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

// Create notifications table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL,
            `item_name` VARCHAR(100) NOT NULL,
            `quantity` INT(11) NOT NULL,
            `message` TEXT NOT NULL,
            `created_by` INT(11),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `sent_to_staff` TINYINT(1) DEFAULT 0,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        )
    ");
} catch (Exception $e) {
    // Table might already exist
}

// Get all notifications
$notifications = $pdo->query("
    SELECT n.*, u.username as created_by_name 
    FROM notifications n
    LEFT JOIN users u ON n.created_by = u.id
    ORDER BY n.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notify Staff | Procurement</title>
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
            background-color: #2ecc71;
            color: white;
        }

        .btn-primary:hover {
            background-color: #27ae60;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .filter-btn.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }

        .filter-btn:hover {
            border-color: #3498db;
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

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.sent {
            background-color: #2ecc71;
            color: white;
        }

        .badge.pending {
            background-color: #f39c12;
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

        .action-btn.send {
            background-color: #2ecc71;
            color: white;
        }

        .action-btn.send:hover {
            background-color: #27ae60;
        }

        .action-btn.delete {
            background-color: #e74c3c;
            color: white;
        }

        .action-btn.delete:hover {
            background-color: #c0392b;
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

        .stat-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
        <h2>Notify Inventory Staff</h2>

        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-value" style="color: #2ecc71;"><?= count(array_filter($notifications, fn($n) => $n['sent_to_staff'] == 1)) ?></div>
                <div class="stat-label">Notifications Sent</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #f39c12;"><?= count(array_filter($notifications, fn($n) => $n['sent_to_staff'] == 0)) ?></div>
                <div class="stat-label">Pending Notifications</div>
            </div>
        </div>

        <div class="action-bar">
            <div class="filter-controls">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="sent">Sent</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
            </div>
            <button class="btn btn-primary" onclick="openNotificationModal()">
                <i class="fas fa-plus"></i> New Notification
            </button>
        </div>

        <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i class="fas fa-bell-slash"></i>
            <h3>No Notifications</h3>
            <p>Create a notification to alert inventory staff when new stock arrives.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="notificationsTable">
                <?php foreach ($notifications as $notif): ?>
                <tr data-sent="<?= $notif['sent_to_staff'] ?>">
                    <td><strong><?= htmlspecialchars($notif['order_number']) ?></strong></td>
                    <td><?= htmlspecialchars($notif['item_name']) ?></td>
                    <td><?= $notif['quantity'] ?> units</td>
                    <td><?= htmlspecialchars(substr($notif['message'], 0, 40)) ?><?= strlen($notif['message']) > 40 ? '...' : '' ?></td>
                    <td><span class="badge <?= $notif['sent_to_staff'] ? 'sent' : 'pending' ?>"><?= $notif['sent_to_staff'] ? 'Sent' : 'Pending' ?></span></td>
                    <td><?= date('M d, Y H:i', strtotime($notif['created_at'])) ?></td>
                    <td>
                        <div class="action-buttons">
                            <?php if (!$notif['sent_to_staff']): ?>
                            <button class="action-btn send" onclick="sendNotification(<?= $notif['id'] ?>)">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                            <?php endif; ?>
                            <button class="action-btn delete" onclick="deleteNotification(<?= $notif['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Modal for creating notification -->
    <div id="notificationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 8px; width: 450px; max-width: 90%;">
            <h3 style="margin-bottom: 20px;">Create Staff Notification</h3>
            <form id="notificationForm">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Order Number *</label>
                    <input type="text" id="notifOrderNumber" name="order_number" placeholder="e.g., ORD-2025-001" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Item Name *</label>
                    <input type="text" id="notifItemName" name="item_name" placeholder="Item name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Quantity *</label>
                    <input type="number" id="notifQuantity" name="quantity" min="1" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Message *</label>
                    <textarea id="notifMessage" name="message" placeholder="Notification message for staff..." rows="4" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeNotificationModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 10px 20px; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">Create</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/third_party/sweetalert.min.js"></script>
    <script>
        function openNotificationModal() {
            document.getElementById('notificationForm').reset();
            document.getElementById('notificationModal').style.display = 'flex';
        }

        function closeNotificationModal() {
            document.getElementById('notificationModal').style.display = 'none';
        }

        document.getElementById('notificationForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch('create_notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (!response.ok) throw new Error('Failed to create notification');

                closeNotificationModal();
                swal('Success', 'Notification created!', 'success').then(() => {
                    window.location.reload();
                });
            } catch (error) {
                console.error('Error:', error);
                swal('Error', 'Failed to create notification', 'error');
            }
        });

        async function sendNotification(notificationId) {
            try {
                const response = await fetch('send_notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: notificationId })
                });

                if (!response.ok) throw new Error('Failed to send notification');

                swal('Success', 'Notification sent to staff!', 'success').then(() => {
                    window.location.reload();
                });
            } catch (error) {
                console.error('Error:', error);
                swal('Error', 'Failed to send notification', 'error');
            }
        }

        async function deleteNotification(notificationId) {
            if (!confirm('Delete this notification?')) return;

            try {
                const response = await fetch('delete_notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: notificationId })
                });

                if (!response.ok) throw new Error('Delete failed');

                swal('Success', 'Notification deleted!', 'success').then(() => {
                    window.location.reload();
                });
            } catch (error) {
                console.error('Error:', error);
                swal('Error', 'Failed to delete notification', 'error');
            }
        }

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                document.querySelectorAll('#notificationsTable tr').forEach(row => {
                    const sent = row.dataset.sent;
                    if (filter === 'all') {
                        row.style.display = '';
                    } else if (filter === 'sent' && sent == 1) {
                        row.style.display = '';
                    } else if (filter === 'pending' && sent == 0) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
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
