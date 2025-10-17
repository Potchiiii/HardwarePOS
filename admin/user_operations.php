<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once '../db.php';

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $userType = $_POST['userType'];

            $stmt = $pdo->prepare("INSERT INTO users (username, password, user_type) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password, $userType]);
            echo json_encode(['success' => true, 'message' => 'User created successfully']);
            break;

        case 'update':
            $id = $_POST['userId'];
            $username = $_POST['username'];
            $userType = $_POST['userType'];

            // Security: Prevent changing the last admin's role
            if ($userType !== 'admin') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'admin' AND id != ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() === 0) {
                    throw new Exception('Cannot change the role of the last administrator.');
                }
            }
            
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, user_type = ? WHERE id = ?");
                $stmt->execute([$username, $password, $userType, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, user_type = ? WHERE id = ?");
                $stmt->execute([$username, $userType, $id]);
            }
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            break;

        case 'delete':
            $id = $_POST['userId'];

            // Security: Prevent deleting the last admin
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'admin' AND id != ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() === 0) {
                throw new Exception('Cannot delete the last administrator.');
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;

        case 'get':
            $id = $_POST['userId'];
            $stmt = $pdo->prepare("SELECT id, username, user_type FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($user);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}