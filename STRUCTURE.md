# Golden Z-5 HR Management System - Structure Documentation

## Overview

Golden Z-5 HR Management System is a role-based HR management application built with PHP, designed for security agencies. The system uses a hybrid architecture combining modern OOP patterns with legacy procedural code for backward compatibility.

## System Architecture

### Architecture Pattern
- **Hybrid Architecture**: Modern OOP (PSR-4 autoloading) + Legacy procedural code
- **Entry Points**: Multiple role-based entry points with centralized routing
- **Session Management**: Custom session handling with secure storage
- **Database**: MySQL with PDO, singleton pattern for connections
- **Security**: Role-based access control (RBAC) with middleware support

### Technology Stack
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: XAMPP (Apache)
- **Session Storage**: File-based (storage/sessions)
- **Email**: PHPMailer (via Composer)

---

## Directory Structure

```
goldenz/
├── app/                          # Application core (PSR-4 namespace: App\)
│   ├── Core/                     # Core system classes
│   │   ├── Auth.php             # Authentication handling
│   │   ├── Config.php           # Configuration management
│   │   ├── Database.php         # Database connection singleton
│   │   └── Security.php         # Security utilities
│   ├── Helpers/                  # Helper functions
│   │   └── functions.php        # Global helper functions
│   ├── Middleware/               # Middleware classes
│   │   ├── AuthMiddleware.php   # Authentication middleware
│   │   └── RoleMiddleware.php   # Role-based access middleware
│   └── Models/                   # Data models
│       ├── Employee.php         # Employee model
│       └── User.php             # User model
│
├── assets/                       # Frontend assets
│   ├── css/                     # Stylesheets
│   ├── icons/                   # Icon files (SVG/PNG)
│   └── js/                      # JavaScript files
│       ├── app.js               # Main application JS
│       ├── comprehensive-functionality.js
│       ├── page-transitions.js
│       └── sidebar-search.js
│
├── bootstrap/                    # Application bootstrap
│   ├── app.php                  # Main bootstrap file
│   ├── autoload.php             # PSR-4 autoloader
│   └── env.php                  # Environment variable loader
│
├── config/                       # Configuration files
│   ├── app.php                  # Application configuration
│   ├── auth.php                 # Authentication settings
│   ├── database.php             # Database configuration
│   ├── paths.php                # Path configuration
│   ├── roles.php                # Role-based access control config
│   └── vendor/                  # Composer dependencies
│       └── phpmailer/           # PHPMailer library
│
├── developer/                    # Developer portal entry point
│   └── index.php                # Developer dashboard router
│
├── hr-admin/                     # HR Admin portal entry point
│   └── index.php                # HR Admin dashboard router
│
├── includes/                     # Shared includes (legacy)
│   ├── database.php             # Legacy database functions
│   ├── footer.php               # Footer template
│   ├── header.php               # Header template
│   ├── headers/                 # Role-specific headers
│   │   ├── accounting-header.php
│   │   ├── employee-header.php
│   │   ├── hr-admin-header.php
│   │   ├── operation-header.php
│   │   └── super-admin-header.php
│   ├── hr-admin-header-section.php
│   ├── password-expiry-modal.php
│   ├── paths.php                # Path helper functions
│   ├── security.php             # Security functions
│   ├── sidebar.php              # Sidebar template
│   └── super-admin-header-section.php
│
├── landing/                      # Authentication & public pages
│   ├── 2fa.php                  # Two-factor authentication
│   ├── alerts-display.php       # Alert display
│   ├── forgot-password.php      # Password recovery
│   ├── index.php                # Login page (main entry)
│   ├── reset-password.php       # Password reset handler
│   └── assets/                  # Landing page assets
│       ├── landing.css
│       └── landing.js
│
├── pages/                        # Page content files
│   ├── add_alert.php
│   ├── add_employee.php         # Employee creation (Page 1)
│   ├── add_employee_page2.php   # Employee creation (Page 2)
│   ├── add_post.php
│   ├── alerts.php
│   ├── archive/                 # Archived pages
│   │   ├── checklist.php
│   │   ├── dtr.php
│   │   ├── handbook.php
│   │   ├── help.php
│   │   ├── hiring.php
│   │   ├── integrations.php
│   │   ├── onboarding.php
│   │   ├── settings.php
│   │   └── timeoff.php
│   ├── audit_trail.php
│   ├── css/                     # Page-specific CSS
│   │   └── add_employee.css
│   ├── dashboard.php
│   ├── edit_employee.php
│   ├── employees.php
│   ├── handbook.php
│   ├── help.php
│   ├── hiring.php
│   ├── hr-admin-settings.php
│   ├── hr-help.php
│   ├── integrations.php
│   ├── onboarding.php
│   ├── permissions.php
│   ├── post_assignments.php
│   ├── posts.php
│   ├── profile.php
│   ├── settings.php
│   ├── super-admin-dashboard.php
│   ├── system_logs.php
│   ├── tasks.php
│   ├── teams.php
│   ├── users.php
│   └── view_employee.php
│
├── public/                       # Public assets
│   ├── favicon.ico
│   └── logo.svg
│
├── storage/                      # Storage directory
│   └── sessions/                 # Session files (auto-created)
│
├── super-admin/                  # Super Admin portal entry point
│   └── index.php                # Super Admin dashboard router
│
├── uploads/                      # User-uploaded files
│   └── employees/               # Employee-related uploads
│       ├── [employee_id].png/jpg  # Employee photos
│       └── fingerprints/        # Fingerprint images
│           └── [employee_id]_fingerprint_[finger].png
│       └── temp/                # Temporary uploads
│
├── .htaccess                     # Apache configuration
├── .gitignore                    # Git ignore rules
├── index.php                     # Main entry point (role router)
├── goldenz_hr.sql                # Main database schema
├── [various migration files].sql # Database migrations
└── README.md                     # Project documentation
```

