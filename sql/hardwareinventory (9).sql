-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 08:36 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hardwareinventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `batch_id` varchar(50) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','checked','received') NOT NULL DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `checked_by` int(11) DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `low_threshold` int(11) NOT NULL DEFAULT 10,
  `image_url` varchar(255) DEFAULT NULL,
  `whole_sale` decimal(10,2) DEFAULT NULL,
  `per_kilo` decimal(10,2) DEFAULT NULL,
  `per_length` decimal(10,2) DEFAULT NULL,
  `batch_id` varchar(50) DEFAULT NULL,
  `condition` varchar(20) NOT NULL DEFAULT 'good',
  `per_unit` decimal(10,2) DEFAULT NULL,
  `wholesale_deduction_units` decimal(10,2) DEFAULT NULL,
  `wholesale_deduction_meters` decimal(10,2) DEFAULT NULL,
  `wholesale_deduction_kilos` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `name`, `brand`, `category`, `quantity`, `low_threshold`, `image_url`, `whole_sale`, `per_kilo`, `per_length`, `batch_id`, `condition`, `per_unit`, `wholesale_deduction_units`, `wholesale_deduction_meters`, `wholesale_deduction_kilos`) VALUES
(10, 'Drill 710 watts', 'Makita', 'Power Tools', 101.00, 23, 'assets/product_images/68857a4458807.webp', 48000.00, NULL, NULL, '1', 'good', 5000.00, 10.00, NULL, NULL),
(11, 'Super glue', 'Mighty Bond', 'Adhesives', 992.00, 20, 'assets/product_images/68857c12a2fc8.webp', 3000.00, NULL, NULL, '1', 'good', 20.00, 1000.00, NULL, NULL),
(14, 'Yero', 'Superlume', 'Plumbing', 995.00, 10, 'assets/product_images/688583ae8964d.jfif', 5000.00, NULL, 200.00, '1', 'good', NULL, NULL, 100.00, NULL),
(15, 'Cement', 'Holcim', 'Plumbing', 1231.00, 10000, 'assets/product_images/6885894eb032a.png', 98000.00, NULL, NULL, '1', 'good', 250.00, 300.00, NULL, NULL),
(17, 'PVC PIPE', 'Orion Pipes', 'Pipings', 99.60, 95, 'assets/product_images/6922070aaebce.jpg', 500.00, NULL, 100.00, NULL, 'good', NULL, NULL, 10.00, NULL),
(18, 'Concrete Nail', 'TOUGH', 'Fastener', 500.00, 200, 'assets/product_images/6925f2a1d0462.jpg', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, NULL),
(19, 'PAINT', 'DAVIES', 'PAINT', 500.00, 100, 'assets/product_images/6925f337cfaf3.webp', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sale_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_logs`
--

INSERT INTO `inventory_logs` (`id`, `user_id`, `sale_date`, `total_amount`) VALUES
(56, 9, '2025-11-06 00:00:00', 5000.00),
(57, 9, '2025-11-06 00:00:00', 5000.00),
(58, 9, '2025-11-25 19:09:16', 1010250.00),
(59, 9, '2025-11-26 01:34:07', 150.00),
(60, 9, '2025-11-26 01:59:16', 140.00),
(61, 9, '2025-11-26 03:13:43', 5000.00);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log_items`
--

CREATE TABLE `inventory_log_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_log_items`
--

INSERT INTO `inventory_log_items` (`id`, `sale_id`, `inventory_id`, `quantity`, `price`) VALUES
(110, 56, 10, 1, 5000.00),
(111, 57, 10, 1, 5000.00),
(112, 58, 17, 3, 100.00),
(113, 58, 10, 2, 5000.00),
(115, 59, 17, 2, 100.00),
(116, 60, 17, 1, 100.00),
(117, 61, 10, 1, 5000.00);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_to_staff` tinyint(1) DEFAULT 0,
  `processed` tinyint(1) DEFAULT 0,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `processed_notes` text DEFAULT NULL,
  `processed_added_qty` int(11) DEFAULT NULL,
  `processed_defective_qty` int(11) DEFAULT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  `item_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `order_number`, `item_name`, `quantity`, `message`, `created_by`, `created_at`, `sent_to_staff`, `processed`, `processed_by`, `processed_at`, `processed_notes`, `processed_added_qty`, `processed_defective_qty`, `order_id`, `item_id`) VALUES
