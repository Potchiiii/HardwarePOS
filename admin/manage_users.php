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
    <script src="../assets/third_party/sweetalert.min.js"></script>
    <style>
        :root {
            --bg: #f6f8fa;
            --card: #ffffff;
            --text: #2c3e50;
            --muted: #6b7280;
            --brand: #2980b9;
            --brand-600: #2573a6;
            --success: #2ecc71;
            --success-600: #27ae60;
            --danger: #e74c3c;
            --danger-600: #cf3e30;
            --blue: #3498db;
            --shadow: 0 2px 8px rgba(16, 24, 40, 0.06);
            --radius: 10px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            display: flex;
            background-color: var(--bg);
            min-height: 100vh;
            color: var(--text);
        }

        .content {
            margin-left: 220px;
            padding: 32px;
            flex: 1;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        h2 {
            font-size: 24px;
            color: var(--text);
            font-weight: 700;
        }

        .toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .toolbar-right { display: flex; align-items: center; }

        .search-input, .select {
            height: 36px;
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            background: #fff;
            border-radius: 6px;
            font-size: 14px;
            color: var(--text);
            outline: none;
        }
        .search-input:focus, .select:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(41,128,185,0.15); }

        .add-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: var(--success);
            color: white;
            border: 1px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .add-btn:hover { background: var(--success-600); }

        .table-wrapper {
            background: var(--card);
            border: 1px solid #eef2f7;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card);
        }

        th, td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        thead th {
            background: var(--brand);
            color: #fff;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        tbody tr:hover { background: #f8fafc; }

        td.actions { white-space: nowrap; }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 6px;
            transition: transform 0.04s ease;
        }
        .action-btn:active { transform: translateY(1px); }

        .edit-btn { background: var(--blue); color: white; }
        .edit-btn:hover { background: #2d89c7; }
        .delete-btn { background: var(--danger); color: white; }
        .delete-btn:hover { background: var(--danger-600); }

        .action-btn:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
            opacity: 0.8;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .admin-badge { background: #3498db; color: white; }
        .staff-badge { background: #2ecc71; color: white; }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 16px;
        }

        .modal-content {
            position: relative;
            background: white;
            padding: 24px;
            border-radius: 12px;
            width: 420px;
            max-width: 100%;
            box-shadow: var(--shadow);
        }

        .modal-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #111827;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            background: #f3f4f6;
            color: #111827;
            font-size: 18px;
            cursor: pointer;
            line-height: 1;
        }
        .modal-close:hover { background: #e5e7eb; }

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; margin-bottom: 6px; color: #374151; font-size: 14px; }
        .form-group input, .form-group select {
            width: 100%;
            height: 38px;
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(41,128,185,0.15); outline: none; }

        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
        .modal-btn { padding: 8px 14px; border-radius: 8px; cursor: pointer; border: 1px solid transparent; font-size: 14px; font-weight: 600; }
        .save-btn { background: var(--success); color: white; }
        .save-btn:hover { background: var(--success-600); }
        .cancel-btn { background: #f3f4f6; color: #111827; }
        .cancel-btn:hover { background: #e5e7eb; }

        /* Accessibility */
        .add-btn:focus-visible, .action-btn:focus-visible, .modal-close:focus-visible, .search-input:focus-visible, .select:focus-visible, .modal-btn:focus-visible {
            outline: 3px solid rgba(41,128,185,0.35);
            outline-offset: 1px;
        }

        @media (max-width: 720px) {
            .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .toolbar { width: 100%; justify-content: space-between; }
            .toolbar-left { width: 100%; }
            .search-input { flex: 1; min-width: 160px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="content">
        <div class="page-header">
            <h2>Manage Users</h2>
            <div class="toolbar">
                <div class="toolbar-left">
                    <input type="text" id="searchUsers" class="search-input" placeholder="Search by username...">
                    <select id="filterRole" class="select">
                        <option value="">All roles</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="cashier">Cashier</option>
                        <option value="procurement">Procurement</option>
                    </select>
                </div>
                <div class="toolbar-right">
                    <button class="add-btn" onclick="showModal('add')">+ Add New User</button>
                </div>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
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

                    echo "<tr data-username='" . htmlspecialchars($user['username'], ENT_QUOTES) . "' data-role='{$user['user_type']}'>";
                    echo "<td>{$user['id']}</td>";
                    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                    echo "<td><span class='badge " . 
                         ($user['user_type'] === 'admin' ? 'admin-badge' : 'staff-badge') . 
                         "'>" . htmlspecialchars(ucfirst($user['user_type'])) . "</span></td>";
                    
                    // Conditionally render action buttons
                    echo "<td class='actions'>";
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
        </div>

        <!-- Modal -->
        <div class="modal" id="userModal" role="dialog" aria-modal="true" aria-hidden="true">
            <div class="modal-content">
                <div class="modal-title" id="userModalTitle">Add New User</div>
                <button type="button" class="modal-close" aria-label="Close" onclick="hideModal()">×</button>
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
        let lastActiveElement = null;

        const tableBody = document.querySelector('tbody');
        const searchInput = document.getElementById('searchUsers');
        const roleFilter = document.getElementById('filterRole');

        function applyFilters() {
            const q = (searchInput?.value || '').trim().toLowerCase();
            const role = (roleFilter?.value || '').trim();
            if (!tableBody) return;
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach(row => {
                const username = (row.getAttribute('data-username') || '').toLowerCase();
                const r = row.getAttribute('data-role') || '';
                const match = (!q || username.includes(q)) && (!role || r === role);
                row.style.display = match ? '' : 'none';
            });
        }

        if (searchInput) searchInput.addEventListener('input', applyFilters);
        if (roleFilter) roleFilter.addEventListener('change', applyFilters);

        // Close modal on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) hideModal();
        });
        // Close modal on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') hideModal();
        });

        function showModal(mode, userData = null, isLastAdmin = false) {
            lastActiveElement = document.activeElement;
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            form.reset();
            
            const userTypeSelect = document.getElementById('userType');
            const passwordInput = document.getElementById('password');

            // Reset fields to their default state
            userTypeSelect.disabled = false;
            passwordInput.required = true;
            document.getElementById('userId').value = '';

            if (mode === 'edit' && userData) {
                document.getElementById('userModalTitle').textContent = 'Edit User';
                document.getElementById('userId').value = userData.id;
                document.getElementById('username').value = userData.username;
                userTypeSelect.value = userData.user_type;
                passwordInput.required = false; // Password is not required for an update
                if (isLastAdmin) {
                    userTypeSelect.disabled = true; // Disable role change for the last admin
                }
            } else {
                document.getElementById('userModalTitle').textContent = 'Add New User';
            }

            // Focus first field when modal opens
            setTimeout(() => { document.getElementById('username').focus(); }, 0);
        }

        function hideModal() {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            if (lastActiveElement) {
                try { lastActiveElement.focus(); } catch (e) {}
            }
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
                    swal('Success', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                swal('Error', error.message, 'error');
            }
        }

        async function deleteUser(id, isLastAdmin = false) {
            if (isLastAdmin) {
                swal({
                    icon: 'error',
                    title: 'Action Not Allowed',
                    text: 'The last administrator account cannot be deleted.',
                });
                return;
            }

            swal({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                buttons: ['Cancel', 'Yes, delete it!'],
                dangerMode: true,
            }).then(async (willDelete) => {
                if (willDelete) {
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
                            swal('Deleted!', data.message, 'success').then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.error);
                        }
                    } catch (error) {
                        swal('Error', error.message, 'error');
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
                swal('Error', 'Failed to fetch user data: ' + error.message, 'error');
            }
        }
    </script>
</body>
</html>
