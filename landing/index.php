<?php
/**
 * AUTHENTICATION FLOW - LANDING PAGE (LOGIN & FIRST-TIME PASSWORD CHANGE)
 * 
 * This file handles the complete authentication flow:
 * 
 * 1. FIRST-TIME LOGIN DETECTION:
 *    - Users log in using temporary password
 *    - System checks if password_changed_at is NULL (first-time login)
 *    - If NULL: Shows password change modal, blocks access until changed
 *    - If NOT NULL: Normal login, proceeds to dashboard
 * 
 * 2. PASSWORD RESET (First Login):
 *    - Modal displayed automatically on first login
 *    - Validates new password (min 8 chars, passwords match)
 *    - Hashes password with bcrypt
 *    - Updates password_changed_at timestamp
 *    - Auto-logs user in after password change
 * 
 * 3. ROLE-BASED DASHBOARD ACCESS:
 *    - developer → ../developer/index.php
 *    - hr_admin, hr, admin, accounting, operation, logistics → ../hr-admin/index.php
 *    - Sets session variables: user_id, user_role, username, name, employee_id, department
 * 
 * 4. SECURITY & AUDIT:
 *    - Account lockout check (locked_until > current time)
 *    - Failed login attempt tracking (5 attempts = 30 min lockout)
 *    - IP address and user agent logging
 *    - Security event logging (login attempts, password changes)
 *    - Audit trail logging
 * 
 * Flow Diagram:
 *   User Login → Verify Password → Check password_changed_at
 *   → If NULL: Show Password Change Modal → User Sets New Password → Auto-Login → Redirect
 *   → If NOT NULL: Update Last Login → Set Session → Redirect to Role-Based Dashboard
 */

ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start session first (before any output)
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../storage/sessions';
    if (is_dir($sessionPath) || mkdir($sessionPath, 0755, true)) {
        session_save_path($sessionPath);
    }
    session_start();
}

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');    
header('X-XSS-Protection: 1; mode=block');

// Bootstrap application (with error handling)
try {
    if (file_exists(__DIR__ . '/../bootstrap/app.php')) {
        require_once __DIR__ . '/../bootstrap/app.php';
    } else {
        // Fallback if bootstrap doesn't exist
        require_once __DIR__ . '/../bootstrap/autoload.php';
    }
} catch (Exception $e) {
    error_log('Bootstrap error: ' . $e->getMessage());
    // Continue anyway
}

// Include database functions
require_once __DIR__ . '/../includes/database.php';

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// If already logged in (and password changed), redirect to appropriate portal
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_role']) && !isset($_SESSION['require_password_change'])) {
    $role = $_SESSION['user_role'];
    if ($role === 'super_admin') {
        header('Location: ../super-admin/index.php');
        exit;
    }
    if ($role === 'hr_admin' || in_array($role, ['hr', 'admin', 'accounting', 'operation', 'logistics'])) {
        header('Location: ../hr-admin/index.php');
        exit;
    }
    if ($role === 'developer') {
        header('Location: ../developer/index.php');
        exit;
    }
}

/**
 * PASSWORD RESET HANDLER (First-Time Login)
 * 
 * When a user logs in with a temporary password (password_changed_at = NULL),
 * they are required to set a new permanent password before accessing the system.
 * 
 * Process:
 * 1. Validates password requirements (min 8 chars, passwords match)
 * 2. Hashes new password using bcrypt (password_hash with PASSWORD_DEFAULT)
 * 3. Updates password_hash and sets password_changed_at = NOW()
 * 4. Logs security event and audit trail
 * 5. Auto-logs user in and redirects to role-based dashboard
 */
