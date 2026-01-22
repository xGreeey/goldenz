-- Migration: Add Leave Management and Violation Management Tables
-- Date: January 2026

-- Leave Requests Table
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `leave_type` VARCHAR(50) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `days` DECIMAL(3,1) NOT NULL DEFAULT 1.0,
  `reason` TEXT,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `request_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_by` INT(11) DEFAULT NULL,
  `processed_date` DATETIME DEFAULT NULL,
  `approval_notes` TEXT,
  `rejection_notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_leave_type` (`leave_type`),
  KEY `idx_start_date` (`start_date`),
  CONSTRAINT `fk_leave_requests_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leave Balances Table (if not exists)
CREATE TABLE IF NOT EXISTS `leave_balances` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `year` INT(4) NOT NULL,
  `sick_leave_total` DECIMAL(4,1) NOT NULL DEFAULT 15.0,
  `sick_leave_used` DECIMAL(4,1) NOT NULL DEFAULT 0.0,
  `vacation_leave_total` DECIMAL(4,1) NOT NULL DEFAULT 15.0,
  `vacation_leave_used` DECIMAL(4,1) NOT NULL DEFAULT 0.0,
  `emergency_leave_total` DECIMAL(4,1) NOT NULL DEFAULT 5.0,
  `emergency_leave_used` DECIMAL(4,1) NOT NULL DEFAULT 0.0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_year` (`employee_id`, `year`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_year` (`year`),
  CONSTRAINT `fk_leave_balances_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance Records Table
CREATE TABLE IF NOT EXISTS `attendance_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `date` DATE NOT NULL,
  `time_in` TIME DEFAULT NULL,
  `time_out` TIME DEFAULT NULL,
  `hours_worked` DECIMAL(4,2) DEFAULT 0.00,
  `status` ENUM('Present', 'Late', 'Absent', 'Half-Day', 'On Leave') NOT NULL DEFAULT 'Present',
  `is_adjusted` TINYINT(1) NOT NULL DEFAULT 0,
  `adjustment_reason` TEXT,
  `adjusted_by` INT(11) DEFAULT NULL,
  `adjusted_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_date` (`employee_id`, `date`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_date` (`date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Violation Types Table
CREATE TABLE IF NOT EXISTS `violation_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `category` ENUM('Major', 'Minor') NOT NULL,
  `description` TEXT,
  `sanctions` JSON,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee Violations Table
CREATE TABLE IF NOT EXISTS `employee_violations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `violation_type_id` INT(11) NOT NULL,
  `violation_date` DATE NOT NULL,
  `description` TEXT,
  `severity` ENUM('Major', 'Minor') NOT NULL,
  `sanction` VARCHAR(200),
  `sanction_date` DATE DEFAULT NULL,
  `reported_by` VARCHAR(100),
  `status` ENUM('Pending', 'Under Review', 'Resolved') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_violation_type_id` (`violation_type_id`),
  KEY `idx_severity` (`severity`),
  KEY `idx_status` (`status`),
  KEY `idx_violation_date` (`violation_date`),
  CONSTRAINT `fk_violations_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_violations_type` FOREIGN KEY (`violation_type_id`) REFERENCES `violation_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee Documents Table
CREATE TABLE IF NOT EXISTS `employee_documents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `document_type` VARCHAR(100) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` INT(11) DEFAULT NULL,
  `upload_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_by` INT(11) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_upload_date` (`upload_date`),
  CONSTRAINT `fk_documents_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default violation types
INSERT INTO `violation_types` (`name`, `category`, `description`, `sanctions`) VALUES
('AWOL (Absence Without Official Leave)', 'Major', 'Employee is absent from work without prior notification or approval.', '["1st Offense: Written Warning", "2nd Offense: 3-day Suspension", "3rd Offense: Termination"]'),
('Tardiness', 'Minor', 'Arriving late to work or designated post beyond grace period.', '["1st Offense: Verbal Warning", "2nd Offense: Written Warning", "3rd Offense: 1-day Suspension"]'),
('Insubordination', 'Major', 'Refusal to follow lawful and reasonable directives from supervisors.', '["1st Offense: Final Warning", "2nd Offense: Termination"]'),
('Dress Code Violation', 'Minor', 'Not adhering to the company uniform or dress code policy.', '["1st Offense: Verbal Warning", "2nd Offense: Written Warning", "3rd Offense: 1-day Suspension"]'),
('Safety Violation', 'Major', 'Engaging in activities that compromise workplace safety or security protocols.', '["1st Offense: Written Warning + Safety Training", "2nd Offense: 5-day Suspension", "3rd Offense: Termination"]'),
('Unauthorized Leave', 'Minor', 'Taking leave without proper approval or documentation.', '["1st Offense: Written Warning", "2nd Offense: 2-day Suspension", "3rd Offense: Final Warning"]'),
('Theft', 'Major', 'Stealing company or client property, regardless of value.', '["1st Offense: Immediate Termination + Legal Action"]'),
('Harassment', 'Major', 'Any form of harassment including sexual, verbal, or physical harassment.', '["1st Offense: Final Warning + Counseling", "2nd Offense: Termination + Legal Action"]')
ON DUPLICATE KEY UPDATE name=name;