(1, 'ORD-2025-11-21-692086b9c5856', '', 100, 'Order ORD-2025-11-21-692086b9c5856 marked as received by procurement. Expected/received: 100. Initial defective reported: 0.', 11, '2025-11-22 17:56:12', 1, 1, 10, '2025-11-23 02:02:51', '20 of the items that arrived are defective', 80, 20, NULL, NULL),
(2, 'ORD-2025-11-22-6921fc59ccbf5', 'Drill 710 watts', 100, 'Marked as received by procurement. Expected/received: 100. Initial defective reported: 0.', 11, '2025-11-22 18:09:41', 1, 1, 10, '2025-11-23 02:10:06', '5 items are defective', 95, 5, NULL, NULL),
(3, 'ORD-2025-11-22-6922070aadbaa', 'PVC PIPE', 400, 'Marked as received by procurement. Expected/received: 400. Initial defective reported: 0.', 11, '2025-11-22 18:56:16', 1, 1, 10, '2025-11-23 02:57:00', 'Many of the units are defective', 100, 300, NULL, NULL),
(4, 'ORD-2025-11-25-6925d6c86ddf8', 'Cement', 1000, 'Marked as received by procurement. Expected/received: 1000. Initial defective reported: 0.', 11, '2025-11-25 16:18:30', 1, 1, 10, '2025-11-26 00:20:11', '', 980, 20, NULL, NULL),
(5, 'ORD-2025-11-25-6925e30324348', 'Cement', 100, 'Marked as received by procurement. Expected/received: 100. Initial defective reported: 0.', 11, '2025-11-25 17:11:01', 1, 1, 10, '2025-11-26 02:20:19', '', 100, 0, NULL, NULL),
(6, 'ORD-2025-11-25-6925eed69a9f2', 'Cement', 100, 'Marked as received by procurement. Expected/received: 100. Initial defective reported: 0.', 11, '2025-11-25 18:04:02', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'ORD-2025-11-25-6925ef43048c7', 'Cement', 100, 'Marked as received by procurement. Expected/received: 100. Initial defective reported: 0.', 11, '2025-11-25 18:04:07', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'ORD-2025-11-25-6925ef7910e0b', 'PVC PIPE', 20, 'Marked as received by procurement. Expected/received: 20. Initial defective reported: 0.', 11, '2025-11-25 18:04:15', 1, 1, 10, '2025-11-26 02:07:01', '', 5, 15, NULL, NULL),
(9, 'ORD-2025-11-25-6925f2a1cf677', 'Concrete Nail', 500, 'Marked as received by procurement. Expected/received: 500. Initial defective reported: 0.', 11, '2025-11-25 18:17:33', 1, 1, 10, '2025-11-26 03:14:55', '', 500, 0, NULL, NULL),
(10, 'ORD-2025-11-25-6925f337c998c', 'PAINT', 500, 'Marked as received by procurement. Expected/received: 500. Initial defective reported: 0.', 11, '2025-11-25 18:19:43', 1, 1, 10, '2025-11-26 03:28:10', '', 500, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items_tracking`
--

CREATE TABLE `order_items_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_received` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity_remaining` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items_tracking`
--

INSERT INTO `order_items_tracking` (`id`, `order_id`, `item_id`, `quantity_received`, `quantity_remaining`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 10, 18, 500.00, 500.00, '0000-00-00', '2025-11-25 19:14:55', '2025-11-25 19:14:55');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `item_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','approved','ordered','received') NOT NULL DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `order_number`, `item_id`, `supplier_id`, `item_name`, `brand`, `quantity`, `status`, `created_by`, `created_at`, `updated_at`, `notes`, `expiry_date`) VALUES
(1, 'ORD-2025-11-21-692084478378b', 10, 1, 'Drill 710 watts', 'Makita', 24, 'received', 11, '2025-11-21 15:24:55', '2025-11-22 18:02:02', '', NULL),
(2, 'ORD-2025-11-21-692086b9c5856', 15, 1, 'Cement', 'Holcim', 100, 'received', 11, '2025-11-21 15:35:21', '2025-11-22 18:02:02', '', NULL),
(3, 'ORD-2025-11-22-6921fc59ccbf5', 10, 2, 'Drill 710 watts', 'Makita', 100, 'received', 11, '2025-11-22 18:09:29', '2025-11-22 18:09:41', 'To arrive on 2 days', NULL),
(4, 'ORD-2025-11-22-6922070aadbaa', 17, 1, 'PVC PIPE', 'Orion Pipes', 400, 'received', 11, '2025-11-22 18:55:06', '2025-11-22 18:56:16', '', NULL),
(5, 'ORD-2025-11-25-6925d6c86ddf8', 15, 2, 'Cement', 'Holcim', 1000, 'received', 11, '2025-11-25 16:18:16', '2025-11-25 16:18:30', '', NULL),
(6, 'ORD-2025-11-25-6925e30324348', 15, 2, 'Cement', 'Holcim', 100, 'received', 11, '2025-11-25 17:10:27', '2025-11-25 17:11:01', '', NULL),
(7, 'ORD-2025-11-25-6925eed69a9f2', 15, 1, 'Cement', 'Holcim', 100, 'received', 11, '2025-11-25 18:00:54', '2025-11-25 18:04:02', 'to arrive in 3 days', NULL),
(8, 'ORD-2025-11-25-6925ef43048c7', 15, 2, 'Cement', 'Holcim', 100, 'received', 11, '2025-11-25 18:02:43', '2025-11-25 18:04:07', '', NULL),
(9, 'ORD-2025-11-25-6925ef7910e0b', 17, 2, 'PVC PIPE', 'Orion Pipes', 20, 'received', 11, '2025-11-25 18:03:37', '2025-11-25 18:04:15', '', NULL),
(10, 'ORD-2025-11-25-6925f2a1cf677', 18, 2, 'Concrete Nail', 'TOUGH', 500, 'received', 11, '2025-11-25 18:17:05', '2025-11-25 18:17:33', '', NULL),
(11, 'ORD-2025-11-25-6925f337c998c', 19, 1, 'PAINT', 'DAVIES', 500, 'received', 11, '2025-11-25 18:19:35', '2025-11-25 18:19:43', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `receipt_items`
--

CREATE TABLE `receipt_items` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) DEFAULT NULL,
  `inventory_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `defective` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt_items`
--

INSERT INTO `receipt_items` (`id`, `receipt_id`, `inventory_id`, `order_id`, `quantity`, `defective`, `notes`) VALUES
(1, 1, 15, 2, 100, 20, '20 of the items that arrived are defective'),
(2, 2, 10, 3, 100, 5, '5 items are defective'),
(3, 3, 17, 4, 400, 300, 'Many of the units are defective'),
(4, 4, 15, 5, 1000, 20, ''),
(5, 5, 17, 9, 20, 15, ''),
(6, 6, 15, 6, 100, 0, ''),
(7, 7, 18, 10, 500, 0, ''),
(8, 8, 19, 11, 500, 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `receipt_logs`
--

CREATE TABLE `receipt_logs` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt_logs`
--

INSERT INTO `receipt_logs` (`id`, `staff_id`, `order_id`, `created_at`, `notes`) VALUES
(1, 10, 2, '2025-11-23 02:02:51', '20 of the items that arrived are defective'),
(2, 10, 3, '2025-11-23 02:10:06', '5 items are defective'),
(3, 10, 4, '2025-11-23 02:57:00', 'Many of the units are defective'),
(4, 10, 5, '2025-11-26 00:20:11', ''),
(5, 10, 9, '2025-11-26 02:07:01', ''),
(6, 10, 6, '2025-11-26 02:20:19', ''),
(7, 10, 10, '2025-11-26 03:14:55', ''),
(8, 10, 11, '2025-11-26 03:28:10', '');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `created_at`) VALUES
(1, 'Cabatangan Hardware', 'Ren', '09925735012', 'hassandaud470@gmail.com', 'Cabatangan, Super Highway, Zamboanga City', '2025-11-21 15:24:05'),
(2, 'Ace Hardware', 'Daud Hassan', '09158500989', 'admin@gmail.com', 'Tubig-boh', '2025-11-22 17:07:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','staff','cashier','procurement') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `user_type`, `created_at`) VALUES
(2, 'Daud', '$2y$10$PaR1ALetObmq5snva0lfTOD/e3YGggYyG.7UIwXDfziYToAymwiIO', 'admin', '2025-07-26 22:18:38'),
(9, 'Ren', '$2y$10$xXPDmsPD.Mqa7fQ9WoYnH.NjULJgrjvWLa6EerchExJPnz.QIbG1G', 'cashier', '2025-08-06 13:28:00'),
(10, 'Jim', '$2y$10$tb0Ris.hOqHn6YZg19dreeqKyWE7zVUT/rxUxbjc1k2Lyf91WGgSm', 'staff', '2025-11-02 20:36:32'),
(11, 'Halon', '$2y$10$pdJt8vl5acqnC7GrLJ5Qh.VsNO/7Ko3tkOEzv8GiMrOS307VKcqzK', 'procurement', '2025-11-02 20:58:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_id` (`batch_id`),
  ADD KEY `item_id_idx` (`item_id`),
  ADD KEY `status_idx` (`status`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- Indexes for table `inventory_log_items`
--
ALTER TABLE `inventory_log_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id_idx` (`sale_id`),
  ADD KEY `inventory_id_idx` (`inventory_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sent_to_staff` (`sent_to_staff`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `order_items_tracking`
--
ALTER TABLE `order_items_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_remaining` (`item_id`,`quantity_remaining`),
  ADD KEY `idx_order_created` (`order_id`,`created_at`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `fk_po_supplier` (`supplier_id`);

--
-- Indexes for table `receipt_items`
--
ALTER TABLE `receipt_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receipt_logs`
--
ALTER TABLE `receipt_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `inventory_log_items`
--
ALTER TABLE `inventory_log_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_items_tracking`
--
ALTER TABLE `order_items_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `receipt_items`
--
ALTER TABLE `receipt_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `receipt_logs`
--
ALTER TABLE `receipt_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD CONSTRAINT `sales_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory_log_items`
--
ALTER TABLE `inventory_log_items`
  ADD CONSTRAINT `sales_items_inventory_fk` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sales_items_sale_fk` FOREIGN KEY (`sale_id`) REFERENCES `inventory_logs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items_tracking`
--
ALTER TABLE `order_items_tracking`
  ADD CONSTRAINT `order_items_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_tracking_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
