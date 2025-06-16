-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 24, 2025 at 06:01 PM
-- Server version: 10.11.11-MariaDB-0ubuntu0.24.04.2
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Hostel`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_superadmin` tinyint(1) NOT NULL DEFAULT 0,
  `permissions` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `reg_date` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `is_superadmin`, `permissions`, `status`, `reg_date`, `last_login`) VALUES
(1, 'chuwa', 'chuwa@gmail.com', '$2y$10$w/qk9i.W3nYzAbEJCG9iOeM/JlgRVrmQdxNwgxRdtVxiVIMtfSBku', 0, NULL, 'active', '2025-05-11 08:03:53', NULL),
(2, 'Dr.abduli', 'abduli@gmail.com', '$2y$10$jWnUQAp63U3FqNKK2Sgyves.08jVG5Mr46LVvMD7MoWWI6Xcd56/y', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-11 08:05:07', NULL),
(4, 'Dr.Kamwaya', 'kamwaya@gmail.com', '$2y$10$pbeHP6e4GqdCqWAxVqiKZe1SN3C6IfhTQGDNL6dbQyWt0cD23F3/m', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-11 23:18:41', '2025-05-12 00:55:11'),
(5, 'Dr.zubeda', 'zubeda@gmail.com', '$2y$10$LpythV55F/SY198nXvKhoePOmxvk6z5GMOtCUdd.VFebGGar7A4by', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-11 23:19:54', NULL),
(7, 'Dr.david', 'David@casto.gmail.com', '$2y$10$wJnZJTx4vmB7iDvFLTPEOObYcFT2KWupp/k/r406JpKec0faLYBru', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-12 01:22:53', NULL),
(11, 'Dr.George', 'george@gmail.com', '$2y$10$pzoeupxHm3vn4xhXKi6cU.xXSNVkJKy/e5ngWKWDhbzr6xIbjKySm', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-12 03:07:32', '2025-05-13 00:41:59'),
(13, 'uncle_ethan', 'ethan@gmail.com', '$2y$10$uVCh.RaRKIs4sXCBf0qtOOwS9sEWTaUUs73R4BppCnP1XUxA.xLR6', 0, NULL, 'active', '2025-05-12 07:55:34', NULL),
(17, 'Dr.Alex', 'alex@gmail.com', '$2y$10$EEF/8aqIP4.mbUqH980np.GKpgj0EeKfOPEOBcUO5.fCzI29f3Syq', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-12 08:35:08', NULL),
(22, 'Dr.halsalt', 'halsalt@gmail.com', '$2y$10$sQfJB/sz0fZUKUkGoLyrwuurfffyec/X1zsbEyTAt3euWo4LCxE9.', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-12 08:59:31', NULL),
(23, 'Dr.amina', 'amina@gmail.com', '$2y$10$1dzvZspjvPjMidRn6b1HvO8TzhgBEFY6yKXPIW8MQ8xenMzhgvVxe', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-12 09:11:32', NULL),
(24, 'Dr.pracxeda', 'pracxedamsofe@gmail.com', '$2y$10$sErsYvhvhA3uK2iOvUjZcOKT1cVGtN.PHzfuwE8KSc8UPeouvIXD6', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-12 09:55:59', NULL),
(25, 'Dr.Bonifas', 'bonifas@gmail.com', '$2y$10$dlGfgZoUvMLI471xohuJQehzJ4XAswAz204wwa.R5SDsfCYPgwW9m', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-12 10:05:51', NULL),
(27, 'pius_official', 'pius@gmail.com', '$2y$10$.7ODBZYiCrbNgOWB7mim0.k6ojmuZOWxQ/qTeAwNdWQX9ietwphQK', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-12 10:44:01', NULL),
(28, 'Ministrant', 'servant@gmail.com', '$2y$10$n58mfRgrkn5lHGKcdKXGb.0a.9Bx/Eixyc0JUfs0.ZAFer/tWA95W', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-12 10:52:36', NULL),
(29, 'Mjuni_professor', 'mjuni@gmail.com', '$2y$10$soe.NyzBQXWXlwqoSWHBBuuW7nL3egW3tp6Ewxpe9AdfLd.FWBA6O', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-12 13:05:45', NULL),
(30, 'gilbert_chuwa', 'gilbert@gmail.com', '$2y$10$cHzrkTChDJMktL/xbUjVWOSIfLDnbPfhFXZ6VqwWq.x69GGwy9xQK', 1, NULL, 'active', '2025-05-15 16:00:30', NULL),
(31, 'block12', 'block12@gmail.com', '$2y$10$WDW4Z/Zxkh5WfkXMffVJMOp2Mi.ZCoZBJdFBjZfNMJc1M4yBKaQqq', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-15 16:01:37', NULL),
(32, 'ludovick', 'ludovickmasawe@gmail.com', '$2y$10$bR8nFTAEqxHd4HG5myrxbONMmHkPPRi6Jutq/hXrJZIwTeKUlk2QO', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-15 17:59:11', NULL),
(33, 'alodia', 'alodia@gmail.com', '$2y$10$3bc4jeCEaEiTgKwYmGVxZ.8212S5nxR5UOxg9Curguc0byQOd7i2K', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-15 18:10:39', NULL),
(34, 'zephania', 'zephani@gmail.com', '$2y$10$3PbG596FM4FRhYZic.bsBuGAs37DHXwbTPZ4jVJh5yb5G0eVVHVX6', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-15 18:13:35', NULL),
(35, 'utawal', 'utawala@gmail.com', '$2y$10$Lf5xmGbA1AY9RdL1xAe1XeK1dhPwy9.3rINyjL9l7qWPzE1CnTs62', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-16 11:21:55', NULL),
(36, 'venue', 'venue@gmail.com', '$2y$10$OAB7CZGYyEGLqn81nyaEnuhhmU.wpHA3/ADTBl27sEvK8ovs97TBy', 0, '{\"manage_students\":true,\"manage_rooms\":true,\"manage_complaints\":true,\"view_reports\":true}', 'active', '2025-05-16 11:46:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `affected_id` int(11) DEFAULT NULL,
  `affected_table` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action_type`, `description`, `affected_id`, `affected_table`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 13, 'create-admin', 'Created new admin account: pius_official', 27, 'admins', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-12 10:44:01'),
(2, 13, 'create-admin', 'Created new admin account: Ministrant', 28, 'admins', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-12 10:52:36'),
(3, 13, 'create-admin', 'Created new admin account: Mjuni_professor', 29, 'admins', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-12 13:05:45');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID of the admin who performed the action',
  `action_type` varchar(50) NOT NULL COMMENT 'Type of action performed',
  `description` text NOT NULL COMMENT 'Detailed description of the action',
  `affected_record_id` int(11) DEFAULT NULL COMMENT 'ID of the affected record',
  `affected_table` varchar(50) DEFAULT NULL COMMENT 'Database table that was affected',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of the user',
  `user_agent` text DEFAULT NULL COMMENT 'Browser/device information',
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Extra context data in JSON' CHECK (json_valid(`additional_data`)),
  `status` enum('success','failed') DEFAULT 'success' COMMENT 'Action status',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complainthistory`
--

CREATE TABLE `complainthistory` (
  `id` int(11) NOT NULL,
  `complaintid` int(11) DEFAULT NULL,
  `compalintStatus` varchar(255) DEFAULT NULL,
  `complaintRemark` mediumtext DEFAULT NULL,
  `postingDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complainthistory`
--

INSERT INTO `complainthistory` (`id`, `complaintid`, `compalintStatus`, `complaintRemark`, `postingDate`) VALUES
(1, 1, 'In Process', 'Electrician assigned.', '2025-01-14 11:11:12'),
(2, 1, 'Closed', 'Switch changed', '2025-01-14 11:11:31'),
(3, 2, 'In Process', 'waiting for few minites of timing', '2025-04-30 14:37:33'),
(4, 3, 'In Process', 'sooon i will solve\r\n', '2025-05-09 20:17:04'),
(5, 4, 'Closed', '', '2025-05-09 20:57:33'),
(6, 5, 'In Process', 'tutakujibu', '2025-05-09 21:13:46'),
(7, 6, 'In Process', 'zcmmnXCn', '2025-05-10 07:02:05'),
(8, 7, 'Closed', 'aesdfghjbk', '2025-05-11 22:04:53'),
(9, 8, 'In Process', 'uwakika', '2025-05-11 22:58:52'),
(10, 9, 'In Process', 'UTASUBIRI SANA', '2025-05-12 09:09:17'),
(11, 2, 'Closed', 'TUMESITISHA HUDUMA', '2025-05-12 10:07:31'),
(12, 10, 'In Process', 'YYOU MUST ME WAIT', '2025-05-14 16:51:07'),
(13, 12, 'In Process', 'safi mkuu\r\n', '2025-05-15 07:23:14');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `ComplainNumber` bigint(12) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `complaintType` varchar(255) DEFAULT NULL,
  `complaintDetails` mediumtext DEFAULT NULL,
  `complaintDoc` varchar(255) DEFAULT NULL,
  `complaintStatus` varchar(255) DEFAULT NULL,
  `registrationDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `ComplainNumber`, `userId`, `complaintType`, `complaintDetails`, `complaintDoc`, `complaintStatus`, `registrationDate`) VALUES
(1, 389685413, 5, 'Electrical', 'Switch not working.', NULL, 'Closed', '2025-01-14 11:10:24'),
(2, 192422503, 11, 'Room Related', 'bad structure', 'f2095cf59868f495b4e392e7872fce3d.pdf', 'Closed', '2025-04-30 13:58:56'),
(3, 195128555, 11, 'Electrical', 'the socket it not fevareble for me to stay', '16cb62482aef087c831318f1bafba7a5.pdf', 'In Process', '2025-05-09 20:06:52'),
(4, 194610985, 11, 'Fee Related', 'sadfghjkl', '08300415068e982ed1e24b170d8978b4.pdf', 'Closed', '2025-05-09 20:35:20'),
(5, 847041786, 11, 'Fee Related', 'sdfghjkljhgfsduiyoyuytghf', 'efb2ce38ba05f39c72b5d65356362dd2.pdf', 'In Process', '2025-05-09 21:01:57'),
(6, 175425223, 11, 'Room Related', 'sdfg', '2db7c6ffb7d2a589f3bccb720526d0bb.pdf', 'In Process', '2025-05-10 00:02:52'),
(7, 897780088, 18, 'Room Related', 'sdfghjklsdfghjklfghjk', NULL, 'Closed', '2025-05-11 05:40:17'),
(8, 755455123, 18, 'Room Related', 'sdfghjk', NULL, 'In Process', '2025-05-11 22:57:23'),
(9, 197763882, 11, 'Electrical', 'hakuna umeme', NULL, 'In Process', '2025-05-11 23:31:40'),
(10, 187614818, 18, 'Fee Related', 'IOUYTRE', NULL, 'In Process', '2025-05-12 20:43:53'),
(11, 597938721, 11, 'Room Related', 'dfghj', NULL, NULL, '2025-05-13 04:33:09'),
(12, 634570836, 18, 'Fee Related', 'asdfghj', NULL, 'In Process', '2025-05-13 06:45:01'),
(13, 594982649, 22, 'Electrical', 'hakuna umeme', NULL, NULL, '2025-05-13 21:12:20'),
(14, 492367570, 39, 'Fee Related', 'mbaya', NULL, NULL, '2025-05-14 16:43:47'),
(15, 954164488, 42, 'Room Related', 'sdfghjkl;', NULL, NULL, '2025-05-16 08:30:06');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(255) DEFAULT NULL,
  `course_sn` varchar(255) DEFAULT NULL,
  `course_fn` varchar(255) DEFAULT NULL,
  `posting_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_sn`, `course_fn`, `posting_date`) VALUES
(6, 'MBA75', 'MBA', 'Master of Business Administration', '2025-01-01 19:31:42'),
(7, '012', 'Bsc-MTA', 'Bachelor of Science in Multimedia Technology & Animation', '2025-01-01 19:31:42'),
(10, '001', 'Bsc-BIS', 'Bachelor of science in Business Information System', '2025-05-01 12:41:04'),
(11, '002', 'Bsc-CE', 'Bachelor of science in Computer Engineering', '2025-05-01 12:41:49'),
(12, '003', 'Bsc-CS', 'Bachelor of Science in Computer Science', '2025-05-01 12:42:38'),
(13, '004', 'Bsc-SE', 'Bachelor of Science in Software Engineering ', '2025-05-01 12:43:21'),
(14, '005', 'Bsc-IS', 'Bachelor of Science in Information System', '2025-05-01 12:44:08'),
(15, '006', 'Bsc-TE', 'Bachelor of Science in Telecommunication Engineering', '2025-05-01 12:45:04'),
(16, '007', 'Bsc-CSDFE', 'Bachelor of Science in Cyber Security and Digital Forensics Engineering', '2025-05-01 12:46:25'),
(17, '008', 'Bsc-DCBE', 'Bachelor of science in Digital Content and Broadcasting Engineering', '2025-05-01 12:47:45'),
(18, '009', 'DET', 'Diploma in Education Technology', '2025-05-01 12:48:33'),
(19, '010', 'DICT', 'Diploma in Information and Communication Technology', '2025-05-01 12:49:18'),
(20, '011', 'DCS&DF', 'Diploma in Cyber Security and Digital Forensics', '2025-05-01 12:50:15'),
(21, 'BSC CHE 005', 'Bsc Chemistry', 'Bachelor of science in chemistry', '2025-05-13 11:45:54');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `AccessibilityWarden` varchar(255) DEFAULT NULL,
  `AccessibilityMember` varchar(255) DEFAULT NULL,
  `RedressalProblem` varchar(255) DEFAULT NULL,
  `Room` varchar(255) DEFAULT NULL,
  `Mess` varchar(255) DEFAULT NULL,
  `HostelSurroundings` varchar(255) DEFAULT NULL,
  `OverallRating` varchar(255) DEFAULT NULL,
  `FeedbackMessage` varchar(255) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `postinDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `AccessibilityWarden`, `AccessibilityMember`, `RedressalProblem`, `Room`, `Mess`, `HostelSurroundings`, `OverallRating`, `FeedbackMessage`, `userId`, `postinDate`) VALUES
(1, 'Very Good', 'Very Good', 'Excellent', 'Very Good', 'Excellent', 'Average', 'Good', 'NA', 5, '2025-01-14 11:12:43'),
(2, 'Excellent', 'Very Good', 'Good', 'Good', 'Very Good', 'Very Good', 'Below Average', 'am so exited', 11, '2025-04-30 13:59:41');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `attempt_time`) VALUES
(1, 'chuwa', '::1', '2025-05-11 07:23:57'),
(2, 'chuwa', '::1', '2025-05-11 07:23:59'),
(3, 'chuwa', '::1', '2025-05-11 07:24:01'),
(4, 'chuwa', '::1', '2025-05-11 07:24:02'),
(5, 'chuwa', '::1', '2025-05-11 07:24:03'),
(6, 'chuwa', '::1', '2025-05-11 07:24:04'),
(7, 'chuwa', '::1', '2025-05-11 07:24:46'),
(8, 'Dr.matrin', '::1', '2025-05-11 07:27:34'),
(9, 'chuwa', '::1', '2025-05-11 07:28:25'),
(10, 'matrin@gmail.com', '::1', '2025-05-11 07:29:29'),
(11, 'matrin@gmail.com', '::1', '2025-05-11 07:30:34'),
(12, 'Dr.abduli', '::1', '2025-05-11 07:38:16'),
(13, 'abduli@gmail.com', '::1', '2025-05-11 07:38:28'),
(14, 'Dr.abduli', '::1', '2025-05-11 07:38:29'),
(15, 'Dr.abduli', '::1', '2025-05-11 07:47:10'),
(16, 'Dr.abduli', '::1', '2025-05-11 07:47:25'),
(17, 'Dr.abduli', '::1', '2025-05-11 07:47:28'),
(18, 'Dr.abduli', '::1', '2025-05-11 08:02:21'),
(19, 'Dr.abduli', '::1', '2025-05-11 08:02:23'),
(20, 'Dr.abduli', '::1', '2025-05-11 08:02:24'),
(21, 'Dr.abduli', '::1', '2025-05-11 08:02:25'),
(22, 'Dr.abduli', '::1', '2025-05-11 08:02:36'),
(23, 'Dr.abduli', '::1', '2025-05-11 08:02:43'),
(24, 'chuwa@gmail.com', '::1', '2025-05-11 08:02:51'),
(25, 'Dr.abduli', '::1', '2025-05-11 08:02:52'),
(26, 'Dr.abduli', '::1', '2025-05-11 08:02:54'),
(27, 'Dr.abduli', '::1', '2025-05-11 08:02:55'),
(28, 'Dr.abduli', '::1', '2025-05-11 08:02:56'),
(29, 'Dr.abduli', '::1', '2025-05-11 08:02:56'),
(30, 'Dr.abduli', '::1', '2025-05-11 08:05:29'),
(31, 'abduli@gmail.com', '::1', '2025-05-11 08:05:47'),
(32, 'chuwa', '::1', '2025-05-11 08:41:10'),
(33, 'chuwa', '::1', '2025-05-11 12:35:45'),
(34, 'chuwa', '::1', '2025-05-11 12:35:49'),
(35, 'chuwa', '::1', '2025-05-11 12:35:54'),
(36, 'chuwa', '::1', '2025-05-12 01:20:37'),
(37, 'Dr.Hussein', '::1', '2025-05-12 02:31:58'),
(38, 'Dr.Praxceda', '::1', '2025-05-12 09:49:38'),
(39, 'Dr.Anna', '::1', '2025-05-12 09:49:47'),
(40, 'Dr.pracxeda', '::1', '2025-05-12 09:51:13'),
(41, 'Dr.Anna', '::1', '2025-05-12 12:09:47'),
(42, 'Dr.Praxceda', '::1', '2025-05-12 13:26:22'),
(43, 'Dr.amani', '::1', '2025-05-12 13:26:30'),
(44, 'Dr.Anna', '::1', '2025-05-12 13:26:35'),
(45, 'Dr.zulfa', '::1', '2025-05-12 13:26:50'),
(46, 'emmaachuwa@gmail.com', '::1', '2025-05-12 16:46:23'),
(47, 'dorine@gmail.com', '::1', '2025-05-12 23:32:32'),
(48, 'Dr.Hussein', '::1', '2025-05-13 08:22:46'),
(49, 'Dr.Praxceda', '::1', '2025-05-13 08:23:02'),
(50, 'kasongo@gmail.com', '::1', '2025-05-13 14:20:07'),
(51, 'kasongo@gmail.com', '::1', '2025-05-13 14:20:25'),
(52, 'Dr.matrin', '::1', '2025-05-13 14:39:00'),
(53, 'Dr.samia', '::1', '2025-05-16 11:30:34'),
(54, 'emmanuel248@gmail.com', '::1', '2025-05-24 20:56:49');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `id` int(11) NOT NULL,
  `roomno` int(11) NOT NULL,
  `seater` int(11) NOT NULL,
  `feespm` decimal(10,2) NOT NULL,
  `foodstatus` tinyint(1) NOT NULL,
  `stayfrom` date NOT NULL,
  `duration` int(11) NOT NULL,
  `course` varchar(100) NOT NULL,
  `regno` varchar(50) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `contactno` char(15) DEFAULT NULL,
  `emailid` varchar(100) NOT NULL,
  `egycontactno` varchar(15) NOT NULL,
  `guardianName` varchar(100) NOT NULL,
  `guardianRelation` varchar(50) NOT NULL,
  `guardianContactno` varchar(15) NOT NULL,
  `corresAddress` text NOT NULL,
  `corresCountry` varchar(50) NOT NULL,
  `corresState` varchar(50) NOT NULL,
  `pmntAddress` text NOT NULL,
  `pmntCountry` varchar(20) NOT NULL,
  `pmntState` varchar(50) NOT NULL,
  `reg_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`id`, `roomno`, `seater`, `feespm`, `foodstatus`, `stayfrom`, `duration`, `course`, `regno`, `firstName`, `middleName`, `lastName`, `gender`, `contactno`, `emailid`, `egycontactno`, `guardianName`, `guardianRelation`, `guardianContactno`, `corresAddress`, `corresCountry`, `corresState`, `pmntAddress`, `pmntCountry`, `pmntState`, `reg_date`) VALUES
(2, 300, 3, 4000.00, 0, '2025-05-14', 7, '0', 'T25-02-00003', 'john', 'michael', 'valerian', 'Male', '255788020014', 'johnchuwa@gmail.com', '0788020014', 'godfrey', '0', '0788020014', '457', 'Tanzania', '0', '457', 'Tanzania', 'Singida', '2025-05-14 06:54:48'),
(3, 5, 5, 12000.00, 1, '2025-05-14', 2, '0', 'T25-02-00008', 'hamis', 'thadeo', 'luhanga', 'Male', '255788020014', 'luhanga@gmail.com', '0788020014', 'chuwa', '0', '0788020014', '456', 'Tanzania', '0', '456', 'Tanzania', 'Morogoro', '2025-05-14 16:26:29'),
(4, 4, 3, 5000.00, 0, '2025-05-16', 9, '0', 'T25-Q2-00004', 'chalile', 'khamis', 'juma', 'Male', '255788020014', 'chalile@gmail.com', '255788020014', 'chuwa', '0', '255788020014', '234', 'tanzania', '0', '234', 'tanzania', 'Pemba North', '2025-05-16 08:37:08');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `seater` int(11) DEFAULT NULL,
  `room_no` int(11) DEFAULT NULL,
  `fees` int(11) DEFAULT NULL,
  `posting_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `seater`, `room_no`, `fees`, `posting_date`) VALUES
(9, 8, 1, 2000, '2025-04-30 10:32:34'),
(11, 6, 2, 24000, '2025-04-30 10:55:41'),
(12, 3, 4, 5000, '2025-04-30 10:57:08'),
(13, 1, 100, 400, '2025-04-30 14:29:31'),
(14, 2, 200, 24000, '2025-04-30 14:29:49'),
(15, 3, 300, 4000, '2025-04-30 14:30:04'),
(16, 4, 400, 45000, '2025-04-30 14:30:20'),
(17, 2, 500, 29000, '2025-05-01 12:16:36'),
(18, 5, 700, 1500, '2025-05-01 16:42:12'),
(19, 5, 5, 12000, '2025-05-11 22:10:05'),
(20, 4, 450, 15000, '2025-05-12 09:45:07'),
(21, 3, 8, 16000, '2025-05-13 11:43:28');

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` int(11) NOT NULL,
  `State` varchar(150) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `State`) VALUES
(29, 'Unguja North (Zanzibar North)'),
(28, 'Tanga'),
(27, 'Tabora'),
(26, 'Songwe'),
(25, 'Singida'),
(24, 'Simiyu'),
(23, 'Shinyanga'),
(22, 'Ruvuma'),
(21, 'Rukwa'),
(20, 'Pwani (Coast)'),
(19, 'Pemba South'),
(18, 'Pemba North'),
(17, 'Njombe'),
(16, 'Mwanza'),
(15, 'Mtwara'),
(14, 'Morogoro'),
(13, 'Mbeya'),
(12, 'Mara'),
(11, 'Manyara'),
(30, 'Unguja South (Zanzibar South)'),
(10, 'Lindi'),
(9, 'Kilimanjaro'),
(8, 'Kigoma'),
(7, 'Katavi'),
(6, 'Kagera'),
(5, 'Iringa'),
(4, 'Geita'),
(3, 'Dodoma'),
(2, 'Dar es Salaam'),
(1, 'Arusha'),
(31, 'Unguja West (Zanzibar Urban/West)');

-- --------------------------------------------------------

--
-- Table structure for table `userlog`
--

CREATE TABLE `userlog` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `userEmail` varchar(255) NOT NULL,
  `userIp` varbinary(16) NOT NULL,
  `city` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `loginTime` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userregistration`
--

CREATE TABLE `userregistration` (
  `id` int(11) NOT NULL,
  `regNo` varchar(255) DEFAULT NULL,
  `firstName` varchar(255) DEFAULT NULL,
  `middleName` varchar(255) DEFAULT NULL,
  `lastName` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `contactNo` char(15) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `regDate` timestamp NULL DEFAULT current_timestamp(),
  `updationDate` varchar(45) DEFAULT NULL,
  `passUdateDate` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `userregistration`
--

INSERT INTO `userregistration` (`id`, `regNo`, `firstName`, `middleName`, `lastName`, `gender`, `contactNo`, `email`, `password`, `regDate`, `updationDate`, `passUdateDate`) VALUES
(20, '11122', 'neema', 'chuwa', 'valerian', 'female', '745678952', 'neemachuwa@gmail.com', '$2y$10$V0DIVbkMW3RcBMPP/FxzaOZKFQOKiWSvID1Um/5Am.khkdIJMGxvK', '2025-05-13 08:29:36', NULL, NULL),
(21, '22255', 'kasongo', 'kibwan', 'yeye', 'male', '788020014', 'kasongo@gmail.com', '$2y$10$lx2ldCug0RIWatAkWwDPfO2VFmQhC/THOQzN.BREFQVngSSIKBs5e', '2025-05-13 08:33:26', '13-05-2025 04:54:45', NULL),
(22, '5555', 'block_8', 'wakuja', 'room', 'male', '0788020014', 'block8@gmail.com', '$2y$10$iNvZv5/ygoJ0m5OjoyXL5eUJTyspnvaOic3FMXMnmV1J/OIemlYti', '2025-05-13 11:26:42', NULL, NULL),
(23, '99911', 'grace', 'mwamba', 'wa nguvu', 'female', '0788020014', 'grace@gmail.com', '$2y$10$ynosvXKSa7krKBz4uJ0RXue5PDNMh4.JS7THRd156OsO6Wg0B8PR.', '2025-05-13 21:14:28', '14-05-2025 03:03:13', NULL),
(24, 'T25-Q2-00001', 'nuru', 'saidi', 'sharifu', 'female', '788045674', 'saidi@gmail.com', '$2y$10$yDu8zOxvTssTOFU/w4BzbeiF3B111n.XjRL7T0heMfSb7jlhBHG8a', '2025-05-13 22:29:58', NULL, NULL),
(25, 'T25-Q2-00002', 'john', 'michael', 'chuwa', 'male', '788020014', 'michael@gmail.com', '$2y$10$3PPZPu3KWyLOua.F58FFFuOxVTAuFHQVkAgJ4JVgiWtlb8GlwZ2/6', '2025-05-13 22:47:15', NULL, NULL),
(26, 'T25-Q2-00003', 'mjahidi', 'salim', 'diwani', 'male', '788020014', 'salim@gmail.com', '$2y$10$Co31EbtzNZ.5Bm4rBLXGXegH/L2JRKXgXNfoj23N3kZSmxPHJm3Z2', '2025-05-13 23:03:43', '14-05-2025 04:44:48', NULL),
(27, 'T25-Q2-00004', 'chalile', 'khamis', 'juma', 'male', '255788020014', 'chalile@gmail.com', '$2y$10$vhUuzYbnNbsVUKqUYyu3pelPceuDJnrNS2SCAKGqd4AgRplT2P5Oe', '2025-05-13 23:31:44', NULL, NULL),
(28, 'T25-02-00001', 'AGNESS', 'SOSPITER', 'Chuwa', 'female', '255678901233', 'agnesschuwa@gmail.com', '$2y$10$76seP4o84TJ3U5FBUHKUL.rOaonEz2KZbZak.lRpDMWUQ.FR9HXOu', '2025-05-14 05:48:12', NULL, NULL),
(29, 'T25-02-00002', 'David', 'casto', 'chuwa', 'male', '255688020014', 'davidachuwa@gmail.com', '$2y$10$Ynn512f53bxkUsiRlx1Bpe9UYWjzzY6SIDEowuAPg7DfRPH3iTHXC', '2025-05-14 05:59:03', '14-05-2025 11:30:50', NULL),
(30, 'T25-02-00003', 'john', 'michael', 'valerian', 'male', '255788020014', 'johnchuwa@gmail.com', '$2y$10$xmuLLslxyQe8jDQPjtMsZOpQN.yVW9UPdn721kOf6fvsdJ1zJ5XKm', '2025-05-14 06:48:20', NULL, NULL),
(31, 'T25-02-00004', 'machano', 'suleimani', 'mpemba', 'male', '255788020014', 'sule@gmail.com', '$2y$10$nT3a.unF797WgLQ4MUtbK.IEYJewyvWQIV2QtH/KZPSqVfgoeud4S', '2025-05-14 06:59:48', NULL, NULL),
(32, 'T25-02-00005', 'mchila', 'tabata', 'kimanga', 'male', '255788020014', 'mchila@gmail.com', '$2y$10$WCpSSDoDFRKGaqOUvSbiguEsBZEi1gONgOuW3VwYYnD9BHlqz3R0K', '2025-05-14 07:11:58', NULL, NULL),
(37, 'T25-02-00006', 'mchila', 'tabata', 'kimanga', 'male', '255788020014', 'kimanga@gmail.com', '$2y$10$fKJs3lV5HAm3COa9S9sEcuPiU3Ji4NDbfkwd6n1ZIM/m0mFaujJ2e', '2025-05-14 15:05:08', NULL, NULL),
(38, 'T25-02-00007', 'adbuli', 'hamisi', 'kimanga', 'male', '255788020014', 'hamis@gmail.com', '$2y$10$6MbdW46bl3jdFhGssCUSeu3RlJuNuOlWh45eR632WQ60zUe7SQQTe', '2025-05-14 15:10:01', NULL, NULL),
(39, 'T25-02-00008', 'hamis', 'thadeo', 'luhanga', 'male', '255788020014', 'luhanga@gmail.com', '$2y$10$qH9yJrgx63eFWHCBZ5okK.1IsEsPkkhocUcRJvn1p/CrBdQy0gR3i', '2025-05-14 15:20:07', NULL, NULL),
(40, 'T25-02-00009', 'kweka', 'block3', 'room34', 'male', '255788020014', 'kweka@gmail.com', '$2y$10$00.HVD9yGO1QKv37nQB5ROTHZcZpTtIG8fq5bVdL5lwWLP18nezVK', '2025-05-15 13:08:34', '15-05-2025 06:46:50', NULL),
(41, 'T25-02-00010', 'manka', 'valerian', 'chuwa', 'female', '255788020014', 'mankachuwa@gmail.com', '$2y$10$kO8LcqWI5fU6dh.DAJYY/uaY2YhSioYRVWSdIkuIj.cLCDbsEAN1y', '2025-05-15 15:42:53', NULL, NULL),
(42, 'T25-02-00011', 'block3', 'ground', 'block@gmail.com', 'male', '255625400249', 'block@gmail.com', '$2y$10$SGQDb52Kf4DufJArQOcwLuYciFiHviOsSJ7cCfVlnFRSUJ/JngicS', '2025-05-16 08:26:45', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_superadmin` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `reg_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `action_type` (`action_type`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_affected` (`affected_table`,`affected_record_id`);

--
-- Indexes for table `complainthistory`
--
ALTER TABLE `complainthistory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`),
  ADD KEY `ip_address` (`ip_address`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `regno` (`regno`),
  ADD UNIQUE KEY `emailid` (`emailid`),
  ADD KEY `roomno` (`roomno`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_no` (`room_no`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `userlog`
--
ALTER TABLE `userlog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `userregistration`
--
ALTER TABLE `userregistration`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `complainthistory`
--
ALTER TABLE `complainthistory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `userlog`
--
ALTER TABLE `userlog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `userregistration`
--
ALTER TABLE `userregistration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`);

--
-- Constraints for table `registration`
--
ALTER TABLE `registration`
  ADD CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`roomno`) REFERENCES `rooms` (`room_no`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
