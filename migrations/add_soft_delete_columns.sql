-- Add soft delete columns to existing chat_messages table
-- This allows "Delete for me" functionality - users can clear their own view
-- without affecting the other user's messages
-- Run this if your chat_messages table already exists

ALTER TABLE `chat_messages` 
ADD COLUMN `deleted_by_sender` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_read`,
ADD COLUMN `deleted_by_receiver` TINYINT(1) NOT NULL DEFAULT 0 AFTER `deleted_by_sender`;

-- Add indexes for efficient querying
ALTER TABLE `chat_messages` 
ADD INDEX `idx_deleted_by_sender` (`deleted_by_sender`),
ADD INDEX `idx_deleted_by_receiver` (`deleted_by_receiver`);