---

## Entry Points & Routing

### Main Entry Points

1. **`index.php`** (Root)
   - Main entry point after authentication
   - Routes users to role-specific portals
   - Handles logout
   - **Flow**: Check login → Route by role → Redirect to portal

2. **`landing/index.php`**
   - Authentication entry point
   - Handles login, password reset, first-time password change
   - **Flow**: Login → Check password_changed_at → Show modal if first login → Set session → Redirect

3. **Role-Specific Portals**:
   - `super-admin/index.php` - Super Administrator portal
   - `hr-admin/index.php` - HR Admin portal (also for: hr, admin, accounting, operation, logistics)
   - `developer/index.php` - Developer portal

### Routing Mechanism

The system uses **role-based routing**:

```php
// index.php routing logic
switch ($user_role) {
    case 'super_admin':
        → super-admin/index.php
    case 'hr_admin':
        → hr-admin/index.php
    case 'developer':
        → developer/index.php
    default:
        → landing/index.php (logout)
}
```

### Page Loading

Pages are loaded via query parameter:
- Format: `?page=page_name`
- Example: `?page=dashboard`, `?page=employees`
- Pages are included from `pages/` directory
- Headers/footers are role-specific

---

## Application Bootstrap

### Bootstrap Flow

1. **`bootstrap/autoload.php`**
   - Defines constants (BASE_PATH, APP_PATH, etc.)
   - Loads environment variables
   - Sets error reporting
   - Registers PSR-4 autoloader
   - Loads helper functions

2. **`bootstrap/app.php`**
   - Starts session (with secure configuration)
   - Sets security headers
   - Loads application configuration via `App\Core\Config`

### Constants Defined

- `BASE_PATH` - Project root directory
- `APP_PATH` - Application directory (`app/`)
- `CONFIG_PATH` - Configuration directory
- `STORAGE_PATH` - Storage directory
- `RESOURCES_PATH` - Resources directory
- `PUBLIC_PATH` - Public assets directory

---

## Core Classes

### `App\Core\Config`
- **Purpose**: Configuration management
- **Features**:
  - Loads `.env` file
  - Loads config files from `config/` directory
  - Dot notation access: `Config::get('database.host')`
  - Environment variable support

### `App\Core\Database`
- **Purpose**: Database connection management
- **Pattern**: Singleton
- **Features**:
  - PDO connection pooling
  - Prepared statements
  - Error handling
  - Transaction support

### `App\Core\Auth`
- **Purpose**: Authentication handling
- **Features**:
  - Login/logout
  - Password verification
  - Session management
  - Account lockout

### `App\Core\Security`
- **Purpose**: Security utilities
- **Features**:
  - Input sanitization
  - XSS prevention
  - CSRF protection
  - Security headers

---

## Models

### `App\Models\User`
- **Purpose**: User data operations
- **Methods**:
  - `findByUsername($username)`
  - `findById($id)`
  - `verifyPassword($password, $hash)`
  - `updateLastLogin($userId)`

### `App\Models\Employee`
- **Purpose**: Employee data operations
- **Methods**:
  - `getAll($filters)`
  - `getById($id)`
  - `create($data)`
  - `update($id, $data)`
  - `delete($id)`

---

## Middleware

