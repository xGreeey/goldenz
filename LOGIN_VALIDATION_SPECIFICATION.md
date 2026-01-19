# Login Form Validation Specification
**Golden Z-5 HR Management System**

## Overview
This document outlines comprehensive validation requirements for the login authentication system, covering both frontend (client-side) and backend (server-side) validation, security measures, and user experience guidelines.

---

## Frontend Validation (Client-Side)

### Username Field

#### **Field Specifications**
```
Label: Username or Email Address *
Placeholder: Enter your username or email
Input Type: text
Autocomplete: username
```

#### **Validation Rules**
| Rule | Requirement | Error Message |
|------|-------------|---------------|
| **Required** | Must not be empty | "Username is required" |
| **Min Length** | Minimum 3 characters | "Username must be at least 3 characters" |
| **Max Length** | Maximum 100 characters | "Username cannot exceed 100 characters" |
| **Pattern** | Alphanumeric, dots, underscores, @ symbols, hyphens | "Username contains invalid characters" |
| **Whitespace** | Leading/trailing spaces trimmed | Auto-trimmed on blur |

#### **Regex Pattern**
```regex
^[a-zA-Z0-9._@+-]+$
```

#### **HTML5 Attributes**
```html
required
minlength="3"
maxlength="100"
pattern="^[a-zA-Z0-9._@+-]+$"
autocomplete="username"
autofocus
aria-required="true"
aria-describedby="username-help username-error"
```

---

### Password Field

#### **Field Specifications**
```
Label: Password *
Placeholder: Enter your password
Input Type: password (toggleable to text)
Autocomplete: current-password
```

#### **Validation Rules**
| Rule | Requirement | Error Message |
|------|-------------|---------------|
| **Required** | Must not be empty | "Password is required" |
| **Min Length** | Minimum 8 characters | "Password must be at least 8 characters" |
| **Max Length** | Maximum 255 characters | "Password is too long" |
| **Case Sensitive** | Maintains original case | Info: "Passwords are case-sensitive" |

#### **HTML5 Attributes**
```html
required
minlength="8"
maxlength="255"
autocomplete="current-password"
aria-required="true"
aria-describedby="password-help password-error"
```

#### **Password Visibility Toggle**
- **Button**: Eye icon to toggle visibility
- **Accessible**: `aria-label="Show password"` / `aria-label="Hide password"`
- **Tab Index**: `-1` (not in tab order)
- **States**: Password / Text input type switching

---

### Remember Me (Optional)

#### **Field Specifications**
```
Type: Checkbox
Label: Keep me signed in
Default: Unchecked
```

#### **Behavior**
- Extends session duration (configurable, default: 7 days)
- Stores secure authentication token
- User must explicitly opt-in

---

### Form Submission

#### **Pre-Submit Validation**
1. Check all required fields are filled
2. Validate field formats
3. Trim whitespace from username
4. Show validation errors inline
5. Focus on first error field

#### **Submit Button States**
```
Default: "Sign In Securely" (enabled)
Validating: Show spinner, disable button
Success: Brief success state before redirect
Error: Re-enable button, show error message
```

#### **Loading State**
```html
<button aria-busy="true" disabled>
    <i class="fas fa-spinner fa-spin"></i>
    <span>Signing In...</span>
</button>
```

---

## Backend Validation (Server-Side)

### Request Validation

#### **Required Headers**
```http
Content-Type: application/x-www-form-urlencoded
User-Agent: <browser info>
Accept: text/html
```

#### **CSRF Protection**
```php
// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

#### **Rate Limiting**
```php
// Maximum 5 attempts per IP per 15 minutes
// Maximum 3 attempts per username per 15 minutes
// Lock account after 10 failed attempts in 1 hour
```

---

### Input Sanitization

#### **Username Sanitization**
```php
$username = trim($_POST['username']);
$username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
$username = strip_tags($username);

// Validation
if (empty($username)) {
    $error = "Username is required";
}
if (strlen($username) < 3) {
    $error = "Username must be at least 3 characters";
}
if (strlen($username) > 100) {
    $error = "Username cannot exceed 100 characters";
}
if (!preg_match('/^[a-zA-Z0-9._@+-]+$/', $username)) {
    $error = "Username contains invalid characters";
}
```

#### **Password Sanitization**
```php
$password = $_POST['password']; // Do NOT trim passwords

