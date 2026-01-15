<?php
/**
 * RESET PASSWORD PAGE
 * 
 * Allows users to reset their password using a secure token sent via email.
 * Validates the token and allows setting a new password.
 */

ob_start();

// Load environment variables FIRST (before any other code)
require_once __DIR__ . '/../bootstrap/env.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start session
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

// Bootstrap application
try {
    if (file_exists(__DIR__ . '/../bootstrap/app.php')) {
        require_once __DIR__ . '/../bootstrap/app.php';
    } else {
        require_once __DIR__ . '/../bootstrap/autoload.php';
    }
} catch (Exception $e) {
    error_log('Bootstrap error: ' . $e->getMessage());
}

// Include database functions
require_once __DIR__ . '/../includes/database.php';

$error = '';
$success = false;
$token_valid = false;
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

// Validate token and email parameters
if (empty($token) || empty($email)) {
    $error = 'Invalid or missing reset link. Please request a new password reset.';
} else {
    try {
        $pdo = get_db_connection();
        
        // Check if password_reset_token column exists
        $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_reset_token'");
        $has_reset_columns = $check_column->rowCount() > 0;
        
        if ($has_reset_columns) {
            // Use dedicated password reset fields
            $sql = "SELECT id, username, name, email, status, password_reset_token, password_reset_expires_at 
                    FROM users 
                    WHERE email = ? AND status = 'active' 
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['password_reset_token'])) {
                $stored_token = $user['password_reset_token'];
                $expires_at = $user['password_reset_expires_at'];
                
                // Verify token matches and hasn't expired
                if (hash_equals($stored_token, $token) && strtotime($expires_at) > time()) {
                    $token_valid = true;
                } else {
                    $error = 'This password reset link has expired or is invalid. Please request a new password reset.';
                }
            } else {
                $error = 'Invalid or expired reset link. Please request a new password reset.';
            }
        } else {
            // Fallback: Use remember_token field
            $sql = "SELECT id, username, name, email, status, remember_token, password_changed_at 
                    FROM users 
                    WHERE email = ? AND status = 'active' 
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['remember_token'])) {
                // Parse token data (format: token|expiry_timestamp)
                $token_data = explode('|', $user['remember_token']);
                $stored_token = $token_data[0] ?? '';
                $expiry_timestamp = isset($token_data[1]) ? (int)$token_data[1] : 0;
                
                // Verify token matches and hasn't expired
                if (hash_equals($stored_token, $token) && $expiry_timestamp > time()) {
                    $token_valid = true;
                } else {
                    $error = 'This password reset link has expired or is invalid. Please request a new password reset.';
                }
            } else {
                $error = 'Invalid or expired reset link. Please request a new password reset.';
            }
        }
    } catch (Exception $e) {
        error_log('Token validation error: ' . $e->getMessage());
        $error = 'An error occurred while validating the reset link. Please try again.';
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = get_db_connection();
            
            // Get user to ensure we have the latest data
            $sql = "SELECT id, username, email FROM users WHERE email = ? AND status = 'active' LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && isset($user['id'])) {
                $user_id = (int)$user['id'];
                
                // Hash new password using bcrypt
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in database
                $update_sql = "UPDATE users 
                              SET password_hash = ?, 
                                  password_changed_at = NOW(), 
                                  password_reset_token = NULL,
                                  password_reset_expires_at = NULL,
                                  failed_login_attempts = 0,
                                  locked_until = NULL
                              WHERE id = ?";
                
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->bindValue(1, $new_password_hash, PDO::PARAM_STR);
                $update_stmt->bindValue(2, $user_id, PDO::PARAM_INT);
                $update_result = $update_stmt->execute();
                
                if ($update_result && $update_stmt->rowCount() > 0) {
                    // Verify the password was updated correctly
                    $verify_sql = "SELECT password_hash FROM users WHERE id = ? LIMIT 1";
                    $verify_stmt = $pdo->prepare($verify_sql);
                    $verify_stmt->execute([$user_id]);
                    $updated_user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($updated_user && password_verify($new_password, $updated_user['password_hash'])) {
                        // Log security event
                        if (function_exists('log_security_event')) {
                            log_security_event('Password Reset Completed', "User: {$user['username']} ({$user['email']}) - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
                        }
                        
                        $success = true;
                        $token_valid = false; // Invalidate token after use
                    } else {
                        $error = 'Password was updated but verification failed. Please contact support.';
                    }
                } else {
                    $error = 'Failed to update password in database. Please try again.';
                }
            } else {
                $error = 'User not found. Please request a new password reset.';
            }
        } catch (Exception $e) {
            error_log('Password reset error: ' . $e->getMessage());
            $error = 'An error occurred while resetting your password. Please try again.';
        }
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Golden Z-5 HR Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../public/logo.svg">
    <link rel="icon" type="image/x-icon" href="../public/favicon.ico">
    <link rel="apple-touch-icon" href="../public/logo.svg">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/landing.css" rel="stylesheet">
    <link href="../assets/css/font-override.css" rel="stylesheet">
    <!-- Number rendering fix for Windows 10/11 -->
    <link href="../assets/css/number-rendering-fix.css" rel="stylesheet">
    
    <style>
        .password-toggle i,
        .password-toggle i::before,
        .password-toggle i::after,
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
        
        .password-input-group {
            position: relative;
        }
        
        .password-input-group .form-control {
            padding-right: 3rem;
        }
        
        .password-toggle {
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
        
        .password-toggle:hover {
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="login-split-container">
        <!-- Left Branded Panel -->
        <div class="login-branded-panel">
            <div class="branded-content">
                <img src="../public/logo.svg" alt="Golden Z-5 Logo" class="branded-logo" onerror="this.style.display='none'">
                <h1 class="branded-headline">Set New Password</h1>
                <p class="branded-description">Create a strong password to secure your account.</p>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="login-form-panel">
            <div class="auth-form-container">
                <div class="auth-form-card">
                    <div class="auth-header">
                        <h2 class="auth-title">Reset Password</h2>
                        <p class="auth-subtitle">Enter your new password below</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            Your password has been successfully reset! You can now login with your new password.
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i> Go to Login
                            </a>
                        </div>
                    <?php elseif ($token_valid): ?>
                    <form method="POST" action="" id="resetPasswordForm" class="auth-form">
                        <input type="hidden" name="reset_password" value="1">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group password-input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Enter new password" 
                                       required 
                                       autocomplete="new-password"
                                       autofocus
                                       minlength="8">
                                <button class="password-toggle" type="button" id="toggleNewPassword">
                                    <i class="fas fa-eye" id="toggleNewPasswordIcon"></i>
                                </button>
                            </div>
                            <small class="text-muted" style="font-size: 0.8125rem;">Must be at least 8 characters long</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group password-input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Re-enter new password" 
                                       required 
                                       autocomplete="new-password"
                                       minlength="8">
                                <button class="password-toggle" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye" id="toggleConfirmPasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="reset_password" class="btn btn-primary btn-lg" id="submitBtn">
                                <span class="btn-text">Reset Password</span>
                                <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="text-center mt-4">
                            <a href="forgot-password.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-redo me-2"></i> Request New Reset Link
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <a href="index.php" class="text-decoration-none" style="color: #2563eb; font-size: 0.875rem; font-weight: 500;">
                            <i class="fas fa-arrow-left me-1"></i> Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
        
        // Form validation
        const form = document.getElementById('resetPasswordForm');
        if (form) {
            form.addEventListener('submit', function(e) {
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
                const submitBtn = document.getElementById('submitBtn');
                const btnText = submitBtn.querySelector('.btn-text');
                const spinner = document.getElementById('submitSpinner');
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    if (btnText) btnText.textContent = 'Resetting Password...';
                    if (spinner) spinner.classList.remove('d-none');
                }
            });
        }
    });
    </script>
</body>
</html>
