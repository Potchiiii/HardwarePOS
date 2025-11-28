<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

// Ensure notifications table has processing-related columns (idempotent, best-effort).
try {
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_by INT(11) NULL");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_at DATETIME NULL");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_notes TEXT NULL");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_added_qty INT(11) NULL");
    $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS processed_defective_qty INT(11) NULL");
} catch (Exception $e) {
    // ignore; if this fails, subsequent queries may fail and will be reported
}

// Fetch notifications sent to staff, resolving brand (and item name fallback) via purchase_orders/inventory
$notifications = $pdo->query(
    "SELECT n.*,
            u.username as created_by_name,
            COALESCE(po.brand, i.brand) AS item_brand,
            COALESCE(NULLIF(n.item_name, ''), po.item_name, i.name) AS display_item_name,
            po.expiry_date,
            COALESCE(n.processed_notes, n.message) AS display_message
     FROM notifications n
     LEFT JOIN users u ON n.created_by = u.id
     LEFT JOIN purchase_orders po ON po.order_number = n.order_number
     LEFT JOIN inventory i ON po.item_id = i.id
     WHERE n.sent_to_staff = 1
     ORDER BY n.created_at DESC"
)->fetchAll();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Notifications</title>
    <link rel="stylesheet" href="../assets/third_party/poppins.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; display: flex; }
        .content { margin-left: 260px; padding: 40px; flex: 1; }
        
        h2 { font-size: 32px; color: #333; margin-bottom: 10px; font-weight: 600; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
        
        .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-value { font-size: 28px; font-weight: 700; color: #2ecc71; }
        .stat-label { font-size: 13px; color: #666; margin-top: 8px; }
        .stat-card.processed .stat-value { color: #3498db; }
        .stat-card.pending .stat-value { color: #f39c12; }
        
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        thead th { background: #34495e; color: #fff; padding: 16px; text-align: left; font-weight: 600; font-size: 14px; }
        tbody td { padding: 14px 16px; border-bottom: 1px solid #ecf0f1; }
        tbody tr:hover { background: #f8f9fa; transition: background 0.2s; }
        tbody tr:last-child td { border-bottom: none; }
        
        .badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge.unprocessed { background: #fff3cd; color: #856404; }
        .badge.processed { background: #d4edda; color: #155724; }
        
        .action-btn { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; transition: all 0.2s; }
        .action-btn.process { background: #2ecc71; color: #fff; }
        .action-btn.process:hover { background: #27ae60; transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
        .action-btn.processed { background: #ecf0f1; color: #7f8c8d; cursor: default; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state i { font-size: 64px; display: block; margin-bottom: 20px; opacity: 0.5; }
        
        /* Modal Styling */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; z-index: 1000; }
        .modal { background: #fff; padding: 30px; width: 500px; max-width: 95%; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.2); }
        .modal h3 { font-size: 22px; color: #34495e; margin-bottom: 24px; font-weight: 600; }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; color: #34495e; margin-bottom: 6px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: 'Segoe UI', sans-serif; transition: all 0.2s; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,0.2); }
        
        .form-info { background: #ecf7ff; padding: 12px; border-radius: 6px; margin-bottom: 16px; border-left: 4px solid #3498db; }
        .form-info-item { margin-bottom: 6px; font-size: 14px; }
        .form-info-item strong { color: #34495e; }
        
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; }
        .btn-cancel { padding: 10px 20px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; }
        .btn-cancel:hover { background: #f8f9fa; border-color: #bbb; }
        .btn-submit { padding: 10px 24px; background: #3498db; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; }
        .btn-submit:hover { background: #2980b9; transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
    </style>
</head>
<body>
    <?php include 'includesStaff/sidebar.php'; ?>
    <div class="content">
        <h2> Inventory Notifications</h2>
        <p class="subtitle">Process received purchase orders and record inventory adjustments</p>
        
        <?php 
            $totalNotifs = count($notifications);
            $processedCount = count(array_filter($notifications, fn($n) => isset($n['processed']) && (int)$n['processed']));
            $pendingCount = $totalNotifs - $processedCount;
        ?>
        
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-value"><?= $totalNotifs ?></div>
                <div class="stat-label">Total Notifications</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-value"><?= $pendingCount ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card processed">
                <div class="stat-value"><?= $processedCount ?></div>
                <div class="stat-label">Processed</div>
            </div>
        </div>
        
        <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No Notifications</h3>
            <p>All orders have been processed or no new orders have arrived.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
				<tr>
					<th>Order #</th>
					<th>Item</th>
					<th>Brand</th>
					<th>Qty</th>
					<th>Message</th>
					<th>Received</th>
					<th>Status</th>
					<th>View</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($notifications as $n): ?>
				<?php $processed = isset($n['processed']) ? (int)$n['processed'] : 0; ?>
				<tr 
					data-id="<?= $n['id'] ?>"
					data-order="<?= htmlspecialchars($n['order_number'] ?? '') ?>"
					data-item="<?= htmlspecialchars($n['display_item_name'] ?? $n['item_name']) ?>"
					data-brand="<?= htmlspecialchars($n['item_brand'] ?? '-') ?>"
					data-qty="<?= (int)$n['quantity'] ?>"
					data-message="<?= htmlspecialchars($n['display_message']) ?>"
					data-received="<?= date('M d, Y', strtotime($n['created_at'])) ?>"
					data-status="<?= $processed ? 'Processed' : 'Pending' ?>"
					data-expiry="<?= $n['expiry_date'] ? date('M d, Y', strtotime($n['expiry_date'])) : '-' ?>"
				>
					<td><strong><?= htmlspecialchars($n['order_number'] ?? '') ?></strong></td>
					<td><?= htmlspecialchars($n['display_item_name'] ?? $n['item_name']) ?></td>
					<td><?= htmlspecialchars($n['item_brand'] ?? '-') ?></td>
					<td><strong><?= $n['quantity'] ?></strong> units</td>
					<td><small><?= htmlspecialchars(substr($n['display_message'],0,50)) ?><?= strlen($n['display_message'])>50? '...':'' ?></small></td>
					<td><?= date('M d, Y', strtotime($n['created_at'])) ?></td>
					<td><span class="badge <?= $processed ? 'processed' : 'unprocessed' ?>"><?= $processed ? '✓ Processed' : '⏳ Pending' ?></span></td>
					<td>
						<button type="button" class="action-btn" onclick="openViewModal(this)"><i class="fas fa-eye"></i> View</button>
					</td>
					<td>
						<?php if (!$processed): ?>
						<button onclick="openProcessModal(<?= $n['id'] ?>)" class="action-btn process"><i class="fas fa-check-circle"></i> Process</button>
						<?php else: ?>
						<button class="action-btn processed" disabled><i class="fas fa-check"></i> Done</button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
    </div>

	<!-- Process Modal -->
	<div id="processModal" class="modal-overlay">
		<div class="modal">
			<h3><i class="fas fa-tasks"></i> Process Notification</h3>
			<form id="processForm">
				<input type="hidden" id="notifId" name="notification_id">
				
				<div class="form-info">
					<div class="form-info-item"><strong>Order:</strong> <span id="procOrder">-</span></div>
					<div class="form-info-item"><strong>Item:</strong> <span id="procItem">-</span></div>
				</div>
				
				<div class="form-group">
					<label><i class="fas fa-box"></i> Received Quantity *</label>
					<input id="procReceived" name="received_qty" type="number" min="0" required>
				</div>
				
				<div class="form-group">
					<label><i class="fas fa-exclamation-triangle"></i> Defective Quantity</label>
					<input id="procDefective" name="defective_qty" type="number" min="0" value="0">
				</div>
				
				<div class="form-group">
					<label><i class="fas fa-calendar"></i> Expiry Date (if applicable)</label>
					<input id="procExpiry" name="expiry_date" type="date">
				</div>
				
				<div class="form-group">
					<label><i class="fas fa-sticky-note"></i> Notes</label>
					<textarea id="procNotes" name="notes" rows="3" placeholder="Add any inspection notes..."></textarea>
				</div>
				
				<div class="modal-actions">
					<button type="button" class="btn-cancel" onclick="closeProcessModal()"><i class="fas fa-times"></i> Cancel</button>
					<button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save & Process</button>
				</div>
			</form>
		</div>
	</div>

	<!-- View Modal -->
	<div id="viewModal" class="modal-overlay">
		<div class="modal">
			<h3><i class="fas fa-eye"></i> Notification Details</h3>
			<div class="form-info">
				<div class="form-info-item"><strong>Order:</strong> <span id="viewOrder">-</span></div>
				<div class="form-info-item"><strong>Item:</strong> <span id="viewItem">-</span></div>
				<div class="form-info-item"><strong>Brand:</strong> <span id="viewBrand">-</span></div>
				<div class="form-info-item"><strong>Quantity:</strong> <span id="viewQty">-</span></div>
				<div class="form-info-item"><strong>Status:</strong> <span id="viewStatus">-</span></div>
				<div class="form-info-item"><strong>Received:</strong> <span id="viewReceived">-</span></div>
				<div class="form-info-item"><strong>Expiry Date:</strong> <span id="viewExpiry">-</span></div>
			</div>
			<div class="form-group">
				<label><i class="fas fa-comment-alt"></i> Message</label>
				<textarea id="viewMessage" rows="4" readonly></textarea>
			</div>
			<div class="modal-actions">
				<button type="button" class="btn-cancel" onclick="closeViewModal()"><i class="fas fa-times"></i> Close</button>
			</div>
		</div>
	</div>

	<script src="../assets/third_party/sweetalert.min.js"></script>
	<script>
		async function openProcessModal(id){
		try{
			const res = await fetch('../procurement/get_notification.php?id=' + id);
			if (!res.ok) throw new Error('Failed');
			const n = await res.json();
			document.getElementById('notifId').value = n.id;
			document.getElementById('procOrder').textContent = n.order_number || n.order_id || '';
			document.getElementById('procItem').textContent = n.item_name;
			document.getElementById('procReceived').value = n.quantity;
			document.getElementById('procDefective').value = 0;
			document.getElementById('procExpiry').value = n.expiry_date || '';
			document.getElementById('procNotes').value = '';
			document.getElementById('processModal').style.display = 'flex';
			// Focus on first input
			document.getElementById('procReceived').focus();
		}catch(e){ console.error(e); swal('Error','Failed to load notification','error'); }
	}
		
		function closeProcessModal(){ 
			document.getElementById('processModal').style.display = 'none';
		}
		
		// Close process modal on overlay click
		document.getElementById('processModal').addEventListener('click', function(e) {
			if (e.target === this) closeProcessModal();
		});
		
		// Close modals on Escape key
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				if (document.getElementById('processModal').style.display === 'flex') {
					closeProcessModal();
				}
				if (document.getElementById('viewModal').style.display === 'flex') {
					closeViewModal();
				}
			}
		});

		document.getElementById('processForm').addEventListener('submit', async function(e){
			e.preventDefault();
			const data = Object.fromEntries(new FormData(e.target));
			try{
				const res = await fetch('process_notification.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
				if (!res.ok) {
					const text = await res.text();
					throw new Error(`HTTP ${res.status}: ${text}`);
				}
				const j = await res.json();
				if (j.success === true) { 
					swal('✓ Success','Notification processed successfully!','success').then(()=>location.reload()); 
				} else {
					swal('Error', j.error || j.message || 'Failed to process notification', 'error');
				}
			}catch(err){ 
				console.error('Processing error:', err); 
				swal('Error', 'Failed to process notification: ' + err.message, 'error'); 
			}
		});

		function openViewModal(button){
			const row = button.closest('tr');
			if (!row) return;
			document.getElementById('viewOrder').textContent = row.dataset.order || '-';
			document.getElementById('viewItem').textContent = row.dataset.item || '-';
			document.getElementById('viewBrand').textContent = row.dataset.brand || '-';
			document.getElementById('viewQty').textContent = row.dataset.qty || '-';
			document.getElementById('viewStatus').textContent = row.dataset.status || '-';
			document.getElementById('viewReceived').textContent = row.dataset.received || '-';
			document.getElementById('viewExpiry').textContent = row.dataset.expiry || '-';
			document.getElementById('viewMessage').value = row.dataset.message || '';
			document.getElementById('viewModal').style.display = 'flex';
		}
		
		function closeViewModal(){
			document.getElementById('viewModal').style.display = 'none';
		}
		
		// Close view modal on overlay click
		document.getElementById('viewModal').addEventListener('click', function(e) {
			if (e.target === this) closeViewModal();
		});
	</script>
</body>
</html>
