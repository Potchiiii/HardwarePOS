<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'procurement') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

// Create table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `purchase_orders` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL UNIQUE,
            `item_id` INT(11) NOT NULL,
            `supplier_id` INT(11) DEFAULT NULL,
            `item_name` VARCHAR(100) NOT NULL,
            `brand` VARCHAR(50),
            `quantity` INT(11) NOT NULL,
            `status` ENUM('pending', 'approved', 'ordered', 'received') NOT NULL DEFAULT 'pending',
            `created_by` INT(11),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `notes` TEXT,
            FOREIGN KEY (`item_id`) REFERENCES `inventory`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        )
    ");
} catch (Exception $e) {
    // Table might already exist
}

// Ensure supplier_id column exists and suppliers table exists
try {
    $col = $pdo->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'supplier_id'")->fetch()['c'];
    if (intval($col) === 0) {
        // try to add column (may fail on some environments)
        $pdo->exec("ALTER TABLE purchase_orders ADD COLUMN supplier_id INT(11) DEFAULT NULL AFTER item_id");
        // attempt to add foreign key if suppliers exists
        $hasSuppliers = $pdo->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers'")->fetch()['c'];
        if (intval($hasSuppliers) > 0) {
            try { $pdo->exec("ALTER TABLE purchase_orders ADD CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL"); } catch (Exception $e) {}
        }
    }
} catch (Exception $e) { }

