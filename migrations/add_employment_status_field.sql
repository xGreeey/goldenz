-- Add employment_status field to employees table
-- Employment Status: Probationary, Regular, Suspended, Terminated

ALTER TABLE `employees` 
ADD COLUMN IF NOT EXISTS `employment_status` ENUM('Probationary', 'Regular', 'Suspended', 'Terminated') NULL DEFAULT NULL 
COMMENT 'Employment Status: Probationary, Regular, Suspended, Terminated' 
AFTER `date_hired`;

-- Update existing records: Set employment_status based on date_hired if not set
-- Probationary: hired within last 6 months
-- Regular: hired more than 6 months ago
UPDATE `employees` 
SET `employment_status` = CASE 
    WHEN `date_hired` IS NOT NULL 
         AND `date_hired` != '' 
         AND `date_hired` != '0000-00-00'
         AND STR_TO_DATE(`date_hired`, '%Y-%m-%d') >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    THEN 'Probationary'
    WHEN `date_hired` IS NOT NULL 
         AND `date_hired` != '' 
         AND `date_hired` != '0000-00-00'
         AND STR_TO_DATE(`date_hired`, '%Y-%m-%d') < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    THEN 'Regular'
    ELSE NULL
END
WHERE `employment_status` IS NULL;

-- Update status field to only allow Active/Inactive (Account Access Status)
-- First, update any Terminated/Suspended records to Inactive for account access
UPDATE `employees` 
SET `status` = 'Inactive' 
WHERE `status` IN ('Terminated', 'Suspended');

-- Modify status ENUM to only include Active and Inactive
ALTER TABLE `employees` 
MODIFY COLUMN `status` ENUM('Active', 'Inactive') DEFAULT 'Active' 
COMMENT 'Account Access Status: Active, Inactive';
