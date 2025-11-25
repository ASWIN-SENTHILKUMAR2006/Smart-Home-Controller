-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 06:14 AM
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
-- Database: `smart_home`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `device_id` int(11) DEFAULT NULL,
  `action` enum('ON','OFF','ADDED','REMOVED','UPDATED') NOT NULL,
  `executed_by` enum('user','admin','scheduler','provider') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `device_id`, `action`, `executed_by`, `timestamp`) VALUES
(1, 6, 1001, 'ON', 'user', '2025-10-28 04:10:59'),
(2, 1, 1002, 'UPDATED', 'admin', '2025-10-28 04:10:59'),
(3, 3, 1004, 'ADDED', 'provider', '2025-10-27 04:10:59'),
(4, 1, 1003, 'ADDED', 'admin', '2025-11-21 13:50:35'),
(5, 1, 1004, 'ADDED', 'admin', '2025-11-21 13:50:39'),
(6, 1, 1002, 'ADDED', 'admin', '2025-11-21 13:50:42'),
(7, 1, 1001, 'ADDED', 'admin', '2025-11-21 13:50:45'),
(8, 11, 1002, 'ON', 'user', '2025-11-21 13:51:06'),
(9, 11, 1004, 'ON', 'scheduler', '2025-11-22 03:46:41'),
(10, 11, 1003, 'OFF', 'scheduler', '2025-11-22 03:49:50'),
(11, 11, 1003, 'ON', 'scheduler', '2025-11-22 03:51:43'),
(12, 11, 1003, 'OFF', 'scheduler', '2025-11-22 03:56:56'),
(13, 11, 1002, 'OFF', 'user', '2025-11-22 04:00:57'),
(14, 11, 1002, 'ON', 'user', '2025-11-22 04:01:13'),
(15, 1, 1003, 'ADDED', 'admin', '2025-11-22 04:02:42'),
(16, 13, NULL, '', 'admin', '2025-11-22 04:29:08'),
(17, NULL, NULL, '', 'admin', '2025-11-22 04:29:21'),
(18, 13, NULL, '', 'admin', '2025-11-22 04:29:26'),
(19, NULL, NULL, '', 'admin', '2025-11-22 04:29:37'),
(20, 20, 1005, 'UPDATED', 'provider', '2025-11-22 04:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `device_id` int(11) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `status` enum('ON','OFF') DEFAULT 'OFF',
  `provider_id` int(11) DEFAULT NULL,
  `compatibility_version` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`device_id`, `device_name`, `status`, `provider_id`, `compatibility_version`) VALUES
(1001, 'Living Room Light', 'ON', 101, 'v2.0'),
(1002, 'Front Door Lock', 'ON', 101, 'v3.1'),
(1003, 'Garage Camera', 'OFF', 102, 'v1.5'),
(1004, 'Thermostat', 'ON', 102, 'v4.0'),
(1005, 'SMART LIGHT', 'OFF', 105, '5.0');

-- --------------------------------------------------------

--
-- Table structure for table `device_assignments`
--

CREATE TABLE `device_assignments` (
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_assignments`
--

INSERT INTO `device_assignments` (`assignment_id`, `user_id`, `device_id`) VALUES
(1, 6, 1001),
(2, 6, 1002),
(3, 7, 1003),
(4, 8, 1004),
(5, 1, 1001),
(6, 1, 1002),
(7, 1, 1003),
(8, 1, 1004),
(9, 11, 1003),
(10, 11, 1004),
(11, 11, 1002),
(12, 11, 1001),
(13, 9, 1003);

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

CREATE TABLE `providers` (
  `provider_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `support_contact` varchar(50) DEFAULT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`provider_id`, `company_name`, `support_contact`, `registered_at`, `user_id`) VALUES
(101, 'Globex Corp IoT', 'support@globex.com', '2025-10-28 04:10:59', 3),
(102, 'SmartDevice Co.', 'help@smartdev.co', '2025-10-28 04:10:59', 4),
(103, 'New Provider Company', 'jake@gmail.com', '2025-11-22 04:31:48', 16),
(104, 'New Provider Company', 'rondo@gmail.com', '2025-11-22 04:32:11', 18),
(105, 'New Provider Company', 'kio@gmail.com', '2025-11-22 04:37:23', 20);

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `action` enum('ON','OFF') NOT NULL,
  `scheduled_time` datetime NOT NULL,
  `status` enum('pending','executed') DEFAULT 'pending',
  `executed_by` enum('user','scheduler') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `user_id`, `device_id`, `action`, `scheduled_time`, `status`, `executed_by`, `created_at`) VALUES
