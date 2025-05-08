-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2025 at 10:05 AM
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
-- Database: `sitin`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `ID` int(11) NOT NULL,
  `USERNAME` varchar(30) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`ID`, `USERNAME`, `PASSWORD`) VALUES
(1, 'admin', '$2y$10$lhNDlxLGkNVVHEZjMry92OYXVeQYlc4d9fEcXIwmGGk8eePHuugwO');

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `ID` int(11) NOT NULL,
  `TITLE` varchar(255) DEFAULT NULL,
  `MESSAGE` text NOT NULL,
  `TIMESTAMP` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`ID`, `TITLE`, `MESSAGE`, `TIMESTAMP`) VALUES
(4, 'Greeting Announcement', 'Hello Everyone!!!', '2025-03-26 15:03:46'),
(12, 'Finals Week', 'The Finals week is on next week May 12-16. Settle your accounts. Thank you', '2025-05-05 19:00:01');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `ID` int(11) NOT NULL,
  `SITIN_RECORD_ID` int(11) NOT NULL,
  `STUDENT_ID` varchar(50) NOT NULL,
  `RATING` int(1) NOT NULL,
  `COMMENT` text DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`ID`, `SITIN_RECORD_ID`, `STUDENT_ID`, `RATING`, `COMMENT`, `CREATED_AT`) VALUES
(1, 41, '2000', 5, 'nice', '2025-04-07 16:41:32'),
(2, 38, '2000', 1, 'meh', '2025-04-07 16:44:49'),
(3, 40, '20951505', 5, 'good experience', '2025-04-07 16:45:32'),
(4, 39, '20951505', 1, 'rude', '2025-04-07 16:47:04'),
(5, 45, '3000', 5, 'Nicee', '2025-04-09 15:30:27');

-- --------------------------------------------------------

--
-- Table structure for table `lab_resources`
--

CREATE TABLE `lab_resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `resource_link` varchar(500) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_resources`
--

INSERT INTO `lab_resources` (`id`, `title`, `description`, `category`, `resource_link`, `file_path`, `created_at`) VALUES
(1, 'Introduction to Programming', 'A beginner friendly tutorial on C programming', 'Programming', 'https://www.youtube.com/watch?v=87SH2Cn0s9A', NULL, '2025-05-06 02:09:09'),
(2, 'Test', 'test123', 'Database', '', 'uploads/res_681903c4adae0.xlsx', '2025-05-06 02:30:28'),
(3, 'Configure SSH', 'asdsdads', 'Networking', '', 'uploads/res_6819043d9d630.pka', '2025-05-06 02:32:29');

-- --------------------------------------------------------

--
-- Table structure for table `lab_schedule`
--

