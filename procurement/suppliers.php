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
        table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
        thead th{background:#34495e;color:#fff;padding:12px;text-align:left;font-weight:600}
        tbody td{padding:12px;border-bottom:1px solid #ecf0f1}
        tbody tr:hover{background:#f8f9fa}
        .btn{padding:8px 12px;border-radius:6px;border:none;cursor:pointer;font-weight:600;transition:all 0.2s}
        .btn-add{background:#2ecc71;color:#fff}
        .btn-add:hover{background:#27ae60;transform:translateY(-2px);box-shadow:0 2px 6px rgba(0,0,0,0.15)}
        .form-row{display:flex;gap:8px;margin-bottom:12px}
        .form-row input,textarea{padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px}
        .form-row input:focus,textarea:focus{outline:none;border-color:#3498db;box-shadow:0 0 0 2px rgba(52,152,219,0.2)}
        
        /* Custom SweetAlert styling */
        .swal-modal-custom {
            border-radius: 12px !important;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2) !important;
            background: #fff !important;
        }
        .swal-modal-custom .swal-title {
            font-size: 24px !important;
            color: #34495e !important;
            margin-bottom: 20px !important;
            font-weight: 700 !important;
        }
        .swal-modal-custom .swal-text {
            color: #555 !important;
        }
        .edit-modal-container {
            text-align: left;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
        }
        .edit-modal-container input,
        .edit-modal-container textarea {
            width: 100% !important;
            padding: 12px !important;
            border: 1px solid #ddd !important;
            border-radius: 6px !important;
            font-size: 14px !important;
            margin-bottom: 14px !important;
            box-sizing: border-box !important;
            font-family: 'Segoe UI', sans-serif !important;
            transition: all 0.2s !important;
        }
        .edit-modal-container input:focus,
        .edit-modal-container textarea:focus {
            outline: none !important;
            border-color: #3498db !important;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.2) !important;
        }
        .edit-modal-label {
            display: block;
            font-weight: 600;
            color: #34495e;
            margin-bottom: 6px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .edit-modal-field {
            margin-bottom: 16px;
        }
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

            // Create form container
            const container = document.createElement('div');
            container.className = 'edit-modal-container';

            // Name field
            const nameField = document.createElement('div');
            nameField.className = 'edit-modal-field';
            const nameLabel = document.createElement('label');
            nameLabel.className = 'edit-modal-label';
            nameLabel.textContent = 'Supplier Name';
            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.id = 'swal-name';
            nameInput.value = name;
            nameField.appendChild(nameLabel);
            nameField.appendChild(nameInput);
            container.appendChild(nameField);

            // Contact field
            const contactField = document.createElement('div');
            contactField.className = 'edit-modal-field';
            const contactLabel = document.createElement('label');
            contactLabel.className = 'edit-modal-label';
            contactLabel.textContent = 'Contact Person';
            const contactInput = document.createElement('input');
            contactInput.type = 'text';
            contactInput.id = 'swal-contact';
            contactInput.value = contact;
            contactField.appendChild(contactLabel);
            contactField.appendChild(contactInput);
            container.appendChild(contactField);

            // Phone field
            const phoneField = document.createElement('div');
            phoneField.className = 'edit-modal-field';
            const phoneLabel = document.createElement('label');
            phoneLabel.className = 'edit-modal-label';
            phoneLabel.textContent = 'Phone Number';
            const phoneInput = document.createElement('input');
            phoneInput.type = 'tel';
            phoneInput.id = 'swal-phone';
            phoneInput.value = phone;
            phoneField.appendChild(phoneLabel);
            phoneField.appendChild(phoneInput);
            container.appendChild(phoneField);

            // Email field
            const emailField = document.createElement('div');
            emailField.className = 'edit-modal-field';
            const emailLabel = document.createElement('label');
            emailLabel.className = 'edit-modal-label';
            emailLabel.textContent = 'Email Address';
            const emailInput = document.createElement('input');
            emailInput.type = 'email';
            emailInput.id = 'swal-email';
            emailInput.value = email;
            emailField.appendChild(emailLabel);
            emailField.appendChild(emailInput);
            container.appendChild(emailField);

            // Address field
            const addressField = document.createElement('div');
            addressField.className = 'edit-modal-field';
            const addressLabel = document.createElement('label');
            addressLabel.className = 'edit-modal-label';
            addressLabel.textContent = 'Address';
            const addressInput = document.createElement('textarea');
            addressInput.id = 'swal-address';
            addressInput.value = address;
            addressInput.rows = 3;
            addressField.appendChild(addressLabel);
            addressField.appendChild(addressInput);
            container.appendChild(addressField);

            swal({
                title: '✎ Edit Supplier',
                content: container,
                icon: null,
                buttons: {
                    cancel: {
                        text: 'Cancel',
                        value: false,
                        visible: true,
                        className: 'swal-button-cancel',
                        closeModal: true
                    },
                    confirm: {
                        text: 'Save Changes',
                        value: true,
                        visible: true,
                        className: 'swal-button-confirm',
                        closeModal: true
                    }
                }
            }).then(async (confirmed) => {
                if (!confirmed) return;

                const formValues = {
                    id: id,
                    name: document.getElementById('swal-name').value,
                    contact_person: document.getElementById('swal-contact').value,
                    phone: document.getElementById('swal-phone').value,
                    email: document.getElementById('swal-email').value,
                    address: document.getElementById('swal-address').value
                };

                if (!formValues.name.trim()) {
                    swal('Error', 'Supplier name is required', 'error');
                    return;
                }

                try {
                    const res = await fetch('update_supplier.php', { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(formValues) });
                    const j = await res.json();
                    if (j.success) {
                        swal('Success', '✓ Supplier updated successfully!', 'success').then(() => window.location.reload());
                    } else {
                        swal('Error', j.error || 'Failed to update', 'error');
                    }
                } catch(e){ 
                    console.error(e); 
                    swal('Error', 'Request failed', 'error');
                }
            });
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
