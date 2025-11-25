<?php
$host = 'localhost';
$db   = 'hardwareInventory';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // use native prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Set timezone to Manila, Philippines (UTC+8)
    $pdo->exec("SET time_zone = '+08:00'");
    date_default_timezone_set('Asia/Manila');
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
