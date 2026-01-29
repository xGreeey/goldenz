-- ============================================================================
-- DTR / Attendance Adjustments - Database Migration
-- ============================================================================
-- Purpose:
-- - Provide an audit trail for manual time corrections on Daily Attendance / DTR
-- - Keep original values for compliance and traceability
-- ============================================================================

CREATE TABLE IF NOT EXISTS `dtr_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `dtr_entry_id` int(11) DEFAULT NULL COMMENT 'Related dtr_entries.id if present',
  `old_time_in` time DEFAULT NULL,
  `new_time_in` time DEFAULT NULL,
  `old_time_out` time DEFAULT NULL,
  `new_time_out` time DEFAULT NULL,
  `reason` text NOT NULL,
  `adjusted_by` int(11) DEFAULT NULL COMMENT 'users.id who performed the adjustment',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`,`entry_date`),
  KEY `idx_dtr_entry_id` (`dtr_entry_id`),
  KEY `idx_adjusted_by` (`adjusted_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_dtr_adj_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dtr_adj_dtr_entry` FOREIGN KEY (`dtr_entry_id`) REFERENCES `dtr_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dtr_adj_user` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log for manual DTR adjustments';

