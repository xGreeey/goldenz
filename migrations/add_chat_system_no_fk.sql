-- Chat System Tables (NO FOREIGN KEY CONSTRAINTS)
-- Use this version if you have issues with foreign keys
-- Private one-to-one messaging for authenticated users

-- Messages table
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sender_id` (`sender_id`),
  INDEX `idx_receiver_id` (`receiver_id`),
  INDEX `idx_conversation` (`sender_id`, `receiver_id`, `created_at`),
  INDEX `idx_is_read` (`is_read`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Typing indicators table (optional, for real-time typing status)
CREATE TABLE IF NOT EXISTS `chat_typing_status` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `recipient_id` INT NOT NULL,
  `is_typing` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_recipient` (`user_id`, `recipient_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_recipient_id` (`recipient_id`),
  INDEX `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversation metadata for quick access (optional optimization)
CREATE TABLE IF NOT EXISTS `chat_conversations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user1_id` INT NOT NULL,
  `user2_id` INT NOT NULL,
  `last_message_id` INT DEFAULT NULL,
  `last_message_at` TIMESTAMP NULL DEFAULT NULL,
  `user1_unread_count` INT NOT NULL DEFAULT 0,
  `user2_unread_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_conversation` (`user1_id`, `user2_id`),
  INDEX `idx_user1_id` (`user1_id`),
  INDEX `idx_user2_id` (`user2_id`),
  INDEX `idx_last_message_at` (`last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: This version does NOT include foreign key constraints
-- The chat system will work perfectly fine without them
-- Foreign keys are only for enforcing referential integrity at the database level
