-- ============================================================================
-- Secure Employee File Management - Database Migrations
-- ============================================================================
-- This migration creates tables for secure employee file storage
-- Files are stored outside web root with UUID filenames
-- All access is controlled through backend endpoints with permission checks
-- ============================================================================

-- Table: employee_files
-- Stores metadata for all employee documents
CREATE TABLE IF NOT EXISTS `employee_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL COMMENT 'Employee this file belongs to',
  `uploaded_by` int(11) NOT NULL COMMENT 'User ID who uploaded the file',
  `original_filename` varchar(255) NOT NULL COMMENT 'Original filename for display',
  `stored_filename` varchar(255) NOT NULL COMMENT 'UUID filename on disk',
  `file_path` varchar(500) NOT NULL COMMENT 'Full path relative to storage root',
  `category` enum('Personal Records','Contracts','Government IDs','Certifications','Other') NOT NULL DEFAULT 'Other' COMMENT 'Document category',
  `mime_type` varchar(100) NOT NULL COMMENT 'MIME type (validated server-side)',
  `size_bytes` bigint(20) NOT NULL COMMENT 'File size in bytes',
  `storage_driver` enum('local','minio') NOT NULL DEFAULT 'local' COMMENT 'Storage backend used',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_category` (`category`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_employee_files_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_employee_files_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Secure employee file storage metadata';

-- Table: file_audit_logs
-- Audit trail for all file operations (upload, download, delete)
CREATE TABLE IF NOT EXISTS `file_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` enum('upload','download','delete','view') NOT NULL COMMENT 'Action performed',
  `user_id` int(11) NOT NULL COMMENT 'User who performed the action',
  `employee_id` int(11) NOT NULL COMMENT 'Employee whose file was accessed',
  `file_id` int(11) DEFAULT NULL COMMENT 'File ID (NULL if file was deleted)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of requester',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'User agent string',
  `success` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether action succeeded',
  `error_message` text DEFAULT NULL COMMENT 'Error message if action failed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_action` (`action`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_file_id` (`file_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_file_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_file_audit_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_audit_file` FOREIGN KEY (`file_id`) REFERENCES `employee_files` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log for file operations';

-- ============================================================================
-- Note: The users table does not need any alterations.
-- It already has all necessary fields (id, role, etc.) for the file system.
-- ============================================================================