### `App\Middleware\AuthMiddleware`
- **Purpose**: Authentication verification
- **Usage**: Ensures user is logged in

### `App\Middleware\RoleMiddleware`
- **Purpose**: Role-based access control
- **Usage**: Verifies user has required role/permissions

---

## Configuration Files

### `config/app.php`
- Application settings (name, version, timezone, etc.)

### `config/database.php`
- Database connection settings
- Reads from environment variables

### `config/auth.php`
- Authentication settings (password requirements, lockout, etc.)

### `config/roles.php`
- **Role definitions** with permissions
- **Role hierarchy** configuration
- **Redirect paths** per role

### `config/paths.php`
- Path configuration for assets and URLs

---

## Role-Based Access Control (RBAC)

### Defined Roles

1. **super_admin**
   - Full system access
   - All permissions (`*`)
   - Redirect: `super-admin/`

2. **hr_admin**
   - HR and administration functions
   - Permissions: employees.*, hiring.*, onboarding.*, posts.*, alerts.*
   - Redirect: `hr-admin/`

3. **hr**
   - HR staff member
   - Limited permissions (view/create/update employees)
   - Redirect: `hr-admin/`

4. **admin**
   - System administrator
   - Permissions: employees.*, settings.view
   - Redirect: `hr-admin/`

5. **operation**
   - Field operations management
   - Permissions: deployments.*, dtr.*, incidents.*
   - Redirect: `operation/`

6. **accounting**
   - Financial management
   - Permissions: payroll.*, expenses.*, deductions.*
   - Redirect: `accounting/`

7. **employee**
   - Employee self-service
   - Permissions: profile.*, dtr.view_own, timeoff.request
   - Redirect: `employee/`

8. **developer**
   - Developer access
   - All permissions (`*`)
   - Redirect: `developer/`

### Role Hierarchy

```
super_admin (highest)
  └─ hr_admin
      └─ hr, admin
  └─ operation
      └─ operations
```

---

## Database Structure

### Main Database File
- `goldenz_hr.sql` - Complete database schema

### Migration Files
- `goldenz_password_reset_migration.sql` - Password reset functionality
- `add_employee_page2_migration.sql` - Employee page 2 fields
- `fix_employee_auto_increment.sql` - Auto-increment fixes
- `fix_audit_logs.sql` - Audit log fixes
- `remove_dummy_employees.sql` - Cleanup script

### Key Tables (Inferred)
- `users` - User accounts
- `employees` - Employee records
- `audit_logs` - System audit trail
- `security_logs` - Security events
- `sessions` - Active sessions (if implemented)

---

## Authentication Flow

### Login Process

1. User submits credentials at `landing/index.php`
2. System verifies password
3. **First-time login check**:
   - If `password_changed_at` is NULL → Show password change modal
   - User must change password before proceeding
4. **Account lockout check**:
   - If `locked_until > current_time` → Account locked
   - Failed attempts tracked (5 attempts = 30 min lockout)
5. **Session creation**:
   - Sets: `logged_in`, `user_id`, `user_role`, `username`, `name`, `employee_id`, `department`
6. **Redirect**:
   - Based on role → Appropriate portal

### Password Reset Flow

1. User requests reset at `landing/forgot-password.php`
2. System generates reset token
3. Email sent with reset link
4. User clicks link → `landing/reset-password.php`
5. User sets new password
6. Password updated, token invalidated

---

## Employee Management Flow

### Two-Page Employee Creation

**Page 1** (`pages/add_employee.php`):
- Basic employee information
- Creates new record: `INSERT INTO employees`
- Stores `employee_id` in session: `$_SESSION['employee_created_id']`
- Redirects with success message

**Page 2** (`pages/add_employee_page2.php`):
- Additional employee information
- Receives `employee_id` from:
  - URL parameter: `$_GET['employee_id']`
  - Session: `$_SESSION['employee_created_id']`
- Updates existing record: `UPDATE employees WHERE id = ?`
- Both pages save to the **same employee record**

See `EMPLOYEE_PAGE_FLOW.md` for detailed flow.

---

## File Uploads

### Upload Structure

```
uploads/
└── employees/
    ├── [employee_id].png/jpg          # Employee photos
    ├── fingerprints/                   # Fingerprint images
    │   └── [employee_id]_fingerprint_[finger_name].png
    └── temp/                           # Temporary uploads
        └── [temp_filename].jpg
```

### Upload Naming Convention
- Employee photos: `{employee_id}.{ext}`
- Fingerprints: `{employee_id}_fingerprint_{finger_name}.{ext}`
- Temp files: Random hash with timestamp

