-- Golden Z-5 HR System Database Backup
-- Generated: 2026-01-20 16:16:23
-- Database: goldenz_hr

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


-- Table structure for table `audit_logs`
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `audit_logs_chk_1` CHECK (json_valid(`old_values`)),
  CONSTRAINT `audit_logs_chk_2` CHECK (json_valid(`new_values`))
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `audit_logs`
LOCK TABLES `audit_logs` WRITE;
INSERT INTO `audit_logs` VALUES ('1','1','INSERT','employees','1',NULL,'{\"surname\": \"ABAD\", \"first_name\": \"JOHN MARK\", \"employee_no\": 1, \"employee_type\": \"SG\"}','192.168.1.100','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36','2024-01-15 00:30:00');
INSERT INTO `audit_logs` VALUES ('2','1','UPDATE','employees','1','{\"status\": \"Active\"}','{\"status\": \"Active\"}','192.168.1.100','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36','2024-01-15 01:15:00');
INSERT INTO `audit_logs` VALUES ('3','2','INSERT','time_off_requests','1',NULL,'{\"start_date\": \"2024-02-01\", \"employee_id\": 1, \"request_type\": \"vacation\"}','192.168.1.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36','2024-01-15 02:00:00');
INSERT INTO `audit_logs` VALUES ('4','25','INSERT','employees','2574',NULL,'{\"employee_no\":\"23432\",\"first_name\":\"asdfsdfsdfsd\",\"surname\":\"sdfasdfsdfsdfasdfsdafsdf\",\"employee_type\":\"SG\",\"post\":\"Lady Guard - Hospital\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-12-13 04:52:11');
INSERT INTO `audit_logs` VALUES ('5','25','INSERT','employees','2867',NULL,'{\"employee_no\":\"43434\",\"first_name\":\"asdf\",\"surname\":\"sadf\",\"employee_type\":\"SG\",\"post\":\"Lady Guard - Hospital\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 06:13:18');
INSERT INTO `audit_logs` VALUES ('6','25','INSERT','employees','2924',NULL,'{\"employee_no\":\"12121\",\"first_name\":\"asdfsadf\",\"surname\":\"asfd\",\"employee_type\":\"SG\",\"post\":\"Lady Guard - Office Building\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 06:24:02');
INSERT INTO `audit_logs` VALUES ('7','25','INSERT','employees','3141',NULL,'{\"employee_no\":\"21342\",\"first_name\":\"dsafsdfsadf\",\"surname\":\"efsdafa\",\"employee_type\":\"SO\",\"post\":\"Security Officer - Headquarters\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 06:34:18');
INSERT INTO `audit_logs` VALUES ('8','25','INSERT','employees','3401',NULL,'{\"employee_no\":\"34232\",\"first_name\":\"sadfasfdasd\",\"surname\":\"sadfsf\",\"employee_type\":\"LG\",\"post\":\"Security Officer - Headquarters\",\"status\":\"Terminated\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 07:26:36');
INSERT INTO `audit_logs` VALUES ('9','25','INSERT','employees','3750',NULL,'{\"employee_no\":\"23423\",\"first_name\":\"SADFSADF\",\"surname\":\"ASDFSADF\",\"employee_type\":\"SG\",\"post\":\"LADY GUARD - OFFICE BUILDING\",\"status\":\"Terminated\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:15:05');
INSERT INTO `audit_logs` VALUES ('10','25','INSERT','employees','3783',NULL,'{\"employee_no\":\"24234\",\"first_name\":\"SDFASDFSFA\",\"surname\":\"SAFASDF\",\"employee_type\":\"SG\",\"post\":\"LADY GUARD - OFFICE BUILDING\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:17:00');
INSERT INTO `audit_logs` VALUES ('11','1','UPDATE','employees','3817','{\"surname\": \"SADFSADFASD\", \"first_name\": \"FASDFSADFS\", \"status\": \"Inactive\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}','{\"surname\": \"SADFSADFASD\", \"first_name\": \"FASDFSADFS\", \"status\": \"Inactive\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}',NULL,NULL,'2025-12-14 08:20:48');
INSERT INTO `audit_logs` VALUES ('12','25','INSERT','employees','3817',NULL,'{\"employee_no\":\"23423\",\"first_name\":\"FASDFSADFS\",\"surname\":\"SADFSADFASD\",\"employee_type\":\"SG\",\"post\":\"SECURITY GUARD - RESIDENTIAL\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:20:48');
INSERT INTO `audit_logs` VALUES ('13','1','UPDATE','employees','3866','{\"surname\": \"SDFASDFASDF\", \"first_name\": \"SADFASFASFSADF\", \"status\": \"Inactive\", \"post\": \"LADY GUARD - OFFICE BUILDING\"}','{\"surname\": \"SDFASDFASDF\", \"first_name\": \"SADFASFASFSADF\", \"status\": \"Inactive\", \"post\": \"LADY GUARD - OFFICE BUILDING\"}',NULL,NULL,'2025-12-14 08:27:18');
INSERT INTO `audit_logs` VALUES ('14','25','INSERT','employees','3866',NULL,'{\"employee_no\":\"24324\",\"first_name\":\"SADFASFASFSADF\",\"surname\":\"SDFASDFASDF\",\"employee_type\":\"SG\",\"post\":\"LADY GUARD - OFFICE BUILDING\",\"status\":\"Inactive\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:27:18');
INSERT INTO `audit_logs` VALUES ('15','1','UPDATE','employees','3907','{\"surname\": \"ASFSDFSADFASDF\", \"first_name\": \"ASDFSDFSD\", \"status\": \"Suspended\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}','{\"surname\": \"ASFSDFSADFASDF\", \"first_name\": \"ASDFSDFSD\", \"status\": \"Suspended\", \"post\": \"SECURITY GUARD - RESIDENTIAL\"}',NULL,NULL,'2025-12-14 08:30:41');
INSERT INTO `audit_logs` VALUES ('16','25','INSERT','employees','3907',NULL,'{\"employee_no\":\"23432\",\"first_name\":\"ASDFSDFSD\",\"surname\":\"ASFSDFSADFASDF\",\"employee_type\":\"LG\",\"post\":\"SECURITY GUARD - RESIDENTIAL\",\"status\":\"Suspended\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:30:42');
INSERT INTO `audit_logs` VALUES ('17','1','UPDATE','employees','3996','{\"surname\": \"ASDFASDFSADFS\", \"first_name\": \"SDFSADFASDFSDAF\", \"status\": \"Suspended\", \"post\": \"SECURITY OFFICER - FIELD OPERATIONS\"}','{\"surname\": \"ASDFASDFSADFS\", \"first_name\": \"SDFSADFASDFSDAF\", \"status\": \"Suspended\", \"post\": \"SECURITY OFFICER - FIELD OPERATIONS\"}',NULL,NULL,'2025-12-14 08:33:42');
INSERT INTO `audit_logs` VALUES ('18','25','INSERT','employees','3996',NULL,'{\"employee_no\":\"23423\",\"first_name\":\"SDFSADFASDFSDAF\",\"surname\":\"ASDFASDFSADFS\",\"employee_type\":\"LG\",\"post\":\"SECURITY OFFICER - FIELD OPERATIONS\",\"status\":\"Suspended\",\"created_by\":\"HR Administrator\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-14 08:33:42');
INSERT INTO `audit_logs` VALUES ('19','25','INSERT','employees','12729',NULL,'{\"employee_no\":\"34324\",\"first_name\":\"HEHE\",\"surname\":\"HAHA\",\"employee_type\":\"SG\",\"post\":\"UNASSIGNED\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-12 07:30:43');
INSERT INTO `audit_logs` VALUES ('20','25','INSERT','employees','13042',NULL,'{\"employee_no\":\"24324\",\"first_name\":\"CHRISTIAN\",\"surname\":\"AMOR\",\"employee_type\":\"SG\",\"post\":\"KAHIT SAN\",\"status\":\"Active\",\"created_by\":\"HR Administrator\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-12 07:47:54');
INSERT INTO `audit_logs` VALUES ('21','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 13:22:12\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 05:22:12');
INSERT INTO `audit_logs` VALUES ('22','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 13:22:17\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 05:22:17');
INSERT INTO `audit_logs` VALUES ('23','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 13:45:41\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 05:45:41');
INSERT INTO `audit_logs` VALUES ('24','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 13:49:45\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 05:49:45');
INSERT INTO `audit_logs` VALUES ('25','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 16:02:37\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:02:37');
INSERT INTO `audit_logs` VALUES ('26','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 16:06:17\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:06:17');
INSERT INTO `audit_logs` VALUES ('27','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 16:08:29\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:08:29');
INSERT INTO `audit_logs` VALUES ('28','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 16:08:36\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:08:36');
INSERT INTO `audit_logs` VALUES ('29','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 16:09:04\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:09:04');
INSERT INTO `audit_logs` VALUES ('30','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 16:10:03\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:10:03');
INSERT INTO `audit_logs` VALUES ('31','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 16:12:39\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:12:39');
INSERT INTO `audit_logs` VALUES ('32','25','USER_CREATED','users','30',NULL,'{\"username\":\"grey\",\"email\":\"greycruz00000000@gmail.com\",\"name\":\"aldrin\",\"role\":\"hr_admin\",\"status\":\"active\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:36:54');
INSERT INTO `audit_logs` VALUES ('33','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 16:37:46\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:37:46');
INSERT INTO `audit_logs` VALUES ('34','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 16:38:08\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:38:08');
INSERT INTO `audit_logs` VALUES ('35','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 16:42:01\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:42:01');
INSERT INTO `audit_logs` VALUES ('36','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 16:52:06\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:52:06');
INSERT INTO `audit_logs` VALUES ('37','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 16:52:12\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:52:12');
INSERT INTO `audit_logs` VALUES ('38','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 16:52:42\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 08:52:42');
INSERT INTO `audit_logs` VALUES ('39','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 17:10:10\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:10:10');
INSERT INTO `audit_logs` VALUES ('40','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 17:16:42\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:16:42');
INSERT INTO `audit_logs` VALUES ('41','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 17:17:12\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:17:12');
INSERT INTO `audit_logs` VALUES ('42','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 17:23:58\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:23:58');
INSERT INTO `audit_logs` VALUES ('43','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 17:42:17\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:42:17');
INSERT INTO `audit_logs` VALUES ('44','30','USER_ROLE_UPDATED','users','25','{\"role\":\"hr_admin\"}','{\"role\":\"super_admin\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:45:59');
INSERT INTO `audit_logs` VALUES ('45','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 17:46:06\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:46:06');
INSERT INTO `audit_logs` VALUES ('46','25','USER_ROLE_UPDATED','users','25','{\"role\":\"super_admin\"}','{\"role\":\"hr_admin\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:46:29');
INSERT INTO `audit_logs` VALUES ('47','25','USER_ROLE_UPDATED','users','25','{\"role\":\"hr_admin\"}','{\"role\":\"super_admin\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:46:34');
INSERT INTO `audit_logs` VALUES ('48','25','USER_ROLE_UPDATED','users','25','{\"role\":\"super_admin\"}','{\"role\":\"hr_admin\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:46:36');
INSERT INTO `audit_logs` VALUES ('49','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-13 17:46:51\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:46:51');
INSERT INTO `audit_logs` VALUES ('50','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 17:47:04\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:47:04');
INSERT INTO `audit_logs` VALUES ('51','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-13 17:49:29\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:49:29');
INSERT INTO `audit_logs` VALUES ('52','30','USER_CREATED','users','31',NULL,'{\"username\":\"amor\",\"email\":\"amor@gmail.com\",\"name\":\"amor\",\"role\":\"hr_admin\",\"status\":\"active\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 09:58:43');
INSERT INTO `audit_logs` VALUES ('53','30','USER_CREATED','users','32',NULL,'{\"username\":\"ChristianAmor\",\"email\":\"christian5787264@gmail.com\",\"name\":\"christian amor\",\"role\":\"super_admin\",\"status\":\"active\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 10:01:09');
INSERT INTO `audit_logs` VALUES ('54','32','LOGIN_ATTEMPT','users','32',NULL,'{\"login_time\":\"2026-01-13 18:01:59\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 10:01:59');
INSERT INTO `audit_logs` VALUES ('55','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-14 07:42:03\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2026-01-13 23:42:03');
INSERT INTO `audit_logs` VALUES ('56','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-16 16:12:26\"}','192.168.1.7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-16 08:12:26');
INSERT INTO `audit_logs` VALUES ('57','1','UPDATE','employees','14910','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:12:54');
INSERT INTO `audit_logs` VALUES ('58','1','UPDATE','employees','14910','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:12:54');
INSERT INTO `audit_logs` VALUES ('59','1','UPDATE','employees','1','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:14:52');
INSERT INTO `audit_logs` VALUES ('60','1','UPDATE','employees','1','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:14:52');
INSERT INTO `audit_logs` VALUES ('61','1','UPDATE','employees','2','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:17:15');
INSERT INTO `audit_logs` VALUES ('62','1','UPDATE','employees','2','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}','{\"surname\": \"USER\", \"first_name\": \"TEMP\", \"status\": \"\", \"post\": \"\"}',NULL,NULL,'2026-01-16 08:17:15');
INSERT INTO `audit_logs` VALUES ('63','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-20 09:34:45\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 01:34:45');
INSERT INTO `audit_logs` VALUES ('64','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-20 10:12:51\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 02:12:51');
INSERT INTO `audit_logs` VALUES ('65','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-20 10:19:50\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 02:19:50');
INSERT INTO `audit_logs` VALUES ('66','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-20 10:27:29\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 02:27:29');
INSERT INTO `audit_logs` VALUES ('67','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-20 10:27:51\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 02:27:51');
INSERT INTO `audit_logs` VALUES ('68','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-20 10:31:00\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 02:31:00');
INSERT INTO `audit_logs` VALUES ('69','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-20 11:19:29\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 03:19:29');
INSERT INTO `audit_logs` VALUES ('70','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-20 11:42:43\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 03:42:43');
INSERT INTO `audit_logs` VALUES ('71','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-20 14:21:11\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 06:21:11');
INSERT INTO `audit_logs` VALUES ('72','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-20 14:27:20\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 06:27:20');
INSERT INTO `audit_logs` VALUES ('73','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-20 14:32:36\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 06:32:36');
INSERT INTO `audit_logs` VALUES ('74','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-20 14:35:55\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 06:35:55');
INSERT INTO `audit_logs` VALUES ('75','30','LOGIN_ATTEMPT','users','30',NULL,'{\"login_time\":\"2026-01-20 14:39:57\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 06:39:57');
INSERT INTO `audit_logs` VALUES ('76','25','LOGIN_ATTEMPT','users','25',NULL,'{\"login_time\":\"2026-01-20 14:40:16\"}','172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-20 06:40:16');
UNLOCK TABLES;


