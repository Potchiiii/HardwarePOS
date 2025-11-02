<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
include '../db.php';
include 'includes/sidebar.php';


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Logs</title>
    <link href="../assets/tailwind.min.js" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="ml-56 p-6">
    <h1 class="text-2xl font-bold mb-6">Upload Sales Logs</h1>
    <form action="#" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow-md">
        <div class="mb-4">
            <label for="sales_log" class="block text-gray-700 font-semibold mb-2">Select Sales Log File:</label>
            <input type="file" name="sales_log" id="sales_log" accept=".csv,.xlsx" required class="border border-gray-300 p-2 rounded w-full">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Upload</button>
    </form>
</body>
</html>
