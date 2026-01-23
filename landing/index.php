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
    session_unset();
    session_destroy();
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    header('Location: /landing/');
    exit;
}

// If already logged in (and password changed), redirect to appropriate portal
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_role']) && !isset($_SESSION['require_password_change'])) {
    $role = $_SESSION['user_role'];
    if ($role === 'super_admin') {
        header('Location: /super-admin/dashboard');
        exit;
    }
    if ($role === 'hr_admin' || in_array($role, ['hr', 'admin', 'accounting', 'operation', 'logistics'])) {
        header('Location: /hr-admin/dashboard');
        exit;
    }
    if ($role === 'developer') {
        header('Location: /developer/dashboard');
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
                    header('Location: /super-admin/dashboard');
                    exit;
                } elseif ($role === 'developer') {
                    header('Location: /developer/dashboard');
                    exit;
                } else {
                    header('Location: /hr-admin/dashboard');
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
$login_status_error = $_SESSION['login_status_error'] ?? '';
$login_status_message = $_SESSION['login_status_message'] ?? '';
// Clear status error from session after reading
unset($_SESSION['login_status_error']);
unset($_SESSION['login_status_message']);

$show_password_change_modal = isset($_SESSION['require_password_change']) && $_SESSION['require_password_change'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $wantsJson = $isAjaxRequest || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    $respondJson = function (array $payload) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    };

    $debug_info[] = "POST request received";
    $debug_info[] = "POST data: " . print_r($_POST, true);
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $debug_info[] = "Username: " . ($username ?: '(empty)');
    $debug_info[] = "Password: " . ($password ? '(provided)' : '(empty)');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
        $debug_info[] = "Validation failed: empty fields";
        if ($wantsJson) {
            $respondJson([
                'success' => false,
                'error' => 'validation',
                'message' => 'Please enter both username and password.'
            ]);
        }
    } else {
        try {
            $pdo = get_db_connection();
            $debug_info[] = "Database connection successful";
            
            // Direct database query (simpler, more reliable)
            // Note: We don't filter by status here so we can check it and show appropriate messages
            $sql = "SELECT id, username, password_hash, name, role, status, employee_id, department, 
                           failed_login_attempts, locked_until, password_changed_at,
                           two_factor_enabled, two_factor_secret
                    FROM users 
                    WHERE username = ?
                    LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $debug_info[] = "User found: " . $user['username'] . " (Role: " . $user['role'] . ", Status: " . $user['status'] . ")";
                
                // Check user status first (before password verification)
                // IMPORTANT: Check status BEFORE password verification so error shows immediately
                if ($user['status'] === 'inactive') {
                    $_SESSION['login_status_error'] = 'inactive';
                    $_SESSION['login_status_message'] = 'Your account is currently inactive. Please contact your administrator to activate your account.';
                    $error = 'inactive';
                    $debug_info[] = "User account is inactive - blocking login";
                    if ($wantsJson) {
                        $respondJson([
                            'success' => false,
                            'error' => 'status',
                            'status' => 'inactive',
                            'message' => $_SESSION['login_status_message']
                        ]);
                    }
                    // Redirect back to login page to show the modal (non-AJAX fallback)
                    header('Location: /landing/');
                    exit;
                } elseif ($user['status'] === 'suspended') {
                    $_SESSION['login_status_error'] = 'suspended';
                    $_SESSION['login_status_message'] = 'Your account has been suspended. Please contact your administrator for assistance.';
                    $error = 'suspended';
                    $debug_info[] = "User account is suspended - blocking login";
                    if ($wantsJson) {
                        $respondJson([
                            'success' => false,
                            'error' => 'status',
                            'status' => 'suspended',
                            'message' => $_SESSION['login_status_message']
                        ]);
                    }
                    // Redirect back to login page to show the modal (non-AJAX fallback)
                    header('Location: /landing/');
                    exit;
                } elseif (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                    $error = 'Account is temporarily locked. Please try again later.';
                    $debug_info[] = "Account locked";
                    if ($wantsJson) {
                        $respondJson([
                            'success' => false,
                            'error' => 'locked',
                            'message' => $error
                        ]);
                    }
                } elseif (password_verify($password, $user['password_hash'])) {
                    $debug_info[] = "Password verified successfully";
                    
                    // Log successful login attempt (Security & Audit)
                    if (function_exists('log_security_event')) {
                        log_security_event('Login Attempt', "User: {$user['username']} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
                    }
                    if (function_exists('log_audit_event')) {
                        log_audit_event('LOGIN_ATTEMPT', 'users', $user['id'], null, ['login_time' => date('Y-m-d H:i:s')], $user['id']);
                    }
                    
                    // First-time password change check DISABLED
                    // Previously checked if password_changed_at is NULL to force password change
                    // Now users can login directly without changing password
                    $is_temporary_password = false; // Disabled - always allow direct login
                    
                    if ($is_temporary_password) {
                        // First login with temporary password - show password change modal
                        // This block is now disabled (is_temporary_password always false)
                        $_SESSION['temp_user_id'] = $user['id'];
                        $_SESSION['temp_username'] = $user['username'];
                        $_SESSION['temp_name'] = $user['name'];
                        $_SESSION['temp_role'] = $user['role'];
                        $_SESSION['temp_employee_id'] = $user['employee_id'] ?? null;
                        $_SESSION['temp_department'] = $user['department'] ?? null;
                        $_SESSION['require_password_change'] = true;
                        $debug_info[] = "Temporary password detected - requiring password change";
                        // Don't redirect, show password change modal instead
                        if ($wantsJson) {
                            $respondJson([
                                'success' => true,
                                'redirect' => '/landing/' // shows password-change UI when enabled
                            ]);
                        }
                    } else {
                        // Check role
                        if (!in_array($user['role'], ['super_admin', 'hr_admin', 'hr', 'admin', 'accounting', 'operation', 'logistics', 'developer'], true)) {
                            $error = 'This account role is not permitted to sign in.';
                            $debug_info[] = "Role not allowed: " . $user['role'];
                            if ($wantsJson) {
                                $respondJson([
                                    'success' => false,
                                    'error' => 'role_not_permitted',
                                    'message' => $error
                                ]);
                            }
                        } else {
                            // Determine if this user must pass 2FA before accessing the dashboard
                            $requires_2fa = in_array($user['role'], ['super_admin', 'admin'], true)
                                && !empty($user['two_factor_enabled'])
                                && !empty($user['two_factor_secret']);

                            if ($requires_2fa) {
                                // Store minimal user context for the 2FA step
                                $_SESSION['pending_2fa_user_id'] = $user['id'];
                                $_SESSION['pending_2fa_username'] = $user['username'];
                                $_SESSION['pending_2fa_name'] = $user['name'];
                                $_SESSION['pending_2fa_role'] = $user['role'];
                                $_SESSION['pending_2fa_employee_id'] = $user['employee_id'] ?? null;
                                $_SESSION['pending_2fa_department'] = $user['department'] ?? null;

                                $debug_info[] = "2FA required - redirecting to 2FA verification page";
                                if ($wantsJson) {
                                    $respondJson([
                                        'success' => true,
                                        'redirect' => '2fa.php'
                                    ]);
                                }
                                header('Location: 2fa.php');
                                exit;
                            }

                            // No 2FA required – complete login immediately
                            // Update last login and reset failed attempts
                            $update_sql = "UPDATE users SET last_login = NOW(), last_login_ip = ?, 
                                          failed_login_attempts = 0, locked_until = NULL 
                                          WHERE id = ?";
                            $update_stmt = $pdo->prepare($update_sql);
                            $update_stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);
                            
                            // Log successful login
                            // Log to system logs
                            if (function_exists('log_system_event')) {
                                log_system_event('info', "User logged in: {$user['username']} ({$user['name']})", 'authentication', [
                                    'user_id' => $user['id'],
                                    'role' => $user['role'],
                                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                                ]);
                            }
                            
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
                                $debug_info[] = "Redirecting to: /super-admin/dashboard";
                                if ($wantsJson) {
                                    $respondJson([
                                        'success' => true,
                                        'redirect' => '/super-admin/dashboard'
                                    ]);
                                }
                                header('Location: /super-admin/dashboard');
                                exit;
                            } elseif ($user['role'] === 'developer') {
                                $debug_info[] = "Redirecting to: ../developer/dashboard";
                                if ($wantsJson) {
                                    $respondJson([
                                        'success' => true,
                                        'redirect' => '/developer/dashboard'
                                    ]);
                                }
                                header('Location: /developer/dashboard');
                                exit;
                            } else {
                                // All other roles (hr_admin, hr, admin, accounting, operation, logistics) go to hr-admin portal
                                $debug_info[] = "Redirecting to: ../hr-admin/dashboard";
                                if ($wantsJson) {
                                    $respondJson([
                                        'success' => true,
                                        'redirect' => '/hr-admin/dashboard'
                                    ]);
                                }
                                header('Location: /hr-admin/dashboard');
                                exit;
                            }
                        }
                    }
                } else {
                    $error = 'Invalid username or password';
                    $debug_info[] = "Password verification failed";
                    if ($wantsJson) {
                        $respondJson([
                            'success' => false,
                            'error' => 'invalid_credentials',
                            'message' => 'Invalid credentials. Verify your username and password and try again.'
                        ]);
                    }
                    
                    // Log failed login attempt
                    // Log to system logs
                    if (function_exists('log_system_event')) {
                        log_system_event('warning', "Failed login attempt for user: {$user['username']}", 'authentication', [
                            'user_id' => $user['id'],
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                            'failed_attempts' => $user['failed_login_attempts'] + 1
                        ]);
                    }
                    
                    if (function_exists('log_security_event')) {
                        log_security_event('Login Failed', "User: {$user['username']} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
                    }
                    
                    // Increment failed login attempts (Security & Audit: Login attempt limits)
                    $failed_attempts = ($user['failed_login_attempts'] ?? 0) + 1;
                    $locked_until = null;
                    if ($failed_attempts >= 5) {
                        $locked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                        // Log account lockout
                        // Log to system logs
                        if (function_exists('log_system_event')) {
                            log_system_event('error', "Account locked: {$user['username']} - 5 failed login attempts", 'authentication', [
                                'user_id' => $user['id'],
                                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                                'lockout_duration' => '30 minutes'
                            ]);
                        }
                        
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
                if ($wantsJson) {
                    $respondJson([
                        'success' => false,
                        'error' => 'invalid_credentials',
                        'message' => 'Invalid credentials. Verify your username and password and try again.'
                    ]);
                }
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            $debug_info[] = "Exception: " . $e->getMessage();
            error_log('Login error: ' . $e->getMessage());
            if ($wantsJson) {
                $respondJson([
                    'success' => false,
                    'error' => 'server',
                    'message' => $error
                ]);
            }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <title>Login</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../public/logo.svg">
    <link rel="icon" type="image/x-icon" href="../public/favicon.ico">
    <link rel="apple-touch-icon" href="../public/logo.svg">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/landing.css" rel="stylesheet">
    <!-- font-override.css moved after landing.css to allow overrides -->
    <link href="../assets/css/font-override.css" rel="stylesheet">
    <link href="../assets/css/notifications.css" rel="stylesheet">
    <!-- Number rendering fix for Windows 10/11 -->
    <!-- number-rendering-fix.css merged into font-override.css -->
    
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
        
        /* Prevent scrolling and zoom */
        html, body {
            overflow: hidden !important;
            height: 100% !important;
            width: 100% !important;
            position: fixed !important;
            touch-action: none !important;
        }
        
        /* Ensure login container fits viewport */
        .login-split-container {
            height: 100vh !important;
            overflow: hidden !important;
        }

        /* Shake animation for invalid login */
        @keyframes loginShake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }
        .auth-form-card.shake {
            animation: loginShake 0.55s ease-in-out;
        }

        /* AI Help widget */
        .ai-help-toggle-btn {
            position: fixed;
            right: 1.5rem;
            bottom: 1.5rem;
            z-index: 1200;
            width: 52px;
            height: 52px;
            border-radius: 999px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gold-primary, #ffd700) 0%, var(--gold-dark, #ffb300) 100%);
            color: #111827;
            box-shadow:
                0 8px 20px rgba(0, 0, 0, 0.25),
                0 0 20px rgba(255, 215, 0, 0.55);
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.2s ease;
        }
        .ai-help-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow:
                0 10px 26px rgba(0, 0, 0, 0.3),
                0 0 26px rgba(255, 215, 0, 0.7);
        }
        .ai-help-toggle-btn:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }
        .ai-help-toggle-btn i {
            font-size: 1.3rem;
        }

        .ai-help-panel {
            position: fixed;
            right: 1.5rem;
            bottom: 5rem;
            width: min(360px, 90vw);
            max-height: 70vh;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow:
                0 18px 50px rgba(15, 23, 42, 0.45),
                0 0 0 1px rgba(148, 163, 184, 0.45);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 1201;
        }
        .ai-help-panel.open {
            display: flex;
        }
        .ai-help-header {
            padding: 0.8rem 1rem;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .ai-help-title {
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .ai-help-title i {
            color: var(--gold-primary, #ffd700);
        }
        .ai-help-header-actions {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .ai-help-icon-btn {
            border: none;
            background: transparent;
            color: #9ca3af;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease, transform 0.12s ease;
        }
        .ai-help-icon-btn:hover {
            background: rgba(15, 23, 42, 0.4);
            color: #e5e7eb;
        }
        .ai-help-icon-btn:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 1px;
        }
        .ai-help-body {
            padding: 0.6rem 0.75rem 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            background: #f9fafb;
            flex: 1;
        }
        .ai-help-messages {
            flex: 1;
            min-height: 120px;
            max-height: 46vh;
            overflow-y: auto;
            padding-right: 0.35rem;
        }
        .ai-help-message {
            margin-bottom: 0.45rem;
            display: flex;
        }
        .ai-help-message.ai-user {
            justify-content: flex-end;
        }
        .ai-help-message.ai-assistant {
            justify-content: flex-start;
        }
        .ai-help-bubble {
            border-radius: 0.75rem;
            padding: 0.45rem 0.7rem;
            font-size: 0.78rem;
            line-height: 1.4;
            max-width: 88%;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        .ai-help-bubble.ai-user {
            background: linear-gradient(135deg, var(--gold-primary, #ffd700), var(--gold-dark, #ffb300));
            color: #111827;
        }
        .ai-help-bubble.ai-assistant {
            background: #111827;
            color: #e5e7eb;
        }
        .ai-help-meta {
            font-size: 0.68rem;
            color: #9ca3af;
            margin-top: 0.1rem;
        }
        .ai-help-input-row {
            border-top: 1px solid #e5e7eb;
            padding-top: 0.55rem;
            margin-top: 0.25rem;
        }
        .ai-help-form {
            display: flex;
            align-items: flex-end;
            gap: 0.35rem;
        }
        .ai-help-input-wrapper {
            flex: 1;
            position: relative;
        }
        .ai-help-input {
            width: 100%;
            border-radius: 0.55rem;
            border: 1px solid #e5e7eb;
            padding: 0.4rem 0.55rem 0.4rem 0.55rem;
            font-size: 0.8rem;
            resize: none;
            min-height: 2.4rem;
            max-height: 4.5rem;
            line-height: 1.4;
            outline: none;
        }
        .ai-help-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.25);
        }
        .ai-help-send-btn {
            border-radius: 999px;
            border: none;
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #111827;
            color: #f9fafb;
            cursor: pointer;
            transition: background 0.18s ease, transform 0.12s ease, box-shadow 0.18s ease;
        }
        .ai-help-send-btn:hover:not(:disabled) {
            background: #020617;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.4);
        }
        .ai-help-send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
        }
        .ai-help-footer-hint {
            margin-top: 0.25rem;
            font-size: 0.68rem;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .ai-help-footer-hint i {
            font-size: 0.65rem;
        }

        @media (max-width: 576px) {
            .ai-help-panel {
                right: 0.75rem;
                bottom: 4.75rem;
                width: min(94vw, 360px);
            }
            .ai-help-toggle-btn {
                right: 0.9rem;
                bottom: 1rem;
            }
        }
    </style>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body>
    <!-- Floating Background Elements - Professional Design -->
    <div class="floating-elements">
        <!-- Strategic Placement: Left Panel Area -->
        <i class="fas fa-shield-alt floating-icon shield size-xl" style="top: 18%; left: 8%; --float-duration: 32s;"></i>
        <i class="fas fa-star floating-icon star size-lg" style="top: 68%; left: 15%; --float-duration: 28s;"></i>
        <i class="fas fa-certificate floating-icon badge size-md" style="top: 42%; left: 12%; --float-duration: 26s;"></i>
        
        <!-- Center Accent Elements -->
        <div class="floating-icon circle size-xl" style="top: 25%; left: 48%; --float-duration: 22s;"></div>
        <i class="fas fa-user-shield floating-icon cap size-lg" style="top: 55%; left: 45%; --float-duration: 30s;"></i>
        <div class="floating-icon circle size-lg" style="top: 78%; left: 42%; --float-duration: 24s;"></div>
        
        <!-- Strategic Placement: Right Panel Area -->
        <i class="fas fa-award floating-icon badge size-lg" style="top: 15%; left: 82%; --float-duration: 29s;"></i>
        <i class="fas fa-star floating-icon star size-md" style="top: 48%; left: 88%; --float-duration: 27s;"></i>
        <i class="fas fa-shield-alt floating-icon shield size-md" style="top: 72%; left: 85%; --float-duration: 25s;"></i>
        
        <!-- Accent Highlights -->
        <i class="fas fa-star floating-icon star size-sm" style="top: 8%; left: 35%; --float-duration: 26s;"></i>
        <i class="fas fa-id-badge floating-icon badge size-sm" style="top: 88%; left: 28%; --float-duration: 28s;"></i>
        <div class="floating-icon circle size-md" style="top: 35%; left: 92%; --float-duration: 23s;"></div>
    </div>
    
    <!-- 
    ================================================================
    RESPONSIVE LOGIN LAYOUT - ORIENTATION-FIRST DESIGN
    ================================================================
    
    DESKTOP (Landscape):
    - Two-column layout: 55% branding (left) | 45% login (right)
    - Full content visibility
    
    TABLET (Portrait):
    - Vertical stacking: Branding (top) | Login (bottom)
    - Reduced content, centered login
    
    TABLET (Landscape):
    - Horizontal layout: 40% branding | 60% login
    - Full content visibility
    
    MOBILE (≤767px Portrait):
    - Single-column, task-focused
    - Minimal branding (logo + name)
    - Hidden: description, social links, buttons
    - Full-width login form priority
    
    MOBILE (≤767px Landscape, Short Height):
    - Compact horizontal: 35% branding | 65% login
    - Minimal content
    
    The layout adapts to ACTUAL device orientation and capabilities,
    not just browser window resizing.
    ================================================================
    -->
    <div class="login-split-container">
        <!-- Left Branded Panel -->
        <div class="login-branded-panel">
            <div class="branded-content">
                <img src="../public/logo.svg" alt="Golden Z-5 Security and Investigation Agency, Inc. Logo" class="branded-logo" onerror="this.style.display='none'">
                <h1 class="branded-headline">Golden Z-5 Security and Investigation Agency, Inc.</h1>
                <p class="branded-description">
                    Human Resources Management System<br>
                    Licensed by PNP-CSG-SAGSD | Registered with SEC
                </p>
                
                <!-- See More Button -->
                <button type="button" class="see-more-btn" id="seeMoreBtn">
                    <i class="fas fa-info-circle"></i> System Information
                </button>
                
                <!-- Social Links -->
                <div class="social-links">
                    <a href="mailto:goldenzfive@yahoo.com.ph" class="social-link" title="Email us">
                        <i class="fas fa-envelope"></i>
                    </a>
                    <a href="https://www.facebook.com/goldenZ5SA" target="_blank" rel="noopener noreferrer" class="social-link" title="Visit our Facebook page">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Form Panel - Centered Card -->
        <div class="login-form-panel">
            <div class="auth-form-container">
                <div class="auth-form-card">
                    <div class="auth-header">
                        <h2 class="auth-title">
                            Sign In
                        </h2>
                        <p class="auth-subtitle">Enter your authorized credentials to access the system</p>
                    </div>

                <?php if ($error && $error !== 'inactive' && $error !== 'suspended'): ?>
                    <div class="alert alert-danger" role="alert">
                        <div class="alert-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="alert-content">
                            <strong>Access Denied</strong>
                            <p>Invalid credentials. Verify your username and password and try again.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- AJAX login error (hidden by default; used when page does not reload) -->
                <div class="alert alert-danger d-none" id="loginErrorAlert" role="alert" aria-live="polite">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Access Denied</strong>
                        <p id="loginErrorMessage">Invalid credentials. Verify your username and password and try again.</p>
                    </div>
                </div>
                
                <?php if ($show_password_change_modal): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Password change required. You must set a new password to continue.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$show_password_change_modal): ?>
                <form method="POST" action="" id="loginForm" class="auth-form" novalidate>
                    <input type="hidden" name="login" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <!-- Validation Alert (Hidden by default) -->
                    <div class="system-alert system-alert-warning d-none" id="validationAlert" role="alert">
                        <div class="system-alert-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="system-alert-content">
                            <strong id="alertTitle">Required Information</strong>
                            <p id="alertMessage">All fields must be completed.</p>
                        </div>
                        <button type="button" class="system-alert-close" id="closeAlert" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">
                            Username
                            <span class="required-indicator" aria-label="Required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Username" 
                                   required 
                                   autocomplete="username"
                                   autofocus
                                   minlength="3"
                                   maxlength="100"
                                   pattern="^[a-zA-Z0-9._@+-]+$"
                                   aria-required="true"
                                   aria-describedby="username-error"
                                   data-validation-message="Enter a valid username"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : ''; ?>">
                            <div class="invalid-feedback" id="username-error" role="alert"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            Password
                            <span class="required-indicator" aria-label="Required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" 
                                   class="form-control password-input" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Password" 
                                   required 
                                   autocomplete="current-password"
                                   minlength="8"
                                   maxlength="255"
                                   aria-required="true"
                                   aria-describedby="password-error"
                                   data-validation-message="Minimum 8 characters required">
                            <button class="password-toggle" type="button" id="togglePassword" aria-label="Show password" tabindex="-1">
                                <i class="fas fa-eye" id="togglePasswordIcon" aria-hidden="true"></i>
                            </button>
                            <div class="invalid-feedback" id="password-error" role="alert"></div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <div class="form-check remember-me">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me" value="1">
                            <label class="form-check-label" for="rememberMe">
                                Remember credentials
                            </label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password-link" id="resetPasswordLink">
                            <span class="link-text">Forgot password?</span>
                            <span class="link-spinner d-none">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </a>
                    </div>

                    <div class="form-submit">
                        <button type="submit" name="login" class="btn btn-primary btn-block" id="submitBtn">
                            <span class="btn-text">Sign In</span>
                            <span class="btn-spinner d-none" id="submitSpinner">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>

                    <div class="form-footer">
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i>
                            <span>For assistance, contact your system administrator.</span>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Notification Icon - Upper Right -->
    <a href="alerts-display.php" class="notification-icon" title="View License Alerts">
        <i class="fas fa-bell"></i>
    </a>

    <!-- AI Help floating button & panel -->
    <button type="button"
            class="ai-help-toggle-btn"
            id="aiHelpToggleBtn"
            aria-label="Open AI Help chat"
            aria-haspopup="dialog"
            aria-expanded="false">
        <i class="fas fa-comments"></i>
    </button>

    <section class="ai-help-panel"
             id="aiHelpPanel"
             role="dialog"
             aria-modal="true"
             aria-labelledby="aiHelpTitle"
             aria-describedby="aiHelpDescription">
        <header class="ai-help-header">
            <div class="ai-help-title" id="aiHelpTitle">
                <i class="fas fa-robot" aria-hidden="true"></i>
                <span>AI Help</span>
            </div>
            <div class="ai-help-header-actions">
                <button type="button"
                        class="ai-help-icon-btn"
                        id="aiHelpClearBtn"
                        aria-label="Clear AI Help conversation">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <button type="button"
                        class="ai-help-icon-btn"
                        id="aiHelpCloseBtn"
                        aria-label="Close AI Help panel">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </header>
        <div class="ai-help-body">
            <div class="ai-help-messages" id="aiHelpMessages" aria-live="polite">
                <div class="ai-help-message ai-assistant">
                    <div class="ai-help-bubble ai-assistant">
                        <strong id="aiHelpDescription">Welcome.</strong>
                        <br>You can ask how to log in, reset your password, or who to contact for help.
                    </div>
                </div>
            </div>
            <div class="ai-help-input-row">
                <form class="ai-help-form" id="aiHelpForm" autocomplete="off">
                    <div class="ai-help-input-wrapper">
                        <textarea
                            class="ai-help-input"
                            id="aiHelpInput"
                            name="ai_help_input"
                            rows="2"
                            placeholder="Ask a quick question…"
                            aria-label="Ask AI Help a question"></textarea>
                    </div>
                    <button type="submit"
                            class="ai-help-send-btn"
                            id="aiHelpSendBtn"
                            aria-label="Send message to AI Help">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                <div class="ai-help-footer-hint">
                    <i class="fas fa-shield-alt"></i>
                    <span>AI Help won’t ask for passwords or codes.</span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- System Information Modal -->
    <div class="system-info-modal" id="systemInfoModal">
        <div class="system-info-overlay" id="systemInfoOverlay"></div>
        <div class="system-info-content">
            <button type="button" class="system-info-close" id="closeModalBtn" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="system-info-header">
                <img src="../public/logo.svg" alt="Golden Z-5 Logo" class="modal-logo" onerror="this.style.display='none'">
                <h2>Golden Z-5 HR Management System</h2>
                <p class="modal-subtitle">Comprehensive Workforce Management Solution</p>
            </div>
            
            <div class="system-info-body">
                <section class="info-section">
                    <h3><i class="fas fa-building"></i> About Golden Z-5</h3>
                    <p>
                        <strong>Golden Z-5 Security and Investigation Agency, Inc.</strong> is duly licensed by the 
                        PNP-CSG-SAGSD (Philippine National Police - Civil Security Group - Security Agencies and Guards 
                        Supervision Division) and registered with the Securities and Exchange Commission to provide 
                        professional Security Services.
                    </p>
                </section>
                
                <section class="info-section">
                    <h3><i class="fas fa-desktop"></i> System Overview</h3>
                    <p>
                        The Golden Z-5 HR Management System is a comprehensive digital platform designed to streamline 
                        workforce administration, enhance operational efficiency, and maintain compliance with regulatory 
                        requirements.
                    </p>
                </section>
                
                <section class="info-section">
                    <h3><i class="fas fa-users"></i> Departments & Users</h3>
                    <div class="features-grid">
                        <div class="feature-item">
                            <i class="fas fa-user-shield"></i>
                            <h4>Super Admin</h4>
                            <p>System-wide control and configuration</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-user-tie"></i>
                            <h4>HR Admin</h4>
                            <p>Employee management and HR operations</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-calculator"></i>
                            <h4>Accounting</h4>
                            <p>Financial and payroll management</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-cogs"></i>
                            <h4>Operations</h4>
                            <p>Daily operations and deployment</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-truck"></i>
                            <h4>Logistics</h4>
                            <p>Resource and equipment management</p>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-user"></i>
                            <h4>Employees</h4>
                            <p>Staff access and self-service</p>
                        </div>
                    </div>
                </section>
                
                <section class="info-section">
                    <h3><i class="fas fa-star"></i> Key Features</h3>
                    <ul class="features-list">
                        <li><i class="fas fa-check-circle"></i> <strong>Employee Management:</strong> Complete employee records, profiles, and documentation</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Posts & Assignments:</strong> Security post management and guard deployment tracking</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Team Management:</strong> Department and team organization</li>
                        <li><i class="fas fa-check-circle"></i> <strong>User Management:</strong> Role-based access control and permissions</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Alerts System:</strong> License expiry and compliance notifications</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Audit Trail:</strong> Complete activity logging and tracking</li>
                        <li><i class="fas fa-check-circle"></i> <strong>System Logs:</strong> Security and system monitoring</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Dashboard:</strong> Real-time insights and analytics</li>
                    </ul>
                </section>
                
                <section class="info-section">
                    <h3><i class="fas fa-shield-alt"></i> Security & Compliance</h3>
                    <p>
                        Built with enterprise-grade security features including role-based access control, 
                        two-factor authentication, audit trails, password policies, and session management 
                        to ensure data protection and regulatory compliance.
                    </p>
                </section>
                
                <section class="info-section contact-section">
                    <h3><i class="fas fa-envelope"></i> Contact Information</h3>
                    <div class="contact-info">
                        <p><strong>Email:</strong> <a href="mailto:goldenzfive@yahoo.com.ph">goldenzfive@yahoo.com.ph</a></p>
                        <p><strong>Facebook:</strong> <a href="https://www.facebook.com/goldenZ5SA" target="_blank" rel="noopener noreferrer">Golden Z-5 Security Agency</a></p>
                    </div>
                </section>
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
                    <button type="button" class="btn btn-link text-decoration-none p-0 me-2 fs-lg" onclick="window.location.href='?logout=1'" aria-label="Back" style="border: none; background: transparent; color: #6b7280;">
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
                            <small class="text-muted fs-13">Must be at least 8 characters long</small>
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
    <script src="../assets/js/notifications.js"></script>
    <script>
    // Simplified JavaScript - minimal interference
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded');
        
        // Prevent zoom with keyboard shortcuts (Ctrl/Cmd + Plus/Minus/0)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === '+' || e.key === '-' || e.key === '=' || e.key === '0' || e.keyCode === 187 || e.keyCode === 189 || e.keyCode === 48)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Prevent zoom with mouse wheel + Ctrl/Cmd
        document.addEventListener('wheel', function(e) {
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                return false;
            }
        }, { passive: false });
        
        // Prevent pinch zoom on touch devices
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(e) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });
        
        // Prevent scrolling
        document.addEventListener('scroll', function(e) {
            window.scrollTo(0, 0);
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        }, { passive: false });
        
        // Lock scroll position continuously
        function lockScroll() {
            window.scrollTo(0, 0);
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        }
        
        // Continuously lock scroll
        setInterval(lockScroll, 10);
        
        // Prevent scroll on window resize
        window.addEventListener('resize', function() {
            window.scrollTo(0, 0);
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        });
        
        // System Information Modal
        const seeMoreBtn = document.getElementById('seeMoreBtn');
        const systemInfoModal = document.getElementById('systemInfoModal');
        const systemInfoOverlay = document.getElementById('systemInfoOverlay');
        const closeModalBtn = document.getElementById('closeModalBtn');
        
        // Open modal
        if (seeMoreBtn) {
            seeMoreBtn.addEventListener('click', function() {
                systemInfoModal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            });
        }
        
        // Close modal function
        function closeModal() {
            systemInfoModal.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }
        
        // Close on X button
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeModal);
        }
        
        // Close on overlay click
        if (systemInfoOverlay) {
            systemInfoOverlay.addEventListener('click', closeModal);
        }
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && systemInfoModal.classList.contains('active')) {
                closeModal();
            }
        });
        
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
        
        // System Alert Close Button
        const closeAlertBtn = document.getElementById('closeAlert');
        if (closeAlertBtn) {
            closeAlertBtn.addEventListener('click', function() {
                const validationAlert = document.getElementById('validationAlert');
                if (validationAlert) {
                    validationAlert.classList.add('d-none');
                }
            });
        }
        
        // Reset Password Link - Show loading state
        const resetPasswordLink = document.getElementById('resetPasswordLink');
        if (resetPasswordLink) {
            resetPasswordLink.addEventListener('click', function(e) {
                // Add loading class
                this.classList.add('loading');
                
                // Store original text
                const linkText = this.querySelector('.link-text');
                if (linkText) {
                    linkText.setAttribute('data-original', linkText.textContent);
                    linkText.textContent = 'Redirecting...';
                }
                
                // Allow navigation to proceed
                // The loading state will be visible during page transition
            });
        }
        
        // Form submission - SIMPLIFIED
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                console.log('Form submit event triggered');
                
                const usernameEl = document.getElementById('username');
                const passwordEl = document.getElementById('password');
                const username = usernameEl ? usernameEl.value.trim() : '';
                const password = passwordEl ? passwordEl.value : '';
                
                console.log('Username:', username ? 'provided' : 'empty');
                console.log('Password:', password ? 'provided' : 'empty');
                
                // Always handle submit so we can run the transition
                e.preventDefault();
                
                // Basic validation
                if (!username || !password) {
                    // Show professional system alert
                    const validationAlert = document.getElementById('validationAlert');
                    const alertTitle = document.getElementById('alertTitle');
                    const alertMessage = document.getElementById('alertMessage');
                    
                    if (validationAlert && alertTitle && alertMessage) {
                        alertTitle.textContent = 'Required Fields Missing';
                        alertMessage.textContent = 'Please enter both username and password to continue.';
                        validationAlert.classList.remove('d-none');
                        
                        // Auto-hide after 5 seconds
                        setTimeout(() => {
                            validationAlert.classList.add('d-none');
                        }, 5000);
                    }
                    
                    if (!username && usernameEl) {
                        usernameEl.focus();
                    } else if (passwordEl) {
                        passwordEl.focus();
                    }
                    return false;
                }
                
                // Show loading state
                const submitBtn = document.getElementById('submitBtn');
                const btnText = submitBtn ? submitBtn.querySelector('.btn-text') : null;
                const spinner = document.getElementById('submitSpinner');
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    if (btnText) btnText.textContent = 'Signing in...';
                    if (spinner) spinner.classList.remove('d-none');
                }

                const authCard = document.querySelector('.auth-form-card');
                const errorAlert = document.getElementById('loginErrorAlert');
                const errorMessage = document.getElementById('loginErrorMessage');
                if (errorAlert) errorAlert.classList.add('d-none');

                // Trigger login transition: center card + portal animation (we'll keep it for success)
                document.body.classList.add('login-transition-active');

                // Add fullscreen circular loader overlay with smooth animations
                let spinnerOverlay = document.querySelector('.login-spinner-overlay');
                if (!spinnerOverlay) {
                    spinnerOverlay = document.createElement('div');
                    spinnerOverlay.className = 'login-spinner-overlay';
                    spinnerOverlay.innerHTML = `
                        <div class="login-spinner-container">
                            <div class="login-spinner"></div>
                            <p class="login-spinner-text">Logging in…</p>
                        </div>
                    `;
                    document.body.appendChild(spinnerOverlay);
                    
                    // Force reflow to ensure initial state
                    void spinnerOverlay.offsetHeight;
                    
                    // Trigger smooth fade-in
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            spinnerOverlay.classList.add('active');
                        });
                    });
                } else {
                    // Reset and reactivate if exists
                    spinnerOverlay.classList.remove('fade-out', 'active');
                    void spinnerOverlay.offsetHeight;
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            spinnerOverlay.classList.add('active');
                        });
                    });
                }
                // Ensure any previous portal overlay exists but has no solid background
                let existingOverlay = document.querySelector('.login-transition-overlay');
                if (!existingOverlay) {
                    existingOverlay = document.createElement('div');
                    existingOverlay.className = 'login-transition-overlay';
                    document.body.appendChild(existingOverlay);
                }

                function resetLoadingState() {
                    // Re-enable button/spinner
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (btnText) btnText.textContent = 'Sign In';
                        if (spinner) spinner.classList.add('d-none');
                    }

                    // Remove transition class
                    document.body.classList.remove('login-transition-active');

                    // Fade out spinner overlay if present
                    const overlay = document.querySelector('.login-spinner-overlay');
                    if (overlay) {
                        overlay.classList.add('fade-out');
                        setTimeout(() => {
                            overlay.remove();
                        }, 250);
                    }
                }

                function shakeLoginCard() {
                    if (!authCard) return;
                    authCard.classList.remove('shake');
                    // force reflow so animation can retrigger
                    void authCard.offsetWidth;
                    authCard.classList.add('shake');
                    setTimeout(() => authCard.classList.remove('shake'), 650);
                }

                const formData = new FormData(loginForm);
                fetch(loginForm.getAttribute('action') || window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(async (res) => {
                    const data = await res.json().catch(() => null);
                    if (!data) {
                        throw new Error('Invalid JSON response');
                    }
                    return data;
                })
                .then((data) => {
                    if (data.success && data.redirect) {
                        // Keep the animation, then navigate
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 850);
                        return;
                    }

                    // Failure: stop animation/loader and shake
                    resetLoadingState();
                    shakeLoginCard();

                    if (data && data.error === 'status' && data.status && data.message) {
                        // Reuse existing modal UI (same styling as server-rendered status errors)
                        const modal = document.getElementById('statusErrorModal');
                        const messageEl = document.getElementById('statusErrorMessage');
                        const titleEl = document.getElementById('statusErrorTitle');
                        const iconEl = document.getElementById('statusErrorIcon');
                        const infoBoxEl = document.getElementById('statusErrorInfoBox');
                        const infoTextEl = document.getElementById('statusErrorInfoText');

                        if (modal && messageEl && titleEl && iconEl && infoBoxEl && infoTextEl && window.bootstrap) {
                            messageEl.textContent = data.message;

                            if (data.status === 'inactive') {
                                titleEl.textContent = 'Account Inactive';
                                iconEl.className = 'fas fa-pause-circle futuristic-status-icon';
                                iconEl.style.color = '#94a3b8';
                                infoBoxEl.style.borderColor = 'rgba(148, 163, 184, 0.3)';
                                infoBoxEl.style.background = 'rgba(148, 163, 184, 0.1)';
                                infoTextEl.textContent = 'Your account has been deactivated. Contact your administrator to reactivate it.';
                            } else if (data.status === 'suspended') {
                                titleEl.textContent = 'Account Suspended';
                                iconEl.className = 'fas fa-ban futuristic-status-icon';
                                iconEl.style.color = '#ef4444';
                                infoBoxEl.style.borderColor = 'rgba(239, 68, 68, 0.3)';
                                infoBoxEl.style.background = 'rgba(239, 68, 68, 0.1)';
                                infoTextEl.textContent = 'Your account has been suspended. This action may be due to policy violations or security concerns.';
                            }

                            const modalInstance = new bootstrap.Modal(modal, {
                                backdrop: 'static',
                                keyboard: false,
                                focus: true
                            });
                            modalInstance.show();
                            return;
                        }
                    }

                    // Default error: show inline alert
                    if (errorMessage) {
                        errorMessage.textContent = (data && data.message) ? data.message : 'Login failed. Please try again.';
                    }
                    if (errorAlert) {
                        errorAlert.classList.remove('d-none');
                    }
                })
                .catch((err) => {
                    console.error('AJAX login error:', err);
                    resetLoadingState();
                    shakeLoginCard();
                    if (errorMessage) errorMessage.textContent = 'Login failed. Please try again.';
                    if (errorAlert) errorAlert.classList.remove('d-none');
                });
            });
        } else {
            console.error('Login form not found!');
        }

        // AI Help widget logic
        (function initAiHelpWidget() {
            const toggleBtn = document.getElementById('aiHelpToggleBtn');
            const panel = document.getElementById('aiHelpPanel');
            const closeBtn = document.getElementById('aiHelpCloseBtn');
            const clearBtn = document.getElementById('aiHelpClearBtn');
            const form = document.getElementById('aiHelpForm');
            const input = document.getElementById('aiHelpInput');
            const messagesEl = document.getElementById('aiHelpMessages');
            const sendBtn = document.getElementById('aiHelpSendBtn');

            if (!toggleBtn || !panel || !form || !input || !messagesEl || !sendBtn) {
                return;
            }

            const state = {
                open: false,
                // Each item: { role: 'user' | 'assistant', text: string }
                history: [],
                thinkingId: null
            };

            function scrollMessagesToBottom() {
                try {
                    messagesEl.scrollTop = messagesEl.scrollHeight;
                } catch (e) { /* ignore */ }
            }

            function createMessageElement(role, text, isMutedMeta) {
                const wrapper = document.createElement('div');
                wrapper.className = 'ai-help-message ' + (role === 'user' ? 'ai-user' : 'ai-assistant');

                const bubble = document.createElement('div');
                bubble.className = 'ai-help-bubble ' + (role === 'user' ? 'ai-user' : 'ai-assistant');
                bubble.textContent = text;
                wrapper.appendChild(bubble);

                if (isMutedMeta) {
                    const meta = document.createElement('div');
                    meta.className = 'ai-help-meta';
                    meta.textContent = isMutedMeta;
                    wrapper.appendChild(meta);
                }

                return wrapper;
            }

            function addMessage(role, text, options) {
                const opts = options || {};
                const el = createMessageElement(role, text, opts.meta || null);
                if (opts.replaceId && state.thinkingId && state.thinkingId === opts.replaceId) {
                    const existing = document.getElementById(state.thinkingId);
                    if (existing && existing.parentNode) {
                        existing.parentNode.replaceChild(el, existing);
                    } else {
                        messagesEl.appendChild(el);
                    }
                    state.thinkingId = null;
                } else {
                    messagesEl.appendChild(el);
                }

                if (!opts.skipHistory && role !== 'system') {
                    state.history.push({ role: role === 'user' ? 'user' : 'assistant', text: text });
                    if (state.history.length > 12) {
                        state.history = state.history.slice(-12);
                    }
                    try {
                        sessionStorage.setItem('ai_help_history', JSON.stringify(state.history));
                    } catch (e) { /* ignore */ }
                }

                scrollMessagesToBottom();
                return el;
            }

            function restoreHistoryFromStorage() {
                try {
                    const raw = sessionStorage.getItem('ai_help_history');
                    if (!raw) return;
                    const parsed = JSON.parse(raw);
                    if (!Array.isArray(parsed)) return;
                    state.history = [];
                    parsed.slice(-12).forEach(msg => {
                        if (!msg || typeof msg.text !== 'string') return;
                        const role = msg.role === 'assistant' ? 'assistant' : 'user';
                        addMessage(role, msg.text, { skipHistory: true });
                        state.history.push({ role, text: msg.text });
                    });
                    if (state.history.length > 0) {
                        scrollMessagesToBottom();
                    }
                } catch (e) {
                    // ignore storage issues
                }
            }

            restoreHistoryFromStorage();

            function openPanel() {
                if (state.open) return;
                state.open = true;
                panel.classList.add('open');
                toggleBtn.setAttribute('aria-expanded', 'true');
                setTimeout(() => {
                    input.focus();
                }, 30);
            }

            function closePanel() {
                if (!state.open) return;
                state.open = false;
                panel.classList.remove('open');
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.focus();
            }

            // Basic focus trap inside the panel when open
            function handleFocusTrap(e) {
                if (!state.open || e.key !== 'Tab') return;
                const focusableSelectors = [
                    'button:not([disabled])',
                    'textarea:not([disabled])',
                    'input:not([disabled])',
                    '[tabindex]:not([tabindex="-1"])'
                ];
                const nodes = panel.querySelectorAll(focusableSelectors.join(','));
                const list = Array.prototype.slice.call(nodes).filter(el => el.offsetParent !== null);
                if (!list.length) return;
                const first = list[0];
                const last = list[list.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    last.focus();
                    e.preventDefault();
                } else if (!e.shiftKey && document.activeElement === last) {
                    first.focus();
                    e.preventDefault();
                }
            }

            toggleBtn.addEventListener('click', function () {
                if (state.open) {
                    closePanel();
                } else {
                    openPanel();
                }
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', closePanel);
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    while (messagesEl.firstChild) {
                        messagesEl.removeChild(messagesEl.firstChild);
                    }
                    state.history = [];
                    try {
                        sessionStorage.removeItem('ai_help_history');
                    } catch (e) { /* ignore */ }
                    // Re-add welcome message
                    const welcome = document.createElement('div');
                    welcome.className = 'ai-help-message ai-assistant';
                    const bubble = document.createElement('div');
                    bubble.className = 'ai-help-bubble ai-assistant';
                    bubble.innerHTML = '<strong>Welcome.</strong><br>You can ask how to log in, reset your password, or who to contact for help.';
                    welcome.appendChild(bubble);
                    messagesEl.appendChild(welcome);
                    scrollMessagesToBottom();
                });
            }

            document.addEventListener('keydown', function (e) {
                if (!state.open) return;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closePanel();
                } else if (e.key === 'Tab') {
                    handleFocusTrap(e);
                }
            });

            function setThinking(isThinking) {
                if (isThinking) {
                    sendBtn.disabled = true;
                    input.setAttribute('aria-busy', 'true');
                    // Create or update lightweight "Thinking..." bubble
                    const tempId = 'ai-help-thinking';
                    let el = document.getElementById(tempId);
                    if (!el) {
                        el = document.createElement('div');
                        el.id = tempId;
                        el.className = 'ai-help-message ai-assistant';
                        const bubble = document.createElement('div');
                        bubble.className = 'ai-help-bubble ai-assistant';
                        bubble.textContent = 'Thinking…';
                        el.appendChild(bubble);
                        messagesEl.appendChild(el);
                    }
                    state.thinkingId = tempId;
                    scrollMessagesToBottom();
                } else {
                    sendBtn.disabled = false;
                    input.removeAttribute('aria-busy');
                }
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const text = (input.value || '').trim();
                if (!text) {
                    input.focus();
                    return;
                }

                addMessage('user', text);
                input.value = '';
                setThinking(true);

                const historyPayload = state.history.slice(-6).map(item => ({
                    role: item.role,
                    text: item.text
                }));

                fetch('/api/ai_help.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        message: text,
                        history: historyPayload
                    })
                })
                .then(async (res) => {
                    let json = null;
                    try {
                        json = await res.json();
                    } catch (err) {
                        throw new Error('Invalid JSON from AI Help');
                    }
                    return { ok: res.ok, data: json };
                })
                .then(({ ok, data }) => {
                    setThinking(false);
                    const reply = data && typeof data.reply === 'string'
                        ? data.reply
                        : (!ok ? 'AI help service is unavailable. Please try again later.' : 'I could not generate an answer. Please try again.');

                    if (!ok) {
                        addMessage('assistant', reply, { replaceId: state.thinkingId || 'ai-help-thinking', meta: null });
                        return;
                    }

                    // If backend flagged this as blocked (e.g., security-related), we still show the safe refusal text
                    addMessage('assistant', reply, { replaceId: state.thinkingId || 'ai-help-thinking' });
                })
                .catch(err => {
                    console.error('AI Help error:', err);
                    setThinking(false);
                    addMessage('assistant', 'Service unavailable. Try again later.', {
                        replaceId: state.thinkingId || 'ai-help-thinking'
                    });
                });
            });
        })();
    });
    </script>

    <!-- Futuristic Status Error Modal -->
    <div class="modal fade" id="statusErrorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content futuristic-status-modal">
                <div class="futuristic-status-header">
                    <div class="futuristic-status-icon-wrapper">
                        <i class="fas fa-ban futuristic-status-icon" id="statusErrorIcon"></i>
                        <div class="futuristic-status-pulse"></div>
                    </div>
                    <h5 class="futuristic-status-title" id="statusErrorTitle">Account Status</h5>
                </div>
                <div class="futuristic-status-body">
                    <p class="futuristic-status-message" id="statusErrorMessage"></p>
                    <div class="futuristic-status-info-box" id="statusErrorInfoBox">
                        <i class="fas fa-info-circle"></i>
                        <span id="statusErrorInfoText">Please contact your administrator for assistance.</span>
                    </div>
                </div>
                <div class="futuristic-status-footer">
                    <button type="button" class="btn futuristic-status-btn-ok" id="statusErrorOkBtn" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>Understood
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show status error modal if status error exists
        <?php if ($login_status_error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('statusErrorModal');
            const messageEl = document.getElementById('statusErrorMessage');
            const titleEl = document.getElementById('statusErrorTitle');
            const iconEl = document.getElementById('statusErrorIcon');
            const infoBoxEl = document.getElementById('statusErrorInfoBox');
            const infoTextEl = document.getElementById('statusErrorInfoText');
            
            if (modal && messageEl) {
                const statusType = '<?php echo htmlspecialchars($login_status_error); ?>';
                const statusMessage = '<?php echo htmlspecialchars($login_status_message); ?>';
                
                messageEl.textContent = statusMessage;
                
                if (statusType === 'inactive') {
                    titleEl.textContent = 'Account Inactive';
                    iconEl.className = 'fas fa-pause-circle futuristic-status-icon';
                    iconEl.style.color = '#94a3b8';
                    infoBoxEl.style.borderColor = 'rgba(148, 163, 184, 0.3)';
                    infoBoxEl.style.background = 'rgba(148, 163, 184, 0.1)';
                    infoTextEl.textContent = 'Your account has been deactivated. Contact your administrator to reactivate it.';
                } else if (statusType === 'suspended') {
                    titleEl.textContent = 'Account Suspended';
                    iconEl.className = 'fas fa-ban futuristic-status-icon';
                    iconEl.style.color = '#ef4444';
                    infoBoxEl.style.borderColor = 'rgba(239, 68, 68, 0.3)';
                    infoBoxEl.style.background = 'rgba(239, 68, 68, 0.1)';
                    infoTextEl.textContent = 'Your account has been suspended. This action may be due to policy violations or security concerns.';
                }
                
                // Show modal using Bootstrap
                const modalInstance = new bootstrap.Modal(modal, {
                    backdrop: 'static',
                    keyboard: false,
                    focus: true
                });
                
                modalInstance.show();
                
                // Ensure modal is visible and properly positioned
                setTimeout(() => {
                    modal.style.display = 'block';
                    modal.style.zIndex = '1060';
                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    modal.setAttribute('aria-modal', 'true');
                    
                    const modalDialog = modal.querySelector('.modal-dialog');
                    if (modalDialog) {
                        modalDialog.style.zIndex = '1061';
                        modalDialog.style.pointerEvents = 'auto';
                        modalDialog.style.margin = '1.75rem auto';
                    }
                    
                    // Ensure backdrop exists
                    let backdrop = document.querySelector('.modal-backdrop');
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                    }
                    backdrop.style.zIndex = '1059';
                    backdrop.classList.add('show');
                    
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                }, 50);
            }
        });
        <?php endif; ?>
    </script>

    <style>
    /* Futuristic Status Error Modal Styles */
    #statusErrorModal {
        z-index: 1060 !important;
    }

    #statusErrorModal .modal-dialog {
        z-index: 1061 !important;
        position: relative;
        margin: 1.75rem auto;
        pointer-events: auto;
        max-width: 500px;
    }

    #statusErrorModal .modal-backdrop {
        z-index: 1059 !important;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
    }

    .futuristic-status-modal {
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.95) 100%);
        border: none;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                    0 0 0 1px rgba(99, 102, 241, 0.3),
                    inset 0 1px 0 rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        overflow: hidden;
        position: relative;
        pointer-events: auto;
        z-index: 1;
    }

    .futuristic-status-modal::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, 
            rgba(99, 102, 241, 0.1) 0%, 
            rgba(168, 85, 247, 0.1) 50%, 
            rgba(236, 72, 153, 0.1) 100%);
        opacity: 0.6;
        z-index: 0;
        animation: statusGradientShift 8s ease infinite;
    }

    @keyframes statusGradientShift {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 0.8; }
    }

    .futuristic-status-modal > * {
        position: relative;
        z-index: 1;
    }

    .futuristic-status-header {
        padding: 2rem 2rem 1rem;
        text-align: center;
        border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        background: linear-gradient(180deg, rgba(99, 102, 241, 0.1) 0%, transparent 100%);
    }

    .futuristic-status-icon-wrapper {
        position: relative;
        display: inline-block;
        margin-bottom: 1rem;
    }

    .futuristic-status-icon {
        font-size: 3rem;
        text-shadow: 0 0 20px currentColor,
                     0 0 40px currentColor;
        animation: statusIconPulse 2s ease-in-out infinite;
        position: relative;
        z-index: 2;
    }

    @keyframes statusIconPulse {
        0%, 100% { 
            transform: scale(1);
            filter: brightness(1);
        }
        50% { 
            transform: scale(1.1);
            filter: brightness(1.3);
        }
    }

    .futuristic-status-pulse {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80px;
        height: 80px;
        border: 2px solid currentColor;
        border-radius: 50%;
        opacity: 0.5;
        animation: statusPulseRing 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes statusPulseRing {
        0% {
            transform: translate(-50%, -50%) scale(0.8);
            opacity: 0.5;
        }
        100% {
            transform: translate(-50%, -50%) scale(1.5);
            opacity: 0;
        }
    }

    .futuristic-status-title {
        color: #ffffff;
        font-weight: 600;
        font-size: 1.5rem;
        margin: 0;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        letter-spacing: 0.5px;
    }

    .futuristic-status-body {
        padding: 2rem;
        color: #e2e8f0;
    }

    .futuristic-status-message {
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 1.5rem;
        color: #cbd5e1;
        text-align: center;
    }

    .futuristic-status-info-box {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        border: 1px solid rgba(99, 102, 241, 0.3);
        border-radius: 12px;
        color: #a5b4fc;
        font-size: 0.9rem;
        backdrop-filter: blur(10px);
    }

    .futuristic-status-info-box i {
        font-size: 1.2rem;
        color: #818cf8;
        flex-shrink: 0;
    }

    .futuristic-status-footer {
        padding: 1.5rem 2rem 2rem;
        display: flex;
        justify-content: center;
        border-top: 1px solid rgba(99, 102, 241, 0.2);
        background: linear-gradient(180deg, transparent 0%, rgba(99, 102, 241, 0.05) 100%);
    }

    .futuristic-status-btn-ok {
        padding: 0.75rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4),
                    0 0 20px rgba(99, 102, 241, 0.2);
        background-size: 200% 200%;
        animation: statusGradientMove 3s ease infinite;
        pointer-events: auto !important;
        cursor: pointer !important;
        z-index: 10;
    }

    @keyframes statusGradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .futuristic-status-btn-ok:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 6px 25px rgba(99, 102, 241, 0.6),
                    0 0 30px rgba(139, 92, 246, 0.4);
    }

    .futuristic-status-btn-ok:active {
        transform: translateY(0) scale(0.98);
    }

    #statusErrorModal.show {
        display: block !important;
        z-index: 1060 !important;
        padding-right: 0 !important;
    }

    #statusErrorModal .modal-content {
        pointer-events: auto !important;
    }

    @media (max-width: 576px) {
        .futuristic-status-header {
            padding: 1.5rem 1.5rem 0.75rem;
        }
        
        .futuristic-status-icon {
            font-size: 2.5rem;
        }
        
        .futuristic-status-title {
            font-size: 1.25rem;
        }
        
        .futuristic-status-body {
            padding: 1.5rem;
        }
        
        .futuristic-status-footer {
            padding: 1rem 1.5rem 1.5rem;
        }
    }
    </style>
</body>
</html>

