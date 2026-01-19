-- ============================================
-- Notification Status Tracking
-- ============================================
-- This table tracks which notifications have been read or dismissed by each user

CREATE TABLE IF NOT EXISTS `notification_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'User who read/dismissed the notification',
  `notification_id` varchar(100) NOT NULL COMMENT 'ID of the notification (can be numeric or string like license_123)',
  `notification_type` enum('alert','license','clearance','task','message') NOT NULL DEFAULT 'alert' COMMENT 'Type of notification',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether the notification has been read',
  `is_dismissed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether the notification has been dismissed',
  `read_at` timestamp NULL DEFAULT NULL COMMENT 'When the notification was read',
  `dismissed_at` timestamp NULL DEFAULT NULL COMMENT 'When the notification was dismissed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_notification` (`user_id`,`notification_id`,`notification_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_notification_id` (`notification_id`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_is_dismissed` (`is_dismissed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks read and dismissed status of notifications per user';

-- ============================================
-- Notes:
-- ============================================
-- 1. This table uses a composite unique key to ensure one status record per user per notification
-- 2. notification_type helps differentiate between different sources of notifications
-- 3. Both is_read and is_dismissed are tracked separately for flexibility
-- 4. Timestamps allow tracking when actions occurred
-- 5. ON DUPLICATE KEY UPDATE is used in the API to update existing records
