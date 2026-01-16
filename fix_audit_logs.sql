-- Fix audit_logs table: Make id column AUTO_INCREMENT
-- This fixes the "Duplicate entry '0' for key 'PRIMARY'" error
-- 
-- PROBLEM: The 'id' column has NO AUTO_INCREMENT, so INSERTs default to id=0
-- SOLUTION: Add AUTO_INCREMENT to the id column

-- Step 1: Delete the record with id=0 (if it exists)
DELETE FROM audit_logs WHERE id = 0;

-- Step 2: Add AUTO_INCREMENT to the id column
ALTER TABLE audit_logs MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT;

-- After running this, the structure should show:
-- id | int(11) | NO | PRI | NULL | auto_increment
--
-- Verify the change worked:
SHOW CREATE TABLE audit_logs;
