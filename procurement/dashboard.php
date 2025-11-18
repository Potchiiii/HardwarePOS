<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'procurement') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Procurement | Hardware Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            display: flex;
        }

        .content {
            margin-left: 220px;
            padding: 30px;
            flex: 1;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 28px;
            color: #333;
        }

        .add-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 18px;
            background-color: #2ecc71;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }
        .add-btn:hover {
            background-color: #27ae60;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #3498db;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .badge.pending {
            background-color: #f39c12;
        }

        .badge.checked {
            background-color: #3498db;
        }

        .badge.received {
            background-color: #2ecc71;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 3px;
            font-size: 12px;
            color: white;
            transition: opacity 0.2s;
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        .action-btn.edit {
            background-color: #3498db;
        }

        .action-btn.delete {
            background-color: #e74c3c;
        }

        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal {
            background: #ffffff;
            padding: 25px 35px;
            border-radius: 12px;
            width: 500px;
            max-width: 95%;
            max-height: 90vh;
            box-shadow: 0 5px 20px rgba(0,0,0,0.25);
            overflow-y: auto;
            position: relative;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 22px;
            color: #343a40;
        }

        .close-btn {
            background: transparent;
            border: none;
            font-size: 28px;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
        }

        .modal-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .modal label {
            margin-bottom: 6px;
            font-size: 14px;
            color: #495057;
            font-weight: 500;
        }

        .modal input, .modal select, .modal textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 13px;
            box-sizing: border-box;
            font-family: inherit;
        }

        .modal input:focus, .modal select:focus, .modal textarea:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .modal .actions {
            margin-top: 25px;
            text-align: right;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            grid-column: 1 / -1;
        }

        .modal .actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .modal .actions .save-btn {
            background-color: #28a745;
            color: white;
            margin-left: 10px;
        }

        .modal .actions .save-btn:hover {
            background-color: #218838;
        }

        .modal .actions .cancel-btn {
            background-color: #f8f9fa;
            color: #343a40;
            border: 1px solid #ced4da;
        }

        .modal .actions .cancel-btn:hover {
            background-color: #e2e6ea;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }

        .filter-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .filter-btn:hover {
            border-color: #3498db;
        }
    </style>
</head>
<body>
    <?php include 'includesProc/sidebar.php'; ?>

    <div class="content">
        <h2>Procurement Dashboard</h2>
        
        <div style="margin-bottom: 20px;">
            <button class="add-btn" id="openModalBtn">+ Create New Batch</button>
        </div>

        <div class="filter-controls">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="pending">Pending</button>
            <button class="filter-btn" data-filter="checked">Checked</button>
            <button class="filter-btn" data-filter="received">Received</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Batch ID</th>
                    <th>Item Name</th>
                    <th>Brand</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Checked By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="batchTable">
                <?php
                $stmt = $pdo->query("SELECT b.*, u.username as created_username, u2.username as checked_username 
                                    FROM batches b 
                                    LEFT JOIN users u ON b.created_by = u.id
                                    LEFT JOIN users u2 ON b.checked_by = u2.id
                                    ORDER BY b.created_at DESC");
                
                while ($batch = $stmt->fetch()):
                    $statusClass = strtolower($batch['status']);
                ?>
                <tr data-batch-id="<?= $batch['batch_id'] ?>" data-status="<?= $batch['status'] ?>">
                    <td><strong><?= htmlspecialchars($batch['batch_id']) ?></strong></td>
                    <td><?= htmlspecialchars($batch['item_name']) ?></td>
                    <td><?= htmlspecialchars($batch['brand'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($batch['quantity']) ?></td>
                    <td><span class="badge <?= $statusClass ?>"><?= ucfirst($batch['status']) ?></span></td>
                    <td><?= date('M d, Y', strtotime($batch['created_at'])) ?></td>
                    <td><?= $batch['checked_username'] ?? '-' ?></td>
                    <td>
                        <button class="action-btn edit" onclick="editBatch('<?= $batch['batch_id'] ?>')">Edit</button>
                        <button class="action-btn delete" onclick="deleteBatch('<?= $batch['batch_id'] ?>')">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for creating/editing batch -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Create New Batch</h3>
                <button class="close-btn" onclick="hideModal()">&times;</button>
            </div>
            <form id="batchForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="batchId">Batch ID *</label>
                        <input type="text" id="batchId" name="batch_id" placeholder="e.g., BATCH-2025-001" required>
                    </div>
                    <div class="form-group">
                        <label for="itemId">Item *</label>
                        <select id="itemId" name="item_id" required onchange="onItemSelect()">
                            <option value="">Select item...</option>
                            <?php
                            $items = $pdo->query("SELECT id, name, brand FROM inventory ORDER BY name");
                            while ($item = $items->fetch()):
                            ?>
                            <option value="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['name']) ?>" data-brand="<?= htmlspecialchars($item['brand'] ?? '') ?>">
                                <?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars($item['brand'] ?? 'No Brand') ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="itemName">Item Name</label>
                        <input type="text" id="itemName" name="item_name" readonly>
                    </div>
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" readonly>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" placeholder="Enter quantity" required min="1">
                    </div>
                    <div class="form-group full-width">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" placeholder="Add any notes about this batch..." rows="3"></textarea>
                    </div>

                    <div class="actions">
                        <button type="button" class="cancel-btn" onclick="hideModal()">Cancel</button>
                        <button type="submit" class="save-btn">Create Batch</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modalOverlay = document.getElementById('modalOverlay');

        function showModal() {
            document.getElementById('modalTitle').textContent = 'Create New Batch';
            document.getElementById('batchForm').reset();
            document.getElementById('batchForm').dataset.batchId = '';
            modalOverlay.classList.add('active');
        }

        function hideModal() {
            modalOverlay.classList.remove('active');
        }

        modalOverlay.addEventListener('click', function(e) {
            if (e.target === this) hideModal();
        });

        function onItemSelect() {
            const select = document.getElementById('itemId');
            const option = select.options[select.selectedIndex];
            document.getElementById('itemName').value = option.dataset.name || '';
            document.getElementById('brand').value = option.dataset.brand || '';
        }

        document.getElementById('openModalBtn').addEventListener('click', showModal);

        document.getElementById('batchForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch('processBatch.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (!response.ok) throw new Error('Failed to save batch');
                
                hideModal();
                setTimeout(() => window.location.reload(), 300);
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to create batch');
            }
        });

        async function deleteBatch(batchId) {
            if (!confirm('Are you sure you want to delete this batch?')) return;

            try {
                const response = await fetch('deleteBatch.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ batch_id: batchId })
                });

                if (!response.ok) throw new Error('Delete failed');
                
                document.querySelector(`tr[data-batch-id="${batchId}"]`).remove();
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete batch');
            }
        }

        async function editBatch(batchId) {
            try {
                const response = await fetch(`getBatch.php?batch_id=${encodeURIComponent(batchId)}`);
                if (!response.ok) throw new Error('Failed to fetch batch');

                const batch = await response.json();
                document.getElementById('modalTitle').textContent = 'Edit Batch';
                document.getElementById('batchId').value = batch.batch_id;
                document.getElementById('batchId').readOnly = true;
                document.getElementById('itemId').value = batch.item_id;
                document.getElementById('itemName').value = batch.item_name;
                document.getElementById('brand').value = batch.brand;
                document.getElementById('quantity').value = batch.quantity;
                document.getElementById('notes').value = batch.notes || '';
                document.getElementById('batchForm').dataset.batchId = batch.batch_id;
                
                showModal();
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load batch');
            }
        }

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const rows = document.querySelectorAll('#batchTable tr');
                
                rows.forEach(row => {
                    const status = row.dataset.status;
                    if (filter === 'all' || status === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
