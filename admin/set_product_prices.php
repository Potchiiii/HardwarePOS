<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
require_once '../db.php';

$products = $pdo->query("SELECT id, name, brand, IFNULL(price, '') as price FROM inventory ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Set Product Prices</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>body{font-family:'Segoe UI',sans-serif;background:#f4f4f4;display:flex} .content{margin-left:260px;padding:30px;flex:1} table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden} thead th{background:#34495e;color:#fff;padding:12px;text-align:left} tbody td{padding:12px;border-bottom:1px solid #eee} input.price{width:120px;padding:6px;border:1px solid #ddd;border-radius:4px}</style>
</head>
<body>
    <?php include '../procurement/includesProc/sidebar.php'; ?>
    <div class="content">
        <h2>Set Product Prices</h2>
        <table>
            <thead><tr><th>Product</th><th>Brand</th><th>Price</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach($products as $p): ?>
                <tr data-id="<?= $p['id'] ?>">
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['brand'] ?? '') ?></td>
                    <td><input class="price" data-id="<?= $p['id'] ?>" value="<?= htmlspecialchars($p['price']) ?>"></td>
                    <td><button onclick="savePrice(<?= $p['id'] ?>)" style="padding:6px 10px;background:#3498db;color:#fff;border:none;border-radius:4px">Save</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="../assets/third_party/sweetalert.min.js"></script>
    <script>
        async function savePrice(id){
            const input = document.querySelector('input.price[data-id="'+id+'"]');
            const price = input.value.trim();
            try{
                const res = await fetch('update_product_price.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id, price}) });
                const j = await res.json();
                if (j.success) swal('Saved', 'Price updated', 'success'); else swal('Error', j.error || 'Failed','error');
            }catch(e){ console.error(e); swal('Error','Request failed','error'); }
        }
    </script>
</body>
</html>
