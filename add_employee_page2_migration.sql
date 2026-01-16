-- Migration: Add Page 2 Employee Application Form Fields to employees table
-- Date: 2024
-- Description: Adds all fields from Add Employee Page 2 form to the employees table

-- General Information Section
ALTER TABLE `employees` 
ADD COLUMN `vacancy_source` TEXT NULL COMMENT 'How did you know of the vacancy (JSON array: Ads, Walk-in, Referral)' AFTER `religion`,
ADD COLUMN `referral_name` VARCHAR(150) NULL COMMENT 'Referral name if vacancy source is Referral' AFTER `vacancy_source`,
ADD COLUMN `knows_agency_person` ENUM('Yes', 'No') NULL COMMENT 'Do you know anyone from the agency?' AFTER `referral_name`,
ADD COLUMN `agency_person_name` VARCHAR(200) NULL COMMENT 'Name and relationship with agency person' AFTER `knows_agency_person`,
ADD COLUMN `physical_defect` ENUM('Yes', 'No') NULL COMMENT 'Do you have any physical defects or chronic ailments?' AFTER `agency_person_name`,
ADD COLUMN `physical_defect_specify` TEXT NULL COMMENT 'Specify physical defects if yes' AFTER `physical_defect`,
ADD COLUMN `drives` ENUM('Yes', 'No') NULL COMMENT 'Do you drive?' AFTER `physical_defect_specify`,
ADD COLUMN `drivers_license_no` VARCHAR(50) NULL COMMENT 'Driver license number' AFTER `drives`,
ADD COLUMN `drivers_license_exp` VARCHAR(50) NULL COMMENT 'Driver license expiration date' AFTER `drivers_license_no`,
ADD COLUMN `drinks_alcohol` ENUM('Yes', 'No') NULL COMMENT 'Do you drink alcoholic beverages?' AFTER `drivers_license_exp`,
ADD COLUMN `alcohol_frequency` VARCHAR(100) NULL COMMENT 'How frequent do you drink alcohol?' AFTER `drinks_alcohol`,
ADD COLUMN `prohibited_drugs` ENUM('Yes', 'No') NULL COMMENT 'Are you taking prohibited drugs?' AFTER `alcohol_frequency`,
ADD COLUMN `security_guard_experience` VARCHAR(100) NULL COMMENT 'How long have you worked as a Security Guard?' AFTER `prohibited_drugs`,
ADD COLUMN `convicted` ENUM('Yes', 'No') NULL COMMENT 'Have you ever been convicted of any offense?' AFTER `security_guard_experience`,
ADD COLUMN `conviction_details` TEXT NULL COMMENT 'Specify conviction details if yes' AFTER `convicted`,
ADD COLUMN `filed_case` ENUM('Yes', 'No') NULL COMMENT 'Have you filed any criminal/civil case against previous employer?' AFTER `conviction_details`,
ADD COLUMN `case_specify` TEXT NULL COMMENT 'Specify case details if yes' AFTER `filed_case`,
ADD COLUMN `action_after_termination` TEXT NULL COMMENT 'What was your action after termination?' AFTER `case_specify`;

-- Specimen Signature and Initial Section
ALTER TABLE `employees`
ADD COLUMN `signature_1` VARCHAR(200) NULL COMMENT 'Specimen signature line 1' AFTER `action_after_termination`,
ADD COLUMN `signature_2` VARCHAR(200) NULL COMMENT 'Specimen signature line 2' AFTER `signature_1`,
ADD COLUMN `signature_3` VARCHAR(200) NULL COMMENT 'Specimen signature line 3' AFTER `signature_2`,
ADD COLUMN `initial_1` VARCHAR(100) NULL COMMENT 'Specimen initial 1 (Pinakiling Pirma)' AFTER `signature_3`,
ADD COLUMN `initial_2` VARCHAR(100) NULL COMMENT 'Specimen initial 2 (Pinakiling Pirma)' AFTER `initial_1`,
ADD COLUMN `initial_3` VARCHAR(100) NULL COMMENT 'Specimen initial 3 (Pinakiling Pirma)' AFTER `initial_2`;

-- Fingerprints Section (file paths)
ALTER TABLE `employees`
ADD COLUMN `fingerprint_right_thumb` VARCHAR(255) NULL COMMENT 'Right thumb fingerprint file path' AFTER `initial_3`,
ADD COLUMN `fingerprint_right_index` VARCHAR(255) NULL COMMENT 'Right index finger fingerprint file path' AFTER `fingerprint_right_thumb`,
ADD COLUMN `fingerprint_right_middle` VARCHAR(255) NULL COMMENT 'Right middle finger fingerprint file path' AFTER `fingerprint_right_index`,
ADD COLUMN `fingerprint_right_ring` VARCHAR(255) NULL COMMENT 'Right ring finger fingerprint file path' AFTER `fingerprint_right_middle`,
ADD COLUMN `fingerprint_right_little` VARCHAR(255) NULL COMMENT 'Right little finger fingerprint file path' AFTER `fingerprint_right_ring`,
ADD COLUMN `fingerprint_left_thumb` VARCHAR(255) NULL COMMENT 'Left thumb fingerprint file path' AFTER `fingerprint_right_little`,
ADD COLUMN `fingerprint_left_index` VARCHAR(255) NULL COMMENT 'Left index finger fingerprint file path' AFTER `fingerprint_left_thumb`,
ADD COLUMN `fingerprint_left_middle` VARCHAR(255) NULL COMMENT 'Left middle finger fingerprint file path' AFTER `fingerprint_left_index`,
ADD COLUMN `fingerprint_left_ring` VARCHAR(255) NULL COMMENT 'Left ring finger fingerprint file path' AFTER `fingerprint_left_middle`,
ADD COLUMN `fingerprint_left_little` VARCHAR(255) NULL COMMENT 'Left little finger fingerprint file path' AFTER `fingerprint_left_ring`;

