-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2025 at 04:18 PM
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
(4, 'Greeting Announcement', 'Hello Everyone!!!', '2025-03-26 15:03:46');

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
(2, 3000, 'C#', '526', '2025-03-13 12:02:52', '2025-03-13 12:44:13'),
(5, 20951505, 'PHP Programming', '524', '2025-03-13 12:39:29', '2025-03-13 12:39:46'),
(6, 2000, 'C++', '530', '2025-03-13 12:42:34', '2025-03-13 12:42:40'),
(7, 2000, 'PHP', '524', '2025-03-20 00:15:30', '2025-03-20 00:23:32'),
(8, 2000, 'PHP', '524', '2025-03-20 00:28:40', '2025-03-20 00:29:19'),
(9, 3123052, 'PHP', '524', '2025-03-20 00:30:48', '2025-03-20 00:30:56'),
(10, 2000, 'PHP', '524', '2025-03-20 00:36:08', '2025-03-20 00:52:19'),
(11, 2000, 'PHP', '524', '2025-03-20 01:05:13', '2025-03-20 01:08:43'),
(12, 2000, 'PHP', '524', '2025-03-20 01:12:16', '2025-03-20 01:19:55'),
(13, 3000, 'PHP', '524', '2025-03-20 01:14:37', '2025-03-20 01:39:12'),
(14, 3123052, 'PHP', '524', '2025-03-20 01:17:23', '2025-03-20 01:39:14'),
(15, 20951505, 'PHP', '524', '2025-03-20 01:19:31', '2025-03-20 01:39:16'),
(16, 2000, 'PHP', '524', '2025-03-20 01:20:06', '2025-03-20 01:21:22'),
(17, 2000, 'PHP', '524', '2025-03-20 01:21:29', '2025-03-20 01:22:55'),
(18, 2000, 'PHP', '524', '2025-03-20 01:23:07', '2025-03-20 01:23:14'),
(19, 2000, 'PHP', '524', '2025-03-20 01:23:50', '2025-03-20 01:23:59'),
(20, 2000, 'PHP', '524', '2025-03-20 01:32:08', '2025-03-20 01:38:41'),
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
(40, 20951505, 'PHP Programming', '528', '2025-03-26 22:09:55', '2025-03-26 23:17:44');

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
  `SESSION_COUNT` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`ID`, `IDNO`, `LASTNAME`, `FIRSTNAME`, `MIDDLENAME`, `COURSE`, `YEAR_LEVEL`, `USERNAME`, `PASSWORD`, `PROFILE_PIC`, `SESSION_COUNT`) VALUES
(2, 2000, 'Doe', 'John', '', 'BSCS', 2, 'j.doe', '$2y$10$OECVWvoX5l1zuQSXmz/LjO.Wf3t70WTI3DRb9y.TG3mbE9TLdeTo.', 'uploads/67cfba7e8a091_man-removebg-preview.png', 28),
(4, 3000, 'Doe', 'Jake', '', 'BSIT', 1, 'jake123', '$2y$10$.kySdo9lb7URfqAf8VZTSOyVEOfNT9aUQFZsyfHxX5T2tOIEyg0CC', 'images/default_pic.png', 27),
(3, 3123052, 'Dela Cruz', 'Juan', '', 'BSCpE', 1, 'j.dela_cruz', '$2y$10$Uo.LAXxgNqAZB3zYw88TJe2Dtd7ZzAmqb53oU5N7EDl6JccM36I/u', 'uploads/67cfba1b81d48_meme-gif-pfp-9.gif', 26),
(1, 20951505, 'Rafaela', 'Allen Kim', 'Calaclan', 'BSIT', 3, 'allenkim115', '$2y$10$2vP9w6uLJVF7f06XwKYqcOI0wsv3ZdIcOy5F.a8mwTZGa5iIHZNDe', 'uploads/67c929f4c620b_icegif-6567.gif', 28);

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
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sitin_records`
--
ALTER TABLE `sitin_records`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sitin_records`
--
ALTER TABLE `sitin_records`
  ADD CONSTRAINT `sitin_records_ibfk_1` FOREIGN KEY (`IDNO`) REFERENCES `user` (`IDNO`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
