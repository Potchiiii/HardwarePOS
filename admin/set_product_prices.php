<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
require_once '../db.php';

$products = $pdo->query("
    SELECT id, name, brand, 
           per_unit, per_length, per_kilo, whole_sale,
           wholesale_deduction_units, wholesale_deduction_meters, wholesale_deduction_kilos
    FROM inventory ORDER BY name ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Set Product Prices</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body{font-family:'Segoe UI',sans-serif;background:#f4f4f4;display:flex}
        .content{margin-left:260px;padding:30px;flex:1}
        table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden}
        thead th{background:#34495e;color:#fff;padding:12px;text-align:left}
        tbody td{padding:12px;border-bottom:1px solid #eee}
        .modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5)}
        .modal.active{display:flex;align-items:center;justify-content:center}
        .modal-content{background:#fff;padding:30px;border-radius:8px;width:400px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}
        .modal-header{font-size:20px;font-weight:bold;margin-bottom:20px}
        .form-group{margin-bottom:15px}
        .form-group label{display:block;margin-bottom:5px;font-weight:500}
        .form-group input, .form-group select{width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px}
        .form-group input:focus, .form-group select:focus{outline:none;border-color:#3498db;box-shadow:0 0 5px rgba(52,152,219,0.3)}
        .modal-buttons{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
        .modal-buttons button{padding:8px 16px;border:none;border-radius:4px;cursor:pointer;font-size:14px}
        .btn-save{background:#3498db;color:#fff}
        .btn-save:hover{background:#2980b9}
        .btn-cancel{background:#95a5a6;color:#fff}
        .btn-cancel:hover{background:#7f8c8d}
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <h2>Set Product Prices</h2>
        <table>
            <thead><tr><th>Product</th><th>Brand</th><th>Retail Price</th><th>Wholesale Price</th><th>Wholesale Deduction</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach($products as $p): ?>
                <tr data-id="<?= $p['id'] ?>">
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['brand'] ?? '') ?></td>
                    <td>
                        <?php 
                            if ($p['per_unit']) {
                                echo '₱' . number_format($p['per_unit'], 2) . ' (Per Unit)';
                            } elseif ($p['per_length']) {
                                echo '₱' . number_format($p['per_length'], 2) . ' (Per Length)';
                            } elseif ($p['per_kilo']) {
                                echo '₱' . number_format($p['per_kilo'], 2) . ' (Per Kilo)';
                            } else {
                                echo '-';
                            }
                        ?>
                    </td>
                    <td><span><?= $p['whole_sale'] ? '₱' . number_format($p['whole_sale'], 2) : '-' ?></span></td>
                    <td>
                        <?php 
                            $deductions = [];
                            if ($p['wholesale_deduction_units']) $deductions[] = $p['wholesale_deduction_units'] . ' units';
                            if ($p['wholesale_deduction_meters']) $deductions[] = $p['wholesale_deduction_meters'] . ' m';
                            if ($p['wholesale_deduction_kilos']) $deductions[] = $p['wholesale_deduction_kilos'] . ' kg';
                            echo count($deductions) > 0 ? implode(', ', $deductions) : '-';
                        ?>
                    </td>
                    <td><button class="edit-price-btn" data-product="<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>" style="padding:6px 10px;background:#3498db;color:#fff;border:none;border-radius:4px;cursor:pointer">Edit Price</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="priceModal" class="modal">
            <div class="modal-content">
                <div class="modal-header" id="modalTitle">Set Product Price</div>
                <form id="priceForm" onsubmit="return false;">
                    <input type="hidden" id="productId">
                    <div class="form-group">
                        <label for="priceCategory">Price Category</label>
                        <select id="priceCategory" required onchange="updatePricingOptions()">
                            <option value="retail">Retail</option>
                            <option value="wholesale">Wholesale</option>
                        </select>
                    </div>
                    <div class="form-group" id="pricingTypeGroup">
                        <label for="pricingType">Pricing Type</label>
                        <select id="pricingType" required>
                            <option value="unit">Per Unit</option>
                            <option value="length">Per Length (meter)</option>
                            <option value="kilo">Per Kilo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="priceInput">Price</label>
                        <input type="number" id="priceInput" placeholder="Enter price" step="0.01" min="0" autofocus>
                    </div>
                    <div id="wholesaleDeductionGroup" style="display:none;border-top:1px solid #ddd;padding-top:15px;margin-top:15px">
                        <div style="font-weight:bold;margin-bottom:10px">Wholesale Deduction Amount</div>
                        <div id="deductionTypeInfo" style="font-size:13px;color:#666;margin-bottom:15px"></div>
                        <div class="form-group" id="deductionUnitsGroup" style="display:none">
                            <label for="deductionUnits">Units to Deduct</label>
                            <input type="number" id="deductionUnits" placeholder="e.g., 10" step="1" min="0">
                        </div>
                        <div class="form-group" id="deductionMetersGroup" style="display:none">
                            <label for="deductionMeters">Meters to Deduct</label>
                            <input type="number" id="deductionMeters" placeholder="e.g., 5.5" step="0.01" min="0">
                        </div>
                        <div class="form-group" id="deductionKilosGroup" style="display:none">
                            <label for="deductionKilos">Kilos to Deduct</label>
                            <input type="number" id="deductionKilos" placeholder="e.g., 2.5" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" class="btn-cancel" onclick="closePriceModal()">Cancel</button>
                        <button type="button" class="btn-save" onclick="savePrice()">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        let currentProduct = null;

        function openPricingDialog(id, name, brand, productData) {
            currentProduct = productData;
            document.getElementById('productId').value = id;
            const brandText = brand ? ` - ${brand}` : '';
            document.getElementById('modalTitle').textContent = `Set Price for ${name}${brandText}`;
            document.getElementById('priceCategory').value = 'retail';
            document.getElementById('pricingType').value = 'unit';
            document.getElementById('priceInput').value = '';
            
            // Clear all deduction fields
            document.getElementById('deductionUnits').value = '';
            document.getElementById('deductionMeters').value = '';
            document.getElementById('deductionKilos').value = '';
            
            updatePricingOptions();
            document.getElementById('priceModal').classList.add('active');
            document.getElementById('priceInput').focus();
        }
        
        function updatePricingOptions() {
            const priceCategory = document.getElementById('priceCategory').value;
            const pricingTypeGroup = document.getElementById('pricingTypeGroup');
            const wholesaleDeductionGroup = document.getElementById('wholesaleDeductionGroup');
            
            if (priceCategory === 'wholesale') {
                pricingTypeGroup.style.display = 'none';
                wholesaleDeductionGroup.style.display = 'block';
                updateWholesaleDeductionFields();
            } else {
                pricingTypeGroup.style.display = 'block';
                wholesaleDeductionGroup.style.display = 'none';
            }
        }
        
        function updateWholesaleDeductionFields() {
            if (!currentProduct) return;
            
            // Determine the product's pricing type
            let pricingType = '';
            let deductionLabel = '';
            
            if (currentProduct.per_unit) {
                pricingType = 'unit';
                deductionLabel = 'Units';
            } else if (currentProduct.per_length) {
                pricingType = 'length';
                deductionLabel = 'Meters';
            } else if (currentProduct.per_kilo) {
                pricingType = 'kilo';
                deductionLabel = 'Kilos';
            }
            
            // Hide all fields first
            document.getElementById('deductionUnitsGroup').style.display = 'none';
            document.getElementById('deductionMetersGroup').style.display = 'none';
            document.getElementById('deductionKilosGroup').style.display = 'none';
            
            // Show only the relevant field
            if (pricingType === 'unit') {
                document.getElementById('deductionUnitsGroup').style.display = 'block';
                document.getElementById('deductionTypeInfo').textContent = 'Set how many units will be deducted in one wholesale transaction.';
                document.getElementById('deductionUnits').value = currentProduct.wholesale_deduction_units || '';
            } else if (pricingType === 'length') {
                document.getElementById('deductionMetersGroup').style.display = 'block';
                document.getElementById('deductionTypeInfo').textContent = 'Set how many meters will be deducted in one wholesale transaction.';
                document.getElementById('deductionMeters').value = currentProduct.wholesale_deduction_meters || '';
            } else if (pricingType === 'kilo') {
                document.getElementById('deductionKilosGroup').style.display = 'block';
                document.getElementById('deductionTypeInfo').textContent = 'Set how many kilos will be deducted in one wholesale transaction.';
                document.getElementById('deductionKilos').value = currentProduct.wholesale_deduction_kilos || '';
            } else {
                document.getElementById('deductionTypeInfo').textContent = 'No pricing type set for this product.';
            }
        }
        
        function closePriceModal() {
            document.getElementById('priceModal').classList.remove('active');
        }
        
        async function savePrice() {
            const id = document.getElementById('productId').value;
            const priceCategory = document.getElementById('priceCategory').value;
            const pricingType = priceCategory === 'wholesale' ? 'wholesale' : document.getElementById('pricingType').value;
            const price = document.getElementById('priceInput').value;
            
            if (!price) {
                swal('Error', 'Please enter a price', 'error');
                return;
            }
            
            const payload = { id, price, pricingType };
            
            // Add wholesale deduction fields if in wholesale mode
            if (priceCategory === 'wholesale') {
                payload.deductionUnits = document.getElementById('deductionUnits').value || null;
                payload.deductionMeters = document.getElementById('deductionMeters').value || null;
                payload.deductionKilos = document.getElementById('deductionKilos').value || null;
            }
            
            try {
                const res = await fetch('update_product_price.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const j = await res.json();
                if (j.success) {
                    swal('Success', 'Price updated successfully', 'success');
                    closePriceModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    swal('Error', j.error || 'Failed to update', 'error');
                }
            } catch(e) {
                console.error(e);
                swal('Error', 'Request failed', 'error');
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('priceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePriceModal();
            }
        });
        
        // Add event listeners to edit price buttons
        document.querySelectorAll('.edit-price-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productData = JSON.parse(this.getAttribute('data-product'));
                openPricingDialog(productData.id, productData.name, productData.brand, productData);
            });
        });
    </script>
</body>
</html>
