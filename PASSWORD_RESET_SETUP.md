# Password Reset Feature Setup Guide

## Overview
The forgot password feature has been successfully added to the landing page. Users can now request password resets via email.

## Files Created/Modified

### New Files:
1. **landing/forgot-password.php** - Form for users to request password reset
2. **landing/reset-password.php** - Page for users to set new password using reset token
3. **goldenz_password_reset_migration.sql** - SQL migration to add password reset fields

### Modified Files:
1. **landing/index.php** - Added "Forgot Password" link to login form

## Database Setup

### Option 1: Run Migration (Recommended)
Run the SQL migration file to add dedicated password reset fields:
```sql
-- Run: goldenz_password_reset_migration.sql
```

This adds:
- `password_reset_token` (VARCHAR 255) - Stores the reset token
- `password_reset_expires_at` (TIMESTAMP) - Stores token expiration time

### Option 2: Use Existing Fields (Temporary)
The code will automatically fall back to using the `remember_token` field if the migration hasn't been run. However, this is not recommended for production.

## Email Configuration

The password reset emails are sent using PHPMailer. Configure your email settings via environment variables or update the code directly.

### Environment Variables (Recommended)
Create or update your `.env` file in the project root:

```env
# SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls

# Email From Address
MAIL_FROM_ADDRESS=noreply@goldenz5.com
MAIL_FROM_NAME=Golden Z-5 HR System
```

### Gmail Setup Example
1. Enable 2-Step Verification on your Google account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the app password in `SMTP_PASSWORD`

### Other Email Providers
- **Outlook/Hotmail**: `smtp-mail.outlook.com`, Port 587, TLS
- **Yahoo**: `smtp.mail.yahoo.com`, Port 587, TLS
- **Custom SMTP**: Use your provider's SMTP settings

### Fallback
If SMTP credentials are not configured, the system will attempt to use PHP's `mail()` function as a fallback.

## Security Features

1. **Secure Token Generation**: Uses `random_bytes(32)` to generate cryptographically secure tokens
2. **Token Expiration**: Reset links expire after 1 hour
3. **One-Time Use**: Tokens are cleared after successful password reset
4. **Email Validation**: Only sends reset emails to active accounts
5. **Rate Limiting**: Consider adding rate limiting to prevent abuse
6. **Security Logging**: All password reset attempts are logged

## Usage Flow

1. User clicks "Forgot Password" link on login page
2. User enters their email address
3. System generates secure token and sends email (if account exists)
4. User clicks link in email (valid for 1 hour)
5. User sets new password
6. Token is invalidated after use

## Testing

1. Navigate to `landing/index.php`
2. Click "Forgot your password?" link
3. Enter a valid email address from your users table
4. Check email inbox for reset link
5. Click link and set new password
6. Login with new password

## Troubleshooting

### Email Not Sending
- Check SMTP credentials in `.env` file
- Verify firewall allows outbound SMTP connections
- Check PHP error logs: `storage/logs/error.log`
- Test SMTP connection using PHPMailer's test script

### Token Not Working
- Ensure migration has been run (if using dedicated fields)
- Check token hasn't expired (1 hour limit)
- Verify email parameter matches exactly

### Database Errors
- Ensure database connection is configured correctly
- Check that users table exists with required fields
- Verify user account status is 'active'

## Notes

- The reset link includes both token and email for validation
- Passwords must be at least 8 characters long
- Failed login attempts are reset when password is changed
- Account lockouts are cleared on password reset