-- Basic Requirements Section
ALTER TABLE `employees`
ADD COLUMN `requirements_signature` VARCHAR(200) NULL COMMENT 'Signature over printed name for requirements section' AFTER `fingerprint_left_little`,
ADD COLUMN `req_2x2` ENUM('YO', 'NO') NULL COMMENT '2x2 photos provided (YO/NO)' AFTER `requirements_signature`,
ADD COLUMN `req_birth_cert` ENUM('YO', 'NO') NULL COMMENT 'NSO/Birth Certificate provided (YO/NO)' AFTER `req_2x2`,
ADD COLUMN `req_barangay` ENUM('YO', 'NO') NULL COMMENT 'Barangay Clearance provided (YO/NO)' AFTER `req_birth_cert`,
ADD COLUMN `req_police` ENUM('YO', 'NO') NULL COMMENT 'Police Clearance provided (YO/NO)' AFTER `req_barangay`,
ADD COLUMN `req_nbi` ENUM('YO', 'NO') NULL COMMENT 'NBI Clearance provided (YO/NO)' AFTER `req_police`,
ADD COLUMN `req_di` ENUM('YO', 'NO') NULL COMMENT 'D.I. Clearance provided (YO/NO)' AFTER `req_nbi`,
ADD COLUMN `req_diploma` ENUM('YO', 'NO') NULL COMMENT 'High School/College Diploma provided (YO/NO)' AFTER `req_di`,
ADD COLUMN `req_neuro_drug` ENUM('YO', 'NO') NULL COMMENT 'Neuro & Drug test result provided (YO/NO)' AFTER `req_diploma`,
ADD COLUMN `req_sec_license` ENUM('YO', 'NO') NULL COMMENT 'Sec.License Certificate from SOSIA provided (YO/NO)' AFTER `req_neuro_drug`,
ADD COLUMN `sec_lic_no` VARCHAR(50) NULL COMMENT 'Security License Number for ID copy' AFTER `req_sec_license`,
ADD COLUMN `req_sec_lic_no` ENUM('YO', 'NO') NULL COMMENT 'Sec.Lic.No. ID copy provided (YO/NO)' AFTER `sec_lic_no`,
ADD COLUMN `req_sss` ENUM('YO', 'NO') NULL COMMENT 'SSS No. ID copy provided (YO/NO)' AFTER `req_sec_lic_no`,
ADD COLUMN `req_pagibig` ENUM('YO', 'NO') NULL COMMENT 'Pag-Ibig No. ID copy provided (YO/NO)' AFTER `req_sss`,
ADD COLUMN `req_philhealth` ENUM('YO', 'NO') NULL COMMENT 'PhilHealth No. ID copy provided (YO/NO)' AFTER `req_pagibig`,
ADD COLUMN `req_tin` ENUM('YO', 'NO') NULL COMMENT 'TIN No. ID copy provided (YO/NO)' AFTER `req_philhealth`;

-- Sworn Statement Section
ALTER TABLE `employees`
ADD COLUMN `sworn_day` VARCHAR(10) NULL COMMENT 'Sworn statement day' AFTER `req_tin`,
ADD COLUMN `sworn_month` VARCHAR(50) NULL COMMENT 'Sworn statement month' AFTER `sworn_day`,
ADD COLUMN `sworn_year` VARCHAR(10) NULL COMMENT 'Sworn statement year' AFTER `sworn_month`,
ADD COLUMN `tax_cert_no` VARCHAR(100) NULL COMMENT 'Tax Certificate Number' AFTER `sworn_year`,
ADD COLUMN `tax_cert_issued_at` VARCHAR(200) NULL COMMENT 'Tax Certificate issued at location' AFTER `tax_cert_no`,
ADD COLUMN `sworn_signature` VARCHAR(200) NULL COMMENT 'Signature over printed name for sworn statement' AFTER `tax_cert_issued_at`,
ADD COLUMN `affiant_community` VARCHAR(200) NULL COMMENT 'Affiant exhibited community' AFTER `sworn_signature`;

-- Form Footer Section
ALTER TABLE `employees`
ADD COLUMN `doc_no` VARCHAR(50) NULL COMMENT 'Document Number' AFTER `affiant_community`,
ADD COLUMN `page_no` VARCHAR(10) NULL COMMENT 'Page Number' AFTER `doc_no`,
ADD COLUMN `book_no` VARCHAR(50) NULL COMMENT 'Book Number' AFTER `page_no`,
ADD COLUMN `series_of` VARCHAR(50) NULL COMMENT 'Series of' AFTER `book_no`;

-- Add index for commonly queried fields
ALTER TABLE `employees` ADD INDEX `idx_convicted` (`convicted`);
ALTER TABLE `employees` ADD INDEX `idx_filed_case` (`filed_case`);
ALTER TABLE `employees` ADD INDEX `idx_prohibited_drugs` (`prohibited_drugs`);
