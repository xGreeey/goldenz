# Golden Z-5 HR Management System

Local HR Management System for Security Agencies

## Setup (XAMPP)

1. Place folder in: `C:\xampp\htdocs\goldenz-hr-system`
2. Import database: `database/goldenz_hr_complete_database.sql` via phpMyAdmin
3. **Run migration**: Execute `database_migration_add_super_admin.sql` to enable Super Admin role
4. Access: `http://localhost/goldenz-hr-system/landing/index.php`

## Default Login

- **HR Admin**: hr / hr123
- **Developer**: developer / developer123
- **Super Admin**: (Create after running migration - see database_migration_add_super_admin.sql)

## Super Admin Features

The Super Admin dashboard provides:
- System-wide statistics across all user roles
- User management and activity monitoring
- Comprehensive audit trail and security logs
- Employee statistics and workforce analytics
- Real-time system activity tracking
- Advanced filtering by role, date, and status

## Requirements

- XAMPP (PHP 7.4+, MySQL 5.7+)
- Web browser
