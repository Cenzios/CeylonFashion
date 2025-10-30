-- phpMyAdmin SQL Dump
-- Ceylon Fashion Database
-- Database: `sahan`

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `sahan`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(15) NOT NULL auto_increment,
  `product_code` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_desc` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `units` int(5) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL auto_increment,
  `product_code` varchar(60) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_desc` text NOT NULL,
  `product_img_name` varchar(255) NOT NULL,
  `qty` int(5) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `product_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;

--
-- Dumping sample data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `product_name`, `product_desc`, `product_img_name`, `qty`, `price`, `category`, `created`) VALUES
(1, 'CF001', 'Elegant Bridal Saree', 'Exquisite bridal saree with intricate embroidery and luxurious fabric. Perfect for your special day with stunning detailing.', 'bridal_saree_1.jpg', 5, 85000.00, 'bridalAttire', NOW()),
(2, 'CF002', 'Designer Bridesmaid Dress', 'Beautiful bridesmaid dress in elegant design. Comfortable and stylish for your special occasion.', 'bridesmaid_dress_1.jpg', 10, 35000.00, 'bridemaidAttire', NOW()),
(3, 'CF003', 'Party Wear Gown', 'Stunning party wear gown with modern design. Perfect for any celebration or event.', 'party_gown_1.jpg', 8, 45000.00, 'partyWear', NOW()),
(4, 'CF004', 'Pre-owned Bridal Outfit', 'Gently used bridal outfit in excellent condition. Elegant and affordable option for your wedding.', 'used_bridal_1.jpg', 1, 35000.00, 'used', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(5, 'CF005', 'Royal Bridal Lehenga', 'Magnificent bridal lehenga with heavy embellishments. A regal choice for modern brides.', 'bridal_lehenga_1.jpg', 3, 125000.00, 'bridalAttire', NOW()),
(6, 'CF006', 'Cocktail Party Dress', 'Chic cocktail dress perfect for evening parties and celebrations.', 'cocktail_dress_1.jpg', 12, 28000.00, 'partyWear', NOW());

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL auto_increment,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `pin` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL default 'user',
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;

--
-- Dumping sample data for table `users`
--

INSERT INTO `users` (`id`, `fname`, `lname`, `address`, `city`, `pin`, `email`, `password`, `type`) VALUES
(1, 'Admin', 'User', 'Colombo', 'Colombo', '00100', 'admin@ceylonfashion.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Nimal', 'Perera', '123 Galle Road', 'Colombo', '00300', 'nimal@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist` (Optional - for wishlist functionality)
--

CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table `cart` (Optional - for cart functionality)
--

CREATE TABLE IF NOT EXISTS `cart` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(5) NOT NULL DEFAULT 1,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;