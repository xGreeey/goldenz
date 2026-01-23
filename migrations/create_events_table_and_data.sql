-- Create events table for Academic Calendar
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `start_date` DATE NOT NULL,
  `start_time` TIME NULL,
  `end_date` DATE NULL,
  `end_time` TIME NULL,
  `event_type` ENUM('Holiday', 'Examination', 'Academic', 'Special Event', 'Other') DEFAULT 'Other',
  `holiday_type` ENUM('Regular Holiday', 'Special Non-Working Holiday', 'Local Special Non-Working Holiday', 'N/A') DEFAULT 'N/A',
  `category` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_start_date` (`start_date`),
  INDEX `idx_end_date` (`end_date`),
  INDEX `idx_event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Academic Calendar Events for School Year 2025-2026
-- Second Semester: January 5, 2026 - May 9, 2026

-- January 5, 2026, Monday - First day of Classes / First day of OJT / Internship
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('First day of Classes', 'Date: January 5, 2026, Monday. First day of OJT / Internship. Second Semester begins for School Year 2025-2026. All classes and OJT/Internship programs commence on this date.', '2026-01-05', '08:00:00', '2026-01-05', '17:00:00', 'Academic', 'N/A', 'Semester Start', 'Second Semester begins. Regular class schedule and OJT/Internship programs start.');

-- January 30, 2026, Friday - Research Colloquium / Dunong Laya 2025
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Research Colloquium', 'Date: January 30, 2026, Friday. Research Colloquium - Dunong Laya 2025. Students will be absent for OJT/Internship on this day. All students are required to attend the Research Colloquium.', '2026-01-30', '08:00:00', '2026-01-30', '17:00:00', 'Special Event', 'N/A', 'Academic Event', 'Students absent for OJT. Research presentations and academic activities.');

-- February 9, 2026, Monday - Liberation Day of Mandaluyong City
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Liberation Day of Mandaluyong City', 'Date: February 9, 2026, Monday. Local Special Non-Working Holiday (Mandaluyong City only). This holiday applies only to Mandaluyong City. Classes and OJT may be affected for students in Mandaluyong City.', '2026-02-09', '00:00:00', '2026-02-09', '23:59:59', 'Holiday', 'Local Special Non-Working Holiday', 'Local Holiday', 'Mandaluyong City only. Students from Mandaluyong City are excused from classes and OJT.');

-- February 9-15, 2026, Monday-Saturday - University Week
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('University Week', 'Date: February 9-15, 2026, Monday-Saturday. University Week celebration. Week-long activities and events. Various activities and events will be held throughout the week.', '2026-02-09', '08:00:00', '2026-02-15', '17:00:00', 'Special Event', 'N/A', 'University Event', 'Week-long celebration. Special activities, competitions, and events scheduled.');

-- February 17, 2026, Tuesday - Chinese New Year
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Chinese New Year', 'Date: February 17, 2026, Tuesday. Special Non-Working Holiday. National holiday observed across the Philippines. No classes and OJT on this day. All academic activities are suspended.', '2026-02-17', '00:00:00', '2026-02-17', '23:59:59', 'Holiday', 'Special Non-Working Holiday', 'National Holiday', 'No classes and OJT. All academic activities are suspended.');

-- February 16, 18-21, 2026, Monday, Wednesday-Saturday - Preliminary Examination
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Preliminary Examination', 'Date: February 16, 18-21, 2026, Monday, Wednesday-Saturday. Preliminary Examination period. All students are required to take examinations. Regular classes are suspended during examination period.', '2026-02-16', '08:00:00', '2026-02-21', '17:00:00', 'Examination', 'N/A', 'Academic', 'Examination schedule: Monday (Feb 16), Wednesday-Saturday (Feb 18-21). All students must complete preliminary examinations.');

-- February 25, 2026, Wednesday - EDSA Revolution
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('EDSA Revolution', 'Date: February 25, 2026, Wednesday. Special Non-Working Holiday. Commemorates the People Power Revolution. No classes and OJT on this day. All academic activities are suspended.', '2026-02-25', '00:00:00', '2026-02-25', '23:59:59', 'Holiday', 'Special Non-Working Holiday', 'National Holiday', 'No classes and OJT. All academic activities are suspended.');

