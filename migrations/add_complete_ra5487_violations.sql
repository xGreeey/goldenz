-- ============================================================================
-- Migration: Add Complete RA 5487 Violations System
-- File: add_complete_ra5487_violations.sql
-- Date: January 2026
-- Description: Complete violation system as per RA 5487 (Private Security Agency Law)
--              Includes Major Violations, Minor Violations, and RA 5487 Offenses
--              with progressive sanctions for 1st, 2nd, 3rd, 4th, and 5th offenses
-- ============================================================================
--
-- WHAT'S INCLUDED IN THIS MIGRATION:
-- ===================================
--
-- 1. MAJOR VIOLATIONS (28 violations)
--    - Reference Numbers: Sequentially numbered MAJ-1 to MAJ-28
--    - Sanction Patterns:
--      * Dismissal on 1st Offense: 11 violations (Ref: MAJ-1, MAJ-2, MAJ-3, MAJ-4, MAJ-5, MAJ-6, MAJ-7, MAJ-8, MAJ-19, MAJ-22, MAJ-25)
--      * 30 days suspension → Dismissal: 16 violations (Ref: MAJ-10 to MAJ-18, MAJ-20, MAJ-21, MAJ-23, MAJ-24, MAJ-26 to MAJ-28)
--      * Special Case: Violation MAJ-23 includes payment requirement
--
-- 2. MINOR VIOLATIONS (30 violations)
--    - Reference Numbers: Sequentially numbered MIN-1 to MIN-30
--    - Progressive Sanctions: 3-5 offenses before dismissal
--    - Examples:
--      * Written reprimand → 7 days → 15 days → 30 days → Dismissal (MIN-21, MIN-29)
--      * 3 days → 7 days → 15 days → 30 days → Dismissal (MIN-9, MIN-28)
--      * 7 days → 15 days → 30 days → Dismissal (most common pattern)
--      * 15 days → 30 days → Dismissal (simpler pattern)
--
-- 3. RA 5487 OFFENSES (39 violations)
--    - Reference Numbers: Preserved exactly as per RA 5487 legal documentation
--    - A. Security Guard Creed: 1 violation (A.1)
--    - B. Code of Conduct: 15 violations (B.1-B.15)
--      * Most have: 15 days → 30 days → Dismissal
--      * B.13 (Lending firearms): Dismissal on 1st offense
--    - C. Code of Ethics: 12 violations (C.1-C.12)
--      * C.5 (Compromising with criminals): Dismissal on 1st offense
--      * Most have: 7-15 days → 30 days → Dismissal
--    - D. Eleven General Orders: 11 violations (D.1-D.11)
--      * D.3: 15 days suspension only
--      * D.5: 30 days → Dismissal
--      * D.7, D.10, D.11: 7 days → 15 days → 30 days (no dismissal)
--      * Others: 7-15 days → 30 days → Dismissal
--
-- DATABASE STRUCTURE CHANGES:
-- ===========================
-- This migration creates the `violation_types` table if it doesn't exist, or
-- adds the following columns to an existing `violation_types` table:
--   - reference_no (VARCHAR(20)): Supports alphanumeric codes (MAJ-1, MIN-1, A.1, B.1, etc.)
--   - subcategory (VARCHAR(100)): For RA 5487 subcategories (A, B, C, D)
--   - first_offense (VARCHAR(255)): Sanction for 1st offense
--   - second_offense (VARCHAR(255)): Sanction for 2nd offense
--   - third_offense (VARCHAR(255)): Sanction for 3rd offense
--   - fourth_offense (VARCHAR(255)): Sanction for 4th offense
--   - fifth_offense (VARCHAR(255)): Sanction for 5th offense
--   - ra5487_compliant (TINYINT(1)): Flag for RA 5487 compliance
-- All column additions are safe and will skip if columns already exist
--
-- TOTAL VIOLATIONS: 97
--   - 28 Major Violations
--   - 30 Minor Violations
--   - 39 RA 5487 Offenses
--
-- IMPORTANT NOTES:
-- ================
-- - This migration deletes existing violations that are not RA 5487 compliant
-- - All new violations are marked with ra5487_compliant = 1
-- - Reference numbers are standardized for consistency:
--   * Major Violations: Sequential numbering (MAJ-1, MAJ-2, MAJ-3... MAJ-28)
--   * Minor Violations: Sequential numbering (MIN-1, MIN-2, MIN-3... MIN-30)
--   * RA 5487 Offenses: Preserved as per legal documentation (A.1, B.1-B.15, C.1-C.12, D.1-D.11)
-- - Progressive sanctions follow the exact pattern specified in RA 5487
--
-- ============================================================================

