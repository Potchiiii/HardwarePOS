-- Migration to add suppliers table and add supplier_id to purchase_orders.
-- Run this SQL in your MySQL database (for example via phpMyAdmin or mysql CLI).

CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100),
  `phone` VARCHAR(50),
  `email` VARCHAR(100),
  `address` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attempt to add supplier_id to purchase_orders. If your MySQL version errors
-- about the column already existing, skip this step.
ALTER TABLE `purchase_orders`
  ADD COLUMN `supplier_id` INT(11) NULL AFTER `item_id`;

-- If you want a foreign key, run the next statement after confirming both tables exist.
-- ALTER TABLE `purchase_orders` ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL;