-- Table structure for table `backup_history`
DROP TABLE IF EXISTS `backup_history`;
CREATE TABLE `backup_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filepath` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `backup_history`
LOCK TABLES `backup_history` WRITE;
INSERT INTO `backup_history` VALUES ('1','backup_goldenz_hr_2026-01-20_161602.sql','storage/backups/backup_goldenz_hr_2026-01-20_161602.sql','67292','2026-01-20 08:16:02');
UNLOCK TABLES;


-- Table structure for table `dtr_entries`
DROP TABLE IF EXISTS `dtr_entries`;
CREATE TABLE `dtr_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `entry_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `entry_type` enum('time-in','time-out','break','overtime') COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_date` (`employee_id`,`entry_date`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_entry_date` (`entry_date`),
  KEY `idx_entry_type` (`entry_type`),
  CONSTRAINT `dtr_entries_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `dtr_summary`
DROP TABLE IF EXISTS `dtr_summary`;

-- Table structure for table `employee_alerts`
DROP TABLE IF EXISTS `employee_alerts`;
CREATE TABLE `employee_alerts` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `alert_type` enum('license_expiry','document_expiry','missing_documents','contract_expiry','training_due','medical_expiry','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `alert_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `status` enum('active','acknowledged','resolved','dismissed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `acknowledged_by` int DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `employee_alerts`
LOCK TABLES `employee_alerts` WRITE;
INSERT INTO `employee_alerts` VALUES ('1','1','license_expiry','Security License Expiring Soon','Security guard license (R4B-202309000367) will expire in 30 days. Please renew before expiration.','2024-01-15','2028-09-14','medium','active','5',NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `employee_alerts` VALUES ('2','2','license_expiry','Security License Expiring Soon','Lady guard license (NCR-202411000339) will expire in 45 days. Please renew before expiration.','2024-01-15','2029-11-07','medium','active','5',NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `employee_alerts` VALUES ('3','3','training_due','Training Required','RLM training is due. Please complete required training before expiration.','2024-01-15','2025-04-05','high','active','5',NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `employee_alerts` VALUES ('4','4','document_expiry','Medical Certificate Expiring','Medical certificate will expire soon. Please renew for continued employment.','2024-01-15','2024-02-15','urgent','active','5',NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
UNLOCK TABLES;


-- Table structure for table `employee_checklist`
DROP TABLE IF EXISTS `employee_checklist`;
CREATE TABLE `employee_checklist` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `item_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `completed` tinyint(1) DEFAULT '0',
  `completed_by` int DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_item` (`employee_id`,`item_key`),
  KEY `completed_by` (`completed_by`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_item_key` (`item_key`),
  KEY `idx_completed` (`completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table structure for table `employee_details`
DROP TABLE IF EXISTS `employee_details`;
-- Dumping data for table `employee_details`
LOCK TABLES `employee_details` WRITE;
INSERT INTO `employee_details` VALUES ('1','24324','','USER, TEMP ','',NULL,NULL,'0000-00-00','','2026-01-16 08:14:52','2026-01-16 08:14:52','Valid');
INSERT INTO `employee_details` VALUES ('2','0','','USER, TEMP ','',NULL,NULL,'0000-00-00','','2026-01-16 08:17:15','2026-01-16 08:17:15','Valid');
UNLOCK TABLES;


-- Table structure for table `employees`
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_no` int NOT NULL,
  `employee_type` enum('SG','LG','SO') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'SG = Security Guard, LG = Lady Guard, SO = Security Officer',
  `surname` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `middle_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `post` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Assignment/Post',
  `license_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `license_exp_date` date DEFAULT NULL,
  `rlm_exp` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'RLM = Renewal of License/Membership',
  `date_hired` date NOT NULL,
  `cp_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Contact Phone Number',
  `sss_no` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Social Security System Number',
  `pagibig_no` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'PAG-IBIG Fund Number',
  `tin_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tax Identification Number',
  `philhealth_no` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'PhilHealth Number',
  `birth_date` date DEFAULT NULL,
  `height` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `contact_person` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relationship` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_person_address` text COLLATE utf8mb4_general_ci,
  `contact_person_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `blood_type` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `religion` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vacancy_source` text COLLATE utf8mb4_general_ci COMMENT 'How did you know of the vacancy (JSON array: Ads, Walk-in, Referral)',
  `referral_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Referral name if vacancy source is Referral',
  `knows_agency_person` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Do you know anyone from the agency?',
  `agency_person_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Name and relationship with agency person',
  `physical_defect` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Do you have any physical defects or chronic ailments?',
  `physical_defect_specify` text COLLATE utf8mb4_general_ci COMMENT 'Specify physical defects if yes',
  `drives` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Do you drive?',
  `drivers_license_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Driver license number',
  `drivers_license_exp` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Driver license expiration date',
  `drinks_alcohol` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Do you drink alcoholic beverages?',
  `alcohol_frequency` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'How frequent do you drink alcohol?',
  `prohibited_drugs` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Are you taking prohibited drugs?',
  `security_guard_experience` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'How long have you worked as a Security Guard?',
  `convicted` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Have you ever been convicted of any offense?',
  `conviction_details` text COLLATE utf8mb4_general_ci COMMENT 'Specify conviction details if yes',
  `filed_case` enum('Yes','No') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Have you filed any criminal/civil case against previous employer?',
  `case_specify` text COLLATE utf8mb4_general_ci COMMENT 'Specify case details if yes',
  `action_after_termination` text COLLATE utf8mb4_general_ci COMMENT 'What was your action after termination?',
  `signature_1` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen signature line 1',
  `signature_2` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen signature line 2',
  `signature_3` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen signature line 3',
  `initial_1` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen initial 1 (Pinakiling Pirma)',
  `initial_2` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen initial 2 (Pinakiling Pirma)',
  `initial_3` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Specimen initial 3 (Pinakiling Pirma)',
  `fingerprint_right_thumb` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Right thumb fingerprint file path',
  `fingerprint_right_index` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Right index finger fingerprint file path',
  `fingerprint_right_middle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Right middle finger fingerprint file path',
  `fingerprint_right_ring` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Right ring finger fingerprint file path',
  `fingerprint_right_little` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Right little finger fingerprint file path',
  `fingerprint_left_thumb` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Left thumb fingerprint file path',
  `fingerprint_left_index` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Left index finger fingerprint file path',
  `fingerprint_left_middle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Left middle finger fingerprint file path',
  `fingerprint_left_ring` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Left ring finger fingerprint file path',
  `fingerprint_left_little` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Left little finger fingerprint file path',
  `requirements_signature` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Signature over printed name for requirements section',
  `req_2x2` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '2x2 photos provided (YO/NO)',
  `req_birth_cert` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'NSO/Birth Certificate provided (YO/NO)',
  `req_barangay` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Barangay Clearance provided (YO/NO)',
  `req_police` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Police Clearance provided (YO/NO)',
  `req_nbi` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'NBI Clearance provided (YO/NO)',
  `req_di` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'D.I. Clearance provided (YO/NO)',
  `req_diploma` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'High School/College Diploma provided (YO/NO)',
  `req_neuro_drug` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Neuro & Drug test result provided (YO/NO)',
  `req_sec_license` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sec.License Certificate from SOSIA provided (YO/NO)',
  `sec_lic_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Security License Number for ID copy',
  `req_sec_lic_no` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sec.Lic.No. ID copy provided (YO/NO)',
  `req_sss` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'SSS No. ID copy provided (YO/NO)',
  `req_pagibig` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Pag-Ibig No. ID copy provided (YO/NO)',
  `req_philhealth` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'PhilHealth No. ID copy provided (YO/NO)',
  `req_tin` enum('YO','NO') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'TIN No. ID copy provided (YO/NO)',
  `sworn_day` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sworn statement day',
  `sworn_month` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sworn statement month',
  `sworn_year` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Sworn statement year',
  `tax_cert_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tax Certificate Number',
  `tax_cert_issued_at` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tax Certificate issued at location',
  `sworn_signature` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Signature over printed name for sworn statement',
  `affiant_community` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Affiant exhibited community',
  `doc_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Document Number',
  `page_no` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Page Number',
  `book_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Book Number',
  `series_of` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Series of',
  `status` enum('Active','Inactive','Terminated','Suspended') COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `created_by_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `civil_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `age` int DEFAULT NULL,
  `birthplace` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `citizenship` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `provincial_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `special_skills` text COLLATE utf8mb4_general_ci,
  `spouse_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `spouse_age` int DEFAULT NULL,
  `spouse_occupation` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `father_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `father_age` int DEFAULT NULL,
  `father_occupation` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_age` int DEFAULT NULL,
  `mother_occupation` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `children_names` text COLLATE utf8mb4_general_ci,
  `college_course` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `college_school_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `college_school_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `college_years` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vocational_course` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vocational_school_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vocational_school_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vocational_years` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `highschool_school_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `highschool_school_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `highschool_years` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `elementary_school_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `elementary_school_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `elementary_years` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `trainings_json` text COLLATE utf8mb4_general_ci,
  `gov_exam_taken` tinyint(1) DEFAULT NULL,
  `gov_exam_json` text COLLATE utf8mb4_general_ci,
  `employment_history_json` text COLLATE utf8mb4_general_ci,
  `profile_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `employees`
LOCK TABLES `employees` WRITE;
INSERT INTO `employees` VALUES ('1','24324','','USER','TEMP',NULL,'',NULL,NULL,NULL,'0000-00-00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2',NULL,NULL,'','2026-01-16 08:14:52','2026-01-16 08:14:52','25','HR Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0',NULL,NULL,'uploads/employees/1.png');
INSERT INTO `employees` VALUES ('2','0','','USER','TEMP',NULL,'',NULL,NULL,NULL,'0000-00-00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2',NULL,NULL,'','2026-01-16 08:17:15','2026-01-16 08:17:15','25','HR Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0',NULL,NULL,'uploads/employees/2.jpg');
UNLOCK TABLES;


-- Table structure for table `hr_tasks`
DROP TABLE IF EXISTS `hr_tasks`;
CREATE TABLE `hr_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `task_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` enum('Employee Record','License','Leave Request','Clearance','Cash Bond','Other') COLLATE utf8mb4_unicode_ci DEFAULT 'Other',
  `assigned_by` int DEFAULT NULL,
  `assigned_by_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `urgency_level` enum('normal','important','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `location_page` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Where can it be found',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Notes created by the person who alerted',
  `status` enum('pending','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `assigned_to` int DEFAULT NULL COMMENT 'HR Admin user ID',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_number` (`task_number`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_category` (`category`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table structure for table `leave_balance_summary`
DROP TABLE IF EXISTS `leave_balance_summary`;

-- Table structure for table `leave_balances`
DROP TABLE IF EXISTS `leave_balances`;
CREATE TABLE `leave_balances` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `leave_type` enum('vacation','sick','personal','emergency','maternity','paternity','bereavement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `total_entitlement` int DEFAULT '0',
  `used_days` int DEFAULT '0',
  `remaining_days` int GENERATED ALWAYS AS ((`total_entitlement` - `used_days`)) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_leave_year` (`employee_id`,`leave_type`,`year`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_leave_type` (`leave_type`),
  KEY `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `leave_balances`
LOCK TABLES `leave_balances` WRITE;
INSERT INTO `leave_balances` VALUES ('1','1','vacation','2024','15','5','10','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('2','1','sick','2024','10','2','8','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('3','2','vacation','2024','15','0','15','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('4','2','sick','2024','10','3','7','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('5','3','vacation','2024','15','0','15','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('6','3','sick','2024','10','0','10','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('7','4','vacation','2024','15','0','15','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('8','4','sick','2024','10','1','9','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('9','5','vacation','2024','20','0','20','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('10','5','sick','2024','15','0','15','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('11','6','vacation','2024','15','0','15','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('12','6','sick','2024','10','0','10','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('13','7','vacation','2024','15','0','15','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('14','7','sick','2024','10','0','10','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('15','8','vacation','2024','20','0','20','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `leave_balances` VALUES ('16','8','sick','2024','15','0','15','2025-12-06 08:39:02','2025-12-06 08:39:02');
UNLOCK TABLES;


-- Table structure for table `post_statistics`
DROP TABLE IF EXISTS `post_statistics`;
-- Dumping data for table `post_statistics`
LOCK TABLES `post_statistics` WRITE;
INSERT INTO `post_statistics` VALUES ('1','Security Guard - Mall','SM Mall of Asia','SG','5','2','3','High','Active','2025-12-06 08:39:02');
INSERT INTO `post_statistics` VALUES ('2','Lady Guard - Office Building','BGC Office Tower','LG','3','1','2','Medium','Active','2025-12-06 08:39:02');
INSERT INTO `post_statistics` VALUES ('3','Security Officer - Headquarters','Main Office','SO','2','1','1','Urgent','Active','2025-12-06 08:39:02');
INSERT INTO `post_statistics` VALUES ('4','Security Guard - Residential','Exclusive Subdivision','SG','4','1','3','Medium','Active','2025-12-06 08:39:02');
INSERT INTO `post_statistics` VALUES ('5','Lady Guard - Hospital','Metro Hospital','LG','2','0','2','High','Active','2025-12-06 08:39:02');
INSERT INTO `post_statistics` VALUES ('6','Security Officer - Field Operations','Various Locations','SO','1','1','0','Medium','Active','2025-12-06 08:39:02');
INSERT INTO `post_statistics` VALUES ('7','sdafsadfasdf','asdfsdfsdaf','SG','1','0','1','Medium','Active','2025-12-14 08:34:48');
INSERT INTO `post_statistics` VALUES ('8','asdfasdf','asdfasdf','SG','1','0','1','Medium','Active','2025-12-14 09:14:40');
UNLOCK TABLES;


-- Table structure for table `posts`
DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` int NOT NULL,
  `post_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_type` enum('SG','LG','SO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `requirements` text COLLATE utf8mb4_unicode_ci,
  `responsibilities` text COLLATE utf8mb4_unicode_ci,
  `required_count` int DEFAULT '1',
  `filled_count` int DEFAULT '0',
  `priority` enum('Low','Medium','High','Urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'Medium',
  `status` enum('Active','Inactive','Closed') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `shift_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_hours` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary_range` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `benefits` text COLLATE utf8mb4_unicode_ci,
  `reporting_to` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_code` (`post_code`),
  KEY `idx_employee_type` (`employee_type`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `posts`
LOCK TABLES `posts` WRITE;
INSERT INTO `posts` VALUES ('1','Security Guard - Mall','SG-MALL-001','Security','SG','SM Mall of Asia','Provide security services for mall operations','Valid Security Guard License, Physical fitness, Good communication skills','Patrol assigned areas, Monitor CCTV, Respond to incidents, Customer assistance','5','2','High','Active','Rotating','8 hours','₱15,000 - ₱18,000','SSS, PhilHealth, Pag-IBIG, 13th month pay','Security Supervisor','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `posts` VALUES ('2','Lady Guard - Office Building','LG-OFFICE-001','Security','LG','BGC Office Tower','Provide security services for office building','Valid Lady Guard License, Professional appearance, Customer service skills','Access control, Visitor management, Emergency response, Building patrol','3','1','Medium','Active','Day Shift','8 hours','₱16,000 - ₱19,000','SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO','Security Manager','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `posts` VALUES ('3','Security Officer - Headquarters','SO-HQ-001','Security','SO','Main Office','Supervise security operations and personnel','Security Officer License, Leadership skills, 3+ years experience','Team supervision, Security planning, Incident investigation, Training coordination','2','1','Urgent','Active','Administrative','8 hours','₱25,000 - ₱30,000','SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Car allowance','Security Director','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `posts` VALUES ('4','Security Guard - Residential','SG-RES-001','Security','SG','Exclusive Subdivision','Provide security for residential community','Valid Security Guard License, Trustworthy, Community-oriented','Gate control, Community patrol, Resident assistance, Incident reporting','4','1','Medium','Active','Night Shift','12 hours','₱14,000 - ₱17,000','SSS, PhilHealth, Pag-IBIG, 13th month pay','Property Manager','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `posts` VALUES ('5','Lady Guard - Hospital','LG-HOSP-001','Security','LG','Metro Hospital','Provide security services in hospital environment','Valid Lady Guard License, Medical knowledge preferred, Compassionate','Patient area security, Visitor screening, Emergency response, Medical escort','2','0','High','Active','Rotating','8 hours','₱17,000 - ₱20,000','SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Hazard pay','Hospital Security Chief','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `posts` VALUES ('6','Security Officer - Field Operations','SO-FIELD-001','Security','SO','Various Locations','Supervise field security operations','Security Officer License, Field experience, Problem-solving skills','Field supervision, Site assessments, Client relations, Team coordination','1','1','Medium','Active','Field Work','8 hours','₱22,000 - ₱28,000','SSS, PhilHealth, Pag-IBIG, 13th month pay, HMO, Field allowance','Operations Manager','2024-12-31','2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `posts` VALUES ('7','sdafsadfasdf','sfd32432432','Security','SG','asdfsdfsdaf','asdfasdf','asdfasdf','asdfsadfds','1','0','Medium','Active','Day','8 hours','23423432','asdfsdf','sadfasdf','2025-12-14','2025-12-14 08:34:48','2025-12-14 08:34:48');
INSERT INTO `posts` VALUES ('8','asdfasdf','ssdf2332423','Administration','SG','asdfasdf','asdfsdf','sadfsdf','asdfsdfasdf','1','0','Medium','Active','Day','8 hours','24234234','asdfsdfasd','asfdasd','2025-12-14','2025-12-14 09:14:40','2025-12-14 09:14:40');
UNLOCK TABLES;


-- Table structure for table `security_settings`
DROP TABLE IF EXISTS `security_settings`;
CREATE TABLE `security_settings` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `security_settings`
LOCK TABLES `security_settings` WRITE;
INSERT INTO `security_settings` VALUES ('password_expiry_days','90','2026-01-15 08:23:05');
INSERT INTO `security_settings` VALUES ('password_min_length','8','2026-01-15 07:17:22');
INSERT INTO `security_settings` VALUES ('password_require_special','0','2026-01-15 08:39:28');
UNLOCK TABLES;


-- Table structure for table `support_tickets`
DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_no` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `user_email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_role` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` enum('system_issue','access_request','data_issue','feature_request','general_inquiry','bug_report') COLLATE utf8mb4_general_ci DEFAULT 'general_inquiry',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_general_ci DEFAULT 'medium',
  `subject` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('open','in_progress','pending_user','resolved','closed') COLLATE utf8mb4_general_ci DEFAULT 'open',
  `assigned_to` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_no` (`ticket_no`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ticket_no` (`ticket_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `ticket_replies`
DROP TABLE IF EXISTS `ticket_replies`;
CREATE TABLE `ticket_replies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `user_role` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_internal` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `time_off_requests`
DROP TABLE IF EXISTS `time_off_requests`;
CREATE TABLE `time_off_requests` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `request_type` enum('vacation','sick','personal','emergency','maternity','paternity','bereavement','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `time_off_requests`
LOCK TABLES `time_off_requests` WRITE;
INSERT INTO `time_off_requests` VALUES ('1','1','vacation','2024-02-01','2024-02-05','5','Family vacation','approved','5','2024-01-20 02:30:00',NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `time_off_requests` VALUES ('2','2','sick','2024-01-20','2024-01-22','3','Flu symptoms','approved','5','2024-01-19 06:15:00',NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `time_off_requests` VALUES ('3','3','personal','2024-02-10','2024-02-10','1','Personal appointment','pending',NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `time_off_requests` VALUES ('4','4','emergency','2024-01-25','2024-01-25','1','Family emergency','approved','5','2024-01-24 08:45:00',NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
INSERT INTO `time_off_requests` VALUES ('5','5','vacation','2024-03-15','2024-03-20','6','Holiday break','pending',NULL,NULL,NULL,'2025-12-06 08:39:02','2025-12-06 08:39:02');
UNLOCK TABLES;


-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','hr_admin','hr','admin','accounting','operation','logistics','employee','developer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hr_admin',
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `employee_id` int DEFAULT NULL COMMENT 'Link to employees table if user is an employee',
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Profile picture path',
  `last_login` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failed_login_attempts` int DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL COMMENT 'Account lockout until this timestamp',
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_secret` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL COMMENT 'User who created this account',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users`
LOCK TABLES `users` WRITE;
INSERT INTO `users` VALUES ('1','hr.admin','hr.admin@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Maria L. Santos','hr_admin','active',NULL,'Human Resources','0917-100-0001',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,'0',NULL,NULL,'2025-12-01 02:11:35','2025-12-01 02:11:35');
INSERT INTO `users` VALUES ('2','hr.lead','hr.lead@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Angela M. Reyes','hr_admin','active',NULL,'Human Resources','0917-100-0002',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,'0',NULL,'1','2025-12-01 02:11:35','2025-12-01 02:11:35');
INSERT INTO `users` VALUES ('3','hr.ops','hr.ops@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Carlos P. Dizon','hr_admin','active',NULL,'Human Resources','0917-100-0003',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,'0',NULL,'1','2025-12-01 02:11:35','2025-12-01 02:11:35');
INSERT INTO `users` VALUES ('4','dev.lead','dev.lead@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Jacob R. Villanueva','developer','active',NULL,'IT/Development','0917-200-0001',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,'0',NULL,'1','2025-12-01 02:11:35','2025-12-01 02:11:35');
INSERT INTO `users` VALUES ('5','dev.engineer','dev.engineer@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Lara S. Mendoza','developer','active',NULL,'IT/Development','0917-200-0002',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,'0',NULL,'1','2025-12-01 02:11:35','2025-12-01 02:11:35');
INSERT INTO `users` VALUES ('6','dev.ops','dev.ops@goldenz5.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Noel T. Cruz','developer','active',NULL,'IT/Development','0917-200-0003',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,'0',NULL,'1','2025-12-01 02:11:35','2025-12-01 02:11:35');
INSERT INTO `users` VALUES ('25','hradmin','hradmin@goldenz5.com','$2y$10$2Fp4cu96Oey7AQ87V/fWd.EVqmVzV5chLxspeoyzzSPooNUOXxYDq','HR Administrator','hr_admin','active',NULL,NULL,NULL,NULL,'2026-01-20 06:40:16','172.18.0.1','0',NULL,'2026-01-13 05:22:28',NULL,NULL,'0',NULL,NULL,'2025-12-11 17:49:50','2026-01-20 06:40:16');
INSERT INTO `users` VALUES ('30','grey','greycruz00000000@gmail.com','$2y$10$7qyDoZ3GUP4okfOd0TGQpeDWUkPAdvBJTXDnTVqOXCoOxOu.z/Vui','aldrin','super_admin','active',NULL,'dikoalam','09563211331','uploads/users/user_30_1768890108.jpg','2026-01-20 06:39:57','172.18.0.1','0',NULL,'2026-01-15 05:31:24',NULL,NULL,'0',NULL,'25','2026-01-13 08:36:54','2026-01-20 06:39:57');
INSERT INTO `users` VALUES ('31','amor','amor@gmail.com','$2y$10$evvPUIl.aoXr/icZ85PNH.zTj4wnx.TEzKxHqijLpO0NgoAw7OyAa','amor','hr_admin','active',NULL,'asdfjh','09562312321',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,'0',NULL,'30','2026-01-13 09:58:43','2026-01-13 09:58:43');
INSERT INTO `users` VALUES ('32','ChristianAmor','christian5787264@gmail.com','$2y$10$4jtmtLYPqQBDYvKq3D9AouI3kPDYDJObT7mtYZ.PHY2j5rx64rqj.','christian amor','super_admin','active',NULL,'it','09613014462',NULL,'2026-01-15 08:20:37','192.168.1.23','0',NULL,'2026-01-15 08:18:54',NULL,'ZHLMOAXVWIX2DK4A','1',NULL,'30','2026-01-13 10:01:09','2026-01-15 08:20:37');
INSERT INTO `users` VALUES ('0','aaaaa','aa@gmail.com','$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6','alskdjfjklfdsa','hr_admin','active',NULL,'askldjf','2980374234',NULL,'2026-01-15 05:28:23','192.168.1.7','0',NULL,'2026-01-15 06:29:12','d95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552',NULL,'0',NULL,'30','2026-01-15 03:07:15','2026-01-16 04:54:16');
INSERT INTO `users` VALUES ('0','zzzzz','zzz@gmail.com','$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6','salkjfksldaj','hr_admin','active',NULL,'salkjfdklsajd','213849089234',NULL,'2026-01-15 05:28:23','192.168.1.7','0',NULL,'2026-01-15 06:29:12','d95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552',NULL,'0',NULL,'30','2026-01-15 03:08:25','2026-01-16 04:54:16');
INSERT INTO `users` VALUES ('0','bbbbaaa','bb@gmail.com','$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6','sadfsdfa','hr_admin','active',NULL,'sadfasfd','2893749823',NULL,'2026-01-15 05:28:23','192.168.1.7','0',NULL,'2026-01-15 06:29:12','d95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552',NULL,'0',NULL,'30','2026-01-15 03:21:17','2026-01-16 04:54:16');
INSERT INTO `users` VALUES ('0','zazaza','zz@gmail.com','$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6','saddfsafdas','hr_admin','active',NULL,'asfsadf','12312321',NULL,'2026-01-15 05:28:23','192.168.1.7','0',NULL,'2026-01-15 06:29:12','d95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552',NULL,'0',NULL,'30','2026-01-15 03:23:16','2026-01-16 04:54:16');
INSERT INTO `users` VALUES ('0','aldrin','aldrininocencio212527@gmail.com','$2y$10$qQKlgAavsZ6C7QHcXYq2TeSoJeGi7jZs0VXQZMjAvdgFN1eSSkR.6','aldrin','hr_admin','active',NULL,'IT','09563211331',NULL,'2026-01-15 05:28:23','192.168.1.7','0',NULL,'2026-01-15 06:29:12','d95a49d4bb7ba0a9c1a277b9fe776d20bbc3c7fd633877cba5b9f4198b03245d|1768458552',NULL,'0',NULL,'30','2026-01-15 05:23:05','2026-01-16 04:54:16');
INSERT INTO `users` VALUES ('0','sssss','sss@gmail.com','$2y$10$V.Tmj4tp3ANqZvMY77yJBurZ1kBuJGCwf5hMdu.vXR1uJbkxioxXi','ssssss','hr_admin','active',NULL,'sss','23432432',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,'0',NULL,'30','2026-01-16 04:34:07','2026-01-16 04:54:16');
UNLOCK TABLES;

