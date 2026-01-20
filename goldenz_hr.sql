-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db:3306
-- Generation Time: Jan 20, 2026 at 01:48 PM
-- Server version: 8.0.44
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `goldenz_hr`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'INSERT', 'employees', 1, NULL, '{\"surname\": \"ABAD\", \"first_name\": \"JOHN MARK\", \"employee_no\": 1, \"employee_type\": \"SG\"}', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '2024-01-15 00:30:00'),
(2, 1, 'UPDATE', 'employees', 1, '{\"status\": \"Active\"}', '{\"status\": \"Active\"}', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '2024-01-15 01:15:00'),
(3, 2, 'INSERT', 'time_off_requests', 1, NULL, '{\"start_date\": \"2024-02-01\", \"employee_id\": 1, \"request_type\": \"vacation\"}', '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', '2024-01-15 02:00:00'),
(4, 25, 'INSERT', 'employees', 2574, NULL, '{\"employee_no\":\"23432\",\"first_name\":\"asdfsdfsdfsd\",\"surname\":\"sdfasdfsdfsdfasdfsdafsdf\",\"employee_type\":\"SG\",\"post\":\"Lady Guard - Hospital\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 04:52:11'),
(5, 25, 'INSERT', 'employees', 2867, NULL, '{\"employee_no\":\"43434\",\"first_name\":\"asdf\",\"surname\":\"sadf\",\"employee_type\":\"SG\",\"post\":\"Lady Guard - Hospital\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 06:13:18'),
(6, 25, 'INSERT', 'employees', 2924, NULL, '{\"employee_no\":\"12121\",\"first_name\":\"asdfsadf\",\"surname\":\"asfd\",\"employee_type\":\"SG\",\"post\":\"Lady Guard - Office Building\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 06:24:02'),
(7, 25, 'INSERT', 'employees', 3141, NULL, '{\"employee_no\":\"21342\",\"first_name\":\"dsafsdfsadf\",\"surname\":\"efsdafa\",\"employee_type\":\"SO\",\"post\":\"Security Officer - Headquarters\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 06:34:18'),
(8, 25, 'INSERT', 'employees', 3401, NULL, '{\"employee_no\":\"34232\",\"first_name\":\"sadfasfdasd\",\"surname\":\"sadfsf\",\"employee_type\":\"LG\",\"post\":\"Security Officer - Headquarters\",\"status\":\"Terminated\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 07:26:36'),
(9, 25, 'INSERT', 'employees', 3750, NULL, '{\"employee_no\":\"23423\",\"first_name\":\"SADFSADF\",\"surname\":\"ASDFSADF\",\"employee_type\":\"SG\",\"post\":\"LADY GUARD - OFFICE BUILDING\",\"status\":\"Terminated\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 08:15:05'),
(10, 25, 'INSERT', 'employees', 3783, NULL, '{\"employee_no\":\"24234\",\"first_name\":\"SDFASDFSFA\",\"surname\":\"SAFASDF\",\"employee_type\":\"SG\",\"post\":\"LADY GUARD - OFFICE BUILDING\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 08:17:00'),
(11, 1, 'UPDATE', 'employees', 3817, '{\"surname\": \"SADFSADFASD\", \"first_name\": \"FASDFSADFS\", \"status\": \"Inactive\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}', '{\"surname\": \"SADFSADFASD\", \"first_name\": \"FASDFSADFS\", \"status\": \"Inactive\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}', NULL, NULL, '2025-12-14 08:20:48'),
(12, 25, 'INSERT', 'employees', 3817, NULL, '{\"employee_no\":\"23423\",\"first_name\":\"FASDFSADFS\",\"surname\":\"SADFSADFASD\",\"employee_type\":\"SG\",\"post\":\"SECURITY GUARD - RESIDENTIAL\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 08:20:48'),
(13, 1, 'UPDATE', 'employees', 3866, '{\"surname\": \"SDFASDFASDF\", \"first_name\": \"SADFASFASFSADF\", \"status\": \"Inactive\", \"post\": \"LADY GUARD - OFFICE BUILDING\"}', '{\"surname\": \"SDFASDFASDF\", \"first_name\": \"SADFASFASFSADF\", \"status\": \"Inactive\", \"post\": \"LADY GUARD - OFFICE BUILDING\"}', NULL, NULL, '2025-12-14 08:27:18'),
(14, 25, 'INSERT', 'employees', 3866, NULL, '{\"employee_no\":\"24324\",\"first_name\":\"SADFASFASFSADF\",\"surname\":\"SDFASDFASDF\",\"employee_type\":\"SG\",\"post\":\"LADY GUARD - OFFICE BUILDING\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 08:27:18'),
(15, 1, 'UPDATE', 'employees', 3907, '{\"surname\": \"ASFSDFSADFASDF\", \"first_name\": \"ASDFSDFSD\", \"status\": \"Suspended\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}', '{\"surname\": \"ASFSDFSADFASDF\", \"first_name\": \"ASDFSDFSD\", \"status\": \"Suspended\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}', NULL, NULL, '2025-12-14 08:30:41'),
(16, 25, 'INSERT', 'employees', 3907, NULL, '{\"employee_no\":\"23432\",\"first_name\":\"ASDFSDFSD\",\"surname\":\"ASFSDFSADFASDF\",\"employee_type\":\"LG\",\"post\":\"SECURITY GUARD - RESIDENTIAL\",\"status\":\"Suspended\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 08:30:42'),
(17, 1, 'UPDATE', 'employees', 3996, '{\"surname\": \"ASDFASDFSADFS\", \"first_name\": \"SDFSADFASDFSDAF\", \"status\": \"Suspended\", \"post\": \"SECURITY OFFICER - FIELD OPERATIONS\"}', '{\"surname\": \"ASDFASDFSADFS\", \"first_name\": \"SDFSADFASDFSDAF\", \"status\": \"Suspended\", \"post\": \"SECURITY OFFICER - FIELD OPERATIONS\"}', NULL, NULL, '2025-12-14 08:33:42'),
(18, 25, 'INSERT', 'employees', 3996, NULL, '{\"employee_no\":\"23423\",\"first_name\":\"SDFSADFASDFSDAF\",\"surname\":\"ASDFASDFSADFS\",\"employee_type\":\"LG\",\"post\":\"SECURITY OFFICER - FIELD OPERATIONS\",\"status\":\"Suspended\",\"created_by\":\"HR Administrator\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-14 08:33:42'),
(19, 25, 'INSERT', 'employees', 12729, NULL, '{\"employee_no\":\"34324\",\"first_name\":\"HEHE\",\"surname\":\"HAHA\",\"employee_type\":\"SG\",\"post\":\"UNASSIGNED\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-12 07:30:43'),
(20, 25, 'INSERT', 'employees', 13042, NULL, '{\"employee_no\":\"24324\",\"first_name\":\"CHRISTIAN\",\"surname\":\"AMOR\",\"employee_type\":\"SG\",\"post\":\"KAHIT SAN\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-12 07:47:54'),
(21, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 13:22:12\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 05:22:12'),
(22, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 13:22:17\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 05:22:17'),
(23, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 13:45:41\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 05:45:41'),
(24, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 13:49:45\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 05:49:45'),
(25, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 16:02:37\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:02:37'),
(26, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 16:06:17\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:06:17'),
(27, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 16:08:29\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:08:29'),
(28, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 16:08:36\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:08:36'),
(29, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 16:09:04\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:09:04'),
(30, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 16:10:03\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:10:03'),
(31, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 16:12:39\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:12:39'),
(32, 25, 'USER_CREATED', 'users', 30, NULL, '{\"username\":\"grey\",\"email\":\"greycruz00000000@gmail.com\",\"name\":\"aldrin\",\"role\":\"hr_admin\",\"status\":\"active\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:36:54'),
(33, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 16:37:46\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:37:46'),
(34, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 16:38:08\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:38:08'),
(35, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 16:42:01\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:42:01'),
(36, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 16:52:06\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:52:06'),
(37, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 16:52:12\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:52:12'),
(38, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 16:52:42\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 08:52:42'),
(39, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 17:10:10\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:10:10'),
(40, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 17:16:42\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:16:42'),
(41, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 17:17:12\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:17:12'),
(42, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 17:23:58\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:23:58'),
(43, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 17:42:17\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:42:17'),
(44, 30, 'USER_ROLE_UPDATED', 'users', 25, '{\"role\":\"hr_admin\"}', '{\"role\":\"super_admin\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:45:59'),
(45, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 17:46:06\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:46:06'),
(46, 25, 'USER_ROLE_UPDATED', 'users', 25, '{\"role\":\"super_admin\"}', '{\"role\":\"hr_admin\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:46:29'),
(47, 25, 'USER_ROLE_UPDATED', 'users', 25, '{\"role\":\"hr_admin\"}', '{\"role\":\"super_admin\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:46:34'),
(48, 25, 'USER_ROLE_UPDATED', 'users', 25, '{\"role\":\"super_admin\"}', '{\"role\":\"hr_admin\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:46:36'),
(49, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-13 17:46:51\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:46:51'),
(50, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 17:47:04\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:47:04'),
(51, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-13 17:49:29\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:49:29'),
(52, 30, 'USER_CREATED', 'users', 31, NULL, '{\"username\":\"amor\",\"email\":\"amor@gmail.com\",\"name\":\"amor\",\"role\":\"hr_admin\",\"status\":\"active\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 09:58:43'),
(53, 30, 'USER_CREATED', 'users', 32, NULL, '{\"username\":\"ChristianAmor\",\"email\":\"christian5787264@gmail.com\",\"name\":\"christian amor\",\"role\":\"super_admin\",\"status\":\"active\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 10:01:09'),
(54, 32, 'LOGIN_ATTEMPT', 'users', 32, NULL, '{\"login_time\":\"2026-01-13 18:01:59\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 10:01:59'),
(55, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-14 07:42:03\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 23:42:03'),
(56, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-16 16:12:26\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-16 08:12:26'),
(57, 1, 'UPDATE', 'employees', 14910, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:12:54'),
(58, 1, 'UPDATE', 'employees', 14910, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:12:54'),
(59, 1, 'UPDATE', 'employees', 1, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:14:52'),
(60, 1, 'UPDATE', 'employees', 1, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:14:52'),
(61, 1, 'UPDATE', 'employees', 2, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:17:15'),
(62, 1, 'UPDATE', 'employees', 2, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:17:15'),
(63, 1, 'UPDATE', 'employees', 3, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:19:27'),
(64, 1, 'UPDATE', 'employees', 3, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:19:27'),
(65, 1, 'UPDATE', 'employees', 4, '{\"surname\": \"ASDFASDF\", \"first_name\": \"ASDFASDF\", \"status\": \"Inactive\", \"post\": \"\"}', '{\"surname\": \"ASDFASDF\", \"first_name\": \"ASDFASDF\", \"status\": \"Inactive\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:27:15'),
(66, 1, 'UPDATE', 'employees', 4, '{\"surname\": \"ASDFASDF\", \"first_name\": \"ASDFASDF\", \"status\": \"Inactive\", \"post\": \"\"}', '{\"surname\": \"ASDFASDF\", \"first_name\": \"ASDFASDF\", \"status\": \"Inactive\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:27:15'),
(67, 1, 'UPDATE', 'employees', 5, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:28:13'),
(68, 1, 'UPDATE', 'employees', 5, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:28:13'),
(69, 1, 'UPDATE', 'employees', 6, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:31:30'),
(70, 1, 'UPDATE', 'employees', 7, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:33:42'),
(71, 1, 'UPDATE', 'employees', 7, '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', '{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}', NULL, NULL, '2026-01-16 08:33:42'),
(72, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-20 21:43:24\"}', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-20 13:43:24');

-- --------------------------------------------------------

--
-- Table structure for table `dtr_entries`
--

CREATE TABLE `dtr_entries` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `entry_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `entry_type` enum('time-in','time-out','break','overtime') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `dtr_summary`
-- (See below for the actual view)
--
CREATE TABLE `dtr_summary` (
`employee_id` int
,`employee_name` varchar(102)
,`entry_date` date
,`entry_type` enum('time-in','time-out','break','overtime')
,`hours_worked` bigint
,`post` varchar(100)
,`time_in` time
,`time_out` time
);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int NOT NULL,
  `employee_no` int NOT NULL,
  `employee_type` enum('SG','LG','SO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'SG = Security Guard, LG = Lady Guard, SO = Security Officer',
  `surname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `middle_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `post` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Assignment/Post',
  `license_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `license_exp_date` date DEFAULT NULL,
  `rlm_exp` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'RLM = Renewal of License/Membership',
  `date_hired` date NOT NULL,
  `cp_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Contact Phone Number',
  `sss_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Social Security System Number',
  `pagibig_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'PAG-IBIG Fund Number',
  `tin_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tax Identification Number',
  `philhealth_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'PhilHealth Number',
  `birth_date` date DEFAULT NULL,
  `height` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `contact_person` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relationship` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_person_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `contact_person_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `blood_type` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `religion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vacancy_source` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'How did you know of the vacancy (JSON array: Ads, Walk-in, Referral)',
  `referral_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Referral name if vacancy source is Referral',
  `knows_agency_person` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Do you know anyone from the agency?',
  `agency_person_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Name and relationship with agency person',
  `physical_defect` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Do you have any physical defects or chronic ailments?',
  `physical_defect_specify` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Specify physical defects if yes',
  `drives` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Do you drive?',
  `drivers_license_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Driver license number',
  `drivers_license_exp` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Driver license expiration date',
  `drinks_alcohol` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Do you drink alcoholic beverages?',
  `alcohol_frequency` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'How frequent do you drink alcohol?',
  `prohibited_drugs` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Are you taking prohibited drugs?',
  `security_guard_experience` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'How long have you worked as a Security Guard?',
  `convicted` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Have you ever been convicted of any offense?',
  `conviction_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Specify conviction details if yes',
  `filed_case` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Have you filed any criminal/civil case against previous employer?',
  `case_specify` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Specify case details if yes',
  `action_after_termination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'What was your action after termination?',
  `signature_1` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen signature line 1',
  `signature_2` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen signature line 2',
  `signature_3` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen signature line 3',
  `initial_1` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen initial 1 (Pinakiling Pirma)',
  `initial_2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen initial 2 (Pinakiling Pirma)',
  `initial_3` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen initial 3 (Pinakiling Pirma)',
  `fingerprint_right_thumb` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Right thumb fingerprint file path',
  `fingerprint_right_index` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Right index finger fingerprint file path',
  `fingerprint_right_middle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Right middle finger fingerprint file path',
  `fingerprint_right_ring` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Right ring finger fingerprint file path',
  `fingerprint_right_little` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Right little finger fingerprint file path',
  `fingerprint_left_thumb` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Left thumb fingerprint file path',
  `fingerprint_left_index` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Left index finger fingerprint file path',
  `fingerprint_left_middle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Left middle finger fingerprint file path',
  `fingerprint_left_ring` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Left ring finger fingerprint file path',
  `fingerprint_left_little` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Left little finger fingerprint file path',
  `requirements_signature` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Signature over printed name for requirements section',
  `req_2x2` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '2x2 photos provided (YO/NO)',
  `req_birth_cert` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'NSO/Birth Certificate provided (YO/NO)',
  `req_barangay` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Barangay Clearance provided (YO/NO)',
  `req_police` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Police Clearance provided (YO/NO)',
  `req_nbi` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'NBI Clearance provided (YO/NO)',
  `req_di` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'D.I. Clearance provided (YO/NO)',
  `req_diploma` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'High School/College Diploma provided (YO/NO)',
  `req_neuro_drug` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Neuro & Drug test result provided (YO/NO)',
  `req_sec_license` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sec.License Certificate from SOSIA provided (YO/NO)',
  `sec_lic_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Security License Number for ID copy',
  `req_sec_lic_no` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sec.Lic.No. ID copy provided (YO/NO)',
  `req_sss` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'SSS No. ID copy provided (YO/NO)',
  `req_pagibig` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Pag-Ibig No. ID copy provided (YO/NO)',
  `req_philhealth` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'PhilHealth No. ID copy provided (YO/NO)',
  `req_tin` enum('YO','NO') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'TIN No. ID copy provided (YO/NO)',
  `sworn_day` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sworn statement day',
  `sworn_month` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sworn statement month',
  `sworn_year` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sworn statement year',
  `tax_cert_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tax Certificate Number',
  `tax_cert_issued_at` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tax Certificate issued at location',
  `sworn_signature` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Signature over printed name for sworn statement',
  `affiant_community` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Affiant exhibited community',
  `doc_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Document Number',
  `page_no` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Page Number',
  `book_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Book Number',
  `series_of` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Series of',
  `status` enum('Active','Inactive','Terminated','Suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `created_by_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `civil_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age` int DEFAULT NULL,
  `birthplace` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `citizenship` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `provincial_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `special_skills` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `spouse_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `spouse_age` int DEFAULT NULL,
  `spouse_occupation` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `father_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `father_age` int DEFAULT NULL,
  `father_occupation` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_age` int DEFAULT NULL,
  `mother_occupation` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `children_names` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `college_course` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `college_school_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `college_school_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `college_years` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vocational_course` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vocational_school_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vocational_school_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vocational_years` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `highschool_school_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `highschool_school_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `highschool_years` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `elementary_school_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `elementary_school_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `elementary_years` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `trainings_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gov_exam_taken` tinyint(1) DEFAULT NULL,
  `gov_exam_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `employment_history_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `profile_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_no`, `employee_type`, `surname`, `first_name`, `middle_name`, `post`, `license_no`, `license_exp_date`, `rlm_exp`, `date_hired`, `cp_number`, `sss_no`, `pagibig_no`, `tin_number`, `philhealth_no`, `birth_date`, `height`, `weight`, `address`, `contact_person`, `relationship`, `contact_person_address`, `contact_person_number`, `blood_type`, `religion`, `vacancy_source`, `referral_name`, `knows_agency_person`, `agency_person_name`, `physical_defect`, `physical_defect_specify`, `drives`, `drivers_license_no`, `drivers_license_exp`, `drinks_alcohol`, `alcohol_frequency`, `prohibited_drugs`, `security_guard_experience`, `convicted`, `conviction_details`, `filed_case`, `case_specify`, `action_after_termination`, `signature_1`, `signature_2`, `signature_3`, `initial_1`, `initial_2`, `initial_3`, `fingerprint_right_thumb`, `fingerprint_right_index`, `fingerprint_right_middle`, `fingerprint_right_ring`, `fingerprint_right_little`, `fingerprint_left_thumb`, `fingerprint_left_index`, `fingerprint_left_middle`, `fingerprint_left_ring`, `fingerprint_left_little`, `requirements_signature`, `req_2x2`, `req_birth_cert`, `req_barangay`, `req_police`, `req_nbi`, `req_di`, `req_diploma`, `req_neuro_drug`, `req_sec_license`, `sec_lic_no`, `req_sec_lic_no`, `req_sss`, `req_pagibig`, `req_philhealth`, `req_tin`, `sworn_day`, `sworn_month`, `sworn_year`, `tax_cert_no`, `tax_cert_issued_at`, `sworn_signature`, `affiant_community`, `doc_no`, `page_no`, `book_no`, `series_of`, `status`, `created_at`, `updated_at`, `created_by`, `created_by_name`, `gender`, `civil_status`, `age`, `birthplace`, `citizenship`, `provincial_address`, `special_skills`, `spouse_name`, `spouse_age`, `spouse_occupation`, `father_name`, `father_age`, `father_occupation`, `mother_name`, `mother_age`, `mother_occupation`, `children_names`, `college_course`, `college_school_name`, `college_school_address`, `college_years`, `vocational_course`, `vocational_school_name`, `vocational_school_address`, `vocational_years`, `highschool_school_name`, `highschool_school_address`, `highschool_years`, `elementary_school_name`, `elementary_school_address`, `elementary_years`, `trainings_json`, `gov_exam_taken`, `gov_exam_json`, `employment_history_json`, `profile_image`) VALUES
(1, 24324, '', 'USER', 'TEMP', NULL, '', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, '', '2026-01-16 08:14:52', '2026-01-16 08:14:52', 25, 'HR Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'uploads/employees/1.png'),
(2, 0, '', 'USER', 'TEMP', NULL, '', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, '', '2026-01-16 08:17:15', '2026-01-16 08:17:15', 25, 'HR Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'uploads/employees/2.jpg'),
(3, 24325, '', 'USER', 'TEMP', NULL, '', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, '', '2026-01-16 08:19:27', '2026-01-16 08:19:27', 25, 'HR Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'uploads/employees/3.png'),
(4, 23423, 'SG', 'ASDFASDF', 'ASDFASDF', NULL, '', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, 'Inactive', '2026-01-16 08:27:15', '2026-01-16 08:27:15', 25, 'HR Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'uploads/employees/4.png'),
(5, 23432, '', 'USER', 'TEMP', NULL, '', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, '', '2026-01-16 08:28:13', '2026-01-16 08:28:13', 25, 'HR Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'uploads/employees/5.png'),
(6, 24326, '', 'USER', 'TEMP', NULL, '', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, '', '2026-01-16 08:31:30', '2026-01-16 08:31:30', 25, 'HR Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(7, 24327, '', 'USER', 'TEMP', NULL, '', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, '', '2026-01-16 08:33:42', '2026-01-16 08:33:42', 25, 'HR Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'uploads/employees/7.jpg');

--
-- Triggers `employees`
--
DELIMITER $$
CREATE TRIGGER `log_employee_changes` AFTER UPDATE ON `employees` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, created_at)
    VALUES (
        1, 
        'UPDATE',
        'employees',
        NEW.id,
        JSON_OBJECT(
            'surname', OLD.surname,
            'first_name', OLD.first_name,
            'status', OLD.status,
            'post', OLD.post
        ),
        JSON_OBJECT(
            'surname', NEW.surname,
            'first_name', NEW.first_name,
            'status', NEW.status,
            'post', NEW.post
        ),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_post_filled_count_after_employee_update` AFTER UPDATE ON `employees` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        CALL UpdatePostFilledCount((SELECT id FROM posts WHERE post_title = NEW.post LIMIT 1));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `employee_alerts`
--

CREATE TABLE `employee_alerts` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `alert_type` enum('license_expiry','document_expiry','missing_documents','contract_expiry','training_due','medical_expiry','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `alert_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `status` enum('active','acknowledged','resolved','dismissed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `acknowledged_by` int DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_alerts`
--

INSERT INTO `employee_alerts` (`id`, `employee_id`, `alert_type`, `title`, `description`, `alert_date`, `due_date`, `priority`, `status`, `created_by`, `acknowledged_by`, `acknowledged_at`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'license_expiry', 'Security License Expiring Soon', 'Security guard license (R4B-202309000367) will expire in 30 days. Please renew before expiration.', '2024-01-15', '2028-09-14', 'medium', 'active', 5, NULL, NULL, NULL, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(2, 2, 'license_expiry', 'Security License Expiring Soon', 'Lady guard license (NCR-202411000339) will expire in 45 days. Please renew before expiration.', '2024-01-15', '2029-11-07', 'medium', 'active', 5, NULL, NULL, NULL, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(3, 3, 'training_due', 'Training Required', 'RLM training is due. Please complete required training before expiration.', '2024-01-15', '2025-04-05', 'high', 'active', 5, NULL, NULL, NULL, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(4, 4, 'document_expiry', 'Medical Certificate Expiring', 'Medical certificate will expire soon. Please renew for continued employment.', '2024-01-15', '2024-02-15', 'urgent', 'active', 5, NULL, NULL, NULL, '2025-12-06 08:39:02', '2025-12-06 08:39:02');

-- --------------------------------------------------------

--
-- Table structure for table `employee_checklist`
--

CREATE TABLE `employee_checklist` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `item_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `completed` tinyint(1) DEFAULT '0',
  `completed_by` int DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `employee_details`
-- (See below for the actual view)
--
CREATE TABLE `employee_details` (
`created_at` timestamp
,`date_hired` date
,`employee_no` int
,`employee_type` enum('SG','LG','SO')
,`full_name` varchar(153)
,`id` int
,`license_exp_date` date
,`license_no` varchar(50)
,`license_status` varchar(13)
,`post` varchar(100)
,`status` enum('Active','Inactive','Terminated','Suspended')
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `hr_tasks`
--

CREATE TABLE `hr_tasks` (
  `id` int NOT NULL,
  `task_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `task_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category` enum('Employee Record','License','Leave Request','Clearance','Cash Bond','Other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Other',
  `assigned_by` int DEFAULT NULL,
  `assigned_by_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `urgency_level` enum('normal','important','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `location_page` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Where can it be found',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Notes created by the person who alerted',
  `status` enum('pending','in_progress','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `assigned_to` int DEFAULT NULL COMMENT 'HR Admin user ID',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `leave_type` enum('vacation','sick','personal','emergency','maternity','paternity','bereavement') NOT NULL,
  `year` int NOT NULL,
  `total_entitlement` int DEFAULT '0',
  `used_days` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `leave_balances`
--

INSERT INTO `leave_balances` (`id`, `employee_id`, `leave_type`, `year`, `total_entitlement`, `used_days`, `created_at`, `updated_at`) VALUES
(1, 1, 'vacation', 2024, 15, 5, '2026-01-20 13:48:27', '2026-01-20 13:48:27'),
(2, 1, 'sick', 2024, 10, 2, '2026-01-20 13:48:27', '2026-01-20 13:48:27');

-- --------------------------------------------------------

--
-- Stand-in structure for view `leave_balance_summary`
-- (See below for the actual view)
--
CREATE TABLE `leave_balance_summary` (
`employee_id` int
,`employee_name` varchar(102)
,`employee_type` enum('SG','LG','SO')
,`leave_type` enum('vacation','sick','personal','emergency','maternity','paternity','bereavement')
,`post` varchar(100)
,`remaining_days` bigint
,`total_entitlement` int
,`used_days` int
,`year` int
);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int NOT NULL,
  `post_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_type` enum('SG','LG','SO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `responsibilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `required_count` int DEFAULT '1',
  `filled_count` int DEFAULT '0',
  `priority` enum('Low','Medium','High','Urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Medium',
  `status` enum('Active','Inactive','Closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `shift_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_hours` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary_range` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `benefits` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reporting_to` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `post_title`, `post_code`, `department`, `employee_type`, `location`, `description`, `requirements`, `responsibilities`, `required_count`, `filled_count`, `priority`, `status`, `shift_type`, `work_hours`, `salary_range`, `benefits`, `reporting_to`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Security Guard - Mall', 'SG-MALL-001', 'Security', 'SG', 'SM Mall of Asia', 'Provide security services for mall operations', 'Valid Security Guard License, Physical fitness, Good communication skills', 'Patrol assigned areas, Monitor CCTV, Respond to incidents, Customer assistance', 5, 2, 'High', 'Active', 'Rotating', '8 hours', 'â‚±15,000 - â‚±18,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay', 'Security Supervisor', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(2, 'Lady Guard - Office Building', 'LG-OFFICE-001', 'Security', 'LG', 'BGC Office Tower', 'Provide security services for office building', 'Valid Lady Guard License, Professional appearance, Customer service skills', 'Access control, Visitor management, Emergency response, Building patrol', 3, 1, 'Medium', 'Active', 'Day Shift', '8 hours', 'â‚±16,000 - â‚±19,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO', 'Security Manager', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(3, 'Security Officer - Headquarters', 'SO-HQ-001', 'Security', 'SO', 'Main Office', 'Supervise security operations and personnel', 'Security Officer License, Leadership skills, 3+ years experience', 'Team supervision, Security planning, Incident investigation, Training coordination', 2, 1, 'Urgent', 'Active', 'Administrative', '8 hours', 'â‚±25,000 - â‚±30,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Car allowance', 'Security Director', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(4, 'Security Guard - Residential', 'SG-RES-001', 'Security', 'SG', 'Exclusive Subdivision', 'Provide security for residential community', 'Valid Security Guard License, Trustworthy, Community-oriented', 'Gate control, Community patrol, Resident assistance, Incident reporting', 4, 1, 'Medium', 'Active', 'Night Shift', '12 hours', 'â‚±14,000 - â‚±17,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay', 'Property Manager', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(5, 'Lady Guard - Hospital', 'LG-HOSP-001', 'Security', 'LG', 'Metro Hospital', 'Provide security services in hospital environment', 'Valid Lady Guard License, Medical knowledge preferred, Compassionate', 'Patient area security, Visitor screening, Emergency response, Medical escort', 2, 0, 'High', 'Active', 'Rotating', '8 hours', 'â‚±17,000 - â‚±20,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Hazard pay', 'Hospital Security Chief', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(6, 'Security Officer - Field Operations', 'SO-FIELD-001', 'Security', 'SO', 'Various Locations', 'Supervise field security operations', 'Security Officer License, Field experience, Problem-solving skills', 'Field supervision, Site assessments, Client relations, Team coordination', 1, 1, 'Medium', 'Active', 'Field Work', '8 hours', 'â‚±22,000 - â‚±28,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Field allowance', 'Operations Manager', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(7, 'sdafsadfasdf', 'sfd32432432', 'Security', 'SG', 'asdfsdfsdaf', 'asdfasdf', 'asdfasdf', 'asdfsadfds', 1, 0, 'Medium', 'Active', 'Day', '8 hours', '23423432', 'asdfsdf', 'sadfasdf', '2025-12-14', '2025-12-14 08:34:48', '2025-12-14 08:34:48'),
(8, 'asdfasdf', 'ssdf2332423', 'Administration', 'SG', 'asdfasdf', 'asdfsdf', 'sadfsdf', 'asdfsdfasdf', 1, 0, 'Medium', 'Active', 'Day', '8 hours', '24234234', 'asdfsdfasd', 'asfdasd', '2025-12-14', '2025-12-14 09:14:40', '2025-12-14 09:14:40');

-- --------------------------------------------------------

--
-- Stand-in structure for view `post_statistics`
-- (See below for the actual view)
--
CREATE TABLE `post_statistics` (
`available_positions` int
,`created_at` int
,`employee_type` int
,`filled_count` int
,`id` int
,`location` int
,`post_title` int
,`priority` int
,`required_count` int
,`status` int
);

-- --------------------------------------------------------

--
-- Table structure for table `security_settings`
--

CREATE TABLE `security_settings` (
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `security_settings`
--

INSERT INTO `security_settings` (`key`, `value`, `updated_at`) VALUES
('password_expiry_days', '90', '2026-01-15 08:23:05'),
('password_min_length', '8', '2026-01-15 07:17:22'),
('password_require_special', '0', '2026-01-15 08:39:28');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int NOT NULL,
  `ticket_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` enum('system_issue','access_request','data_issue','feature_request','general_inquiry','bug_report') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'general_inquiry',
  `priority` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'medium',
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('open','in_progress','pending_user','resolved','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'open',
  `assigned_to` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int NOT NULL,
  `ticket_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_internal` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_off_requests`
--

CREATE TABLE `time_off_requests` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `request_type` enum('vacation','sick','personal','emergency','maternity','paternity','bereavement','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_off_requests`
--

INSERT INTO `time_off_requests` (`id`, `employee_id`, `request_type`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 'vacation', '2024-02-01', '2024-02-05', 5, 'Family vacation', 'approved', 5, '2024-01-20 02:30:00', NULL, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(2, 2, 'sick', '2024-01-20', '2024-01-22', 3, 'Flu symptoms', 'approved', 5, '2024-01-19 06:15:00', NULL, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(3, 3, 'personal', '2024-02-10', '2024-02-10', 1, 'Personal appointment', 'pending', NULL, NULL, NULL, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(4, 4, 'emergency', '2024-01-25', '2024-01-25', 1, 'Family emergency', 'approved', 5, '2024-01-24 08:45:00', NULL, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(5, 5, 'vacation', '2024-03-15', '2024-03-20', 6, 'Holiday break', 'pending', NULL, NULL, NULL, '2025-12-06 08:39:02', '2025-12-06 08:39:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','hr_admin','hr','admin','accounting','operation','logistics','employee','developer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hr_admin',
  `status` enum('active','inactive','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `employee_id` int DEFAULT NULL COMMENT 'Link to employees table if user is an employee',
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Profile picture path',
  `last_login` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failed_login_attempts` int DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL COMMENT 'Account lockout until this timestamp',
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_secret` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_recovery_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL COMMENT 'User who created this account',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `name`, `role`, `status`, `employee_id`, `department`, `phone`, `avatar`, `last_login`, `last_login_ip`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `remember_token`, `two_factor_secret`, `two_factor_enabled`, `two_factor_recovery_codes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'hr.admin', 'hr.admin@goldenz5.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria L. Santos', 'hr_admin', 'active', NULL, 'Human Resources', '0917-100-0001', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-12-01 02:11:35', '2025-12-01 02:11:35'),
(2, 'hr.lead', 'hr.lead@goldenz5.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Angela M. Reyes', 'hr_admin', 'active', NULL, 'Human Resources', '0917-100-0002', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 1, '2025-12-01 02:11:35', '2025-12-01 02:11:35'),
(3, 'hr.ops', 'hr.ops@goldenz5.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos P. Dizon', 'hr_admin', 'active', NULL, 'Human Resources', '0917-100-0003', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 1, '2025-12-01 02:11:35', '2025-12-01 02:11:35'),
(4, 'dev.lead', 'dev.lead@goldenz5.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jacob R. Villanueva', 'developer', 'active', NULL, 'IT/Development', '0917-200-0001', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 1, '2025-12-01 02:11:35', '2025-12-01 02:11:35'),
(5, 'dev.engineer', 'dev.engineer@goldenz5.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lara S. Mendoza', 'developer', 'active', NULL, 'IT/Development', '0917-200-0002', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 1, '2025-12-01 02:11:35', '2025-12-01 02:11:35'),
(6, 'dev.ops', 'dev.ops@goldenz5.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Noel T. Cruz', 'developer', 'active', NULL, 'IT/Development', '0917-200-0003', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 1, '2025-12-01 02:11:35', '2025-12-01 02:11:35'),
(25, 'hradmin', 'hradmin@goldenz5.com', '$2y$10$2Fp4cu96Oey7AQ87V/fWd.EVqmVzV5chLxspeoyzzSPooNUOXxYDq', 'HR Administrator', 'hr_admin', 'active', NULL, NULL, NULL, NULL, '2026-01-20 13:43:24', '172.18.0.1', 0, NULL, '2026-01-13 05:22:28', NULL, NULL, 0, NULL, NULL, '2025-12-11 17:49:50', '2026-01-20 13:43:24'),
(30, 'grey', 'greycruz00000000@gmail.com', '$2y$10$7qyDoZ3GUP4okfOd0TGQpeDWUkPAdvBJTXDnTVqOXCoOxOu.z/Vui', 'aldrin', 'super_admin', 'active', NULL, 'dikoalam', '09563211331', NULL, '2026-01-16 08:10:18', '192.168.1.7', 0, NULL, '2026-01-15 05:31:24', NULL, NULL, 0, NULL, 25, '2026-01-13 08:36:54', '2026-01-16 08:10:18'),
(31, 'amor', 'amor@gmail.com', '$2y$10$evvPUIl.aoXr/icZ85PNH.zTj4wnx.TEzKxHqijLpO0NgoAw7OyAa', 'amor', 'hr_admin', 'active', NULL, 'asdfjh', '09562312321', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 30, '2026-01-13 09:58:43', '2026-01-13 09:58:43'),
(32, 'ChristianAmor', 'christian5787264@gmail.com', '$2y$10$4jtmtLYPqQBDYvKq3D9AouI3kPDYDJObT7mtYZ.PHY2j5rx64rqj.', 'christian amor', 'super_admin', 'active', NULL, 'it', '09613014462', NULL, '2026-01-15 08:20:37', '192.168.1.23', 0, NULL, '2026-01-15 08:18:54', NULL, 'ZHLMOAXVWIX2DK4A', 1, NULL, 30, '2026-01-13 10:01:09', '2026-01-15 08:20:37'),
(0, 'aaaaa', 'aa@gmail.com', '$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6', 'alskdjfjklfdsa', 'hr_admin', 'active', NULL, 'askldjf', '2980374234', NULL, '2026-01-15 05:28:23', '192.168.1.7', 0, NULL, '2026-01-15 06:29:12', 'd95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552', NULL, 0, NULL, 30, '2026-01-15 03:07:15', '2026-01-16 04:54:16'),
(0, 'zzzzz', 'zzz@gmail.com', '$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6', 'salkjfksldaj', 'hr_admin', 'active', NULL, 'salkjfdklsajd', '213849089234', NULL, '2026-01-15 05:28:23', '192.168.1.7', 0, NULL, '2026-01-15 06:29:12', 'd95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552', NULL, 0, NULL, 30, '2026-01-15 03:08:25', '2026-01-16 04:54:16'),
(0, 'bbbbaaa', 'bb@gmail.com', '$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6', 'sadfsdfa', 'hr_admin', 'active', NULL, 'sadfasfd', '2893749823', NULL, '2026-01-15 05:28:23', '192.168.1.7', 0, NULL, '2026-01-15 06:29:12', 'd95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552', NULL, 0, NULL, 30, '2026-01-15 03:21:17', '2026-01-16 04:54:16'),
(0, 'zazaza', 'zz@gmail.com', '$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6', 'saddfsafdas', 'hr_admin', 'active', NULL, 'asfsadf', '12312321', NULL, '2026-01-15 05:28:23', '192.168.1.7', 0, NULL, '2026-01-15 06:29:12', 'd95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552', NULL, 0, NULL, 30, '2026-01-15 03:23:16', '2026-01-16 04:54:16'),
(0, 'aldrin', 'aldrininocencio212527@gmail.com', '$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6', 'aldrin', 'hr_admin', 'active', NULL, 'IT', '09563211331', NULL, '2026-01-15 05:28:23', '192.168.1.7', 0, NULL, '2026-01-15 06:29:12', 'd95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552', NULL, 0, NULL, 30, '2026-01-15 05:23:05', '2026-01-16 04:54:16'),
(0, 'sssss', 'sss@gmail.com', '$2y$10$V.Tmj4tp3ANqZvMY77yJBurZ1kBuJGCwf5hMdu.vXR1uJbkxioxXi', 'ssssss', 'hr_admin', 'active', NULL, 'sss', '23432432', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 30, '2026-01-16 04:34:07', '2026-01-16 04:54:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table_name` (`table_name`),
  ADD KEY `idx_record_id` (`record_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `dtr_entries`
--
ALTER TABLE `dtr_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_date` (`employee_id`,`entry_date`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_entry_date` (`entry_date`),
  ADD KEY `idx_entry_type` (`entry_type`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_no` (`license_no`),
  ADD KEY `idx_employee_no` (`employee_no`),
  ADD KEY `idx_employee_type` (`employee_type`),
  ADD KEY `idx_post` (`post`),
  ADD KEY `idx_license_no` (`license_no`),
  ADD KEY `idx_license_exp` (`license_exp_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_convicted` (`convicted`),
  ADD KEY `idx_filed_case` (`filed_case`),
  ADD KEY `idx_prohibited_drugs` (`prohibited_drugs`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `employee_alerts`
--
ALTER TABLE `employee_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_alert_type` (`alert_type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `employee_checklist`
--
ALTER TABLE `employee_checklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_item` (`employee_id`,`item_key`),
  ADD KEY `completed_by` (`completed_by`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_item_key` (`item_key`),
  ADD KEY `idx_completed` (`completed`);

--
-- Indexes for table `hr_tasks`
--
ALTER TABLE `hr_tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_number` (`task_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_assigned_to` (`assigned_to`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_leave_year` (`employee_id`,`leave_type`,`year`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_leave_type` (`leave_type`),
  ADD KEY `idx_year` (`year`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `post_code` (`post_code`),
  ADD KEY `idx_employee_type` (`employee_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `security_settings`
--
ALTER TABLE `security_settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_no` (`ticket_no`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_ticket_no` (`ticket_no`);

--
-- Indexes for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `dtr_entries`
--
ALTER TABLE `dtr_entries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `hr_tasks`
--
ALTER TABLE `hr_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `dtr_summary`
--
DROP TABLE IF EXISTS `dtr_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dtr_summary`  AS SELECT `e`.`id` AS `employee_id`, concat(`e`.`surname`,', ',`e`.`first_name`) AS `employee_name`, `e`.`post` AS `post`, `d`.`entry_date` AS `entry_date`, `d`.`time_in` AS `time_in`, `d`.`time_out` AS `time_out`, `d`.`entry_type` AS `entry_type`, (case when ((`d`.`time_in` is not null) and (`d`.`time_out` is not null)) then timestampdiff(HOUR,concat(`d`.`entry_date`,' ',`d`.`time_in`),concat(`d`.`entry_date`,' ',`d`.`time_out`)) else NULL end) AS `hours_worked` FROM (`employees` `e` left join `dtr_entries` `d` on((`e`.`id` = `d`.`employee_id`))) WHERE (`d`.`entry_date` >= (curdate() - interval 30 day)) ;

-- --------------------------------------------------------

--
-- Structure for view `employee_details`
--
DROP TABLE IF EXISTS `employee_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `employee_details`  AS SELECT `e`.`id` AS `id`, `e`.`employee_no` AS `employee_no`, `e`.`employee_type` AS `employee_type`, concat(`e`.`surname`,', ',`e`.`first_name`,' ',coalesce(`e`.`middle_name`,'')) AS `full_name`, `e`.`post` AS `post`, `e`.`license_no` AS `license_no`, `e`.`license_exp_date` AS `license_exp_date`, `e`.`date_hired` AS `date_hired`, `e`.`status` AS `status`, `e`.`created_at` AS `created_at`, `e`.`updated_at` AS `updated_at`, (case when (`e`.`license_exp_date` < curdate()) then 'Expired' when (`e`.`license_exp_date` <= (curdate() + interval 30 day)) then 'Expiring Soon' else 'Valid' end) AS `license_status` FROM `employees` AS `e` ;

-- --------------------------------------------------------

--
-- Structure for view `leave_balance_summary`
--
DROP TABLE IF EXISTS `leave_balance_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `leave_balance_summary`  AS SELECT `e`.`id` AS `employee_id`, concat(`e`.`surname`,', ',`e`.`first_name`) AS `employee_name`, `e`.`employee_type` AS `employee_type`, `e`.`post` AS `post`, `lb`.`leave_type` AS `leave_type`, `lb`.`year` AS `year`, `lb`.`total_entitlement` AS `total_entitlement`, `lb`.`used_days` AS `used_days`, (ifnull(`lb`.`total_entitlement`,0) - ifnull(`lb`.`used_days`,0)) AS `remaining_days` FROM (`employees` `e` left join `leave_balances` `lb` on(((`e`.`id` = `lb`.`employee_id`) and (`lb`.`year` = year(curdate()))))) ;

-- --------------------------------------------------------

--
-- Structure for view `post_statistics`
--
DROP TABLE IF EXISTS `post_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `post_statistics`  AS SELECT 1 AS `id`, 1 AS `post_title`, 1 AS `location`, 1 AS `employee_type`, 1 AS `required_count`, 1 AS `filled_count`, 1 AS `available_positions`, 1 AS `priority`, 1 AS `status`, 1 AS `created_at` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dtr_entries`
--
ALTER TABLE `dtr_entries`
  ADD CONSTRAINT `dtr_entries_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
