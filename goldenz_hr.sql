-- ============================================================================
-- GOLDEN Z-5 HR MANAGEMENT SYSTEM - MAIN DATABASE SCHEMA
-- ============================================================================
--
-- Company: Golden Z-5 Security and Investigation Agency, Inc.
-- Database: goldenz_hr
-- Version: 2.0
-- Last Updated: January 2026
--
-- ============================================================================
-- DESCRIPTION
-- ============================================================================
--
-- This is the complete database schema for the Golden Z-5 HR Management System.
-- It includes all tables, views, indexes, and data necessary for managing:
--   - Employees (Security Guards, Lady Guards, Security Officers)
--   - Posts and Assignments
--   - Employee Alerts and Notifications
--   - User Management and Permissions
--   - Audit Trails and System Logs
--   - DTR (Daily Time Records)
--   - Leave Management
--   - Password Reset Functionality
--
-- ============================================================================
-- APPLIED MIGRATIONS (CONSOLIDATED)
-- ============================================================================
--
-- All previous migration files have been consolidated into this single file.
-- The following migrations are already applied in this schema:
--
-- 1. Password Reset Migration
--    - Added password_reset_token column to users table
--    - Added password_reset_expires_at column to users table
--    - Added indexes for password reset token lookups
--
-- 2. User Name Fields Migration  
--    - Added first_name and last_name columns to users table
--    - Kept name column for backward compatibility
--
-- 3. Employee Page 2 Fields Migration
--    - Added vacancy_source, referral_name, agency contacts
--    - Added physical health and defect information
--    - Added driver's license information
--    - Added alcohol and drug-related fields
--    - Added security experience and conviction history
--    - Added specimen signatures and initials (3 each)
--    - Added fingerprint fields (10 fingers)
--    - Added basic requirements checklist (YO/NO fields)
--    - Added sworn statement fields
--    - Added document tracking (doc_no, page_no, book_no, series_of)
--
-- 4. System Logs Tables
--    - Created system_logs table for developer dashboard
--    - Created security_logs table for security event tracking
--    - Added proper indexes for performance
--
-- 5. Audit Logs Fix
--    - Fixed id column to be AUTO_INCREMENT (was causing duplicate key errors)
--    - Cleaned up any records with id=0
--
-- 6. Employee Auto-Increment Fix
--    - Reset AUTO_INCREMENT counter to max(id) + 1
--    - Prevents ID collision issues
--
-- ============================================================================
-- IMPORTANT NOTES
-- ============================================================================
--
-- • All tables use InnoDB engine for ACID compliance and foreign key support
-- • Charset: utf8mb4 with unicode collation for full Unicode support
-- • All timestamps use CURRENT_TIMESTAMP by default
-- • Foreign keys cascade on delete for data integrity
-- • Indexes are optimized for common query patterns
-- • JSON columns use json_valid() constraint for data validation
-- • AUTO_INCREMENT values are set to prevent ID conflicts
--
-- ============================================================================
-- INSTALLATION INSTRUCTIONS
-- ============================================================================
--
-- 1. Create database: CREATE DATABASE goldenz_hr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 2. Select database: USE goldenz_hr;
-- 3. Import this file: SOURCE /path/to/goldenz_hr.sql;
-- 4. Verify tables: SHOW TABLES;
-- 5. Check for errors: SHOW WARNINGS;
--
-- ============================================================================
-- MAINTENANCE
-- ============================================================================
--
-- • Regular backups recommended (daily automated backups available in system)
-- • Audit logs should be archived monthly to prevent table bloat
-- • Monitor AUTO_INCREMENT values approaching INT(11) limit (2,147,483,647)
-- • Review and optimize slow query log periodically
--
-- ============================================================================
-- MariaDB DUMP INFORMATION
-- ============================================================================
--
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
-- Host: localhost    Database: goldenz_hr
-- Server version: 10.4.32-MariaDB
-- Dump Date: January 2026
--
-- ============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'INSERT','employees',1,NULL,'{\"surname\": \"ABAD\", \"first_name\": \"JOHN MARK\", \"employee_no\": 1, \"employee_type\": \"SG\"}','192.168.1.100','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36','2024-01-15 00:30:00'),(2,1,'UPDATE','employees',1,'{\"status\": \"Active\"}','{\"status\": \"Active\"}','192.168.1.100','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36','2024-01-15 01:15:00'),(3,2,'INSERT','time_off_requests',1,NULL,'{\"start_date\": \"2024-02-01\", \"employee_id\": 1, \"request_type\": \"vacation\"}','192.168.1.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36','2024-01-15 02:00:00'),(4,25,'INSERT','employees',2574,NULL,'{\"employee_no\":\"23432\",\"first_name\":\"asdfsdfsdfsd\",\"surname\":\"sdfasdfsdfsdfasdfsdafsdf\",\"employee_type\":\"SG\",\"post\":\"Lady Guard - Hospital\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-12-13 04:52:11'),(5,25,'INSERT','employees',2867,NULL,'{\"employee_no\":\"43434\",\"first_name\":\"asdf\",\"surname\":\"sadf\",\"employee_type\":\"SG\",\"post\":\"Lady Guard - Hospital\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 06:13:18'),(6,25,'INSERT','employees',2924,NULL,'{\"employee_no\":\"12121\",\"first_name\":\"asdfsadf\",\"surname\":\"asfd\",\"employee_type\":\"SG\",\"post\":\"Lady Guard - Office Building\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 06:24:02'),(7,25,'INSERT','employees',3141,NULL,'{\"employee_no\":\"21342\",\"first_name\":\"dsafsdfsadf\",\"surname\":\"efsdafa\",\"employee_type\":\"SO\",\"post\":\"Security Officer - Headquarters\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 06:34:18'),(8,25,'INSERT','employees',3401,NULL,'{\"employee_no\":\"34232\",\"first_name\":\"sadfasfdasd\",\"surname\":\"sadfsf\",\"employee_type\":\"LG\",\"post\":\"Security Officer - Headquarters\",\"status\":\"Terminated\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 07:26:36'),(9,25,'INSERT','employees',3750,NULL,'{\"employee_no\":\"23423\",\"first_name\":\"SADFSADF\",\"surname\":\"ASDFSADF\",\"employee_type\":\"SG\",\"post\":\"LADY GUARD - OFFICE BUILDING\",\"status\":\"Terminated\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:15:05'),(10,25,'INSERT','employees',3783,NULL,'{\"employee_no\":\"24234\",\"first_name\":\"SDFASDFSFA\",\"surname\":\"SAFASDF\",\"employee_type\":\"SG\",\"post\":\"LADY GUARD - OFFICE BUILDING\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:17:00'),(11,1,'UPDATE','employees',3817,'{\"surname\": \"SADFSADFASD\", \"first_name\": \"FASDFSADFS\", \"status\": \"Inactive\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}','{\"surname\": \"SADFSADFASD\", \"first_name\": \"FASDFSADFS\", \"status\": \"Inactive\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}',NULL,NULL,'2025-12-14 08:20:48'),(12,25,'INSERT','employees',3817,NULL,'{\"employee_no\":\"23423\",\"first_name\":\"FASDFSADFS\",\"surname\":\"SADFSADFASD\",\"employee_type\":\"SG\",\"post\":\"SECURITY GUARD - RESIDENTIAL\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:20:48'),(13,1,'UPDATE','employees',3866,'{\"surname\": \"SDFASDFASDF\", \"first_name\": \"SADFASFASFSADF\", \"status\": \"Inactive\", \"post\": \"LADY GUARD - OFFICE BUILDING\"}','{\"surname\": \"SDFASDFASDF\", \"first_name\": \"SADFASFASFSADF\", \"status\": \"Inactive\", \"post\": \"LADY GUARD - OFFICE BUILDING\"}',NULL,NULL,'2025-12-14 08:27:18'),(14,25,'INSERT','employees',3866,NULL,'{\"employee_no\":\"24324\",\"first_name\":\"SADFASFASFSADF\",\"surname\":\"SDFASDFASDF\",\"employee_type\":\"SG\",\"post\":\"LADY GUARD - OFFICE BUILDING\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:27:18'),(15,1,'UPDATE','employees',3907,'{\"surname\": \"ASFSDFSADFASDF\", \"first_name\": \"ASDFSDFSD\", \"status\": \"Suspended\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}','{\"surname\": \"ASFSDFSADFASDF\", \"first_name\": \"ASDFSDFSD\", \"status\": \"Suspended\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}',NULL,NULL,'2025-12-14 08:30:41'),(16,25,'INSERT','employees',3907,NULL,'{\"employee_no\":\"23432\",\"first_name\":\"ASDFSDFSD\",\"surname\":\"ASFSDFSADFASDF\",\"employee_type\":\"LG\",\"post\":\"SECURITY GUARD - RESIDENTIAL\",\"status\":\"Suspended\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:30:42'),(17,1,'UPDATE','employees',3996,'{\"surname\": \"ASDFASDFSADFS\", \"first_name\": \"SDFSADFASDFSDAF\", \"status\": \"Suspended\", \"post\": \"SECURITY OFFICER - FIELD OPERATIONS\"}','{\"surname\": \"ASDFASDFSADFS\", \"first_name\": \"SDFSADFASDFSDAF\", \"status\": \"Suspended\", \"post\": \"SECURITY OFFICER - FIELD OPERATIONS\"}',NULL,NULL,'2025-12-14 08:33:42'),(18,25,'INSERT','employees',3996,NULL,'{\"employee_no\":\"23423\",\"first_name\":\"SDFSADFASDFSDAF\",\"surname\":\"ASDFASDFSADFS\",\"employee_type\":\"LG\",\"post\":\"SECURITY OFFICER - FIELD OPERATIONS\",\"status\":\"Suspended\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:33:42'),(19,25,'INSERT','employees',12729,NULL,'{\"employee_no\":\"34324\",\"first_name\":\"HEHE\",\"surname\":\"HAHA\",\"employee_type\":\"SG\",\"post\":\"UNASSIGNED\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-12 07:30:43'),(20,25,'INSERT','employees',13042,NULL,'{\"employee_no\":\"24324\",\"first_name\":\"CHRISTIAN\",\"surname\":\"AMOR\",\"employee_type\":\"SG\",\"post\":\"KAHIT SAN\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-12 07:47:54'),(21,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 13:22:12\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 05:22:12'),(22,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 13:22:17\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 05:22:17'),(23,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 13:45:41\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 05:45:41'),(24,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 13:49:45\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 05:49:45'),(25,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 16:02:37\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:02:37'),(26,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 16:06:17\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:06:17'),(27,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 16:08:29\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:08:29'),(28,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 16:08:36\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:08:36'),(29,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 16:09:04\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:09:04'),(30,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 16:10:03\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:10:03'),(31,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 16:12:39\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:12:39'),(32,25,'USER_CREATED','users',30,NULL,'{\"username\":\"grey\",\"email\":\"greycruz00000000@gmail.com\",\"name\":\"aldrin\",\"role\":\"hr_admin\",\"status\":\"active\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:36:54'),(33,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 16:37:46\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:37:46'),(34,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 16:38:08\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:38:08'),(35,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 16:42:01\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:42:01'),(36,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 16:52:06\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:52:06'),(37,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 16:52:12\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:52:12'),(38,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 16:52:42\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:52:42'),(39,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 17:10:10\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:10:10'),(40,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 17:16:42\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:16:42'),(41,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 17:17:12\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:17:12'),(42,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 17:23:58\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:23:58'),(43,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 17:42:17\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:42:17'),(44,30,'USER_ROLE_UPDATED','users',25,'{\"role\":\"hr_admin\"}','{\"role\":\"super_admin\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:45:59'),(45,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 17:46:06\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:46:06'),(46,25,'USER_ROLE_UPDATED','users',25,'{\"role\":\"super_admin\"}','{\"role\":\"hr_admin\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:46:29'),(47,25,'USER_ROLE_UPDATED','users',25,'{\"role\":\"hr_admin\"}','{\"role\":\"super_admin\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:46:34'),(48,25,'USER_ROLE_UPDATED','users',25,'{\"role\":\"super_admin\"}','{\"role\":\"hr_admin\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:46:36'),(49,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-13 17:46:51\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:46:51'),(50,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 17:47:04\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:47:04'),(51,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-13 17:49:29\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:49:29'),(52,30,'USER_CREATED','users',31,NULL,'{\"username\":\"amor\",\"email\":\"amor@gmail.com\",\"name\":\"amor\",\"role\":\"hr_admin\",\"status\":\"active\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:58:43'),(53,30,'USER_CREATED','users',32,NULL,'{\"username\":\"ChristianAmor\",\"email\":\"christian5787264@gmail.com\",\"name\":\"christian amor\",\"role\":\"super_admin\",\"status\":\"active\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 10:01:09'),(54,32,'LOGIN_ATTEMPT','users',32,NULL,'{\"login_time\":\"2026-01-13 18:01:59\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 10:01:59'),(55,30,'LOGIN_ATTEMPT','users',30,NULL,'{\"login_time\":\"2026-01-14 07:42:03\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 23:42:03'),(56,25,'LOGIN_ATTEMPT','users',25,NULL,'{\"login_time\":\"2026-01-16 16:12:26\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-16 08:12:26'),(57,1,'UPDATE','employees',14910,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:12:54'),(58,1,'UPDATE','employees',14910,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:12:54'),(59,1,'UPDATE','employees',1,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:14:52'),(60,1,'UPDATE','employees',1,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:14:52'),(61,1,'UPDATE','employees',2,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:17:15'),(62,1,'UPDATE','employees',2,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:17:15'),(63,1,'UPDATE','employees',3,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:19:27'),(64,1,'UPDATE','employees',3,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:19:27'),(65,1,'UPDATE','employees',4,'{\"surname\": \"ASDFASDF\", \"first_name\": \"ASDFASDF\", \"status\": \"Inactive\", \"post\": \"\"}','{\"surname\": \"ASDFASDF\", \"first_name\": \"ASDFASDF\", \"status\": \"Inactive\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:27:15'),(66,1,'UPDATE','employees',4,'{\"surname\": \"ASDFASDF\", \"first_name\": \"ASDFASDF\", \"status\": \"Inactive\", \"post\": \"\"}','{\"surname\": \"ASDFASDF\", \"first_name\": \"ASDFASDF\", \"status\": \"Inactive\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:27:15'),(67,1,'UPDATE','employees',5,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:28:13'),(68,1,'UPDATE','employees',5,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:28:13'),(69,1,'UPDATE','employees',6,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:31:30'),(70,1,'UPDATE','employees',7,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:33:42'),(71,1,'UPDATE','employees',7,'{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:33:42');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dtr_entries`
--

DROP TABLE IF EXISTS `dtr_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dtr_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `entry_type` enum('time-in','time-out','break','overtime') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_date` (`employee_id`,`entry_date`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_entry_date` (`entry_date`),
  KEY `idx_entry_type` (`entry_type`),
  CONSTRAINT `dtr_entries_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dtr_entries`
--

LOCK TABLES `dtr_entries` WRITE;
/*!40000 ALTER TABLE `dtr_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `dtr_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `dtr_summary`
--

DROP TABLE IF EXISTS `dtr_summary`;
/*!50001 DROP VIEW IF EXISTS `dtr_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `dtr_summary` AS SELECT
 1 AS `employee_id`,
  1 AS `employee_name`,
  1 AS `post`,
  1 AS `entry_date`,
  1 AS `time_in`,
  1 AS `time_out`,
  1 AS `entry_type`,
  1 AS `hours_worked` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `employee_alerts`
--

DROP TABLE IF EXISTS `employee_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_alerts`
--

LOCK TABLES `employee_alerts` WRITE;
/*!40000 ALTER TABLE `employee_alerts` DISABLE KEYS */;
INSERT INTO `employee_alerts` VALUES (1,1,'license_expiry','Security License Expiring Soon','Security guard license (R4B-202309000367) will expire in 30 days. Please renew before expiration.','2024-01-15','2028-09-14','medium','active',5,NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(2,2,'license_expiry','Security License Expiring Soon','Lady guard license (NCR-202411000339) will expire in 45 days. Please renew before expiration.','2024-01-15','2029-11-07','medium','active',5,NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(3,3,'training_due','Training Required','RLM training is due. Please complete required training before expiration.','2024-01-15','2025-04-05','high','active',5,NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(4,4,'document_expiry','Medical Certificate Expiring','Medical certificate will expire soon. Please renew for continued employment.','2024-01-15','2024-02-15','urgent','active',5,NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
/*!40000 ALTER TABLE `employee_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_checklist`
--

DROP TABLE IF EXISTS `employee_checklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_checklist` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `item_key` varchar(100) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_item` (`employee_id`,`item_key`),
  KEY `completed_by` (`completed_by`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_item_key` (`item_key`),
  KEY `idx_completed` (`completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_checklist`
--

LOCK TABLES `employee_checklist` WRITE;
/*!40000 ALTER TABLE `employee_checklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_checklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `employee_details`
--

DROP TABLE IF EXISTS `employee_details`;
/*!50001 DROP VIEW IF EXISTS `employee_details`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `employee_details` AS SELECT
 1 AS `id`,
  1 AS `employee_no`,
  1 AS `employee_type`,
  1 AS `full_name`,
  1 AS `post`,
  1 AS `license_no`,
  1 AS `license_exp_date`,
  1 AS `date_hired`,
  1 AS `status`,
  1 AS `created_at`,
  1 AS `updated_at`,
  1 AS `license_status` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `vacancy_source` text DEFAULT NULL COMMENT 'How did you know of the vacancy (JSON array: Ads, Walk-in, Referral)',
  `referral_name` varchar(150) DEFAULT NULL COMMENT 'Referral name if vacancy source is Referral',
  `knows_agency_person` enum('Yes','No') DEFAULT NULL COMMENT 'Do you know anyone from the agency?',
  `agency_person_name` varchar(200) DEFAULT NULL COMMENT 'Name and relationship with agency person',
  `physical_defect` enum('Yes','No') DEFAULT NULL COMMENT 'Do you have any physical defects or chronic ailments?',
  `physical_defect_specify` text DEFAULT NULL COMMENT 'Specify physical defects if yes',
  `drives` enum('Yes','No') DEFAULT NULL COMMENT 'Do you drive?',
  `drivers_license_no` varchar(50) DEFAULT NULL COMMENT 'Driver license number',
  `drivers_license_exp` varchar(50) DEFAULT NULL COMMENT 'Driver license expiration date',
  `drinks_alcohol` enum('Yes','No') DEFAULT NULL COMMENT 'Do you drink alcoholic beverages?',
  `alcohol_frequency` varchar(100) DEFAULT NULL COMMENT 'How frequent do you drink alcohol?',
  `prohibited_drugs` enum('Yes','No') DEFAULT NULL COMMENT 'Are you taking prohibited drugs?',
  `security_guard_experience` varchar(100) DEFAULT NULL COMMENT 'How long have you worked as a Security Guard?',
  `convicted` enum('Yes','No') DEFAULT NULL COMMENT 'Have you ever been convicted of any offense?',
  `conviction_details` text DEFAULT NULL COMMENT 'Specify conviction details if yes',
  `filed_case` enum('Yes','No') DEFAULT NULL COMMENT 'Have you filed any criminal/civil case against previous employer?',
  `case_specify` text DEFAULT NULL COMMENT 'Specify case details if yes',
  `action_after_termination` text DEFAULT NULL COMMENT 'What was your action after termination?',
  `signature_1` varchar(200) DEFAULT NULL COMMENT 'Specimen signature line 1',
  `signature_2` varchar(200) DEFAULT NULL COMMENT 'Specimen signature line 2',
  `signature_3` varchar(200) DEFAULT NULL COMMENT 'Specimen signature line 3',
  `initial_1` varchar(100) DEFAULT NULL COMMENT 'Specimen initial 1 (Pinakiling Pirma)',
  `initial_2` varchar(100) DEFAULT NULL COMMENT 'Specimen initial 2 (Pinakiling Pirma)',
  `initial_3` varchar(100) DEFAULT NULL COMMENT 'Specimen initial 3 (Pinakiling Pirma)',
  `fingerprint_right_thumb` varchar(255) DEFAULT NULL COMMENT 'Right thumb fingerprint file path',
  `fingerprint_right_index` varchar(255) DEFAULT NULL COMMENT 'Right index finger fingerprint file path',
  `fingerprint_right_middle` varchar(255) DEFAULT NULL COMMENT 'Right middle finger fingerprint file path',
  `fingerprint_right_ring` varchar(255) DEFAULT NULL COMMENT 'Right ring finger fingerprint file path',
  `fingerprint_right_little` varchar(255) DEFAULT NULL COMMENT 'Right little finger fingerprint file path',
  `fingerprint_left_thumb` varchar(255) DEFAULT NULL COMMENT 'Left thumb fingerprint file path',
  `fingerprint_left_index` varchar(255) DEFAULT NULL COMMENT 'Left index finger fingerprint file path',
  `fingerprint_left_middle` varchar(255) DEFAULT NULL COMMENT 'Left middle finger fingerprint file path',
  `fingerprint_left_ring` varchar(255) DEFAULT NULL COMMENT 'Left ring finger fingerprint file path',
  `fingerprint_left_little` varchar(255) DEFAULT NULL COMMENT 'Left little finger fingerprint file path',
  `requirements_signature` varchar(200) DEFAULT NULL COMMENT 'Signature over printed name for requirements section',
  `req_2x2` enum('YO','NO') DEFAULT NULL COMMENT '2x2 photos provided (YO/NO)',
  `req_birth_cert` enum('YO','NO') DEFAULT NULL COMMENT 'NSO/Birth Certificate provided (YO/NO)',
  `req_barangay` enum('YO','NO') DEFAULT NULL COMMENT 'Barangay Clearance provided (YO/NO)',
  `req_police` enum('YO','NO') DEFAULT NULL COMMENT 'Police Clearance provided (YO/NO)',
  `req_nbi` enum('YO','NO') DEFAULT NULL COMMENT 'NBI Clearance provided (YO/NO)',
  `req_di` enum('YO','NO') DEFAULT NULL COMMENT 'D.I. Clearance provided (YO/NO)',
  `req_diploma` enum('YO','NO') DEFAULT NULL COMMENT 'High School/College Diploma provided (YO/NO)',
  `req_neuro_drug` enum('YO','NO') DEFAULT NULL COMMENT 'Neuro & Drug test result provided (YO/NO)',
  `req_sec_license` enum('YO','NO') DEFAULT NULL COMMENT 'Sec.License Certificate from SOSIA provided (YO/NO)',
  `sec_lic_no` varchar(50) DEFAULT NULL COMMENT 'Security License Number for ID copy',
  `req_sec_lic_no` enum('YO','NO') DEFAULT NULL COMMENT 'Sec.Lic.No. ID copy provided (YO/NO)',
  `req_sss` enum('YO','NO') DEFAULT NULL COMMENT 'SSS No. ID copy provided (YO/NO)',
  `req_pagibig` enum('YO','NO') DEFAULT NULL COMMENT 'Pag-Ibig No. ID copy provided (YO/NO)',
  `req_philhealth` enum('YO','NO') DEFAULT NULL COMMENT 'PhilHealth No. ID copy provided (YO/NO)',
  `req_tin` enum('YO','NO') DEFAULT NULL COMMENT 'TIN No. ID copy provided (YO/NO)',
  `sworn_day` varchar(10) DEFAULT NULL COMMENT 'Sworn statement day',
  `sworn_month` varchar(50) DEFAULT NULL COMMENT 'Sworn statement month',
  `sworn_year` varchar(10) DEFAULT NULL COMMENT 'Sworn statement year',
  `tax_cert_no` varchar(100) DEFAULT NULL COMMENT 'Tax Certificate Number',
  `tax_cert_issued_at` varchar(200) DEFAULT NULL COMMENT 'Tax Certificate issued at location',
  `sworn_signature` varchar(200) DEFAULT NULL COMMENT 'Signature over printed name for sworn statement',
  `affiant_community` varchar(200) DEFAULT NULL COMMENT 'Affiant exhibited community',
  `doc_no` varchar(50) DEFAULT NULL COMMENT 'Document Number',
  `page_no` varchar(10) DEFAULT NULL COMMENT 'Page Number',
  `book_no` varchar(50) DEFAULT NULL COMMENT 'Book Number',
  `series_of` varchar(50) DEFAULT NULL COMMENT 'Series of',
  `status` enum('Active','Inactive','Terminated','Suspended') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `birthplace` varchar(150) DEFAULT NULL,
  `citizenship` varchar(80) DEFAULT NULL,
  `provincial_address` varchar(255) DEFAULT NULL,
  `special_skills` text DEFAULT NULL,
  `spouse_name` varchar(150) DEFAULT NULL,
  `spouse_age` int(11) DEFAULT NULL,
  `spouse_occupation` varchar(150) DEFAULT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `father_age` int(11) DEFAULT NULL,
  `father_occupation` varchar(150) DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `mother_age` int(11) DEFAULT NULL,
  `mother_occupation` varchar(150) DEFAULT NULL,
  `children_names` text DEFAULT NULL,
  `college_course` varchar(150) DEFAULT NULL,
  `college_school_name` varchar(200) DEFAULT NULL,
  `college_school_address` varchar(255) DEFAULT NULL,
  `college_years` varchar(15) DEFAULT NULL,
  `vocational_course` varchar(150) DEFAULT NULL,
  `vocational_school_name` varchar(200) DEFAULT NULL,
  `vocational_school_address` varchar(255) DEFAULT NULL,
  `vocational_years` varchar(15) DEFAULT NULL,
  `highschool_school_name` varchar(200) DEFAULT NULL,
  `highschool_school_address` varchar(255) DEFAULT NULL,
  `highschool_years` varchar(15) DEFAULT NULL,
  `elementary_school_name` varchar(200) DEFAULT NULL,
  `elementary_school_address` varchar(255) DEFAULT NULL,
  `elementary_years` varchar(15) DEFAULT NULL,
  `trainings_json` text DEFAULT NULL,
  `gov_exam_taken` tinyint(1) DEFAULT NULL,
  `gov_exam_json` text DEFAULT NULL,
  `employment_history_json` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_no` (`license_no`),
  KEY `idx_employee_no` (`employee_no`),
  KEY `idx_employee_type` (`employee_type`),
  KEY `idx_post` (`post`),
  KEY `idx_license_no` (`license_no`),
  KEY `idx_license_exp` (`license_exp_date`),
  KEY `idx_status` (`status`),
  KEY `idx_convicted` (`convicted`),
  KEY `idx_filed_case` (`filed_case`),
  KEY `idx_prohibited_drugs` (`prohibited_drugs`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,24324,'','USER','TEMP',NULL,'',NULL,NULL,NULL,'0000-00-00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2',NULL,NULL,'','2026-01-16 08:14:52','2026-01-16 08:14:52',25,'HR Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'uploads/employees/1.png'),(2,0,'','USER','TEMP',NULL,'',NULL,NULL,NULL,'0000-00-00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2',NULL,NULL,'','2026-01-16 08:17:15','2026-01-16 08:17:15',25,'HR Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'uploads/employees/2.jpg'),(3,24325,'','USER','TEMP',NULL,'',NULL,NULL,NULL,'0000-00-00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2',NULL,NULL,'','2026-01-16 08:19:27','2026-01-16 08:19:27',25,'HR Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'uploads/employees/3.png'),(4,23423,'SG','ASDFASDF','ASDFASDF',NULL,'',NULL,NULL,NULL,'0000-00-00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2',NULL,NULL,'Inactive','2026-01-16 08:27:15','2026-01-16 08:27:15',25,'HR Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'uploads/employees/4.png'),(5,23432,'','USER','TEMP',NULL,'',NULL,NULL,NULL,'0000-00-00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2',NULL,NULL,'','2026-01-16 08:28:13','2026-01-16 08:28:13',25,'HR Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'uploads/employees/5.png'),(6,24326,'','USER','TEMP',NULL,'',NULL,NULL,NULL,'0000-00-00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2',NULL,NULL,'','2026-01-16 08:31:30','2026-01-16 08:31:30',25,'HR Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL),(7,24327,'','USER','TEMP',NULL,'',NULL,NULL,NULL,'0000-00-00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2',NULL,NULL,'','2026-01-16 08:33:42','2026-01-16 08:33:42',25,'HR Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'uploads/employees/7.jpg');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `log_employee_changes` AFTER UPDATE ON `employees` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `update_post_filled_count_after_employee_update` AFTER UPDATE ON `employees` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        CALL UpdatePostFilledCount((SELECT id FROM posts WHERE post_title = NEW.post LIMIT 1));
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `hr_tasks`
--

DROP TABLE IF EXISTS `hr_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_number` (`task_number`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_category` (`category`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_tasks`
--

LOCK TABLES `hr_tasks` WRITE;
/*!40000 ALTER TABLE `hr_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `leave_balance_summary`
--

DROP TABLE IF EXISTS `leave_balance_summary`;
/*!50001 DROP VIEW IF EXISTS `leave_balance_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `leave_balance_summary` AS SELECT
 1 AS `employee_id`,
  1 AS `employee_name`,
  1 AS `employee_type`,
  1 AS `post`,
  1 AS `leave_type`,
  1 AS `year`,
  1 AS `total_entitlement`,
  1 AS `used_days`,
  1 AS `remaining_days` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_balances` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('vacation','sick','personal','emergency','maternity','paternity','bereavement') NOT NULL,
  `year` int(11) NOT NULL,
  `total_entitlement` int(11) DEFAULT 0,
  `used_days` int(11) DEFAULT 0,
  `remaining_days` int(11) GENERATED ALWAYS AS (`total_entitlement` - `used_days`) STORED,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_leave_year` (`employee_id`,`leave_type`,`year`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_leave_type` (`leave_type`),
  KEY `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
INSERT INTO `leave_balances` VALUES (1,1,'vacation',2024,15,5,10,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(2,1,'sick',2024,10,2,8,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(3,2,'vacation',2024,15,0,15,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(4,2,'sick',2024,10,3,7,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(5,3,'vacation',2024,15,0,15,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(6,3,'sick',2024,10,0,10,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(7,4,'vacation',2024,15,0,15,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(8,4,'sick',2024,10,1,9,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(9,5,'vacation',2024,20,0,20,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(10,5,'sick',2024,15,0,15,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(11,6,'vacation',2024,15,0,15,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(12,6,'sick',2024,10,0,10,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(13,7,'vacation',2024,15,0,15,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(14,7,'sick',2024,10,0,10,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(15,8,'vacation',2024,20,0,20,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(16,8,'sick',2024,15,0,15,'2025-12-06 08:39:02','2025-12-06 08:39:02');
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `post_statistics`
--

DROP TABLE IF EXISTS `post_statistics`;
/*!50001 DROP VIEW IF EXISTS `post_statistics`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `post_statistics` AS SELECT
 1 AS `id`,
  1 AS `post_title`,
  1 AS `location`,
  1 AS `employee_type`,
  1 AS `required_count`,
  1 AS `filled_count`,
  1 AS `available_positions`,
  1 AS `priority`,
  1 AS `status`,
  1 AS `created_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_code` (`post_code`),
  KEY `idx_employee_type` (`employee_type`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,'Security Guard - Mall','SG-MALL-001','Security','SG','SM Mall of Asia','Provide security services for mall operations','Valid Security Guard License, Physical fitness, Good communication skills','Patrol assigned areas, Monitor CCTV, Respond to incidents, Customer assistance',5,2,'High','Active','Rotating','8 hours','â‚±15,000 - â‚±18,000','SSS, PhilHealth, Pag-IBIG, 13th month pay','Security Supervisor','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02'),(2,'Lady Guard - Office Building','LG-OFFICE-001','Security','LG','BGC Office Tower','Provide security services for office building','Valid Lady Guard License, Professional appearance, Customer service skills','Access control, Visitor management, Emergency response, Building patrol',3,1,'Medium','Active','Day Shift','8 hours','â‚±16,000 - â‚±19,000','SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO','Security Manager','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02'),(3,'Security Officer - Headquarters','SO-HQ-001','Security','SO','Main Office','Supervise security operations and personnel','Security Officer License, Leadership skills, 3+ years experience','Team supervision, Security planning, Incident investigation, Training coordination',2,1,'Urgent','Active','Administrative','8 hours','â‚±25,000 - â‚±30,000','SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Car allowance','Security Director','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02'),(4,'Security Guard - Residential','SG-RES-001','Security','SG','Exclusive Subdivision','Provide security for residential community','Valid Security Guard License, Trustworthy, Community-oriented','Gate control, Community patrol, Resident assistance, Incident reporting',4,1,'Medium','Active','Night Shift','12 hours','â‚±14,000 - â‚±17,000','SSS, PhilHealth, Pag-IBIG, 13th month pay','Property Manager','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02'),(5,'Lady Guard - Hospital','LG-HOSP-001','Security','LG','Metro Hospital','Provide security services in hospital environment','Valid Lady Guard License, Medical knowledge preferred, Compassionate','Patient area security, Visitor screening, Emergency response, Medical escort',2,0,'High','Active','Rotating','8 hours','â‚±17,000 - â‚±20,000','SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Hazard pay','Hospital Security Chief','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02'),(6,'Security Officer - Field Operations','SO-FIELD-001','Security','SO','Various Locations','Supervise field security operations','Security Officer License, Field experience, Problem-solving skills','Field supervision, Site assessments, Client relations, Team coordination',1,1,'Medium','Active','Field Work','8 hours','â‚±22,000 - â‚±28,000','SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Field allowance','Operations Manager','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02'),(7,'sdafsadfasdf','sfd32432432','Security','SG','asdfsdfsdaf','asdfasdf','asdfasdf','asdfsadfds',1,0,'Medium','Active','Day','8 hours','23423432','asdfsdf','sadfasdf','2025-12-14','2025-12-14 08:34:48','2025-12-14 08:34:48'),(8,'asdfasdf','ssdf2332423','Administration','SG','asdfasdf','asdfsdf','sadfsdf','asdfsdfasdf',1,0,'Medium','Active','Day','8 hours','24234234','asdfsdfasd','asfdasd','2025-12-14','2025-12-14 09:14:40','2025-12-14 09:14:40');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_settings`
--

DROP TABLE IF EXISTS `security_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_settings` (
  `key` varchar(100) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_settings`
--

LOCK TABLES `security_settings` WRITE;
/*!40000 ALTER TABLE `security_settings` DISABLE KEYS */;
INSERT INTO `security_settings` VALUES ('password_expiry_days','90','2026-01-15 08:23:05'),('password_min_length','8','2026-01-15 07:17:22'),('password_require_special','0','2026-01-15 08:39:28');
/*!40000 ALTER TABLE `security_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_no` (`ticket_no`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ticket_no` (`ticket_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_replies`
--

DROP TABLE IF EXISTS `ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_replies`
--

LOCK TABLES `ticket_replies` WRITE;
/*!40000 ALTER TABLE `ticket_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_off_requests`
--

DROP TABLE IF EXISTS `time_off_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_off_requests`
--

LOCK TABLES `time_off_requests` WRITE;
/*!40000 ALTER TABLE `time_off_requests` DISABLE KEYS */;
INSERT INTO `time_off_requests` VALUES (1,1,'vacation','2024-02-01','2024-02-05',5,'Family vacation','approved',5,'2024-01-20 02:30:00',NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(2,2,'sick','2024-01-20','2024-01-22',3,'Flu symptoms','approved',5,'2024-01-19 06:15:00',NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(3,3,'personal','2024-02-10','2024-02-10',1,'Personal appointment','pending',NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(4,4,'emergency','2024-01-25','2024-01-25',1,'Family emergency','approved',5,'2024-01-24 08:45:00',NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02'),(5,5,'vacation','2024-03-15','2024-03-20',6,'Holiday break','pending',NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
/*!40000 ALTER TABLE `time_off_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'hr.admin','hr.admin@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Maria L. Santos','hr_admin','active',NULL,'Human Resources','0917-100-0001',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,NULL,'2025-12-01 02:11:35','2025-12-01 02:11:35'),(2,'hr.lead','hr.lead@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Angela M. Reyes','hr_admin','active',NULL,'Human Resources','0917-100-0002',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,1,'2025-12-01 02:11:35','2025-12-01 02:11:35'),(3,'hr.ops','hr.ops@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Carlos P. Dizon','hr_admin','active',NULL,'Human Resources','0917-100-0003',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,1,'2025-12-01 02:11:35','2025-12-01 02:11:35'),(4,'dev.lead','dev.lead@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Jacob R. Villanueva','developer','active',NULL,'IT/Development','0917-200-0001',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,1,'2025-12-01 02:11:35','2025-12-01 02:11:35'),(5,'dev.engineer','dev.engineer@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Lara S. Mendoza','developer','active',NULL,'IT/Development','0917-200-0002',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,1,'2025-12-01 02:11:35','2025-12-01 02:11:35'),(6,'dev.ops','dev.ops@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Noel T. Cruz','developer','active',NULL,'IT/Development','0917-200-0003',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,1,'2025-12-01 02:11:35','2025-12-01 02:11:35'),(25,'hradmin','hradmin@goldenz5.com','$2y$10$2Fp4cu96Oey7AQ87V/fWd.EVqmVzV5chLxspeoyzzSPooNUOXxYDq','HR Administrator','hr_admin','active',NULL,NULL,NULL,NULL,'2026-01-16 08:12:26','192.168.1.7',0,NULL,'2026-01-13 05:22:28',NULL,NULL,0,NULL,NULL,'2025-12-11 17:49:50','2026-01-16 08:12:26'),(30,'grey','greycruz00000000@gmail.com','$2y$10$7qyDoZ3GUP4okfOd0TGQpeDWUkPAdvBJTXDnTVqOXCoOxOu.z/Vui','aldrin','super_admin','active',NULL,'dikoalam','09563211331',NULL,'2026-01-16 08:10:18','192.168.1.7',0,NULL,'2026-01-15 05:31:24',NULL,NULL,0,NULL,25,'2026-01-13 08:36:54','2026-01-16 08:10:18'),(31,'amor','amor@gmail.com','$2y$10$evvPUIl.aoXr/icZ85PNH.zTj4wnx.TEzKxHqijLpO0NgoAw7OyAa','amor','hr_admin','active',NULL,'asdfjh','09562312321',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,30,'2026-01-13 09:58:43','2026-01-13 09:58:43'),(32,'ChristianAmor','christian5787264@gmail.com','$2y$10$4jtmtLYPqQBDYvKq3D9AouI3kPDYDJObT7mtYZ.PHY2j5rx64rqj.','christian amor','super_admin','active',NULL,'it','09613014462',NULL,'2026-01-15 08:20:37','192.168.1.23',0,NULL,'2026-01-15 08:18:54',NULL,'ZHLMOAXVWIX2DK4A',1,NULL,30,'2026-01-13 10:01:09','2026-01-15 08:20:37'),(0,'aaaaa','aa@gmail.com','$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6','alskdjfjklfdsa','hr_admin','active',NULL,'askldjf','2980374234',NULL,'2026-01-15 05:28:23','192.168.1.7',0,NULL,'2026-01-15 06:29:12','d95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552',NULL,0,NULL,30,'2026-01-15 03:07:15','2026-01-16 04:54:16'),(0,'zzzzz','zzz@gmail.com','$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6','salkjfksldaj','hr_admin','active',NULL,'salkjfdklsajd','213849089234',NULL,'2026-01-15 05:28:23','192.168.1.7',0,NULL,'2026-01-15 06:29:12','d95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552',NULL,0,NULL,30,'2026-01-15 03:08:25','2026-01-16 04:54:16'),(0,'bbbbaaa','bb@gmail.com','$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6','sadfsdfa','hr_admin','active',NULL,'sadfasfd','2893749823',NULL,'2026-01-15 05:28:23','192.168.1.7',0,NULL,'2026-01-15 06:29:12','d95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552',NULL,0,NULL,30,'2026-01-15 03:21:17','2026-01-16 04:54:16'),(0,'zazaza','zz@gmail.com','$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6','saddfsafdas','hr_admin','active',NULL,'asfsadf','12312321',NULL,'2026-01-15 05:28:23','192.168.1.7',0,NULL,'2026-01-15 06:29:12','d95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552',NULL,0,NULL,30,'2026-01-15 03:23:16','2026-01-16 04:54:16'),(0,'aldrin','aldrininocencio212527@gmail.com','$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6','aldrin','hr_admin','active',NULL,'IT','09563211331',NULL,'2026-01-15 05:28:23','192.168.1.7',0,NULL,'2026-01-15 06:29:12','d95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552',NULL,0,NULL,30,'2026-01-15 05:23:05','2026-01-16 04:54:16'),(0,'sssss','sss@gmail.com','$2y$10$V.Tmj4tp3ANqZvMY77yJBurZ1kBuJGCwf5hMdu.vXR1uJbkxioxXi','ssssss','hr_admin','active',NULL,'sss','23432432',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,30,'2026-01-16 04:34:07','2026-01-16 04:54:16');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'goldenz_hr'
--

--
-- Dumping routines for database 'goldenz_hr'
--

--
-- Final view structure for view `dtr_summary`
--

/*!50001 DROP VIEW IF EXISTS `dtr_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `dtr_summary` AS select `e`.`id` AS `employee_id`,concat(`e`.`surname`,', ',`e`.`first_name`) AS `employee_name`,`e`.`post` AS `post`,`d`.`entry_date` AS `entry_date`,`d`.`time_in` AS `time_in`,`d`.`time_out` AS `time_out`,`d`.`entry_type` AS `entry_type`,case when `d`.`time_in` is not null and `d`.`time_out` is not null then timestampdiff(HOUR,concat(`d`.`entry_date`,' ',`d`.`time_in`),concat(`d`.`entry_date`,' ',`d`.`time_out`)) else NULL end AS `hours_worked` from (`employees` `e` left join `dtr_entries` `d` on(`e`.`id` = `d`.`employee_id`)) where `d`.`entry_date` >= curdate() - interval 30 day */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `employee_details`
--

/*!50001 DROP VIEW IF EXISTS `employee_details`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `employee_details` AS select `e`.`id` AS `id`,`e`.`employee_no` AS `employee_no`,`e`.`employee_type` AS `employee_type`,concat(`e`.`surname`,', ',`e`.`first_name`,' ',coalesce(`e`.`middle_name`,'')) AS `full_name`,`e`.`post` AS `post`,`e`.`license_no` AS `license_no`,`e`.`license_exp_date` AS `license_exp_date`,`e`.`date_hired` AS `date_hired`,`e`.`status` AS `status`,`e`.`created_at` AS `created_at`,`e`.`updated_at` AS `updated_at`,case when `e`.`license_exp_date` < curdate() then 'Expired' when `e`.`license_exp_date` <= curdate() + interval 30 day then 'Expiring Soon' else 'Valid' end AS `license_status` from `employees` `e` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `leave_balance_summary`
--

/*!50001 DROP VIEW IF EXISTS `leave_balance_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `leave_balance_summary` AS select `e`.`id` AS `employee_id`,concat(`e`.`surname`,', ',`e`.`first_name`) AS `employee_name`,`e`.`employee_type` AS `employee_type`,`e`.`post` AS `post`,`lb`.`leave_type` AS `leave_type`,`lb`.`year` AS `year`,`lb`.`total_entitlement` AS `total_entitlement`,`lb`.`used_days` AS `used_days`,`lb`.`remaining_days` AS `remaining_days` from (`employees` `e` left join `leave_balances` `lb` on(`e`.`id` = `lb`.`employee_id`)) where `lb`.`year` = year(curdate()) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `post_statistics`
--

/*!50001 DROP VIEW IF EXISTS `post_statistics`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `post_statistics` AS select `p`.`id` AS `id`,`p`.`post_title` AS `post_title`,`p`.`location` AS `location`,`p`.`employee_type` AS `employee_type`,`p`.`required_count` AS `required_count`,`p`.`filled_count` AS `filled_count`,`p`.`required_count` - `p`.`filled_count` AS `available_positions`,`p`.`priority` AS `priority`,`p`.`status` AS `status`,`p`.`created_at` AS `created_at` from `posts` `p` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-16 16:36:26
