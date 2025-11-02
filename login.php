<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['userType'] ?? '';

    if (empty($username) || empty($password) || empty($userType)) {
        $message = "All fields are required.";
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

    if ($user['user_type'] !== $userType) {
        $message = "User type mismatch.";
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
