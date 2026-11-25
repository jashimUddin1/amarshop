-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2026 at 05:44 PM
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
-- Database: `amarshop`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_profile_pictures`
--

CREATE TABLE `admin_profile_pictures` (
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_profile_pictures`
--

INSERT INTO `admin_profile_pictures` (`admin_user_id`, `image_path`, `updated_at`) VALUES
(79, 'profiles/489a18de74702aee0c2533cbb226033f.jpg', '2026-11-01 16:25:25');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customar_name` varchar(180) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(40) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL,
  `role` varchar(40) NOT NULL,
  `verify_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `customar_name`, `email`, `phone`, `password`, `role`, `verify_status`, `created_at`) VALUES
(77, 'manager', 'manager@gmail.com', '01950047790', '$2y$10$mEIDRjom7lckASfjrftcmO3kAsNrmBcV0TDXXzjbl5VRx1.qeclBy', 'MANAGER', 1, '2026-10-20 00:30:16'),
(79, 'CEO', 'ceo@gmail.com', '01601610105', '$2y$10$mEIDRjom7lckASfjrftcmO3kAsNrmBcV0TDXXzjbl5VRx1.qeclBy', 'CEO', 1, '2026-10-20 00:37:44');

-- --------------------------------------------------------

--
-- Table structure for table `auth`
--

CREATE TABLE `auth` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `smtp_host` varchar(255) NOT NULL DEFAULT 'smtp.gmail.com',
  `smtp_port` smallint(5) UNSIGNED NOT NULL DEFAULT 587,
  `smtp_encryption` varchar(8) NOT NULL DEFAULT 'tls',
  `smtp_user` varchar(191) NOT NULL DEFAULT '',
  `smtp_pass` text DEFAULT NULL,
  `smtp_from` varchar(191) NOT NULL DEFAULT '',
  `smtp_name` varchar(120) NOT NULL DEFAULT 'AmarShop',
  `updated_by_admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth`
--

INSERT INTO `auth` (`id`, `smtp_host`, `smtp_port`, `smtp_encryption`, `smtp_user`, `smtp_pass`, `smtp_from`, `smtp_name`, `updated_by_admin_id`, `updated_at`) VALUES
(1, 'smtp.gmail.com', 587, 'tls', 'developermdjasim@gmail.com', '16BY31xq0G1+fox5F8tTyad5jIlXYCLmVe9HUJEwoxPZ1xKZpIQMQ2UM6c/RMiA=', 'developermdjasim@gmail.com', 'AmarShop', 79, '2026-11-01 21:52:34');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_code` varchar(91) NOT NULL,
  `size` varchar(50) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_collectors`
--

CREATE TABLE `cash_collectors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(180) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_collectors`
--

INSERT INTO `cash_collectors` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Md Jasim Uddin', '2026-11-16 06:50:37', '2026-11-16 06:50:37'),
(2, 'Alif', '2026-11-16 06:50:54', '2026-11-16 06:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_photo` varchar(255) DEFAULT NULL,
  `sub_category` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `category_photo`, `sub_category`, `created_at`) VALUES
(1, 'Shirt', 'cat_1792022293_7940.png', '', '2026-07-23 17:17:03'),
(5, 'Pant', 'cat_1792022441_4440.png', '', '2026-08-31 13:58:34'),
(6, 'Shoes', 'cat_1792022896_3020.png', '', '2026-10-15 00:08:16'),
(7, 'Slippers', 'cat_1792022920_5401.png', '', '2026-10-15 00:08:40'),
(8, 'Lungi', 'cat_1792022959_3270.png', '', '2026-10-15 00:09:19'),
(9, 'Gamsa', 'cat_1792022985_3806.png', '', '2026-10-15 00:09:45'),
(10, 'Three Piece', 'cat_1792024228_7302.png', '', '2026-10-15 00:30:28'),
(11, 'ohu', 'default-avatar.svg', '', '2026-10-19 14:00:52');

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `badge` varchar(100) DEFAULT NULL,
  `badge_bg` varchar(50) DEFAULT 'bg-danger',
  `percentage_bg` varchar(50) DEFAULT 'bg-dark',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`id`, `title`, `slug`, `badge`, `badge_bg`, `percentage_bg`, `created_at`) VALUES
(1, 'New Arrivals', 'new_arrivals', 'New', 'bg-primary', 'bg-dark', '2026-08-31 14:06:41'),
(2, 'Top Deals', 'top_deals', 'Top Deals', 'bg-danger', 'bg-success', '2026-08-31 14:10:25'),
(3, 'High Discount Product', 'high_discount', 'Offer', 'bg-primary', 'bg-dark', '2026-08-31 14:11:12'),
(4, 'Top Sales', 'top_sales', 'Best Sellar', 'bg-success', 'bg-dark', '2026-08-31 14:15:09'),
(5, 'Top Rated Product', 'top_rated', 'Top Rated', 'bg-warning text-dark', 'bg-dark', '2026-08-31 14:15:40');

-- --------------------------------------------------------

--
-- Table structure for table `deliveryman`
--

CREATE TABLE `deliveryman` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customar_name` varchar(180) NOT NULL,
  `phone` varchar(40) NOT NULL DEFAULT '',
  `email` varchar(191) DEFAULT NULL,
  `login_password` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveryman`
--

INSERT INTO `deliveryman` (`id`, `customar_name`, `phone`, `email`, `login_password`, `status`, `created_at`) VALUES
(1, 'Alif', '016406404560', NULL, '$2y$10$RL6Kq7qXzx0sXDF2Mb4o0e5nvddIubMdkBxIRQbNdPCnpOabkE/.6', 'active', '2026-11-01 16:37:48');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_bags`
--

CREATE TABLE `delivery_bags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bag_code` varchar(40) NOT NULL,
  `bag_name` varchar(120) NOT NULL,
  `zone` varchar(180) NOT NULL DEFAULT '',
  `order_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deliveryman_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `return_submitted` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_admin_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_bags`
--

INSERT INTO `delivery_bags` (`id`, `bag_code`, `bag_name`, `zone`, `order_quantity`, `deliveryman_id`, `notes`, `status`, `return_submitted`, `created_by_admin_id`, `created_at`, `updated_at`, `closed_at`) VALUES
(1, 'BAG-261102-7BBC12', 'betagi', '', 0, 1, NULL, 'assigned', 0, 77, '2026-11-02 06:35:48', '2026-11-16 00:19:37', NULL),
(2, 'BAG-261102-B0E5E5', 'F3', 'betagi', 4, 1, NULL, 'closed', 1, 77, '2026-11-02 22:10:42', '2026-11-02 22:38:42', '2026-11-02 22:38:42'),
(3, 'BAG-261116-FB5206', 'Bag 261116-001730-6ED3', 'betagi', 2, 1, NULL, 'assigned', 0, 79, '2026-11-16 00:17:30', '2026-11-16 06:54:03', NULL),
(4, 'BAG-261116-43E134', 'Bag 261116-215427-9A6A', 'betagi', 1, 1, NULL, 'assigned', 0, 79, '2026-11-16 21:54:27', '2026-11-16 21:54:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_bag_cash_collections`
--

