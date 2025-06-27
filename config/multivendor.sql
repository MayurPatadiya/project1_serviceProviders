-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2025 at 05:22 AM
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
-- Database: `multivendor`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `total_amount` decimal(10,2) NOT NULL,
  `requirements` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `provider_id`, `service_id`, `booking_date`, `booking_time`, `duration`, `total_amount`, `requirements`, `status`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, '2025-06-27', '10:00:00', 120, 150.00, 'Need electrical installation for new kitchen', 'confirmed', 'paid', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(2, 2, 2, 4, '2025-06-28', '14:00:00', 90, 97.50, 'Emergency pipe repair needed', 'pending', 'pending', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(3, 3, 3, 7, '2025-06-26', '09:00:00', 120, 90.00, 'Regular house cleaning service', 'completed', 'paid', '2025-06-25 06:35:32', '2025-06-26 06:13:22'),
(4, 3, 1, 2, '2025-06-24', '15:00:00', 60, 75.00, 'Electrical outlet not working', 'completed', 'paid', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(5, 2, 2, 5, '2025-06-23', '11:00:00', 60, 65.00, 'Kitchen sink drain clogged', 'completed', 'paid', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(6, 7, 3, 7, '2025-06-25', '15:12:00', 60, 45.00, 'welcome', 'cancelled', 'pending', '2025-06-25 07:43:28', '2025-06-25 07:47:24'),
(7, 7, 3, 7, '2025-06-25', '14:10:00', 60, 45.00, 'welcome', 'completed', 'pending', '2025-06-25 07:44:17', '2025-06-25 11:24:06'),
(8, 7, 2, 6, '2025-06-25', '18:32:00', 60, 65.00, 'welcome', 'pending', 'pending', '2025-06-25 12:01:27', '2025-06-25 12:01:27');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('booking','system','admin') NOT NULL DEFAULT 'system',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 'Booking Confirmed', 'Your booking with Mike\'s Electrical Services has been confirmed for tomorrow.', 'booking', 0, '2025-06-25 06:35:32'),
(2, 3, 'Service Completed', 'Your cleaning service with David\'s Cleaning Pro has been completed.', 'booking', 0, '2025-06-25 06:35:32'),
(3, 4, 'New Booking Request', 'You have a new booking request from John Doe.', 'booking', 0, '2025-06-25 06:35:32'),
(4, 5, 'Payment Received', 'Payment received for your plumbing service.', 'system', 0, '2025-06-25 06:35:32'),
(5, 6, 'New Booking Request', 'You have a new booking request from mayurpatadiya', 'booking', 0, '2025-06-25 07:43:28'),
(6, 6, 'New Booking Request', 'You have a new booking request from mayurpatadiya', 'booking', 0, '2025-06-25 07:44:17'),
(7, 6, 'Booking Cancelled', 'A booking has been cancelled by the customer.', 'booking', 0, '2025-06-25 07:47:24'),
(8, 8, 'Provider Account Update', 'Congratulations! Your provider account has been approved. You can now start offering services.', 'system', 0, '2025-06-25 09:34:47'),
(9, 5, 'New Booking Request', 'You have a new booking request from mayurpatadiya', 'booking', 0, '2025-06-25 12:01:27'),
(10, 8, 'New Booking Request', 'You have a new booking request from abhay', 'booking', 0, '2025-06-25 12:08:24');

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

CREATE TABLE `providers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `service_category` enum('electrician','plumber','photographer','cleaner','gardener','painter','carpenter','mechanic','tutor','designer') NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `kyc_document` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`id`, `user_id`, `business_name`, `service_category`, `description`, `location`, `phone`, `hourly_rate`, `kyc_document`, `status`, `rating`, `total_reviews`, `created_at`, `updated_at`) VALUES
(1, 4, 'Mikes Electrical Services', 'electrician', 'Professional electrical services for residential and commercial properties. Licensed and insured.', 'Downtown Area', '+1234567892', 75.00, NULL, 'approved', 5.00, 1, '2025-06-25 06:35:32', '2025-06-25 07:30:26'),
(2, 5, 'Sarahs Plumbing Solutions', 'plumber', 'Expert plumbing services with 24/7 emergency response. All types of plumbing work.', 'Westside District', '+1234567893', 65.00, NULL, 'approved', 4.00, 1, '2025-06-25 06:35:32', '2025-06-25 07:31:06'),
(3, 6, 'David\'s Cleaning Pro', 'cleaner', 'Professional cleaning services for homes and offices. Eco-friendly products used.', 'Eastside Community', '+1234567894', 45.00, NULL, 'approved', NULL, 0, '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(4, 8, 'Repairing Alpha', 'mechanic', 'Best mechanical works provided by Raj Repairing Alpha', 'India', '123', 7.00, 'htPPgz8Xcm.png', 'approved', 0.00, 0, '2025-06-25 06:57:13', '2025-06-26 04:22:10'),
(5, 10, 'Jay Clean Works', 'cleaner', 'We works for clean', 'New York', '123', 1.00, 'waf1o8Efh0.png', 'pending', 0.00, 0, '2025-06-26 06:19:25', '2025-06-26 06:19:25');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reported_user_id` int(11) DEFAULT NULL,
  `reported_provider_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `report_type` enum('user','provider','booking','service') NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','investigating','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `reporter_id`, `reported_user_id`, `reported_provider_id`, `booking_id`, `report_type`, `reason`, `status`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 1, 1, 'provider', 'Provider arrived late and was unprofessional', 'pending', '', '2025-06-25 06:35:32', '2025-06-26 06:16:31'),
(2, 3, NULL, 2, 2, 'booking', 'Service quality was not as expected', 'investigating', NULL, '2025-06-25 06:35:32', '2025-06-25 06:35:32');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `status` enum('active','hidden') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `customer_id`, `provider_id`, `booking_id`, `rating`, `comment`, `status`, `created_at`) VALUES
(1, 3, 1, 4, 5, 'Excellent work! Fixed the electrical issue quickly and professionally.', 'active', '2025-06-25 06:35:32'),
(2, 2, 2, 5, 4, 'Good service, unclogged the drain efficiently. Would recommend.', 'active', '2025-06-25 06:35:32'),
(3, 7, 3, 7, 3, 'Good work', 'active', '2025-06-25 12:00:10');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `provider_id`, `title`, `description`, `price`, `duration`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Electrical Installation', 'Complete electrical installation for new construction or renovations', 75.00, 120, 'active', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(2, 1, 'Electrical Repair', 'Quick electrical repairs and troubleshooting', 75.00, 60, 'active', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(3, 1, 'Light Fixture Installation', 'Installation of new light fixtures and chandeliers', 75.00, 90, 'active', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(4, 2, 'Pipe Repair', 'Emergency pipe repair and leak fixing', 65.00, 90, 'active', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(5, 2, 'Drain Cleaning', 'Professional drain cleaning and unclogging', 65.00, 60, 'active', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(6, 2, 'Water Heater Installation', 'Installation and replacement of water heaters', 65.00, 180, 'active', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(7, 3, 'House Cleaning', 'Complete house cleaning including kitchen, bathrooms, and living areas', 45.00, 120, 'active', '2025-06-25 06:35:32', '2025-06-26 07:12:13'),
(8, 3, 'Deep Cleaning', 'Thorough deep cleaning with special attention to detail', 45.00, 180, 'active', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(9, 3, 'Move-in/Move-out Cleaning', 'Comprehensive cleaning for moving situations', 45.00, 240, 'active', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(11, 4, 'Repair Bikes', 'We repair bikes at door step', 5.00, 2, '', '2025-06-26 04:19:55', '2025-06-26 04:45:20'),
(12, 4, 'Motor Car Repairing', 'Car Mechanic Work', 35.00, 1, '', '2025-06-26 04:21:37', '2025-06-26 04:42:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','provider','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `profile_image`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@servicehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NULL, NULL, NULL, '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(2, 'john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active', NULL, '+1234567890', '123 Main St, City, State', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(3, 'jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active', NULL, '+1234567891', '456 Oak Ave, City, State', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(4, 'mike_electric', 'mike@electric.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider', 'active', 'mAyiL2X1Ru.jpeg', '+1234567892', '789 Service Rd, City, State', '2025-06-25 06:35:32', '2025-06-25 07:26:58'),
(5, 'sarah_plumber', 'sarah@plumber.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider', 'active', 'WJrSJsNhC0.jpeg', '+1234567893', '321 Fix St, City, State', '2025-06-25 06:35:32', '2025-06-25 07:30:59'),
(6, 'david_cleaner', 'david@cleaner.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider', 'active', NULL, '+1234567894', '654 Clean Ave, City, State', '2025-06-25 06:35:32', '2025-06-25 06:35:32'),
(7, 'mayurpatadiya', 'mpatadiya0@gmail.com', '$2y$10$fvOZZotsQhFSulxqhY5fLunatjWLJw/uAJPnW2CoHCC.QU19xBSgC', 'customer', 'active', 'Yzy7BexnjK.png', '9106723238', 'Mayur Patadiya', '2025-06-25 06:36:05', '2025-06-25 07:48:38'),
(8, 'Raj', 'raj@gmail.com', '$2y$10$ZMN7x9e8.k43xm/d89EswOqXBJnX6WmoAeyw.XrzxEJBLbgYR/PNW', 'provider', 'active', 'qEJLRteSK7.png', NULL, NULL, '2025-06-25 06:56:09', '2025-06-25 09:28:28'),
(9, 'abhay', 'abhay@gmail.com', '$2y$10$8A7ccBytnB93Fv23SszvWOIxGOFwj35UxURZfcH64oQEz4ISgsBUS', 'customer', 'active', NULL, NULL, NULL, '2025-06-25 12:03:07', '2025-06-26 06:15:52'),
(10, 'jayesh', 'jayesh@gmail.com', '$2y$10$oBZ9VrOJXOywcWZzYNoe4u8EZQ6g2Xy2MwOtOK0WV0FRkUS.EzISO', 'provider', 'active', NULL, NULL, NULL, '2025-06-26 06:18:05', '2025-06-26 06:18:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `idx_bookings_customer` (`customer_id`),
  ADD KEY `idx_bookings_provider` (`provider_id`),
  ADD KEY `idx_bookings_date` (`booking_date`),
  ADD KEY `idx_bookings_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`is_read`);

--
-- Indexes for table `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_providers_status` (`status`),
  ADD KEY `idx_providers_category` (`service_category`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_user_id` (`reported_user_id`),
  ADD KEY `reported_provider_id` (`reported_provider_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `idx_reports_status` (`status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `idx_reviews_provider` (`provider_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_services_provider` (`provider_id`),
  ADD KEY `idx_services_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `providers`
--
ALTER TABLE `providers`
  ADD CONSTRAINT `providers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`reported_provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reports_ibfk_4` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