// Handle password change (first login)
$password_change_error = '';
$password_change_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_SESSION['require_password_change']) || !$_SESSION['require_password_change']) {
        $password_change_error = 'Invalid request. Please login again.';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user_id = $_SESSION['temp_user_id'] ?? null;
        
        // Validate passwords
        if (empty($new_password) || empty($confirm_password)) {
            $password_change_error = 'Please fill in all password fields.';
        } elseif (strlen($new_password) < 8) {
            $password_change_error = 'Password must be at least 8 characters long.';
        } elseif ($new_password !== $confirm_password) {
            $password_change_error = 'Passwords do not match.';
        } elseif ($user_id) {
            try {
                $pdo = get_db_connection();
                
                // Hash new password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in database using helper function if available
                if (function_exists('update_user_password')) {
                    $update_result = update_user_password($user_id, $new_password);
                } else {
                    // Fallback to direct update
                    $update_sql = "UPDATE users SET password_hash = ?, password_changed_at = NOW() WHERE id = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_result = $update_stmt->execute([$new_password_hash, $user_id]);
                }
                
                if (!$update_result) {
                    throw new Exception('Failed to update password in database');
                }
                
                // Set session variables for login
                $_SESSION['user_id'] = $_SESSION['temp_user_id'];
                $_SESSION['user_role'] = $_SESSION['temp_role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $_SESSION['temp_username'];
                $_SESSION['name'] = $_SESSION['temp_name'];
                $_SESSION['employee_id'] = $_SESSION['temp_employee_id'] ?? null;
                $_SESSION['department'] = $_SESSION['temp_department'] ?? null;
                
                // Update last login and reset failed attempts
                $update_login_sql = "UPDATE users SET last_login = NOW(), last_login_ip = ?, 
                                    failed_login_attempts = 0, locked_until = NULL 
                                    WHERE id = ?";
                $update_login_stmt = $pdo->prepare($update_login_sql);
                $update_login_stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user_id]);
                
                // Log security event
                if (function_exists('log_security_event')) {
                    log_security_event('Password Changed - First Login', "User ID: $user_id - Username: " . ($_SESSION['temp_username'] ?? 'Unknown'));
                }
                
                // Clear temporary session variables
                unset($_SESSION['temp_user_id'], $_SESSION['temp_username'], $_SESSION['temp_name'], 
                      $_SESSION['temp_role'], $_SESSION['temp_employee_id'], $_SESSION['temp_department'], 
                      $_SESSION['require_password_change']);
                
                // Redirect based on role
                $role = $_SESSION['user_role'];
                if ($role === 'super_admin') {
                    header('Location: ../super-admin/index.php');
                    exit;
                } elseif ($role === 'developer') {
                    header('Location: ../developer/index.php');
                    exit;
                } else {
                    header('Location: ../hr-admin/index.php');
                    exit;
                }
            } catch (Exception $e) {
                $password_change_error = 'Error updating password. Please try again.';
                error_log('Password change error: ' . $e->getMessage());
            }
        } else {
            $password_change_error = 'Invalid request. Please login again.';
        }
    }
}

/**
 * LOGIN HANDLER
 * 
 * Handles user authentication with the following security features:
 * 
 * Security Checks:
 * - Account lockout verification (locked_until > current time)
 * - Failed login attempt tracking (increments on failure, resets on success)
 * - Account lockout after 5 failed attempts (30 minutes)
 * - IP address and user agent logging
 * 
 * First-Time Login Detection:
 * - Checks if password_changed_at is NULL
 * - If NULL: Stores user data in temporary session, shows password change modal
 * - If NOT NULL: Normal login flow, redirects to dashboard
 * 
 * Role-Based Redirect:
 * - developer → Developer Portal
 * - All other roles → HR Admin Portal
 */
