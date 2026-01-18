-- Migration: Add first_name and last_name columns to users table
-- This replaces the 'name' column with separate first_name and last_name columns

-- Step 1: Add first_name and last_name columns (nullable initially)
ALTER TABLE `users` 
ADD COLUMN `first_name` VARCHAR(100) NULL AFTER `name`,
ADD COLUMN `last_name` VARCHAR(100) NULL AFTER `first_name`;

-- Step 2: Migrate existing data from 'name' to first_name and last_name
-- This splits the name field by space - first word goes to first_name, rest goes to last_name
UPDATE `users` 
SET 
    `first_name` = SUBSTRING_INDEX(`name`, ' ', 1),
    `last_name` = CASE 
        WHEN LOCATE(' ', `name`) > 0 THEN SUBSTRING(`name`, LOCATE(' ', `name`) + 1)
        ELSE ''
    END
WHERE `first_name` IS NULL OR `last_name` IS NULL;

-- Step 3: (Optional) Remove the 'name' column after migration
-- Uncomment the line below only after verifying the migration worked correctly
-- ALTER TABLE `users` DROP COLUMN `name`;

-- Note: Keep the 'name' column for now to ensure backward compatibility
-- You can remove it later once you've verified everything works correctly
