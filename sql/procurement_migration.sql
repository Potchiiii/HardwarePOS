-- Procurement Module Database Changes
-- Run this script to set up the simplified procurement module

-- Create purchase_orders table
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
    INDEX `idx_status` (`status`),
    INDEX `idx_item_id` (`item_id`),
    INDEX `idx_created_by` (`created_by`),
    FOREIGN KEY (`item_id`) REFERENCES `inventory`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL,
    `item_name` VARCHAR(100) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `message` TEXT NOT NULL,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `sent_to_staff` TINYINT(1) DEFAULT 0,
    INDEX `idx_sent_to_staff` (`sent_to_staff`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Summary of changes:
-- 1. Added purchase_orders table to track purchase orders
-- 2. Added notifications table to track staff notifications
-- 3. Both tables linked to users table for audit trail
-- 4. Both tables linked to inventory table (purchase_orders only)
-- 5. Added indexes for better performance on filtered queries
-- 6. All timestamps auto-managed by database