// Backfill missing item_name / brand from inventory for older orders (one-time, idempotent)
try {
    $pdo->exec("
        UPDATE purchase_orders po
        JOIN inventory i ON po.item_id = i.id
        SET po.item_name = COALESCE(NULLIF(po.item_name, ''), i.name),
            po.brand = COALESCE(NULLIF(po.brand, ''), i.brand)
        WHERE (po.item_name IS NULL OR po.item_name = '')
           OR (po.brand IS NULL OR po.brand = '')
    ");
} catch (Exception $e) {
    // ignore; just a best-effort backfill
}

// Get all purchase orders
$orders = $pdo->query("
    SELECT po.*, u.username as created_by_name, s.name as supplier_name 
    FROM purchase_orders po
    LEFT JOIN users u ON po.created_by = u.id
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    ORDER BY po.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Orders | Procurement</title>
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

        .badge.pending {
            background-color: #f39c12;
            color: white;
        }

        .badge.approved {
            background-color: #3498db;
            color: white;
        }

        .badge.ordered {
            background-color: #9b59b6;
            color: white;
        }

        .badge.received {
            background-color: #2ecc71;
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

        .action-btn.edit {
            background-color: #3498db;
            color: white;
        }

        .action-btn.edit:hover {
            background-color: #2980b9;
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
            grid-template-columns: repeat(4, 1fr);
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
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .search-box input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
        }
    </style>
</head>
<body>
    <?php include 'includesProc/sidebar.php'; ?>

    <div class="content">
        <h2>Purchase Orders</h2>

        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-value" style="color: #f39c12;"><?= count(array_filter($orders, fn($o) => $o['status'] === 'pending')) ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #3498db;"><?= count(array_filter($orders, fn($o) => $o['status'] === 'approved')) ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #9b59b6;"><?= count(array_filter($orders, fn($o) => $o['status'] === 'ordered')) ?></div>
                <div class="stat-label">Ordered</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #2ecc71;"><?= count(array_filter($orders, fn($o) => $o['status'] === 'received')) ?></div>
                <div class="stat-label">Received</div>
            </div>
        </div>

        <div class="action-bar">
            <div class="filter-controls">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="approved">Approved</button>
                <button class="filter-btn" data-filter="ordered">Ordered</button>
                <button class="filter-btn" data-filter="received">Received</button>
            </div>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by order #, item name, or supplier...">
            </div>
            <button class="btn btn-primary" onclick="openNewProductModal()" style="background-color: #2ecc71;">
                <i class="fas fa-plus"></i> Order New Product
            </button>
        </div>

        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No Purchase Orders</h3>
            <p>Navigate to Low Stock Items to create your first purchase order.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Item Name</th>
                    <th>Supplier</th>
                    <th>Brand</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="ordersTable">
                <?php foreach ($orders as $order): ?>
                <tr data-status="<?= $order['status'] ?>">
                    <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                    <td><?= htmlspecialchars($order['item_name']) ?></td>
                    <td><?= htmlspecialchars($order['supplier_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($order['brand'] ?? '-') ?></td>
                    <td><?= $order['quantity'] ?> units</td>
                    <td><span class="badge <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                    <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                    <td><?= htmlspecialchars(substr($order['notes'] ?? '', 0, 30)) ?><?= strlen($order['notes'] ?? '') > 30 ? '...' : '' ?></td>
                    <td>
                        <div class="action-buttons">
                            <?php if ($order['status'] !== 'received'): ?>
                                <button class="action-btn edit" onclick="editOrder(<?= $order['id'] ?>, <?= $order['quantity'] ?>)">
                                    <i class="fas fa-edit"></i> Status
                                </button>
                                <button class="action-btn delete" onclick="deleteOrder(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php else: ?>
                                <span class="badge received">Received</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Modal for editing order status -->
    <div id="statusModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <h3 style="margin-bottom: 20px;">Update Order Status</h3>
            <form id="statusForm">
                <input type="hidden" id="orderId" name="order_id">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">New Status *</label>
                    <select id="orderStatus" name="status" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select status...</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="ordered">Ordered</option>
                        <option value="received">Received</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeStatusModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for ordering new product -->
    <div id="newProductModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 8px; width: 480px; max-width: 90%; max-height: 90vh; display: flex; flex-direction: column;">
            <h3 style="margin-bottom: 20px;">Order New Product</h3>
            <form id="newProductForm" style="flex: 1; overflow-y: auto; padding-right: 10px;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Product Name *</label>
                    <input type="text" id="newProdName" name="product_name" placeholder="e.g., Hammer, Screw..." required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Brand</label>
                    <input type="text" id="newProdBrand" name="brand" placeholder="e.g., Stanley, Makita..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Category</label>
                    <input type="text" id="newProdCategory" name="category" placeholder="e.g., Tools, Hardware..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Order Quantity *</label>
                    <input type="number" id="newProdQty" name="quantity" min="1" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Supplier</label>
                    <select id="newProdSupplier" name="supplier_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">-- Select supplier --</option>
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Low Stock Threshold</label>
                    <input type="number" id="newProdThreshold" name="low_threshold" min="1" value="10" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Product Image</label>
                    <input type="file" id="newProdImage" name="product_image" accept="image/*" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <small style="color: #666; display: block; margin-top: 5px;">JPG, PNG, WEBP (max 5MB)</small>
                    <img id="imagePreviewNew" style="display: none; max-width: 150px; margin-top: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeNewProductModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 10px 20px; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">Create Order</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/third_party/sweetalert.min.js"></script>
    <script>
        // Load suppliers when modals open
        async function loadSuppliers(selectId) {
            try {
                const res = await fetch('get_suppliers.php');
                if (!res.ok) return;
                const list = await res.json();
                const sel = document.getElementById(selectId);
                sel.innerHTML = '<option value="">-- Select supplier --</option>';
                list.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name + (s.contact_person ? ' ('+s.contact_person+')' : '');
                    sel.appendChild(opt);
                });
            } catch(err){ console.error('Failed to load suppliers', err); }
        }

        function editOrder(orderId, orderQty) {
            const orderIdEl = document.getElementById('orderId');
            orderIdEl.value = orderId;
            document.getElementById('statusModal').style.display = 'flex';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        function openNewProductModal() {
            document.getElementById('newProductForm').reset();
            document.getElementById('newProdThreshold').value = '10';
            loadSuppliers('newProdSupplier');
            document.getElementById('newProductModal').style.display = 'flex';
        }

        function closeNewProductModal() {
            document.getElementById('newProductModal').style.display = 'none';
        }

        // Image preview for new product
        document.getElementById('newProdImage')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreviewNew');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Handle new product form submission
        document.getElementById('newProductForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const imageFile = formData.get('product_image');

            // Validate image if provided
            if (imageFile && imageFile.size > 0) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (imageFile.size > maxSize) {
                    swal('Error', 'Image size must be less than 5MB', 'error');
                    return;
                }
            }

            // Create FormData to handle file upload
            const uploadFormData = new FormData();
            uploadFormData.append('product_name', formData.get('product_name'));
            uploadFormData.append('brand', formData.get('brand'));
            uploadFormData.append('category', formData.get('category'));
            uploadFormData.append('quantity', formData.get('quantity'));
            uploadFormData.append('supplier_id', formData.get('supplier_id'));
            uploadFormData.append('low_threshold', formData.get('low_threshold'));
            if (imageFile && imageFile.size > 0) {
                uploadFormData.append('product_image', imageFile);
            }

            try {
                const response = await fetch('create_order.php', {
                    method: 'POST',
                    body: uploadFormData  // Don't set Content-Type header; browser will set it with boundary
                });

                if (!response.ok) throw new Error('Failed to create order');

                const result = await response.json();
                closeNewProductModal();
                swal('Success', 'Purchase order created for new product!', 'success').then(() => {
                    window.location.reload();
                });
            } catch (error) {
                console.error('Error:', error);
                swal('Error', 'Failed to create order', 'error');
            }
        });

        document.getElementById('statusForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch('update_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (!response.ok) throw new Error('Failed to update order');

                closeStatusModal();
                swal('Success', 'Order status updated!', 'success').then(() => {
                    window.location.reload();
                });
            } catch (error) {
                console.error('Error:', error);
                swal('Error', 'Failed to update order', 'error');
            }
        });

        async function deleteOrder(orderId, orderNumber) {
            if (!confirm(`Delete order ${orderNumber}?`)) return;

            try {
                const response = await fetch('delete_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                });

                if (!response.ok) throw new Error('Delete failed');

                swal('Success', 'Order deleted!', 'success').then(() => {
                    window.location.reload();
                });
            } catch (error) {
                console.error('Error:', error);
                swal('Error', 'Failed to delete order', 'error');
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterTable();
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                filterTable();
            });
        });

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const activeFilter = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';

            document.querySelectorAll('#ordersTable tr').forEach(row => {
                const status = row.dataset.status;
                const orderNumber = row.cells[0].textContent.toLowerCase();
                const itemName = row.cells[1].textContent.toLowerCase();
                const supplier = row.cells[2].textContent.toLowerCase();

                // Check status filter
                const statusMatch = activeFilter === 'all' || status === activeFilter;

                // Check search filter
                const searchMatch = searchTerm === '' || 
                                    orderNumber.includes(searchTerm) || 
                                    itemName.includes(searchTerm) || 
                                    supplier.includes(searchTerm);

                row.style.display = (statusMatch && searchMatch) ? '' : 'none';
            });
        }

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
