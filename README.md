# Golden Z-5 HR Management System

**Version 2.0** | Enterprise-grade HR Management System for Security Agencies

---

## 📋 Overview

Comprehensive human resources management system designed specifically for Golden Z-5 Security and Investigation Agency, Inc. The system manages employee records, post assignments, daily time records, license tracking, alerts, and provides powerful audit trails and reporting capabilities.

---

## 🚀 Quick Start

### Installation (XAMPP)

1. **Clone or extract** the project to: `C:\xampp\htdocs\golden\goldenz`

2. **Import Database**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Create database: `CREATE DATABASE goldenz_hr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
   - Import `goldenz_hr.sql` into the `goldenz_hr` database

3. **Configure Environment** (if needed):
   - Copy `.env.example` to `.env` (if using environment variables)
   - Update database credentials in `bootstrap/env.php` or `config/database.php`

4. **Access the System**:
   - Login Page: `http://localhost/golden/goldenz/landing/`
   - HR Admin: `http://localhost/golden/goldenz/hr-admin/`
   - Super Admin: `http://localhost/golden/goldenz/super-admin/`

---

## 🔑 Default Credentials

| Role | Username | Password |
|------|----------|----------|
| **Super Admin** | admin | admin123 |
| **HR Admin** | hr | hr123 |
| **Developer** | developer | developer123 |

> ⚠️ **Security Note**: Change default passwords immediately after first login!

---

## ✨ Key Features

### 👥 Employee Management
- Complete employee records with photos and fingerprints
- License tracking with expiration alerts
- Post assignment management
- Employee status tracking (Active, Inactive, Suspended, Terminated)
- Comprehensive employee search and filtering
- Two-page employee application form with digital signatures

### 📍 Post & Assignment Management
- Security post creation and management
- Employee-to-post assignment tracking
- Post capacity and vacancy monitoring
- Post priority and status management
- Geographic location tracking

### 🔔 Alerts & Notifications
- License expiration alerts (30, 60, 90-day warnings)
- System-wide notification system (Bottom-right toast, middle-top alerts)
- Employee-specific alerts with priority levels
- Alert acknowledgment and resolution tracking

### 📊 Dashboard & Reporting
- Real-time employee statistics
- License watchlist (expiring and expired licenses)
- Audit trail for all system activities
- Security event logging
- System logs for developers

### 👤 User Management (Super Admin)
- Multi-role support (Super Admin, HR Admin, HR, Developer, Accounting, Operations)
- User creation and permission management
- Role-based access control
- User activity monitoring
- Password policy enforcement
- Two-factor authentication support

### 🔒 Security Features
- Password reset functionality with secure tokens
- Session management and auto-logout
- Audit logging for all actions
- IP address and user agent tracking
- Failed login attempt tracking
- CSRF protection
- SQL injection prevention

---

## 🎨 System Architecture

### Frontend
- **Framework**: Bootstrap 5.3.0
- **CSS**: Custom design system with orientation-first responsive strategy
- **JavaScript**: Vanilla JS with modular components
- **Icons**: Font Awesome 6.4.0
- **Fonts**: Segoe UI, Inter, Open Sans (system fonts for performance)

### Backend
- **Language**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.4+
- **Architecture**: MVC pattern with custom routing
- **Authentication**: Session-based with secure token management
- **Email**: PHPMailer for password reset and notifications

### Database
- **Engine**: InnoDB (ACID compliance, foreign key support)
- **Charset**: utf8mb4 with unicode collation
- **Tables**: 15+ core tables (employees, users, posts, alerts, audit_logs, etc.)
- **Views**: 3 materialized views for performance
- **Indexes**: Optimized for common query patterns

---

## 📂 Project Structure

```
goldenz/
├── app/                    # Application core
│   ├── Core/              # Core classes (Database, Auth, Config)
│   ├── Helpers/           # Helper functions
│   ├── Middleware/        # Authentication & role middleware
│   └── Models/            # Data models (Employee, User)
├── assets/                # Frontend assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── icons/            # Custom icon set
├── bootstrap/             # Application bootstrap
├── config/                # Configuration files
├── includes/              # Reusable components
│   ├── headers/          # Role-specific headers
│   └── page-header.php   # Global sticky header
├── landing/               # Login and authentication pages
├── pages/                 # Application pages
├── storage/               # Storage directory
│   ├── cache/            # Cached data
│   ├── logs/             # Application logs
│   └── sessions/         # Session files
├── uploads/               # User-uploaded files
│   ├── employees/        # Employee photos & fingerprints
│   └── users/            # User avatars
├── goldenz_hr.sql        # Main database schema
├── README.md             # This file
├── CHANGELOG.md          # Detailed change history
├── ENV_SETUP.md          # Environment setup guide
└── GITHUB_SETUP.md       # Git configuration guide
```

---

## 💾 Database Schema

### Core Tables
- `employees` - Employee master data
- `users` - System user accounts
- `posts` - Security post definitions
- `employee_alerts` - Employee alerts and notifications
- `audit_logs` - System activity audit trail
- `system_logs` - Developer logs
- `security_logs` - Security event logs
- `dtr_entries` - Daily time records
- `leave_balances` - Employee leave balances
- `time_off_requests` - Leave requests