(1, 6, 1001, 'OFF', '2025-10-28 10:40:59', 'pending', 'user', '2025-10-28 04:10:59'),
(2, 7, 1003, 'ON', '2025-10-28 11:40:59', 'pending', 'scheduler', '2025-10-28 04:10:59'),
(3, 11, 1003, 'ON', '2025-11-21 19:22:00', 'pending', 'user', '2025-11-21 13:51:50'),
(4, 11, 1003, 'OFF', '2025-11-21 07:23:00', 'pending', 'user', '2025-11-21 13:52:50'),
(5, 11, 1004, 'ON', '2025-11-22 09:17:00', 'executed', 'scheduler', '2025-11-22 03:46:35'),
(6, 11, 1003, 'ON', '2025-11-22 09:18:00', 'pending', 'user', '2025-11-22 03:48:48'),
(7, 11, 1003, 'OFF', '2025-11-22 09:19:00', 'pending', 'user', '2025-11-22 03:49:29'),
(8, 11, 1003, 'OFF', '2025-11-22 09:20:00', 'executed', 'scheduler', '2025-11-22 03:49:47'),
(9, 11, 1003, 'ON', '2025-11-22 09:22:00', 'executed', 'scheduler', '2025-11-22 03:50:08'),
(10, 11, 1003, 'OFF', '2025-11-22 09:27:00', 'executed', 'scheduler', '2025-11-22 03:55:47');

-- --------------------------------------------------------

--
-- Table structure for table `updates`
--

CREATE TABLE `updates` (
  `update_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `update_description` text DEFAULT NULL,
  `update_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `updates`
--

INSERT INTO `updates` (`update_id`, `provider_id`, `device_id`, `update_description`, `update_date`) VALUES
(1, 105, 1005, 'Added new features\r\n', '2025-11-22 04:37:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user','provider') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User 1', 'admin1@test.com', 'admin123', 'admin', '2025-10-28 04:10:59'),
(3, 'Provider User 1', 'provider1@test.com', 'provider123', 'provider', '2025-10-28 04:10:59'),
(4, 'Provider User 2', 'provider2@test.com', 'provider123', 'provider', '2025-10-28 04:10:59'),
(6, 'Standard User 1', 'user1@test.com', 'user123', 'user', '2025-10-28 04:10:59'),
(7, 'Standard User 2', 'user2@test.com', 'user123', 'user', '2025-10-28 04:10:59'),
(8, 'Standard User 3', 'user3@test.com', 'user123', 'user', '2025-10-28 04:10:59'),
(9, 'Standard User 4', 'user4@test.com', 'user123', 'user', '2025-10-28 04:10:59'),
(10, 'Guest User', 'user5@test.com', 'user123', 'user', '2025-10-28 04:10:59'),
(11, 'Jonson', 'jonson@gmail.com', 'jonson123', 'user', '2025-11-21 13:50:13'),
(12, 'Arunagirinathan', 'arunagirinathan@gmail.com', 'arun123', 'provider', '2025-11-22 04:04:02'),
(13, 'Pranav', 'pranav@gmail.com', 'pranav123', 'admin', '2025-11-22 04:10:27'),
(14, '', '', '', '', '2025-11-22 04:30:01'),
(16, 'jake', 'jake@gmail.com', 'jake123', 'provider', '2025-11-22 04:31:48'),
(18, 'rondo', 'rondo@gmail.com', 'rondo123', 'provider', '2025-11-22 04:32:11'),
(20, 'kio', 'kio@gmail.com', 'kio123', 'provider', '2025-11-22 04:37:23'),
(21, 'Arunagirinathan', 'arung@gmail.com', 'arun123', 'user', '2025-11-25 03:32:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`device_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `device_assignments`
--
ALTER TABLE `device_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`provider_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `updates`
--
ALTER TABLE `updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `device_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1006;

--
-- AUTO_INCREMENT for table `device_assignments`
--
ALTER TABLE `device_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE SET NULL;

--
-- Constraints for table `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`provider_id`) ON DELETE SET NULL;

--
-- Constraints for table `device_assignments`
--
ALTER TABLE `device_assignments`
  ADD CONSTRAINT `device_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `device_assignments_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE;

--
-- Constraints for table `providers`
--
ALTER TABLE `providers`
  ADD CONSTRAINT `providers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE;

--
-- Constraints for table `updates`
--
ALTER TABLE `updates`
  ADD CONSTRAINT `updates_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`provider_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `updates_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
