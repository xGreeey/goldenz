-- Add soft delete support for chat messages
-- Allows users to clear their own view of messages without affecting the other user

ALTER TABLE `chat_messages` 
ADD COLUMN `deleted_by_sender` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_read`,
ADD COLUMN `deleted_by_receiver` TINYINT(1) NOT NULL DEFAULT 0 AFTER `deleted_by_sender`;

-- Add indexes for efficient querying
ALTER TABLE `chat_messages` 
ADD INDEX `idx_deleted_by_sender` (`deleted_by_sender`),
ADD INDEX `idx_deleted_by_receiver` (`deleted_by_receiver`);

-- Comments for clarity
-- deleted_by_sender: 1 if sender has cleared this message from their view
-- deleted_by_receiver: 1 if receiver has cleared this message from their view
-- Message is only physically deleted when both users have deleted it (optional cleanup job)
