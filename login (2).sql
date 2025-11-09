-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2025 at 04:00 PM
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
-- Database: `login`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(30) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin') DEFAULT 'admin',
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `email`, `password`, `role`, `profile_picture`) VALUES
(1, 'Manu', 'admin@example.com', 'admin@123', 'admin', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `booking_datetime` datetime NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `mechanic_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `time_slot` varchar(100) NOT NULL,
  `appointment_date` date NOT NULL,
  `prefereddate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `vehicle_id`, `booking_datetime`, `status`, `mechanic_id`, `staff_id`, `time_slot`, `appointment_date`, `prefereddate`) VALUES
(1, 1, 1, '2025-09-17 05:25:25', 'Assigned', 1, NULL, '10:57', '2025-09-18', NULL),
(2, 3, 2, '2025-09-17 08:38:45', 'Confirmed', 1, NULL, '14:09', '2025-09-20', NULL),
(3, 1, 1, '2025-09-19 08:33:26', 'In Progress', 1, NULL, '14:04', '2025-09-29', NULL),
(4, 5, 3, '2025-09-19 08:53:13', 'In Progress', 1, 1, '15:12', '2025-10-14', NULL),
(5, 5, 3, '2025-09-19 08:57:01', 'Assigned', 2, 1, '15:02', '2025-10-09', NULL),
(7, 1, 1, '2025-09-25 09:13:57', 'Assigned', 2, 1, '13:59', '2025-10-04', '2025-09-29'),
(8, 1, 1, '2025-09-25 09:21:26', 'Completed', 1, 1, '16:13', '2025-10-02', '2025-10-02'),
(9, 1, 4, '2025-09-25 18:50:41', 'Completed', 2, 1, '13:52', '2025-10-05', '2025-09-26');

-- --------------------------------------------------------

--
-- Table structure for table `booking_services`
--

CREATE TABLE `booking_services` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `service_price` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_services`
--

INSERT INTO `booking_services` (`id`, `booking_id`, `service_id`, `service_price`, `payment_status`) VALUES
(1, 1, 4, 2000.00, 'unpaid'),
(2, 2, 3, 100.00, 'unpaid'),
(3, 3, 1, 500.00, 'paid'),
(4, 4, 3, 100.00, 'unpaid'),
(5, 4, 9, 1000.00, 'unpaid'),
(6, 4, 6, 1200.00, 'unpaid'),
(7, 4, 4, 2000.00, 'unpaid'),
(8, 5, 8, 2500.00, 'unpaid'),
(10, 7, 8, 2500.00, 'paid'),
(11, 7, 3, 100.00, 'paid'),
(12, 8, 5, 2500.00, 'paid'),
(13, 8, 9, 1000.00, 'paid'),
(14, 9, 8, 2500.00, 'paid'),
(15, 10, 8, 2500.00, 'paid'),
(16, 10, 3, 100.00, 'paid'),
(17, 11, 8, 2500.00, 'paid'),
(18, 11, 3, 100.00, 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comments` text NOT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `leave_reason` text NOT NULL,
  `for_when` date NOT NULL,
  `till_when` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`id`, `staff_id`, `leave_reason`, `for_when`, `till_when`, `created_at`, `status`) VALUES