-- ============================================================================
-- CREATE VIOLATION_TYPES TABLE IF IT DOES NOT EXIST
-- ============================================================================
-- This section creates the violation_types table with all required columns
-- If the table already exists, it will be skipped
-- ============================================================================

CREATE TABLE IF NOT EXISTS `violation_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `reference_no` VARCHAR(20) DEFAULT NULL,
  `name` VARCHAR(200) NOT NULL,
  `category` ENUM('Major', 'Minor') NOT NULL,
  `subcategory` VARCHAR(100) DEFAULT NULL,
  `description` TEXT,
  `sanctions` JSON DEFAULT NULL,
  `first_offense` VARCHAR(255) DEFAULT NULL,
  `second_offense` VARCHAR(255) DEFAULT NULL,
  `third_offense` VARCHAR(255) DEFAULT NULL,
  `fourth_offense` VARCHAR(255) DEFAULT NULL,
  `fifth_offense` VARCHAR(255) DEFAULT NULL,
  `ra5487_compliant` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_reference_no` (`reference_no`),
  KEY `idx_ra5487_compliant` (`ra5487_compliant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ALTER EXISTING TABLE TO ADD MISSING COLUMNS (IF TABLE ALREADY EXISTS)
-- ============================================================================
-- This section adds any missing columns to an existing violation_types table
-- All checks are safe and will skip if columns already exist
-- ============================================================================

SET @dbname = DATABASE();
SET @tablename = 'violation_types';

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname 
   AND TABLE_NAME = @tablename 
   AND COLUMN_NAME = 'reference_no') > 0,
  'SELECT 1',
  'ALTER TABLE `violation_types` ADD COLUMN `reference_no` VARCHAR(20) DEFAULT NULL AFTER `id`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname 
   AND TABLE_NAME = @tablename 
   AND COLUMN_NAME = 'subcategory') > 0,
  'SELECT 1',
  'ALTER TABLE `violation_types` ADD COLUMN `subcategory` VARCHAR(100) DEFAULT NULL AFTER `category`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname 
   AND TABLE_NAME = @tablename 
   AND COLUMN_NAME = 'first_offense') > 0,
  'SELECT 1',
  'ALTER TABLE `violation_types` ADD COLUMN `first_offense` VARCHAR(255) DEFAULT NULL AFTER `sanctions`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname 
   AND TABLE_NAME = @tablename 
   AND COLUMN_NAME = 'second_offense') > 0,
  'SELECT 1',
  'ALTER TABLE `violation_types` ADD COLUMN `second_offense` VARCHAR(255) DEFAULT NULL AFTER `first_offense`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname 
   AND TABLE_NAME = @tablename 
   AND COLUMN_NAME = 'third_offense') > 0,
  'SELECT 1',
  'ALTER TABLE `violation_types` ADD COLUMN `third_offense` VARCHAR(255) DEFAULT NULL AFTER `second_offense`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname 
   AND TABLE_NAME = @tablename 
   AND COLUMN_NAME = 'fourth_offense') > 0,
  'SELECT 1',
  'ALTER TABLE `violation_types` ADD COLUMN `fourth_offense` VARCHAR(255) DEFAULT NULL AFTER `third_offense`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname 
   AND TABLE_NAME = @tablename 
   AND COLUMN_NAME = 'fifth_offense') > 0,
  'SELECT 1',
  'ALTER TABLE `violation_types` ADD COLUMN `fifth_offense` VARCHAR(255) DEFAULT NULL AFTER `fourth_offense`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname 
   AND TABLE_NAME = @tablename 
   AND COLUMN_NAME = 'ra5487_compliant') > 0,
  'SELECT 1',
  'ALTER TABLE `violation_types` ADD COLUMN `ra5487_compliant` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fifth_offense`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add index for reference number if it doesn't exist
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
   WHERE TABLE_SCHEMA = @dbname 
   AND TABLE_NAME = @tablename 
   AND INDEX_NAME = 'idx_reference_no') > 0,
  'SELECT 1',
  'ALTER TABLE `violation_types` ADD INDEX `idx_reference_no` (`reference_no`)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Delete existing violations that are not RA 5487 compliant
DELETE FROM `violation_types` 
WHERE (`ra5487_compliant` = 0 OR `ra5487_compliant` IS NULL);

-- ============================================================================
-- INSERT MAJOR VIOLATIONS (Reference MAJ-1 to MAJ-28)
-- ============================================================================
INSERT INTO `violation_types` (`reference_no`, `name`, `category`, `subcategory`, `description`, `first_offense`, `second_offense`, `third_offense`, `fourth_offense`, `fifth_offense`, `ra5487_compliant`, `is_active`) VALUES

