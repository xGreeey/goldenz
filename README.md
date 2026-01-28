# Golden Z-5 HR Management System

**Version 2.0** | Enterprise-grade HR Management System for Security Agencies

---

## 📋 Overview

Comprehensive human resources management system designed specifically for Golden Z-5 Security and Investigation Agency, Inc. The system manages employee records, post assignments, daily time records, license tracking, alerts, and provides powerful audit trails and reporting capabilities.

---

## 🚀 Quick Start

### Installation (Docker)

1. **Prerequisites**:
   - Docker Desktop installed and running
   - Git (for cloning the repository)

2. **Clone or navigate** to the project directory:
   ```powershell
   cd C:\docker-projects\goldenz_hr_system
   ```

3. **Start Docker Services**:
   ```powershell
   cd C:\docker-projects\goldenz_hr_system
   docker-compose up -d
   ```
   
   Or use the provided PowerShell script:
   ```powershell
   .\start-docker-services.ps1
   ```

4. **Initialize Database**:
   - The database will be automatically created on first run
   - Import `sql/goldenz_hr.sql` via phpMyAdmin if needed
   - Access phpMyAdmin at: `http://localhost:8080`
     - Server: `db`
     - Username: `root`
     - Password: `Suomynona027`

5. **Access the System**:
   - Login Page: `http://localhost/landing/`
   - HR Admin: `http://localhost/hr-admin/`
   - Super Admin: `http://localhost/super-admin/`
   - phpMyAdmin: `http://localhost:8080`
   - MinIO Console: `http://localhost:9001` (goldenz / SUOMYNONA)

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
- Employee document management with secure file storage
- Violation tracking and history

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
- Leave balance tracking and reports
- Attendance and DTR management

### 💬 Communication & Collaboration
- Private messaging system (chat)
- Employee feed and activity stream
- Team management
- Task assignment and tracking
- Events and announcements

### 📁 Document Management
- Secure employee file storage
- Document upload and organization
- File access control by role
- Integration with MinIO object storage

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

### 💾 Backup & Storage
- Automated database backups every 30 minutes
- Backups stored in MinIO object storage
- Backup retention policy (90 days default)
- Secure file storage with MinIO integration
- Google Drive backup support via rclone

---

## 🎨 System Architecture

### Frontend
- **Framework**: Bootstrap 5.3.0
- **CSS**: Custom design system with orientation-first responsive strategy
- **JavaScript**: Vanilla JS with modular components
- **Icons**: Font Awesome 6.4.0
- **Fonts**: Segoe UI, Inter, Open Sans (system fonts for performance)

### Backend
- **Language**: PHP 8.2
- **Database**: MySQL 8.0
- **Architecture**: MVC pattern with custom routing
- **Authentication**: Session-based with secure token management
- **Email**: PHPMailer for password reset and notifications
- **Dependency Management**: Composer (AWS SDK for PHP)
- **Containerization**: Docker with Docker Compose
- **Object Storage**: MinIO (S3-compatible)
- **Backup Tools**: MinIO Client (mc), rclone

### Database
- **Engine**: InnoDB (ACID compliance, foreign key support)
- **Charset**: utf8mb4 with unicode collation
- **Tables**: 20+ core tables (employees, users, posts, alerts, audit_logs, chat_messages, employee_files, violations, etc.)
- **Views**: 3 materialized views for performance
- **Indexes**: Optimized for common query patterns

---

## 📂 Project Structure

