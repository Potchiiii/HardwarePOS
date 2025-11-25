<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = "Username and password are required.";
        include 'error_alert.php';
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $message = "Invalid username or password.";
        include 'error_alert.php';
        exit();
    }

    // Successful login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = $user['user_type'];

    // Redirect based on user type
    if ($user['user_type'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else if ($user['user_type'] === 'cashier') {
        header("Location: cashier/cashier.php");
    }
    else if ($user['user_type'] === 'staff') {
        header("Location: staff/inventory.php");
    }
    else if ($user['user_type'] === 'cashier') {
        header("Location: staff/staff_dashboard.php");
    }
    else if ($user['user_type'] === 'procurement') {
        header("Location: procurement/dashboard.php");
    }
    else {
        $message = "Invalid user type.";
        include 'error_alert.php';
    }
    exit();
} else {
    $message = "Invalid request.";
    include 'error_alert.php';
    exit();
}
