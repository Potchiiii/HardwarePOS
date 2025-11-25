<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'cashier') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale | Hardware Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../assets/third_party/sweetalert.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: #f5f5f5;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .staff-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .logout-btn {
            padding: 0.5rem 1rem;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 1rem;
            min-height: calc(100vh - 64px);
        }

        .products-panel {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 96px);
        }

        .search-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-bar {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-bar:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            padding: 0.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .product-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-height: 240px;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-color: #3498db;
        }

        .product-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 6px;
        }

        .item-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            line-height: 1.2;
        }

        .item-brand {
            font-size: 0.8rem;
            color: #666;
            font-style: italic;
        }

        .item-price {
            color: #2980b9;
            font-weight: 600;
            font-size: 0.95rem;
            word-break: break-word;
            overflow-wrap: break-word;
            line-height: 1.2;
        }

        .item-stock {
            font-size: 0.8rem;
            color: #666;
            margin-top: auto;
        }
        
        .wholesale-price {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.3rem;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .cart-panel {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            position: sticky;
            top: 80px;
            height: calc(100vh - 96px);
            display: flex;
            flex-direction: column;
        }

        .cart-panel h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            margin: 1rem 0;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
            gap: 1rem;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 1;
            min-width: 0;
        }

        .item-details .item-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2px;
        }

        .item-details .item-brand {
            font-size: 0.8rem;
            color: #666;
            font-style: italic;
            margin-bottom: 4px;
        }

        .item-subtotal {
            font-size: 0.8rem;
            color: #2980b9;
            font-weight: 500;
        }

        .controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 1;
            min-width: 150px;
        }

        .quantity-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            background: #f1f1f1;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .quantity-btn:hover {
            background: #e0e0e0;
        }

        .quantity-input {
            width: 100%;
            min-width: 80px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 6px 4px;
            font-size: 0.9rem;
            flex: 1;
        }

        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            opacity: 1;
        }

        .remove-btn {
            background: transparent;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 1rem;
            padding: 0.25rem;
            transition: color 0.2s;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-btn:hover {
            color: #c0392b;
            background: rgba(231, 76, 60, 0.1);
            border-radius: 4px;
        }

        .cart-total {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: right;
            margin: 1rem 0;
            color: #2c3e50;
            padding: 1rem 0;
            border-top: 2px solid #eee;
        }

        .checkout-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 6px;
            font-size: 1.1rem;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
            font-weight: 600;
        }

        .checkout-btn:hover {
            background: #219a52;
        }

        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .products-grid::-webkit-scrollbar {
            width: 8px;
        }

        .products-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .products-grid::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .products-grid::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .cart-items::-webkit-scrollbar {
            width: 6px;
        }

        .cart-items::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .cart-items::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .mobile-toggle:hover {
            background: #34495e;
            transform: scale(1.1);
        }

        .mobile-toggle.cart-active {
            background: #27ae60;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                justify-content: center;
                text-align: center;
            }

            .header h1 {
                font-size: 1.2rem;
            }

            .staff-info {
                justify-content: center;
                font-size: 0.9rem;
            }

            .content {
                grid-template-columns: 1fr;
                padding: 0.5rem;
                gap: 0;
                position: relative;
            }

            .products-panel {
                height: calc(100vh - 120px);
                padding: 1rem;
                border-radius: 8px 8px 0 0;
                margin-bottom: 0;
            }

            .cart-panel {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 999;
                height: 100vh;
                border-radius: 0;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                padding: 2rem 1rem 1rem;
            }

            .cart-panel.active {
                transform: translateX(0);
            }

            .cart-panel h2 {
                position: relative;
                margin-bottom: 2rem;
            }

            .cart-panel h2::before {
                content: '\f00d';
                font-family: 'Font Awesome 6 Free';
                font-weight: 900;
                position: absolute;
                left: -2rem;
                top: 0;
                cursor: pointer;
                font-size: 1.2rem;
                color: #e74c3c;
                width: 1.5rem;
                height: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 0.8rem;
                padding: 0.25rem;
            }

            .product-card {
                padding: 0.8rem;
                min-height: 200px;
            }

            .product-image {
                height: 100px;
            }

            .item-name {
                font-size: 0.8rem;
            }

            .item-brand {
                font-size: 0.7rem;
            }

            .item-price {
                font-size: 0.85rem;
            }

            .item-stock {
                font-size: 0.7rem;
            }

            .search-bar {
                padding: 0.8rem 1rem 0.8rem 2.5rem;
                font-size: 1rem;
            }

            .mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .cart-item {
                padding: 1rem 0.5rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
            }

            .item-details {
                width: 100%;
            }

            .controls {
                width: 100%;
                justify-content: space-between;
            }

            .quantity-btn, .remove-btn {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .quantity-input {
                width: 60px;
                padding: 8px 4px;
                font-size: 1rem;
            }

            .cart-total {
                font-size: 1.3rem;
                padding: 1.5rem 0;
            }

            .checkout-btn {
                padding: 1.2rem;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 0.8rem;
            }

            .header h1 {
                font-size: 1.1rem;
            }

            .staff-info {
                font-size: 0.8rem;
            }

            .logout-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 0.6rem;
            }

            .product-card {
                padding: 0.6rem;
                min-height: 180px;
            }

            .product-image {
                height: 80px;
            }

            .search-bar {
                padding: 0.7rem 1rem 0.7rem 2.3rem;
                font-size: 0.9rem;
            }

            .search-icon {
                left: 0.8rem;
                font-size: 0.9rem;
            }

            .mobile-toggle {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
                bottom: 15px;
                right: 15px;
            }

            .cart-panel {
                padding: 1.5rem 0.8rem 1rem;
            }

            .cart-panel h2 {
                font-size: 1.2rem;
                margin-bottom: 1.5rem;
            }

            .cart-panel h2::before {
                left: -1.5rem;
                font-size: 1.1rem;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .product-card {
            animation: fadeIn 0.3s ease-out;
        }

        /* Touch optimizations */
        @media (hover: none) and (pointer: coarse) {
            .product-card:hover {
                transform: none;
            }

            .product-card:active {
                transform: scale(0.98);
                background: #e9ecef;
            }

            .quantity-btn:hover,
            .remove-btn:hover {
                background: initial;
                color: initial;
            }

            .quantity-btn:active {
                background: #d0d0d0;
            }

            .remove-btn:active {
                background: rgba(231, 76, 60, 0.2);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>HardwarePOS</h1>
            <div class="staff-info">
                <span>Cashier: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </header>

    <main class="content">
        <div class="products-panel">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-bar" id="searchInput" placeholder="Search products by name, brand, or category...">
            </div>
            <div class="products-grid">
                <?php
                $stmt = $pdo->query("SELECT * FROM inventory WHERE quantity > 0 ORDER BY name");
                while ($product = $stmt->fetch()) {
                    $stockClass = $product['quantity'] <= $product['low_threshold'] ? 'text-warning' : '';
                ?>
                <div class="product-card" data-product-id="<?= $product['id'] ?>" data-product="<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="../<?php echo htmlspecialchars($product['image_url'] ?? 'assets/product_images/no-image.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                    <div class="item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="item-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                    <div class="item-price">
                        <?php 
                            $price = 0;
                            $priceType = '';
                            if ($product['per_unit']) {
                                $price = $product['per_unit'];
                                $priceType = '(Per Unit)';
                            } elseif ($product['per_length']) {
                                $price = $product['per_length'];
                                $priceType = '(Per Length)';
                            } elseif ($product['per_kilo']) {
                                $price = $product['per_kilo'];
                                $priceType = '(Per Kilo)';
                            } elseif ($product['whole_sale']) {
                                $price = $product['whole_sale'];
                                $priceType = '(Wholesale)';
                            }
                            echo '₱' . number_format($price, 2) . ' ' . $priceType;
                        ?>
                    </div>
                    <div class="wholesale-price">
                        <?php 
                            if ($product['whole_sale']) {
                                echo 'WS: ₱' . number_format($product['whole_sale'], 2);
                                // Show deduction info
                                if ($product['wholesale_deduction_units']) {
                                    echo ' (' . $product['wholesale_deduction_units'] . ' units)';
                                } elseif ($product['wholesale_deduction_meters']) {
                                    echo ' (' . $product['wholesale_deduction_meters'] . ' m)';
                                } elseif ($product['wholesale_deduction_kilos']) {
                                    echo ' (' . $product['wholesale_deduction_kilos'] . ' kg)';
                                }
                            }
                        ?>
                    </div>
                    <div class="item-stock <?php echo $stockClass; ?>">Stock: <?php echo htmlspecialchars($product['quantity']); ?></div>
                </div>
                <?php } ?>
            </div>
        </div>

        <div class="cart-panel" id="cartPanel">
            <h2>Current Transaction</h2>
            <div id="cartItems" class="cart-items"></div>
            <div class="cart-total">Total: ₱<span id="cartTotal">0.00</span></div>
            <button id="checkoutBtn" class="checkout-btn" onclick="checkout()" disabled>Record Sale</button>
        </div>
    </main>

    <button class="mobile-toggle" id="mobileToggle" onclick="toggleCart()">
        <i class="fas fa-book"></i>
    </button>

    <script>
        let cart = [];
        let total = 0;

        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', function() {
                const productData = JSON.parse(this.getAttribute('data-product'));
                showAddToCartConfirm(productData);
            });
        });

        function showAddToCartConfirm(product) {
            const hasRetailPrice = (product.per_unit != null) || (product.per_length != null) || (product.per_kilo != null);
            const hasWholesalePrice = (product.whole_sale != null);

            let buttons = { cancel: 'Cancel' };
            if (hasRetailPrice) buttons['retail'] = { text: 'Retail Price', value: 'retail' };
            if (hasWholesalePrice) buttons['wholesale'] = { text: 'Wholesale Price', value: 'wholesale' };

            if (!hasRetailPrice && !hasWholesalePrice) {
                swal('Error', 'No price set for this product', 'error');
                return;
            }

            swal({
                title: 'Select Price Type',
                text: product.name + ' - ' + product.brand,
                buttons: buttons
            }).then((value) => {
                if (value === 'retail' || value === 'wholesale') {
                    const toAdd = Object.assign({}, product, { priceType: value });
                    addToCart(toAdd);
                }
            });
        }

        function addToCart(product) {
            // Determine the unit type and price
            let unitType = 'unit';
            let unitTypeLabel = '';
            let quantityLabel = '';
            let price = 0;
            
            if (product.priceType === 'wholesale') {
                price = product.whole_sale;
                unitType = 'unit';
            } else {
                // Detect available retail price type
                if (product.per_unit != null) {
                    price = product.per_unit;
                    unitType = 'unit';
                } else if (product.per_length != null) {
                    price = product.per_length;
                    unitType = 'length';
                    unitTypeLabel = 'meters';
                    quantityLabel = ' m';
                } else if (product.per_kilo != null) {
                    price = product.per_kilo;
                    unitType = 'kilo';
                    unitTypeLabel = 'kilos';
                    quantityLabel = ' kg';
                } else {
                    swal('Error', 'No price available for this product', 'error');
                    return;
                }
            }
            
            if (unitType === 'unit') {
                // Per unit: ask for quantity (SweetAlert v1 input)
                swal({
                    title: `Enter quantity`,
                    content: {
                        element: 'input',
                        attributes: {
                            type: 'number',
                            placeholder: 'e.g., 1, 2, 5',
                            step: '1',
                            min: '1',
                            max: '999'
                        }
                    },
                    buttons: {
                        cancel: 'Cancel',
                        confirm: 'Add to Cart'
                    }
                }).then((quantity) => {
                    if (quantity === null || quantity === '') return;
                    let qty = parseInt(quantity);
                    if (!isNaN(qty) && qty > 0 && qty <= 999) {
                        const existing = cart.find(i => i.id === product.id && i.unitType === 'unit');
                        if (existing) {
                            existing.quantity += qty;
                        } else {
                            cart.push({
                                id: product.id,
                                name: product.name,
                                brand: product.brand,
                                price: parseFloat(price),
                                quantity: qty,
                                maxStock: product.quantity,
                                unitType: 'unit',
                                quantityLabel: '',
                                priceType: product.priceType
                            });
                        }
                        updateCart();
                        updateMobileToggle();
                    } else {
                        swal('Invalid', 'Please enter a number between 1 and 999', 'error');
                    }
                });
            } else {
                // Per length/kilo: ask for length/weight (one decimal place, SweetAlert v1 input)
                const inputLabel = unitType === 'length' ? 'length in meters' : 'weight in kilos';
                swal({
                    title: `Enter ${inputLabel}`,
                    content: {
                        element: 'input',
                        attributes: {
                            type: 'number',
                            placeholder: 'e.g., 1 or 1.5',
                            step: '0.1',
                            min: '0.1',
                            max: '999.9'
                        }
                    },
                    buttons: {
                        cancel: 'Cancel',
                        confirm: 'Add to Cart'
                    }
                }).then((quantity) => {
                    if (quantity === null || quantity === '') return;
                    let qty = parseFloat(quantity);
                    qty = Math.round(qty * 10) / 10; // one decimal place
                    if (!isNaN(qty) && qty > 0 && qty <= 999.9) {
                        const existing = cart.find(i => i.id === product.id && i.unitType === unitType);
                        if (existing) {
                            existing.quantity = Math.round((existing.quantity + qty) * 10) / 10;
                        } else {
                            cart.push({
                                id: product.id,
                                name: product.name,
                                brand: product.brand,
                                price: parseFloat(price),
                                quantity: qty,
                                maxStock: product.quantity,
                                unitType: unitType,
                                quantityLabel: quantityLabel,
                                priceType: product.priceType
                            });
                        }
                        updateCart();
                        updateMobileToggle();
                    } else {
                        swal('Invalid', 'Please enter a number between 0.1 and 999.9', 'error');
                    }
                });
            }
        }

        function updateQuantity(id, change) {
            const item = cart.find(i => i.id === id);
            if (!item) return;
            const delta = parseFloat(change);
            let newQ = item.quantity + delta;
            // Enforce one decimal for length/kilo
            if (item.quantityLabel) newQ = Math.round(newQ * 10) / 10;
            const minQ = item.quantityLabel ? 0.1 : 1;
            if (newQ >= minQ && newQ <= item.maxStock) {
                item.quantity = newQ;
            } else if (newQ < minQ) {
                removeItem(id);
                return;
            }
            updateCart();
            updateMobileToggle();
        }

        function setQuantity(id, value) {
            const item = cart.find(i => i.id === id);
            if (!item) return;

            let qty = parseFloat(value);
            if (item.quantityLabel) {
                // one decimal place for length/kilo
                qty = Math.round(qty * 10) / 10;
            } else {
                qty = Math.round(qty);
            }

            const minQ = item.quantityLabel ? 0.1 : 1;
            if (qty > item.maxStock) {
                item.quantity = item.maxStock;
                swal({
                    title: "Stock Limit Reached",
                    text: `Only ${item.maxStock} in stock for ${item.brand} - ${item.name}.`,
                    icon: "warning",
                    buttons: false,
                    timer: 5000
                });
            } else if (qty < minQ) {
                removeItem(id);
                return;
            } else {
                item.quantity = qty;
            }

            updateCart();
            updateMobileToggle();
        }

        function removeItem(id) {
            cart = cart.filter(i => i.id !== id);
            updateCart();
            updateMobileToggle();
        }

        function updateCart() {
            const cartDiv = document.getElementById('cartItems');
            const checkoutBtn = document.getElementById('checkoutBtn');
            cartDiv.innerHTML = '';
            total = 0;

            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                let quantityDisplay;
                let quantityStep;
                if (item.quantityLabel) {
                    quantityDisplay = `${item.quantity.toFixed(1)}${item.quantityLabel}`;
                    quantityStep = '0.1';
                } else {
                    quantityDisplay = item.quantity;
                    quantityStep = '1';
                }
                cartDiv.innerHTML += `
                    <div class="cart-item">
                        <div class="item-details">
                            <div class="item-name">${item.name}</div>
                            <div class="item-brand">${item.brand}</div>
                            <div class="item-subtotal">₱${item.price.toLocaleString('en-PH', { minimumFractionDigits: 2 })} × ${quantityDisplay} = ₱${subtotal.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
                        </div>
                        <div class="controls">
                            <input type="number" class="quantity-input" value="${item.quantity}" min="${item.quantityLabel ? '0.1' : '1'}" step="${quantityStep}" onchange="setQuantity(${item.id}, this.value)" style="flex: 1;">
                            <button class="remove-btn" onclick="removeItem(${item.id})"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                `;
            });

            document.getElementById('cartTotal').textContent = total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            checkoutBtn.disabled = cart.length === 0;
        }

        function toggleCart() {
            const cartPanel = document.getElementById('cartPanel');
            const mobileToggle = document.getElementById('mobileToggle');
            
            cartPanel.classList.toggle('active');
            mobileToggle.classList.toggle('cart-active');
        }

        function updateMobileToggle() {
            const mobileToggle = document.getElementById('mobileToggle');
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            if (totalItems > 0) {
                mobileToggle.innerHTML = `<i class="fas fa-book"></i><span style="position: absolute; top: -5px; right: -5px; background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center;">${totalItems > 99 ? '99+' : totalItems}</span>`;
            } else {
                mobileToggle.innerHTML = '<i class="fas fa-book"></i>';
            }
        }

        // Close cart when clicking close button on mobile
        document.addEventListener('click', function(e) {
            if (e.target.closest('.cart-panel h2::before') || (e.target.closest('h2') && window.innerWidth <= 768)) {
                const cartPanel = document.getElementById('cartPanel');
                const mobileToggle = document.getElementById('mobileToggle');
                cartPanel.classList.remove('active');
                mobileToggle.classList.remove('cart-active');
            }
        });

        // Add event listener for the close button on mobile
        document.querySelector('.cart-panel h2').addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const rect = this.getBoundingClientRect();
                if (e.clientX < rect.left + 30) { // Click on close area
                    toggleCart();
                }
            }
        });

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(pc => {
                const name = pc.querySelector('.item-name').textContent.toLowerCase();
                const brand = pc.querySelector('.item-brand').textContent.toLowerCase();
                const visible = name.includes(term) || brand.includes(term);
                pc.style.display = visible ? 'block' : 'none';
            });
        });

        function logout() {
            swal({
                title: "Logout Confirmation",
                text: "Are you sure you want to logout?",
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Cancel",
                        visible: true,
                        className: "",
                        closeModal: true
                    },
                    confirm: {
                        text: "Logout",
                        className: "swal-button--danger",
                        closeModal: true
                    }
                },
                dangerMode: true
            }).then((willLogout) => {
                if (willLogout) {
                    window.location.href = '../includes/logout.php';
                }
            });
        }

        function checkout() {
            if (!cart.length) {
                return swal({
                    title: "Cart is empty",
                    text: "Please add items before checking out.",
                    icon: "info",
                    timer: 2000,
                    buttons: false
                });
            }

            // Create a custom form to input amount
            const wrapper = document.createElement("div");
            wrapper.innerHTML = `
                <p><strong>Total Due:</strong> ₱${total.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
                <input type="number" id="amountInput" min="${total}" step="0.01" placeholder="Enter amount received" style="margin-top: 10px; width: 100%; padding: 8px; font-size: 1rem; border: 1px solid #ddd; border-radius: 4px;" autofocus>
                <p id="changeOutput" style="margin-top: 10px; font-weight: bold;"></p>
                `;

            swal({
                title: "Complete Sale",
                content: wrapper,
                buttons: {
                    cancel: "Cancel",
                    confirm: {
                        text: "Record Sale",
                        closeModal: false
                    }
                }
            }).then(confirmed => {
                if (!confirmed) return;

                const input = document.getElementById("amountInput");
                const received = parseFloat(input.value);
                if (!received || isNaN(received)) {
                    return swal("Invalid Input", "Please enter a valid amount.", "error");
                }
                if (received < total) {
                    return swal("Insufficient", "Amount received is less than total due.", "error");
                }

                const change = received - total;

                // Check if cart has wholesale items
                const hasWholesale = cart.some(item => item.priceType === 'wholesale');
                const endpoint = hasWholesale ? 'process_wholesale_checkout.php' : 'process_checkout.php';

                // Proceed to process the sale
                fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart, received })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        swal({
                            title: "Sale Complete",
                            text: `Change: ₱${change.toFixed(2)}\nSale ID: ${data.sale_id}`,
                            icon: "success",
                            timer: 4000,
                            buttons: false
                        }).then(() => {
                            cart = [];
                            updateCart();
                            updateMobileToggle();
                            // Close mobile cart if open
                            const cartPanel = document.getElementById('cartPanel');
                            const mobileToggle = document.getElementById('mobileToggle');
                            cartPanel.classList.remove('active');
                            mobileToggle.classList.remove('cart-active');
                            window.location.reload();
                        });
                    } else {
                        swal("Error", data.error || "Sale could not be completed", "error");
                    }
                })
                .catch(err => {
                    console.error(err);
                    swal("Error", "Something went wrong while processing the sale.", "error");
                });
            });

            // Add real-time change calculation
            setTimeout(() => {
                const input = document.getElementById("amountInput");
                const output = document.getElementById("changeOutput");
                input.addEventListener("input", () => {
                    const val = parseFloat(input.value);
                    if (!isNaN(val)) {
                        const change = val - total;
                        if (change < 0) {
                            output.textContent = `❌ Insufficient amount`;
                            output.style.color = "red";
                        } else {
                            output.textContent = `✅ Change: ₱${change.toFixed(2)}`;
                            output.style.color = "green";
                        }
                    } else {
                        output.textContent = ``;
                    }
                });
            }, 100);
        }

        // Initialize mobile toggle on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateMobileToggle();
        });
    </script>
</body>
</html>