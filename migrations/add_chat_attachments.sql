-- Add attachment support to chat_messages table
-- Run this after add_chat_system_no_fk.sql

ALTER TABLE `chat_messages` 
ADD COLUMN `attachment_type` VARCHAR(20) NULL DEFAULT NULL AFTER `message`,
ADD COLUMN `attachment_path` VARCHAR(255) NULL DEFAULT NULL AFTER `attachment_type`,
ADD COLUMN `attachment_size` INT NULL DEFAULT NULL AFTER `attachment_path`,
ADD COLUMN `attachment_name` VARCHAR(255) NULL DEFAULT NULL AFTER `attachment_size`;

-- Add index for attachment queries
ALTER TABLE `chat_messages` 
ADD INDEX `idx_attachment` (`attachment_type`, `attachment_path`);