// Handle login
$error = '';
$debug_info = [];
$show_password_change_modal = isset($_SESSION['require_password_change']) && $_SESSION['require_password_change'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $debug_info[] = "POST request received";
    $debug_info[] = "POST data: " . print_r($_POST, true);
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $debug_info[] = "Username: " . ($username ?: '(empty)');
    $debug_info[] = "Password: " . ($password ? '(provided)' : '(empty)');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
        $debug_info[] = "Validation failed: empty fields";
    } else {
        try {
            $pdo = get_db_connection();
            $debug_info[] = "Database connection successful";
            
            // Direct database query (simpler, more reliable)
            $sql = "SELECT id, username, password_hash, name, role, status, employee_id, department, 
                           failed_login_attempts, locked_until, password_changed_at
                    FROM users 
                    WHERE username = ? AND status = 'active'
                    LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $debug_info[] = "User found: " . $user['username'] . " (Role: " . $user['role'] . ")";
                
                // Check if account is locked
                if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                    $error = 'Account is temporarily locked. Please try again later.';
                    $debug_info[] = "Account locked";
                } elseif (password_verify($password, $user['password_hash'])) {
                    $debug_info[] = "Password verified successfully";
                    
                    // Log successful login attempt (Security & Audit)
                    if (function_exists('log_security_event')) {
                        log_security_event('Login Attempt', "User: {$user['username']} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
                    }
                    if (function_exists('log_audit_event')) {
                        log_audit_event('LOGIN_ATTEMPT', 'users', $user['id'], null, ['login_time' => date('Y-m-d H:i:s')], $user['id']);
                    }
                    
                    // Check if this is a first-time login (password_changed_at is NULL)
                    // This flags accounts that need to change their password on first login
                    $is_temporary_password = empty($user['password_changed_at']);
                    
                    if ($is_temporary_password) {
                        // First login with temporary password - show password change modal
                        $_SESSION['temp_user_id'] = $user['id'];
                        $_SESSION['temp_username'] = $user['username'];
                        $_SESSION['temp_name'] = $user['name'];
                        $_SESSION['temp_role'] = $user['role'];
                        $_SESSION['temp_employee_id'] = $user['employee_id'] ?? null;
                        $_SESSION['temp_department'] = $user['department'] ?? null;
                        $_SESSION['require_password_change'] = true;
                        $debug_info[] = "Temporary password detected - requiring password change";
                        // Don't redirect, show password change modal instead
                    } else {
                        // Check role
                        if (!in_array($user['role'], ['super_admin', 'hr_admin', 'hr', 'admin', 'accounting', 'operation', 'logistics', 'developer'], true)) {
                            $error = 'This account role is not permitted to sign in.';
                            $debug_info[] = "Role not allowed: " . $user['role'];
                        } else {
                            // Update last login and reset failed attempts
                            $update_sql = "UPDATE users SET last_login = NOW(), last_login_ip = ?, 
                                          failed_login_attempts = 0, locked_until = NULL 
                                          WHERE id = ?";
                            $update_stmt = $pdo->prepare($update_sql);
                            $update_stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);
                            
                            // Log successful login
                            if (function_exists('log_security_event')) {
                                log_security_event('Login Success', "User: {$user['username']} ({$user['name']}) - Role: {$user['role']} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
                            }
                            
                            // Set session variables
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['logged_in'] = true;
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['name'] = $user['name'];
                            $_SESSION['employee_id'] = $user['employee_id'] ?? null;
                            $_SESSION['department'] = $user['department'] ?? null;
                            
                            $debug_info[] = "Session variables set";
                            
                            // Redirect based on role (Role-Based Dashboard Access)
                            if ($user['role'] === 'super_admin') {
                                $debug_info[] = "Redirecting to: ../super-admin/index.php";
                                header('Location: ../super-admin/index.php');
                                exit;
                            } elseif ($user['role'] === 'developer') {
                                $debug_info[] = "Redirecting to: ../developer/index.php";
                                header('Location: ../developer/index.php');
                                exit;
                            } else {
                                // All other roles (hr_admin, hr, admin, accounting, operation, logistics) go to hr-admin portal
                                $debug_info[] = "Redirecting to: ../hr-admin/index.php";
                                header('Location: ../hr-admin/index.php');
                                exit;
                            }
                        }
                    }
                } else {
                    $error = 'Invalid username or password';
                    $debug_info[] = "Password verification failed";
                    
                    // Log failed login attempt
                    if (function_exists('log_security_event')) {
                        log_security_event('Login Failed', "User: {$user['username']} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
                    }
                    
                    // Increment failed login attempts (Security & Audit: Login attempt limits)
                    $failed_attempts = ($user['failed_login_attempts'] ?? 0) + 1;
                    $locked_until = null;
                    if ($failed_attempts >= 5) {
                        $locked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                        // Log account lockout
                        if (function_exists('log_security_event')) {
                            log_security_event('Account Locked', "User: {$user['username']} - Locked for 30 minutes due to 5 failed login attempts");
                        }
                    }
                    $update_sql = "UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_stmt->execute([$failed_attempts, $locked_until, $user['id']]);
                }
            } else {
                $error = 'Invalid username or password';
                $debug_info[] = "User not found or inactive";
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            $debug_info[] = "Exception: " . $e->getMessage();
            error_log('Login error: ' . $e->getMessage());
        }
    }
}

