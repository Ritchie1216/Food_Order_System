-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2025 at 08:39 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `food_ordering1`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`) VALUES
(1, 'admin3', '$2y$10$GCSbO2UxypDb1JJvBeCN.eVNXnnjIDKrgOkABlL4xA6DrMa88aOXm', 'admin3@example.com');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Appetizers', 'Start your meal with our delicious appetizers', 'active', '2025-03-08 09:24:33', '2025-03-10 14:59:49'),
(3, 'Desserts', 'Sweet treats to end your meal', 'active', '2025-03-08 09:24:33', '2025-03-08 09:24:33'),
(4, 'Beverages', 'Refreshing drinks and beverages', 'inactive', '2025-03-08 09:24:33', '2025-03-20 06:40:35'),
(5, 'Noodles', '', 'active', '2025-03-08 10:08:38', '2025-03-08 10:08:38'),
(6, 'Food', '', 'active', '2025-03-10 16:19:24', '2025-03-10 16:19:24');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category`, `status`, `created_at`, `category_id`, `image_path`) VALUES
(1, 'Ice Cream', '', 6.00, NULL, 'available', '2025-03-08 09:35:19', 3, 'uploads/menu_items/67cc0f579aa28.jpg'),
(2, 'Ramen', '', 12.00, NULL, 'available', '2025-03-08 10:09:00', 5, 'uploads/menu_items/67cc173c954ff.jpg'),
(3, 'Shrimp Fritters', ' Crispy outside, soft inside, served with a spicy honey drizzle', 18.00, NULL, 'available', '2025-03-10 16:17:20', 1, 'uploads/menu_items/67cf1090a7931.jpg'),
(4, 'Lemon Tea', 'Lemon tea can be made with either black or green tea, and it\'s often sweetened with honey or sugar', 20.00, NULL, 'available', '2025-03-10 16:18:29', 4, 'uploads/menu_items/67cf10d521fec.png'),
(5, 'Chinese Rice Porridge ', 'Congee is typically made with white rice, such as jasmine or japonica, which provides a smooth and silky texture when cooked with a high water ratio', 6.00, NULL, 'available', '2025-03-10 16:20:11', 6, 'uploads/menu_items/67cf113b03f26.jpg'),
(7, 'Stick', 'A comfort-food favorite, stacked with multiple layers of pasta, creamy béchamel, tangy tomato sauce, and an extra cheesy mix of mozzarella and parmesan.', 15.00, NULL, 'available', '2025-04-08 07:54:08', 1, 'uploads/menu_items/67f4d620130c5.jpg'),
(8, 'lasagna', 'A rich and hearty Italian dish layered with tender pasta sheets, seasoned ground beef, creamy béchamel sauce, and melted mozzarella and parmesan cheese. Baked to golden perfection.', 35.00, NULL, 'available', '2025-04-08 07:54:33', 6, 'uploads/menu_items/67f4d6399abd6.jpg'),
(9, 'Pancakes', 'Fluffy and golden on the outside, soft and airy on the inside. Served with maple syrup and a pat of butter for the perfect start to your day.', 15.00, NULL, 'available', '2025-04-09 00:31:07', 1, 'uploads/menu_items/67f5bfcbb0056.jpg'),
(10, 'Burger', 'Fluffy and golden on the outside, soft and airy on the inside. Served with maple syrup and a pat of butter for the perfect start to your day.', 25.00, NULL, 'available', '2025-04-09 00:32:33', 6, 'uploads/menu_items/67f5c021bb4d8.jpg'),
(11, 'Grilled Salmon Fillet', 'Fresh salmon fillet, grilled to perfection, served with sautéed vegetables and a lemon butter sauce.', 35.00, NULL, 'available', '2025-04-09 00:34:11', 6, 'uploads/menu_items/67f5c08376d22.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `table_id`, `status`, `total_amount`, `created_at`) VALUES
(1, 27, 'completed', 19.08, '2025-03-10 03:49:43'),
(2, 27, 'completed', 12.72, '2025-03-10 05:54:55'),
(3, 27, 'completed', 6.36, '2025-03-10 13:01:09'),
(4, 27, 'completed', 6.36, '2025-03-10 13:26:08'),
(5, 27, 'completed', 6.36, '2025-03-10 13:28:35'),
(6, 27, 'completed', 6.36, '2025-03-10 13:29:15'),
(7, 27, 'completed', 6.36, '2025-03-10 13:36:01'),
(8, 27, 'cancelled', 12.72, '2025-03-10 13:36:48'),
(9, 27, 'cancelled', 6.36, '2025-03-10 13:37:26'),
(10, 27, 'processing', 6.36, '2025-03-10 13:37:46'),
(11, 27, 'processing', 6.36, '2025-03-10 13:41:56'),
(12, 27, 'processing', 6.36, '2025-03-10 13:42:24'),
(13, 27, 'processing', 6.36, '2025-03-10 13:45:18'),
(14, 27, 'completed', 6.36, '2025-03-10 13:46:57'),
(15, 27, 'completed', 6.36, '2025-03-10 13:47:09'),
(16, 27, 'processing', 6.36, '2025-03-10 13:47:31'),
(17, 27, 'cancelled', 6.36, '2025-03-10 13:49:37'),
(18, 30, 'completed', 6.36, '2025-03-10 14:01:12'),
(19, 30, 'completed', 6.36, '2025-03-10 14:17:28'),
(20, 33, 'completed', 50.88, '2025-03-12 03:29:19'),
(21, 34, 'completed', 80.56, '2025-03-12 05:19:02'),
(22, 34, 'completed', 19.08, '2025-03-12 05:41:38'),
(23, 34, 'completed', 6.36, '2025-03-12 05:45:20'),
(24, 34, 'completed', 25.44, '2025-03-12 05:48:46'),
(25, 34, 'completed', 12.72, '2025-03-12 05:54:31'),
(26, 34, 'completed', 6.36, '2025-03-12 06:25:23'),
(27, 34, 'completed', 19.08, '2025-03-12 06:25:52'),
(28, 33, 'completed', 50.88, '2025-03-13 01:46:50'),
(29, 33, 'completed', 12.72, '2025-03-13 02:09:20'),
(30, 36, 'completed', 6.36, '2025-03-13 02:13:27'),
(31, 36, 'completed', 12.72, '2025-03-13 02:16:30'),
(32, 36, 'completed', 2.12, '2025-03-13 02:32:08'),
(33, 36, 'completed', 19.08, '2025-03-20 07:38:40'),
(34, 40, 'completed', 19.08, '2025-03-20 07:40:04'),
(35, 40, 'completed', 12.72, '2025-03-20 08:06:55'),
(36, 40, 'completed', 19.08, '2025-03-20 08:22:03'),
(37, 40, 'completed', 19.08, '2025-03-20 08:27:09'),
(38, 40, 'completed', 19.08, '2025-03-20 08:29:55'),
(39, 40, 'completed', 25.44, '2025-03-21 01:21:23'),
(40, 40, 'completed', 19.08, '2025-03-21 01:49:56'),
(41, 40, 'completed', 19.08, '2025-03-21 03:12:46'),
(42, 40, 'completed', 25.44, '2025-03-21 07:37:34'),
(43, 40, 'completed', 19.08, '2025-03-21 07:42:27'),
(44, 40, 'completed', 76.32, '2025-03-24 00:53:14'),
(45, 40, 'completed', 57.24, '2025-03-24 01:04:56'),
(46, 40, 'completed', 95.40, '2025-03-24 01:07:15'),
(47, 40, 'completed', 57.24, '2025-03-24 01:13:32'),
(48, 40, 'completed', 25.44, '2025-03-24 01:18:17'),
(49, 40, 'completed', 77.04, '2025-03-24 01:30:01'),
(50, 40, 'completed', 38.52, '2025-03-24 01:32:37'),
(51, 40, 'completed', 38.16, '2025-03-24 01:40:49'),
(52, 40, 'completed', 19.26, '2025-03-24 01:55:34'),
(53, 40, 'completed', 381.60, '2025-03-24 02:06:19'),
(54, 40, 'completed', 6.36, '2025-03-24 02:08:00'),
(55, 40, 'completed', 38.16, '2025-03-24 02:22:48'),
(56, 40, 'processing', 19.08, '2025-03-26 02:03:00'),
(57, 40, 'completed', 57.24, '2025-04-08 05:49:06'),
(58, 40, 'completed', 25.44, '2025-04-08 06:02:48'),
(59, 40, 'completed', 190.80, '2025-04-08 06:13:50'),
(60, 40, 'pending', 76.32, '2025-04-08 06:14:41'),
(61, 40, 'completed', 57.24, '2025-04-08 06:15:47'),
(62, 36, 'completed', 38.16, '2025-04-08 07:51:56'),
(63, 36, 'completed', 31.80, '2025-04-09 00:25:02');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `menu_item_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `special_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price`, `created_at`, `special_instructions`) VALUES
(1, 1, 1, 1, 6.00, '2025-03-10 03:49:43', NULL),
(2, 1, 2, 1, 12.00, '2025-03-10 03:49:43', NULL),
(3, 2, 1, 2, 6.00, '2025-03-10 05:54:55', NULL),
(4, 3, 1, 1, 6.00, '2025-03-10 13:01:09', NULL),
(5, 4, 1, 1, 6.00, '2025-03-10 13:26:08', NULL),
(6, 5, 1, 1, 6.00, '2025-03-10 13:28:35', NULL),
(7, 6, 1, 1, 6.00, '2025-03-10 13:29:15', NULL),
(8, 7, 1, 1, 6.00, '2025-03-10 13:36:01', NULL),
(9, 8, 1, 2, 6.00, '2025-03-10 13:36:48', NULL),
(10, 9, 1, 1, 6.00, '2025-03-10 13:37:26', NULL),
(11, 10, 1, 1, 6.00, '2025-03-10 13:37:46', NULL),
(12, 11, 1, 1, 6.00, '2025-03-10 13:41:56', NULL),
(13, 12, 1, 1, 6.00, '2025-03-10 13:42:24', NULL),
(14, 13, 1, 1, 6.00, '2025-03-10 13:45:18', NULL),
(15, 14, 1, 1, 6.00, '2025-03-10 13:46:57', NULL),
(16, 15, 1, 1, 6.00, '2025-03-10 13:47:09', NULL),
(17, 16, 1, 1, 6.00, '2025-03-10 13:47:31', NULL),
(18, 17, 1, 1, 6.00, '2025-03-10 13:49:37', NULL),
(19, 18, 1, 1, 6.00, '2025-03-10 14:01:12', NULL),
(20, 19, 1, 1, 6.00, '2025-03-10 14:17:28', NULL),
(21, 20, 3, 2, 18.00, '2025-03-12 03:29:19', NULL),
(22, 20, 4, 3, 2.00, '2025-03-12 03:29:19', NULL),
(23, 20, 5, 1, 6.00, '2025-03-12 03:29:19', NULL),
(24, 21, 3, 2, 18.00, '2025-03-12 05:19:02', 'no chili'),
(25, 21, 4, 2, 2.00, '2025-03-12 05:19:02', 'no ice'),
(26, 21, 1, 2, 6.00, '2025-03-12 05:19:02', NULL),
(27, 21, 5, 2, 6.00, '2025-03-12 05:19:02', NULL),
(28, 21, 2, 1, 12.00, '2025-03-12 05:19:02', NULL),
(29, 22, 3, 1, 18.00, '2025-03-12 05:41:38', 'no chili\n'),
(30, 23, 5, 1, 6.00, '2025-03-12 05:45:20', 'no chili\n'),
(31, 24, 1, 1, 6.00, '2025-03-12 05:48:46', 'give me more spon'),
(32, 24, 5, 1, 6.00, '2025-03-12 05:48:46', 'no cili'),
(33, 24, 2, 1, 12.00, '2025-03-12 05:48:46', 'no egg'),
(34, 25, 2, 1, 12.00, '2025-03-12 05:54:31', NULL),
(35, 26, 1, 1, 6.00, '2025-03-12 06:25:23', NULL),
(36, 27, 3, 1, 18.00, '2025-03-12 06:25:52', NULL),
(37, 28, 1, 3, 6.00, '2025-03-13 01:46:50', 'add 1 spon'),
(38, 28, 5, 5, 6.00, '2025-03-13 01:46:50', 'No vegetable'),
(39, 29, 2, 1, 12.00, '2025-03-13 02:09:20', 'no cili vegetable\n'),
(40, 30, 5, 1, 6.00, '2025-03-13 02:13:27', NULL),
(41, 31, 2, 1, 12.00, '2025-03-13 02:16:30', NULL),
(42, 32, 4, 1, 2.00, '2025-03-13 02:32:08', NULL),
(43, 33, 3, 1, 18.00, '2025-03-20 07:38:40', NULL),
(44, 34, 3, 1, 18.00, '2025-03-20 07:40:04', NULL),
(45, 35, 2, 1, 12.00, '2025-03-20 08:06:55', NULL),
(46, 36, 3, 1, 18.00, '2025-03-20 08:22:03', NULL),
(47, 37, 3, 1, 18.00, '2025-03-20 08:27:09', NULL),
(48, 38, 3, 1, 18.00, '2025-03-20 08:29:55', NULL),
(49, 39, 3, 1, 18.00, '2025-03-21 01:21:23', NULL),
(50, 39, 1, 1, 6.00, '2025-03-21 01:21:23', NULL),
(51, 40, 3, 1, 18.00, '2025-03-21 01:49:56', NULL),
(52, 41, 3, 1, 18.00, '2025-03-21 03:12:46', NULL),
(53, 42, 1, 1, 6.00, '2025-03-21 07:37:34', NULL),
(54, 42, 3, 1, 18.00, '2025-03-21 07:37:34', NULL),
(55, 43, 3, 1, 18.00, '2025-03-21 07:42:27', NULL),
(56, 44, 3, 4, 18.00, '2025-03-24 00:53:14', NULL),
(57, 45, 3, 3, 18.00, '2025-03-24 01:04:56', NULL),
(58, 46, 3, 5, 18.00, '2025-03-24 01:07:15', NULL),
(59, 47, 3, 3, 18.00, '2025-03-24 01:13:32', NULL),
(60, 48, 5, 4, 6.00, '2025-03-24 01:18:17', NULL),
(61, 49, 3, 4, 18.00, '2025-03-24 01:30:01', 'pppppppppppppp'),
(62, 50, 3, 2, 18.00, '2025-03-24 01:32:37', NULL),
(63, 51, 3, 2, 18.00, '2025-03-24 01:40:49', NULL),
(64, 52, 3, 1, 18.00, '2025-03-24 01:55:34', NULL),
(65, 53, 3, 20, 18.00, '2025-03-24 02:06:19', NULL),
(66, 54, 5, 1, 6.00, '2025-03-24 02:08:00', NULL),
(67, 55, 3, 2, 18.00, '2025-03-24 02:22:48', NULL),
(68, 56, 3, 1, 18.00, '2025-03-26 02:03:00', NULL),
(69, 57, 3, 3, 18.00, '2025-04-08 05:49:06', NULL),
(70, 58, 2, 2, 12.00, '2025-04-08 06:02:48', NULL),
(71, 59, 3, 6, 18.00, '2025-04-08 06:13:50', NULL),
(72, 59, 1, 6, 6.00, '2025-04-08 06:13:50', NULL),
(73, 59, 5, 6, 6.00, '2025-04-08 06:13:50', NULL),
(74, 60, 3, 4, 18.00, '2025-04-08 06:14:41', NULL),
(75, 61, 3, 3, 18.00, '2025-04-08 06:15:47', NULL),
(76, 62, 3, 2, 18.00, '2025-04-08 07:51:56', NULL),
(77, 63, 7, 2, 15.00, '2025-04-09 00:25:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','completed') DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT current_timestamp(),
  `cash_received` decimal(10,2) DEFAULT NULL,
  `change_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `amount`, `payment_status`, `payment_date`, `cash_received`, `change_amount`) VALUES
