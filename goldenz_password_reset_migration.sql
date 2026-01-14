-- Password Reset Migration
-- Adds password_reset_token and password_reset_expires_at fields to users table
-- Run this SQL to add proper password reset functionality

ALTER TABLE `users` 
ADD COLUMN `password_reset_token` VARCHAR(255) NULL AFTER `remember_token`,
ADD COLUMN `password_reset_expires_at` TIMESTAMP NULL AFTER `password_reset_token`;

-- Add index for faster lookups
CREATE INDEX `idx_password_reset_token` ON `users` (`password_reset_token`);
CREATE INDEX `idx_password_reset_expires` ON `users` (`password_reset_expires_at`);
