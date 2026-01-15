-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 15, 2026 at 11:14 AM
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
-- Database: `goldenz_hr`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CalculateMonthlyAttendance` (IN `target_month` INT, IN `target_year` INT)   BEGIN
    SELECT 
        e.id,
        CONCAT(e.surname, ', ', e.first_name) as employee_name,
        e.post,
        COUNT(d.id) as total_entries,
        SUM(CASE WHEN d.entry_type = 'time-in' THEN 1 ELSE 0 END) as time_in_count,
        SUM(CASE WHEN d.entry_type = 'time-out' THEN 1 ELSE 0 END) as time_out_count,
        SUM(CASE WHEN d.entry_type = 'break' THEN 1 ELSE 0 END) as break_count,
        SUM(CASE WHEN d.entry_type = 'overtime' THEN 1 ELSE 0 END) as overtime_count
    FROM employees e
    LEFT JOIN dtr_entries d ON e.id = d.employee_id 
        AND MONTH(d.entry_date) = target_month 
        AND YEAR(d.entry_date) = target_year
    WHERE e.status = 'Active'
    GROUP BY e.id, e.surname, e.first_name, e.post
    ORDER BY e.surname, e.first_name;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateLicenseExpiryAlerts` ()   BEGIN
    INSERT INTO employee_alerts (employee_id, alert_type, title, description, alert_date, due_date, priority, status, created_by)
    SELECT 
        e.id,
        'license_expiry',
        'Security License Expiring Soon',
        CONCAT('Security guard license (', e.license_no, ') will expire in ', DATEDIFF(e.license_exp_date, CURDATE()), ' days. Please renew before expiration.'),
        CURDATE(),
        e.license_exp_date,
        CASE 
            WHEN DATEDIFF(e.license_exp_date, CURDATE()) <= 7 THEN 'urgent'
            WHEN DATEDIFF(e.license_exp_date, CURDATE()) <= 15 THEN 'high'
            ELSE 'medium'
        END,
        'active',
        1
    FROM employees e
    WHERE e.license_no IS NOT NULL 
    AND e.license_exp_date IS NOT NULL 
    AND e.license_exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND e.id NOT IN (
        SELECT employee_id 
        FROM employee_alerts 
        WHERE alert_type = 'license_expiry' 
        AND status IN ('active', 'acknowledged')
        AND due_date >= CURDATE()
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdatePostFilledCount` (IN `post_id` INT)   BEGIN
    UPDATE posts 
    SET filled_count = (
        SELECT COUNT(*) 
        FROM employees 
        WHERE post = (SELECT post_title FROM posts WHERE id = post_id)
        AND status = 'Active'
    )
    WHERE id = post_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(0, 25, 'LOGIN_ATTEMPT', 'users', 25, NULL, '{\"login_time\":\"2026-01-15 11:01:02\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-15 03:01:02'),
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
(55, 30, 'LOGIN_ATTEMPT', 'users', 30, NULL, '{\"login_time\":\"2026-01-14 07:42:03\"}', '192.168.1.7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2026-01-13 23:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `dtr_entries`
--

CREATE TABLE `dtr_entries` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `entry_type` enum('time-in','time-out','break','overtime') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `dtr_summary`
-- (See below for the actual view)
--
CREATE TABLE `dtr_summary` (
`employee_id` int(11)
,`employee_name` varchar(102)
,`post` varchar(100)
,`entry_date` date
,`time_in` time
,`time_out` time
,`entry_type` enum('time-in','time-out','break','overtime')
,`hours_worked` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_no` int(11) NOT NULL,
  `employee_type` enum('SG','LG','SO') NOT NULL COMMENT 'SG = Security Guard, LG = Lady Guard, SO = Security Officer',
  `surname` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `post` varchar(100) NOT NULL COMMENT 'Assignment/Post',
  `license_no` varchar(50) DEFAULT NULL,
  `license_exp_date` date DEFAULT NULL,
  `rlm_exp` varchar(50) DEFAULT NULL COMMENT 'RLM = Renewal of License/Membership',
  `date_hired` date NOT NULL,
  `cp_number` varchar(20) DEFAULT NULL COMMENT 'Contact Phone Number',
  `sss_no` varchar(20) DEFAULT NULL COMMENT 'Social Security System Number',
  `pagibig_no` varchar(20) DEFAULT NULL COMMENT 'PAG-IBIG Fund Number',
  `tin_number` varchar(20) DEFAULT NULL COMMENT 'Tax Identification Number',
  `philhealth_no` varchar(20) DEFAULT NULL COMMENT 'PhilHealth Number',
  `birth_date` date DEFAULT NULL,
  `height` varchar(10) DEFAULT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `contact_person_address` text DEFAULT NULL,
  `contact_person_number` varchar(20) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive','Terminated','Suspended') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_no`, `employee_type`, `surname`, `first_name`, `middle_name`, `post`, `license_no`, `license_exp_date`, `rlm_exp`, `date_hired`, `cp_number`, `sss_no`, `pagibig_no`, `tin_number`, `philhealth_no`, `birth_date`, `height`, `weight`, `address`, `contact_person`, `relationship`, `contact_person_address`, `contact_person_number`, `blood_type`, `religion`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'SG', 'ABAD', 'JOHN MARK', 'DANIEL', 'BENAVIDES', 'R4B-202309000367', '2028-09-14', '2025-10-05', '2023-09-28', '0926-6917781', '04-4417766-7', '1213-0723-0701', '623-432-731-000', '09-202633701-3', '1999-10-05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2026-01-15 02:59:38', '2026-01-15 02:59:38'),
(2, 2, 'LG', 'ABADILLA', 'NORA', 'CABALQUINTO', 'SAPPORO', 'NCR-202411000339', '2029-11-07', '2025-12-27', '2024-11-13', '0967-9952106', '03-9677548-9', '1210-1313-3667', '905-112-708-000', '02-200206334-6', '1970-12-27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2026-01-15 02:59:38', '2026-01-15 02:59:38'),
(3, 3, 'LG', 'ABANILLA', 'VILMA', 'ABEDAÑO', 'MCMC', 'R05-202412001808', '2029-12-20', '2025-04-05', '2022-03-30', '0928-5781417', '33-0816833-7', '1211-3233-7121', '236-835-638-000', '19-090526559-1', '1974-06-10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2026-01-15 02:59:38', '2026-01-15 02:59:38'),
(4, 4, 'SG', 'ABDULMAN', 'ALMAN', 'JAINUDDIN', 'MCMC', 'BAR-202504000186', '2030-04-07', 'NO SEMINAR', '2025-06-26', '0905-1844366', '10-1537326-8', '1213-4444-8273', '676-724-973-000', '14-050287358-6', '2002-12-04', '5\'7', '62 KG', 'BLOCK 27, ADDITION HILLS, MANDALUYONG CITY', 'ALNISAR SAID', 'COUSIN', 'BLOCK 27, ADDITION HILLS, MANDALUYONG CITY', '0912-9440814', NULL, 'MUSLIM', 'Active', '2026-01-15 02:59:38', '2026-01-15 02:59:38');

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
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `alert_type` enum('license_expiry','document_expiry','missing_documents','contract_expiry','training_due','medical_expiry','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `alert_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('active','acknowledged','resolved','dismissed') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `item_key` varchar(100) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `employee_details`
-- (See below for the actual view)
--
CREATE TABLE `employee_details` (
`id` int(11)
,`employee_no` int(11)
,`employee_type` enum('SG','LG','SO')
,`full_name` varchar(153)
,`post` varchar(100)
,`license_no` varchar(50)
,`license_exp_date` date
,`date_hired` date
,`status` enum('Active','Inactive','Terminated','Suspended')
,`created_at` timestamp
,`updated_at` timestamp
,`license_status` varchar(13)
);

-- --------------------------------------------------------

--
-- Table structure for table `hr_tasks`
--

CREATE TABLE `hr_tasks` (
  `id` int(11) NOT NULL,
  `task_number` varchar(20) NOT NULL,
  `task_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('Employee Record','License','Leave Request','Clearance','Cash Bond','Other') DEFAULT 'Other',
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_by_name` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `urgency_level` enum('normal','important','critical') DEFAULT 'normal',
  `location_page` varchar(255) DEFAULT NULL COMMENT 'Where can it be found',
  `notes` text DEFAULT NULL COMMENT 'Notes created by the person who alerted',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL COMMENT 'HR Admin user ID',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('vacation','sick','personal','emergency','maternity','paternity','bereavement') NOT NULL,
  `year` int(11) NOT NULL,
  `total_entitlement` int(11) DEFAULT 0,
  `used_days` int(11) DEFAULT 0,
  `remaining_days` int(11) GENERATED ALWAYS AS (`total_entitlement` - `used_days`) STORED,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_balances`
--

INSERT INTO `leave_balances` (`id`, `employee_id`, `leave_type`, `year`, `total_entitlement`, `used_days`, `created_at`, `updated_at`) VALUES
(1, 1, 'vacation', 2024, 15, 5, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(2, 1, 'sick', 2024, 10, 2, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(3, 2, 'vacation', 2024, 15, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(4, 2, 'sick', 2024, 10, 3, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(5, 3, 'vacation', 2024, 15, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(6, 3, 'sick', 2024, 10, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(7, 4, 'vacation', 2024, 15, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(8, 4, 'sick', 2024, 10, 1, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(9, 5, 'vacation', 2024, 20, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(10, 5, 'sick', 2024, 15, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(11, 6, 'vacation', 2024, 15, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(12, 6, 'sick', 2024, 10, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(13, 7, 'vacation', 2024, 15, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(14, 7, 'sick', 2024, 10, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(15, 8, 'vacation', 2024, 20, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(16, 8, 'sick', 2024, 15, 0, '2025-12-06 08:39:02', '2025-12-06 08:39:02');

-- --------------------------------------------------------

--
-- Stand-in structure for view `leave_balance_summary`
-- (See below for the actual view)
--
CREATE TABLE `leave_balance_summary` (
`employee_id` int(11)
,`employee_name` varchar(102)
,`employee_type` enum('SG','LG','SO')
,`post` varchar(100)
,`leave_type` enum('vacation','sick','personal','emergency','maternity','paternity','bereavement')
,`year` int(11)
,`total_entitlement` int(11)
,`used_days` int(11)
,`remaining_days` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `post_title` varchar(255) NOT NULL,
  `post_code` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employee_type` enum('SG','LG','SO') NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `required_count` int(11) DEFAULT 1,
  `filled_count` int(11) DEFAULT 0,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('Active','Inactive','Closed') DEFAULT 'Active',
  `shift_type` varchar(50) DEFAULT NULL,
  `work_hours` varchar(50) DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `reporting_to` varchar(100) DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `post_title`, `post_code`, `department`, `employee_type`, `location`, `description`, `requirements`, `responsibilities`, `required_count`, `filled_count`, `priority`, `status`, `shift_type`, `work_hours`, `salary_range`, `benefits`, `reporting_to`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Security Guard - Mall', 'SG-MALL-001', 'Security', 'SG', 'SM Mall of Asia', 'Provide security services for mall operations', 'Valid Security Guard License, Physical fitness, Good communication skills', 'Patrol assigned areas, Monitor CCTV, Respond to incidents, Customer assistance', 5, 2, 'High', 'Active', 'Rotating', '8 hours', '₱15,000 - ₱18,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay', 'Security Supervisor', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(2, 'Lady Guard - Office Building', 'LG-OFFICE-001', 'Security', 'LG', 'BGC Office Tower', 'Provide security services for office building', 'Valid Lady Guard License, Professional appearance, Customer service skills', 'Access control, Visitor management, Emergency response, Building patrol', 3, 1, 'Medium', 'Active', 'Day Shift', '8 hours', '₱16,000 - ₱19,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO', 'Security Manager', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(3, 'Security Officer - Headquarters', 'SO-HQ-001', 'Security', 'SO', 'Main Office', 'Supervise security operations and personnel', 'Security Officer License, Leadership skills, 3+ years experience', 'Team supervision, Security planning, Incident investigation, Training coordination', 2, 1, 'Urgent', 'Active', 'Administrative', '8 hours', '₱25,000 - ₱30,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Car allowance', 'Security Director', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(4, 'Security Guard - Residential', 'SG-RES-001', 'Security', 'SG', 'Exclusive Subdivision', 'Provide security for residential community', 'Valid Security Guard License, Trustworthy, Community-oriented', 'Gate control, Community patrol, Resident assistance, Incident reporting', 4, 1, 'Medium', 'Active', 'Night Shift', '12 hours', '₱14,000 - ₱17,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay', 'Property Manager', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(5, 'Lady Guard - Hospital', 'LG-HOSP-001', 'Security', 'LG', 'Metro Hospital', 'Provide security services in hospital environment', 'Valid Lady Guard License, Medical knowledge preferred, Compassionate', 'Patient area security, Visitor screening, Emergency response, Medical escort', 2, 0, 'High', 'Active', 'Rotating', '8 hours', '₱17,000 - ₱20,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Hazard pay', 'Hospital Security Chief', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(6, 'Security Officer - Field Operations', 'SO-FIELD-001', 'Security', 'SO', 'Various Locations', 'Supervise field security operations', 'Security Officer License, Field experience, Problem-solving skills', 'Field supervision, Site assessments, Client relations, Team coordination', 1, 1, 'Medium', 'Active', 'Field Work', '8 hours', '₱22,000 - ₱28,000', 'SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Field allowance', 'Operations Manager', '2024-12-31', '2025-12-06 08:39:02', '2025-12-06 08:39:02'),
(7, 'sdafsadfasdf', 'sfd32432432', 'Security', 'SG', 'asdfsdfsdaf', 'asdfasdf', 'asdfasdf', 'asdfsadfds', 1, 0, 'Medium', 'Active', 'Day', '8 hours', '23423432', 'asdfsdf', 'sadfasdf', '2025-12-14', '2025-12-14 08:34:48', '2025-12-14 08:34:48'),
(8, 'asdfasdf', 'ssdf2332423', 'Administration', 'SG', 'asdfasdf', 'asdfsdf', 'sadfsdf', 'asdfsdfasdf', 1, 0, 'Medium', 'Active', 'Day', '8 hours', '24234234', 'asdfsdfasd', 'asfdasd', '2025-12-14', '2025-12-14 09:14:40', '2025-12-14 09:14:40');

-- --------------------------------------------------------

--
-- Stand-in structure for view `post_statistics`
-- (See below for the actual view)
--
CREATE TABLE `post_statistics` (
`id` int(11)
,`post_title` varchar(255)
,`location` varchar(255)
,`employee_type` enum('SG','LG','SO')
,`required_count` int(11)
,`filled_count` int(11)
,`available_positions` bigint(12)
,`priority` enum('Low','Medium','High','Urgent')
,`status` enum('Active','Inactive','Closed')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `security_settings`
--

CREATE TABLE `security_settings` (
  `key` varchar(100) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `id` int(11) NOT NULL,
  `ticket_no` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `category` enum('system_issue','access_request','data_issue','feature_request','general_inquiry','bug_report') DEFAULT 'general_inquiry',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','in_progress','pending_user','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_off_requests`
--

CREATE TABLE `time_off_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `request_type` enum('vacation','sick','personal','emergency','maternity','paternity','bereavement','other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('super_admin','hr_admin','hr','admin','accounting','operation','logistics','employee','developer') NOT NULL DEFAULT 'hr_admin',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `employee_id` int(11) DEFAULT NULL COMMENT 'Link to employees table if user is an employee',
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL COMMENT 'Profile picture path',
  `last_login` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL COMMENT 'Account lockout until this timestamp',
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `two_factor_secret` varchar(64) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'User who created this account',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(25, 'hradmin', 'hradmin@goldenz5.com', '$2y$10$2Fp4cu96Oey7AQ87V/fWd.EVqmVzV5chLxspeoyzzSPooNUOXxYDq', 'HR Administrator', 'hr_admin', 'active', NULL, NULL, NULL, NULL, '2026-01-15 08:42:25', '192.168.1.7', 0, NULL, '2026-01-13 05:22:28', NULL, NULL, 0, NULL, NULL, '2025-12-11 17:49:50', '2026-01-15 08:42:25'),
(30, 'grey', 'greycruz00000000@gmail.com', '$2y$10$7qyDoZ3GUP4okfOd0TGQpeDWUkPAdvBJTXDnTVqOXCoOxOu.z/Vui', 'aldrin', 'super_admin', 'active', NULL, 'dikoalam', '09563211331', NULL, '2026-01-15 10:09:46', '192.168.1.7', 0, NULL, '2026-01-15 05:31:24', NULL, 'IG5TPS6YVHHLKPTL', 1, NULL, 25, '2026-01-13 08:36:54', '2026-01-15 10:09:46'),
(31, 'amor', 'amor@gmail.com', '$2y$10$evvPUIl.aoXr/icZ85PNH.zTj4wnx.TEzKxHqijLpO0NgoAw7OyAa', 'amor', 'hr_admin', 'active', NULL, 'asdfjh', '09562312321', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 30, '2026-01-13 09:58:43', '2026-01-13 09:58:43'),
(32, 'ChristianAmor', 'christian5787264@gmail.com', '$2y$10$4jtmtLYPqQBDYvKq3D9AouI3kPDYDJObT7mtYZ.PHY2j5rx64rqj.', 'christian amor', 'super_admin', 'active', NULL, 'it', '09613014462', NULL, '2026-01-15 08:20:37', '192.168.1.23', 0, NULL, '2026-01-15 08:18:54', NULL, 'ZHLMOAXVWIX2DK4A', 1, NULL, 30, '2026-01-13 10:01:09', '2026-01-15 08:20:37'),
(0, 'aaaaa', 'aa@gmail.com', '$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6', 'alskdjfjklfdsa', 'hr', 'active', NULL, 'askldjf', '2980374234', NULL, '2026-01-15 05:28:23', '192.168.1.7', 0, NULL, '2026-01-15 06:29:12', 'd95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552', NULL, 0, NULL, 30, '2026-01-15 03:07:15', '2026-01-15 05:29:12'),
(0, 'zzzzz', 'zzz@gmail.com', '$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6', 'salkjfksldaj', 'hr', 'active', NULL, 'salkjfdklsajd', '213849089234', NULL, '2026-01-15 05:28:23', '192.168.1.7', 0, NULL, '2026-01-15 06:29:12', 'd95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552', NULL, 0, NULL, 30, '2026-01-15 03:08:25', '2026-01-15 05:29:12'),
(0, 'bbbbaaa', 'bb@gmail.com', '$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6', 'sadfsdfa', 'hr', 'active', NULL, 'sadfasfd', '2893749823', NULL, '2026-01-15 05:28:23', '192.168.1.7', 0, NULL, '2026-01-15 06:29:12', 'd95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552', NULL, 0, NULL, 30, '2026-01-15 03:21:17', '2026-01-15 05:29:12'),
(0, 'zazaza', 'zz@gmail.com', '$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6', 'saddfsafdas', 'hr', 'active', NULL, 'asfsadf', '12312321', NULL, '2026-01-15 05:28:23', '192.168.1.7', 0, NULL, '2026-01-15 06:29:12', 'd95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552', NULL, 0, NULL, 30, '2026-01-15 03:23:16', '2026-01-15 05:29:12'),
(0, 'aldrin', 'aldrininocencio212527@gmail.com', '$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6', 'aldrin', 'hr_admin', 'active', NULL, 'IT', '09563211331', NULL, '2026-01-15 05:28:23', '192.168.1.7', 0, NULL, '2026-01-15 06:29:12', 'd95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552', NULL, 0, NULL, 30, '2026-01-15 05:23:05', '2026-01-15 05:29:12');

-- --------------------------------------------------------

--
-- Structure for view `dtr_summary`
--
DROP TABLE IF EXISTS `dtr_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dtr_summary`  AS SELECT `e`.`id` AS `employee_id`, concat(`e`.`surname`,', ',`e`.`first_name`) AS `employee_name`, `e`.`post` AS `post`, `d`.`entry_date` AS `entry_date`, `d`.`time_in` AS `time_in`, `d`.`time_out` AS `time_out`, `d`.`entry_type` AS `entry_type`, CASE WHEN `d`.`time_in` is not null AND `d`.`time_out` is not null THEN timestampdiff(HOUR,concat(`d`.`entry_date`,' ',`d`.`time_in`),concat(`d`.`entry_date`,' ',`d`.`time_out`)) ELSE NULL END AS `hours_worked` FROM (`employees` `e` left join `dtr_entries` `d` on(`e`.`id` = `d`.`employee_id`)) WHERE `d`.`entry_date` >= curdate() - interval 30 day ;

-- --------------------------------------------------------

--
-- Structure for view `employee_details`
--
DROP TABLE IF EXISTS `employee_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `employee_details`  AS SELECT `e`.`id` AS `id`, `e`.`employee_no` AS `employee_no`, `e`.`employee_type` AS `employee_type`, concat(`e`.`surname`,', ',`e`.`first_name`,' ',coalesce(`e`.`middle_name`,'')) AS `full_name`, `e`.`post` AS `post`, `e`.`license_no` AS `license_no`, `e`.`license_exp_date` AS `license_exp_date`, `e`.`date_hired` AS `date_hired`, `e`.`status` AS `status`, `e`.`created_at` AS `created_at`, `e`.`updated_at` AS `updated_at`, CASE WHEN `e`.`license_exp_date` < curdate() THEN 'Expired' WHEN `e`.`license_exp_date` <= curdate() + interval 30 day THEN 'Expiring Soon' ELSE 'Valid' END AS `license_status` FROM `employees` AS `e` ;

-- --------------------------------------------------------

--
-- Structure for view `leave_balance_summary`
--
DROP TABLE IF EXISTS `leave_balance_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `leave_balance_summary`  AS SELECT `e`.`id` AS `employee_id`, concat(`e`.`surname`,', ',`e`.`first_name`) AS `employee_name`, `e`.`employee_type` AS `employee_type`, `e`.`post` AS `post`, `lb`.`leave_type` AS `leave_type`, `lb`.`year` AS `year`, `lb`.`total_entitlement` AS `total_entitlement`, `lb`.`used_days` AS `used_days`, `lb`.`remaining_days` AS `remaining_days` FROM (`employees` `e` left join `leave_balances` `lb` on(`e`.`id` = `lb`.`employee_id`)) WHERE `lb`.`year` = year(curdate()) ;

-- --------------------------------------------------------

--
-- Structure for view `post_statistics`
--
DROP TABLE IF EXISTS `post_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `post_statistics`  AS SELECT `p`.`id` AS `id`, `p`.`post_title` AS `post_title`, `p`.`location` AS `location`, `p`.`employee_type` AS `employee_type`, `p`.`required_count` AS `required_count`, `p`.`filled_count` AS `filled_count`, `p`.`required_count`- `p`.`filled_count` AS `available_positions`, `p`.`priority` AS `priority`, `p`.`status` AS `status`, `p`.`created_at` AS `created_at` FROM `posts` AS `p` ;

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
  ADD KEY `idx_status` (`status`);

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
-- AUTO_INCREMENT for table `dtr_entries`
--
ALTER TABLE `dtr_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7237;

--
-- AUTO_INCREMENT for table `hr_tasks`
--
ALTER TABLE `hr_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
