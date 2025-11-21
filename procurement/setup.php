<?php
// This file creates necessary tables for procurement module
require_once '../db.php';

try {
    // Create purchase_orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `purchase_orders` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL UNIQUE,
            `item_id` INT(11) NOT NULL,
            `item_name` VARCHAR(100) NOT NULL,
            `brand` VARCHAR(50),
            `quantity` INT(11) NOT NULL,
            `status` ENUM('pending', 'approved', 'ordered', 'received') NOT NULL DEFAULT 'pending',
            `created_by` INT(11),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `notes` TEXT,
            FOREIGN KEY (`item_id`) REFERENCES `inventory`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        )
    ");

    // Create notifications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL,
            `item_name` VARCHAR(100) NOT NULL,
            `quantity` INT(11) NOT NULL,
            `message` TEXT NOT NULL,
            `created_by` INT(11),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `sent_to_staff` TINYINT(1) DEFAULT 0,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        )
    ");

    echo "Tables created successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