### Views
- `employee_details` - Enhanced employee information
- `post_statistics` - Post capacity and vacancy stats
- `leave_balance_summary` - Leave balance aggregations

> 📘 See `goldenz_hr.sql` for complete schema documentation including all applied migrations.

---

## 🔧 Configuration

### Database Configuration
Edit `config/database.php`:
```php
return [
    'host' => 'localhost',
    'database' => 'goldenz_hr',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

### Email Configuration (Password Reset)
Edit `config/mail.php` for SMTP settings.

### Session Configuration
Edit `config/session.php` for session timeout and security settings.

---

## 📖 Documentation

### Inline Code Documentation
All system documentation is now embedded as comments within the code files:

- **CSS Architecture**: See comments in `assets/css/font-override.css`
- **JavaScript Modules**: See comments in `assets/js/*.js`
- **PHP Functions**: See PHPDoc comments in `includes/database.php`, `includes/security.php`
- **Database Schema**: See comprehensive header comments in `goldenz_hr.sql`
- **Component Structure**: See comments in `includes/header.php`, `includes/sidebar.php`, `includes/page-header.php`

### Additional Documentation Files
- `CHANGELOG.md` - Detailed history of all changes and updates
- `ENV_SETUP.md` - Step-by-step environment configuration
- `GITHUB_SETUP.md` - Git repository setup and workflow
- `LICENSE` - Software license agreement (EULA)

---

## 🛠 Development

### Code Style
- **PHP**: Follow PSR-12 coding standards
- **JavaScript**: ES6+ with consistent naming conventions
- **CSS**: BEM-like naming for components
- **Database**: Snake_case for tables and columns

### Git Workflow
- **Main Branch**: `main` (stable production code)
- **Backup Branch**: `backup` (development and testing)
- Commit messages follow conventional commit format

### Testing
- Manual testing checklist for all major features
- Browser compatibility: Chrome, Firefox, Edge, Safari
- Device testing: Desktop, tablet, mobile (portrait & landscape)

---

## 🚨 Maintenance

### Regular Tasks
- **Daily**: Automated database backups (via system)
- **Weekly**: Review security logs and audit trails
- **Monthly**: Archive old audit logs, review user permissions
- **Quarterly**: Update dependencies, security patches

### Database Optimization
```sql
-- Reset employee auto-increment if needed
ALTER TABLE employees AUTO_INCREMENT = (SELECT MAX(id) + 1 FROM employees);

-- Optimize tables
OPTIMIZE TABLE employees, users, audit_logs, employee_alerts;

-- Archive old audit logs (older than 90 days)
DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## 🐛 Troubleshooting

### Common Issues

**"Duplicate entry for key 'PRIMARY'"**
- Run: `ALTER TABLE table_name AUTO_INCREMENT = (SELECT MAX(id) + 1 FROM table_name);`

**"Session expired" immediately after login**
- Check PHP session configuration in `php.ini`
- Ensure `storage/sessions/` directory is writable

**Numbers displaying as boxes**
- Clear browser cache
- Ensure `font-override.css` is loaded
- Check font stack in CSS

**Password reset email not sending**
- Configure SMTP settings in `config/mail.php`
- Check PHPMailer logs in `storage/logs/`

---

## 📋 System Requirements

### Server Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7+ or MariaDB 10.4+
- **Web Server**: Apache 2.4+ with mod_rewrite
- **RAM**: Minimum 512MB, Recommended 2GB+
- **Storage**: Minimum 500MB for application + database

### PHP Extensions Required
- `pdo_mysql` - Database connectivity
- `mbstring` - String handling
- `openssl` - Secure password hashing
- `fileinfo` - File upload validation
- `gd` or `imagick` - Image processing

### Browser Support
- Chrome 90+
- Firefox 88+
- Edge 90+
- Safari 14+

---

## 📞 Support

For issues, questions, or feature requests:
- **Email**: goldenzfive@yahoo.com.ph
- **Facebook**: [Golden Z-5 Security Agency](https://www.facebook.com/goldenZ5SA)
- **System Logs**: Check `storage/logs/` for error details

---

## 📄 License

**Proprietary Software** - Closed-Source End User License Agreement (EULA)

Copyright © 2024-2026 Golden Z-5 Security and Investigation Agency, Inc.

This software is proprietary and licensed exclusively for use by Golden Z-5 Security and Investigation Agency for internal organizational operations only, as detailed in the `LICENSE` file.

**Restrictions:**
- No redistribution or public hosting
- No resale or commercial use outside the organization
- No sharing of source code
- No derivative works without written consent

---

## 👥 Credits

**Developed by**: Golden Z-5 Development Team  
**Company**: Golden Z-5 Security and Investigation Agency, Inc.  
**License**: PNP-CSG-SAGSD | SEC Registered  
**Version**: 2.0  
**Last Updated**: January 2026

---

*For detailed change history, see `CHANGELOG.md`*  
*For environment setup instructions, see `ENV_SETUP.md`*  
*For Git configuration, see `GITHUB_SETUP.md`*