(1, 27, 19.08, 'pending', '2025-03-12 07:34:27', NULL, NULL),
(2, 27, 19.08, 'pending', '2025-03-12 07:34:36', NULL, NULL),
(3, 27, 19.08, 'pending', '2025-03-12 07:34:38', NULL, NULL),
(4, 27, 19.08, 'pending', '2025-03-12 07:34:44', NULL, NULL),
(5, 27, 19.08, 'pending', '2025-03-12 07:34:53', NULL, NULL),
(6, 27, 19.08, 'pending', '2025-03-12 07:35:23', NULL, NULL),
(7, 27, 19.08, 'pending', '2025-03-12 07:35:53', NULL, NULL),
(8, 27, 19.08, 'pending', '2025-03-12 07:35:57', NULL, NULL),
(9, 27, 19.08, 'pending', '2025-03-12 07:35:59', NULL, NULL),
(10, 27, 19.08, 'pending', '2025-03-12 07:36:30', NULL, NULL),
(11, 27, 19.08, 'pending', '2025-03-12 07:37:00', NULL, NULL),
(12, 27, 19.08, 'pending', '2025-03-12 07:37:31', NULL, NULL),
(13, 27, 19.08, 'pending', '2025-03-12 07:38:02', NULL, NULL),
(14, 27, 19.08, 'pending', '2025-03-12 07:38:33', NULL, NULL),
(15, 27, 19.08, 'pending', '2025-03-12 07:39:03', NULL, NULL),
(16, 27, 19.08, 'pending', '2025-03-12 07:39:33', NULL, NULL),
(17, 27, 19.08, 'pending', '2025-03-12 07:40:03', NULL, NULL),
(18, 27, 19.08, 'pending', '2025-03-12 07:40:33', NULL, NULL),
(19, 27, 19.08, 'pending', '2025-03-12 07:41:03', NULL, NULL),
(20, 27, 19.08, 'pending', '2025-03-12 07:41:33', NULL, NULL),
(21, 27, 19.08, 'pending', '2025-03-12 07:41:41', NULL, NULL),
(22, 27, 19.08, 'pending', '2025-03-12 07:41:42', NULL, NULL),
(23, 27, 19.08, 'pending', '2025-03-12 07:42:12', NULL, NULL),
(24, 27, 19.08, 'pending', '2025-03-12 07:42:31', NULL, NULL),
(25, 27, 19.08, 'pending', '2025-03-12 07:42:31', NULL, NULL),
(26, 27, 19.08, 'pending', '2025-03-12 07:42:31', NULL, NULL),
(27, 27, 19.08, 'pending', '2025-03-12 07:42:32', NULL, NULL),
(28, 27, 19.08, 'pending', '2025-03-12 07:42:32', NULL, NULL),
(29, 27, 19.08, 'completed', '2025-03-12 07:43:03', NULL, NULL),
(30, 27, 19.08, 'completed', '2025-03-12 07:43:34', NULL, NULL),
(31, 27, 19.08, 'completed', '2025-03-12 07:43:35', NULL, NULL),
(32, 27, 19.08, 'completed', '2025-03-12 07:44:06', NULL, NULL),
(33, 27, 19.08, 'completed', '2025-03-12 07:44:29', NULL, NULL),
(34, 27, 19.08, 'completed', '2025-03-12 07:45:00', NULL, NULL),
(35, 27, 19.08, 'completed', '2025-03-12 07:45:06', NULL, NULL),
(36, 27, 19.08, 'completed', '2025-03-12 07:45:07', NULL, NULL),
(37, 27, 19.08, 'completed', '2025-03-12 07:45:07', NULL, NULL),
(38, 27, 19.08, 'completed', '2025-03-12 07:45:07', NULL, NULL),
(39, 27, 19.08, 'completed', '2025-03-12 07:45:37', NULL, NULL),
(40, 27, 19.08, 'completed', '2025-03-12 07:46:07', NULL, NULL),
(41, 27, 19.08, 'completed', '2025-03-12 07:46:23', NULL, NULL),
(42, 27, 19.08, 'completed', '2025-03-12 07:46:23', NULL, NULL),
(43, 27, 19.08, 'completed', '2025-03-12 07:46:24', NULL, NULL),
(44, 27, 19.08, 'completed', '2025-03-12 07:46:24', NULL, NULL),
(45, 27, 19.08, 'completed', '2025-03-12 07:46:55', NULL, NULL),
(46, 27, 19.08, 'completed', '2025-03-12 07:47:22', NULL, NULL),
(47, 27, 19.08, 'completed', '2025-03-12 07:47:53', NULL, NULL),
(48, 27, 19.08, 'completed', '2025-03-12 07:47:57', NULL, NULL),
(49, 27, 19.08, 'completed', '2025-03-12 07:48:28', NULL, NULL),
(50, 27, 19.08, 'completed', '2025-03-12 07:49:54', NULL, NULL),
(51, 27, 19.08, 'completed', '2025-03-12 07:49:54', NULL, NULL),
(52, 27, 19.08, 'completed', '2025-03-12 07:49:55', NULL, NULL),
(53, 27, 19.08, 'completed', '2025-03-12 07:50:25', NULL, NULL),
(54, 27, 19.08, 'completed', '2025-03-12 07:50:31', NULL, NULL),
(55, 27, 19.08, 'completed', '2025-03-12 07:50:32', NULL, NULL),
(56, 27, 19.08, 'completed', '2025-03-12 07:51:02', NULL, NULL),
(57, 27, 19.08, 'completed', '2025-03-12 07:51:10', NULL, NULL),
(58, 27, 19.08, 'completed', '2025-03-12 07:51:12', NULL, NULL),
(59, 24, 25.44, 'completed', '2025-03-12 07:59:39', NULL, NULL),
(60, 24, 25.44, 'completed', '2025-03-12 08:00:10', NULL, NULL),
(61, 24, 25.44, 'completed', '2025-03-12 08:00:41', NULL, NULL),
(62, 24, 25.44, 'completed', '2025-03-12 08:01:12', NULL, NULL),
(63, 24, 25.44, 'completed', '2025-03-12 08:01:18', NULL, NULL),
(64, 22, 19.08, 'completed', '2025-03-12 08:01:22', NULL, NULL),
(65, 22, 19.08, 'completed', '2025-03-12 08:01:52', NULL, NULL),
(66, 22, 19.08, 'completed', '2025-03-12 08:02:22', NULL, NULL),
(67, 22, 19.08, 'completed', '2025-03-12 08:02:52', NULL, NULL),
(68, 24, 25.44, 'completed', '2025-03-12 08:03:24', NULL, NULL),
(69, 24, 25.44, 'completed', '2025-03-12 08:03:54', NULL, NULL),
(70, 24, 25.44, 'completed', '2025-03-12 08:04:25', NULL, NULL),
(71, 24, 25.44, 'completed', '2025-03-12 08:04:56', NULL, NULL),
(72, 24, 25.44, 'completed', '2025-03-12 08:05:27', NULL, NULL),
(73, 24, 25.44, 'completed', '2025-03-12 08:05:57', NULL, NULL),
(74, 24, 25.44, 'completed', '2025-03-12 08:06:22', NULL, NULL),
(75, 24, 25.44, 'completed', '2025-03-12 08:06:52', NULL, NULL),
(76, 24, 25.44, 'completed', '2025-03-12 08:07:23', NULL, NULL),
(77, 24, 25.44, 'completed', '2025-03-12 08:07:54', NULL, NULL),
(78, 24, 25.44, 'completed', '2025-03-12 08:08:25', NULL, NULL),
(79, 24, 25.44, 'completed', '2025-03-12 08:08:53', NULL, NULL),
(80, 24, 25.44, 'completed', '2025-03-12 08:09:24', NULL, NULL),
(81, 24, 25.44, 'completed', '2025-03-12 08:09:55', NULL, NULL),
(82, 24, 25.44, 'completed', '2025-03-12 08:10:26', NULL, NULL),
(83, 24, 25.44, 'completed', '2025-03-12 08:10:57', NULL, NULL),
(84, 21, 80.56, 'completed', '2025-03-12 08:23:47', 100.00, 19.44),
(85, 21, 80.56, 'completed', '2025-03-12 08:23:57', 100.00, 19.44),
(86, 18, 6.36, 'completed', '2025-03-12 08:24:24', 10.00, 3.64),
(87, 18, 6.36, 'completed', '2025-03-12 08:24:55', 10.00, 3.64),
(88, 18, 6.36, 'completed', '2025-03-12 08:25:26', 10.00, 3.64),
(89, 18, 6.36, 'completed', '2025-03-12 08:25:56', 10.00, 3.64),
(90, 19, 6.36, 'completed', '2025-03-12 08:30:14', 10.00, 3.64),
(91, 5, 6.36, 'completed', '2025-03-12 08:33:14', 10.00, 3.64),
(92, 4, 6.36, 'completed', '2025-03-12 08:43:05', 20.00, 13.64),
(93, 28, 50.88, 'completed', '2025-03-13 01:47:58', 60.00, 9.12),
(96, 29, 12.72, 'completed', '2025-03-13 02:11:32', 20.00, 7.28),
(97, 30, 6.36, 'completed', '2025-03-13 02:14:11', 7.00, 0.64),
(98, 31, 12.72, 'completed', '2025-03-13 02:17:01', 20.00, 7.28),
(99, 32, 2.12, 'completed', '2025-03-13 02:32:35', 3.00, 0.88),
(100, 34, 80.56, 'completed', '2025-03-21 01:48:40', 120.00, 5.52),
(101, 35, 0.00, 'completed', '2025-03-21 01:48:40', 120.00, 5.52),
(102, 36, 6.36, 'completed', '2025-03-21 01:48:40', 120.00, 5.52),
(103, 37, 0.00, 'completed', '2025-03-21 01:48:40', 120.00, 5.52),
(104, 38, 0.00, 'completed', '2025-03-21 01:48:40', 120.00, 5.52),
(105, 39, 0.00, 'completed', '2025-03-21 01:48:40', 120.00, 5.52),
(106, 40, 19.08, 'completed', '2025-03-21 03:13:43', 38.16, 0.00),
(107, 41, 0.00, 'completed', '2025-03-21 03:13:43', 38.16, 0.00),
(108, 42, 0.00, 'completed', '2025-03-21 07:38:26', 26.00, 0.56),
(109, 43, 0.00, 'completed', '2025-03-21 07:43:01', 19.08, 0.00),
(110, 44, 0.00, 'completed', '2025-03-24 00:58:30', 76.32, 0.00),
(111, 45, 0.00, 'completed', '2025-03-24 01:05:35', 58.00, 0.76),
(112, 46, 0.00, 'completed', '2025-03-24 01:12:49', 95.40, 0.00),
(113, 47, 0.00, 'completed', '2025-03-24 01:17:28', 58.00, 0.76),
(114, 48, 0.00, 'completed', '2025-03-24 01:18:45', 25.44, 0.00),
(115, 23, 0.00, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(116, 25, 0.00, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(117, 26, 0.00, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(118, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(119, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(120, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(121, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(122, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(123, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(124, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(125, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(126, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(127, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(128, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(129, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(130, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(131, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(132, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(133, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(134, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(135, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(136, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(137, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(138, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(139, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(140, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(141, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(142, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(143, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(144, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(145, 27, 19.08, 'completed', '2025-03-24 01:22:36', 588.00, 23.04),
(146, 49, 0.00, 'completed', '2025-03-24 01:31:37', 77.05, 0.01),
(147, 50, 0.00, 'completed', '2025-03-24 01:39:45', 100.00, 61.48),
(148, 51, 0.00, 'completed', '2025-03-24 01:45:17', 39.00, 0.84),
(149, 52, 0.00, 'completed', '2025-03-24 01:58:01', 100.00, 80.74),
(150, 20, 0.00, 'completed', '2025-03-24 02:03:00', 150.00, 41.88),
(151, 53, 0.00, 'completed', '2025-03-24 02:06:53', 500.00, 118.40),
(152, 54, 0.00, 'completed', '2025-03-24 02:08:28', 7.00, 0.64),
(153, 33, 50.88, 'completed', '2025-03-24 02:12:25', 20.00, 0.92),
(154, 55, 0.00, 'completed', '2025-03-24 02:56:28', 38.17, 0.02),
(155, 57, 0.00, 'completed', '2025-04-08 05:50:33', 58.00, 0.75),
(156, 58, 0.00, 'completed', '2025-04-08 06:11:53', 30.00, 4.55),
(157, 59, 0.00, 'completed', '2025-04-08 06:16:48', 248.05, 0.00),
(158, 61, 0.00, 'completed', '2025-04-08 06:16:48', 248.05, 0.00),
(159, 62, 0.00, 'completed', '2025-04-08 07:52:43', 39.00, 0.85),
(160, 63, 0.00, 'completed', '2025-04-09 00:25:51', 40.00, 8.20);

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `token` varchar(32) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`id`, `table_id`, `token`, `image_path`, `created_at`, `expires_at`, `is_active`) VALUES
(47, 27, 'a64975a6f85be752592eae60f38da33a', 'table_12_1741578408.png', '2025-03-10 11:46:48', '2025-03-10 13:46:48', 0),
(50, 27, 'a884e95b524913e59a09e54b0af485c6', 'table_12_1741610094.png', '2025-03-10 20:34:54', '2025-03-10 22:34:54', 0),
(51, 27, '28774ffb56741b7da0907045c2de0fa8', 'table_12_1741610110.png', '2025-03-10 20:35:10', '2025-03-10 22:35:10', 0),
(52, 27, '4eca886cd5bc827e42c357f8b22669b4', 'table_12_1741610114.png', '2025-03-10 20:35:14', '2025-03-10 22:35:14', 0),
(53, 27, 'b95e5d964d788c69882ecbc7f6f6f81d', 'table_12_1741610209.png', '2025-03-10 20:36:49', '2025-03-10 22:36:49', 0),
(56, 27, '616bd92fbabe1cab9eb74ba706b3479c', 'table_12_1741610223.png', '2025-03-10 20:37:03', '2025-03-10 22:37:03', 0),
(57, 27, 'f372c5c4a2c8ba770bcb321b9bcd8060', 'table_12_1741610223.png', '2025-03-10 20:37:03', '2025-03-10 22:37:03', 0),
(58, 27, 'f7ffbcab41051c1e71c5f89c2743059c', 'table_12_1741610991.png', '2025-03-10 20:49:51', '2025-03-10 22:49:51', 0),
(59, 27, 'caa6ce27e6caaed8b2732a5b877e3d15', 'table_12_1741610995.png', '2025-03-10 20:49:55', '2025-03-10 22:49:55', 0),
(60, 27, '3e312b4bf32a38454f2cf70cde56c30a', 'table_12_1741611071.png', '2025-03-10 20:51:11', '2025-03-10 22:51:11', 0),
(61, 27, '04f2700b3a62bc5418f1124cbd00b720', 'table_12_1741611602.png', '2025-03-10 21:00:02', '2025-03-10 23:00:02', 0),
(62, 27, '2a0d23b713b118761cebbbb858eb2ccc', 'table_12_1741611630.png', '2025-03-10 21:00:30', '2025-03-10 23:00:30', 0),
(63, 30, '1e5f2194fb6346673d5c0f2a15a81497', 'table_15_1741613138.png', '2025-03-10 21:25:38', '2025-03-10 23:25:38', 0),
(64, 27, '9fc4c2a8c955b3e4a02585d485e03bbe', 'table_12_1741613181.png', '2025-03-10 21:26:21', '2025-03-10 23:26:21', 0),
(65, 30, '1e3f8d7bb0c37f8b689f9c3a3e3e4aaf', 'table_15_1741615282.png', '2025-03-10 22:01:22', '2025-03-11 00:01:22', 0),
(66, 30, 'c771f2d8db31bcdf25f3f3635f5d68f6', 'table_15_1741615681.png', '2025-03-10 22:08:01', '2025-03-11 00:08:01', 1),
(89, 39, '67d180fa1b0e18bfab29112e85d79d40', 'table_2_1741833210.png', '2025-03-13 10:33:30', '2025-03-13 12:33:30', 0),
(96, 27, 'bc17cd7c6e7e505bef2076782028b005', 'table_12_1742438833.png', '2025-03-20 10:47:13', '2025-03-20 12:47:13', 1),
(97, 39, '3fcc9d4a0f4207a93d08dac65712eaa6', 'table_2_1742438834.png', '2025-03-20 10:47:14', '2025-03-20 12:47:14', 0),
(101, 39, '033f869f6fae32f652de80eaef993abb', 'table_2_1742438966.png', '2025-03-20 10:49:26', '2025-03-20 12:49:26', 0),
(103, 39, '34d6f332a05f3a738fc02f7ac002c34e', 'table_2_1742439124.png', '2025-03-20 10:52:04', '2025-03-20 12:52:04', 0),
(104, 39, 'ba048ed3e2021f1d1323b46a08ff8a86', 'table_2_1742439124.png', '2025-03-20 10:52:04', '2025-03-20 12:52:04', 0),
(105, 39, '5480d340579859eefe446e57eb39d394', 'table_2_1742439126.png', '2025-03-20 10:52:06', '2025-03-20 12:52:06', 1);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `restaurant_name` varchar(255) NOT NULL DEFAULT 'My Restaurant',
  `contact_email` varchar(255) NOT NULL DEFAULT 'contact@restaurant.com',
  `opening_time` time NOT NULL DEFAULT '09:00:00',
  `closing_time` time NOT NULL DEFAULT '22:00:00',
  `last_order_time` time NOT NULL DEFAULT '21:30:00',
  `online_ordering` tinyint(1) DEFAULT 1,
  `reservations` tinyint(1) DEFAULT 1,
  `order_notifications` tinyint(1) DEFAULT 1,
  `cash_payments` tinyint(1) DEFAULT 1,
  `card_payments` tinyint(1) DEFAULT 1,
  `digital_payments` tinyint(1) DEFAULT 0,
  `tax_rate` decimal(5,2) DEFAULT 6.00,
  `tax_name` varchar(10) DEFAULT 'SST',
  `currency_symbol` varchar(10) DEFAULT 'RM',
  `currency_code` varchar(10) DEFAULT 'MYR',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `restaurant_name`, `contact_email`, `opening_time`, `closing_time`, `last_order_time`, `online_ordering`, `reservations`, `order_notifications`, `cash_payments`, `card_payments`, `digital_payments`, `tax_rate`, `tax_name`, `currency_symbol`, `currency_code`, `created_at`, `updated_at`) VALUES
(1, 'Lize Restaurant', 'contact@restaurant.com', '10:00:00', '22:00:00', '21:30:00', 1, 1, 1, 1, 1, 0, 6.00, 'SST', 'RM', 'MYR', '2025-03-24 00:50:58', '2025-04-08 05:51:20');

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_number` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_number`, `status`, `created_at`) VALUES
(27, 12, 'active', '2025-03-10 03:46:48'),
(30, 15, 'active', '2025-03-10 13:25:38'),
(33, 16, 'active', '2025-03-12 03:15:08'),
(34, 17, 'active', '2025-03-12 05:17:15'),
(36, 1, 'active', '2025-03-13 02:13:03'),
(39, 2, 'active', '2025-03-13 02:33:30'),
(40, 88, 'active', '2025-03-20 07:13:31'),
(41, 99, 'active', '2025-04-08 06:01:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_table` (`table_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
