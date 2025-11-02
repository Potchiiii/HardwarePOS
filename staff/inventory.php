<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory | Hardware Store</title>
    <!-- Add this line for icons -->
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
      padding: 8px 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #3498db;
      color: white;
      font-size: 12px;
    }

    tr:hover {
      background-color: #f5f5f5;
      transition: background-color 0.2s ease;
    }

    .low-stock:hover {
      background-color: #ffd9d9;
    }

    /* Optional: Add subtle shadow on hover for more depth */
    tr:hover td {
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .category {
      font-style: italic;
      color: #888;
    }

    /* Low stock highlighting */
    .low-stock {
      background-color: #ffe5e5;
    }

    .badge {
      display: inline-block;
      padding: 2px 8px;
      background-color: #e74c3c;
      color: white;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
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
        transition: opacity 0.3s ease;
    }

    .modal {
        background: #ffffff;
        padding: 25px 35px;
        border-radius: 12px;
        width: 450px;
        max-width: 95%;
        max-height: 90vh;
        box-shadow: 0 5px 20px rgba(0,0,0,0.25);
        overflow-y: auto;
        margin: auto;
        position: relative;
        transform: translateY(-20px);
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .modal-overlay.active {
        display: flex;
        opacity: 1;
    }

    .modal-overlay.active .modal {
        transform: translateY(0);
        opacity: 1;
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
        font-weight: 600;
    }

    .close-btn {
        background: transparent;
        border: none;
        font-size: 28px;
        color: #6c757d;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        transition: color 0.2s ease;
    }

    .close-btn:hover {
        color: #343a40;
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

    .modal input, .modal select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        font-size: 13px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        box-sizing: border-box;
    }

    .modal input:focus, .modal select:focus {
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
        transition: background-color 0.2s ease, transform 0.1s ease;
    }

    .modal .actions button:active {
        transform: scale(0.98);
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

    /* Stock control styles */
    .stock-control {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .stock-btn {
      width: 28px;
      height: 28px;
      border-radius: 4px;
      border: none;
      color: white;
      cursor: pointer;
      transition: opacity 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .stock-btn:hover {
      opacity: 0.8;
    }

    .stock-btn.decrease {
      background-color: #e74c3c;
    }

    .stock-btn.increase {
      background-color: #2ecc71;
    }

    .quantity {
      min-width: 40px;
      text-align: center;
      font-weight: bold;
    }

    /* Add new style for product images */
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        .image-preview {
            margin-top: 10px;
            max-width: 200px;
        }

        #imagePreview {
            width: 100%;
            height: 150px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: none;
        }

        /* Smooth scrollbar styling for modal */
    .modal::-webkit-scrollbar {
        width: 8px;
    }
    .modal::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    .modal::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    .modal::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .action-btn {
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 2px;
            transition: background-color 0.2s;
        }

        .action-btn.edit {
            background-color: #3498db;
            color: white;
        }

        .action-btn.delete {
            background-color: #e74c3c;
            color: white;
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        .stock-btn {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            border: none;
            color: white;
            cursor: pointer;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .top-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.search-box {
    position: relative;
    width: 300px;
}

.search-box input {
    width: 100%;
    padding: 10px 35px 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-box input:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    outline: none;
}

.search-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
}

.highlight {
    background-color: #fff3cd;
}
    </style>
</head>
<body>
    <?php include 'includesStaff/sidebar.php'; ?>

    <div class="content">
        <h2>Inventory Management</h2>
        
        <div class="top-controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search items...">
                <i class="fas fa-search search-icon"></i>
            </div>
            <button class="add-btn" id="openModalBtn">+ Add New Item</button>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>ID</th>
                    <th>Item Name</th>
                    <th>Brand</th>
                    <th>Quantity</th>
                    <th>Price (₱)</th>
                    <th>Whole Sale (₱)</th>
                    <th>Per Kilo (₱)</th>
                    <th>Per Length (₱)</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM inventory ORDER BY name");

                while ($item = $stmt->fetch()):
                    $isLow = $item['quantity'] < $item['low_threshold'];
                ?>
                <tr class="<?= $isLow ? 'low-stock' : '' ?>" data-id="<?= $item['id'] ?>">
                    <td>
                        <img src="../<?= $item['image_url'] ?? 'assets/product_images/no-image.jpg' ?>" 
                             alt="<?= htmlspecialchars($item['name']) ?>" 
                             class="product-image">
                    </td>
                    <td><?= htmlspecialchars($item['id']) ?></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['brand']) ?></td>
                    <td>
                        <div class="stock-control">
                            <button class="stock-btn decrease" onclick="promptUpdateStock(<?= $item['id'] ?>, 'remove')">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="quantity"><?= htmlspecialchars($item['quantity']) ?></span>
                            <button class="stock-btn increase" onclick="promptUpdateStock(<?= $item['id'] ?>, 'add')">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </td>
                    <td><?= number_format($item['price'], 2) ?></td>
                    <td><?= number_format($item['whole_sale'], 2) ?></td>
                    <td><?= number_format($item['per_kilo'], 2) ?></td>
                    <td><?= number_format($item['per_length'], 2) ?></td>
                    <td class="category"><?= htmlspecialchars($item['category']) ?></td>
                    <td>
                        <?php if ($isLow): ?>
                            <span class="badge">Low Stock</span>
                        <?php else: ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="action-btn edit" onclick="editItem(<?= $item['id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="deleteItem(<?= $item['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal" id="itemModal">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Inventory Item</h3>
                <button class="close-btn" onclick="hideModal()">&times;</button>
            </div>
            <form id="itemForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="itemId" name="itemId">

                    <div class="form-group full-width">
                        <label for="itemName">Item Name</label>
                        <input type="text" id="itemName" name="itemName" required>
                    </div>

                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select category</option>
                            <option value="Tools">Tools</option>
                            <option value="Power Tools">Power Tools</option>
                            <option value="Paint">Paint</option>
                            <option value="Electrical">Electrical</option>
                            <option value="Plumbing">Plumbing</option>
                            <option value="Adhesives">Adhesives</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="price">Price (₱)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="wholesale_price">Wholesale Price (₱)</label>
                        <input type="number" id="wholesale_price" name="wholesale_price" step="0.01" min="0">
                    </div>

                    <div class="form-group">
                        <label for="price_per_kilo">Price per Kilo (₱)</label>
                        <input type="number" id="price_per_kilo" name="price_per_kilo" step="0.01" min="0">
                    </div>

                    <div class="form-group">
                        <label for="price_per_meter">Price per Meter (₱)</label>
                        <input type="number" id="price_per_meter" name="price_per_meter" step="0.01" min="0">
                    </div>

                    <div class="form-group full-width">
                        <label for="lowThreshold">Low Stock Alert Threshold</label>
                        <input type="number" id="lowThreshold" name="lowThreshold" min="0" value="10" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="productImage">Product Image</label>
                        <input type="file" id="productImage" name="productImage" accept="image/*" onchange="previewImage(this)">
                    </div>
                    
                    <div class="image-preview full-width">
                        <img id="imagePreview" src="#" alt="Preview">
                    </div>
                </div>

                <div class="actions">
                    <button type="button" class="cancel-btn" onclick="hideModal()">Cancel</button>
                    <button type="submit" class="save-btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modalOverlay = document.getElementById('modalOverlay');
        const modal = document.getElementById('itemModal');

        function showModal(title = 'Add New Inventory Item') {
            document.getElementById('modalTitle').textContent = title;
            modalOverlay.classList.add('active');
        }

        function hideModal() {
            modalOverlay.classList.remove('active');
            // Delay reset to allow for closing animation
            setTimeout(() => {
                document.getElementById('itemForm').reset();
                document.getElementById('imagePreview').style.display = 'none';
            }, 300);
        }

        // Close modal if overlay is clicked
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                hideModal();
            }
        });

        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function promptUpdateStock(id, action) {
            const verb = action === 'add' ? 'add' : 'remove';
            const amountStr = prompt(`Enter quantity to ${verb}:`, "1");

            if (amountStr === null || amountStr.trim() === "" || !/^\d+$/.test(amountStr)) {
                // User cancelled, entered nothing, or entered a non-integer
                return;
            }

            const amount = parseInt(amountStr, 10);
            if (amount <= 0) {
                alert("Please enter a positive number.");
                return;
            }

            const change = action === 'add' ? amount : -amount;
            await updateStock(id, change);
        }

        async function updateStock(id, change) {
            try {
                const response = await fetch('inventoryActions/update_stock.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, change })
                });
                
                if (!response.ok) throw new Error('Update failed');
                
                const data = await response.json();
                const row = document.querySelector(`tr[data-id="${id}"]`);
                const quantitySpan = row.querySelector('.quantity');
                quantitySpan.textContent = data.quantity;

                // Update low stock status
                if (data.isLow) {
                    row.classList.add('low-stock');
                    row.querySelector('td:nth-last-child(2)').innerHTML = '<span class="badge">Low Stock</span>';
                } else {
                    row.classList.remove('low-stock');
                    row.querySelector('td:nth-last-child(2)').innerHTML = '&mdash;';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update stock');
            }
        }

        document.getElementById('openModalBtn').addEventListener('click', () => showModal());

        document.getElementById('itemForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('inventoryActions/save_item.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) throw new Error('Save failed');
                
                hideModal();
                // Add a small delay to let the modal close before reloading
                setTimeout(() => window.location.reload(), 300);
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to save item');
            }
        });

        async function deleteItem(id) {
            if (!confirm('Are you sure you want to delete this item?')) return;
            
            try {
                const response = await fetch('inventoryActions/delete_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                
                if (!response.ok) throw new Error('Delete failed');
                
                const row = document.querySelector(`tr[data-id="${id}"]`);
                row.remove();
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete item');
            }
        }

        async function editItem(id) {
            try {
                const response = await fetch(`inventoryActions/get_item.php?id=${id}`);
                if (!response.ok) throw new Error('Failed to fetch item');
                
                const item = await response.json();
                document.getElementById('itemId').value = item.id;
                document.getElementById('itemName').value = item.name;
                document.getElementById('brand').value = item.brand;
                document.getElementById('category').value = item.category;
                document.getElementById('quantity').value = item.quantity;
                document.getElementById('price').value = item.price;
                document.getElementById('wholesale_price').value = item.whole_sale;
                document.getElementById('price_per_kilo').value = item.per_kilo;
                document.getElementById('price_per_meter').value = item.per_length;
                document.getElementById('lowThreshold').value = item.low_threshold;
                
                if (item.image_url) {
                    document.getElementById('imagePreview').src = `../${item.image_url}`;
                    document.getElementById('imagePreview').style.display = 'block';
                }
                
                showModal('Edit Item');
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load item details');
            }
        }

        // Search functionality
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const tableRows = document.querySelectorAll('tbody tr');

        tableRows.forEach(row => {
            const itemName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const brand = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
            const category = row.querySelector('td:nth-child(7)').textContent.toLowerCase();
            
            const matches = itemName.includes(searchTerm) || 
                          brand.includes(searchTerm) || 
                          category.includes(searchTerm);

            // Remove previous highlights
            row.querySelectorAll('.highlight').forEach(el => {
                el.classList.remove('highlight');
            });

            if (matches) {
                row.style.display = '';
                if (searchTerm) {
                    // Highlight matching text
                    if (itemName.includes(searchTerm)) {
                        row.querySelector('td:nth-child(3)').classList.add('highlight');
                    }
                    if (brand.includes(searchTerm)) {
                        row.querySelector('td:nth-child(4)').classList.add('highlight');
                    }
                    if (category.includes(searchTerm)) {
                        row.querySelector('td:nth-child(7)').classList.add('highlight');
                    }
                }
            } else {
                row.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>