-- March 20, 2026, Friday - Eid-Ul Fitr
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Eid-Ul Fitr', 'Date: March 20, 2026, Friday. Regular Holiday. Islamic holiday marking the end of Ramadan. No classes and OJT on this day. All academic activities are suspended.', '2026-03-20', '00:00:00', '2026-03-20', '23:59:59', 'Holiday', 'Regular Holiday', 'National Holiday', 'No classes and OJT. All academic activities are suspended.');

-- March 23-28, 2026, Monday-Saturday - Midterm Examinations
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Midterm Examinations', 'Date: March 23-28, 2026, Monday-Saturday. Midterm Examination period. All students are required to take midterm examinations. Regular classes are suspended during midterm examination period.', '2026-03-23', '08:00:00', '2026-03-28', '17:00:00', 'Examination', 'N/A', 'Academic', 'All students must complete their midterm examinations. Regular classes are suspended.');

-- April 2-4, 2026, Thursday-Saturday - Holy Week
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Maundy Thursday', 'Date: April 2, 2026, Thursday. Regular Holiday. Maundy Thursday – Regular Holiday. Part of Holy Week observance. No classes and OJT. All academic activities are suspended.', '2026-04-02', '00:00:00', '2026-04-02', '23:59:59', 'Holiday', 'Regular Holiday', 'Religious Holiday', 'Holy Week - No classes and OJT. All academic activities are suspended.'),
('Good Friday', 'Date: April 3, 2026, Friday. Regular Holiday. Good Friday – Regular Holiday. Part of Holy Week observance. No classes and OJT. All academic activities are suspended.', '2026-04-03', '00:00:00', '2026-04-03', '23:59:59', 'Holiday', 'Regular Holiday', 'Religious Holiday', 'Holy Week - No classes and OJT. All academic activities are suspended.'),
('Black Saturday', 'Date: April 4, 2026, Saturday. Special Non-Working Holiday. Black Saturday – Special Non-Working Holiday. Part of Holy Week observance. No classes and OJT. All academic activities are suspended.', '2026-04-04', '00:00:00', '2026-04-04', '23:59:59', 'Holiday', 'Special Non-Working Holiday', 'Religious Holiday', 'Holy Week - No classes and OJT. All academic activities are suspended.');

-- April 9, 2026, Thursday - Araw ng Kagitingan
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Araw ng Kagitingan', 'Date: April 9, 2026, Thursday. Regular Holiday. Also known as Day of Valor, commemorating the Fall of Bataan. No classes and OJT on this day. All academic activities are suspended.', '2026-04-09', '00:00:00', '2026-04-09', '23:59:59', 'Holiday', 'Regular Holiday', 'National Holiday', 'No classes and OJT. All academic activities are suspended.');

-- April 23-25, 2026, Thursday-Saturday - Final Examinations (Graduating)
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Final Examinations (Graduating)', 'Date: April 23-25, 2026, Thursday-Saturday. Final Examination period for graduating students only. Regular classes are suspended for graduating students during this period. Non-graduating students continue with regular classes.', '2026-04-23', '08:00:00', '2026-04-25', '17:00:00', 'Examination', 'N/A', 'Academic', 'For graduating students only. All graduating students must complete their final examinations.');

-- May 1, 2026, Friday - Labor Day
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Labor Day', 'Date: May 1, 2026, Friday. Regular Holiday. International Workers Day. No classes and OJT on this day. All academic activities are suspended.', '2026-05-01', '00:00:00', '2026-05-01', '23:59:59', 'Holiday', 'Regular Holiday', 'National Holiday', 'No classes and OJT. All academic activities are suspended.');

-- May 4-9, 2026, Monday-Saturday - Final Examinations (Non-graduating)
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Final Examinations (Non-graduating)', 'Date: May 4-9, 2026, Monday-Saturday. Final Examination period for non-graduating students. All non-graduating students are required to take final examinations. Regular classes are suspended during this examination period.', '2026-05-04', '08:00:00', '2026-05-09', '17:00:00', 'Examination', 'N/A', 'Academic', 'For non-graduating students only. All non-graduating students must complete their final examinations.');

-- June 21, 2026, Sunday - Commencement Exercises
INSERT INTO `events` (`title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `event_type`, `holiday_type`, `category`, `notes`) VALUES
('Commencement Exercises', 'Date: June 21, 2026, Sunday. Graduation ceremony for School Year 2025-2026. All graduating students and their families are invited to attend. Formal graduation ceremony.', '2026-06-21', '08:00:00', '2026-06-21', '17:00:00', 'Special Event', 'N/A', 'Academic Event', 'Formal graduation ceremony. Venue and detailed schedule to be announced.');
