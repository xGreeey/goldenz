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
    <title>Reset Password</title>
    
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
    <!-- number-rendering-fix.css merged into font-override.css -->
    
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
            transition: color 0.2s ease;
        }
        
        .password-toggle:hover {
            color: #2563eb;
        }

        /* Password Strength Indicator */
        .password-strength-container {
            margin-top: 0.75rem;
        }

        .password-strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .password-strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .password-strength-fill.weak {
            width: 33%;
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .password-strength-fill.fair {
            width: 66%;
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .password-strength-fill.good {
            width: 85%;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .password-strength-fill.strong {
            width: 100%;
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .password-strength-text.weak { color: #ef4444; }
        .password-strength-text.fair { color: #f59e0b; }
        .password-strength-text.good { color: #3b82f6; }
        .password-strength-text.strong { color: #10b981; }

        /* Password Requirements Checklist */
        .password-requirements {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .password-requirements-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .password-requirement-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .password-requirement-item:last-child {
            margin-bottom: 0;
        }

        .password-requirement-item.valid {
            color: #10b981;
        }

        .password-requirement-item.valid .requirement-icon {
            color: #10b981;
        }

        .requirement-icon {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            transition: all 0.2s ease;
        }

        .requirement-icon i {
            font-size: 12px;
        }

        /* Password Match Indicator */
        .password-match-indicator {
            margin-top: 0.5rem;
            font-size: 0.8125rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .password-match-indicator.show {
            opacity: 1;
        }

        .password-match-indicator.match {
            color: #10b981;
        }

        .password-match-indicator.mismatch {
            color: #ef4444;
        }

        /* Enhanced Success State */
        .success-state {
            text-align: center;
            padding: 2rem 1rem;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #10b981, #34d399);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease;
        }

        .success-icon i {
            font-size: 2.5rem;
            color: white;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-message {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .success-description {
            font-size: 0.9375rem;
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        /* Enhanced Form Animations */
        .form-group {
            animation: fadeInUp 0.4s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading State for Button */
        .btn-primary:disabled .btn-text {
            display: none !important;
        }

        .btn-primary:disabled .spinner-border {
            display: inline-block !important;
            margin: 0 auto;
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
                        <div class="success-state">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h3 class="success-message">Password Reset Successful!</h3>
                            <p class="success-description">Your password has been successfully reset. You will be redirected to the login page shortly.</p>
                            <div class="text-center mt-4">
                                <a href="index.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i> Go to Login Now
                                </a>
                            </div>
                            <div class="text-center mt-3">
                                <small class="text-muted" id="redirectCountdown">Redirecting in 5 seconds...</small>
                            </div>
                        </div>
                    <?php elseif ($token_valid): ?>
                    <form method="POST" action="" id="resetPasswordForm" class="auth-form">
                        <input type="hidden" name="reset_password" value="1">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">
                                <i class="fas fa-lock me-1"></i> New Password
                            </label>
                            <div class="input-group password-input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Create a strong password" 
                                       required 
                                       autocomplete="new-password"
                                       autofocus
                                       minlength="8">
                                <button class="password-toggle" type="button" id="toggleNewPassword">
                                    <i class="fas fa-eye" id="toggleNewPasswordIcon"></i>
                                </button>
                            </div>
                            
                            <!-- Password Strength Indicator -->
                            <div class="password-strength-container">
                                <div class="password-strength-bar">
                                    <div class="password-strength-fill" id="strengthBar"></div>
                                </div>
                                <div class="password-strength-text" id="strengthText"></div>
                            </div>

                            <!-- Password Requirements Checklist -->
                            <div class="password-requirements">
                                <div class="password-requirements-title">
                                    <i class="fas fa-list-check"></i>
                                    Password Requirements
                                </div>
                                <div class="password-requirement-item" id="reqLength">
                                    <span class="requirement-icon"><i class="fas fa-circle"></i></span>
                                    <span>At least 8 characters</span>
                                </div>
                                <div class="password-requirement-item" id="reqUppercase">
                                    <span class="requirement-icon"><i class="fas fa-circle"></i></span>
                                    <span>One uppercase letter</span>
                                </div>
                                <div class="password-requirement-item" id="reqLowercase">
                                    <span class="requirement-icon"><i class="fas fa-circle"></i></span>
                                    <span>One lowercase letter</span>
                                </div>
                                <div class="password-requirement-item" id="reqNumber">
                                    <span class="requirement-icon"><i class="fas fa-circle"></i></span>
                                    <span>One number</span>
                                </div>
                                <div class="password-requirement-item" id="reqSpecial">
                                    <span class="requirement-icon"><i class="fas fa-circle"></i></span>
                                    <span>One special character</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-1"></i> Confirm New Password
                            </label>
                            <div class="input-group password-input-group">
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
                            <div class="password-match-indicator" id="passwordMatchIndicator">
                                <i class="fas fa-check-circle"></i>
                                <span>Passwords match</span>
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
                        <div class="text-center mt-4 py-4">
                            <div class="mb-4">
                                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #f59e0b; margin-bottom: 1rem;"></i>
                                <h4 class="mb-2">Invalid or Expired Link</h4>
                                <p class="text-muted mb-4">This password reset link has expired or is invalid. Please request a new one.</p>
                            </div>
                            <a href="forgot-password.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-redo me-2"></i> Request New Reset Link
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <a href="index.php" class="text-decoration-none fs-sm fw-medium" style="color: #2563eb;">
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
        
        // Password Strength Calculation
        function calculatePasswordStrength(password) {
            let strength = 0;
            let strengthClass = '';
            let strengthText = '';
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthClass = 'weak';
                strengthText = 'Weak';
            } else if (strength <= 3) {
                strengthClass = 'fair';
                strengthText = 'Fair';
            } else if (strength <= 4) {
                strengthClass = 'good';
                strengthText = 'Good';
            } else {
                strengthClass = 'strong';
                strengthText = 'Strong';
            }
            
            return { strengthClass, strengthText };
        }
        
        // Check Password Requirements
        function checkPasswordRequirements(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
            
            return requirements;
        }
        
        // Update Password Strength Indicator
        function updatePasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            if (!password) {
                strengthBar.className = 'password-strength-fill';
                strengthBar.style.width = '0%';
                strengthText.textContent = '';
                strengthText.className = 'password-strength-text';
                return;
            }
            
            const { strengthClass, strengthText: text } = calculatePasswordStrength(password);
            strengthBar.className = `password-strength-fill ${strengthClass}`;
            strengthText.textContent = text;
            strengthText.className = `password-strength-text ${strengthClass}`;
        }
        
        // Update Requirements Checklist
        function updateRequirementsChecklist(password) {
            const requirements = checkPasswordRequirements(password);
            
            const reqLength = document.getElementById('reqLength');
            const reqUppercase = document.getElementById('reqUppercase');
            const reqLowercase = document.getElementById('reqLowercase');
            const reqNumber = document.getElementById('reqNumber');
            const reqSpecial = document.getElementById('reqSpecial');
            
            function updateRequirement(element, isValid) {
                if (isValid) {
                    element.classList.add('valid');
                    const icon = element.querySelector('.requirement-icon i');
                    icon.className = 'fas fa-check-circle';
                } else {
                    element.classList.remove('valid');
                    const icon = element.querySelector('.requirement-icon i');
                    icon.className = 'fas fa-circle';
                }
            }
            
            updateRequirement(reqLength, requirements.length);
            updateRequirement(reqUppercase, requirements.uppercase);
            updateRequirement(reqLowercase, requirements.lowercase);
            updateRequirement(reqNumber, requirements.number);
            updateRequirement(reqSpecial, requirements.special);
        }
        
        // Check Password Match
        function checkPasswordMatch() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const matchIndicator = document.getElementById('passwordMatchIndicator');
            
            if (!confirmPassword) {
                matchIndicator.classList.remove('show', 'match', 'mismatch');
                return;
            }
            
            matchIndicator.classList.add('show');
            
            if (newPassword === confirmPassword && newPassword.length > 0) {
                matchIndicator.classList.add('match');
                matchIndicator.classList.remove('mismatch');
                matchIndicator.innerHTML = '<i class="fas fa-check-circle"></i><span>Passwords match</span>';
            } else {
                matchIndicator.classList.add('mismatch');
                matchIndicator.classList.remove('match');
                matchIndicator.innerHTML = '<i class="fas fa-times-circle"></i><span>Passwords do not match</span>';
            }
        }
        
        // Event Listeners for Password Inputs
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                updatePasswordStrength(password);
                updateRequirementsChecklist(password);
                checkPasswordMatch();
            });
        }
        
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                checkPasswordMatch();
            });
        }
        
        // Form validation
        const form = document.getElementById('resetPasswordForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                // Check all requirements
                const requirements = checkPasswordRequirements(newPassword);
                const allMet = Object.values(requirements).every(req => req === true);
                
                if (!allMet) {
                    e.preventDefault();
                    alert('Please ensure your password meets all requirements.');
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
                    // Hide text and show centered spinner
                    if (btnText) btnText.style.display = 'none';
                    if (spinner) {
                        spinner.classList.remove('d-none');
                        spinner.style.display = 'inline-block';
                    }
                }
            });
        }
        
        // Auto-redirect after success
        const successState = document.querySelector('.success-state');
        if (successState) {
            let countdown = 5;
            const countdownElement = document.getElementById('redirectCountdown');
            
            const countdownInterval = setInterval(function() {
                countdown--;
                if (countdownElement) {
                    countdownElement.textContent = `Redirecting to login page in ${countdown} ${countdown === 1 ? 'second' : 'seconds'}...`;
                }
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = 'index.php';
                }
            }, 1000);
        }
    });
    </script>
</body>
</html>