---

## Security Features

### Session Security
- HTTP-only cookies
- Secure cookie flag (configurable)
- Strict mode enabled
- Custom session storage path

### Security Headers
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`

### Authentication Security
- Password hashing (bcrypt)
- Account lockout after failed attempts
- IP address and user agent logging
- Security event logging
- Audit trail logging

---

## Helper Functions

### Path Helpers (`includes/paths.php`)
- `root_prefix()` - Get root directory prefix
- `base_url()` - Get base URL
- `asset_url($path)` - Get asset URL
- `public_url($path)` - Get public URL

### Database Helpers (`includes/database.php`)
- Legacy database functions for backward compatibility
- Wraps `App\Core\Database` for legacy code

### Security Helpers (`includes/security.php`)
- Input sanitization functions
- XSS prevention
- CSRF token generation/verification

---

## JavaScript Architecture

### Main Files

1. **`assets/js/app.js`**
   - Main application JavaScript

2. **`assets/js/comprehensive-functionality.js`**
   - Extended functionality

3. **`assets/js/page-transitions.js`**
   - Page transition handling

4. **`assets/js/sidebar-search.js`**
   - Sidebar search functionality

5. **`landing/assets/landing.js`**
   - Landing page specific JavaScript

---

## CSS Architecture

### Main Files

1. **`assets/css/`**
   - Global stylesheets

2. **`pages/css/add_employee.css`**
   - Page-specific styles

3. **`landing/assets/landing.css`**
   - Landing page styles

---

## Environment Configuration

### Environment Variables (`.env`)

Required variables:
- `APP_ENV` - Environment (development/production)
- `APP_DEBUG` - Debug mode (true/false)
- `DB_HOST` - Database host
- `DB_USERNAME` - Database username
- `DB_PASSWORD` - Database password
- `DB_DATABASE` - Database name

### Configuration Loading

1. `.env` file loaded first
2. Config files from `config/` directory loaded
3. Environment variables take precedence
4. Access via `App\Core\Config::get('key')`

---

## Development Workflow

### Adding New Features

1. **Create Model** (if needed):
   - Add to `app/Models/`
   - Follow PSR-4 namespace: `App\Models\YourModel`

2. **Create Page**:
   - Add to `pages/`
   - Include via `?page=your_page`

3. **Add Route** (if needed):
   - Update role-specific header files
   - Add menu item in sidebar

4. **Update Permissions**:
   - Edit `config/roles.php`
   - Add permissions for new feature

### Database Changes

1. Create migration SQL file
2. Document in CHANGELOG.md
3. Test on development database
4. Apply to production

---

## Key Files Reference

### Entry Points
- `index.php` - Main router
- `landing/index.php` - Login page
- `super-admin/index.php` - Super Admin portal
- `hr-admin/index.php` - HR Admin portal
- `developer/index.php` - Developer portal

### Core Bootstrap
- `bootstrap/app.php` - Application bootstrap
- `bootstrap/autoload.php` - Autoloader
- `bootstrap/env.php` - Environment loader

### Configuration
- `config/roles.php` - Role definitions
- `config/database.php` - Database config
- `config/app.php` - App config

### Models
- `app/Models/User.php` - User model
- `app/Models/Employee.php` - Employee model

### Core Classes
- `app/Core/Database.php` - Database singleton
- `app/Core/Config.php` - Config manager
- `app/Core/Auth.php` - Authentication
- `app/Core/Security.php` - Security utilities

### Documentation
- `README.md` - Setup instructions
- `EMPLOYEE_PAGE_FLOW.md` - Employee creation flow
- `STRUCTURE.md` - This file

---

## Notes for Developers

### Backward Compatibility
- Legacy functions in `includes/` are maintained
- New code should use `App\Core\*` classes
- Both patterns coexist for gradual migration

### Session Management
- Sessions stored in `storage/sessions/`
- Directory auto-created if missing
- Secure configuration applied

### Error Handling
- Error reporting based on `APP_DEBUG`
- Errors logged to PHP error log
- Production mode hides errors from users

### Database Queries
- Use prepared statements (PDO)
- New code: `App\Core\Database`
- Legacy code: `get_db_connection()` function

### File Paths
- Use `root_prefix()` for relative paths
- Use `asset_url()` for assets
- Use `base_url()` for absolute URLs

---

## Version History

See `CHANGELOG.md` for detailed version history and changes.

---

**Last Updated**: [Current Date]
**System Version**: Golden Z-5 HR Management System
**PHP Version**: 7.4+
**Database**: MySQL 5.7+
