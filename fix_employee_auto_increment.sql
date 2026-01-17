-- Fix Employee Auto-Increment Counter
-- This script resets the auto-increment counter for the employees table
-- to be one more than the maximum existing ID

-- Step 1: Find the maximum ID in the employees table
SET @max_id = (SELECT COALESCE(MAX(id), 0) FROM employees);

-- Step 2: Reset the auto-increment counter
-- The next employee will get ID = @max_id + 1
ALTER TABLE employees AUTO_INCREMENT = @max_id + 1;

-- Verify the fix
SELECT 
    'Current Max ID' AS Description,
    @max_id AS Value
UNION ALL
SELECT 
    'Next Auto-Increment Value' AS Description,
    (SELECT AUTO_INCREMENT 
     FROM information_schema.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'employees') AS Value;