(1, 1, 'i want leave', '2025-09-22', '2025-09-24', '2025-09-17 03:05:56', 'approved'),
(2, 1, 'Emergency Leave', '2025-09-26', '2025-09-28', '2025-09-25 16:16:31', NULL),
(3, 1, 'Emergency Leave', '2025-09-27', '2025-09-28', '2025-09-26 07:03:24', 'approved'),
(4, 1, 'Emergency Leave', '2025-09-27', '2025-09-27', '2025-09-26 07:24:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mechanics`
--

CREATE TABLE `mechanics` (
  `mechanic_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `profession` varchar(100) NOT NULL,
  `status` enum('free','assigned') DEFAULT 'free',
  `joined_date` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mechanics`
--

INSERT INTO `mechanics` (`mechanic_id`, `name`, `age`, `profession`, `status`, `joined_date`, `address`, `phone_number`, `email`) VALUES
(1, 'sasi', 34, 'engine', 'free', '2025-09-17', 'kdjbfjbdf', '877878723', 'sasi@gmail.com'),
(2, 'sreehari', 32, 'Tyre Service', 'assigned', '2025-09-19', 'malabari p.o', '852478391', 'sree432@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `staff_id`, `message`, `response`, `created_at`, `responded_at`) VALUES
(1, 2, 1, 'jkhhgytdtuk', 'vjhjbj', '2025-09-16 04:28:06', '2025-09-16 05:19:55'),
(2, 4, NULL, 'bad experience', NULL, '2025-09-17 13:08:49', NULL),
(3, 3, 1, 'hey i need some help', 'we are here to help', '2025-10-01 08:10:54', '2025-10-01 08:11:36'),
(4, 3, 1, 'hiiiiiiiiiiiiiiiiiiiii', 'hello', '2025-10-01 08:12:48', '2025-10-01 08:13:14');

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `user_id` int(11) NOT NULL,
  `name` varchar(30) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phonenumber` varchar(10) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`user_id`, `name`, `email`, `password`, `phonenumber`, `profile_picture`, `created_at`) VALUES
(1, 'naveen', 'naveensaji321@gmail.com', 'Naveen@12', '6282952601', '68cb758b6e09a_naveensaji321@gmail.com.jpg', '2025-09-09 03:01:25'),
(2, 'vishnu', 'vishnu2@gmail.com', 'Vishnu@123', '9345727537', NULL, '2025-09-16 03:32:34'),
(3, 'vivek', 'bennyvivek3@gmail.com', 'Vivek@123', '6282795821', NULL, '2025-09-17 06:37:00'),
(4, 'adithian sonthosh', 'adithian12@gmail.com', 'Sonthosh@123', '6238167077', NULL, '2025-09-17 13:08:04'),
(5, 'sreehari', 'sree234@gmail.com', 'Sree@123', '4356253763', NULL, '2025-09-19 06:42:12'),
(6, 'savio', 'savio12@gmail.com', 'Savio@12', '2435263536', NULL, '2025-09-25 15:56:08');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `estimated_time` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `duration_minutes` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `marketing_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `description`, `price`, `estimated_time`, `category`, `status`, `duration_minutes`, `image`, `marketing_description`) VALUES
(1, 'Car Washing', 'Exterior & interior wash', 500.00, NULL, 'Car service', 'active', 30, 'uploads/services/service_1_1757387649.jpg', NULL),
(2, 'Tyre Service', 'Tyre check & replacement', 800.00, NULL, 'Car service', 'active', 45, 'uploads/services/service_2_1757425185.jpg', NULL),
(3, 'Air Filling', 'Check & refill air pressure', 100.00, NULL, 'Car service', 'active', 10, 'uploads/services/service_3_1757425171.jpg', NULL),
(4, 'Polishing & Waxing', 'Full body polish & wax', 2000.00, NULL, 'Car service', 'active', 90, 'uploads/services/service_4_1757425283.jpg', NULL),
(5, 'Engine Cleaning', 'Deep clean engine', 2500.00, NULL, 'Car service', 'active', 120, 'uploads/services/service_5_1757425535.jpg', NULL),
(6, 'Oil Change', 'Replace engine oil', 1200.00, NULL, 'Car service', 'active', 40, 'uploads/services/service_6_1757425868.jpg', NULL),
(7, 'Battery Replacement', 'Install new battery', 4000.00, NULL, 'Car service', 'active', 60, 'uploads/services/service_7_1757425831.jpg', NULL),
(8, 'AC Service', 'Clean & refill AC gas', 2500.00, NULL, 'Car service', 'active', 90, 'uploads/services/service_8_1757425818.jpg', NULL),
(9, 'General Inspection', 'Full vehicle check-up', 1000.00, NULL, 'Car service', 'active', 60, 'uploads/services/service_9_1757425851.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `service_details`
--

CREATE TABLE `service_details` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `why_choose` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_includes`
--

CREATE TABLE `service_includes` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `included_item` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `staffname` varchar(30) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('staff') DEFAULT 'staff',
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `staffname`, `email`, `password`, `phone`, `created_at`, `role`, `profile_picture`) VALUES
(1, 'staff1', 'staff@example.com', 'staff123', '2345678910', '2025-09-09 03:00:59', 'staff', NULL),
(75, 'Rahul', 'rahul@gmail.com', 'rahul123', '7868772334', '2025-09-17 03:51:54', 'staff', NULL),
(287, 'thambu', 'tham2@gmail.com', 'tha@123', '9347562388', '2025-09-26 07:09:31', 'staff', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `registration_no` varchar(20) DEFAULT NULL,
  `year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`vehicle_id`, `user_id`, `vehicle_type`, `brand`, `model`, `registration_no`, `year`) VALUES
(1, 1, 'SUV', 'MERCEDES', 'BENZ', 'KL 06 2009', 2019),
(2, 3, 'Sedan', 'BMW', 'C', 'KL 03 6878', 2010),
(3, 5, 'Sedan', 'BMW', 'c700', 'KL 03 2014', 2030),
(4, 1, 'Sedan', 'MERCEDES', 'c700', 'KL 06 2056', 2011),
(7, 1, 'Hatchback', 'bharat', 'j', 'KL 14 GG 6565', 1998),
(8, 1, 'Truck', 'fgh', 'h2', 'KL 14 HG 7876', 2025);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `mechanic_id` (`mechanic_id`),
  ADD KEY `bookings_staff_fk` (`staff_id`);

--
-- Indexes for table `booking_services`
--
ALTER TABLE `booking_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `fk_feedback_user` (`user_id`),
  ADD KEY `fk_feedback_booking` (`booking_id`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_staff_dates` (`staff_id`,`for_when`,`till_when`);

--
-- Indexes for table `mechanics`
--
ALTER TABLE `mechanics`
  ADD PRIMARY KEY (`mechanic_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_details`
--
ALTER TABLE `service_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_service_details_services` (`service_id`);

--
-- Indexes for table `service_includes`
--
ALTER TABLE `service_includes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=336;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `booking_services`
--
ALTER TABLE `booking_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mechanics`
--
ALTER TABLE `mechanics`
  MODIFY `mechanic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `service_details`
--
ALTER TABLE `service_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_includes`
--
ALTER TABLE `service_includes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=338;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `register` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_staff_fk` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_services`
--
ALTER TABLE `booking_services`
  ADD CONSTRAINT `booking_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD CONSTRAINT `fk_feedback_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `register` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `service_details`
--
ALTER TABLE `service_details`
  ADD CONSTRAINT `fk_service_details_services` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `service_includes`
--
ALTER TABLE `service_includes`
  ADD CONSTRAINT `service_includes_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `register` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
