-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2025 at 10:41 AM
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
(5, 'Chinese Rice Porridge ', 'Congee is typically made with white rice, such as jasmine or japonica, which provides a smooth and silky texture when cooked with a high water ratio', 6.00, NULL, 'available', '2025-03-10 16:20:11', 6, 'uploads/menu_items/67cf113b03f26.jpg');

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
(9, 27, 'pending', 6.36, '2025-03-10 13:37:26'),
(10, 27, 'processing', 6.36, '2025-03-10 13:37:46'),
(11, 27, 'pending', 6.36, '2025-03-10 13:41:56'),
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
(40, 40, 'pending', 19.08, '2025-03-21 01:49:56');

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
(51, 40, 3, 1, 18.00, '2025-03-21 01:49:56', NULL);

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
(105, 39, 0.00, 'completed', '2025-03-21 01:48:40', 120.00, 5.52);

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
(70, 33, '598215aa49b3caa462a181316267b7f5', 'table_16_1741749308.png', '2025-03-12 11:15:08', '2025-03-12 13:15:08', 0),
(71, 34, '45e2f97e1faa5d84fb1f9319b073bace', 'table_17_1741756635.png', '2025-03-12 13:17:15', '2025-03-12 15:17:15', 0),
(73, 33, 'e59eb0a5491767192f3f5bb0f60e7e87', 'table_16_1741760246.png', '2025-03-12 14:17:26', '2025-03-12 16:17:26', 0),
(74, 33, '0003ca8b7029356103341512647a7702', 'table_16_1741760687.png', '2025-03-12 14:24:47', '2025-03-12 16:24:47', 0),
(75, 33, '08173b02bceda8735abd72acea64a766', 'table_16_1741760692.png', '2025-03-12 14:24:52', '2025-03-12 16:24:52', 0),
(76, 33, 'ab76a14ba4f12971eb33c8e5a224fb66', 'table_16_1741760696.png', '2025-03-12 14:24:57', '2025-03-12 16:24:57', 0),
(77, 33, '4f1c9b1820bcea8bb84cdb955af1afc9', 'table_16_1741830240.png', '2025-03-13 09:44:00', '2025-03-13 11:44:00', 0),
(78, 33, '7378b67b0c990bfb2c40287865c3b8e9', 'table_16_1741830276.png', '2025-03-13 09:44:36', '2025-03-13 11:44:36', 0),
(79, 33, '7f9e19be267f9057a4b0fdc4105713e3', 'table_16_1741830368.png', '2025-03-13 09:46:08', '2025-03-13 11:46:08', 0),
(80, 33, 'e608b1d66b521c4a83e00ae6fa3d0522', 'table_16_1741831968.png', '2025-03-13 10:12:48', '2025-03-13 12:12:48', 0),
(84, 33, '335adaf5d4d1814e7753a1ce2bd3a6b0', 'table_16_1741832737.png', '2025-03-13 10:25:37', '2025-03-13 12:25:37', 0),
(85, 33, 'ebe2344054cf83f32b5b0649bad1b497', 'table_16_1741832788.png', '2025-03-13 10:26:28', '2025-03-13 12:26:28', 0),
(88, 36, '0f5672ab58dc1fbefe808fc4b9a19140', 'table_1_1741833160.png', '2025-03-13 10:32:41', '2025-03-13 12:32:41', 0),
(89, 39, '67d180fa1b0e18bfab29112e85d79d40', 'table_2_1741833210.png', '2025-03-13 10:33:30', '2025-03-13 12:33:30', 0),
(90, 36, 'ca895f0c5e17ac034ccadd279af77ff3', 'table_1_1742434403.png', '2025-03-20 09:33:23', '2025-03-20 11:33:23', 0),
(91, 36, '9a506a051036a4d2886234d7d8c1ad2e', 'table_1_1742437701.png', '2025-03-20 10:28:21', '2025-03-20 12:28:21', 0),
(92, 36, '88b911de4b4bf9209103d206354db9b2', 'table_1_1742437703.png', '2025-03-20 10:28:23', '2025-03-20 12:28:23', 0),
(93, 36, '7bb9a0c31fb5f8ad0339b9edb950261d', 'table_1_1742437855.png', '2025-03-20 10:30:55', '2025-03-20 12:30:55', 0),
(94, 34, 'f348fed69d35ead1a1ada780a8d582ce', 'table_17_1742438323.png', '2025-03-20 10:38:43', '2025-03-20 12:38:43', 0),
(95, 36, 'ac928f21d54e7c303cc142d00cde4c1e', 'table_1_1742438591.png', '2025-03-20 10:43:11', '2025-03-20 12:43:11', 0),
(96, 27, 'bc17cd7c6e7e505bef2076782028b005', 'table_12_1742438833.png', '2025-03-20 10:47:13', '2025-03-20 12:47:13', 1),
(97, 39, '3fcc9d4a0f4207a93d08dac65712eaa6', 'table_2_1742438834.png', '2025-03-20 10:47:14', '2025-03-20 12:47:14', 0),
(98, 36, 'bcbd4db1d7dc2fd69d0a86eab3839c2a', 'table_1_1742438834.png', '2025-03-20 10:47:14', '2025-03-20 12:47:14', 0),
(99, 34, 'e0566bdada9ca11bd7fe085bb65c8958', 'table_17_1742438836.png', '2025-03-20 10:47:16', '2025-03-20 12:47:16', 0),
(100, 33, '6db160304db5e2f816f548657aa26d3d', 'table_16_1742438837.png', '2025-03-20 10:47:17', '2025-03-20 12:47:17', 1),
(101, 39, '033f869f6fae32f652de80eaef993abb', 'table_2_1742438966.png', '2025-03-20 10:49:26', '2025-03-20 12:49:26', 0),
(102, 34, '285436539c74c3e933ff072f998bb8db', 'table_17_1742439105.png', '2025-03-20 10:51:45', '2025-03-20 12:51:45', 1),
(103, 39, '34d6f332a05f3a738fc02f7ac002c34e', 'table_2_1742439124.png', '2025-03-20 10:52:04', '2025-03-20 12:52:04', 0),
(104, 39, 'ba048ed3e2021f1d1323b46a08ff8a86', 'table_2_1742439124.png', '2025-03-20 10:52:04', '2025-03-20 12:52:04', 0),
(105, 39, '5480d340579859eefe446e57eb39d394', 'table_2_1742439126.png', '2025-03-20 10:52:06', '2025-03-20 12:52:06', 1),
(106, 36, 'bb6cbc2b8686955894e6e4b2151ea27e', 'table_1_1742453305.png', '2025-03-20 14:48:25', '2025-03-20 16:48:25', 0),
(110, 36, 'be4548c4006a033eb34026dcf5736613', 'table_1_1742456169.png', '2025-03-20 15:36:09', '2025-03-20 17:36:09', 1),
(113, 40, '38c3c453a694eae9528eb5fa6915a7ad', 'table_88_1742521766.png', '2025-03-21 09:49:26', '2025-03-21 11:49:26', 1);

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
(40, 88, 'active', '2025-03-20 07:13:31');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

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