```
goldenz_hr_system/         # Project root
├── src/                   # Application source code
│   ├── app/              # Application core
│   │   ├── Core/         # Core classes (Database, Auth, Config)
│   │   ├── Helpers/      # Helper functions
│   │   ├── Middleware/   # Authentication & role middleware
│   │   └── Models/       # Data models (Employee, User)
│   ├── api/              # API endpoints
│   │   ├── chat.php      # Chat/messaging API
│   │   └── employee_files.php  # File management API
│   ├── assets/           # Frontend assets
│   │   ├── css/          # Stylesheets
│   │   ├── js/           # JavaScript files
│   │   └── icons/        # Custom icon set
│   ├── bootstrap/        # Application bootstrap
│   ├── config/           # Configuration files
│   ├── cron/             # Scheduled tasks
│   │   ├── backup-to-minio.php  # Automated backup script
│   │   └── README.md     # Backup documentation
│   ├── includes/         # Reusable components
│   │   ├── headers/      # Role-specific headers
│   │   └── page-header.php  # Global sticky header
│   ├── landing/          # Login and authentication pages
│   ├── pages/            # Application pages
│   │   ├── chat.php      # Chat interface
│   │   ├── documents.php # Document management
│   │   ├── employees.php # Employee management
│   │   └── ...           # Other pages
│   ├── sql/              # Database files
│   │   ├── goldenz_hr.sql  # Main database schema
│   │   └── migrations/   # Database migrations
│   ├── storage/          # Storage directory
│   │   ├── cache/        # Cached data
│   │   ├── logs/         # Application logs
│   │   └── sessions/     # Session files
│   ├── uploads/          # User-uploaded files (local fallback)
│   │   ├── employees/    # Employee photos & fingerprints
│   │   └── users/        # User avatars
│   ├── composer.json     # PHP dependencies
│   └── README.md         # This file
├── docker-compose.yml    # Docker services configuration
├── Dockerfile            # Web container image
├── mysql-init.sql        # Database initialization script
├── scripts/              # Utility scripts
│   └── backup.sh         # Backup script
├── start-containers.ps1  # PowerShell startup script
└── start-docker-services.ps1  # Docker services startup script
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
- `chat_messages` - Private messaging system
- `chat_conversations` - Chat conversation metadata
- `chat_typing_status` - Real-time typing indicators
- `employee_files` - Employee document metadata
- `file_audit_logs` - File operation audit trail
- `violations` - Employee violation records
- `violation_types` - Violation type definitions

### Views
- `employee_details` - Enhanced employee information
- `post_statistics` - Post capacity and vacancy stats
- `leave_balance_summary` - Leave balance aggregations

> 📘 See `goldenz_hr.sql` for complete schema documentation including all applied migrations.

---

## 🔧 Configuration

### Docker Services
The system runs in Docker containers. Configuration is managed via environment variables in `docker-compose.yml` at the project root:

**Services:**
- **web**: PHP 8.2 Apache container (ports 80, 443)
- **db**: MySQL 8.0 database (internal)
- **phpmyadmin**: Database management (port 8080)
- **minio**: Object storage (ports 9000, 9001)
- **db_backup**: Automated backup service

**Environment Variables:**
- Database credentials: Set in `docker-compose.yml`
- MinIO credentials: `goldenz` / `SUOMYNONA`
- Backup schedule: `*/30 * * * *` (every 30 minutes)

### Database Configuration
Database settings are configured via Docker environment variables. The connection is automatically established using:
- Host: `db` (Docker service name)
- Database: `goldenz_hr`
- Username/Password: As set in `docker-compose.yml`

### MinIO Storage Configuration
MinIO is used for object storage (employee files, backups):
- Endpoint: `http://minio:9000` (internal) or `http://localhost:9000` (external)
- Bucket: `goldenz-uploads`
- Console: `http://localhost:9001`

### Email Configuration (Password Reset)
Edit `config/mail.php` for SMTP settings.

### Session Configuration
Edit `config/session.php` for session timeout and security settings.

### Automated Backups
Database backups run automatically every 30 minutes:
- Location: MinIO bucket `goldenz-uploads/db-backups/`
- Retention: 90 days (configurable)
- Logs: `storage/logs/backup-cron.log`

See `cron/README.md` for detailed backup documentation.

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
- `cron/README.md` - Automated backup system documentation
- Docker setup guides (at project root):
  - `MINIO_SETUP.md` - MinIO object storage setup
  - `BACKUP_GUIDE.md` - Backup and restore procedures
  - `RCLONE_SETUP.md` - Google Drive backup configuration
  - `REBUILD_INSTRUCTIONS.md` - Container rebuild procedures
  - `CHAT_SYSTEM_README.md` - Chat system documentation
- `guide md/` - Additional system guides and documentation

---

## 🛠 Development

### Local Development Setup

1. **Start Docker containers**:
   ```powershell
   cd C:\docker-projects\goldenz_hr_system
   docker-compose up -d
   ```

2. **View logs**:
   ```powershell
   docker-compose logs -f web
   ```

3. **Access containers**:
   ```powershell
   docker exec -it hr_web bash
   docker exec -it hr_db mysql -u root -p
   ```

4. **Install PHP dependencies** (if needed):
   ```powershell
   docker exec hr_web composer install
   ```

5. **Rebuild containers** (after Dockerfile changes):
   ```powershell
   docker-compose down
   docker-compose build
   docker-compose up -d
   ```

### Code Style
- **PHP**: Follow PSR-12 coding standards
- **JavaScript**: ES6+ with consistent naming conventions
- **CSS**: BEM-like naming for components
- **Database**: Snake_case for tables and columns

### Git Workflow
- **Main Branch**: `main` (stable production code)
- **Development Branch**: `running-docker` (current development)
- Commit messages follow conventional commit format

### Testing
- Manual testing checklist for all major features
- Browser compatibility: Chrome, Firefox, Edge, Safari
- Device testing: Desktop, tablet, mobile (portrait & landscape)

