-- Remove Dummy/Test Employee Data from employees table
-- This script removes the sample/dummy employee records that were inserted for testing
-- Date: 2024
-- Description: Removes dummy employee data so only real employee records are shown

-- Delete dummy employees (IDs 1-4 based on the sample data)
DELETE FROM `employees` WHERE `id` IN (1, 2, 3, 4);

-- Reset AUTO_INCREMENT to start from 1 (optional - only if you want to reset IDs)
-- ALTER TABLE `employees` AUTO_INCREMENT = 1;

-- Verify deletion (uncomment to check)
-- SELECT COUNT(*) as total_employees FROM employees;
