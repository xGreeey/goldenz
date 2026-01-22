# Golden Z-5 HR System - Database Documentation

**Last Updated:** January 2026  
**Database Name:** `goldenz_hr`

This document tracks all database tables, migrations, and their purposes in the Golden Z-5 HR Management System.

---

## 📋 Table of Contents

1. [Core Tables](#core-tables)
2. [HR Management Tables](#hr-management-tables)
3. [Leave Management](#leave-management)
4. [Violation Management](#violation-management)
5. [Attendance Management](#attendance-management)
6. [Document Management](#document-management)
7. [Notification System](#notification-system)
8. [Migration History](#migration-history)

---

## Core Tables

These are the foundational tables that exist in the main `goldenz_hr.sql` file:

### Users & Authentication
- **`users`** - User accounts for system access
  - Stores login credentials, roles, and user information
  - Links to employees table for HR data

### Employee Management
- **`employees`** - Core employee information
  - Personal details, employment status, contact information
  - Links to all other employee-related tables

### Other Core Tables
- **`posts`** - Job positions/designations
- **`teams`** - Department/team structures
- **`alerts`** - System alerts and announcements
- **`tasks`** - Task management
- **`system_logs`** - System activity logging

---

## HR Management Tables

### Employee-Related
- **`employees`** (Core Table)
  - Employee personal information
  - Employment details (date_hired, status, etc.)
  - License information (license_no, license_exp_date)
  - Contact and address information

---

## Leave Management

**Migration File:** `add_leave_and_violation_tables.sql`

### Tables Created:

#### 1. `leave_requests`
- **Purpose:** Tracks employee leave requests
- **Key Fields:**
  - `employee_id` - Links to employees table
  - `leave_type` - Type of leave (sick, vacation, emergency)
  - `start_date`, `end_date` - Leave period
  - `days` - Number of leave days
  - `status` - pending, approved, rejected
  - `processed_by` - User who processed the request
  - `approval_notes`, `rejection_notes` - Processing notes
- **Relationships:** Foreign key to `employees` table

#### 2. `leave_balances`
- **Purpose:** Tracks annual leave balances per employee
- **Key Fields:**
  - `employee_id` - Links to employees table
  - `year` - Year for the balance
  - `sick_leave_total`, `sick_leave_used` - Sick leave tracking
  - `vacation_leave_total`, `vacation_leave_used` - Vacation leave tracking
  - `emergency_leave_total`, `emergency_leave_used` - Emergency leave tracking
- **Relationships:** Foreign key to `employees` table
- **Unique Constraint:** One record per employee per year

---

## Violation Management

**Migration Files:** 
- `add_leave_and_violation_tables.sql` (initial structure)
- `add_complete_ra5487_violations.sql` (Complete RA 5487 violations system)

### Tables Created:

#### 1. `violation_types`
- **Purpose:** Defines types of violations with progressive sanctions
- **Key Fields:**
  - `id` - Primary key
  - `reference_no` - Reference number (e.g., "1", "MIN-1", "A.1", "B.1")
  - `name` - Violation name/description
  - `category` - Major or Minor
  - `subcategory` - RA 5487 subcategory (A, B, C, D)
  - `description` - Detailed description
  - `first_offense` - Sanction for 1st offense
  - `second_offense` - Sanction for 2nd offense
  - `third_offense` - Sanction for 3rd offense
  - `fourth_offense` - Sanction for 4th offense
  - `fifth_offense` - Sanction for 5th offense
  - `ra5487_compliant` - Flag indicating RA 5487 compliance
  - `is_active` - Whether the violation type is active
- **Total Violations:** 97
  - **28 Major Violations** (Reference: 1-28)
  - **30 Minor Violations** (Reference: MIN-1 to MIN-30)
  - **39 RA 5487 Offenses:**
    - A. Security Guard Creed: 1 violation (A.1)
    - B. Code of Conduct: 15 violations (B.1-B.15)
    - C. Code of Ethics: 12 violations (C.1-C.12)
    - D. Eleven General Orders: 11 violations (D.1-D.11)

#### 2. `employee_violations`
- **Purpose:** Records violations committed by employees
- **Key Fields:**
  - `id` - Primary key
  - `employee_id` - Links to employees table
  - `violation_type_id` - Links to violation_types table
  - `violation_date` - Date violation occurred
  - `description` - Additional details
  - `severity` - Major or Minor
  - `sanction` - Applied sanction
  - `sanction_date` - When sanction was applied
  - `reported_by` - Who reported the violation
  - `status` - Pending, Under Review, Resolved
- **Relationships:** 
  - Foreign key to `employees` table
  - Foreign key to `violation_types` table

---

## Attendance Management

**Migration File:** `add_leave_and_violation_tables.sql`

### Tables Created:

#### 1. `attendance_records`
- **Purpose:** Tracks daily attendance of employees
- **Key Fields:**
  - `id` - Primary key
  - `employee_id` - Links to employees table
  - `date` - Attendance date
  - `time_in`, `time_out` - Clock in/out times
  - `hours_worked` - Calculated hours
  - `status` - Present, Late, Absent, Half-Day, On Leave
  - `is_adjusted` - Whether record was manually adjusted
  - `adjustment_reason` - Reason for adjustment
  - `adjusted_by` - User who made adjustment
  - `adjusted_at` - When adjustment was made
- **Relationships:** Foreign key to `employees` table
- **Unique Constraint:** One record per employee per date

---

## Document Management

**Migration File:** `add_leave_and_violation_tables.sql`

### Tables Created:

#### 1. `employee_documents`
- **Purpose:** Stores employee documents (201 files)
- **Key Fields:**
  - `id` - Primary key
  - `employee_id` - Links to employees table
  - `document_type` - Type of document
  - `file_name` - Original file name
  - `file_path` - Storage path
  - `file_size` - File size in bytes
  - `upload_date` - When document was uploaded
  - `uploaded_by` - User who uploaded
  - `notes` - Additional notes
- **Relationships:** Foreign key to `employees` table

---

## Notification System

**Migration File:** `add_notification_status_table.sql`

### Tables Created:

#### 1. `notification_status`
- **Purpose:** Tracks read/dismissed status of notifications per user
- **Key Fields:**
  - `id` - Primary key
  - `user_id` - Links to users table
  - `notification_id` - ID of notification (numeric or string)
  - `notification_type` - Type: alert, license, clearance, task, message
  - `is_read` - Whether notification was read
  - `is_dismissed` - Whether notification was dismissed
  - `read_at` - Timestamp when read
  - `dismissed_at` - Timestamp when dismissed
- **Relationships:** Links to `users` table
- **Unique Constraint:** One status record per user per notification per type

---

## Migration History

### Migration Files Applied:

1. **`add_leave_and_violation_tables.sql`** (January 2026)
   - Created: `leave_requests`, `leave_balances`, `attendance_records`, `violation_types`, `employee_violations`, `employee_documents`
   - Initial violation types (8 default violations)

2. **`add_notification_status_table.sql`** (January 2026)
   - Created: `notification_status`
   - Enables per-user notification tracking

3. **`add_complete_ra5487_violations.sql`** (January 2026)
   - Updated: `violation_types` table structure
   - Added columns: `reference_no`, `subcategory`, `first_offense` through `fifth_offense`, `ra5487_compliant`
   - Inserted: 97 RA 5487 compliant violations
     - 28 Major Violations
     - 30 Minor Violations
     - 39 RA 5487 Offenses (A, B, C, D categories)

---

## Database Schema Summary

### Total Tables: ~15+ (including core tables)

**Core System:**
- users
- employees
- posts
- teams
- alerts
- tasks
- system_logs

**HR Modules:**
- leave_requests
- leave_balances
- attendance_records
- violation_types
- employee_violations
- employee_documents
- notification_status

---

## Important Notes

### Violation System
- All violations follow RA 5487 (Private Security Agency Law) standards
- Progressive sanctions are enforced (1st, 2nd, 3rd, 4th, 5th offenses)
- Major violations typically result in dismissal faster than minor violations
- RA 5487 offenses are categorized into:
  - Security Guard Creed
  - Code of Conduct
  - Code of Ethics
  - Eleven General Orders

### Data Integrity
- All employee-related tables use foreign keys with CASCADE delete
- Unique constraints prevent duplicate records
- Timestamps track creation and updates automatically

### Future Migrations
- Always document new migrations in this file
- Update the migration history section
- Include table structure changes and data inserts

---

## Maintenance

**To add new violations:**
1. Update `violation_types` table
2. Set `ra5487_compliant = 1` for RA 5487 violations
3. Include all progressive sanctions (1st-5th offenses)
4. Update this documentation

**To modify existing violations:**
1. Update the violation record in `violation_types`
2. Document changes in this file
3. Consider versioning for audit purposes

---

**Document Maintained By:** Development Team  
**For Questions:** Refer to migration SQL files for exact table structures