-- Major Violations with Dismissal for 1st Offense
('MAJ-1', 'Engaging in espionage or sabotage of company properties or operation process', 'Major', NULL, 
 'Engaging in espionage or sabotage activities against company properties or operational processes.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('MAJ-2', 'Circulating written or printed unauthorized materials inside the company premises.', 'Major', NULL, 
 'Distributing unauthorized written or printed materials within company premises.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('MAJ-3', 'Selling of any company properties including guns and ammunitions.', 'Major', NULL, 
 'Unauthorized sale of company properties, including firearms and ammunition.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('MAJ-4', 'Prostitution and violation of sexual harassment act under influence of drugs or using prohibited drugs while in the performance of duty and/or while inside the client/company premises.', 'Major', NULL, 
 'Engaging in prostitution, violating sexual harassment laws, or using prohibited drugs while on duty or within company/client premises.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('MAJ-5', 'Acts of cowardism. defeatism or turning away from the performance of duty in the face of criminals, arsonist, pilferers, thieves or robbers and/or other criminal elements.', 'Major', NULL, 
 'Displaying cowardice, defeatism, or abandoning duty when facing criminals, arsonists, pilferers, thieves, robbers, or other criminal elements.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('MAJ-6', 'Endangering the safety of client, client''s personnel and relatives, tenants, colleagues or workers on the post by any of the following: 1. misconduct; 2. negligence; 3. disobedience.', 'Major', NULL, 
 'Endangering safety of clients, personnel, relatives, tenants, colleagues, or workers through misconduct, negligence, or disobedience.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('MAJ-7', 'Stealing and/or in connivance with thieves while on and/or off official duty.', 'Major', NULL, 
 'Engaging in theft or conspiring with thieves while on or off official duty.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('MAJ-8', 'Challenging or assaulting co-employees, clients, clients authorized representatives, client/s children and legally adopted children and relatives and or company''s officers, company officers children and/or legally adopted children and relatives.', 'Major', NULL, 
 'Challenging or assaulting co-employees, clients, authorized representatives, children, relatives, or company officers and their families.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('MAJ-19', 'Disclosure of operation methods or formula of the company or revealing any company information considered confidential to competitors.', 'Major', NULL, 
 'Disclosing company operational methods, formulas, or confidential information to competitors.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('MAJ-22', 'Malversation of funds and other financial accountabilities such as payroll for the guards, company funds for SSS, Philhealth, HDMF, Insurance remittances and any other similar acts.', 'Major', NULL, 
 'Misappropriation of funds including payroll, SSS, PhilHealth, HDMF, Insurance remittances, and other financial accountabilities.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('MAJ-25', 'Dishonesty, or any manner obstructing legitimate facts and concealing fraudulent action which is material to any subject that may greatly affect the company''s operation in general and to the department where he/she is belong in particular.', 'Major', NULL, 
 'Dishonesty, obstruction of legitimate facts, or concealment of fraudulent actions materially affecting company operations.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

-- Major Violations with 30 days suspension for 1st Offense, Dismissal for 2nd Offense
('MAJ-10', 'Threatening, intimidating, bad mouthing, coercing, or disturbing fellow employees inside the company premises.', 'Major', NULL, 
 'Threatening, intimidating, bad mouthing, coercing, or disturbing fellow employees within company premises.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-11', 'Provoking or instigating a fight, fighting or inflicting bodily harm upon another within the company premises.', 'Major', NULL, 
 'Provoking, instigating fights, or inflicting bodily harm upon others within company premises.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-12', 'Leaving or abandoning the place of works without the authorization from the immediate superior.', 'Major', NULL, 
 'Leaving or abandoning the workplace without authorization from immediate superior.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-13', 'Gross negligence resulting to damage of any company property and termination of security services with the clientele.', 'Major', NULL, 
 'Gross negligence causing damage to company property or termination of security services with clients.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-14', 'Making false statement or testimony during investigation conducted by the management or derogatory remarks (oral or written) to the detriment of company''s operation and loss of company''s probity.', 'Major', NULL, 
 'Making false statements during investigations or derogatory remarks detrimental to company operations and reputation.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-15', 'Sleeping on post during office or working hours.', 'Major', NULL, 
 'Sleeping while on post during office or working hours.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-16', 'Fraud or willful breach of the trust and confidence entrusted to him/her by the management like misappropriation or malversation of funds, merchandise or other properties of the company and such other fraudulent acts.', 'Major', NULL, 
 'Fraud or willful breach of trust including misappropriation or malversation of funds, merchandise, or company properties.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-17', 'Securing employment or working with other business establishment without notice or permissions from the management while still connected with the company.', 'Major', NULL, 
 'Securing employment or working with other businesses without notice or permission while still employed with the company.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-18', 'Encouraging, coercing, initiating bribery including other employees to engage in any practice to violate company''s rules and regulations.', 'Major', NULL, 
 'Encouraging, coercing, or initiating bribery or practices that violate company rules and regulations.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-20', 'Threatening, drawing deadly weapon, dry firing, accidental firing and/or unlawful discharge of ammunitions, or offering violence to an officer, clients and client/s personnel and duly authorized representatives and relatives, tenants, workers, visitors and fellow employee without justifiable cause.', 'Major', NULL, 
 'Threatening with deadly weapons, dry firing, accidental firing, unlawful discharge of ammunition, or offering violence without justifiable cause.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-21', 'Illegally selling or wrongfully disposing clients or tenant and/or company property.', 'Major', NULL, 
 'Illegally selling or wrongfully disposing of client, tenant, or company property.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-24', 'Sending, giving, entrusting company issued firearm/s to any unauthorized personalities.', 'Major', NULL, 
 'Sending, giving, or entrusting company-issued firearms to unauthorized persons.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-26', 'Allowing one''s ID card to be used by the others.', 'Major', NULL, 
 'Allowing one''s ID card to be used by others.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-27', 'Entering into any business with third parties for personal gain or profit without company''s authorization.', 'Major', NULL, 
 'Entering into business arrangements with third parties for personal gain without company authorization.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('MAJ-28', 'Fighting, horse playing, manhandling, shopping, slapping, punching, or quarreling with client, client authorized representatives, tenants, contractors, workers, visitors or co-employee without justifiable cause.', 'Major', NULL, 
 'Fighting, horse playing, manhandling, slapping, punching, or quarreling with clients, representatives, tenants, contractors, workers, visitors, or co-employees without justifiable cause.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

-- Special case: Violation MAJ-23 with payment requirement
('MAJ-23', 'Allowing unauthorized person/s to enter the client''s premises or company premises without the required security pass.', 'Major', NULL, 
 'Allowing unauthorized persons to enter client or company premises without required security pass.', 
 '30 days suspension & payment for disbursed/malversed funds', 'Dismissal & Payment for disbursed/malversed funds.', NULL, NULL, NULL, 1, 1);

-- ============================================================================
-- INSERT MINOR VIOLATIONS (Reference 1-30)
-- ============================================================================
INSERT INTO `violation_types` (`reference_no`, `name`, `category`, `subcategory`, `description`, `first_offense`, `second_offense`, `third_offense`, `fourth_offense`, `fifth_offense`, `ra5487_compliant`, `is_active`) VALUES

('MIN-1', 'Insubordination, disrespect, disobedience, or willfully and intentionally refusing to obey superior''s legal order to perform task. Refuse to accept duty assignment, to include failure to submit reportorial requirements required by the management.', 'Minor', NULL, 
 'Insubordination, disrespect, disobedience, or willfully refusing to obey superior''s legal orders or accept duty assignments, including failure to submit required reports.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('MIN-2', 'Habitual neglect of duty or responsibility.', 'Minor', NULL, 
 'Habitual neglect of duty or responsibility.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-3', 'Illegally obtaining material, tools, or supplies on fraudulent orders.', 'Minor', NULL, 
 'Illegally obtaining materials, tools, or supplies through fraudulent orders.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('MIN-4', 'Non-intentional sleeping on post during working hours.', 'Minor', NULL, 
 'Non-intentional sleeping while on post during working hours.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('MIN-5', 'Drinking liquor or drunkenness while in the performance of duty and/or while inside the office/client premises.', 'Minor', NULL, 
 'Drinking liquor or being drunk while performing duty or inside office/client premises.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('MIN-6', 'Gambling, play cara y cruz, card and all other forms of illegal gambling while on duty or inside the client premises.', 'Minor', NULL, 
 'Engaging in gambling, cara y cruz, card games, or other illegal gambling while on duty or inside client premises.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('MIN-7', 'Favoring and/or conniving with suppliers, creditors, fellow guards, fellow officers in the client and company in consideration of kickbacks or personal rebates working company funds.', 'Minor', NULL, 
 'Favoring or conniving with suppliers, creditors, fellow guards, or officers for kickbacks or personal rebates using company funds.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('MIN-8', 'Loitering and/or attending to personal matters without the authority from the immediate superior and/or Manager during hours and/or while within the company premises.', 'Minor', NULL, 
 'Loitering or attending to personal matters without authorization from immediate superior or Manager during working hours or within company premises.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-9', 'Accumulation of a total of sixty (60) minutes or more than 1 hour late in a week.', 'Minor', NULL, 
 'Accumulating 60 minutes or more of tardiness in a week.', 
 '3 days suspension', '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', 1, 1),

('MIN-10', 'Failure to return to work upon authorized leave without reasonable or justifiable cause.', 'Minor', NULL, 
 'Failure to return to work after authorized leave without reasonable or justifiable cause.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-11', 'Failure to observe proper behaviour during working hours.', 'Minor', NULL, 
 'Failure to observe proper behavior during working hours.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-12', 'Use of vulgar, abusive and indecent words/language towards co-employees/staff and/or company officials.', 'Minor', NULL, 
 'Using vulgar, abusive, or indecent words/language towards co-employees, staff, or company officials.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-13', 'Using company''s time, equipment, tools, or supplies for personal purposes without management permission.', 'Minor', NULL, 
 'Using company time, equipment, tools, or supplies for personal purposes without management permission.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-14', 'False report to have ailment or malingering or pretending to be sick in order to be absent for work.', 'Minor', NULL, 
 'Falsely reporting illness, malingering, or pretending to be sick to be absent from work.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('MIN-15', 'Refusal to observed company policy on security and safety requirements such as wearing personal protective equipment etc.', 'Minor', NULL, 
 'Refusal to observe company policy on security and safety requirements such as wearing personal protective equipment.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-16', 'Disrespect towards clients, relatives and personal tenants, superior officer and visitors.', 'Minor', NULL, 
 'Disrespect towards clients, relatives, personal tenants, superior officers, and visitors.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-17', 'Disobeying lawful orders from client, client''s authorized representatives.', 'Minor', NULL, 
 'Disobeying lawful orders from clients or client''s authorized representatives.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('MIN-18', 'Allowing client''s or tenant''s property to be brought out without the necessary gate pass or clearance.', 'Minor', NULL, 
 'Allowing client''s or tenant''s property to be brought out without necessary gate pass or clearance.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('MIN-19', 'Borrowing money and anything of value convertible to cash from client and/or client representative and tenants thereby damaging the company''s good reputations.', 'Minor', NULL, 
 'Borrowing money or anything of value convertible to cash from clients, client representatives, or tenants, damaging company reputation.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-20', 'Cooking, washing clothes, bathing, and overnight staying without permission from the client or the company.', 'Minor', NULL, 
 'Cooking, washing clothes, bathing, or overnight staying without permission from client or company.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('MIN-21', 'Smoking inside the company premises except in the designated places for smoking.', 'Minor', NULL, 
 'Smoking inside company premises except in designated smoking areas.', 
 'Written reprimand', '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', 1, 1),

('MIN-22', 'Unauthorized use of company''s telephone for national and overseas long distance call and other personal calls, company equipment and other company properties.', 'Minor', NULL, 
 'Unauthorized use of company telephone for long distance calls, personal calls, or company equipment and properties.', 
 '15 days suspension & Payment for the entire billing acquired', '30 days suspension & Payment for the entire billing acquired', 'Payment for the entire billing acquired & Dismissal', NULL, NULL, 1, 1),

('MIN-23', 'Unauthorized tampering of co-employee''s pay envelope for the purpose of retrieving liabilities and obligation.', 'Minor', NULL, 
 'Unauthorized tampering with co-employee''s pay envelope to retrieve liabilities and obligations.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-24', 'Reporting to work/duty with incomplete paraphernalia and uniform.', 'Minor', NULL, 
 'Reporting to work or duty with incomplete paraphernalia and uniform.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-25', 'Failure to attend company meeting, guard mounting, general formation, training activities, and other invitations for human development without justifiable reason/s.', 'Minor', NULL, 
 'Failure to attend company meetings, guard mounting, general formation, training activities, or human development invitations without justifiable reasons.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-26', 'Failure to report for duty without notifying the duty officer/direct superior.', 'Minor', NULL, 
 'Failure to report for duty without notifying the duty officer or direct superior.', 
 '3 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-27', 'Entering the office, house of the client and management staff personnel without proper authority and/or legitimate transaction/purpose.', 'Minor', NULL, 
 'Entering the office or house of clients and management staff personnel without proper authority or legitimate transaction/purpose.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('MIN-28', 'Reporting to the assigned post without attending the required guard mounting.', 'Minor', NULL, 
 'Reporting to assigned post without attending required guard mounting.', 
 '3 days suspension', '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', 1, 1),

('MIN-29', 'Failure to wear company ID card inside the company premises.', 'Minor', NULL, 
 'Failure to wear company ID card inside company premises.', 
 'Written reprimand', '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', 1, 1),

('MIN-30', 'Repeated or deliberate slow-down of work.', 'Minor', NULL, 
 'Repeated or deliberate slow-down of work.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1);

-- ============================================================================
-- INSERT RA 5487 OFFENSES (A. Security Guard Creed, B. Code of Conduct, C. Code of Ethics, D. Eleven General Orders)
-- ============================================================================
INSERT INTO `violation_types` (`reference_no`, `name`, `category`, `subcategory`, `description`, `first_offense`, `second_offense`, `third_offense`, `fourth_offense`, `fifth_offense`, `ra5487_compliant`, `is_active`) VALUES

-- A. Security Guard Creed
('A.1', 'As a security guard my fundamental duty is to protect lives and property and maintain order within my place of duty; protect the interest of my employer and our clients and the security and stability of our government and country without compromise and prejudice, honest in my action, words and thought; and do my best to uphold the principle: MAKADIOS, MAKABAYAN, MAKATAO at MAKAKALIKASAN.', 'Major', 'A. Security Guard Creed', 
 'Violation of the fundamental duty to protect lives and property, maintain order, and uphold MAKADIOS, MAKABAYAN, MAKATAO at MAKAKALIKASAN.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

-- B. Code of Conduct
('B.1', 'He shall carry with him at all times during his tour of duty his license, identification card and duty detail order with an authority to carry firearm.', 'Major', 'B. Code of Conduct', 
 'Failure to carry license, identification card, and duty detail order with authority to carry firearm during tour of duty.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('B.2', 'He shall not use his license and other privileges if any, to be prejudice of the public, the client or customer and his agency.', 'Major', 'B. Code of Conduct', 
 'Using license and privileges to the prejudice of the public, client, customer, or agency.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('B.3', 'He shall not engage in any unnecessary conversation with any, to the prejudice of the public, the client or customer and his agency.', 'Major', 'B. Code of Conduct', 
 'Engaging in unnecessary conversations to the prejudice of the public, client, customer, or agency.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('B.4', 'He shall refrain from reading newspapers, magazines, books, etc, while actually performing his duties.', 'Major', 'B. Code of Conduct', 
 'Reading newspapers, magazines, books, etc. while actually performing duties.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('B.5', 'He shall not drink any intoxicating liquor immediately before and during his tour of duty.', 'Major', 'B. Code of Conduct', 
 'Drinking intoxicating liquor immediately before or during tour of duty.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('B.6', 'He shall know the location of the alarm box near his post and sound the alarm in case of fire or disorder.', 'Major', 'B. Code of Conduct', 
 'Failure to know location of alarm box near post and sound alarm in case of fire or disorder.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('B.7', 'He shall know how to operate any fire extinguisher at his post.', 'Major', 'B. Code of Conduct', 
 'Failure to know how to operate fire extinguisher at post.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('B.8', 'He shall know the location of the telephone and/or telephone number of the police precincts as well as the telephone numbers of the fire stations in the locality.', 'Major', 'B. Code of Conduct', 
 'Failure to know location of telephone or telephone numbers of police precincts and fire stations in the locality.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('B.9', 'He shall immediately notify the police in case of any sign of disorder, strike, riot, or any serious violation of the law.', 'Major', 'B. Code of Conduct', 
 'Failure to immediately notify police in case of disorder, strike, riot, or serious violation of the law.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('B.10', 'He or his group of guards, shall not participate or integrate any disorder, strike, riot, or any serious violations of the law.', 'Major', 'B. Code of Conduct', 
 'Participating or integrating in disorder, strike, riot, or serious violations of the law.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('B.11', 'He shall assist the police in the preservation and maintenance of peace and order and in the protection of life and property having in mind that the nature of his responsibilities is similar to that of the latter.', 'Major', 'B. Code of Conduct', 
 'Failure to assist police in preservation and maintenance of peace and order and protection of life and property.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('B.12', 'He shall familiarize himself by heart with the Private Security Agency Law (RA 5487, as amended) and these implementing rules and regulations.', 'Major', 'B. Code of Conduct', 
 'Failure to familiarize by heart with RA 5487 and implementing rules and regulations.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('B.13', 'When issued a Firearms he should not lend his Firearms to anybody.', 'Major', 'B. Code of Conduct', 
 'Lending issued firearms to anybody.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('B.14', 'He shall always be in proper uniform and shall always carry with him his basic requirements, and equipment''s such as writing notebook, ballpen, night stick (baton) and/or radio.', 'Major', 'B. Code of Conduct', 
 'Failure to be in proper uniform or carry basic requirements and equipment such as writing notebook, ballpen, night stick (baton), and/or radio.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('B.15', 'He shall endeavor at all times, to merit and be worthy of the trust and confidence of the agency he represents and the client he serves.', 'Major', 'B. Code of Conduct', 
 'Failure to merit and be worthy of the trust and confidence of the agency and client.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

-- C. Code of Ethics
('C.1', 'As a security guard/detective his fundamental duty is to serve the interest or mission of his agency in compliance with the contract entered into with clients or customers of the agency he is supposed to serve.', 'Major', 'C. Code of Ethics', 
 'Failure to serve the interest or mission of agency in compliance with contract entered into with clients or customers.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('C.2', 'He shall be honest in thoughts and deeds both in his personal and official actuations, obeying the laws of the land and the regulations prescribed by his agency and those established by the company he is supposed to protect.', 'Major', 'C. Code of Ethics', 
 'Failure to be honest in thoughts and deeds, or failure to obey laws and regulations.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('C.3', 'He shall not reveal any confidential information confided to him as security guard and such other matters imposed upon him by law.', 'Major', 'C. Code of Ethics', 
 'Revealing confidential information confided as security guard or matters imposed by law.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('C.4', 'He shall act at all times with decorum and shall not permit personal feelings, prejudices and undue friendship to influence his actuation while in the performance of his functions.', 'Major', 'C. Code of Ethics', 
 'Failure to act with decorum or allowing personal feelings, prejudices, or undue friendship to influence performance.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('C.5', 'He shall not compromise with criminals and other lawless elements to the prejudice of the customers or clients and shall assist the government in its relentless drive against lawlessness and other forms of criminality.', 'Major', 'C. Code of Ethics', 
 'Compromising with criminals and lawless elements to the prejudice of customers or clients, or failure to assist government against lawlessness.', 
 'Dismissal', NULL, NULL, NULL, NULL, 1, 1),

('C.6', 'He shall carry out his assigned duties as required by law to the best of his ability and shall safeguard the life and property of the establishment he is assigned to.', 'Major', 'C. Code of Ethics', 
 'Failure to carry out assigned duties to the best of ability or safeguard life and property of assigned establishment.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('C.7', 'He shall wear his uniform, badge, patches, and insignia properly as a symbol of public trust and confidence, as an honest and trustworthy security guard and private detectives.', 'Major', 'C. Code of Ethics', 
 'Failure to wear uniform, badge, patches, and insignia properly as symbol of public trust and confidence.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('C.8', 'He shall keep his allegiance first to the government, then to the agency where is employed and to the establishment he is assigned to serve with loyalty and utmost dedication.', 'Major', 'C. Code of Ethics', 
 'Failure to keep allegiance first to government, then to agency, and to assigned establishment with loyalty and dedication.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('C.9', 'He shall diligently and progressively familiarize himself with the rules and regulations laid down by his agency and those of the customers or clients.', 'Major', 'C. Code of Ethics', 
 'Failure to diligently and progressively familiarize with rules and regulations of agency and customers or clients.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('C.10', 'He shall at all times be courteous, respectful and salute his superior officers.', 'Major', 'C. Code of Ethics', 
 'Failure to be courteous, respectful, and salute superior officers at all times.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('C.11', 'He shall report to duty always in proper uniform and neat in his appearance.', 'Major', 'C. Code of Ethics', 
 'Failure to report to duty in proper uniform and neat appearance.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('C.12', 'He shall learn at heart and strictly observe the laws and regulations governing the use of firearms.', 'Major', 'C. Code of Ethics', 
 'Failure to learn at heart and strictly observe laws and regulations governing the use of firearms.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

-- D. Eleven General Orders
('D.1', 'To protect life and properties and to protect/preserve the same with utmost diligence.', 'Major', 'D. Eleven General Orders', 
 'Failure to protect life and properties and protect/preserve the same with utmost diligence.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('D.2', 'To walk in an alert manner during my tour of duty and observe everything within sight or hearing.', 'Major', 'D. Eleven General Orders', 
 'Failure to walk in alert manner during tour of duty and observe everything within sight or hearing.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('D.3', 'To report all violations of regulations or orders I am instructed to enforce.', 'Major', 'D. Eleven General Orders', 
 'Failure to report all violations of regulations or orders instructed to enforce.', 
 '15 days suspension', NULL, NULL, NULL, NULL, 1, 1),

('D.4', 'To relay all calls from more distant from the guard house where I am stationed.', 'Major', 'D. Eleven General Orders', 
 'Failure to relay all calls from more distant from the guard house where stationed.', 
 '7 days suspension', '15 days suspension', '30 days suspension', 'Dismissal', NULL, 1, 1),

('D.5', 'To quit my post only when properly relieved.', 'Major', 'D. Eleven General Orders', 
 'Quitting post without being properly relieved.', 
 '30 days suspension', 'Dismissal', NULL, NULL, NULL, 1, 1),

('D.6', 'To receive, obey and pass to the relieving guard all orders from the company officials, officers in the agency, supervisor, post in charge of shift leaders.', 'Major', 'D. Eleven General Orders', 
 'Failure to receive, obey, and pass to relieving guard all orders from company officials, agency officers, supervisor, post in charge, or shift leaders.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('D.7', 'To talk to no one except in the line of duty.', 'Major', 'D. Eleven General Orders', 
 'Talking to someone not in the line of duty.', 
 '7 days suspension', '15 days suspension', '30 days suspension', NULL, NULL, 1, 1),

('D.8', 'To sound or call the alarm in case of fire or disorder.', 'Major', 'D. Eleven General Orders', 
 'Failure to sound or call alarm in case of fire or disorder.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('D.9', 'To call the superior officer in any case not covered by the instructions.', 'Major', 'D. Eleven General Orders', 
 'Failure to call superior officer in any case not covered by instructions.', 
 '15 days suspension', '30 days suspension', 'Dismissal', NULL, NULL, 1, 1),

('D.10', 'To salute all company official, officers of the agency, ranking public officials and officers of the AFP and PNP.', 'Major', 'D. Eleven General Orders', 
 'Failure to salute company officials, agency officers, ranking public officials, and officers of AFP and PNP.', 
 '7 days suspension', '15 days suspension', '30 days suspension', NULL, NULL, 1, 1),

('D.11', 'To be especially watchful at night, and during the time of challenging and to challenge all person on or near my post and allow no one to enter or pass on without proper authority.', 'Major', 'D. Eleven General Orders', 
 'Failure to be especially watchful at night, challenge all persons on or near post, or allow entry/passage without proper authority.', 
 '7 days suspension', '15 days suspension', '30 days suspension', NULL, NULL, 1, 1);

-- ============================================================================
-- STANDARDIZE REFERENCE NUMBERS FOR CONSISTENCY
-- ============================================================================
-- This section ensures Major and Minor violations have sequential reference numbers
-- Major violations use MAJ- prefix (MAJ-1, MAJ-2, etc.)
-- Minor violations use MIN- prefix (MIN-1, MIN-2, etc.)
-- RA 5487 offense reference numbers are preserved exactly as per legal documentation
-- Compatible with MySQL 5.7+ and MariaDB 10.2+
-- ============================================================================

-- Renumber Major Violations to Sequential MAJ-1, MAJ-2, MAJ-3... MAJ-28
SET @row_number = 0;

UPDATE `violation_types` AS vt
INNER JOIN (
    SELECT 
        id,
        (@row_number := @row_number + 1) AS new_ref_no
    FROM `violation_types`
    WHERE category = 'Major' 
    AND (subcategory IS NULL OR subcategory = '')
    AND is_active = 1
    ORDER BY 
        CASE 
            WHEN reference_no LIKE 'MAJ-%' THEN 
                CAST(SUBSTRING_INDEX(reference_no, '-', -1) AS UNSIGNED)
            WHEN reference_no REGEXP '^[0-9]+$' THEN CAST(reference_no AS UNSIGNED)
            ELSE 999
        END,
        reference_no,
        id
) AS numbered
ON vt.id = numbered.id
SET vt.reference_no = CONCAT('MAJ-', CAST(numbered.new_ref_no AS CHAR))
WHERE vt.category = 'Major' 
AND (vt.subcategory IS NULL OR vt.subcategory = '');

-- Renumber Minor Violations to Sequential MIN-1, MIN-2... MIN-30
SET @row_number = 0;

UPDATE `violation_types` AS vt
INNER JOIN (
    SELECT 
        id,
        (@row_number := @row_number + 1) AS new_ref_no
    FROM `violation_types`
    WHERE category = 'Minor' 
    AND (subcategory IS NULL OR subcategory = '')
    AND is_active = 1
    ORDER BY 
        CASE 
            WHEN reference_no LIKE 'MIN-%' THEN 
                CAST(SUBSTRING_INDEX(reference_no, '-', -1) AS UNSIGNED)
            ELSE 999
        END,
        reference_no,
        id
) AS numbered
ON vt.id = numbered.id
SET vt.reference_no = CONCAT('MIN-', CAST(numbered.new_ref_no AS CHAR))
WHERE vt.category = 'Minor' 
AND (vt.subcategory IS NULL OR vt.subcategory = '');

-- ============================================================================
-- FINAL UPDATES
-- ============================================================================

-- Update existing violation_types to mark non-RA5487 violations
UPDATE `violation_types` 
SET `ra5487_compliant` = 0 
WHERE (`ra5487_compliant` IS NULL OR `ra5487_compliant` = 0)
AND (`reference_no` IS NULL OR `reference_no` NOT IN (
    SELECT reference_no FROM (SELECT reference_no FROM `violation_types` WHERE `ra5487_compliant` = 1) AS temp
));

-- Add comment to table
ALTER TABLE `violation_types` 
COMMENT = 'Violation types including RA 5487 Major Violations, Minor Violations, and RA 5487 Offenses with progressive sanctions';
