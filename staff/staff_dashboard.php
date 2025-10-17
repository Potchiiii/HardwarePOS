<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['username'];
$userType = $_SESSION['user_type'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard | <?php echo ucfirst($userType); ?></title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      background-color: #f4f4f4;
      color: #333;
      display: flex;
    }

    .content {
      margin-left: 220px;
      padding: 30px;
      flex: 1;
    }

    .card {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      max-width: 600px;
      margin: 0 auto;
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .proceed-btn {
        display: inline-block;
        margin-top: 15px;
        padding: 10px 20px;
        background-color: #2980b9;
        color: #fff;
        text-decoration: none;
        border-radius: 5px;
        transition: background-color 0.3s;
    }
    .proceed-btn:hover {
        background-color: #2573a6;
    }
  </style>
</head>
<body>

  

<div class="content">
  <div class="card">
    <h2>Hello, <?php echo htmlspecialchars($username); ?>!</h2>
    <p>You are logged in as <strong><?php echo strtoupper($userType); ?></strong>.</p>
    <?php if ($userType === 'admin'): ?>
      <p>As an admin, you can manage users, access inventory records, and configure system settings.</p>
    <?php else: ?>
      <p>As a staff user, you can update inventory data and log internal item movements. This system is intended strictly for internal inventory tracking purposes only.</p>
    <?php endif; ?>
    <a href="staff.php" class="proceed-btn">PROCEED</a>
  </div>
</div>


</body>
</html>
