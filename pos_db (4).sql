-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 02, 2026 at 10:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pos_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cancelled_orders`
--

CREATE TABLE `cancelled_orders` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `cashier_name` varchar(50) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `cancellation_reason` text NOT NULL,
  `cancelled_by` int(11) NOT NULL,
  `cancelled_by_name` varchar(50) NOT NULL,
  `cancelled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `original_created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cancelled_orders`
--

INSERT INTO `cancelled_orders` (`id`, `order_id`, `order_number`, `cashier_id`, `cashier_name`, `customer_name`, `total_amount`, `payment_method`, `cancellation_reason`, `cancelled_by`, `cancelled_by_name`, `cancelled_at`, `original_created_at`) VALUES
(1, 6, 'ORD-20260302-0003', 6, 'ME', '', 220.00, 'cash', 'THE CUSTOMER CHANGE HIS MIND', 1, 'admin', '2026-03-02 08:45:08', '2026-03-02 08:38:42');

-- --------------------------------------------------------

--
-- Table structure for table `cashier_sessions`
--

CREATE TABLE `cashier_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cashier_sessions`
--

INSERT INTO `cashier_sessions` (`id`, `user_id`, `login_time`, `logout_time`) VALUES
(1, 3, '2026-02-26 05:38:17', NULL),
(2, 3, '2026-02-26 05:58:47', NULL),
(3, 6, '2026-03-02 08:26:06', NULL),
(4, 6, '2026-03-02 08:36:35', NULL),
(5, 6, '2026-03-02 08:39:50', NULL),
(6, 6, '2026-03-02 08:46:57', NULL),
(7, 6, '2026-03-02 09:22:50', NULL),
(8, 8, '2026-03-02 09:44:17', NULL),
(9, 8, '2026-03-02 09:44:57', NULL),
(10, 8, '2026-03-02 09:45:59', NULL),
(11, 8, '2026-03-02 09:46:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','online','gcash','maya') DEFAULT 'cash',
  `payment_status` enum('paid','unpaid','refunded') DEFAULT 'paid',
  `order_status` enum('pending','preparing','served','cancelled') DEFAULT 'pending',
  `cancellation_requested` tinyint(1) DEFAULT 0,
  `cancellation_requested_by` int(11) DEFAULT NULL,
  `cancellation_requested_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `cashier_id`, `customer_name`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `cancellation_requested`, `cancellation_requested_by`, `cancellation_requested_at`, `cancellation_reason`, `cancelled_by`, `cancelled_at`, `created_at`, `updated_at`) VALUES
(1, 'ORD-20260226-0001', 3, 'jay', 640.00, 'cash', 'paid', 'served', 0, NULL, NULL, NULL, NULL, NULL, '2026-02-26 05:59:48', '2026-03-02 08:28:01'),
(2, 'ORD-20260226-0002', 3, '', 315.00, 'gcash', 'paid', 'served', 0, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:00:12', '2026-03-02 08:27:46'),
(3, 'ORD-20260226-0003', 3, '', 150.00, 'cash', 'paid', 'served', 0, NULL, NULL, NULL, NULL, NULL, '2026-02-26 06:00:44', '2026-03-02 08:27:34'),
(4, 'ORD-20260302-0001', 6, '', 425.00, 'cash', 'paid', 'served', 0, NULL, NULL, NULL, NULL, NULL, '2026-03-02 08:26:40', '2026-03-02 08:27:27'),
(5, 'ORD-20260302-0002', 6, '', 900.00, 'gcash', 'paid', 'served', 0, NULL, NULL, NULL, NULL, NULL, '2026-03-02 08:37:11', '2026-03-02 08:38:30'),
(6, 'ORD-20260302-0003', 6, '', 220.00, 'cash', 'paid', 'cancelled', 1, 6, '2026-03-02 08:39:02', 'THE CUSTOMER CHANGE HIS MIND', 1, '2026-03-02 08:45:08', '2026-03-02 08:38:42', '2026-03-02 08:45:08');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`, `notes`) VALUES
(1, 1, 10, 1, 95.00, 95.00, ''),
(2, 1, 26, 1, 70.00, 70.00, ''),
(3, 1, 29, 1, 80.00, 80.00, ''),
(4, 1, 49, 1, 120.00, 120.00, ''),
(5, 1, 51, 1, 90.00, 90.00, ''),
(6, 1, 56, 1, 120.00, 120.00, ''),
(7, 1, 54, 1, 65.00, 65.00, ''),
(8, 2, 31, 2, 60.00, 120.00, ''),
(9, 2, 26, 1, 70.00, 70.00, ''),
(10, 2, 33, 1, 85.00, 85.00, ''),
(11, 2, 55, 1, 40.00, 40.00, ''),
(12, 3, 4, 1, 85.00, 85.00, ''),
(13, 3, 21, 1, 65.00, 65.00, ''),
(14, 4, 12, 1, 120.00, 120.00, ''),
(15, 4, 18, 1, 70.00, 70.00, ''),
(16, 4, 49, 1, 120.00, 120.00, ''),
(17, 4, 38, 1, 115.00, 115.00, ''),
(18, 5, 9, 1, 85.00, 85.00, ''),
(19, 5, 21, 2, 65.00, 130.00, ''),
(20, 5, 18, 1, 70.00, 70.00, ''),
(21, 5, 1, 1, 65.00, 65.00, ''),
(22, 5, 14, 1, 80.00, 80.00, ''),
(23, 5, 16, 2, 85.00, 170.00, ''),
(24, 5, 6, 1, 110.00, 110.00, ''),
(25, 5, 53, 2, 95.00, 190.00, ''),
(26, 6, 18, 1, 70.00, 70.00, ''),
(27, 6, 21, 1, 65.00, 65.00, ''),
(28, 6, 4, 1, 85.00, 85.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `main_category` enum('drinks','pastry','foods') NOT NULL,
  `sub_category` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default-product.jpg',
  `stock` int(11) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `main_category`, `sub_category`, `image`, `stock`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 'Espresso', 'Strong black coffee shot, rich and aromatic', 65.00, 'drinks', 'Hot Coffee', 'drinks/1772442333_69a552dd8dc9e.jpg', 99, 1, '2026-02-20 18:29:02', '2026-03-02 09:05:33'),
