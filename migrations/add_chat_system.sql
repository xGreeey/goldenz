-- Chat System Tables
-- Private one-to-one messaging for authenticated users

-- Check and fix users table structure if needed
-- Skip this section if your users table is already properly configured

-- Messages table
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_by_sender` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_by_receiver` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sender_id` (`sender_id`),
  INDEX `idx_receiver_id` (`receiver_id`),
  INDEX `idx_conversation` (`sender_id`, `receiver_id`, `created_at`),
  INDEX `idx_is_read` (`is_read`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_deleted_by_sender` (`deleted_by_sender`),
  INDEX `idx_deleted_by_receiver` (`deleted_by_receiver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraints separately (safer approach)
-- If these fail, the tables will still work, just without FK constraints
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_messages_sender` 
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_messages_receiver` 
  FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

-- Add foreign key constraints separately
ALTER TABLE `chat_typing_status`
  ADD CONSTRAINT `fk_chat_typing_user` 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `chat_typing_status`
  ADD CONSTRAINT `fk_chat_typing_recipient` 
  FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

-- Add foreign key constraints separately
ALTER TABLE `chat_conversations`
  ADD CONSTRAINT `fk_chat_conversations_user1` 
  FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `chat_conversations`
  ADD CONSTRAINT `fk_chat_conversations_user2` 
  FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `chat_conversations`
  ADD CONSTRAINT `fk_chat_conversations_last_message` 
  FOREIGN KEY (`last_message_id`) REFERENCES `chat_messages` (`id`) ON DELETE SET NULL;
