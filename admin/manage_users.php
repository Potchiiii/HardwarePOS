<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users | Hardware Store</title>
    <script src="../assets/third_party/sweetalert2.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            display: flex;
            background-color: #f9f9f9;
            min-height: 100vh;
        }

        .content {
            margin-left: 220px;
            padding: 30px;
            flex: 1;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #2c3e50;
        }

        .add-btn {
            display: inline-block;
            padding: 10px 18px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .add-btn:hover {
            background: #27ae60;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #2980b9;
            color: white;
            font-weight: normal;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 5px;
        }

        .edit-btn {
            background: #3498db;
            color: white;
        }

        .delete-btn {
            background: #e74c3c;
            color: white;
        }

        .action-btn:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 24px;
            border-radius: 8px;
            width: 400px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }

        .save-btn {
            background: #2ecc71;
            color: white;
        }

        .cancel-btn {
            background: #e0e0e0;
            color: #333;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .admin-badge {
            background: #3498db;
            color: white;
        }

        .staff-badge {
            background: #2ecc71;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="content">
        <h2>Manage Users</h2>
        <button class="add-btn" onclick="showModal('add')">+ Add New User</button>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require_once '../db.php';

                // First, count how many administrators exist.
                $adminCountStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = ?");
                $adminCountStmt->execute(['admin']);
                $adminCount = (int) $adminCountStmt->fetchColumn();

                $stmt = $pdo->query("SELECT id, username, user_type FROM users ORDER BY id");
                while ($user = $stmt->fetch()) {
                    // Determine if the current user in the loop is the last admin.
                    $isLastAdmin = ($adminCount === 1 && $user['user_type'] === 'admin');
                    $isLastAdminJs = $isLastAdmin ? 'true' : 'false';

                    echo "<tr>";
                    echo "<td>{$user['id']}</td>";
                    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                    echo "<td><span class='badge " . 
                         ($user['user_type'] === 'admin' ? 'admin-badge' : 'staff-badge') . 
                         "'>" . htmlspecialchars(ucfirst($user['user_type'])) . "</span></td>";
                    
                    // Conditionally render action buttons
                    echo "<td>";
                    echo "<button class='action-btn edit-btn' onclick='editUser({$user['id']}, {$isLastAdminJs})'>Edit</button>";
                    if ($isLastAdmin) {
                        echo "<button class='action-btn delete-btn' disabled title='Cannot delete the last administrator.'>Delete</button>";
                    } else {
                        echo "<button class='action-btn delete-btn' onclick='deleteUser({$user['id']}, {$isLastAdminJs})'>Delete</button>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Modal -->
        <div class="modal" id="userModal">
            <div class="modal-content">
                <div class="modal-title">Add New User</div>
                <form id="userForm" onsubmit="handleSubmit(event)">
                    <input type="hidden" id="userId" name="userId">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="userType">Role</label>
                        <select id="userType" name="userType" required>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                            <option value="cashier">Cashier</option>
                            <option value="procurement">Procurement</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-btn cancel-btn" onclick="hideModal()">Cancel</button>
                        <button type="submit" class="modal-btn save-btn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');

        function showModal(mode, userData = null, isLastAdmin = false) {
            modal.style.display = 'flex';
            form.reset();
            
            const userTypeSelect = document.getElementById('userType');
            const passwordInput = document.getElementById('password');

            // Reset fields to their default state
            userTypeSelect.disabled = false;
            passwordInput.required = true;
            document.getElementById('userId').value = '';

            if (mode === 'edit' && userData) {
                document.querySelector('.modal-title').textContent = 'Edit User';
                document.getElementById('userId').value = userData.id;
                document.getElementById('username').value = userData.username;
                userTypeSelect.value = userData.user_type;
                passwordInput.required = false; // Password is not required for an update
                if (isLastAdmin) {
                    userTypeSelect.disabled = true; // Disable role change for the last admin
                }
            } else {
                document.querySelector('.modal-title').textContent = 'Add New User';
            }
        }

        function hideModal() {
            modal.style.display = 'none';
        }

        async function handleSubmit(event) {
            event.preventDefault();
            const formData = new FormData(form);
            const userId = document.getElementById('userId').value;
            
            formData.append('action', userId ? 'update' : 'create');
            if (userId) formData.append('userId', userId);

            try {
                const response = await fetch('user_operations.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    Swal.fire('Success', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        }

        async function deleteUser(id, isLastAdmin = false) {
            if (isLastAdmin) {
                Swal.fire({
                    icon: 'error',
                    title: 'Action Not Allowed',
                    text: 'The last administrator account cannot be deleted.',
                });
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, delete it!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('userId', id);

                        const response = await fetch('user_operations.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success').then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.error);
                        }
                    } catch (error) {
                        Swal.fire('Error', error.message, 'error');
                    }
                }
            });
        }

        async function editUser(id, isLastAdmin = false) {
            try {
                const formData = new FormData();
                formData.append('action', 'get');
                formData.append('userId', id);

                const response = await fetch('user_operations.php', {
                    method: 'POST',
                    body: formData
                });

                const userData = await response.json();
                if (userData.error) {
                    throw new Error(userData.error);
                }
                showModal('edit', userData, isLastAdmin);
            } catch (error) {
                Swal.fire('Error', 'Failed to fetch user data: ' + error.message, 'error');
            }
        }
    </script>
</body>
</html>
