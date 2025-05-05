-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 09:01 PM
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
(2, '524', 'Monday/Wednesday', '9:00AM-10:30AM', 'Available'),
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
(54, 3123052, 'Java Programming', '530', '2025-05-06 01:51:38', '2025-05-06 01:51:43');

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
(2, 2000, 'Doe', 'John', '', 'BSCS', 2, 'j.doe', '$2y$10$OECVWvoX5l1zuQSXmz/LjO.Wf3t70WTI3DRb9y.TG3mbE9TLdeTo.', 'uploads/67cfba7e8a091_man-removebg-preview.png', 27, 1),
(4, 3000, 'Doe', 'Jake', '', 'BSIT', 1, 'jake123', '$2y$10$.kySdo9lb7URfqAf8VZTSOyVEOfNT9aUQFZsyfHxX5T2tOIEyg0CC', 'images/default_pic.png', 25, 3),
(8, 2233311, 'Kujo', 'Jotaro', 'Joestar', 'BSCS', 2, 'starplatinum', '$2y$10$h.0lhVFY.XE1p.hIMmY0COXkDXBfIgZauUZZcL557aJwQj4eKeAf6', 'images/default_pic.png', 30, 0),
(3, 3123052, 'Dela Cruz', 'Juan', '', 'BSCpE', 1, 'j.dela_cruz', '$2y$10$Uo.LAXxgNqAZB3zYw88TJe2Dtd7ZzAmqb53oU5N7EDl6JccM36I/u', 'uploads/67cfba1b81d48_meme-gif-pfp-9.gif', 29, 1),
(6, 19894948, 'Caumeran', 'Damien', '', 'BSIT', 3, 'damskie', '$2y$10$x2hKXgI3yAtjgwumnUCSj.B5XA5sZcuhqz6y28xNiH5PE/fXKu9AO', 'images/default_pic.png', 30, 0),
(1, 20951505, 'Rafaela', 'Allen Kim', 'Calaclan', 'BSIT', 3, 'allenkim115', '$2y$10$2vP9w6uLJVF7f06XwKYqcOI0wsv3ZdIcOy5F.a8mwTZGa5iIHZNDe', 'uploads/67c929f4c620b_icegif-6567.gif', 25, 11),
(7, 22651798, 'Bustillo', 'Jarom', '', 'BSIT', 3, 'jaromy', '$2y$10$o5zg6.8vtr5Hq.33dMUZMuZYsIFWoHFK.F4RQyM6TxytW1XaUIl2.', 'images/default_pic.png', 30, 0);

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
-- AUTO_INCREMENT for table `sitin_records`
--
ALTER TABLE `sitin_records`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`SITIN_RECORD_ID`) REFERENCES `sitin_records` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `sitin_records`
--
ALTER TABLE `sitin_records`
  ADD CONSTRAINT `sitin_records_ibfk_1` FOREIGN KEY (`IDNO`) REFERENCES `user` (`IDNO`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
