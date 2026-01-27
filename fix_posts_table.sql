-- Fix posts table: Add AUTO_INCREMENT to id column
-- Run this SQL command in your MySQL database

-- First, check the current max ID to set AUTO_INCREMENT properly
-- (This prevents conflicts if there are existing records)
SET @max_id = (SELECT COALESCE(MAX(id), 0) FROM posts);

-- Alter the table to add AUTO_INCREMENT
ALTER TABLE posts 
MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;

-- Set the AUTO_INCREMENT value to start after the current max ID
SET @sql = CONCAT('ALTER TABLE posts AUTO_INCREMENT = ', @max_id + 1);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the change
SHOW CREATE TABLE posts;
