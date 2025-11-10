-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2025 at 11:52 AM
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
-- Database: `sahan`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `fabric_type` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `fabric_type`, `quantity`, `price`, `created_at`, `updated_at`) VALUES
(10, 2, 17, NULL, 1, 50.00, '2025-11-04 16:30:42', '2025-11-04 16:30:42');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `fabric_id` int(11) DEFAULT NULL,
  `fabric_type` varchar(100) DEFAULT NULL,
  `size` varchar(10) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed','cancelled') DEFAULT 'pending',
  `payment_id` varchar(100) DEFAULT NULL,
  `delivery_status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `delivery_address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_id`, `username`, `product_id`, `product_name`, `fabric_id`, `fabric_type`, `size`, `quantity`, `unit_price`, `total_amount`, `payment_status`, `payment_id`, `delivery_status`, `customer_name`, `customer_email`, `customer_phone`, `delivery_address`, `city`, `postal_code`, `created_at`, `updated_at`) VALUES
(4, 'ORD-1762506343937', 'user@gmail.com', 16, 't shirt', 18, 'Satin', '0', 1, 300.00, 300.00, 'paid', NULL, 'delivered', 'Chamith Herath', 'chamithsandeepa321@gmail.com', '0716522935', 'NO 17/80 Thannewaththa, Poholiyadda Road, Galagedara.', 'Galagedara', NULL, '2025-11-07 09:05:43', '2025-11-07 12:05:32'),
(5, 'ORD-1762519775337', 'user@gmail.com', 17, 'saree', 23, 'Satin', '0', 1, 100.00, 100.00, 'pending', NULL, 'pending', 'john cena', 'johncena@gmail.com', '4155551212', '123 Main Street\nSan Francisco, CA 94105', 'United States', NULL, '2025-11-07 12:49:35', '2025-11-07 12:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `merchant_id` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status_message` text DEFAULT NULL,
  `card_holder_name` varchar(255) DEFAULT NULL,
  `card_no` varchar(50) DEFAULT NULL,
  `card_expiry` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_code` varchar(60) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_desc` text NOT NULL,
  `product_img_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `product_name`, `product_desc`, `product_img_name`, `category`, `created`, `updated`) VALUES
(14, '34342', 'test 3', 'fwef regrg gewrgweg', 'Black Minimalist Motivation Quote LinkedIn Banner.png', 'bridalAttire', '2025-11-03 10:14:29', '2025-11-03 10:14:29'),
(15, '1', 'Bottom', 'my bottom', 'Lucid_Origin_An_elegant_Victorian_mansion_hallway_where_each_d_0.jpg', 'used', '2025-11-03 13:57:06', '2025-11-03 13:57:06'),
(16, '2', 't shirt', 'my t shirt', 'Flux_Dev_A_lush_rainforest_growing_upsidedown_from_clouds_in_t_2.jpg', 'bridalAttire', '2025-11-03 13:58:10', '2025-11-03 13:58:10'),
(17, '123', 'saree', 'my saree', 'Gemini_Generated_Image_bim5mcbim5mcbim5 (1).png', 'bridemaidAttire', '2025-11-04 11:08:59', '2025-11-04 11:08:59');

-- --------------------------------------------------------

--
-- Table structure for table `product_fabrics`
--

CREATE TABLE `product_fabrics` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `fabric_type` varchar(50) DEFAULT NULL,
  `fabric_qty` int(11) DEFAULT NULL,
  `fabric_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_fabrics`
--

INSERT INTO `product_fabrics` (`id`, `product_id`, `fabric_type`, `fabric_qty`, `fabric_price`) VALUES
(6, 14, 'Silk', 6, 450.00),
(7, 14, 'Lace and Net', 0, 0.00),
(8, 14, 'Satin', 0, 0.00),
(9, 14, 'Georgette', 0, 0.00),
(10, 14, 'Velvet', 0, 0.00),
(11, 15, 'Silk', 10, 100.00),
(12, 15, 'Lace and Net', 20, 150.00),
(13, 15, 'Satin', 30, 200.00),
(14, 15, 'Georgette', 20, 150.00),
(15, 15, 'Velvet', 45, 125.00),
(16, 16, 'Silk', 15, 100.00),
(17, 16, 'Lace and Net', 0, 0.00),
(18, 16, 'Satin', 50, 300.00),
(19, 16, 'Georgette', 15, 100.00),
(20, 16, 'Velvet', 20, 100.00),
(21, 17, 'Silk', 10, 200.00),
(22, 17, 'Lace and Net', 20, 50.00),
(23, 17, 'Satin', 20, 100.00),
(24, 17, 'Georgette', 25, 75.00),
(25, 17, 'Velvet', 15, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `product_questions`
--

CREATE TABLE `product_questions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `question` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reseller_products`
--

CREATE TABLE `reseller_products` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `fabric_type` varchar(100) DEFAULT NULL,
  `resale_price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','approved','sold','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reseller_products`
--

INSERT INTO `reseller_products` (`id`, `product_id`, `user_id`, `first_name`, `last_name`, `contact_number`, `email`, `address`, `notes`, `fabric_type`, `resale_price`, `original_price`, `status`, `created_at`) VALUES
(3, 17, 2, 'lahiru', 'sandeepa', '0812463504', 'lahiru@gmail.com', 'No 17/80 Thannewaththa,', 'hellow', 'Velvet', 90.00, 150.00, 'pending', '2025-11-06 11:02:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `pin` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'user',
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `lname`, `address`, `city`, `pin`, `email`, `password`, `type`, `created`) VALUES
(1, 'Admin', 'User', 'Colombo', 'Colombo', '00100', 'admin@gmail.com', 'Asdf@1234', 'admin', '2025-10-30 06:50:50'),
(2, 'Nimal', 'Herath', '123 Galle Road', 'Colombo', '00300', 'user@gmail.com', 'Asdf@1234', 'user', '2025-10-30 06:50:50'),
(6, 'Chamith', 'Herath', 'NO 17/80 Thannewaththa, Poholiyadda Road, Galagedara.', 'Galagedara', '20100', 'chamithsandeepa321@gmail.com', 'Asdf@1234', 'user', '2025-10-30 17:24:30'),
(7, 'lahiru', 'sandeepa', 'No 17/80 Thannewaththa,', 'Galagedara', '20100', 'lahiru@gmail.com', 'test@123', 'user', '2025-10-30 21:59:55'),
(8, 'john', 'cena', '123 Main Street', 'United States', '44052', 'admin1@gmail.com', 'Asdf@1234', 'admin', '2025-11-04 17:45:37');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created`, `created_at`) VALUES
(8, 6, 7, '2025-10-31 16:10:49', '2025-10-31 16:10:49'),
(17, 2, 17, '2025-11-04 16:29:54', '2025-11-04 16:29:54'),
(18, 2, 15, '2025-11-07 16:21:41', '2025-11-07 16:21:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_cart_user` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `username` (`username`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `payment_status` (`payment_status`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`);

--
-- Indexes for table `product_fabrics`
--
ALTER TABLE `product_fabrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_questions`
--
ALTER TABLE `product_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reseller_products`
--
ALTER TABLE `reseller_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product` (`user_id`,`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `product_fabrics`
--
ALTER TABLE `product_fabrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `product_questions`
--
ALTER TABLE `product_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reseller_products`
--
ALTER TABLE `reseller_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_fabrics`
--
ALTER TABLE `product_fabrics`
  ADD CONSTRAINT `product_fabrics_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `reseller_products`
--
ALTER TABLE `reseller_products`
  ADD CONSTRAINT `reseller_products_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
