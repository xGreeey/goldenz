# Environment Variables Setup Guide

This guide explains how to set up environment variables for the Golden Z-5 HR System using phpdotenv.

## Quick Setup

### 1. Install phpdotenv (Optional but Recommended)

The system includes a fallback .env parser, but for better compatibility, install phpdotenv:

```bash
# If you have Composer installed globally
composer require vlucas/phpdotenv

# Or if using Composer locally
php composer.phar require vlucas/phpdotenv
```

**Note:** If you don't have Composer, the system will use a built-in simple .env parser as a fallback.

### 2. Create `.env` File

Create a `.env` file in the project root (same directory as `vendor/` or `config/`):

```env
# Golden Z-5 HR System - Environment Configuration
# DO NOT commit this file to version control

# Application Environment
APP_ENV=local
APP_DEBUG=true

# SMTP Email Configuration (Gmail)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password-here

# Email From Address
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Golden Z-5 HR System"
```

### 3. Configure Gmail SMTP

1. **Enable 2-Step Verification** on your Google Account
2. **Generate an App Password**:
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Enter "Golden Z-5 HR System"
   - Copy the 16-character password
3. **Update `.env` file**:
   - Set `SMTP_USERNAME` to your Gmail address
   - Set `SMTP_PASSWORD` to the generated App Password
   - Set `MAIL_FROM_ADDRESS` to your Gmail address

### 4. Verify Setup

The `.env` file is automatically loaded by:
- `bootstrap/autoload.php` (for most pages)
- `bootstrap/env.php` (standalone loader)
- `landing/forgot-password.php` (direct load)
- `landing/reset-password.php` (direct load)

## Environment Variables Reference

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `SMTP_HOST` | Yes | - | SMTP server hostname (e.g., `smtp.gmail.com`) |
| `SMTP_USERNAME` | Yes | - | SMTP authentication username (Gmail address) |
| `SMTP_PASSWORD` | Yes | - | SMTP authentication password (Gmail App Password) |
| `SMTP_PORT` | No | `587` | SMTP server port |
| `SMTP_ENCRYPTION` | No | `tls` | Encryption method: `tls` or `ssl` |
| `MAIL_FROM_ADDRESS` | Yes | - | Email address to send from |
| `MAIL_FROM_NAME` | No | `Golden Z-5 HR System` | Display name for emails |
| `APP_ENV` | No | `production` | Application environment (`local`, `production`) |
| `APP_DEBUG` | No | `false` | Enable debug mode (`true`, `false`) |

## Security Notes

- ✅ `.env` is automatically excluded from Git (see `.gitignore`)
- ✅ Never commit `.env` to version control
- ✅ Use Gmail App Passwords, not your regular password
- ✅ Keep `.env` file permissions restricted (600 on Linux/Mac)

## Troubleshooting

### "SMTP configuration is incomplete" Error

This means required environment variables are missing. Check:
1. `.env` file exists in project root
2. All required variables are set (no empty values)
3. No typos in variable names
4. File is readable by PHP

### Email Not Sending

1. Verify Gmail App Password is correct
2. Check SMTP settings match Gmail requirements:
   - Host: `smtp.gmail.com`
   - Port: `587`
   - Encryption: `tls`
3. Check error logs: `storage/logs/error.log`
4. Enable SMTP debug (uncomment in `forgot-password.php`)

### phpdotenv Not Found

The system includes a fallback parser, so phpdotenv is optional. If you want to use it:
1. Install via Composer: `composer require vlucas/phpdotenv`
2. Ensure `vendor/autoload.php` is loaded before `bootstrap/env.php`

## File Structure

```
goldenz/
├── .env                    # Your environment variables (NOT in Git)
├── .env.example            # Template file (safe to commit)
├── .gitignore             # Excludes .env from Git
├── bootstrap/
│   ├── env.php            # Environment loader
│   ├── autoload.php       # Loads env.php automatically
│   └── app.php            # Application bootstrap
└── landing/
    ├── forgot-password.php # Loads env.php directly
    └── reset-password.php  # Loads env.php directly
```
