<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'procurement') {
    header("Location: ../index.php");
    exit();
}
require_once '../db.php';

// Ensure suppliers table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `suppliers` (
        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `contact_person` VARCHAR(100),
        `phone` VARCHAR(50),
        `email` VARCHAR(100),
        `address` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
    // ignore
}

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suppliers | Procurement</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body{font-family: 'Segoe UI',sans-serif; background:#f4f4f4; display:flex}
        .content{margin-left:260px;padding:30px;flex:1}
        table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden}
        thead th{background:#34495e;color:#fff;padding:12px;text-align:left}
        tbody td{padding:12px;border-bottom:1px solid #eee}
        .btn{padding:8px 12px;border-radius:6px;border:none;cursor:pointer}
        .btn-add{background:#2ecc71;color:#fff}
        .form-row{display:flex;gap:8px;margin-bottom:12px}
        .form-row input,textarea{padding:8px;border:1px solid #ddd;border-radius:4px}
    </style>
</head>
<body>
    <?php include 'includesProc/sidebar.php'; ?>
    <div class="content">
        <h2>Suppliers</h2>

        <div style="margin:20px 0;background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05)">
            <form id="addSupplierForm">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Supplier name" required style="flex:2">
                    <input type="text" name="contact_person" placeholder="Contact person" style="flex:1">
                </div>
                <div class="form-row">
                    <input type="text" name="phone" placeholder="Phone" style="flex:1">
                    <input type="email" name="email" placeholder="Email" style="flex:1">
                </div>
                <div class="form-row">
                    <textarea name="address" placeholder="Address" rows="2" style="flex:1"></textarea>
                    <button class="btn btn-add" type="submit">Add Supplier</button>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Address</th><th>Actions</th></tr>
            </thead>
            <tbody id="suppliersTable">
                <?php foreach($suppliers as $s): ?>
                <tr data-id="<?= $s['id'] ?>">
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['contact_person'] ?? '') ?></td>
                    <td><?= htmlspecialchars($s['phone'] ?? '') ?></td>
                    <td><?= htmlspecialchars($s['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($s['address'] ?? '') ?></td>
                    <td>
                        <button onclick="editSupplier(<?= $s['id'] ?>)" class="btn">Edit</button>
                        <button onclick="deleteSupplier(<?= $s['id'] ?>)" class="btn" style="background:#e74c3c;color:#fff">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="../assets/third_party/sweetalert.min.js"></script>
    <script>
        document.getElementById('addSupplierForm').addEventListener('submit', async function(e){
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target));
            try {
                const res = await fetch('create_supplier.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
                const json = await res.json();
                if (json.success) window.location.reload();
                else swal('Error', json.error || 'Failed', 'error');
            } catch(err){ console.error(err); swal('Error', 'Request failed','error'); }
        });

        async function editSupplier(id){
            const row = document.querySelector('tr[data-id="'+id+'"]');
            const name = row.children[0].innerText;
            const contact = row.children[1].innerText;
            const phone = row.children[2].innerText;
            const email = row.children[3].innerText;
            const address = row.children[4].innerText;

            const { value: formValues } = await Swal.fire({
                title: 'Edit Supplier',
                html:
                    '<input id="swal-name" class="swal2-input" placeholder="Name" value="'+name+'">'+
                    '<input id="swal-contact" class="swal2-input" placeholder="Contact" value="'+contact+'">'+
                    '<input id="swal-phone" class="swal2-input" placeholder="Phone" value="'+phone+'">'+
                    '<input id="swal-email" class="swal2-input" placeholder="Email" value="'+email+'">'+
                    '<textarea id="swal-address" class="swal2-textarea" placeholder="Address">'+address+'</textarea>',
                focusConfirm: false,
                preConfirm: () => {
                    return {
                        name: document.getElementById('swal-name').value,
                        contact_person: document.getElementById('swal-contact').value,
                        phone: document.getElementById('swal-phone').value,
                        email: document.getElementById('swal-email').value,
                        address: document.getElementById('swal-address').value
                    }
                }
            });

            if (!formValues) return;

            try {
                const res = await fetch('update_supplier.php', { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(Object.assign({id}, formValues)) });
                const j = await res.json();
                if (j.success) window.location.reload(); else swal('Error', j.error || 'Failed','error');
            } catch(e){ console.error(e); swal('Error','Request failed','error'); }
        }

        async function deleteSupplier(id){
            const confirmed = await Swal.fire({ title: 'Delete supplier?', text: 'This cannot be undone.', icon: 'warning', showCancelButton:true }).then(r=>r.isConfirmed);
            if (!confirmed) return;
            try {
                const res = await fetch('delete_supplier.php', { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
                const j = await res.json();
                if (j.success) window.location.reload(); else swal('Error', j.error || 'Failed','error');
            } catch(e){ console.error(e); swal('Error','Request failed','error'); }
        }
    </script>
</body>
</html>
