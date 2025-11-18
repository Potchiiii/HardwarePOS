-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2025 at 10:06 AM
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
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `low_threshold` int(11) NOT NULL DEFAULT 10,
  `image_url` varchar(255) DEFAULT NULL,
  `whole_sale` decimal(10,2) DEFAULT NULL,
  `per_kilo` decimal(10,2) DEFAULT NULL,
  `per_length` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `name`, `brand`, `category`, `quantity`, `price`, `low_threshold`, `image_url`, `whole_sale`, `per_kilo`, `per_length`) VALUES
(10, 'Drill 710 watts', 'Makita', 'Power Tools', 43, 5000.00, 23, 'assets/product_images/68857a4458807.webp', NULL, NULL, NULL),
(11, 'Super glue', 'Mighty Bond', 'Adhesives', 992, 15.00, 20, 'assets/product_images/68857c12a2fc8.webp', NULL, NULL, NULL),
(12, 'Super glue', 'Gorilla Glue', 'Adhesives', 82, 22.00, 10, 'assets/product_images/68857c74a3bbf.webp', NULL, NULL, NULL),
(14, 'Yero', 'Superlume', 'Plumbing', 995, 250.00, 10, 'assets/product_images/688583ae8964d.jfif', NULL, NULL, NULL),
(15, 'Cement', 'Holcim', 'Plumbing', 71, 25.00, 10, 'assets/product_images/6885894eb032a.png', NULL, NULL, NULL),
(16, 'cement', 'republic', 'Plumbing', 40, 210.00, 10, 'assets/product_images/6885951886374.jfif', NULL, NULL, NULL);

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
(57, 9, '2025-11-06 00:00:00', 5000.00);

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
(111, 57, 10, 1, 5000.00);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `inventory_log_items`
--
ALTER TABLE `inventory_log_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
