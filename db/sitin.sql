-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2025 at 05:51 AM
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
  `MESSAGE` text NOT NULL,
  `TIMESTAMP` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`ID`, `MESSAGE`, `TIMESTAMP`) VALUES
(4, 'Hello Everyone!!!', '2025-03-13 03:24:35');

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
(1, 2000, 'PHP', '524', '2025-03-13 11:56:23', '2025-03-13 12:34:36'),
(2, 3000, 'C#', '526', '2025-03-13 12:02:52', '2025-03-13 12:44:13'),
(3, 3123052, 'Python', '530', '2025-03-13 12:05:50', NULL),
(5, 20951505, 'PHP Programming', '524', '2025-03-13 12:39:29', '2025-03-13 12:39:46'),
(6, 2000, 'C++', '530', '2025-03-13 12:42:34', '2025-03-13 12:42:40');

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
(4, 3000, 'Doe', 'Jake', '', 'BSIT', 1, 'jake123', '$2y$10$.kySdo9lb7URfqAf8VZTSOyVEOfNT9aUQFZsyfHxX5T2tOIEyg0CC', 'images/default_pic.png', 29),
(3, 3123052, 'Dela Cruz', 'Juan', '', 'BSCpE', 1, 'j.dela_cruz', '$2y$10$Uo.LAXxgNqAZB3zYw88TJe2Dtd7ZzAmqb53oU5N7EDl6JccM36I/u', 'uploads/67cfba1b81d48_meme-gif-pfp-9.gif', 30),
(1, 20951505, 'Rafaela', 'Allen Kim', 'Calaclan', 'BSIT', 3, 'allenkim115', '$2y$10$2vP9w6uLJVF7f06XwKYqcOI0wsv3ZdIcOy5F.a8mwTZGa5iIHZNDe', 'uploads/67c929f4c620b_icegif-6567.gif', 29);

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
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sitin_records`
--
ALTER TABLE `sitin_records`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