CREATE TABLE `lab_schedule` (
  `id` int(11) NOT NULL,
  `lab_room` varchar(10) NOT NULL,
  `day_of_week` enum('Monday/Wednesday','Tuesday/Thursday','Friday','Saturday') NOT NULL,
  `time_slot` varchar(20) NOT NULL,
  `status` enum('Available','Occupied') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_schedule`
--

INSERT INTO `lab_schedule` (`id`, `lab_room`, `day_of_week`, `time_slot`, `status`) VALUES
(1, '524', 'Monday/Wednesday', '7:30AM-9:00AM', 'Occupied'),
(2, '524', 'Monday/Wednesday', '9:00AM-10:30AM', 'Occupied'),
(3, '524', 'Monday/Wednesday', '10:30AM-12:00PM', 'Available'),
(4, '524', 'Monday/Wednesday', '12:00PM-1:00PM', 'Available'),
(5, '524', 'Monday/Wednesday', '1:00PM-3:00PM', 'Available'),
(6, '524', 'Monday/Wednesday', '3:00PM-4:30PM', 'Available'),
(7, '524', 'Monday/Wednesday', '4:30PM-6:00PM', 'Available'),
(8, '524', 'Monday/Wednesday', '6:00PM-7:30PM', 'Available'),
(9, '524', 'Monday/Wednesday', '7:30PM-9:00PM', 'Available'),
(10, '524', 'Tuesday/Thursday', '7:30AM-9:00AM', 'Available'),
(11, '524', 'Tuesday/Thursday', '9:00AM-10:30AM', 'Available'),
(12, '524', 'Tuesday/Thursday', '10:30AM-12:00PM', 'Occupied'),
(13, '524', 'Tuesday/Thursday', '12:00PM-1:00PM', 'Available'),
(14, '524', 'Tuesday/Thursday', '1:00PM-3:00PM', 'Available'),
(15, '524', 'Tuesday/Thursday', '3:00PM-4:30PM', 'Available'),
(16, '524', 'Tuesday/Thursday', '4:30PM-6:00PM', 'Available'),
(17, '524', 'Tuesday/Thursday', '6:00PM-7:30PM', 'Available'),
(18, '524', 'Tuesday/Thursday', '7:30PM-9:00PM', 'Available'),
(19, '524', 'Friday', '7:30AM-9:00AM', 'Available'),
(20, '524', 'Friday', '9:00AM-10:30AM', 'Available'),
(21, '524', 'Friday', '10:30AM-12:00PM', 'Available'),
(22, '524', 'Friday', '12:00PM-1:00PM', 'Available'),
(23, '524', 'Friday', '1:00PM-3:00PM', 'Occupied'),
(24, '524', 'Friday', '3:00PM-4:30PM', 'Available'),
(25, '524', 'Friday', '4:30PM-6:00PM', 'Available'),
(26, '524', 'Friday', '6:00PM-7:30PM', 'Available'),
(27, '524', 'Friday', '7:30PM-9:00PM', 'Available'),
(28, '524', 'Saturday', '7:30AM-9:00AM', 'Available'),
(29, '524', 'Saturday', '9:00AM-10:30AM', 'Available'),
(30, '524', 'Saturday', '10:30AM-12:00PM', 'Available'),
(31, '524', 'Saturday', '12:00PM-1:00PM', 'Available'),
(32, '524', 'Saturday', '1:00PM-3:00PM', 'Available'),
(33, '524', 'Saturday', '3:00PM-4:30PM', 'Occupied'),
(34, '524', 'Saturday', '4:30PM-6:00PM', 'Available'),
(35, '524', 'Saturday', '6:00PM-7:30PM', 'Available'),
(36, '524', 'Saturday', '7:30PM-9:00PM', 'Available'),
(37, '526', 'Monday/Wednesday', '7:30AM-9:00AM', 'Available'),
(38, '526', 'Monday/Wednesday', '9:00AM-10:30AM', 'Available'),
(39, '526', 'Monday/Wednesday', '10:30AM-12:00PM', 'Available'),
(40, '526', 'Monday/Wednesday', '12:00PM-1:00PM', 'Available'),
(41, '526', 'Monday/Wednesday', '1:00PM-3:00PM', 'Available'),
(42, '526', 'Monday/Wednesday', '3:00PM-4:30PM', 'Available'),
(43, '526', 'Monday/Wednesday', '4:30PM-6:00PM', 'Available'),
(44, '526', 'Monday/Wednesday', '6:00PM-7:30PM', 'Available'),
(45, '526', 'Monday/Wednesday', '7:30PM-9:00PM', 'Available'),
(46, '526', 'Tuesday/Thursday', '7:30AM-9:00AM', 'Available'),
(47, '526', 'Tuesday/Thursday', '9:00AM-10:30AM', 'Available'),
(48, '526', 'Tuesday/Thursday', '10:30AM-12:00PM', 'Available'),
(49, '526', 'Tuesday/Thursday', '12:00PM-1:00PM', 'Occupied'),
(50, '526', 'Tuesday/Thursday', '1:00PM-3:00PM', 'Occupied'),
(51, '526', 'Tuesday/Thursday', '3:00PM-4:30PM', 'Occupied'),
(52, '526', 'Tuesday/Thursday', '4:30PM-6:00PM', 'Available'),
(53, '526', 'Tuesday/Thursday', '6:00PM-7:30PM', 'Available'),
(54, '526', 'Tuesday/Thursday', '7:30PM-9:00PM', 'Available'),
(55, '526', 'Friday', '7:30AM-9:00AM', 'Available'),
(56, '526', 'Friday', '9:00AM-10:30AM', 'Available'),
(57, '526', 'Friday', '10:30AM-12:00PM', 'Available'),
(58, '526', 'Friday', '12:00PM-1:00PM', 'Available'),
(59, '526', 'Friday', '1:00PM-3:00PM', 'Available'),
(60, '526', 'Friday', '3:00PM-4:30PM', 'Available'),
(61, '526', 'Friday', '4:30PM-6:00PM', 'Available'),
(62, '526', 'Friday', '6:00PM-7:30PM', 'Available'),
(63, '526', 'Friday', '7:30PM-9:00PM', 'Available'),
(64, '526', 'Saturday', '7:30AM-9:00AM', 'Available'),
(65, '526', 'Saturday', '9:00AM-10:30AM', 'Available'),
(66, '526', 'Saturday', '10:30AM-12:00PM', 'Available'),
(67, '526', 'Saturday', '12:00PM-1:00PM', 'Available'),
(68, '526', 'Saturday', '1:00PM-3:00PM', 'Available'),
(69, '526', 'Saturday', '3:00PM-4:30PM', 'Available'),
(70, '526', 'Saturday', '4:30PM-6:00PM', 'Available'),
(71, '526', 'Saturday', '6:00PM-7:30PM', 'Available'),
(72, '526', 'Saturday', '7:30PM-9:00PM', 'Available'),
(73, '528', 'Monday/Wednesday', '7:30AM-9:00AM', 'Occupied'),
(74, '528', 'Monday/Wednesday', '9:00AM-10:30AM', 'Occupied'),
(75, '528', 'Monday/Wednesday', '10:30AM-12:00PM', 'Occupied'),
(76, '528', 'Monday/Wednesday', '12:00PM-1:00PM', 'Occupied'),
(77, '528', 'Monday/Wednesday', '1:00PM-3:00PM', 'Available'),
(78, '528', 'Monday/Wednesday', '3:00PM-4:30PM', 'Available'),
(79, '528', 'Monday/Wednesday', '4:30PM-6:00PM', 'Available'),
(80, '528', 'Monday/Wednesday', '6:00PM-7:30PM', 'Available'),
(81, '528', 'Monday/Wednesday', '7:30PM-9:00PM', 'Available'),
(82, '528', 'Tuesday/Thursday', '7:30AM-9:00AM', 'Available'),
(83, '528', 'Tuesday/Thursday', '9:00AM-10:30AM', 'Available'),
(84, '528', 'Tuesday/Thursday', '10:30AM-12:00PM', 'Available'),
(85, '528', 'Tuesday/Thursday', '12:00PM-1:00PM', 'Available'),
(86, '528', 'Tuesday/Thursday', '1:00PM-3:00PM', 'Available'),
(87, '528', 'Tuesday/Thursday', '3:00PM-4:30PM', 'Available'),
(88, '528', 'Tuesday/Thursday', '4:30PM-6:00PM', 'Available'),
(89, '528', 'Tuesday/Thursday', '6:00PM-7:30PM', 'Available'),
(90, '528', 'Tuesday/Thursday', '7:30PM-9:00PM', 'Available'),
(91, '528', 'Friday', '7:30AM-9:00AM', 'Available'),
(92, '528', 'Friday', '9:00AM-10:30AM', 'Available'),
(93, '528', 'Friday', '10:30AM-12:00PM', 'Available'),
(94, '528', 'Friday', '12:00PM-1:00PM', 'Available'),
(95, '528', 'Friday', '1:00PM-3:00PM', 'Available'),
(96, '528', 'Friday', '3:00PM-4:30PM', 'Available'),
(97, '528', 'Friday', '4:30PM-6:00PM', 'Available'),
(98, '528', 'Friday', '6:00PM-7:30PM', 'Available'),
(99, '528', 'Friday', '7:30PM-9:00PM', 'Available'),
(100, '528', 'Saturday', '7:30AM-9:00AM', 'Available'),
(101, '528', 'Saturday', '9:00AM-10:30AM', 'Available'),
(102, '528', 'Saturday', '10:30AM-12:00PM', 'Available'),
(103, '528', 'Saturday', '12:00PM-1:00PM', 'Available'),
(104, '528', 'Saturday', '1:00PM-3:00PM', 'Available'),
(105, '528', 'Saturday', '3:00PM-4:30PM', 'Available'),
(106, '528', 'Saturday', '4:30PM-6:00PM', 'Available'),
(107, '528', 'Saturday', '6:00PM-7:30PM', 'Available'),
(108, '528', 'Saturday', '7:30PM-9:00PM', 'Available'),
(109, '530', 'Monday/Wednesday', '7:30AM-9:00AM', 'Available'),
(110, '530', 'Monday/Wednesday', '9:00AM-10:30AM', 'Available'),
(111, '530', 'Monday/Wednesday', '10:30AM-12:00PM', 'Available'),
(112, '530', 'Monday/Wednesday', '12:00PM-1:00PM', 'Available'),
(113, '530', 'Monday/Wednesday', '1:00PM-3:00PM', 'Available'),
(114, '530', 'Monday/Wednesday', '3:00PM-4:30PM', 'Available'),
(115, '530', 'Monday/Wednesday', '4:30PM-6:00PM', 'Available'),
(116, '530', 'Monday/Wednesday', '6:00PM-7:30PM', 'Available'),
(117, '530', 'Monday/Wednesday', '7:30PM-9:00PM', 'Available'),
(118, '530', 'Tuesday/Thursday', '7:30AM-9:00AM', 'Available'),
(119, '530', 'Tuesday/Thursday', '9:00AM-10:30AM', 'Occupied'),
(120, '530', 'Tuesday/Thursday', '10:30AM-12:00PM', 'Available'),
(121, '530', 'Tuesday/Thursday', '12:00PM-1:00PM', 'Available'),
(122, '530', 'Tuesday/Thursday', '1:00PM-3:00PM', 'Available'),
(123, '530', 'Tuesday/Thursday', '3:00PM-4:30PM', 'Available'),
(124, '530', 'Tuesday/Thursday', '4:30PM-6:00PM', 'Available'),
(125, '530', 'Tuesday/Thursday', '6:00PM-7:30PM', 'Available'),
(126, '530', 'Tuesday/Thursday', '7:30PM-9:00PM', 'Available'),
(127, '530', 'Friday', '7:30AM-9:00AM', 'Occupied'),
(128, '530', 'Friday', '9:00AM-10:30AM', 'Occupied'),
(129, '530', 'Friday', '10:30AM-12:00PM', 'Available'),
(130, '530', 'Friday', '12:00PM-1:00PM', 'Available'),
(131, '530', 'Friday', '1:00PM-3:00PM', 'Available'),
(132, '530', 'Friday', '3:00PM-4:30PM', 'Available'),
(133, '530', 'Friday', '4:30PM-6:00PM', 'Available'),
(134, '530', 'Friday', '6:00PM-7:30PM', 'Available'),
(135, '530', 'Friday', '7:30PM-9:00PM', 'Available'),
(136, '530', 'Saturday', '7:30AM-9:00AM', 'Available'),
(137, '530', 'Saturday', '9:00AM-10:30AM', 'Available'),
(138, '530', 'Saturday', '10:30AM-12:00PM', 'Available'),
(139, '530', 'Saturday', '12:00PM-1:00PM', 'Available'),
(140, '530', 'Saturday', '1:00PM-3:00PM', 'Available'),
(141, '530', 'Saturday', '3:00PM-4:30PM', 'Available'),
(142, '530', 'Saturday', '4:30PM-6:00PM', 'Available'),
(143, '530', 'Saturday', '6:00PM-7:30PM', 'Available'),
(144, '530', 'Saturday', '7:30PM-9:00PM', 'Available'),
(145, '542', 'Monday/Wednesday', '7:30AM-9:00AM', 'Occupied'),
(146, '542', 'Monday/Wednesday', '9:00AM-10:30AM', 'Available'),
(147, '542', 'Monday/Wednesday', '10:30AM-12:00PM', 'Available'),
(148, '542', 'Monday/Wednesday', '12:00PM-1:00PM', 'Occupied'),
(149, '542', 'Monday/Wednesday', '1:00PM-3:00PM', 'Available'),
(150, '542', 'Monday/Wednesday', '3:00PM-4:30PM', 'Available'),
(151, '542', 'Monday/Wednesday', '4:30PM-6:00PM', 'Available'),
(152, '542', 'Monday/Wednesday', '6:00PM-7:30PM', 'Available'),
(153, '542', 'Monday/Wednesday', '7:30PM-9:00PM', 'Available'),
(154, '542', 'Tuesday/Thursday', '7:30AM-9:00AM', 'Available'),
(155, '542', 'Tuesday/Thursday', '9:00AM-10:30AM', 'Available'),
(156, '542', 'Tuesday/Thursday', '10:30AM-12:00PM', 'Available'),
(157, '542', 'Tuesday/Thursday', '12:00PM-1:00PM', 'Available'),
(158, '542', 'Tuesday/Thursday', '1:00PM-3:00PM', 'Available'),
(159, '542', 'Tuesday/Thursday', '3:00PM-4:30PM', 'Available'),
(160, '542', 'Tuesday/Thursday', '4:30PM-6:00PM', 'Available'),
(161, '542', 'Tuesday/Thursday', '6:00PM-7:30PM', 'Available'),
(162, '542', 'Tuesday/Thursday', '7:30PM-9:00PM', 'Available'),
(163, '542', 'Friday', '7:30AM-9:00AM', 'Available'),
(164, '542', 'Friday', '9:00AM-10:30AM', 'Available'),
(165, '542', 'Friday', '10:30AM-12:00PM', 'Available'),
(166, '542', 'Friday', '12:00PM-1:00PM', 'Available'),
(167, '542', 'Friday', '1:00PM-3:00PM', 'Available'),
(168, '542', 'Friday', '3:00PM-4:30PM', 'Available'),
(169, '542', 'Friday', '4:30PM-6:00PM', 'Available'),
(170, '542', 'Friday', '6:00PM-7:30PM', 'Available'),
(171, '542', 'Friday', '7:30PM-9:00PM', 'Available'),
(172, '542', 'Saturday', '7:30AM-9:00AM', 'Available'),
(173, '542', 'Saturday', '9:00AM-10:30AM', 'Available'),
(174, '542', 'Saturday', '10:30AM-12:00PM', 'Available'),
(175, '542', 'Saturday', '12:00PM-1:00PM', 'Available'),
(176, '542', 'Saturday', '1:00PM-3:00PM', 'Available'),
(177, '542', 'Saturday', '3:00PM-4:30PM', 'Available'),
(178, '542', 'Saturday', '4:30PM-6:00PM', 'Available'),
(179, '542', 'Saturday', '6:00PM-7:30PM', 'Available'),
(180, '542', 'Saturday', '7:30PM-9:00PM', 'Available'),
(181, '544', 'Monday/Wednesday', '7:30AM-9:00AM', 'Occupied'),
(182, '544', 'Monday/Wednesday', '9:00AM-10:30AM', 'Available'),
(183, '544', 'Monday/Wednesday', '10:30AM-12:00PM', 'Occupied'),
(184, '544', 'Monday/Wednesday', '12:00PM-1:00PM', 'Available'),
(185, '544', 'Monday/Wednesday', '1:00PM-3:00PM', 'Available'),
(186, '544', 'Monday/Wednesday', '3:00PM-4:30PM', 'Available'),
(187, '544', 'Monday/Wednesday', '4:30PM-6:00PM', 'Available'),
(188, '544', 'Monday/Wednesday', '6:00PM-7:30PM', 'Available'),
(189, '544', 'Monday/Wednesday', '7:30PM-9:00PM', 'Available'),
(190, '544', 'Tuesday/Thursday', '7:30AM-9:00AM', 'Occupied'),
(191, '544', 'Tuesday/Thursday', '9:00AM-10:30AM', 'Available'),
(192, '544', 'Tuesday/Thursday', '10:30AM-12:00PM', 'Occupied'),
(193, '544', 'Tuesday/Thursday', '12:00PM-1:00PM', 'Available'),
(194, '544', 'Tuesday/Thursday', '1:00PM-3:00PM', 'Available'),
(195, '544', 'Tuesday/Thursday', '3:00PM-4:30PM', 'Available'),
(196, '544', 'Tuesday/Thursday', '4:30PM-6:00PM', 'Available'),
(197, '544', 'Tuesday/Thursday', '6:00PM-7:30PM', 'Available'),
(198, '544', 'Tuesday/Thursday', '7:30PM-9:00PM', 'Available'),
(199, '544', 'Friday', '7:30AM-9:00AM', 'Occupied'),
(200, '544', 'Friday', '9:00AM-10:30AM', 'Available'),
(201, '544', 'Friday', '10:30AM-12:00PM', 'Occupied'),
(202, '544', 'Friday', '12:00PM-1:00PM', 'Available'),
(203, '544', 'Friday', '1:00PM-3:00PM', 'Available'),
(204, '544', 'Friday', '3:00PM-4:30PM', 'Available'),
(205, '544', 'Friday', '4:30PM-6:00PM', 'Available'),
(206, '544', 'Friday', '6:00PM-7:30PM', 'Available'),
(207, '544', 'Friday', '7:30PM-9:00PM', 'Available'),
(208, '544', 'Saturday', '7:30AM-9:00AM', 'Available'),
(209, '544', 'Saturday', '9:00AM-10:30AM', 'Available'),
(210, '544', 'Saturday', '10:30AM-12:00PM', 'Available'),
(211, '544', 'Saturday', '12:00PM-1:00PM', 'Available'),
(212, '544', 'Saturday', '1:00PM-3:00PM', 'Available'),
(213, '544', 'Saturday', '3:00PM-4:30PM', 'Available'),
(214, '544', 'Saturday', '4:30PM-6:00PM', 'Available'),
(215, '544', 'Saturday', '6:00PM-7:30PM', 'Available'),
(216, '544', 'Saturday', '7:30PM-9:00PM', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `pc_status`
--

CREATE TABLE `pc_status` (
  `id` int(11) NOT NULL,
  `pc_number` varchar(10) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `status` enum('available','used','maintenance') DEFAULT 'available',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pc_status`
--

INSERT INTO `pc_status` (`id`, `pc_number`, `room_number`, `status`, `last_updated`) VALUES
(1, 'PC1', 'Room 524', 'available', '2025-05-07 17:19:33'),
(2, 'PC2', 'Room 524', 'maintenance', '2025-05-08 04:39:44'),
(3, 'PC3', 'Room 524', 'available', '2025-05-07 17:19:33'),
(4, 'PC4', 'Room 524', 'available', '2025-05-07 17:19:33'),
(5, 'PC5', 'Room 524', 'available', '2025-05-07 17:19:33'),
(6, 'PC6', 'Room 524', 'available', '2025-05-07 17:19:33'),
(7, 'PC7', 'Room 524', 'available', '2025-05-07 17:19:33'),
(8, 'PC8', 'Room 524', 'available', '2025-05-07 17:19:33'),
(9, 'PC9', 'Room 524', 'available', '2025-05-07 17:19:33'),
(10, 'PC10', 'Room 524', 'available', '2025-05-07 17:19:33'),
(11, 'PC11', 'Room 524', 'available', '2025-05-07 17:19:33'),
(12, 'PC12', 'Room 524', 'available', '2025-05-07 17:19:33'),
(13, 'PC13', 'Room 524', 'available', '2025-05-07 17:19:33'),
(14, 'PC14', 'Room 524', 'available', '2025-05-07 17:19:33'),
(15, 'PC15', 'Room 524', 'available', '2025-05-07 17:19:33'),
(16, 'PC16', 'Room 524', 'maintenance', '2025-05-08 07:54:48'),
(17, 'PC17', 'Room 524', 'available', '2025-05-07 17:19:33'),
(18, 'PC18', 'Room 524', 'available', '2025-05-07 17:19:33'),
(19, 'PC19', 'Room 524', 'available', '2025-05-07 17:19:33'),
(20, 'PC20', 'Room 524', 'available', '2025-05-07 17:19:33'),
(21, 'PC21', 'Room 524', 'available', '2025-05-07 17:19:33'),
(22, 'PC22', 'Room 524', 'available', '2025-05-07 17:19:33'),
(23, 'PC23', 'Room 524', 'available', '2025-05-07 17:19:33'),
(24, 'PC24', 'Room 524', 'available', '2025-05-07 17:19:33'),
(25, 'PC25', 'Room 524', 'maintenance', '2025-05-08 07:54:52'),
(26, 'PC26', 'Room 524', 'available', '2025-05-07 17:19:33'),
(27, 'PC27', 'Room 524', 'available', '2025-05-07 17:19:33'),
(28, 'PC28', 'Room 524', 'available', '2025-05-07 17:19:33'),
(29, 'PC29', 'Room 524', 'available', '2025-05-07 17:19:33'),
(30, 'PC30', 'Room 524', 'available', '2025-05-07 17:19:33'),
(31, 'PC31', 'Room 524', 'available', '2025-05-07 17:19:33'),
(32, 'PC32', 'Room 524', 'available', '2025-05-07 17:19:33'),
(33, 'PC33', 'Room 524', 'available', '2025-05-07 17:19:33'),
(34, 'PC34', 'Room 524', 'available', '2025-05-07 17:19:33'),
(35, 'PC35', 'Room 524', 'available', '2025-05-07 17:19:33'),
(36, 'PC36', 'Room 524', 'available', '2025-05-07 17:19:33'),
(37, 'PC37', 'Room 524', 'available', '2025-05-07 17:19:33'),
(38, 'PC38', 'Room 524', 'available', '2025-05-07 17:19:33'),
(39, 'PC39', 'Room 524', 'available', '2025-05-07 17:19:33'),
(40, 'PC40', 'Room 524', 'available', '2025-05-07 17:19:33'),
(41, 'PC1', 'Room 526', 'available', '2025-05-07 17:15:39'),
(42, 'PC2', 'Room 526', 'available', '2025-05-07 17:15:39'),
(43, 'PC3', 'Room 526', 'available', '2025-05-07 17:15:39'),
(44, 'PC4', 'Room 526', 'available', '2025-05-07 17:15:39'),
(45, 'PC5', 'Room 526', 'available', '2025-05-07 17:15:39'),
(46, 'PC6', 'Room 526', 'available', '2025-05-07 17:15:39'),
(47, 'PC7', 'Room 526', 'available', '2025-05-07 17:15:39'),
(48, 'PC8', 'Room 526', 'available', '2025-05-07 17:15:39'),
(49, 'PC9', 'Room 526', 'available', '2025-05-07 17:15:39'),
(50, 'PC10', 'Room 526', 'available', '2025-05-07 17:15:39'),
(51, 'PC11', 'Room 526', 'available', '2025-05-07 17:15:39'),
(52, 'PC12', 'Room 526', 'available', '2025-05-07 17:15:39'),
(53, 'PC13', 'Room 526', 'available', '2025-05-07 17:15:39'),
(54, 'PC14', 'Room 526', 'available', '2025-05-07 17:15:39'),
(55, 'PC15', 'Room 526', 'used', '2025-05-08 07:55:02'),
(56, 'PC16', 'Room 526', 'used', '2025-05-08 07:55:02'),
(57, 'PC17', 'Room 526', 'available', '2025-05-07 17:15:39'),
(58, 'PC18', 'Room 526', 'available', '2025-05-07 17:15:39'),
(59, 'PC19', 'Room 526', 'available', '2025-05-07 17:15:39'),
(60, 'PC20', 'Room 526', 'available', '2025-05-07 17:15:39'),
(61, 'PC21', 'Room 526', 'available', '2025-05-07 17:15:39'),
(62, 'PC22', 'Room 526', 'available', '2025-05-07 17:15:39'),
(63, 'PC23', 'Room 526', 'available', '2025-05-07 17:15:39'),
(64, 'PC24', 'Room 526', 'available', '2025-05-07 17:15:39'),
(65, 'PC25', 'Room 526', 'available', '2025-05-07 17:15:39'),
(66, 'PC26', 'Room 526', 'available', '2025-05-07 17:15:39'),
(67, 'PC27', 'Room 526', 'available', '2025-05-07 17:15:39'),
(68, 'PC28', 'Room 526', 'available', '2025-05-07 17:15:39'),
(69, 'PC29', 'Room 526', 'available', '2025-05-07 17:15:39'),
(70, 'PC30', 'Room 526', 'available', '2025-05-07 17:15:39'),
(71, 'PC31', 'Room 526', 'available', '2025-05-07 17:15:39'),
(72, 'PC32', 'Room 526', 'available', '2025-05-07 17:15:39'),
(73, 'PC33', 'Room 526', 'available', '2025-05-07 17:15:39'),
(74, 'PC34', 'Room 526', 'available', '2025-05-07 17:15:39'),
(75, 'PC35', 'Room 526', 'available', '2025-05-07 17:15:39'),
(76, 'PC36', 'Room 526', 'available', '2025-05-07 17:15:39'),
(77, 'PC37', 'Room 526', 'available', '2025-05-07 17:15:39'),
(78, 'PC38', 'Room 526', 'available', '2025-05-07 17:15:39'),
(79, 'PC39', 'Room 526', 'available', '2025-05-07 17:15:39'),
(80, 'PC40', 'Room 526', 'available', '2025-05-07 17:15:39'),
(81, 'PC1', 'Room 528', 'available', '2025-05-07 17:19:26'),
(82, 'PC2', 'Room 528', 'available', '2025-05-07 17:19:26'),
(83, 'PC3', 'Room 528', 'available', '2025-05-07 17:19:26'),
(84, 'PC4', 'Room 528', 'available', '2025-05-07 17:19:26'),
(85, 'PC5', 'Room 528', 'available', '2025-05-07 17:19:26'),
(86, 'PC6', 'Room 528', 'available', '2025-05-07 17:19:26'),
(87, 'PC7', 'Room 528', 'available', '2025-05-07 17:19:26'),
(88, 'PC8', 'Room 528', 'available', '2025-05-07 17:19:26'),
(89, 'PC9', 'Room 528', 'available', '2025-05-07 17:19:26'),
(90, 'PC10', 'Room 528', 'available', '2025-05-07 17:19:26'),
(91, 'PC11', 'Room 528', 'available', '2025-05-07 17:19:26'),
(92, 'PC12', 'Room 528', 'available', '2025-05-07 17:19:26'),
(93, 'PC13', 'Room 528', 'available', '2025-05-07 17:19:26'),
(94, 'PC14', 'Room 528', 'available', '2025-05-07 17:19:26'),
(95, 'PC15', 'Room 528', 'available', '2025-05-07 17:19:26'),
(96, 'PC16', 'Room 528', 'available', '2025-05-07 17:19:26'),
(97, 'PC17', 'Room 528', 'available', '2025-05-07 17:19:26'),
(98, 'PC18', 'Room 528', 'available', '2025-05-07 17:19:26'),
(99, 'PC19', 'Room 528', 'available', '2025-05-07 17:19:26'),
(100, 'PC20', 'Room 528', 'available', '2025-05-07 17:19:26'),
(101, 'PC21', 'Room 528', 'available', '2025-05-07 17:19:26'),
(102, 'PC22', 'Room 528', 'available', '2025-05-07 17:19:26'),
(103, 'PC23', 'Room 528', 'available', '2025-05-07 17:19:26'),
(104, 'PC24', 'Room 528', 'available', '2025-05-07 17:19:26'),
(105, 'PC25', 'Room 528', 'available', '2025-05-07 17:19:26'),
(106, 'PC26', 'Room 528', 'available', '2025-05-07 17:19:26'),
(107, 'PC27', 'Room 528', 'available', '2025-05-07 17:19:26'),
(108, 'PC28', 'Room 528', 'available', '2025-05-07 17:19:26'),
(109, 'PC29', 'Room 528', 'available', '2025-05-07 17:19:26'),
(110, 'PC30', 'Room 528', 'available', '2025-05-07 17:19:26'),
(111, 'PC31', 'Room 528', 'available', '2025-05-07 17:19:26'),
(112, 'PC32', 'Room 528', 'available', '2025-05-07 17:19:26'),
(113, 'PC33', 'Room 528', 'available', '2025-05-07 17:19:26'),
(114, 'PC34', 'Room 528', 'available', '2025-05-07 17:19:26'),
(115, 'PC35', 'Room 528', 'available', '2025-05-07 17:19:26'),
(116, 'PC36', 'Room 528', 'available', '2025-05-07 17:19:26'),
(117, 'PC37', 'Room 528', 'maintenance', '2025-05-08 07:55:12'),
(118, 'PC38', 'Room 528', 'maintenance', '2025-05-08 07:55:12'),
(119, 'PC39', 'Room 528', 'maintenance', '2025-05-08 07:55:12'),
(120, 'PC40', 'Room 528', 'maintenance', '2025-05-08 07:55:12'),
(121, 'PC1', 'Room 530', 'maintenance', '2025-05-08 07:55:23'),
(122, 'PC2', 'Room 530', 'maintenance', '2025-05-08 07:55:23'),
(123, 'PC3', 'Room 530', 'maintenance', '2025-05-08 07:55:23'),
(124, 'PC4', 'Room 530', 'maintenance', '2025-05-08 07:55:23'),
(125, 'PC5', 'Room 530', 'maintenance', '2025-05-08 07:55:23'),
(126, 'PC6', 'Room 530', 'available', '2025-05-07 17:19:23'),
(127, 'PC7', 'Room 530', 'available', '2025-05-07 17:19:23'),
(128, 'PC8', 'Room 530', 'available', '2025-05-07 17:19:23'),
(129, 'PC9', 'Room 530', 'available', '2025-05-07 17:19:23'),
(130, 'PC10', 'Room 530', 'available', '2025-05-07 17:19:23'),
(131, 'PC11', 'Room 530', 'available', '2025-05-07 17:19:23'),
(132, 'PC12', 'Room 530', 'available', '2025-05-07 17:19:23'),
(133, 'PC13', 'Room 530', 'available', '2025-05-07 17:19:23'),
(134, 'PC14', 'Room 530', 'available', '2025-05-07 17:19:23'),
(135, 'PC15', 'Room 530', 'available', '2025-05-07 17:19:23'),
(136, 'PC16', 'Room 530', 'available', '2025-05-07 17:19:23'),
(137, 'PC17', 'Room 530', 'available', '2025-05-07 17:19:23'),
(138, 'PC18', 'Room 530', 'available', '2025-05-07 17:19:23'),
(139, 'PC19', 'Room 530', 'available', '2025-05-07 17:19:23'),
(140, 'PC20', 'Room 530', 'available', '2025-05-07 17:19:23'),
(141, 'PC21', 'Room 530', 'available', '2025-05-07 17:19:23'),
(142, 'PC22', 'Room 530', 'available', '2025-05-07 17:19:23'),
(143, 'PC23', 'Room 530', 'available', '2025-05-07 17:19:23'),
(144, 'PC24', 'Room 530', 'available', '2025-05-07 17:19:23'),
(145, 'PC25', 'Room 530', 'available', '2025-05-07 17:19:23'),
(146, 'PC26', 'Room 530', 'available', '2025-05-07 17:19:23'),
(147, 'PC27', 'Room 530', 'available', '2025-05-07 17:19:23'),
(148, 'PC28', 'Room 530', 'available', '2025-05-07 17:19:23'),
(149, 'PC29', 'Room 530', 'available', '2025-05-07 17:19:23'),
(150, 'PC30', 'Room 530', 'available', '2025-05-07 17:19:23'),
(151, 'PC31', 'Room 530', 'available', '2025-05-07 17:19:23'),
(152, 'PC32', 'Room 530', 'available', '2025-05-07 17:19:23'),
(153, 'PC33', 'Room 530', 'available', '2025-05-07 17:19:23'),
(154, 'PC34', 'Room 530', 'available', '2025-05-07 17:19:23'),
(155, 'PC35', 'Room 530', 'available', '2025-05-07 17:19:23'),
(156, 'PC36', 'Room 530', 'available', '2025-05-07 17:19:23'),
(157, 'PC37', 'Room 530', 'available', '2025-05-07 17:19:23'),
(158, 'PC38', 'Room 530', 'available', '2025-05-07 17:19:23'),
(159, 'PC39', 'Room 530', 'available', '2025-05-07 17:19:23'),
(160, 'PC40', 'Room 530', 'available', '2025-05-07 17:19:23'),
(161, 'PC1', 'Room 542', 'used', '2025-05-08 07:55:33'),
(162, 'PC2', 'Room 542', 'used', '2025-05-08 07:55:33'),
(163, 'PC3', 'Room 542', 'available', '2025-05-07 17:15:39'),
(164, 'PC4', 'Room 542', 'available', '2025-05-07 17:15:39'),
(165, 'PC5', 'Room 542', 'available', '2025-05-07 17:15:39'),
(166, 'PC6', 'Room 542', 'available', '2025-05-07 17:15:39'),
(167, 'PC7', 'Room 542', 'available', '2025-05-07 17:15:39'),
(168, 'PC8', 'Room 542', 'available', '2025-05-07 17:15:39'),
(169, 'PC9', 'Room 542', 'available', '2025-05-07 17:15:39'),
(170, 'PC10', 'Room 542', 'available', '2025-05-07 17:15:39'),
(171, 'PC11', 'Room 542', 'available', '2025-05-07 17:15:39'),
(172, 'PC12', 'Room 542', 'available', '2025-05-07 17:15:39'),
(173, 'PC13', 'Room 542', 'available', '2025-05-07 17:15:39'),
(174, 'PC14', 'Room 542', 'available', '2025-05-07 17:15:39'),
(175, 'PC15', 'Room 542', 'available', '2025-05-07 17:15:39'),
(176, 'PC16', 'Room 542', 'available', '2025-05-07 17:15:39'),
(177, 'PC17', 'Room 542', 'available', '2025-05-07 17:15:39'),
(178, 'PC18', 'Room 542', 'available', '2025-05-07 17:15:39'),
(179, 'PC19', 'Room 542', 'available', '2025-05-07 17:15:39'),
(180, 'PC20', 'Room 542', 'available', '2025-05-07 17:15:39'),
(181, 'PC21', 'Room 542', 'available', '2025-05-07 17:15:39'),
(182, 'PC22', 'Room 542', 'available', '2025-05-07 17:15:39'),
(183, 'PC23', 'Room 542', 'available', '2025-05-07 17:15:39'),
(184, 'PC24', 'Room 542', 'available', '2025-05-07 17:15:39'),
(185, 'PC25', 'Room 542', 'available', '2025-05-07 17:15:39'),
(186, 'PC26', 'Room 542', 'available', '2025-05-07 17:15:39'),
(187, 'PC27', 'Room 542', 'available', '2025-05-07 17:15:39'),
(188, 'PC28', 'Room 542', 'available', '2025-05-07 17:15:39'),
(189, 'PC29', 'Room 542', 'available', '2025-05-07 17:15:39'),
(190, 'PC30', 'Room 542', 'available', '2025-05-07 17:15:39'),
(191, 'PC31', 'Room 542', 'available', '2025-05-07 17:15:39'),
(192, 'PC32', 'Room 542', 'available', '2025-05-07 17:15:39'),
(193, 'PC33', 'Room 542', 'available', '2025-05-07 17:15:39'),
(194, 'PC34', 'Room 542', 'available', '2025-05-07 17:15:39'),
(195, 'PC35', 'Room 542', 'available', '2025-05-07 17:15:39'),
(196, 'PC36', 'Room 542', 'available', '2025-05-07 17:15:39'),
(197, 'PC37', 'Room 542', 'available', '2025-05-07 17:15:39'),
(198, 'PC38', 'Room 542', 'available', '2025-05-07 17:15:39'),
(199, 'PC39', 'Room 542', 'available', '2025-05-07 17:15:39'),
(200, 'PC40', 'Room 542', 'available', '2025-05-07 17:15:39'),
(201, 'PC1', 'Room 544', 'maintenance', '2025-05-08 07:55:44'),
(202, 'PC2', 'Room 544', 'maintenance', '2025-05-08 07:55:44'),
(203, 'PC3', 'Room 544', 'available', '2025-05-07 17:19:17'),
(204, 'PC4', 'Room 544', 'available', '2025-05-07 17:19:17'),
(205, 'PC5', 'Room 544', 'available', '2025-05-07 17:19:17'),
(206, 'PC6', 'Room 544', 'available', '2025-05-07 17:19:17'),
(207, 'PC7', 'Room 544', 'available', '2025-05-07 17:19:17'),
(208, 'PC8', 'Room 544', 'available', '2025-05-07 17:19:17'),
(209, 'PC9', 'Room 544', 'available', '2025-05-07 17:19:17'),
(210, 'PC10', 'Room 544', 'available', '2025-05-07 17:19:17'),
(211, 'PC11', 'Room 544', 'available', '2025-05-07 17:19:17'),
(212, 'PC12', 'Room 544', 'available', '2025-05-07 17:19:17'),
(213, 'PC13', 'Room 544', 'available', '2025-05-07 17:19:17'),
(214, 'PC14', 'Room 544', 'available', '2025-05-07 17:19:17'),
(215, 'PC15', 'Room 544', 'available', '2025-05-07 17:19:17'),
(216, 'PC16', 'Room 544', 'available', '2025-05-07 17:19:17'),
(217, 'PC17', 'Room 544', 'available', '2025-05-07 17:19:17'),
(218, 'PC18', 'Room 544', 'available', '2025-05-07 17:19:17'),
(219, 'PC19', 'Room 544', 'available', '2025-05-07 17:19:17'),
(220, 'PC20', 'Room 544', 'available', '2025-05-07 17:19:17'),
(221, 'PC21', 'Room 544', 'available', '2025-05-07 17:19:17'),
(222, 'PC22', 'Room 544', 'available', '2025-05-07 17:19:17'),
(223, 'PC23', 'Room 544', 'available', '2025-05-07 17:19:17'),
(224, 'PC24', 'Room 544', 'available', '2025-05-07 17:19:17'),
(225, 'PC25', 'Room 544', 'available', '2025-05-07 17:19:17'),
(226, 'PC26', 'Room 544', 'available', '2025-05-07 17:19:17'),
(227, 'PC27', 'Room 544', 'available', '2025-05-07 17:19:17'),
(228, 'PC28', 'Room 544', 'available', '2025-05-07 17:19:17'),
(229, 'PC29', 'Room 544', 'available', '2025-05-07 17:19:17'),
(230, 'PC30', 'Room 544', 'available', '2025-05-07 17:19:17'),
(231, 'PC31', 'Room 544', 'available', '2025-05-07 17:19:17'),
(232, 'PC32', 'Room 544', 'available', '2025-05-07 17:19:17'),
(233, 'PC33', 'Room 544', 'available', '2025-05-07 17:19:17'),
(234, 'PC34', 'Room 544', 'available', '2025-05-07 17:19:17'),
(235, 'PC35', 'Room 544', 'available', '2025-05-07 17:19:17'),
(236, 'PC36', 'Room 544', 'available', '2025-05-07 17:19:17'),
(237, 'PC37', 'Room 544', 'available', '2025-05-07 17:19:17'),
(238, 'PC38', 'Room 544', 'available', '2025-05-07 17:19:17'),
(239, 'PC39', 'Room 544', 'available', '2025-05-07 17:19:17'),
(240, 'PC40', 'Room 544', 'available', '2025-05-07 17:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `idno` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `pc_number` varchar(10) NOT NULL,
  `reservation_date` date NOT NULL,
  `time_slot` varchar(20) NOT NULL,
  `purpose` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `idno`, `room_number`, `pc_number`, `reservation_date`, `time_slot`, `purpose`, `status`, `created_at`, `updated_at`) VALUES
(10, 2000, '524', 'PC6', '2025-05-09', '10:30AM-12:00PM', 'C++ Programming', 'approved', '2025-05-08 06:13:48', '2025-05-08 06:18:26'),
(11, 2000, '524', 'PC6', '2025-05-09', '9:00AM-10:30AM', 'C Programming', 'approved', '2025-05-08 06:18:04', '2025-05-08 06:18:28'),
(12, 2000, '524', 'PC8', '2025-05-09', '9:00AM-10:30AM', 'Java Programming', 'approved', '2025-05-08 06:48:13', '2025-05-08 07:13:39'),
(13, 20951505, '524', 'PC7', '2025-05-09', '9:00AM-10:30AM', 'Python Programming', 'approved', '2025-05-08 07:12:58', '2025-05-08 07:13:40'),
(14, 2000, '524', 'PC9', '2025-05-09', '9:00AM-10:30AM', 'C++ Programming', 'rejected', '2025-05-08 07:14:04', '2025-05-08 07:14:28'),
(15, 2000, '524', 'PC1', '2025-05-09', '7:30AM-9:00AM', 'C Programming', '', '2025-05-08 07:22:34', '2025-05-08 07:22:58');

-- --------------------------------------------------------

--
-- Table structure for table `sitin_records`
--

CREATE TABLE `sitin_records` (
  `ID` int(11) NOT NULL,
  `IDNO` int(11) NOT NULL,
  `PURPOSE` varchar(255) NOT NULL,
  `LABORATORY` varchar(255) NOT NULL,
  `TIME_IN` datetime NOT NULL DEFAULT current_timestamp(),
  `TIME_OUT` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitin_records`
--

INSERT INTO `sitin_records` (`ID`, `IDNO`, `PURPOSE`, `LABORATORY`, `TIME_IN`, `TIME_OUT`) VALUES
(5, 20951505, 'PHP Programming', '524', '2025-03-13 12:39:29', '2025-03-13 12:39:46'),
(21, 2000, 'PHP Programming', '524', '2025-03-20 01:38:52', '2025-03-20 01:39:08'),
(22, 2000, 'C Programming', '526', '2025-03-20 01:39:27', '2025-03-20 01:43:18'),
(23, 2000, 'PHP Programming', '524', '2025-03-20 01:43:56', '2025-03-25 22:11:30'),
(24, 3000, 'Java Programming', '526', '2025-03-25 22:15:35', '2025-03-26 00:08:12'),
(25, 2000, 'C Programming', '530', '2025-03-25 22:45:21', '2025-03-25 23:06:18'),
(26, 20951505, 'C++ Programming', '544', '2025-03-25 22:46:02', '2025-03-25 22:57:42'),
(27, 3123052, 'Java Programming', '524', '2025-03-25 22:46:41', '2025-03-26 00:08:14'),
(28, 20951505, 'Java Programming', '528', '2025-03-25 22:57:52', '2025-03-25 22:58:03'),
(29, 20951505, 'Java Programming', '524', '2025-03-25 22:58:19', '2025-03-25 22:58:31'),
(30, 20951505, 'PHP Programming', '524', '2025-03-25 22:58:51', '2025-03-25 22:59:00'),
(31, 20951505, 'PHP Programming', '524', '2025-03-25 22:59:32', '2025-03-25 22:59:38'),
(32, 20951505, 'PHP Programming', '524', '2025-03-25 23:03:50', '2025-03-25 23:09:57'),
(33, 2000, 'C Programming', '526', '2025-03-25 23:06:28', '2025-03-26 00:08:10'),
(34, 20951505, 'C++ Programming', '542', '2025-03-25 23:10:33', '2025-03-25 23:14:42'),
(35, 20951505, 'C Programming', '524', '2025-03-25 23:15:01', '2025-03-25 23:16:54'),
(36, 20951505, 'C++ Programming', '542', '2025-03-25 23:17:05', '2025-03-25 23:20:09'),
(37, 20951505, '.Net Programming', '528', '2025-03-25 23:20:24', '2025-03-26 00:08:15'),
(38, 2000, 'C++ Programming', '526', '2025-03-26 22:08:14', '2025-03-26 22:09:35'),
(39, 20951505, 'Python Programming', '530', '2025-03-26 22:09:16', '2025-03-26 22:09:33'),
(40, 20951505, 'PHP Programming', '528', '2025-03-26 22:09:55', '2025-03-26 23:17:44'),
(41, 2000, 'C++ Programming', '526', '2025-04-08 00:25:57', '2025-04-08 00:26:08'),
(42, 3000, 'C++ Programming', '528', '2025-04-08 20:40:57', '2025-04-08 20:44:46'),
(43, 20951505, 'Python Programming', '526', '2025-04-08 20:41:26', '2025-04-08 20:44:44'),
(44, 20951505, 'PHP Programming', '524', '2025-04-08 21:22:09', '2025-04-08 21:25:40'),
(45, 3000, 'Python Programming', '526', '2025-04-09 23:29:41', '2025-04-09 23:29:57'),
(46, 20951505, 'C Programming', '526', '2025-05-06 00:05:24', '2025-05-06 00:11:03'),
(47, 2000, 'Python Programming', '524', '2025-05-06 00:09:52', '2025-05-06 00:09:58'),
(48, 20951505, 'C++ Programming', '526', '2025-05-06 00:14:06', '2025-05-06 00:15:03'),
(49, 2000, 'PHP Programming', '524', '2025-05-06 00:15:55', '2025-05-06 00:15:58'),
(50, 3000, 'Python Programming', '524', '2025-05-06 00:16:43', '2025-05-06 00:16:49'),
(51, 3000, 'Python Programming', '524', '2025-05-06 00:18:42', '2025-05-06 00:18:46'),
(52, 3000, 'C++ Programming', '524', '2025-05-06 00:19:43', '2025-05-06 00:19:47'),
(53, 20951505, 'PHP Programming', '524', '2025-05-06 00:22:19', '2025-05-06 00:22:23'),
(54, 3123052, 'Java Programming', '530', '2025-05-06 01:51:38', '2025-05-06 01:51:43'),
(55, 2233311, 'PHP Programming', '526', '2025-05-06 03:06:02', '2025-05-06 03:06:08'),
(56, 3123052, 'Python Programming', '526', '2025-05-06 03:06:37', '2025-05-06 03:06:52'),
(57, 2000, 'PHP Programming', '524', '2025-05-06 03:06:47', '2025-05-06 03:06:50'),
(58, 2000, 'C++ Programming', '524', '2025-05-08 11:32:10', '2025-05-08 15:47:28'),
(59, 2000, '.Net Programming', '530', '2025-05-08 15:47:44', '2025-05-08 15:47:48'),
(60, 2000, 'C++ Programming', '544', '2025-05-08 15:48:19', '2025-05-08 15:48:23'),
(61, 20951505, 'PHP Programming', '524', '2025-05-08 15:48:44', '2025-05-08 15:48:47'),
(62, 3123052, 'Java Programming', '544', '2025-05-08 15:49:02', '2025-05-08 15:49:07'),
(63, 3123052, 'Java Programming', '544', '2025-05-08 15:49:20', '2025-05-08 15:49:23'),
(64, 2233311, 'Python Programming', '526', '2025-05-08 15:49:37', '2025-05-08 15:49:40'),
(65, 19894948, 'PHP Programming', '524', '2025-05-08 15:51:41', '2025-05-08 15:51:46');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `ID` int(11) NOT NULL,
  `IDNO` int(11) NOT NULL,
  `LASTNAME` varchar(30) NOT NULL,
  `FIRSTNAME` varchar(30) NOT NULL,
  `MIDDLENAME` varchar(30) DEFAULT NULL,
  `COURSE` varchar(30) NOT NULL,
  `YEAR_LEVEL` int(11) NOT NULL,
  `USERNAME` varchar(30) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `PROFILE_PIC` varchar(255) NOT NULL DEFAULT 'images/default_pic.png',
  `SESSION_COUNT` int(11) DEFAULT 30,
  `POINTS` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`ID`, `IDNO`, `LASTNAME`, `FIRSTNAME`, `MIDDLENAME`, `COURSE`, `YEAR_LEVEL`, `USERNAME`, `PASSWORD`, `PROFILE_PIC`, `SESSION_COUNT`, `POINTS`) VALUES
(2, 2000, 'Doe', 'John', '', 'BSCS', 2, 'j.doe', '$2y$10$OECVWvoX5l1zuQSXmz/LjO.Wf3t70WTI3DRb9y.TG3mbE9TLdeTo.', 'uploads/67cfba7e8a091_man-removebg-preview.png', 23, 5),
(4, 3000, 'Doe', 'Jake', '', 'BSIT', 1, 'jake123', '$2y$10$.kySdo9lb7URfqAf8VZTSOyVEOfNT9aUQFZsyfHxX5T2tOIEyg0CC', 'images/default_pic.png', 25, 3),
(9, 202025, 'White', 'Walter', 'Hartwell', 'BSCpE', 4, 'heisenberg', '$2y$10$QHQpJXwsI818Fe6v7z5RK.1ZaWkxKe5qYwDDqTsfQaIG1RtRwTB7S', 'uploads/681c64fb4d0e1_Omg kitty.jpg', 30, 0),
(8, 2233311, 'Kujo', 'Jotaro', 'Joestar', 'BSCS', 2, 'starplatinum', '$2y$10$h.0lhVFY.XE1p.hIMmY0COXkDXBfIgZauUZZcL557aJwQj4eKeAf6', 'images/default_pic.png', 28, 2),
(3, 3123052, 'Dela Cruz', 'Juan', '', 'BSCpE', 1, 'j.dela_cruz', '$2y$10$Uo.LAXxgNqAZB3zYw88TJe2Dtd7ZzAmqb53oU5N7EDl6JccM36I/u', 'uploads/67cfba1b81d48_meme-gif-pfp-9.gif', 26, 4),
(6, 19894948, 'Caumeran', 'Damien', '', 'BSIT', 3, 'damskie', '$2y$10$r25EqLHKI9qcm2PaeWR.ceriLWv/rLx1.sEtjL9vTJm8xx93yNfc.', 'uploads/681c6596a1c98_pexels-photo-2719416.jpeg', 29, 1),
(1, 20951505, 'Rafaela', 'Allen Kim', 'Calaclan', 'BSIT', 3, 'allenkim115', '$2y$10$2vP9w6uLJVF7f06XwKYqcOI0wsv3ZdIcOy5F.a8mwTZGa5iIHZNDe', 'uploads/67c929f4c620b_icegif-6567.gif', 24, 12),
(7, 22651798, 'Bustillo', 'Jarom', '', 'BSIT', 3, 'jaromy', '$2y$10$.J4.UBYjIliIiBcXATySMuaWICUWjnWmoquzeNkf3xt/M3ajpppCG', 'images/default_pic.png', 30, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `SITIN_RECORD_ID` (`SITIN_RECORD_ID`);

--
-- Indexes for table `lab_resources`
--
ALTER TABLE `lab_resources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_schedule`
--
ALTER TABLE `lab_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pc_status`
--
ALTER TABLE `pc_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reservation` (`room_number`,`pc_number`,`reservation_date`,`time_slot`),
  ADD KEY `idno` (`idno`);

--
-- Indexes for table `sitin_records`
--
ALTER TABLE `sitin_records`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `IDNO` (`IDNO`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`IDNO`),
  ADD UNIQUE KEY `ID` (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lab_resources`
--
ALTER TABLE `lab_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lab_schedule`
--
ALTER TABLE `lab_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=217;

--
-- AUTO_INCREMENT for table `pc_status`
--
ALTER TABLE `pc_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=241;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sitin_records`
--
ALTER TABLE `sitin_records`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`SITIN_RECORD_ID`) REFERENCES `sitin_records` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`idno`) REFERENCES `user` (`IDNO`);

--
-- Constraints for table `sitin_records`
--
ALTER TABLE `sitin_records`
  ADD CONSTRAINT `sitin_records_ibfk_1` FOREIGN KEY (`IDNO`) REFERENCES `user` (`IDNO`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