---

## 🚨 Maintenance

### Regular Tasks
- **Automated**: Database backups every 30 minutes to MinIO
- **Daily**: Review backup logs and verify MinIO storage
- **Weekly**: Review security logs and audit trails
- **Monthly**: Archive old audit logs, review user permissions, check MinIO storage usage
- **Quarterly**: Update Docker images, dependencies, security patches

### Database Optimization
```sql
-- Reset employee auto-increment if needed
ALTER TABLE employees AUTO_INCREMENT = (SELECT MAX(id) + 1 FROM employees);

-- Optimize tables
OPTIMIZE TABLE employees, users, audit_logs, employee_alerts;

-- Archive old audit logs (older than 90 days)
DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Docker Maintenance
```powershell
# View running containers
docker ps

# View container logs
docker logs hr_web
docker logs hr_db

# Restart services
docker-compose restart

# Stop all services
docker-compose down

# Remove volumes (⚠️ deletes data)
docker-compose down -v
```

### Backup Management
- View backups in MinIO Console: `http://localhost:9001`
- Check backup logs: `docker exec hr_web tail -f /var/www/html/storage/logs/backup-cron.log`
- Manual backup: `docker exec hr_web php /var/www/html/cron/backup-to-minio.php`

---

## 🐛 Troubleshooting

### Common Issues

**Docker containers not starting**
- Ensure Docker Desktop is running
- Check Docker logs: `docker-compose logs`
- Verify ports 80, 443, 8080, 9000, 9001 are not in use
- Try: `docker-compose down && docker-compose up -d`

**Database connection errors**
- Verify database container is running: `docker ps | grep hr_db`
- Check database logs: `docker logs hr_db`
- Verify credentials in `docker-compose.yml`
- Test connection: `docker exec hr_web php -r "echo getenv('DB_HOST');"`

**"Duplicate entry for key 'PRIMARY'"**
- Run: `ALTER TABLE table_name AUTO_INCREMENT = (SELECT MAX(id) + 1 FROM table_name);`
- Access via phpMyAdmin: `http://localhost:8080`

**"Session expired" immediately after login**
- Check PHP session configuration
- Ensure `storage/sessions/` directory is writable: `docker exec hr_web chmod -R 777 /var/www/html/storage/sessions`

**MinIO upload failures**
- Verify MinIO container is running: `docker ps | grep hr_minio`
- Check MinIO logs: `docker logs hr_minio`
- Verify AWS SDK is installed: `docker exec hr_web composer show aws/aws-sdk-php`
- Test MinIO connection: `docker exec hr_web mc alias list`

**Backups not running**
- Check cron service: `docker exec hr_web service cron status`
- View backup logs: `docker exec hr_web tail -f /var/www/html/storage/logs/backup-cron.log`
- Test backup manually: `docker exec hr_web php /var/www/html/cron/backup-to-minio.php`

**Numbers displaying as boxes**
- Clear browser cache
- Ensure `font-override.css` is loaded
- Check font stack in CSS

**Password reset email not sending**
- Configure SMTP settings in `config/mail.php`
- Check PHPMailer logs in `storage/logs/`

---

## 📋 System Requirements

### Host System Requirements
- **OS**: Windows 10/11, macOS, or Linux
- **Docker**: Docker Desktop 4.0+ (or Docker Engine 20.10+)
- **Docker Compose**: 2.0+ (included with Docker Desktop)
- **RAM**: Minimum 4GB, Recommended 8GB+
- **Storage**: Minimum 10GB free space
- **Ports**: 80, 443, 8080, 9000, 9001 must be available

### Container Requirements
- **PHP**: 8.2 (in Docker container)
- **MySQL**: 8.0 (in Docker container)
- **Apache**: 2.4+ (in Docker container)
- **MinIO**: Latest (S3-compatible object storage)

### PHP Extensions (Included in Docker Image)
- `pdo_mysql` - Database connectivity
- `mysqli` - MySQL improved extension
- `curl` - HTTP client
- `mbstring` - String handling
- `openssl` - Secure password hashing
- `fileinfo` - File upload validation
- `gd` - Image processing

### Additional Tools (Included)
- **Composer**: PHP dependency manager
- **MinIO Client (mc)**: Object storage management
- **rclone**: Cloud storage sync (Google Drive support)
- **Cron**: Scheduled task execution

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
**Deployment**: Docker-based (PHP 8.2, MySQL 8.0, MinIO)

---

*For detailed change history, see `CHANGELOG.md`*  
*For environment setup instructions, see `ENV_SETUP.md`*  
*For Git configuration, see `GITHUB_SETUP.md`*