CREATE TABLE `delivery_bag_cash_collections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bag_id` bigint(20) UNSIGNED NOT NULL,
  `deliveryman_id` bigint(20) UNSIGNED NOT NULL,
  `collector_id` bigint(20) UNSIGNED DEFAULT NULL,
  `collector_name` varchar(180) DEFAULT NULL,
  `submitted_amount` decimal(12,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `rider_note` text DEFAULT NULL,
  `manager_note` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by_admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_bag_cash_collections`
--

INSERT INTO `delivery_bag_cash_collections` (`id`, `bag_id`, `deliveryman_id`, `collector_id`, `collector_name`, `submitted_amount`, `status`, `rider_note`, `manager_note`, `submitted_at`, `reviewed_by_admin_id`, `reviewed_at`) VALUES
(1, 2, 1, NULL, NULL, 4409.00, 'confirmed', NULL, NULL, '2026-11-02 22:31:32', 77, '2026-11-02 22:37:16'),
(2, 3, 1, 1, 'Md Jasim Uddin', 1001.00, 'pending', NULL, NULL, '2026-11-16 07:00:00', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `movement_type` varchar(24) NOT NULL,
  `quantity` int(11) NOT NULL,
  `stock_before` int(11) NOT NULL,
  `stock_after` int(11) NOT NULL,
  `reference_type` varchar(24) NOT NULL,
  `reference_id` bigint(20) UNSIGNED NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `product_id`, `movement_type`, `quantity`, `stock_before`, `stock_after`, `reference_type`, `reference_id`, `note`, `admin_user_id`, `created_at`) VALUES
(1, 39, 'purchase', 2, 0, 2, 'purchase', 2, 'Purchase PUR-261103-E89A21', 79, '2026-11-03 06:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `offline_pos_installation`
--

CREATE TABLE `offline_pos_installation` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `is_complete` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offline_pos_installation`
--

INSERT INTO `offline_pos_installation` (`id`, `is_complete`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `offline_pos_sales`
--

CREATE TABLE `offline_pos_sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `receipt_no` varchar(40) NOT NULL,
  `cashier_id` bigint(20) UNSIGNED NOT NULL,
  `customer_name` varchar(180) NOT NULL DEFAULT '',
  `customer_phone` varchar(40) NOT NULL DEFAULT '',
  `subtotal` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL,
  `cash_received` decimal(12,2) NOT NULL,
  `change_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(20) NOT NULL DEFAULT 'cash',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offline_pos_sales`
--

INSERT INTO `offline_pos_sales` (`id`, `receipt_no`, `cashier_id`, `customer_name`, `customer_phone`, `subtotal`, `discount_amount`, `grand_total`, `cash_received`, `change_amount`, `payment_method`, `created_at`) VALUES
(1, 'POS-20261104-000001', 1, 'Developer Jasim', '01601610105', 1154.11, 20.00, 1134.11, 2000.00, 865.89, 'cash', '2026-11-04 06:26:15');

-- --------------------------------------------------------

--
-- Table structure for table `offline_pos_sale_items`
--

CREATE TABLE `offline_pos_sale_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `product_code` varchar(50) NOT NULL DEFAULT '',
  `product_name` varchar(255) NOT NULL,
  `size` varchar(100) NOT NULL DEFAULT '',
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offline_pos_sale_items`
--

INSERT INTO `offline_pos_sale_items` (`id`, `sale_id`, `product_id`, `product_code`, `product_name`, `size`, `quantity`, `unit_price`, `line_total`) VALUES
(1, 1, 18, 'shirt-108', 'Cotton shirt', 'M,L,XL', 1, 585.00, 585.00),
(2, 1, 35, 'shirt-125', 'Primium Cotton Shirt', 'M,L,XL,XXL', 1, 569.11, 569.11);

-- --------------------------------------------------------

--
-- Table structure for table `offline_pos_users`
--

CREATE TABLE `offline_pos_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(180) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'cashier',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offline_pos_users`
--

INSERT INTO `offline_pos_users` (`id`, `full_name`, `username`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'Md Jasim Uddin', 'mdjasimh944', '$2y$10$s9zPi0e69AD1qLyOqqevuuAjekLSuHn0WN2u3ivuSgI67OPScEfo6', 'owner', 1, '2026-11-04 06:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reseller_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fullname` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text NOT NULL,
  `shipping_area` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_charge` decimal(10,2) NOT NULL,
  `grand_total` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'COD',
  `order_status` varchar(50) DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `reseller_id`, `fullname`, `phone`, `email`, `address`, `shipping_area`, `subtotal`, `shipping_charge`, `grand_total`, `payment_method`, `order_status`, `created_at`) VALUES
(19, '002', 78, NULL, 'customar', '01609069539', 'cus@gmail.com', 'House-10/11, Road-12, Mirpur-1, Dhaka', 60, 1080.00, 60.00, 1140.00, 'COD', 'delivering', '2026-10-21 23:31:50'),
(20, '003', 78, NULL, 'customar', '01609069539', 'cus@gmail.com', 'House-10/11, Road-12, Mirpur-1, Dhaka', 60, 2516.00, 60.00, 2576.00, 'COD', 'delivered', '2026-10-21 23:32:59'),
(21, '004', 85, NULL, 'name', '01745213653', '', 'er', 60, 4349.00, 60.00, 4409.00, 'COD', 'delivered', '2026-10-24 22:01:22'),
(22, '005', 78, NULL, 'customar', '01609069539', 'cus@gmail.com', 'House-10/11, Road-12, Mirpur-1, Dhaka', 60, 2696.00, 60.00, 2756.00, 'COD', 'delivered', '2026-11-01 18:47:18'),
(23, '006', 84, NULL, 'Mst Mariya Akter', '01949645050', 'developermdjasim@gmail.com', 'hose -3 d', 0, 1526.00, 60.00, 1586.00, 'COD', 'delivering', '2026-11-15 19:32:04'),
(24, '007', 87, NULL, 'DEMO Customer 001', '0000000001', 'demo-order-001@example.test', 'DEMO ONLY - Address 001', 60, 585.00, 60.00, 645.00, 'COD', 'processing', '2026-11-08 12:10:23'),
(25, '008', 87, NULL, 'DEMO Customer 002', '0000000002', 'demo-order-002@example.test', 'DEMO ONLY - Address 002', 60, 569.00, 60.00, 629.00, 'COD', 'confirmed', '2026-11-07 02:04:49'),
(26, '009', 87, NULL, 'DEMO Customer 003', '0000000003', 'demo-order-003@example.test', 'DEMO ONLY - Address 003', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-09 08:41:14'),
(27, '010', 87, NULL, 'DEMO Customer 004', '0000000004', 'demo-order-004@example.test', 'DEMO ONLY - Address 004', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-13 23:51:15'),
(28, '011', 87, NULL, 'DEMO Customer 005', '0000000005', 'demo-order-005@example.test', 'DEMO ONLY - Address 005', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-05 08:37:11'),
(29, '012', 87, NULL, 'DEMO Customer 006', '0000000006', 'demo-order-006@example.test', 'DEMO ONLY - Address 006', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-07 22:17:08'),
(30, '013', 87, NULL, 'DEMO Customer 007', '0000000007', 'demo-order-007@example.test', 'DEMO ONLY - Address 007', 120, 569.00, 120.00, 689.00, 'COD', 'confirmed', '2026-11-10 01:06:12'),
(31, '014', 87, NULL, 'DEMO Customer 008', '0000000008', 'demo-order-008@example.test', 'DEMO ONLY - Address 008', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-12 21:16:08'),
(32, '015', 87, NULL, 'DEMO Customer 009', '0000000009', 'demo-order-009@example.test', 'DEMO ONLY - Address 009', 60, 569.00, 60.00, 629.00, 'COD', 'confirmed', '2026-11-10 16:46:49'),
(33, '016', 87, NULL, 'DEMO Customer 010', '0000000010', 'demo-order-010@example.test', 'DEMO ONLY - Address 010', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-11 06:47:14'),
(34, '017', 87, NULL, 'DEMO Customer 011', '0000000011', 'demo-order-011@example.test', 'DEMO ONLY - Address 011', 60, 495.00, 60.00, 555.00, 'COD', 'confirmed', '2026-11-09 21:46:41'),
(35, '018', 87, NULL, 'DEMO Customer 012', '0000000012', 'demo-order-012@example.test', 'DEMO ONLY - Address 012', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-07 16:38:13'),
(36, '019', 87, NULL, 'DEMO Customer 013', '0000000013', 'demo-order-013@example.test', 'DEMO ONLY - Address 013', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-10 12:00:16'),
(37, '020', 87, NULL, 'DEMO Customer 014', '0000000014', 'demo-order-014@example.test', 'DEMO ONLY - Address 014', 120, 941.00, 120.00, 1061.00, 'COD', 'confirmed', '2026-11-03 02:56:32'),
(38, '021', 87, NULL, 'DEMO Customer 015', '0000000015', 'demo-order-015@example.test', 'DEMO ONLY - Address 015', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-10 15:18:38'),
(39, '022', 87, NULL, 'DEMO Customer 016', '0000000016', 'demo-order-016@example.test', 'DEMO ONLY - Address 016', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-02 10:17:40'),
(40, '023', 87, NULL, 'DEMO Customer 017', '0000000017', 'demo-order-017@example.test', 'DEMO ONLY - Address 017', 120, 941.00, 120.00, 1061.00, 'COD', 'confirmed', '2026-11-02 07:12:18'),
(41, '024', 87, NULL, 'DEMO Customer 018', '0000000018', 'demo-order-018@example.test', 'DEMO ONLY - Address 018', 60, 569.00, 60.00, 629.00, 'COD', 'confirmed', '2026-11-13 04:37:59'),
(42, '025', 87, NULL, 'DEMO Customer 019', '0000000019', 'demo-order-019@example.test', 'DEMO ONLY - Address 019', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-02 10:08:57'),
(43, '026', 87, NULL, 'DEMO Customer 020', '0000000020', 'demo-order-020@example.test', 'DEMO ONLY - Address 020', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-10 04:16:18'),
(44, '027', 87, NULL, 'DEMO Customer 021', '0000000021', 'demo-order-021@example.test', 'DEMO ONLY - Address 021', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-04 11:00:05'),
(45, '028', 87, NULL, 'DEMO Customer 022', '0000000022', 'demo-order-022@example.test', 'DEMO ONLY - Address 022', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-15 01:18:57'),
(46, '029', 87, NULL, 'DEMO Customer 023', '0000000023', 'demo-order-023@example.test', 'DEMO ONLY - Address 023', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-06 17:09:59'),
(47, '030', 87, NULL, 'DEMO Customer 024', '0000000024', 'demo-order-024@example.test', 'DEMO ONLY - Address 024', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-14 13:40:26'),
(48, '031', 87, NULL, 'DEMO Customer 025', '0000000025', 'demo-order-025@example.test', 'DEMO ONLY - Address 025', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-12 06:24:30'),
(49, '032', 87, NULL, 'DEMO Customer 026', '0000000026', 'demo-order-026@example.test', 'DEMO ONLY - Address 026', 60, 569.00, 60.00, 629.00, 'COD', 'confirmed', '2026-11-09 22:17:53'),
(50, '033', 87, NULL, 'DEMO Customer 027', '0000000027', 'demo-order-027@example.test', 'DEMO ONLY - Address 027', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-05 06:31:31'),
(51, '034', 87, NULL, 'DEMO Customer 028', '0000000028', 'demo-order-028@example.test', 'DEMO ONLY - Address 028', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-04 01:51:49'),
(52, '035', 87, NULL, 'DEMO Customer 029', '0000000029', 'demo-order-029@example.test', 'DEMO ONLY - Address 029', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-02 13:58:26'),
(53, '036', 87, NULL, 'DEMO Customer 030', '0000000030', 'demo-order-030@example.test', 'DEMO ONLY - Address 030', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-09 23:48:55'),
(54, '037', 87, NULL, 'DEMO Customer 031', '0000000031', 'demo-order-031@example.test', 'DEMO ONLY - Address 031', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-06 07:26:51'),
(55, '038', 87, NULL, 'DEMO Customer 032', '0000000032', 'demo-order-032@example.test', 'DEMO ONLY - Address 032', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-02 02:33:19'),
(56, '039', 87, NULL, 'DEMO Customer 033', '0000000033', 'demo-order-033@example.test', 'DEMO ONLY - Address 033', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-13 03:39:41'),
(57, '040', 87, NULL, 'DEMO Customer 034', '0000000034', 'demo-order-034@example.test', 'DEMO ONLY - Address 034', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-10 02:02:03'),
(58, '041', 87, NULL, 'DEMO Customer 035', '0000000035', 'demo-order-035@example.test', 'DEMO ONLY - Address 035', 120, 569.00, 120.00, 689.00, 'COD', 'confirmed', '2026-11-13 07:05:45'),
(59, '042', 87, NULL, 'DEMO Customer 036', '0000000036', 'demo-order-036@example.test', 'DEMO ONLY - Address 036', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-11 03:23:48'),
(60, '043', 87, NULL, 'DEMO Customer 037', '0000000037', 'demo-order-037@example.test', 'DEMO ONLY - Address 037', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-04 19:14:25'),
(61, '044', 87, NULL, 'DEMO Customer 038', '0000000038', 'demo-order-038@example.test', 'DEMO ONLY - Address 038', 120, 495.00, 120.00, 615.00, 'COD', 'confirmed', '2026-11-13 12:07:57'),
(62, '045', 87, NULL, 'DEMO Customer 039', '0000000039', 'demo-order-039@example.test', 'DEMO ONLY - Address 039', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-10 16:39:27'),
(63, '046', 87, NULL, 'DEMO Customer 040', '0000000040', 'demo-order-040@example.test', 'DEMO ONLY - Address 040', 120, 569.00, 120.00, 689.00, 'COD', 'confirmed', '2026-11-13 13:22:54'),
(64, '047', 87, NULL, 'DEMO Customer 041', '0000000041', 'demo-order-041@example.test', 'DEMO ONLY - Address 041', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-09 09:40:15'),
(65, '048', 87, NULL, 'DEMO Customer 042', '0000000042', 'demo-order-042@example.test', 'DEMO ONLY - Address 042', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-05 12:43:04'),
(66, '049', 87, NULL, 'DEMO Customer 043', '0000000043', 'demo-order-043@example.test', 'DEMO ONLY - Address 043', 120, 569.00, 120.00, 689.00, 'COD', 'confirmed', '2026-11-05 23:12:37'),
(67, '050', 87, NULL, 'DEMO Customer 044', '0000000044', 'demo-order-044@example.test', 'DEMO ONLY - Address 044', 120, 941.00, 120.00, 1061.00, 'COD', 'confirmed', '2026-11-11 18:08:29'),
(68, '051', 87, NULL, 'DEMO Customer 045', '0000000045', 'demo-order-045@example.test', 'DEMO ONLY - Address 045', 120, 941.00, 120.00, 1061.00, 'COD', 'confirmed', '2026-11-03 15:05:23'),
(69, '052', 87, NULL, 'DEMO Customer 046', '0000000046', 'demo-order-046@example.test', 'DEMO ONLY - Address 046', 120, 569.00, 120.00, 689.00, 'COD', 'confirmed', '2026-11-09 04:19:49'),
(70, '053', 87, NULL, 'DEMO Customer 047', '0000000047', 'demo-order-047@example.test', 'DEMO ONLY - Address 047', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-03 01:35:12'),
(71, '054', 87, NULL, 'DEMO Customer 048', '0000000048', 'demo-order-048@example.test', 'DEMO ONLY - Address 048', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-07 21:27:47'),
(72, '055', 87, NULL, 'DEMO Customer 049', '0000000049', 'demo-order-049@example.test', 'DEMO ONLY - Address 049', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-04 02:15:35'),
(73, '056', 87, NULL, 'DEMO Customer 050', '0000000050', 'demo-order-050@example.test', 'DEMO ONLY - Address 050', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-07 11:00:59'),
(74, '057', 87, NULL, 'DEMO Customer 051', '0000000051', 'demo-order-051@example.test', 'DEMO ONLY - Address 051', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-15 07:49:49'),
(75, '058', 87, NULL, 'DEMO Customer 052', '0000000052', 'demo-order-052@example.test', 'DEMO ONLY - Address 052', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-14 17:13:18'),
(76, '059', 87, NULL, 'DEMO Customer 053', '0000000053', 'demo-order-053@example.test', 'DEMO ONLY - Address 053', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-06 08:00:16'),
(77, '060', 87, NULL, 'DEMO Customer 054', '0000000054', 'demo-order-054@example.test', 'DEMO ONLY - Address 054', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-08 05:28:42'),
(78, '061', 87, NULL, 'DEMO Customer 055', '0000000055', 'demo-order-055@example.test', 'DEMO ONLY - Address 055', 120, 569.00, 120.00, 689.00, 'COD', 'confirmed', '2026-11-05 12:58:28'),
(79, '062', 87, NULL, 'DEMO Customer 056', '0000000056', 'demo-order-056@example.test', 'DEMO ONLY - Address 056', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-02 17:17:34'),
(80, '063', 87, NULL, 'DEMO Customer 057', '0000000057', 'demo-order-057@example.test', 'DEMO ONLY - Address 057', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-11 02:03:54'),
(81, '064', 87, NULL, 'DEMO Customer 058', '0000000058', 'demo-order-058@example.test', 'DEMO ONLY - Address 058', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-08 09:08:39'),
(82, '065', 87, NULL, 'DEMO Customer 059', '0000000059', 'demo-order-059@example.test', 'DEMO ONLY - Address 059', 120, 585.00, 120.00, 705.00, 'COD', 'confirmed', '2026-11-08 03:41:50'),
(83, '066', 87, NULL, 'DEMO Customer 060', '0000000060', 'demo-order-060@example.test', 'DEMO ONLY - Address 060', 60, 941.00, 60.00, 1001.00, 'COD', 'confirmed', '2026-11-04 03:13:30'),
(84, '067', 87, NULL, 'DEMO Customer 061', '0000000061', 'demo-order-061@example.test', 'DEMO ONLY - Address 061', 120, 941.00, 120.00, 1061.00, 'COD', 'confirmed', '2026-11-14 05:22:44'),
(85, '068', 87, NULL, 'DEMO Customer 062', '0000000062', 'demo-order-062@example.test', 'DEMO ONLY - Address 062', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-15 08:06:36'),
(86, '069', 87, NULL, 'DEMO Customer 063', '0000000063', 'demo-order-063@example.test', 'DEMO ONLY - Address 063', 120, 941.00, 120.00, 1061.00, 'COD', 'confirmed', '2026-11-03 07:49:53'),
(87, '070', 87, NULL, 'DEMO Customer 064', '0000000064', 'demo-order-064@example.test', 'DEMO ONLY - Address 064', 120, 941.00, 120.00, 1061.00, 'COD', 'confirmed', '2026-11-12 01:07:39'),
(88, '071', 87, NULL, 'DEMO Customer 065', '0000000065', 'demo-order-065@example.test', 'DEMO ONLY - Address 065', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-13 20:38:12'),
(89, '072', 87, NULL, 'DEMO Customer 066', '0000000066', 'demo-order-066@example.test', 'DEMO ONLY - Address 066', 60, 569.00, 60.00, 629.00, 'COD', 'confirmed', '2026-11-02 07:08:36'),
(90, '073', 87, NULL, 'DEMO Customer 067', '0000000067', 'demo-order-067@example.test', 'DEMO ONLY - Address 067', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-10 03:28:18'),
(91, '074', 87, NULL, 'DEMO Customer 068', '0000000068', 'demo-order-068@example.test', 'DEMO ONLY - Address 068', 60, 585.00, 60.00, 645.00, 'COD', 'confirmed', '2026-11-10 20:37:58'),
(92, '075', 87, NULL, 'DEMO Customer 069', '0000000069', 'demo-order-069@example.test', 'DEMO ONLY - Address 069', 120, 495.00, 120.00, 615.00, 'COD', 'confirmed', '2026-11-13 00:14:58'),
(93, '076', 87, NULL, 'DEMO Customer 070', '0000000070', 'demo-order-070@example.test', 'DEMO ONLY - Address 070', 120, 941.00, 120.00, 1061.00, 'COD', 'confirmed', '2026-11-13 00:27:09'),
(94, '077', 87, NULL, 'DEMO Customer 071', '0000000071', 'demo-order-071@example.test', 'DEMO ONLY - Address 071', 120, 495.00, 120.00, 615.00, 'COD', 'picked', '2026-11-14 03:03:40'),
(95, '078', 87, NULL, 'DEMO Customer 072', '0000000072', 'demo-order-072@example.test', 'DEMO ONLY - Address 072', 120, 585.00, 120.00, 705.00, 'COD', 'picked', '2026-11-04 14:36:49'),
(96, '079', 87, NULL, 'DEMO Customer 073', '0000000073', 'demo-order-073@example.test', 'DEMO ONLY - Address 073', 60, 941.00, 60.00, 1001.00, 'COD', 'picked', '2026-11-08 07:03:22'),
(97, '080', 87, NULL, 'DEMO Customer 074', '0000000074', 'demo-order-074@example.test', 'DEMO ONLY - Address 074', 120, 941.00, 120.00, 1061.00, 'COD', 'picked', '2026-11-11 02:01:18'),
(98, '081', 87, NULL, 'DEMO Customer 075', '0000000075', 'demo-order-075@example.test', 'DEMO ONLY - Address 075', 120, 569.00, 120.00, 689.00, 'COD', 'picked', '2026-11-12 02:27:39'),
(99, '082', 87, NULL, 'DEMO Customer 076', '0000000076', 'demo-order-076@example.test', 'DEMO ONLY - Address 076', 120, 941.00, 120.00, 1061.00, 'COD', 'picked', '2026-11-10 03:29:28'),
(100, '083', 87, NULL, 'DEMO Customer 077', '0000000077', 'demo-order-077@example.test', 'DEMO ONLY - Address 077', 120, 585.00, 120.00, 705.00, 'COD', 'picked', '2026-11-09 09:49:31'),
(101, '084', 87, NULL, 'DEMO Customer 078', '0000000078', 'demo-order-078@example.test', 'DEMO ONLY - Address 078', 60, 585.00, 60.00, 645.00, 'COD', 'picked', '2026-11-13 06:18:18'),
(102, '085', 87, NULL, 'DEMO Customer 079', '0000000079', 'demo-order-079@example.test', 'DEMO ONLY - Address 079', 120, 585.00, 120.00, 705.00, 'COD', 'picked', '2026-11-12 14:50:32'),
(103, '086', 87, NULL, 'DEMO Customer 080', '0000000080', 'demo-order-080@example.test', 'DEMO ONLY - Address 080', 120, 941.00, 120.00, 1061.00, 'COD', 'picked', '2026-11-14 21:15:42'),
(104, '087', 87, NULL, 'DEMO Customer 081', '0000000081', 'demo-order-081@example.test', 'DEMO ONLY - Address 081', 120, 941.00, 120.00, 1061.00, 'COD', 'picked', '2026-11-13 04:28:23'),
(105, '088', 87, NULL, 'DEMO Customer 082', '0000000082', 'demo-order-082@example.test', 'DEMO ONLY - Address 082', 120, 941.00, 120.00, 1061.00, 'COD', 'picked', '2026-11-08 17:53:11'),
(106, '089', 87, NULL, 'DEMO Customer 083', '0000000083', 'demo-order-083@example.test', 'DEMO ONLY - Address 083', 120, 569.00, 120.00, 689.00, 'COD', 'picked', '2026-11-06 13:40:44'),
(107, '090', 87, NULL, 'DEMO Customer 084', '0000000084', 'demo-order-084@example.test', 'DEMO ONLY - Address 084', 120, 585.00, 120.00, 705.00, 'COD', 'picked', '2026-11-14 22:42:24'),
(108, '091', 87, NULL, 'DEMO Customer 085', '0000000085', 'demo-order-085@example.test', 'DEMO ONLY - Address 085', 60, 941.00, 60.00, 1001.00, 'COD', 'picked', '2026-11-09 04:18:54'),
(109, '092', 87, NULL, 'DEMO Customer 086', '0000000086', 'demo-order-086@example.test', 'DEMO ONLY - Address 086', 120, 569.00, 120.00, 689.00, 'COD', 'picked', '2026-11-07 18:35:13'),
(110, '093', 87, NULL, 'DEMO Customer 087', '0000000087', 'demo-order-087@example.test', 'DEMO ONLY - Address 087', 60, 495.00, 60.00, 555.00, 'COD', 'picked', '2026-11-06 05:59:09'),
(111, '094', 87, NULL, 'DEMO Customer 088', '0000000088', 'demo-order-088@example.test', 'DEMO ONLY - Address 088', 120, 941.00, 120.00, 1061.00, 'COD', 'picked', '2026-11-04 18:52:32'),
(112, '095', 87, NULL, 'DEMO Customer 089', '0000000089', 'demo-order-089@example.test', 'DEMO ONLY - Address 089', 60, 569.00, 60.00, 629.00, 'COD', 'picked', '2026-11-04 17:45:14'),
(113, '096', 87, NULL, 'DEMO Customer 090', '0000000090', 'demo-order-090@example.test', 'DEMO ONLY - Address 090', 120, 585.00, 120.00, 705.00, 'COD', 'picked', '2026-11-09 07:32:51'),
(114, '097', 87, NULL, 'DEMO Customer 091', '0000000091', 'demo-order-091@example.test', 'DEMO ONLY - Address 091', 60, 941.00, 60.00, 1001.00, 'COD', 'packed_done', '2026-11-02 12:39:34'),
(115, '098', 87, NULL, 'DEMO Customer 092', '0000000092', 'demo-order-092@example.test', 'DEMO ONLY - Address 092', 60, 941.00, 60.00, 1001.00, 'COD', 'delivered', '2026-11-05 09:19:12'),
(116, '099', 87, NULL, 'DEMO Customer 093', '0000000093', 'demo-order-093@example.test', 'DEMO ONLY - Address 093', 60, 585.00, 60.00, 645.00, 'COD', 'packed_done', '2026-11-04 03:47:14'),
(117, '100', 87, NULL, 'DEMO Customer 094', '0000000094', 'demo-order-094@example.test', 'DEMO ONLY - Address 094', 120, 585.00, 120.00, 705.00, 'COD', 'packed', '2026-11-13 11:47:47'),
(118, '101', 87, NULL, 'DEMO Customer 095', '0000000095', 'demo-order-095@example.test', 'DEMO ONLY - Address 095', 120, 941.00, 120.00, 1061.00, 'COD', 'packed', '2026-11-12 09:25:19'),
(119, '102', 87, NULL, 'DEMO Customer 096', '0000000096', 'demo-order-096@example.test', 'DEMO ONLY - Address 096', 120, 941.00, 120.00, 1061.00, 'COD', 'packed', '2026-11-10 13:52:40'),
(120, '103', 87, NULL, 'DEMO Customer 097', '0000000097', 'demo-order-097@example.test', 'DEMO ONLY - Address 097', 60, 585.00, 60.00, 645.00, 'COD', 'packed', '2026-11-08 20:11:49'),
(121, '104', 87, NULL, 'DEMO Customer 098', '0000000098', 'demo-order-098@example.test', 'DEMO ONLY - Address 098', 120, 569.00, 120.00, 689.00, 'COD', 'packed', '2026-11-08 22:03:57'),
(122, '105', 87, NULL, 'DEMO Customer 099', '0000000099', 'demo-order-099@example.test', 'DEMO ONLY - Address 099', 120, 585.00, 120.00, 705.00, 'COD', 'packed', '2026-11-04 13:46:34'),
(123, '106', 87, NULL, 'DEMO Customer 100', '0000000100', 'demo-order-100@example.test', 'DEMO ONLY - Address 100', 60, 569.00, 60.00, 629.00, 'COD', 'packed', '2026-11-03 04:05:07'),
(124, '107', 84, 1, 'Mst Mariya Akter', '01949645050', 'developermdjasim@gmail.com', 'hose -3 d', 0, 569.00, 60.00, 629.00, 'COD', 'delivered', '2026-11-16 21:53:36');

-- --------------------------------------------------------

--
-- Table structure for table `order_delivery_assignments`
--

CREATE TABLE `order_delivery_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `deliveryman_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_type` varchar(20) NOT NULL,
  `provider_name` varchar(120) NOT NULL,
  `contact_name` varchar(120) NOT NULL DEFAULT '',
  `contact_phone` varchar(40) NOT NULL DEFAULT '',
  `tracking_number` varchar(120) NOT NULL DEFAULT '',
  `notes` text DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_delivery_assignments`
--

INSERT INTO `order_delivery_assignments` (`id`, `order_id`, `deliveryman_id`, `delivery_type`, `provider_name`, `contact_name`, `contact_phone`, `tracking_number`, `notes`, `assigned_at`, `updated_at`) VALUES
(1, 17, NULL, 'rider', 'Alif', '01609069539', '', '', NULL, '2026-10-21 21:53:42', '2026-10-21 21:54:07'),
(3, 18, NULL, 'courier', 'Steadfast', '01609069539', '+880 1601610105', 'sdf', 'note nai', '2026-10-21 22:48:51', '2026-10-21 22:48:51'),
(4, 20, NULL, 'courier', 'Patho', 'Md. Jahid', '01304542100', '', NULL, '2026-10-22 02:00:12', '2026-10-22 02:00:12'),
(5, 19, NULL, 'rider', 'Alif', '', '01304542100', '', NULL, '2026-10-22 02:34:35', '2026-10-22 02:34:35'),
(6, 22, NULL, 'rider', 'Alif', 'Alif', '016406404560', '', NULL, '2026-11-01 19:28:04', '2026-11-01 19:32:25'),
(8, 21, 1, 'rider', 'AmarShop Delivery', 'Alif', '016406404560', '', NULL, '2026-11-02 22:21:00', '2026-11-02 22:21:00'),
(9, 115, 1, 'rider', 'AmarShop Delivery', 'Alif', '016406404560', '', NULL, '2026-11-16 06:54:03', '2026-11-16 06:54:03'),
(10, 23, 1, 'rider', 'AmarShop Delivery', 'Alif', '016406404560', '', NULL, '2026-11-16 06:54:03', '2026-11-16 06:54:03'),
(11, 124, 1, 'rider', 'AmarShop Delivery', 'Alif', '016406404560', '', NULL, '2026-11-16 21:54:27', '2026-11-16 21:54:27');

-- --------------------------------------------------------

--
-- Table structure for table `order_issues`
--

CREATE TABLE `order_issues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_by_admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `issue_type` varchar(24) NOT NULL,
  `shipment_number` varchar(32) DEFAULT NULL,
  `shipment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_issues`
--

INSERT INTO `order_issues` (`id`, `order_id`, `user_id`, `created_by_admin_id`, `issue_type`, `shipment_number`, `shipment_status`, `message`, `status`, `admin_note`, `created_at`, `resolved_at`) VALUES
(1, 20, 78, NULL, 'missing', NULL, 'delivered', 'fdskjf', 'resolved', NULL, '2026-11-01 16:47:54', '2026-11-01 19:19:39'),
(2, 20, 78, NULL, 'return', NULL, 'delivered', 'dsf', 'resolved', 'ok', '2026-11-01 16:48:00', '2026-11-01 19:17:49'),
(3, 20, 78, 79, 'replacement', NULL, 'pending', 'lkfdg', 'resolved', NULL, '2026-11-01 17:14:05', '2026-11-01 19:17:26'),
(4, 22, 78, 79, 'missing', '005B', 'packed', 'ak ps kom pathano hoiche tai aber pathano holo', 'resolved', NULL, '2026-11-01 19:37:35', '2026-11-02 22:32:36');

-- --------------------------------------------------------

--
-- Table structure for table `order_issue_delivery_assignments`
--

CREATE TABLE `order_issue_delivery_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `issue_id` bigint(20) UNSIGNED NOT NULL,
  `deliveryman_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_type` varchar(20) NOT NULL,
  `provider_name` varchar(120) NOT NULL,
  `contact_name` varchar(120) NOT NULL DEFAULT '',
  `contact_phone` varchar(40) NOT NULL DEFAULT '',
  `tracking_number` varchar(120) NOT NULL DEFAULT '',
  `notes` text DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_issue_delivery_assignments`
--

INSERT INTO `order_issue_delivery_assignments` (`id`, `issue_id`, `deliveryman_id`, `delivery_type`, `provider_name`, `contact_name`, `contact_phone`, `tracking_number`, `notes`, `assigned_at`, `updated_at`) VALUES
(1, 3, 1, 'rider', 'Alif', 'Alif', '016406404560', '', NULL, '2026-11-01 19:19:02', '2026-11-01 19:19:02'),
(3, 1, 1, 'rider', 'AmarShop Delivery', 'Alif', '016406404560', '', NULL, '2026-11-02 22:21:00', '2026-11-02 22:21:00'),
(4, 2, 1, 'rider', 'AmarShop Delivery', 'Alif', '016406404560', '', NULL, '2026-11-02 22:21:00', '2026-11-02 22:21:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_issue_items`
--

CREATE TABLE `order_issue_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `issue_id` bigint(20) UNSIGNED NOT NULL,
  `direction` varchar(8) NOT NULL,
  `source_order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_code` varchar(120) NOT NULL DEFAULT '',
  `size` varchar(100) NOT NULL DEFAULT '',
  `quantity` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_issue_items`
--

INSERT INTO `order_issue_items` (`id`, `issue_id`, `direction`, `source_order_item_id`, `product_id`, `product_name`, `product_code`, `size`, `quantity`) VALUES
(1, 4, 'give', 36, 33, 'Cotton shirt', 'shirt-123', 'XL', 1);

-- --------------------------------------------------------

--
-- Table structure for table `order_issue_shipment_history`
--

CREATE TABLE `order_issue_shipment_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `issue_id` bigint(20) UNSIGNED NOT NULL,
  `shipment_status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_issue_shipment_history`
--

INSERT INTO `order_issue_shipment_history` (`id`, `issue_id`, `shipment_status`, `created_at`) VALUES
(1, 1, 'packed', '2026-11-01 19:18:34'),
(2, 2, 'packed', '2026-11-01 19:18:45'),
(3, 4, 'pending', '2026-11-01 19:37:35'),
(4, 4, 'packed', '2026-11-01 19:39:07'),
(5, 1, 'delivering', '2026-11-02 22:21:00'),
(6, 2, 'delivering', '2026-11-02 22:21:01'),
(7, 4, 'delivering', '2026-11-02 22:21:02'),
(8, 2, 'delivered', '2026-11-02 22:22:43'),
(9, 1, 'delivered', '2026-11-02 22:27:44'),
(10, 4, 'packed', '2026-11-02 22:38:42');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_code` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `reseller_price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_code`, `size`, `quantity`, `price`, `reseller_price`, `total_price`) VALUES
(27, 19, 33, 'shirt-123', 'M', 1, 585.00, NULL, 585.00),
(28, 20, 33, 'shirt-123', 'M', 1, 585.00, NULL, 585.00),
(29, 20, 2, 'shirt-102', 'M', 2, 495.00, NULL, 990.00),
(30, 20, 34, 'shirt-124', 'XL', 1, 941.00, NULL, 941.00),
(31, 19, 32, 'shirt-122', 'L', 1, 495.00, NULL, 495.00),
(32, 21, 34, 'shirt-124', 'M', 1, 941.00, NULL, 941.00),
(33, 21, 33, 'shirt-123', 'M', 1, 585.00, NULL, 585.00),
(34, 21, 34, 'shirt-124', 'M', 3, 941.00, NULL, 2823.00),
(35, 22, 34, 'shirt-124', 'L', 1, 941.00, NULL, 941.00),
(36, 22, 33, 'shirt-123', 'XL', 2, 585.00, NULL, 1170.00),
(37, 22, 33, 'shirt-123', 'L', 1, 585.00, NULL, 585.00),
(38, 23, 33, 'shirt-123', 'M', 1, 585.00, NULL, 585.00),
(39, 23, 19, 'shirt-109', 'L', 1, 941.00, NULL, 941.00),
(40, 24, 26, 'shirt-116', 'L', 1, 585.00, NULL, 585.00),
(41, 25, 30, 'shirt-120', 'XXL', 1, 569.00, NULL, 569.00),
(42, 26, 33, 'shirt-123', 'L', 1, 585.00, NULL, 585.00),
(43, 27, 1, 'shirt-101', 'L', 1, 585.00, NULL, 585.00),
(44, 28, 1, 'shirt-101', 'L', 1, 585.00, NULL, 585.00),
(45, 29, 8, 'shirt-103', 'XL', 1, 585.00, NULL, 585.00),
(46, 30, 20, 'shirt-110', 'XL', 1, 569.00, NULL, 569.00),
(47, 31, 18, 'shirt-108', 'M', 1, 585.00, NULL, 585.00),
(48, 32, 20, 'shirt-110', 'XXL', 1, 569.00, NULL, 569.00),
(49, 33, 21, 'shirt-111', 'L', 1, 585.00, NULL, 585.00),
(50, 34, 36, 'shirt-201', 'M', 1, 495.00, NULL, 495.00),
(51, 35, 31, 'shirt-121', 'L', 1, 585.00, NULL, 585.00),
(52, 36, 31, 'shirt-121', 'L', 1, 585.00, NULL, 585.00),
(53, 37, 9, 'shirt-104', 'XXL', 1, 941.00, NULL, 941.00),
(54, 38, 18, 'shirt-108', 'XL', 1, 585.00, NULL, 585.00),
(55, 39, 33, 'shirt-123', 'L', 1, 585.00, NULL, 585.00),
(56, 40, 29, 'shirt-119', 'XL', 1, 941.00, NULL, 941.00),
(57, 41, 20, 'shirt-110', 'XL', 1, 569.00, NULL, 569.00),
(58, 42, 21, 'shirt-111', 'L', 1, 585.00, NULL, 585.00),
(59, 43, 23, 'shirt-113', 'XL', 1, 585.00, NULL, 585.00),
(60, 44, 1, 'shirt-101', 'L', 1, 585.00, NULL, 585.00),
(61, 45, 24, 'shirt-114', 'XL', 1, 941.00, NULL, 941.00),
(62, 46, 29, 'shirt-119', 'XL', 1, 941.00, NULL, 941.00),
(63, 47, 19, 'shirt-109', 'L', 1, 941.00, NULL, 941.00),
(64, 48, 8, 'shirt-103', 'XL', 1, 585.00, NULL, 585.00),
(65, 49, 25, 'shirt-115', 'L', 1, 569.00, NULL, 569.00),
(66, 50, 34, 'shirt-124', 'XXL', 1, 941.00, NULL, 941.00),
(67, 51, 29, 'shirt-119', 'XXL', 1, 941.00, NULL, 941.00),
(68, 52, 29, 'shirt-119', 'L', 1, 941.00, NULL, 941.00),
(69, 53, 24, 'shirt-114', 'M', 1, 941.00, NULL, 941.00),
(70, 54, 16, 'shirt-106', 'L', 1, 585.00, NULL, 585.00),
(71, 55, 31, 'shirt-121', 'L', 1, 585.00, NULL, 585.00),
(72, 56, 26, 'shirt-116', 'L', 1, 585.00, NULL, 585.00),
(73, 57, 26, 'shirt-116', 'L', 1, 585.00, NULL, 585.00),
(74, 58, 25, 'shirt-115', 'XXL', 1, 569.00, NULL, 569.00),
(75, 59, 24, 'shirt-114', 'L', 1, 941.00, NULL, 941.00),
(76, 60, 8, 'shirt-103', 'M', 1, 585.00, NULL, 585.00),
(77, 61, 36, 'shirt-201', 'M', 1, 495.00, NULL, 495.00),
(78, 62, 31, 'shirt-121', 'L', 1, 585.00, NULL, 585.00),
(79, 63, 10, 'shirt-105', 'XL', 1, 569.00, NULL, 569.00),
(80, 64, 1, 'shirt-101', 'L', 1, 585.00, NULL, 585.00),
(81, 65, 8, 'shirt-103', 'M', 1, 585.00, NULL, 585.00),
(82, 66, 30, 'shirt-120', 'M', 1, 569.00, NULL, 569.00),
(83, 67, 29, 'shirt-119', 'XXL', 1, 941.00, NULL, 941.00),
(84, 68, 29, 'shirt-119', 'XXL', 1, 941.00, NULL, 941.00),
(85, 69, 30, 'shirt-120', 'XXL', 1, 569.00, NULL, 569.00),
(86, 70, 21, 'shirt-111', 'L', 1, 585.00, NULL, 585.00),
(87, 71, 8, 'shirt-103', 'XL', 1, 585.00, NULL, 585.00),
(88, 72, 9, 'shirt-104', 'M', 1, 941.00, NULL, 941.00),
(89, 73, 18, 'shirt-108', 'XL', 1, 585.00, NULL, 585.00),
(90, 74, 23, 'shirt-113', 'L', 1, 585.00, NULL, 585.00),
(91, 75, 21, 'shirt-111', 'L', 1, 585.00, NULL, 585.00),
(92, 76, 9, 'shirt-104', 'XXL', 1, 941.00, NULL, 941.00),
(93, 77, 21, 'shirt-111', 'L', 1, 585.00, NULL, 585.00),
(94, 78, 30, 'shirt-120', 'XXL', 1, 569.00, NULL, 569.00),
(95, 79, 29, 'shirt-119', 'L', 1, 941.00, NULL, 941.00),
(96, 80, 9, 'shirt-104', 'M', 1, 941.00, NULL, 941.00),
(97, 81, 23, 'shirt-113', 'XL', 1, 585.00, NULL, 585.00),
(98, 82, 1, 'shirt-101', 'L', 1, 585.00, NULL, 585.00),
(99, 83, 24, 'shirt-114', 'M', 1, 941.00, NULL, 941.00),
(100, 84, 24, 'shirt-114', 'L', 1, 941.00, NULL, 941.00),
(101, 85, 16, 'shirt-106', 'L', 1, 585.00, NULL, 585.00),
(102, 86, 34, 'shirt-124', 'M', 1, 941.00, NULL, 941.00),
(103, 87, 29, 'shirt-119', 'XL', 1, 941.00, NULL, 941.00),
(104, 88, 21, 'shirt-111', 'L', 1, 585.00, NULL, 585.00),
(105, 89, 30, 'shirt-120', 'XXL', 1, 569.00, NULL, 569.00),
(106, 90, 28, 'shirt-118', 'L', 1, 585.00, NULL, 585.00),
(107, 91, 28, 'shirt-118', 'L', 1, 585.00, NULL, 585.00),
(108, 92, 36, 'shirt-201', 'XL', 1, 495.00, NULL, 495.00),
(109, 93, 24, 'shirt-114', 'L', 1, 941.00, NULL, 941.00),
(110, 94, 36, 'shirt-201', 'L', 1, 495.00, NULL, 495.00),
(111, 95, 28, 'shirt-118', 'XL', 1, 585.00, NULL, 585.00),
(112, 96, 9, 'shirt-104', 'XXL', 1, 941.00, NULL, 941.00),
(113, 97, 19, 'shirt-109', 'XXL', 1, 941.00, NULL, 941.00),
(114, 98, 30, 'shirt-120', 'XL', 1, 569.00, NULL, 569.00),
(115, 99, 34, 'shirt-124', 'XXL', 1, 941.00, NULL, 941.00),
(116, 100, 8, 'shirt-103', 'M', 1, 585.00, NULL, 585.00),
(117, 101, 26, 'shirt-116', 'L', 1, 585.00, NULL, 585.00),
(118, 102, 8, 'shirt-103', 'L', 1, 585.00, NULL, 585.00),
(119, 103, 29, 'shirt-119', 'L', 1, 941.00, NULL, 941.00),
(120, 104, 9, 'shirt-104', 'L', 1, 941.00, NULL, 941.00),
(121, 105, 24, 'shirt-114', 'XL', 1, 941.00, NULL, 941.00),
(122, 106, 10, 'shirt-105', 'L', 1, 569.00, NULL, 569.00),
(123, 107, 21, 'shirt-111', 'L', 1, 585.00, NULL, 585.00),
(124, 108, 9, 'shirt-104', 'M', 1, 941.00, NULL, 941.00),
(125, 109, 20, 'shirt-110', 'L', 1, 569.00, NULL, 569.00),
(126, 110, 36, 'shirt-201', 'L', 1, 495.00, NULL, 495.00),
(127, 111, 34, 'shirt-124', 'XXL', 1, 941.00, NULL, 941.00),
(128, 112, 20, 'shirt-110', 'L', 1, 569.00, NULL, 569.00),
(129, 113, 18, 'shirt-108', 'XL', 1, 585.00, NULL, 585.00),
(130, 114, 34, 'shirt-124', 'M', 1, 941.00, NULL, 941.00),
(131, 115, 29, 'shirt-119', 'XXL', 1, 941.00, NULL, 941.00),
(132, 116, 8, 'shirt-103', 'M', 1, 585.00, NULL, 585.00),
(133, 117, 18, 'shirt-108', 'M', 1, 585.00, NULL, 585.00),
(134, 118, 29, 'shirt-119', 'XL', 1, 941.00, NULL, 941.00),
(135, 119, 19, 'shirt-109', 'M', 1, 941.00, NULL, 941.00),
(136, 120, 23, 'shirt-113', 'XL', 1, 585.00, NULL, 585.00),
(137, 121, 20, 'shirt-110', 'XL', 1, 569.00, NULL, 569.00),
(138, 122, 21, 'shirt-111', 'L', 1, 585.00, NULL, 585.00),
(139, 123, 10, 'shirt-105', 'XL', 1, 569.00, NULL, 569.00),
(140, 124, 30, 'shirt-120', 'L', 1, 569.00, 300.00, 569.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_number_sequences`
--

CREATE TABLE `order_number_sequences` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `next_number` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_number_sequences`
--

INSERT INTO `order_number_sequences` (`id`, `next_number`) VALUES
(1, 108);

-- --------------------------------------------------------

--
-- Table structure for table `order_quantity`
--

CREATE TABLE `order_quantity` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bag_id` bigint(20) UNSIGNED NOT NULL,
  `shipment_type` varchar(12) NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `issue_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shipment_number` varchar(32) NOT NULL,
  `customer_name` varchar(180) NOT NULL DEFAULT '',
  `customer_phone` varchar(40) NOT NULL DEFAULT '',
  `address` text NOT NULL,
  `expected_cash` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'in_bag',
  `cash_received` decimal(12,2) DEFAULT NULL,
  `cash_receipt_no` varchar(40) DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_quantity`
--

INSERT INTO `order_quantity` (`id`, `bag_id`, `shipment_type`, `order_id`, `issue_id`, `shipment_number`, `customer_name`, `customer_phone`, `address`, `expected_cash`, `status`, `cash_received`, `cash_receipt_no`, `delivered_at`, `returned_at`, `created_at`) VALUES
(1, 2, 'order', 21, NULL, '004A', 'name', '01745213653', 'er, 60', 4409.00, 'delivered', 4409.00, 'RCPT-261102-000001', '2026-11-02 22:26:34', NULL, '2026-11-02 22:10:42'),
(2, 2, 'issue', NULL, 1, '003A', 'customar', '01609069539', 'House-10/11, Road-12, Mirpur-1, Dhaka, 60', 0.00, 'delivered', NULL, NULL, '2026-11-02 22:27:44', NULL, '2026-11-02 22:10:42'),
(3, 2, 'issue', NULL, 2, '003B', 'customar', '01609069539', 'House-10/11, Road-12, Mirpur-1, Dhaka, 60', 0.00, 'delivered', NULL, NULL, '2026-11-02 22:22:43', NULL, '2026-11-02 22:10:42'),
(4, 2, 'issue', NULL, 4, '005B', 'customar', '01609069539', 'House-10/11, Road-12, Mirpur-1, Dhaka, 60', 0.00, 'returned', NULL, NULL, NULL, '2026-11-02 22:38:42', '2026-11-02 22:10:42'),
(5, 3, 'order', 115, NULL, '098A', 'DEMO Customer 092', '0000000092', 'DEMO ONLY - Address 092, Inside Dhaka', 1001.00, 'delivered', 1001.00, 'RCPT-261116-000005', '2026-11-16 06:54:11', NULL, '2026-11-16 00:17:30'),
(6, 3, 'order', 23, NULL, '006A', 'Mst Mariya Akter', '01949645050', 'hose -3 d', 1586.00, 'delivering', NULL, NULL, NULL, NULL, '2026-11-16 00:17:30'),
(7, 4, 'order', 124, NULL, '107A', 'Mst Mariya Akter', '01949645050', 'hose -3 d', 629.00, 'delivered', 629.00, 'RCPT-261116-000007', '2026-11-16 21:56:01', NULL, '2026-11-16 21:54:27');

-- --------------------------------------------------------

--
-- Table structure for table `order_requests`
--

CREATE TABLE `order_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `request_type` varchar(24) NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `size` varchar(100) NOT NULL DEFAULT '',
  `quantity` int(10) UNSIGNED DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_requests`
--

INSERT INTO `order_requests` (`id`, `order_id`, `user_id`, `request_type`, `product_id`, `size`, `quantity`, `message`, `status`, `admin_note`, `created_at`, `resolved_at`) VALUES
(1, 17, 1, 'add_item', 35, 'XL', 2, 'Please add Primium Cotton Shirt (shirt-125) - XL, quantity 2.', 'approved', 'ok', '2026-10-21 22:34:42', '2026-10-21 22:58:26'),
(2, 18, 1, 'add_item', 32, 'L', 2, 'Please add Primium Cotton Shirt (shirt-122) - L, quantity 2.', 'rejected', 'sorry stock nai', '2026-10-21 22:50:53', '2026-10-21 23:00:02'),
(3, 20, 78, 'add_item', 34, 'XL', 1, 'Please add Primium Cotton Shirt (shirt-124) - XL, quantity 1.', 'approved', 'ok', '2026-10-22 00:04:00', '2026-10-22 00:06:06'),
(4, 20, 78, 'update', NULL, '', NULL, 'aro akta product add kora lagbe', 'approved', 'ok', '2026-10-22 00:04:30', '2026-10-22 00:05:55'),
(5, 19, 78, 'add_item', 32, 'L', 1, 'Please add Primium Cotton Shirt (shirt-122) - L, quantity 1.', 'approved', 'ok add kore deya holo', '2026-10-22 02:05:30', '2026-10-22 02:06:13'),
(6, 21, 85, 'add_item', 34, 'M', 3, 'Please add Primium Cotton Shirt (shirt-124) - M, quantity 3.', 'approved', '', '2026-10-24 22:10:21', '2026-10-24 22:10:38'),
(7, 22, 78, 'add_item', 33, 'L', 1, 'Please add Cotton shirt (shirt-123) - L, quantity 1.', 'approved', 'ok done', '2026-11-01 18:47:49', '2026-11-01 19:00:46'),
(8, 22, 78, 'update', NULL, '', NULL, 'akta shirt bad dite chassi', 'rejected', '', '2026-11-01 18:48:18', '2026-11-01 19:16:30'),
(9, 23, 84, 'add_item', 32, 'L', 1, 'Please add Primium Cotton Shirt (shirt-122) - L, quantity 1.', 'pending', NULL, '2026-11-15 19:43:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_request_messages`
--

CREATE TABLE `order_request_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `sender_type` varchar(16) NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_request_messages`
--

INSERT INTO `order_request_messages` (`id`, `request_id`, `sender_type`, `sender_id`, `message`, `created_at`) VALUES
(1, 7, 'admin', 79, 'are you sure?', '2026-11-01 18:49:06'),
(2, 7, 'user', 78, 'ji', '2026-11-01 18:52:28'),
(3, 8, 'admin', 79, 'konta ?', '2026-11-01 19:01:14'),
(4, 8, 'admin', 79, 'ki holo', '2026-11-01 19:14:49'),
(5, 8, 'user', 78, '941 tk ta', '2026-11-01 19:16:01'),
(6, 8, 'admin', 79, 'na akhon packed hoiye geche', '2026-11-01 19:16:28'),
(7, 9, 'admin', 79, 'sure?', '2026-11-15 21:11:18'),
(8, 9, 'user', 84, 'ji confirm', '2026-11-15 21:11:53');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(32) NOT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_name` varchar(191) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `admin_id`, `admin_name`, `created_at`) VALUES
(18, 19, 'placed', NULL, NULL, '2026-10-21 23:31:50'),
(19, 19, 'processing', NULL, NULL, '2026-10-21 23:31:50'),
(20, 20, 'placed', NULL, NULL, '2026-10-21 23:32:59'),
(21, 20, 'processing', NULL, NULL, '2026-10-21 23:32:59'),
(22, 19, 'confirmed', NULL, NULL, '2026-10-21 23:43:21'),
(23, 19, 'processing', NULL, NULL, '2026-10-21 23:43:31'),
(24, 19, 'confirmed', NULL, NULL, '2026-10-21 23:44:10'),
(25, 19, 'processing', NULL, NULL, '2026-10-22 00:28:25'),
(26, 20, 'confirmed', NULL, NULL, '2026-10-22 00:29:49'),
(28, 20, 'picked', NULL, NULL, '2026-10-22 01:57:19'),
(29, 20, 'packed', NULL, NULL, '2026-10-22 01:57:33'),
(30, 20, 'picked', NULL, NULL, '2026-10-22 01:58:24'),
(31, 20, 'confirmed', NULL, NULL, '2026-10-22 01:58:49'),
(32, 20, 'picked', NULL, NULL, '2026-10-22 01:59:02'),
(33, 20, 'packed', NULL, NULL, '2026-10-22 01:59:04'),
(34, 20, 'delivering', NULL, NULL, '2026-10-22 02:00:14'),
(35, 19, 'confirmed', NULL, NULL, '2026-10-22 02:34:18'),
(36, 19, 'picked', NULL, NULL, '2026-10-22 02:34:20'),
(37, 19, 'packed', NULL, NULL, '2026-10-22 02:34:21'),
(38, 19, 'delivering', NULL, NULL, '2026-10-22 02:34:40'),
(39, 20, 'delivered', NULL, NULL, '2026-10-23 21:30:31'),
(40, 21, 'placed', NULL, NULL, '2026-10-24 22:01:22'),
(41, 21, 'processing', NULL, NULL, '2026-10-24 22:01:22'),
(42, 22, 'placed', NULL, NULL, '2026-11-01 18:47:18'),
(43, 22, 'processing', NULL, NULL, '2026-11-01 18:47:18'),
(44, 22, 'confirmed', NULL, NULL, '2026-11-01 19:19:51'),
(45, 22, 'picked', NULL, NULL, '2026-11-01 19:19:53'),
(46, 22, 'packed', NULL, NULL, '2026-11-01 19:19:57'),
(47, 22, 'delivering', NULL, NULL, '2026-11-01 19:32:31'),
(48, 22, 'delivered', NULL, NULL, '2026-11-01 19:32:45'),
(49, 21, 'confirmed', NULL, NULL, '2026-11-02 06:37:31'),
(50, 21, 'picked', NULL, NULL, '2026-11-02 06:37:34'),
(51, 21, 'packed', NULL, NULL, '2026-11-02 06:37:40'),
(52, 21, 'inbag', NULL, NULL, '2026-11-02 22:10:42'),
(53, 21, 'delivering', NULL, NULL, '2026-11-02 22:21:00'),
(54, 21, 'delivered', NULL, NULL, '2026-11-02 22:26:34'),
(55, 23, 'placed', NULL, NULL, '2026-11-15 19:32:04'),
(56, 23, 'processing', NULL, NULL, '2026-11-15 19:32:04'),
(57, 23, 'confirmed', NULL, NULL, '2026-11-15 21:09:18'),
(58, 24, 'placed', NULL, NULL, '2026-11-08 12:10:23'),
(59, 24, 'processing', NULL, NULL, '2026-11-08 12:12:24'),
(60, 25, 'placed', NULL, NULL, '2026-11-07 02:04:49'),
(61, 25, 'processing', NULL, NULL, '2026-11-07 02:06:41'),
(62, 26, 'placed', NULL, NULL, '2026-11-09 08:41:14'),
(63, 26, 'processing', NULL, NULL, '2026-11-09 08:44:14'),
(64, 27, 'placed', NULL, NULL, '2026-11-13 23:51:15'),
(65, 27, 'processing', NULL, NULL, '2026-11-13 23:52:04'),
(66, 28, 'placed', NULL, NULL, '2026-11-05 08:37:11'),
(67, 28, 'processing', NULL, NULL, '2026-11-05 08:38:55'),
(68, 29, 'placed', NULL, NULL, '2026-11-07 22:17:08'),
(69, 29, 'processing', NULL, NULL, '2026-11-07 22:19:12'),
(70, 30, 'placed', NULL, NULL, '2026-11-10 01:06:12'),
(71, 30, 'processing', NULL, NULL, '2026-11-10 01:08:08'),
(72, 31, 'placed', NULL, NULL, '2026-11-12 21:16:08'),
(73, 31, 'processing', NULL, NULL, '2026-11-12 21:18:25'),
(74, 32, 'placed', NULL, NULL, '2026-11-10 16:46:49'),
(75, 32, 'processing', NULL, NULL, '2026-11-10 16:48:34'),
(76, 33, 'placed', NULL, NULL, '2026-11-11 06:47:14'),
(77, 33, 'processing', NULL, NULL, '2026-11-11 06:48:35'),
(78, 34, 'placed', NULL, NULL, '2026-11-09 21:46:41'),
(79, 34, 'processing', NULL, NULL, '2026-11-09 21:48:45'),
(80, 35, 'placed', NULL, NULL, '2026-11-07 16:38:13'),
(81, 35, 'processing', NULL, NULL, '2026-11-07 16:39:22'),
(82, 36, 'placed', NULL, NULL, '2026-11-10 12:00:16'),
(83, 36, 'processing', NULL, NULL, '2026-11-10 12:02:09'),
(84, 37, 'placed', NULL, NULL, '2026-11-03 02:56:32'),
(85, 37, 'processing', NULL, NULL, '2026-11-03 02:58:13'),
(86, 38, 'placed', NULL, NULL, '2026-11-10 15:18:38'),
(87, 38, 'processing', NULL, NULL, '2026-11-10 15:20:27'),
(88, 39, 'placed', NULL, NULL, '2026-11-02 10:17:40'),
(89, 39, 'processing', NULL, NULL, '2026-11-02 10:19:02'),
(90, 40, 'placed', NULL, NULL, '2026-11-02 07:12:18'),
(91, 40, 'processing', NULL, NULL, '2026-11-02 07:14:52'),
(92, 41, 'placed', NULL, NULL, '2026-11-13 04:37:59'),
(93, 41, 'processing', NULL, NULL, '2026-11-13 04:39:50'),
(94, 42, 'placed', NULL, NULL, '2026-11-02 10:08:57'),
(95, 42, 'processing', NULL, NULL, '2026-11-02 10:10:18'),
(96, 43, 'placed', NULL, NULL, '2026-11-10 04:16:18'),
(97, 43, 'processing', NULL, NULL, '2026-11-10 04:17:24'),
(98, 44, 'placed', NULL, NULL, '2026-11-04 11:00:05'),
(99, 44, 'processing', NULL, NULL, '2026-11-04 11:03:05'),
(100, 45, 'placed', NULL, NULL, '2026-11-15 01:18:57'),
(101, 45, 'processing', NULL, NULL, '2026-11-15 01:21:10'),
(102, 46, 'placed', NULL, NULL, '2026-11-06 17:09:59'),
(103, 46, 'processing', NULL, NULL, '2026-11-06 17:12:50'),
(104, 47, 'placed', NULL, NULL, '2026-11-14 13:40:26'),
(105, 47, 'processing', NULL, NULL, '2026-11-14 13:41:58'),
(106, 48, 'placed', NULL, NULL, '2026-11-12 06:24:30'),
(107, 48, 'processing', NULL, NULL, '2026-11-12 06:25:18'),
(108, 49, 'placed', NULL, NULL, '2026-11-09 22:17:53'),
(109, 49, 'processing', NULL, NULL, '2026-11-09 22:18:44'),
(110, 50, 'placed', NULL, NULL, '2026-11-05 06:31:31'),
(111, 50, 'processing', NULL, NULL, '2026-11-05 06:32:37'),
(112, 51, 'placed', NULL, NULL, '2026-11-04 01:51:49'),
(113, 51, 'processing', NULL, NULL, '2026-11-04 01:54:24'),
(114, 52, 'placed', NULL, NULL, '2026-11-02 13:58:26'),
(115, 52, 'processing', NULL, NULL, '2026-11-02 14:00:29'),
(116, 53, 'placed', NULL, NULL, '2026-11-09 23:48:55'),
(117, 53, 'processing', NULL, NULL, '2026-11-09 23:50:38'),
(118, 54, 'placed', NULL, NULL, '2026-11-06 07:26:51'),
(119, 54, 'processing', NULL, NULL, '2026-11-06 07:28:53'),
(120, 55, 'placed', NULL, NULL, '2026-11-02 02:33:19'),
(121, 55, 'processing', NULL, NULL, '2026-11-02 02:35:42'),
(122, 56, 'placed', NULL, NULL, '2026-11-13 03:39:41'),
(123, 56, 'processing', NULL, NULL, '2026-11-13 03:42:24'),
(124, 57, 'placed', NULL, NULL, '2026-11-10 02:02:03'),
(125, 57, 'processing', NULL, NULL, '2026-11-10 02:03:57'),
(126, 58, 'placed', NULL, NULL, '2026-11-13 07:05:45'),
(127, 58, 'processing', NULL, NULL, '2026-11-13 07:06:39'),
(128, 59, 'placed', NULL, NULL, '2026-11-11 03:23:48'),
(129, 59, 'processing', NULL, NULL, '2026-11-11 03:26:29'),
(130, 60, 'placed', NULL, NULL, '2026-11-04 19:14:25'),
(131, 60, 'processing', NULL, NULL, '2026-11-04 19:17:07'),
(132, 61, 'placed', NULL, NULL, '2026-11-13 12:07:57'),
(133, 61, 'processing', NULL, NULL, '2026-11-13 12:10:10'),
(134, 62, 'placed', NULL, NULL, '2026-11-10 16:39:27'),
(135, 62, 'processing', NULL, NULL, '2026-11-10 16:40:54'),
(136, 63, 'placed', NULL, NULL, '2026-11-13 13:22:54'),
(137, 63, 'processing', NULL, NULL, '2026-11-13 13:24:20'),
(138, 64, 'placed', NULL, NULL, '2026-11-09 09:40:15'),
(139, 64, 'processing', NULL, NULL, '2026-11-09 09:42:26'),
(140, 64, 'confirmed', NULL, NULL, '2026-11-09 09:44:03'),
(141, 65, 'placed', NULL, NULL, '2026-11-05 12:43:04'),
(142, 65, 'processing', NULL, NULL, '2026-11-05 12:44:30'),
(143, 65, 'confirmed', NULL, NULL, '2026-11-05 12:46:40'),
(144, 66, 'placed', NULL, NULL, '2026-11-05 23:12:37'),
(145, 66, 'processing', NULL, NULL, '2026-11-05 23:13:38'),
(146, 66, 'confirmed', NULL, NULL, '2026-11-05 23:15:30'),
(147, 67, 'placed', NULL, NULL, '2026-11-11 18:08:29'),
(148, 67, 'processing', NULL, NULL, '2026-11-11 18:10:35'),
(149, 67, 'confirmed', NULL, NULL, '2026-11-11 18:13:35'),
(150, 68, 'placed', NULL, NULL, '2026-11-03 15:05:23'),
(151, 68, 'processing', NULL, NULL, '2026-11-03 15:07:20'),
(152, 68, 'confirmed', NULL, NULL, '2026-11-03 15:09:41'),
(153, 69, 'placed', NULL, NULL, '2026-11-09 04:19:49'),
(154, 69, 'processing', NULL, NULL, '2026-11-09 04:21:34'),
(155, 69, 'confirmed', NULL, NULL, '2026-11-09 04:23:28'),
(156, 70, 'placed', NULL, NULL, '2026-11-03 01:35:12'),
(157, 70, 'processing', NULL, NULL, '2026-11-03 01:36:20'),
(158, 70, 'confirmed', NULL, NULL, '2026-11-03 01:38:09'),
(159, 71, 'placed', NULL, NULL, '2026-11-07 21:27:47'),
(160, 71, 'processing', NULL, NULL, '2026-11-07 21:30:32'),
(161, 71, 'confirmed', NULL, NULL, '2026-11-07 21:32:34'),
(162, 72, 'placed', NULL, NULL, '2026-11-04 02:15:35'),
(163, 72, 'processing', NULL, NULL, '2026-11-04 02:17:16'),
(164, 72, 'confirmed', NULL, NULL, '2026-11-04 02:19:08'),
(165, 73, 'placed', NULL, NULL, '2026-11-07 11:00:59'),
(166, 73, 'processing', NULL, NULL, '2026-11-07 11:03:02'),
(167, 73, 'confirmed', NULL, NULL, '2026-11-07 11:03:58'),
(168, 74, 'placed', NULL, NULL, '2026-11-15 07:49:49'),
(169, 74, 'processing', NULL, NULL, '2026-11-15 07:52:34'),
(170, 74, 'confirmed', NULL, NULL, '2026-11-15 07:54:28'),
(171, 75, 'placed', NULL, NULL, '2026-11-14 17:13:18'),
(172, 75, 'processing', NULL, NULL, '2026-11-14 17:14:16'),
(173, 75, 'confirmed', NULL, NULL, '2026-11-14 17:15:38'),
(174, 76, 'placed', NULL, NULL, '2026-11-06 08:00:16'),
(175, 76, 'processing', NULL, NULL, '2026-11-06 08:02:16'),
(176, 76, 'confirmed', NULL, NULL, '2026-11-06 08:04:42'),
(177, 77, 'placed', NULL, NULL, '2026-11-08 05:28:42'),
(178, 77, 'processing', NULL, NULL, '2026-11-08 05:31:37'),
(179, 77, 'confirmed', NULL, NULL, '2026-11-08 05:33:38'),
(180, 78, 'placed', NULL, NULL, '2026-11-05 12:58:28'),
(181, 78, 'processing', NULL, NULL, '2026-11-05 13:00:00'),
(182, 78, 'confirmed', NULL, NULL, '2026-11-05 13:02:45'),
(183, 79, 'placed', NULL, NULL, '2026-11-02 17:17:34'),
(184, 79, 'processing', NULL, NULL, '2026-11-02 17:18:47'),
(185, 79, 'confirmed', NULL, NULL, '2026-11-02 17:20:24'),
(186, 80, 'placed', NULL, NULL, '2026-11-11 02:03:54'),
(187, 80, 'processing', NULL, NULL, '2026-11-11 02:06:05'),
(188, 80, 'confirmed', NULL, NULL, '2026-11-11 02:06:59'),
(189, 81, 'placed', NULL, NULL, '2026-11-08 09:08:39'),
(190, 81, 'processing', NULL, NULL, '2026-11-08 09:10:11'),
(191, 81, 'confirmed', NULL, NULL, '2026-11-08 09:12:05'),
(192, 82, 'placed', NULL, NULL, '2026-11-08 03:41:50'),
(193, 82, 'processing', NULL, NULL, '2026-11-08 03:44:35'),
(194, 82, 'confirmed', NULL, NULL, '2026-11-08 03:45:43'),
(195, 83, 'placed', NULL, NULL, '2026-11-04 03:13:30'),
(196, 83, 'processing', NULL, NULL, '2026-11-04 03:14:58'),
(197, 83, 'confirmed', NULL, NULL, '2026-11-04 03:17:39'),
(198, 84, 'placed', NULL, NULL, '2026-11-14 05:22:44'),
(199, 84, 'processing', NULL, NULL, '2026-11-14 05:23:58'),
(200, 84, 'confirmed', NULL, NULL, '2026-11-14 05:24:45'),
(201, 85, 'placed', NULL, NULL, '2026-11-15 08:06:36'),
(202, 85, 'processing', NULL, NULL, '2026-11-15 08:07:59'),
(203, 85, 'confirmed', NULL, NULL, '2026-11-15 08:10:22'),
(204, 86, 'placed', NULL, NULL, '2026-11-03 07:49:53'),
(205, 86, 'processing', NULL, NULL, '2026-11-03 07:51:22'),
(206, 86, 'confirmed', NULL, NULL, '2026-11-03 07:53:50'),
(207, 87, 'placed', NULL, NULL, '2026-11-12 01:07:39'),
(208, 87, 'processing', NULL, NULL, '2026-11-12 01:08:47'),
(209, 87, 'confirmed', NULL, NULL, '2026-11-12 01:10:25'),
(210, 88, 'placed', NULL, NULL, '2026-11-13 20:38:12'),
(211, 88, 'processing', NULL, NULL, '2026-11-13 20:39:05'),
(212, 88, 'confirmed', NULL, NULL, '2026-11-13 20:40:53'),
(213, 89, 'placed', NULL, NULL, '2026-11-02 07:08:36'),
(214, 89, 'processing', NULL, NULL, '2026-11-02 07:10:28'),
(215, 89, 'confirmed', NULL, NULL, '2026-11-02 07:12:30'),
(216, 90, 'placed', NULL, NULL, '2026-11-10 03:28:18'),
(217, 90, 'processing', NULL, NULL, '2026-11-10 03:29:26'),
(218, 90, 'confirmed', NULL, NULL, '2026-11-10 03:31:37'),
(219, 91, 'placed', NULL, NULL, '2026-11-10 20:37:58'),
(220, 91, 'processing', NULL, NULL, '2026-11-10 20:39:49'),
(221, 91, 'confirmed', NULL, NULL, '2026-11-10 20:42:44'),
(222, 92, 'placed', NULL, NULL, '2026-11-13 00:14:58'),
(223, 92, 'processing', NULL, NULL, '2026-11-13 00:16:28'),
(224, 92, 'confirmed', NULL, NULL, '2026-11-13 00:19:02'),
(225, 93, 'placed', NULL, NULL, '2026-11-13 00:27:09'),
(226, 93, 'processing', NULL, NULL, '2026-11-13 00:28:32'),
(227, 93, 'confirmed', NULL, NULL, '2026-11-13 00:31:10'),
(228, 94, 'placed', NULL, NULL, '2026-11-14 03:03:40'),
(229, 94, 'processing', NULL, NULL, '2026-11-14 03:05:56'),
(230, 94, 'confirmed', NULL, NULL, '2026-11-14 03:07:40'),
(231, 94, 'picked', NULL, NULL, '2026-11-14 03:09:52'),
(232, 95, 'placed', NULL, NULL, '2026-11-04 14:36:49'),
(233, 95, 'processing', NULL, NULL, '2026-11-04 14:39:40'),
(234, 95, 'confirmed', NULL, NULL, '2026-11-04 14:42:19'),
(235, 95, 'picked', NULL, NULL, '2026-11-04 14:44:28'),
(236, 96, 'placed', NULL, NULL, '2026-11-08 07:03:22'),
(237, 96, 'processing', NULL, NULL, '2026-11-08 07:05:57'),
(238, 96, 'confirmed', NULL, NULL, '2026-11-08 07:06:53'),
(239, 96, 'picked', NULL, NULL, '2026-11-08 07:08:22'),
(240, 97, 'placed', NULL, NULL, '2026-11-11 02:01:18'),
(241, 97, 'processing', NULL, NULL, '2026-11-11 02:03:01'),
(242, 97, 'confirmed', NULL, NULL, '2026-11-11 02:04:19'),
(243, 97, 'picked', NULL, NULL, '2026-11-11 02:05:32'),
(244, 98, 'placed', NULL, NULL, '2026-11-12 02:27:39'),
(245, 98, 'processing', NULL, NULL, '2026-11-12 02:29:41'),
(246, 98, 'confirmed', NULL, NULL, '2026-11-12 02:32:23'),
(247, 98, 'picked', NULL, NULL, '2026-11-12 02:34:56'),
(248, 99, 'placed', NULL, NULL, '2026-11-10 03:29:28'),
(249, 99, 'processing', NULL, NULL, '2026-11-10 03:31:56'),
(250, 99, 'confirmed', NULL, NULL, '2026-11-10 03:34:07'),
(251, 99, 'picked', NULL, NULL, '2026-11-10 03:36:02'),
(252, 100, 'placed', NULL, NULL, '2026-11-09 09:49:31'),
(253, 100, 'processing', NULL, NULL, '2026-11-09 09:51:06'),
(254, 100, 'confirmed', NULL, NULL, '2026-11-09 09:52:38'),
(255, 100, 'picked', NULL, NULL, '2026-11-09 09:55:12'),
(256, 101, 'placed', NULL, NULL, '2026-11-13 06:18:18'),
(257, 101, 'processing', NULL, NULL, '2026-11-13 06:20:57'),
(258, 101, 'confirmed', NULL, NULL, '2026-11-13 06:23:52'),
(259, 101, 'picked', NULL, NULL, '2026-11-13 06:24:44'),
(260, 102, 'placed', NULL, NULL, '2026-11-12 14:50:32'),
(261, 102, 'processing', NULL, NULL, '2026-11-12 14:53:32'),
(262, 102, 'confirmed', NULL, NULL, '2026-11-12 14:56:29'),
(263, 102, 'picked', NULL, NULL, '2026-11-12 14:57:25'),
(264, 103, 'placed', NULL, NULL, '2026-11-14 21:15:42'),
(265, 103, 'processing', NULL, NULL, '2026-11-14 21:16:39'),
(266, 103, 'confirmed', NULL, NULL, '2026-11-14 21:19:18'),
(267, 103, 'picked', NULL, NULL, '2026-11-14 21:20:50'),
(268, 104, 'placed', NULL, NULL, '2026-11-13 04:28:23'),
(269, 104, 'processing', NULL, NULL, '2026-11-13 04:30:43'),
(270, 104, 'confirmed', NULL, NULL, '2026-11-13 04:33:07'),
(271, 104, 'picked', NULL, NULL, '2026-11-13 04:35:58'),
(272, 105, 'placed', NULL, NULL, '2026-11-08 17:53:11'),
(273, 105, 'processing', NULL, NULL, '2026-11-08 17:56:05'),
(274, 105, 'confirmed', NULL, NULL, '2026-11-08 17:58:34'),
(275, 105, 'picked', NULL, NULL, '2026-11-08 18:00:04'),
(276, 106, 'placed', NULL, NULL, '2026-11-06 13:40:44'),
(277, 106, 'processing', NULL, NULL, '2026-11-06 13:42:26'),
(278, 106, 'confirmed', NULL, NULL, '2026-11-06 13:43:49'),
(279, 106, 'picked', NULL, NULL, '2026-11-06 13:45:47'),
(280, 107, 'placed', NULL, NULL, '2026-11-14 22:42:24'),
(281, 107, 'processing', NULL, NULL, '2026-11-14 22:44:24'),
(282, 107, 'confirmed', NULL, NULL, '2026-11-14 22:46:56'),
(283, 107, 'picked', NULL, NULL, '2026-11-14 22:49:52'),
(284, 108, 'placed', NULL, NULL, '2026-11-09 04:18:54'),
(285, 108, 'processing', NULL, NULL, '2026-11-09 04:20:16'),
(286, 108, 'confirmed', NULL, NULL, '2026-11-09 04:22:12'),
(287, 108, 'picked', NULL, NULL, '2026-11-09 04:24:42'),
(288, 109, 'placed', NULL, NULL, '2026-11-07 18:35:13'),
(289, 109, 'processing', NULL, NULL, '2026-11-07 18:38:10'),
(290, 109, 'confirmed', NULL, NULL, '2026-11-07 18:40:22'),
(291, 109, 'picked', NULL, NULL, '2026-11-07 18:41:41'),
(292, 110, 'placed', NULL, NULL, '2026-11-06 05:59:09'),
(293, 110, 'processing', NULL, NULL, '2026-11-06 06:00:23'),
(294, 110, 'confirmed', NULL, NULL, '2026-11-06 06:02:33'),
(295, 110, 'picked', NULL, NULL, '2026-11-06 06:03:21'),
(296, 111, 'placed', NULL, NULL, '2026-11-04 18:52:32'),
(297, 111, 'processing', NULL, NULL, '2026-11-04 18:54:54'),
(298, 111, 'confirmed', NULL, NULL, '2026-11-04 18:56:28'),
(299, 111, 'picked', NULL, NULL, '2026-11-04 18:57:14'),
(300, 112, 'placed', NULL, NULL, '2026-11-04 17:45:14'),
(301, 112, 'processing', NULL, NULL, '2026-11-04 17:47:00'),
(302, 112, 'confirmed', NULL, NULL, '2026-11-04 17:49:19'),
(303, 112, 'picked', NULL, NULL, '2026-11-04 17:51:41'),
(304, 113, 'placed', NULL, NULL, '2026-11-09 07:32:51'),
(305, 113, 'processing', NULL, NULL, '2026-11-09 07:35:32'),
(306, 113, 'confirmed', NULL, NULL, '2026-11-09 07:38:13'),
(307, 113, 'picked', NULL, NULL, '2026-11-09 07:41:03'),
(308, 114, 'placed', NULL, NULL, '2026-11-02 12:39:34'),
(309, 114, 'processing', NULL, NULL, '2026-11-02 12:42:01'),
(310, 114, 'confirmed', NULL, NULL, '2026-11-02 12:44:39'),
(311, 114, 'picked', NULL, NULL, '2026-11-02 12:45:34'),
(312, 114, 'packed', NULL, NULL, '2026-11-02 12:46:39'),
(313, 115, 'placed', NULL, NULL, '2026-11-05 09:19:12'),
(314, 115, 'processing', NULL, NULL, '2026-11-05 09:21:43'),
(315, 115, 'confirmed', NULL, NULL, '2026-11-05 09:22:50'),
(316, 115, 'picked', NULL, NULL, '2026-11-05 09:25:48'),
(317, 115, 'packed', NULL, NULL, '2026-11-05 09:27:48'),
(318, 116, 'placed', NULL, NULL, '2026-11-04 03:47:14'),
(319, 116, 'processing', NULL, NULL, '2026-11-04 03:49:01'),
(320, 116, 'confirmed', NULL, NULL, '2026-11-04 03:50:35'),
(321, 116, 'picked', NULL, NULL, '2026-11-04 03:52:28'),
(322, 116, 'packed', NULL, NULL, '2026-11-04 03:54:29'),
(323, 117, 'placed', NULL, NULL, '2026-11-13 11:47:47'),
(324, 117, 'processing', NULL, NULL, '2026-11-13 11:49:26'),
(325, 117, 'confirmed', NULL, NULL, '2026-11-13 11:51:17'),
(326, 117, 'picked', NULL, NULL, '2026-11-13 11:54:12'),
(327, 117, 'packed', NULL, NULL, '2026-11-13 11:56:13'),
(328, 118, 'placed', NULL, NULL, '2026-11-12 09:25:19'),
(329, 118, 'processing', NULL, NULL, '2026-11-12 09:26:07'),
(330, 118, 'confirmed', NULL, NULL, '2026-11-12 09:28:36'),
(331, 118, 'picked', NULL, NULL, '2026-11-12 09:30:18'),
(332, 118, 'packed', NULL, NULL, '2026-11-12 09:31:16'),
(333, 119, 'placed', NULL, NULL, '2026-11-10 13:52:40'),
(334, 119, 'processing', NULL, NULL, '2026-11-10 13:54:52'),
(335, 119, 'confirmed', NULL, NULL, '2026-11-10 13:57:05'),
(336, 119, 'picked', NULL, NULL, '2026-11-10 14:00:02'),
(337, 119, 'packed', NULL, NULL, '2026-11-10 14:00:50'),
(338, 120, 'placed', NULL, NULL, '2026-11-08 20:11:49'),
(339, 120, 'processing', NULL, NULL, '2026-11-08 20:13:06'),
(340, 120, 'confirmed', NULL, NULL, '2026-11-08 20:13:53'),
(341, 120, 'picked', NULL, NULL, '2026-11-08 20:15:05'),
(342, 120, 'packed', NULL, NULL, '2026-11-08 20:17:09'),
(343, 121, 'placed', NULL, NULL, '2026-11-08 22:03:57'),
(344, 121, 'processing', NULL, NULL, '2026-11-08 22:05:59'),
(345, 121, 'confirmed', NULL, NULL, '2026-11-08 22:06:59'),
(346, 121, 'picked', NULL, NULL, '2026-11-08 22:08:24'),
(347, 121, 'packed', NULL, NULL, '2026-11-08 22:11:13'),
(348, 122, 'placed', NULL, NULL, '2026-11-04 13:46:34'),
(349, 122, 'processing', NULL, NULL, '2026-11-04 13:47:31'),
(350, 122, 'confirmed', NULL, NULL, '2026-11-04 13:49:26'),
(351, 122, 'picked', NULL, NULL, '2026-11-04 13:51:04'),
(352, 122, 'packed', NULL, NULL, '2026-11-04 13:52:48'),
(353, 123, 'placed', NULL, NULL, '2026-11-03 04:05:07'),
(354, 123, 'processing', NULL, NULL, '2026-11-03 04:06:29'),
(355, 123, 'confirmed', NULL, NULL, '2026-11-03 04:08:03'),
(356, 123, 'picked', NULL, NULL, '2026-11-03 04:08:50'),
(357, 123, 'packed', NULL, NULL, '2026-11-03 04:11:22'),
(358, 25, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(359, 26, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(360, 27, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(361, 28, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(362, 29, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(363, 30, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(364, 31, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(365, 32, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(366, 33, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(367, 34, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(368, 35, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(369, 36, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(370, 37, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(371, 38, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(372, 39, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(373, 40, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(374, 41, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(375, 42, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(376, 43, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(377, 44, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(378, 45, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(379, 46, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(380, 47, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(381, 48, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(382, 49, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(383, 50, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(384, 51, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(385, 52, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(386, 53, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(387, 54, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(388, 55, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(389, 56, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(390, 57, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(391, 58, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(392, 59, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(393, 60, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(394, 61, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(395, 62, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(396, 63, 'confirmed', 79, 'CEO', '2026-11-15 23:19:09'),
(397, 115, 'packed_done', 79, 'CEO', '2026-11-15 23:42:07'),
(398, 23, 'processing', 79, 'CEO', '2026-11-15 23:54:06'),
(399, 23, 'confirmed', 79, 'CEO', '2026-11-15 23:54:11'),
(400, 23, 'picked', 79, 'CEO', '2026-11-15 23:54:17'),
(401, 23, 'packed', 79, 'CEO', '2026-11-15 23:54:22'),
(402, 23, 'packed_done', 79, 'CEO', '2026-11-15 23:55:54'),
(403, 115, 'inbag', NULL, NULL, '2026-11-16 00:17:30'),
(404, 23, 'inbag', NULL, NULL, '2026-11-16 00:17:30'),
(405, 114, 'packed_done', 79, 'CEO', '2026-11-16 00:21:12'),
(406, 116, 'packed_done', 79, 'CEO', '2026-11-16 00:21:12'),
(407, 115, 'delivering', NULL, NULL, '2026-11-16 06:54:03'),
(408, 23, 'delivering', NULL, NULL, '2026-11-16 06:54:03'),
(409, 115, 'delivered', NULL, NULL, '2026-11-16 06:54:11'),
(410, 124, 'placed', NULL, NULL, '2026-11-16 21:53:36'),
(411, 124, 'processing', NULL, NULL, '2026-11-16 21:53:36'),
(412, 124, 'confirmed', 79, 'CEO', '2026-11-16 21:53:50'),
(413, 124, 'picked', 79, 'CEO', '2026-11-16 21:54:03'),
(414, 124, 'packed', 79, 'CEO', '2026-11-16 21:54:06'),
(415, 124, 'packed_done', 79, 'CEO', '2026-11-16 21:54:09'),
(416, 124, 'inbag', NULL, NULL, '2026-11-16 21:54:27'),
(417, 124, 'delivering', NULL, NULL, '2026-11-16 21:54:27'),
(418, 124, 'delivered', NULL, NULL, '2026-11-16 21:56:01');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_name` varchar(100) DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `reseller_markup_percent` decimal(5,2) DEFAULT NULL,
  `sell_markup_percent` decimal(5,2) DEFAULT NULL,
  `sell_price` decimal(10,2) NOT NULL,
  `reseller_price` decimal(10,2) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `actual_price` decimal(10,2) GENERATED ALWAYS AS (`sell_price` - `sell_price` * `discount_percent` / 100) STORED,
  `description` text DEFAULT NULL,
  `size` varchar(100) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `collection_name` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `main_img` varchar(255) NOT NULL,
  `side_img1` varchar(255) DEFAULT NULL,
  `side_img2` varchar(255) DEFAULT NULL,
  `side_img3` varchar(255) DEFAULT NULL,
  `side_img4` varchar(255) DEFAULT NULL,
  `side_img5` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `delete_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `name`, `category_name`, `purchase_price`, `reseller_markup_percent`, `sell_markup_percent`, `sell_price`, `reseller_price`, `discount_percent`, `description`, `size`, `color`, `collection_name`, `stock`, `main_img`, `side_img1`, `side_img2`, `side_img3`, `side_img4`, `side_img5`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`, `delete_reason`) VALUES
(1, 'shirt-101', 'Stylish Fashion Shirt', 'Shirt', 280.00, 0.00, 132.14, 650.00, 280.00, 10.00, 'Stylish Fashionable Shirt', 'L', 'blue, yellow', 'top_rated', 5, '1787049204_6a8434f4e6290.png', '1784828522_6a62526a0c3f4.png', '', '', '', '', 3, NULL, NULL, '2026-07-23 17:20:32', '2026-11-16 13:10:36', NULL, NULL),
(2, 'shirt-102', 'Primium Cotton Shirt', 'Shirt', 280.00, 0.00, 96.43, 550.00, 280.00, 10.00, '', 'M,L,XL,XXL', NULL, 'new_arrivals', 3, '1787049194_6a8434ea788b1.png', '1787070696_6a8488e870f9c.png', '1787070696_6a8488e87161c.png', '1787070696_6a8488e871cd4.jpg', '1787070696_6a8488e872244.png', '1787070696_6a8488e872f30.png', 70, NULL, 70, '2026-08-17 18:06:57', '2026-11-16 13:10:36', '2026-08-18 04:09:21', 'test'),
(8, 'shirt-103', 'Cotton shirt', 'Shirt', 320.00, 0.00, 103.13, 650.00, 320.00, 10.00, 'HIgh Quality', 'M,L,XL', NULL, 'new_arrivals', 2, '1787049300_6a843554a917e.png', NULL, NULL, NULL, NULL, NULL, 70, NULL, NULL, '2026-08-18 10:35:00', '2026-11-16 13:10:36', NULL, NULL),
(9, 'shirt-104', 'Primium Cotton Shirt', 'Shirt', 450.00, 0.00, 120.00, 990.00, 450.00, 5.00, '', 'M,L,XL,XXL', NULL, 'new_arrivals', 12, '1787049402_6a8435ba7090e.png', NULL, NULL, NULL, NULL, NULL, 70, NULL, NULL, '2026-08-18 10:36:42', '2026-11-16 13:10:36', NULL, NULL),
(10, 'shirt-105', 'Primium Cotton Shirt', 'Shirt', 300.00, 0.00, 99.67, 599.00, 300.00, 4.99, '', 'M,L,XL,XXL', '', 'new_arrivals', 7, '1787049472_6a843600b7888.png', '', '', '', '', '', 70, NULL, NULL, '2026-08-18 10:37:52', '2026-11-16 13:10:36', NULL, NULL),
(16, 'shirt-106', 'Stylish Fashion Shirt', 'Shirt', 280.00, 0.00, 132.14, 650.00, 280.00, 10.00, 'Stylish Fashionable Shirt', 'L', NULL, 'top_deals', 8, '1787049204_6a8434f4e6290.png', '1784828522_6a62526a0c3f4.png', '', '', '', '', 3, NULL, NULL, '2026-07-23 17:20:32', '2026-11-16 13:10:36', NULL, NULL),
(17, 'shirt-107', 'Primium Cotton Shirt', 'Shirt', 280.00, 0.00, 96.43, 550.00, 280.00, 10.00, '', 'L', NULL, 'top_deals', 5, '1787049194_6a8434ea788b1.png', '', '', '', '', '', 70, NULL, 70, '2026-08-17 18:06:57', '2026-11-16 13:10:36', '2026-08-18 04:09:21', 'test'),
(18, 'shirt-108', 'Cotton shirt', 'Shirt', 320.00, 0.00, 103.13, 650.00, 320.00, 10.00, 'HIgh Quality', 'M,L,XL', NULL, 'top_deals', 4, '1787049300_6a843554a917e.png', NULL, NULL, NULL, NULL, NULL, 70, NULL, NULL, '2026-08-18 10:35:00', '2026-11-16 13:10:36', NULL, NULL),
(19, 'shirt-109', 'Primium Cotton Shirt', 'Shirt', 450.00, 0.00, 120.00, 990.00, 450.00, 5.00, '', 'M,L,XL,XXL', NULL, 'top_deals', 15, '1787049402_6a8435ba7090e.png', NULL, NULL, NULL, NULL, NULL, 70, NULL, NULL, '2026-08-18 10:36:42', '2026-11-16 13:10:36', NULL, NULL),
(20, 'shirt-110', 'Primium Cotton Shirt', 'Shirt', 300.00, 0.00, 99.67, 599.00, 300.00, 4.99, 'fashionable', 'M,L,XL,XXL', NULL, 'top_deals', 4, '1787049472_6a843600b7888.png', '', '', '', '', '', 70, NULL, NULL, '2026-08-18 10:37:52', '2026-11-16 13:10:36', NULL, NULL),
(21, 'shirt-111', 'Stylish Fashion Shirt', 'Shirt', 280.00, 0.00, 132.14, 650.00, 280.00, 10.00, 'Stylish Fashionable Shirt', 'L', NULL, 'high_discount', 2, '1787049204_6a8434f4e6290.png', '1784828522_6a62526a0c3f4.png', '', '', '', '', 3, NULL, NULL, '2026-07-23 17:20:32', '2026-11-16 13:10:36', NULL, NULL),
(22, 'shirt-112', 'Primium Cotton Shirt', 'Shirt', 280.00, 0.00, 96.43, 550.00, 280.00, 10.00, '', 'L', NULL, 'high_discount', 4, '1787049194_6a8434ea788b1.png', '', '', '', '', '', 70, NULL, 70, '2026-08-17 18:06:57', '2026-11-16 13:10:36', '2026-08-18 04:09:21', 'test'),
(23, 'shirt-113', 'Cotton shirt', 'Shirt', 320.00, 0.00, 103.13, 650.00, 320.00, 10.00, 'HIgh Quality', 'M,L,XL', NULL, 'high_discount', 6, '1787049300_6a843554a917e.png', NULL, NULL, NULL, NULL, NULL, 70, NULL, NULL, '2026-08-18 10:35:00', '2026-11-16 13:10:36', NULL, NULL),
(24, 'shirt-114', 'Primium Cotton Shirt', 'Shirt', 450.00, 0.00, 120.00, 990.00, 450.00, 5.00, '', 'M,L,XL,XXL', NULL, 'high_discount', 13, '1787049402_6a8435ba7090e.png', NULL, NULL, NULL, NULL, NULL, 70, NULL, NULL, '2026-08-18 10:36:42', '2026-11-16 13:10:36', NULL, NULL),
(25, 'shirt-115', 'Primium Cotton Shirt', 'Shirt', 300.00, 0.00, 99.67, 599.00, 300.00, 4.99, '', 'M,L,XL,XXL', NULL, 'high_discount', 8, '1787049472_6a843600b7888.png', NULL, NULL, NULL, NULL, NULL, 70, NULL, NULL, '2026-08-18 10:37:52', '2026-11-16 13:10:36', NULL, NULL),
(26, 'shirt-116', 'Stylish Fashion Shirt', 'Shirt', 280.00, 0.00, 132.14, 650.00, 280.00, 10.00, 'Stylish Fashionable Shirt', 'L', NULL, 'top_sales', 6, '1787049204_6a8434f4e6290.png', '1784828522_6a62526a0c3f4.png', '', '', '', '', 3, NULL, NULL, '2026-07-23 17:20:32', '2026-11-16 13:10:36', NULL, NULL),
(27, 'shirt-117', 'Primium Cotton Shirt', 'Shirt', 280.00, 0.00, 96.43, 550.00, 280.00, 10.00, '', 'L', NULL, 'top_sales', 5, '1787049194_6a8434ea788b1.png', '', '', '', '', '', 70, NULL, 70, '2026-08-17 18:06:57', '2026-11-16 13:10:36', '2026-08-18 04:09:21', 'test'),
(28, 'shirt-118', 'Cotton shirt', 'Shirt', 320.00, 0.00, 103.13, 650.00, 320.00, 10.00, 'HIgh Quality', 'M,L,XL', NULL, 'top_sales', 7, '1787049300_6a843554a917e.png', NULL, NULL, NULL, NULL, NULL, 70, NULL, NULL, '2026-08-18 10:35:00', '2026-11-16 13:10:36', NULL, NULL),
(29, 'shirt-119', 'Primium Cotton Shirt', 'Shirt', 450.00, 0.00, 120.00, 990.00, 450.00, 5.00, '', 'M,L,XL,XXL', NULL, 'new_arrivals', 9, '1787049402_6a8435ba7090e.png', '', '', '', '', '', 70, NULL, NULL, '2026-08-18 10:36:42', '2026-11-16 13:10:36', NULL, NULL),
(30, 'shirt-120', 'Primium Cotton Shirt', 'Shirt', 300.00, 0.00, 99.67, 599.00, 300.00, 4.99, '', 'M,L,XL,XXL', NULL, 'new_arrivals', 3, '1787049472_6a843600b7888.png', '', '', '', '', '', 70, NULL, NULL, '2026-08-18 10:37:52', '2026-11-16 15:53:36', NULL, NULL),
(31, 'shirt-121', 'Stylish Fashion Shirt', 'Shirt', 280.00, 0.00, 132.14, 650.00, 280.00, 10.00, 'Stylish Fashionable Shirt', 'L', NULL, 'top_rated', 6, '1787049204_6a8434f4e6290.png', '1784828522_6a62526a0c3f4.png', '', '', '', '', 3, NULL, NULL, '2026-07-23 17:20:32', '2026-11-16 13:10:36', NULL, NULL),
(32, 'shirt-122', 'Primium Cotton Shirt', 'Shirt', 280.00, 0.00, 96.43, 550.00, 280.00, 10.00, '', 'L', NULL, 'new_arrivals', 3, '1787049194_6a8434ea788b1.png', '', '', '', '', '', 70, NULL, 70, '2026-08-17 18:06:57', '2026-11-16 13:10:36', '2026-08-18 04:09:21', 'test'),
(33, 'shirt-123', 'Cotton shirt', 'Shirt', 320.00, 0.00, 103.13, 650.00, 320.00, 10.00, 'HIgh Quality', 'M,L,XL', NULL, 'new_arrivals', 0, '1787049300_6a843554a917e.png', '', '', '', '', '', 70, NULL, NULL, '2026-08-18 10:35:00', '2026-11-16 13:10:36', NULL, NULL),
(34, 'shirt-124', 'Primium Cotton Shirt', 'Shirt', 450.00, 0.00, 120.00, 990.00, 450.00, 5.00, '', 'M,L,XL,XXL', NULL, 'new_arrivals', 9, '1787049402_6a8435ba7090e.png', '', '', '', '', '', 70, NULL, NULL, '2026-08-18 10:36:42', '2026-11-16 13:10:36', NULL, NULL),
(35, 'shirt-125', 'Primium Cotton Shirt', 'Shirt', 300.00, 0.00, 99.67, 599.00, 300.00, 4.99, '', 'M,L,XL,XXL', 'blue, black', 'new_arrivals', 0, '1787049472_6a843600b7888.png', '', '', '', '', '', 70, NULL, NULL, '2026-08-18 10:37:52', '2026-11-16 13:10:36', NULL, NULL),
(36, 'shirt-201', 'Stylish Fashion Shirt', 'Shirt', 300.00, 0.00, 83.33, 550.00, 300.00, 10.00, '', 'M,L,XL,XXL', 'blue, black, green, yellow', '', 0, '1788182968_6a9581b85ae9d.png', '', '', '', '', '', 75, NULL, NULL, '2026-08-31 13:22:11', '2026-11-16 13:10:36', NULL, NULL),
(37, 'shirt-202', 'jksdf', 'pant', 350.00, 0.00, 85.71, 650.00, 350.00, 10.00, '', '32,34,36', '', '', 0, '1788288161_6a971ca19588c.png', NULL, NULL, NULL, NULL, NULL, 75, NULL, NULL, '2026-09-01 18:42:41', '2026-11-16 13:10:36', NULL, NULL),
(38, '156', 'test2', 'ohu', 320.00, 0.00, 56.25, 500.00, 320.00, 0.00, '', '', '', 'new_arrivals', 0, '1792862218_6adce80a62c21.jpg', NULL, NULL, NULL, NULL, NULL, 79, NULL, NULL, '2026-10-24 17:16:58', '2026-11-16 13:10:36', NULL, NULL),
(39, 'TEST-CODE', 'Test product', 'Gamsa', 100.00, 0.00, 150.00, 250.00, 100.00, 0.00, '', '', '', 'new_arrivals', 2, '1794838682_6afb109a184e3.png', '', '', '', '', '', 79, NULL, NULL, '2026-11-03 00:07:54', '2026-11-16 14:18:02', NULL, NULL),
(40, 'shirt-test-102', 'test shirt', 'Shirt', 320.00, 8.00, 30.00, 416.00, 345.60, 3.00, '', 'M,L,XL,XXL', 'blue, black', 'new_arrivals', 2, '1794844123_6afb25db9b231.png', NULL, NULL, NULL, NULL, NULL, 79, NULL, NULL, '2026-11-16 15:48:43', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_code` varchar(40) NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_invoice` varchar(100) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `extra_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by_admin_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `purchase_code`, `supplier_id`, `supplier_invoice`, `purchase_date`, `subtotal`, `discount_amount`, `extra_cost`, `total_amount`, `notes`, `created_by_admin_id`, `created_at`) VALUES
(2, 'PUR-261103-E89A21', 2, 'TEST-INVOICE', '2026-11-02', 200.00, 0.00, 0.00, 200.00, NULL, 79, '2026-11-03 06:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `size` varchar(100) NOT NULL DEFAULT '',
  `quantity` int(10) UNSIGNED NOT NULL,
  `returned_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `unit_cost` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `product_code`, `product_name`, `size`, `quantity`, `returned_quantity`, `unit_cost`, `line_total`) VALUES
(2, 2, 39, 'TEST-CODE', 'Test product', '', 2, 0, 100.00, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_payments`
--

CREATE TABLE `purchase_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(30) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `received_by_admin_id` bigint(20) UNSIGNED NOT NULL,
  `paid_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_payments`
--

INSERT INTO `purchase_payments` (`id`, `purchase_id`, `amount`, `payment_method`, `reference_no`, `note`, `received_by_admin_id`, `paid_at`) VALUES
(2, 2, 100.00, 'cash', NULL, NULL, 77, '2026-11-03 06:10:29');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `return_code` varchar(40) NOT NULL,
  `purchase_id` bigint(20) UNSIGNED NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(500) DEFAULT NULL,
  `created_by_admin_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

CREATE TABLE `purchase_return_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `return_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_item_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reseller_accounts`
--

CREATE TABLE `reseller_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(180) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `referral_code` varchar(24) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by_admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reseller_accounts`
--

INSERT INTO `reseller_accounts` (`id`, `name`, `phone`, `email`, `password_hash`, `referral_code`, `status`, `rejection_reason`, `reviewed_by_admin_id`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 'Developer Jasim', '01601610105', 'mdjasimh944@gmail.com', '$2y$10$VKaabYzcSHroKs//mFpbaOkItjocUwDBu.zEIH/kSXfv.puy25o3K', '6F53945D3942', 'approved', NULL, 79, '2026-11-16 21:51:20', '2026-11-16 21:50:38', '2026-11-16 21:51:20');

-- --------------------------------------------------------

--
-- Table structure for table `reseller_commissions`
--

CREATE TABLE `reseller_commissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reseller_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `order_amount` decimal(12,2) NOT NULL,
  `commission_amount` decimal(12,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reseller_commissions`
--

INSERT INTO `reseller_commissions` (`id`, `reseller_id`, `order_id`, `order_amount`, `commission_amount`, `created_at`) VALUES
(1, 1, 124, 569.00, 269.00, '2026-11-16 21:56:01');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_title', 'AmarShop', '2026-11-02 13:31:57'),
(2, 'site_tagline', 'Welcome to AmarShop - Best Online Store!', '2026-11-02 13:31:57'),
(3, 'contact_phone', '+880 1700-000000', '2026-11-02 13:31:57'),
(4, 'contact_email', 'info@amarshop.com', '2026-11-02 13:31:57'),
(5, 'contact_address', 'Dhaka, Bangladesh', '2026-11-02 13:31:57'),
(6, 'facebook_url', 'https://www.facebook.com/profile.php?id=61595701101787', '2026-11-02 13:31:57'),
(7, 'instagram_url', '', '2026-11-02 13:31:57'),
(8, 'whatsapp_number', '', '2026-11-02 13:31:57');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(180) NOT NULL,
  `phone` varchar(40) NOT NULL DEFAULT '',
  `email` varchar(191) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `email`, `address`, `status`, `created_at`, `updated_at`) VALUES
(2, 'মামা', '', NULL, NULL, 'active', '2026-11-02 21:45:43', '2026-11-02 21:45:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `customar_name` varchar(255) NOT NULL,
  `phone` text NOT NULL,
  `address` text DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `verify_token` text NOT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `verify_status` tinyint(4) NOT NULL,
  `phone_verified` tinyint(1) NOT NULL DEFAULT 0,
  `phone_otp` varchar(10) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_at` datetime DEFAULT NULL,
  `reset_count` int(11) NOT NULL,
  `pwd_up_at` datetime DEFAULT NULL,
  `pwd_up_count` int(11) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `user_ip` varchar(45) DEFAULT NULL,
  `client_ip` varchar(45) DEFAULT NULL,
  `remote_host` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `customar_name`, `phone`, `address`, `email`, `password`, `verify_token`, `otp_code`, `otp_expires_at`, `verify_status`, `phone_verified`, `phone_otp`, `email_verified`, `remember_token`, `created_at`, `updated_at`, `reset_token`, `reset_at`, `reset_count`, `pwd_up_at`, `pwd_up_count`, `role`, `user_ip`, `client_ip`, `remote_host`, `user_agent`) VALUES
(77, 'manager', '01950047790', NULL, 'manager@gmail.com', '$2y$10$8Qs.BF1yJMMtsDNdTls5Aug9q8ahDj/MCBLj5uPhN2RPXoJXBIF6O', '', NULL, NULL, 1, 0, NULL, 0, NULL, '2026-10-20 00:30:16', '2026-10-21 17:21:48', NULL, NULL, 0, NULL, 0, 'manager', NULL, NULL, NULL, NULL),
(78, 'customar', '01609069539', 'House-10/11, Road-12, Mirpur-1, Dhaka', 'cus@gmail.com', '$2y$10$mEIDRjom7lckASfjrftcmO3kAsNrmBcV0TDXXzjbl5VRx1.qeclBy', '', NULL, NULL, 1, 0, NULL, 0, NULL, '2026-10-20 00:35:19', '2026-11-15 13:35:16', NULL, NULL, 0, NULL, 0, 'user', NULL, NULL, NULL, NULL),
(79, 'CEO', '', NULL, 'ceo@gmail.com', '$2y$10$kpcV3a60oGtXiCNelGDvJ.LmFbBmiogieryJnFU9yzvzzmbaUztKO', 'fcdf1bdab33332c0a25a332f3721dbe5444a8c18f55a55b2f5a7619f18c086d8', NULL, NULL, 1, 1, NULL, 0, NULL, '2026-10-20 00:37:44', '2026-11-01 15:55:58', NULL, NULL, 0, NULL, 0, 'admin', NULL, NULL, NULL, NULL),
(84, 'Mst Mariya Akter', '01949645050', 'hose -3 d', 'developermdjasim@gmail.com', '$2y$10$dWxcgFpGqphgKnv/Bqet7OOL4aFJxFotGsifymJFhq1q8TYD1Rs3C', '6e61c7759d8f117a7bf297ea1b7d87bf', NULL, NULL, 1, 1, NULL, 1, NULL, '2026-10-23 23:19:22', '2026-11-15 13:35:28', NULL, NULL, 0, NULL, 0, 'user', '::1', '::1', 'DESKTOP-UOJ9V05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36'),
(85, 'name', '01745213653', NULL, NULL, '$2y$10$iRIJrqjSge2rZcIRoL/.hOhpq9HI1Pt0F3Jpv7oV6RdeqbXtkzRJe', '277c8b862eb774debe1926c9c41ac433', NULL, NULL, 0, 0, NULL, 0, NULL, '2026-10-24 19:05:49', '2026-10-24 15:59:41', NULL, NULL, 0, NULL, 0, 'user', '::1', '::1', 'DESKTOP-UOJ9V05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36'),
(86, 'Md Jasim Uddin', '01601610105', NULL, 'mdjasimh944@gmail.com', '$2y$10$NEBiWs1JSepYL5Gsc3ounerNOl2EPRYhX7xzvhUZ8eHJPuSP.CFqC', 'dd48be9055bd9e9019e62b83c1dcf8d7', NULL, NULL, 1, 0, NULL, 1, NULL, '2026-11-01 21:56:41', '2026-11-01 15:57:17', NULL, NULL, 0, NULL, 0, 'user', '::1', '::1', 'DESKTOP-UOJ9V05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36'),
(87, 'DEMO Customer Account', '0000000000', 'DEMO DATA ONLY', 'demo.orders.20261115@example.test', '$2y$10$F6WzeH0LbEdwewwnXyeTeOESY4FRCnOSeZipi6lYz9A3C732X4uIm', 'dc0485eeea8442bef6bfb3a847102d21e7107db2c0fcce98475a2131f62efce9', NULL, NULL, 0, 0, NULL, 0, NULL, '2026-11-15 23:17:23', '2026-11-15 17:17:23', NULL, NULL, 0, NULL, 0, 'user', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `label` varchar(80) NOT NULL DEFAULT 'Home Address',
  `address` text NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `label`, `address`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 78, 'Home Address', 'House-10/11, Road-12, Mirpur-1, Dhaka', 1, '2026-10-21 23:30:50', '2026-10-21 23:30:50'),
(2, 84, 'Home Address', 'hose -3 d', 1, '2026-10-24 06:52:47', '2026-10-24 06:56:10'),
(3, 84, 'present', 'jkg', 0, '2026-10-24 06:55:57', '2026-10-24 06:56:10');

-- --------------------------------------------------------

--
-- Table structure for table `user_profile_pictures`
--

CREATE TABLE `user_profile_pictures` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profile_pictures`
--

INSERT INTO `user_profile_pictures` (`user_id`, `image_path`, `updated_at`) VALUES
(78, 'profiles/3fc314dfd17df2c630daca20db87db92.jpg', '2026-10-21 23:17:36'),
(79, 'profiles/489a18de74702aee0c2533cbb226033f.jpg', '2026-10-21 23:18:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_profile_pictures`
--
ALTER TABLE `admin_profile_pictures`
  ADD PRIMARY KEY (`admin_user_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_users_email` (`email`);

--
-- Indexes for table `auth`
--
ALTER TABLE `auth`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_collectors`
--
ALTER TABLE `cash_collectors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deliveryman`
--
ALTER TABLE `deliveryman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deliveryman_status` (`status`);

--
-- Indexes for table `delivery_bags`
--
ALTER TABLE `delivery_bags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_delivery_bags_code` (`bag_code`),
  ADD KEY `idx_delivery_bags_deliveryman` (`deliveryman_id`,`created_at`);

--
-- Indexes for table `delivery_bag_cash_collections`
--
ALTER TABLE `delivery_bag_cash_collections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bag_cash_status` (`status`,`submitted_at`),
  ADD KEY `idx_bag_cash_deliveryman` (`deliveryman_id`,`bag_id`,`id`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_movements_product` (`product_id`,`created_at`),
  ADD KEY `idx_inventory_movements_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `offline_pos_installation`
--
ALTER TABLE `offline_pos_installation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offline_pos_sales`
--
ALTER TABLE `offline_pos_sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_offline_pos_receipt` (`receipt_no`),
  ADD KEY `idx_offline_pos_sales_date` (`created_at`),
  ADD KEY `idx_offline_pos_sales_cashier` (`cashier_id`,`created_at`);

--
-- Indexes for table `offline_pos_sale_items`
--
ALTER TABLE `offline_pos_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_offline_pos_sale_items_sale` (`sale_id`,`id`),
  ADD KEY `idx_offline_pos_sale_items_product` (`product_id`);

--
-- Indexes for table `offline_pos_users`
--
ALTER TABLE `offline_pos_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_offline_pos_username` (`username`),
  ADD KEY `idx_offline_pos_users_active` (`is_active`,`role`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_reseller` (`reseller_id`,`created_at`);

--
-- Indexes for table `order_delivery_assignments`
--
ALTER TABLE `order_delivery_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_order_delivery_assignment` (`order_id`);

--
-- Indexes for table `order_issues`
--
ALTER TABLE `order_issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_issues_status_created` (`status`,`created_at`),
  ADD KEY `idx_order_issues_order` (`order_id`,`user_id`);

--
-- Indexes for table `order_issue_delivery_assignments`
--
ALTER TABLE `order_issue_delivery_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_order_issue_delivery_assignment` (`issue_id`);

--
-- Indexes for table `order_issue_items`
--
ALTER TABLE `order_issue_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_issue_items_issue` (`issue_id`);

--
-- Indexes for table `order_issue_shipment_history`
--
ALTER TABLE `order_issue_shipment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_issue_shipment_history` (`issue_id`,`created_at`,`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_number_sequences`
--
ALTER TABLE `order_number_sequences`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_quantity`
--
ALTER TABLE `order_quantity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_quantity_bag` (`bag_id`,`status`),
  ADD KEY `idx_order_quantity_order` (`order_id`,`status`),
  ADD KEY `idx_order_quantity_issue` (`issue_id`,`status`);

--
-- Indexes for table `order_requests`
--
ALTER TABLE `order_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_requests_status_created` (`status`,`created_at`),
  ADD KEY `idx_order_requests_order` (`order_id`);

--
-- Indexes for table `order_request_messages`
--
ALTER TABLE `order_request_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_request_messages_request` (`request_id`,`created_at`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_status_history_order` (`order_id`,`created_at`,`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_purchases_code` (`purchase_code`),
  ADD KEY `idx_purchases_supplier_date` (`supplier_id`,`purchase_date`),
  ADD KEY `idx_purchases_date` (`purchase_date`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase_items_purchase` (`purchase_id`,`id`),
  ADD KEY `idx_purchase_items_product` (`product_id`);

--
-- Indexes for table `purchase_payments`
--
ALTER TABLE `purchase_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase_payments_purchase` (`purchase_id`,`paid_at`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_purchase_returns_code` (`return_code`),
  ADD KEY `idx_purchase_returns_purchase` (`purchase_id`,`created_at`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase_return_items_return` (`return_id`,`id`),
  ADD KEY `idx_purchase_return_items_purchase_item` (`purchase_item_id`);

--
-- Indexes for table `reseller_accounts`
--
ALTER TABLE `reseller_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reseller_email` (`email`),
  ADD UNIQUE KEY `uq_reseller_phone` (`phone`),
  ADD UNIQUE KEY `uq_reseller_referral` (`referral_code`),
  ADD KEY `idx_reseller_status` (`status`,`created_at`);

--
-- Indexes for table `reseller_commissions`
--
ALTER TABLE `reseller_commissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reseller_commission_order` (`order_id`),
  ADD KEY `idx_reseller_commissions` (`reseller_id`,`created_at`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_suppliers_status_name` (`status`,`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_addresses_user` (`user_id`,`is_default`,`id`);

--
-- Indexes for table `user_profile_pictures`
--
ALTER TABLE `user_profile_pictures`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `cash_collectors`
--
ALTER TABLE `cash_collectors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `deliveryman`
--
ALTER TABLE `deliveryman`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `delivery_bags`
--
ALTER TABLE `delivery_bags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `delivery_bag_cash_collections`
--
ALTER TABLE `delivery_bag_cash_collections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `offline_pos_sales`
--
ALTER TABLE `offline_pos_sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `offline_pos_sale_items`
--
ALTER TABLE `offline_pos_sale_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `offline_pos_users`
--
ALTER TABLE `offline_pos_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `order_delivery_assignments`
--
ALTER TABLE `order_delivery_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_issues`
--
ALTER TABLE `order_issues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_issue_delivery_assignments`
--
ALTER TABLE `order_issue_delivery_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_issue_items`
--
ALTER TABLE `order_issue_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_issue_shipment_history`
--
ALTER TABLE `order_issue_shipment_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `order_quantity`
--
ALTER TABLE `order_quantity`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_requests`
--
ALTER TABLE `order_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_request_messages`
--
ALTER TABLE `order_request_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=419;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_payments`
--
ALTER TABLE `purchase_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reseller_accounts`
--
ALTER TABLE `reseller_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reseller_commissions`
--
ALTER TABLE `reseller_commissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