// Log debug info
if (!empty($debug_info)) {
    error_log('Login debug: ' . implode(' | ', $debug_info));
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Golden Z-5 HR Management System</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/landing.css" rel="stylesheet">
    <!-- font-override.css moved after landing.css to allow overrides -->
    <link href="../assets/css/font-override.css" rel="stylesheet">
    
    <!-- Override font-override.css for Font Awesome password toggle icon -->
    <style>
        .password-toggle i,
        .password-toggle i::before,
        .password-toggle i::after,
        #togglePasswordIcon,
        #togglePasswordIcon::before,
        #togglePasswordIcon::after,
        #toggleNewPasswordIcon,
        #toggleNewPasswordIcon::before,
        #toggleNewPasswordIcon::after,
        #toggleConfirmPasswordIcon,
        #toggleConfirmPasswordIcon::before,
        #toggleConfirmPasswordIcon::after {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            font-style: normal !important;
            font-variant: normal !important;
            text-rendering: auto !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
        }
        
        /* Password Change Modal Styles */
        #passwordChangeModal {
            z-index: 1055;
        }
        
        #passwordChangeModal .modal-dialog {
            max-width: 500px;
        }
        
        #passwordChangeModal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        #passwordChangeModal .modal-header {
            border-bottom: 1px solid #e5e7eb;
            padding: 1.5rem;
        }
        
        #passwordChangeModal .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
        }
        
        #passwordChangeModal .modal-body {
            padding: 1.5rem;
        }
        
        #passwordChangeModal .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        #passwordChangeModal .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.2s ease;
        }
        
        #passwordChangeModal .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }
        
        #passwordChangeModal .password-input-group {
            position: relative;
        }
        
        #passwordChangeModal .password-input-group .form-control {
            padding-right: 3rem;
        }
        
        #passwordChangeModal .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            padding: 0.5rem;
            color: #6b7280;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #passwordChangeModal .password-toggle:hover {
            color: #374151;
        }
        
        #passwordChangeModal .btn-primary {
            background: #111827;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        #passwordChangeModal .btn-primary:hover {
            background: #1f2937;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        #passwordChangeModal .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        
        #passwordChangeModal .modal-footer {
            border-top: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }
        
        #passwordChangeModal .modal-footer a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        
        #passwordChangeModal .modal-footer a:hover {
            text-decoration: underline;
        }
    </style>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body>
    <div class="login-split-container">
        <!-- Left Branded Panel -->
        <div class="login-branded-panel">
            <div class="branded-content">
                <img src="../public/logo.svg" alt="Golden Z-5 Logo" class="branded-logo" onerror="this.style.display='none'">
                <h1 class="branded-headline">Welcome to Golden Z-5</h1>
                <p class="branded-description">Your comprehensive HR Management System for efficient workforce administration and streamlined operations.</p>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="login-form-panel">
            <div class="auth-form-container">
                <div class="auth-form-card">
                    <div class="auth-header">
                        <h2 class="auth-title">Sign in</h2>
                        <p class="auth-subtitle">Enter your credentials to access your account</p>
                    </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_password_change_modal): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        This is your first login. Please set a new password to continue.
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$show_password_change_modal): ?>
                <form method="POST" action="" id="loginForm" class="auth-form">
                    <input type="hidden" name="login" value="1">
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Enter your username" 
                                   required 
                                   autocomplete="username"
                                   autofocus
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group password-input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password" 
                                   required 
                                   autocomplete="current-password">
                            <button class="password-toggle" type="button" id="togglePassword">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="login" class="btn btn-primary btn-lg" id="submitBtn">
                            <span class="btn-text">Sign in</span>
                            <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                        </button>
                    </div>

                    <div class="text-center text-muted small mt-3">
                        Need access? Contact your administrator.
                    </div>
                </form>
                
                <!-- Alerts Display Link -->
                <div class="text-center mt-4">
                    <a href="alerts-display.php" class="btn btn-outline-primary btn-lg" style="border-radius: 8px; padding: 0.75rem 2rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                        View License Alerts
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>

    <!-- Password Change Modal (shown on first login) -->
    <?php if ($show_password_change_modal): ?>
    <div class="modal fade show" id="passwordChangeModal" tabindex="-1" aria-labelledby="passwordChangeModalLabel" aria-modal="true" role="dialog" style="display: block !important; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn btn-link text-decoration-none p-0 me-2" onclick="window.location.href='?logout=1'" aria-label="Back" style="border: none; background: transparent; color: #6b7280; font-size: 1.25rem;">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h5 class="modal-title mb-0" id="passwordChangeModalLabel">
                        Set a new password
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">
                        Create a strong password to keep your account safe and secure.
                    </p>
                    
                    <?php if ($password_change_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($password_change_error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="passwordChangeForm">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group mb-3">
                            <label for="new_password" class="form-label">
                                New Password
                            </label>
                            <div class="input-group password-input-group" style="position: relative;">
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Create strong password" 
                                       required 
                                       autocomplete="new-password"
                                       minlength="8">
                                <button class="password-toggle" type="button" id="toggleNewPassword">
                                    <i class="fas fa-eye" id="toggleNewPasswordIcon"></i>
                                </button>
                            </div>
                            <small class="text-muted" style="font-size: 0.8125rem;">Must be at least 8 characters long</small>
                        </div>

                        <div class="form-group mb-4">
                            <label for="confirm_password" class="form-label">
                                Confirm New Password
                            </label>
                            <div class="input-group password-input-group" style="position: relative;">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Re-enter your new password" 
                                       required 
                                       autocomplete="new-password"
                                       minlength="8">
                                <button class="password-toggle" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye" id="toggleConfirmPasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="changePasswordBtn">
                                Create New Password
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <p class="text-center text-muted small mb-0" style="width: 100%;">
                        Remembered it? <a href="?logout=1">Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Simplified JavaScript - minimal interference
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded');
        
        // Toggle Password Visibility for login form
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const togglePasswordIcon = document.getElementById('togglePasswordIcon');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                togglePasswordIcon.classList.toggle('fa-eye');
                togglePasswordIcon.classList.toggle('fa-eye-slash');
            });
        }
        
        // Toggle Password Visibility for new password field
        const toggleNewPassword = document.getElementById('toggleNewPassword');
        const newPasswordInput = document.getElementById('new_password');
        const toggleNewPasswordIcon = document.getElementById('toggleNewPasswordIcon');
        
        if (toggleNewPassword && newPasswordInput) {
            toggleNewPassword.addEventListener('click', function() {
                const type = newPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                newPasswordInput.setAttribute('type', type);
                toggleNewPasswordIcon.classList.toggle('fa-eye');
                toggleNewPasswordIcon.classList.toggle('fa-eye-slash');
            });
        }
        
        // Toggle Password Visibility for confirm password field
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const toggleConfirmPasswordIcon = document.getElementById('toggleConfirmPasswordIcon');
        
        if (toggleConfirmPassword && confirmPasswordInput) {
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                toggleConfirmPasswordIcon.classList.toggle('fa-eye');
                toggleConfirmPasswordIcon.classList.toggle('fa-eye-slash');
            });
        }
        
        // Password change form validation
        const passwordChangeForm = document.getElementById('passwordChangeForm');
        if (passwordChangeForm) {
            passwordChangeForm.addEventListener('submit', function(e) {
                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (newPassword.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long.');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match. Please try again.');
                    return false;
                }
                
                // Show loading state
                const changePasswordBtn = document.getElementById('changePasswordBtn');
                if (changePasswordBtn) {
                    changePasswordBtn.disabled = true;
                    changePasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating Password...';
                }
            });
        }
        
        // Form submission - SIMPLIFIED
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                console.log('Form submit event triggered');
                
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value;
                
                console.log('Username:', username ? 'provided' : 'empty');
                console.log('Password:', password ? 'provided' : 'empty');
                
                // Only prevent if fields are empty
                if (!username || !password) {
                    e.preventDefault();
                    alert('Please enter both username and password');
                    return false;
                }
                
                // Show loading state
                const submitBtn = document.getElementById('submitBtn');
                const btnText = submitBtn.querySelector('.btn-text');
                const spinner = document.getElementById('submitSpinner');
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    if (btnText) btnText.textContent = 'Signing in...';
                    if (spinner) spinner.classList.remove('d-none');
                }
                
                console.log('Form will submit - allowing default behavior');
                // DON'T prevent default - let form submit normally!
            });
        } else {
            console.error('Login form not found!');
        }
    });
    </script>
</body>
</html>