(2, 'Americano', 'Espresso with hot water, smooth and bold', 75.00, 'drinks', 'Hot Coffee', 'drinks/1772442298_69a552ba15bfe.jpg', 100, 1, '2026-02-20 18:29:02', '2026-03-02 09:04:58'),
(3, 'Latte', 'Espresso with steamed milk, creamy and smooth', 85.00, 'drinks', 'Hot Coffee', 'drinks/1772442456_69a55358b8c04.jpg', 100, 1, '2026-02-20 18:29:02', '2026-03-02 09:07:36'),
(4, 'Cappuccino', 'Espresso with foamed milk, rich and frothy', 85.00, 'drinks', 'Hot Coffee', 'drinks/1772442285_69a552ad30b83.jpg', 99, 1, '2026-02-20 18:29:02', '2026-03-02 09:04:45'),
(5, 'Mocha', 'Espresso with chocolate and milk, sweet indulgence', 95.00, 'drinks', 'Hot Coffee', 'drinks/1772442470_69a5536674d8b.jpg', 100, 1, '2026-02-20 18:29:02', '2026-03-02 09:07:50'),
(6, 'Caramel Latte', 'Latte with caramel syrup, sweet and creamy', 110.00, 'drinks', 'Specialty Coffee', 'drinks/1772442657_69a5542192348.jpg', 99, 1, '2026-02-20 18:29:02', '2026-03-02 09:10:57'),
(7, 'Vanilla Latte', 'Latte with vanilla syrup, smooth and aromatic', 110.00, 'drinks', 'Specialty Coffee', 'drinks/1772442682_69a5543ad9ed4.jpg', 100, 1, '2026-02-20 18:29:02', '2026-03-02 09:11:22'),
(8, 'Hazelnut Coffee', 'Coffee with hazelnut flavor, nutty and rich', 105.00, 'drinks', 'Specialty Coffee', 'drinks/1772442672_69a5543023a2e.jpg', 100, 1, '2026-02-20 18:29:02', '2026-03-02 09:11:12'),
(9, 'Iced Americano', 'Chilled americano with ice, refreshing and bold', 85.00, 'drinks', 'Cold Coffee', 'drinks/1772441881_69a5511976f2d.jpg', 99, 1, '2026-02-20 18:29:02', '2026-03-02 08:58:01'),
(10, 'Iced Latte', 'Chilled latte with ice, smooth and refreshing', 95.00, 'drinks', 'Cold Coffee', 'drinks/1772442133_69a5521586ee3.jpg', 99, 1, '2026-02-20 18:29:02', '2026-03-02 09:02:13'),
(11, 'Iced Caramel Latte', 'Iced latte with caramel syrup, sweet and cool', 110.00, 'drinks', 'Cold Coffee', 'drinks/1772442036_69a551b47fe7e.jpg', 100, 1, '2026-02-20 18:29:02', '2026-03-02 09:00:36'),
(12, 'Frappuccino', 'Blended coffee drink with whipped cream', 120.00, 'drinks', 'Cold Coffee', 'drinks/1772441863_69a55107e7de1.jpg', 99, 1, '2026-02-20 18:29:02', '2026-03-02 08:57:43'),
(13, 'Cold Brew', 'Slow-steeped cold coffee, smooth and less acidic', 110.00, 'drinks', 'Cold Coffee', 'drinks/1772441930_69a5514aa0d58.jpg', 100, 0, '2026-02-20 18:29:02', '2026-03-02 09:46:17'),
(14, 'Hot Chocolate', 'Rich hot chocolate with marshmallows', 80.00, 'drinks', 'Non-Coffee', 'drinks/1772442501_69a553853e764.jpg', 99, 1, '2026-02-20 18:29:02', '2026-03-02 09:08:21'),
(15, 'Matcha Latte', 'Green tea latte, earthy and creamy', 95.00, 'drinks', 'Non-Coffee', 'drinks/1772442510_69a5538ed617a.jpg', 100, 1, '2026-02-20 18:29:02', '2026-03-02 09:08:30'),
(16, 'Chai Tea Latte', 'Spiced tea latte with milk', 85.00, 'drinks', 'Non-Coffee', 'drinks/1772442484_69a553749fad5.jpg', 98, 1, '2026-02-20 18:29:02', '2026-03-02 09:08:04'),
(17, 'White Chocolate Mocha', 'White chocolate and espresso, sweet and creamy', 110.00, 'drinks', 'Non-Coffee', 'drinks/1772442622_69a553fecfcf3.jpg', 100, 1, '2026-02-20 18:29:02', '2026-03-02 09:10:22'),
(18, 'Fresh Lemonade', 'Fresh squeezed lemonade, tangy and refreshing', 70.00, 'drinks', 'Fruit Drinks', 'drinks/1772442144_69a55220836b4.jpg', 98, 1, '2026-02-20 18:29:02', '2026-03-02 09:02:24'),
(19, 'Mango Shake', 'Fresh mango blended with milk', 90.00, 'drinks', 'Fruit Drinks', 'drinks/1772442432_69a553403fd7b.jpg', 100, 1, '2026-02-20 18:29:02', '2026-03-02 09:07:12'),
(20, 'Strawberry Smoothie', 'Fresh strawberries blended with yogurt', 95.00, 'drinks', 'Fruit Drinks', 'drinks/1772442444_69a5534c7a305.jpg', 100, 1, '2026-02-20 18:29:02', '2026-03-02 09:07:24'),
(21, 'Iced Tea', 'Fresh brewed iced tea with lemon', 65.00, 'drinks', 'Fruit Drinks', 'drinks/1772442420_69a55334e8e62.jpg', 97, 1, '2026-02-20 18:29:02', '2026-03-02 09:07:00'),
(22, 'Butter Croissant', 'Flaky, buttery croissant', 65.00, 'pastry', 'Pastries', 'pastry/1772443274_69a5568a04c67.jpg', 50, 1, '2026-02-20 18:29:02', '2026-03-02 09:21:14'),
(23, 'Chocolate Croissant', 'Croissant with rich chocolate filling', 80.00, 'pastry', 'Pastries', 'pastry/1772443289_69a5569950f42.jpg', 50, 1, '2026-02-20 18:29:02', '2026-03-02 09:21:29'),
(24, 'Almond Croissant', 'Croissant with almond filling and sliced almonds', 85.00, 'pastry', 'Pastries', 'pastry/1772443257_69a5567930738.jpg', 40, 1, '2026-02-20 18:29:02', '2026-03-02 09:20:57'),
(25, 'Blueberry Muffin', 'Fresh blueberry muffin with streusel topping', 70.00, 'pastry', 'Muffins', 'pastry/1772443220_69a5565440824.jpg', 50, 1, '2026-02-20 18:29:02', '2026-03-02 09:20:20'),
(26, 'Chocolate Chip Muffin', 'Rich chocolate chip muffin', 70.00, 'pastry', 'Muffins', 'pastry/1772443237_69a55665b625c.jpg', 48, 1, '2026-02-20 18:29:02', '2026-03-02 09:20:37'),
(27, 'Banana Nut Muffin', 'Moist banana muffin with walnuts', 75.00, 'pastry', 'Muffins', 'pastry/1772443201_69a5564195cb9.jpg', 45, 1, '2026-02-20 18:29:02', '2026-03-02 09:20:01'),
(28, 'Cinnamon Roll', 'Cinnamon swirl with cream cheese icing', 85.00, 'pastry', 'Sweet Pastries', 'pastry/1772443314_69a556b2004cb.jpg', 40, 1, '2026-02-20 18:29:02', '2026-03-02 09:21:54'),
(29, 'Apple Turnover', 'Flaky pastry with spiced apple filling', 80.00, 'pastry', 'Sweet Pastries', 'pastry/1772443301_69a556a59afd4.jpg', 39, 1, '2026-02-20 18:29:02', '2026-03-02 09:21:41'),
(30, 'Danish Pastry', 'Buttery pastry with fruit filling', 75.00, 'pastry', 'Sweet Pastries', 'pastry/1772443327_69a556bfbb282.jpg', 45, 1, '2026-02-20 18:29:02', '2026-03-02 09:22:07'),
(31, 'Plain Bagel', 'Fresh baked plain bagel', 60.00, 'pastry', 'Bagels', 'pastry/1772443190_69a5563634336.jpg', 58, 1, '2026-02-20 18:29:02', '2026-03-02 09:19:50'),
(32, 'Everything Bagel', 'Bagel with sesame, poppy seeds, garlic, onion', 65.00, 'pastry', 'Bagels', 'pastry/1772443177_69a55629c058f.jpg', 60, 1, '2026-02-20 18:29:02', '2026-03-02 09:19:37'),
(33, 'Cream Cheese Bagel', 'Bagel with cream cheese spread', 85.00, 'pastry', 'Bagels', 'pastry/1772443162_69a5561ae1be4.jpg', 49, 1, '2026-02-20 18:29:02', '2026-03-02 09:19:22'),
(34, 'Ham & Cheese Sandwich', 'Ham and cheese on fresh bread with lettuce', 95.00, 'foods', 'Sandwiches', 'foods/1772444635_69a55bdb35e97.jpg', 40, 1, '2026-02-20 18:29:02', '2026-03-02 09:43:55'),
(35, 'Tuna Sandwich', 'Tuna mayo with lettuce and tomato', 90.00, 'foods', 'Sandwiches', 'foods/1772444646_69a55be6a5c22.jpg', 40, 1, '2026-02-20 18:29:02', '2026-03-02 09:44:06'),
(36, 'Chicken Sandwich', 'Grilled chicken with lettuce and mayo', 110.00, 'foods', 'Sandwiches', 'foods/1772444593_69a55bb1a2a4d.jpg', 40, 1, '2026-02-20 18:29:02', '2026-03-02 09:43:13'),
(37, 'Clubhouse Sandwich', 'Triple-layer with chicken, bacon, lettuce, tomato', 135.00, 'foods', 'Sandwiches', 'foods/1772444620_69a55bcc84ad8.jpg', 35, 1, '2026-02-20 18:29:02', '2026-03-02 09:43:40'),
(38, 'BLT Sandwich', 'Bacon, lettuce, tomato on toasted bread', 115.00, 'foods', 'Sandwiches', 'foods/1772444580_69a55ba43c4b4.jpg', 39, 1, '2026-02-20 18:29:02', '2026-03-02 09:43:00'),
(39, 'Chicken Alfredo', 'Creamy alfredo pasta with grilled chicken', 150.00, 'foods', 'Pasta', 'foods/1772444488_69a55b48cfc0c.jpg', 30, 1, '2026-02-20 18:29:02', '2026-03-02 09:41:28'),
(40, 'Carbonara', 'Classic carbonara with bacon and creamy sauce', 140.00, 'foods', 'Pasta', 'foods/1772444476_69a55b3c0307e.jpg', 30, 1, '2026-02-20 18:29:02', '2026-03-02 09:41:16'),
(41, 'Pomodoro Pasta', 'Tomato basil pasta with parmesan', 130.00, 'foods', 'Pasta', 'foods/1772444526_69a55b6e37f60.jpg', 35, 1, '2026-02-20 18:29:02', '2026-03-02 09:42:06'),
(42, 'Lasagna', 'Layered pasta with meat sauce and cheese', 160.00, 'foods', 'Pasta', 'foods/1772444501_69a55b55ebe54.jpg', 25, 1, '2026-02-20 18:29:02', '2026-03-02 09:41:41'),
(43, 'Chicken Teriyaki Bowl', 'Grilled chicken teriyaki with rice', 145.00, 'foods', 'Rice Meals', 'foods/1772444608_69a55bc03c9dd.jpg', 40, 1, '2026-02-20 18:29:02', '2026-03-02 09:43:28'),
(44, 'Beef Tapa', 'Marinated beef tapa with garlic rice and egg', 160.00, 'foods', 'Rice Meals', 'foods/1772444538_69a55b7a30b8d.jpg', 35, 1, '2026-02-20 18:29:02', '2026-03-02 09:42:18'),
(45, 'Pork Sinigang', 'Savory sour soup with pork and vegetables', 150.00, 'foods', 'Rice Meals', 'foods/1772444566_69a55b96c885e.jpg', 30, 1, '2026-02-20 18:29:02', '2026-03-02 09:42:46'),
(46, 'Chicken Adobo', 'Classic chicken adobo with rice', 145.00, 'foods', 'Rice Meals', 'foods/1772444552_69a55b8804e69.jpg', 40, 1, '2026-02-20 18:29:02', '2026-03-02 09:42:32'),
(47, 'Pancakes', 'Fluffy pancakes with butter and maple syrup', 110.00, 'foods', 'Breakfast', 'foods/1772444355_69a55ac32f680.jpg', 40, 1, '2026-02-20 18:29:02', '2026-03-02 09:39:15'),
(48, 'Waffles', 'Crispy waffles with powdered sugar', 115.00, 'foods', 'Breakfast', 'foods/1772444371_69a55ad331917.jpg', 40, 1, '2026-02-20 18:29:02', '2026-03-02 09:39:31'),
(49, 'French Toast', 'Brioche french toast with berries', 120.00, 'foods', 'Breakfast', 'foods/1772444339_69a55ab38cdf5.jpg', 33, 1, '2026-02-20 18:29:02', '2026-03-02 09:38:59'),
(50, 'Breakfast Plate', 'Eggs, bacon, sausage, toast, hash browns', 160.00, 'foods', 'Breakfast', 'foods/1772444308_69a55a9454d17.jpg', 35, 1, '2026-02-20 18:29:02', '2026-03-02 09:38:28'),
(51, 'Chocolate Cake', 'Rich chocolate cake slice with ganache', 90.00, 'foods', 'Cakes', 'foods/1772444400_69a55af067d20.jpg', 29, 1, '2026-02-20 18:29:02', '2026-03-02 09:40:00'),
(52, 'Cheesecake', 'Creamy New York style cheesecake', 100.00, 'foods', 'Cakes', 'foods/1772444385_69a55ae163330.jpg', 30, 1, '2026-02-20 18:29:02', '2026-03-02 09:39:45'),
(53, 'Red Velvet Cake', 'Red velvet cake with cream cheese frosting', 95.00, 'foods', 'Cakes', 'foods/1772444416_69a55b00002bc.jpg', 28, 1, '2026-02-20 18:29:02', '2026-03-02 09:40:16'),
(54, 'Brownie', 'Fudgy chocolate brownie with walnuts', 65.00, 'foods', 'Desserts', 'foods/1772444443_69a55b1b231f2.jpg', 44, 1, '2026-02-20 18:29:02', '2026-03-02 09:40:43'),
(55, 'Chocolate Chip Cookie', 'Fresh baked chocolate chip cookie', 40.00, 'foods', 'Cookies', 'foods/1772444430_69a55b0e50b4d.jpg', 99, 1, '2026-02-20 18:29:02', '2026-03-02 09:40:30'),
(56, 'Macarons', 'Assorted French macarons (box of 4)', 120.00, 'foods', 'Desserts', 'foods/1772444464_69a55b307ca2a.jpg', 39, 1, '2026-02-20 18:29:02', '2026-03-02 09:41:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('cashier','manager') DEFAULT 'cashier',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_logout` timestamp NULL DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `last_login`, `last_logout`, `is_approved`, `approved_by`, `approved_at`) VALUES
(1, 'admin', 'admin@coffee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', '2026-02-20 18:29:02', '2026-03-02 09:46:13', NULL, 1, NULL, NULL),
(3, 'name', 'name@gmail.com', '$2y$10$jQSpFFo4v6qKGWfP.CZwm.83evRoO9RjdzdWz9uBVicHl7d16yZTq', 'cashier', '2026-02-26 05:09:48', '2026-02-26 05:58:47', NULL, 1, 1, '2026-03-02 08:25:53'),
(6, 'ME', 'Darwin@gmail.com', '$2y$10$rz8Gn3tpNSrwqCNTm3EMROMTD3s0wfYcGhG5IYCSUDWYijynIqB2K', 'cashier', '2026-03-02 08:22:22', '2026-03-02 09:22:50', NULL, 1, 1, '2026-03-02 08:25:57'),
(8, 'YOU', 'YOU@gmail.com', '$2y$10$3avU..zgZnTEwNv9dL5kjekWUA.P12EkIzFt2ikd397sYQHdG1GCa', 'cashier', '2026-03-02 09:27:54', '2026-03-02 09:46:26', NULL, 1, 1, '2026-03-02 09:28:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cancelled_orders`
--
ALTER TABLE `cancelled_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `cashier_sessions`
--
ALTER TABLE `cashier_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `cashier_id` (`cashier_id`),
  ADD KEY `cancelled_by` (`cancelled_by`),
  ADD KEY `cancellation_requested_by` (`cancellation_requested_by`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cancelled_orders`
--
ALTER TABLE `cancelled_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cashier_sessions`
--
ALTER TABLE `cashier_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `cancelled_orders`
--
ALTER TABLE `cancelled_orders`
  ADD CONSTRAINT `cancelled_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `cashier_sessions`
--
ALTER TABLE `cashier_sessions`
  ADD CONSTRAINT `cashier_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`cancellation_requested_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