// Validation
if (empty($password)) {
    $error = "Password is required";
}
if (strlen($password) < 8) {
    $error = "Password must be at least 8 characters";
}
if (strlen($password) > 255) {
    $error = "Password is too long";
}
```

---

### Database Query (Secure)

#### **Prepared Statements**
```php
// Use parameterized queries to prevent SQL injection
$stmt = $conn->prepare("
    SELECT id, username, password_hash, role, status, failed_attempts, last_failed_attempt
    FROM users 
    WHERE (username = ? OR email = ?) 
    AND status = 'active'
    LIMIT 1
");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
```

#### **Account Status Checks**
```php
// Check if account exists
if (!$user) {
    // Generic error message (security best practice)
    $error = "Invalid username or password";
    logFailedAttempt($username, $_SERVER['REMOTE_ADDR']);
    exit;
}

// Check if account is locked
if ($user['failed_attempts'] >= 10) {
    $lockoutTime = strtotime($user['last_failed_attempt']) + (60 * 60); // 1 hour
    if (time() < $lockoutTime) {
        $error = "Account temporarily locked. Please try again later or contact support.";
        exit;
    }
}

// Check if account is suspended
if ($user['status'] === 'suspended') {
    $error = "Your account has been suspended. Please contact the administrator.";
    exit;
}
```

---

### Password Verification

#### **Secure Password Verification**
```php
// Use password_verify() - DO NOT compare plain text
if (!password_verify($password, $user['password_hash'])) {
    // Increment failed attempts
    incrementFailedAttempts($user['id']);
    
    // Generic error message
    $error = "Invalid username or password";
    
    // Log failed attempt
    logFailedAttempt($username, $_SERVER['REMOTE_ADDR'], $user['id']);
    
    exit;
}

// Password is correct - reset failed attempts
resetFailedAttempts($user['id']);
```

---

### Session Management

#### **Successful Authentication**
```php
// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['authenticated'] = true;
$_SESSION['login_time'] = time();
$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

// Set session timeout
ini_set('session.gc_maxlifetime', 3600); // 1 hour

// Handle "Remember Me"
if (isset($_POST['remember_me'])) {
    setRememberMeToken($user['id']);
}
```

#### **Session Security**
```php
// Validate session on each request
function validateSession() {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        return false;
    }
    
    // Check IP address (optional, may cause issues with dynamic IPs)
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        logSecurityWarning('IP address mismatch');
    }
    
    // Check user agent
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        logSecurityWarning('User agent mismatch');
        session_destroy();
        return false;
    }
    
    // Check session timeout
    if (time() - $_SESSION['login_time'] > 3600) { // 1 hour
        session_destroy();
        return false;
    }
    
    return true;
}
```

---

## Security Measures

### Rate Limiting Implementation

```php
function checkRateLimit($username, $ip_address) {
    // Check IP-based rate limit
    $ip_attempts = getAttempts($ip_address, 'ip', 900); // 15 minutes
    if ($ip_attempts >= 5) {
        return [
            'allowed' => false,
            'message' => 'Too many login attempts from this IP address. Please try again in 15 minutes.'
        ];
    }
    
    // Check username-based rate limit
    $user_attempts = getAttempts($username, 'username', 900); // 15 minutes
    if ($user_attempts >= 3) {
        return [
            'allowed' => false,
            'message' => 'Too many login attempts for this account. Please try again in 15 minutes.'
        ];
    }
    
    return ['allowed' => true];
}
```

### Audit Logging

```php
function logAuthenticationAttempt($username, $ip_address, $success, $user_id = null) {
    $stmt = $conn->prepare("
        INSERT INTO audit_logs 
        (user_id, username, action, ip_address, user_agent, status, timestamp)
        VALUES (?, ?, 'login_attempt', ?, ?, ?, NOW())
    ");
    
    $status = $success ? 'success' : 'failed';
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt->bind_param("issss", $user_id, $username, $ip_address, $user_agent, $status);
    $stmt->execute();
}
```

### Failed Attempt Tracking

```php
function incrementFailedAttempts($user_id) {
    $stmt = $conn->prepare("
        UPDATE users 
        SET 
            failed_attempts = failed_attempts + 1,
            last_failed_attempt = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

function resetFailedAttempts($user_id) {
    $stmt = $conn->prepare("
        UPDATE users 
        SET 
            failed_attempts = 0,
            last_failed_attempt = NULL,
            last_login = NOW(),
            last_login_ip = ?
        WHERE id = ?
    ");
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("si", $ip, $user_id);
    $stmt->execute();
}
```

---

## Error Messaging

### User-Facing Errors (Generic)

Use generic error messages for security:

✅ **Correct:**
```
"Invalid username or password"
"Authentication failed. Please check your credentials and try again."
```

❌ **Incorrect:**
```
"Username does not exist"
"Password is incorrect"
"Account is locked"
```

### System/Admin Logs (Specific)

Log detailed errors for administrators:

```php
// Log specific error for admins
error_log("Failed login attempt for user: $username | Reason: account_locked | IP: $ip");

// Show generic error to user
$error = "Unable to sign in. Please contact support if this issue persists.";
```

---

## Accessibility Requirements

### ARIA Labels
```html
<label for="username">
    Username or Email Address
    <span class="required-indicator" aria-label="Required">*</span>
</label>

<input 
    aria-required="true"
    aria-describedby="username-help username-error"
    aria-invalid="false"
>

<div class="invalid-feedback" id="username-error" role="alert"></div>
```

### Keyboard Navigation
- Tab order: Username → Password → Remember Me → Forgot Password → Sign In
- Enter key submits form from any input field
- Password toggle excluded from tab order (`tabindex="-1"`)

### Screen Reader Support
- Error messages announced via `role="alert"`
- Loading states announced via `aria-busy="true"`
- Required fields indicated with `aria-required="true"`

---

## Testing Checklist

### Frontend Tests
- [ ] Empty username shows error
- [ ] Empty password shows error
- [ ] Username too short (<3 chars) shows error
- [ ] Username too long (>100 chars) shows error
- [ ] Invalid characters in username show error
- [ ] Password too short (<8 chars) shows error
- [ ] Password visibility toggle works
- [ ] Form submits on Enter key
- [ ] Loading state displays correctly
- [ ] Validation errors clear on correction
- [ ] Remember me checkbox functions

### Backend Tests
- [ ] SQL injection attempts blocked
- [ ] XSS attempts sanitized
- [ ] CSRF token validated
- [ ] Rate limiting enforced (IP)
- [ ] Rate limiting enforced (username)
- [ ] Account lockout after 10 attempts
- [ ] Session fixation prevented
- [ ] Session hijacking mitigated
- [ ] Passwords never logged
- [ ] Audit trail created
- [ ] Failed attempts tracked
- [ ] Generic error messages shown

### Security Tests
- [ ] Brute force attack mitigated
- [ ] Timing attacks prevented (constant-time comparison)
- [ ] Account enumeration prevented
- [ ] Session timeout enforced
- [ ] Remember me token secure
- [ ] HTTPS enforced
- [ ] Secure headers set
- [ ] Password never sent in GET requests

---

## Implementation Checklist

### Phase 1: Frontend
- [x] Add input icons
- [x] Add validation attributes
- [x] Add help text
- [x] Add inline error display
- [x] Add loading states
- [x] Add remember me checkbox
- [x] Improve button styling
- [x] Add security notice

### Phase 2: Backend
- [ ] Implement CSRF protection
- [ ] Add rate limiting
- [ ] Implement account lockout
- [ ] Add audit logging
- [ ] Implement session validation
- [ ] Add remember me functionality
- [ ] Implement password verification
- [ ] Add failed attempt tracking

### Phase 3: Security
- [ ] Implement HTTPS redirect
- [ ] Add security headers
- [ ] Implement IP blocking
- [ ] Add honeypot field
- [ ] Implement device fingerprinting
- [ ] Add 2FA support (future)

---

## Configuration

### Recommended Settings
```php
// config/auth.php
return [
    'max_login_attempts' => 10,
    'lockout_duration' => 3600, // 1 hour
    'rate_limit_ip' => 5,
    'rate_limit_window' => 900, // 15 minutes
    'session_lifetime' => 3600, // 1 hour
    'remember_me_lifetime' => 604800, // 7 days
    'password_min_length' => 8,
    'username_min_length' => 3,
    'username_max_length' => 100,
];
```

---

**Document Version:** 1.0  
**Last Updated:** January 19, 2026  
**Status:** Implementation in Progress
