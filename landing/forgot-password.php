<?php
/**
 * FORGOT PASSWORD PAGE
 * 
 * Allows users to request a password reset by entering their email address.
 * Sends a password reset email with a secure token.
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

// Load PHPMailer
require_once __DIR__ . '/../config/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = false;
$email_sent = false;

// Handle forgot password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = get_db_connection();
            
            // Check if user exists with this email
            $sql = "SELECT id, username, name, email, status FROM users WHERE email = ? AND status = 'active' LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate secure reset token
                $reset_token = bin2hex(random_bytes(32)); // 64 character token
                $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
                
                // Check if password_reset_token column exists
                $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_reset_token'");
                $has_reset_columns = $check_column->rowCount() > 0;
                
                if ($has_reset_columns) {
                    // Use dedicated password reset fields
                    $update_sql = "UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_stmt->execute([$reset_token, $reset_expires, $user['id']]);
                } else {
                    // Fallback: Use remember_token field (store token and expiry in format: token|expiry_timestamp)
                    $update_sql = "UPDATE users SET remember_token = ?, password_changed_at = ? WHERE id = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    $token_data = $reset_token . '|' . strtotime($reset_expires);
                    $update_stmt->execute([$token_data, $reset_expires, $user['id']]);
                }
                
                // Generate reset URL - use improved HTTPS detection
                require_once __DIR__ . '/../includes/paths.php';
                $scheme = is_https() ? 'https' : 'http';
                $reset_url = $scheme . '://' . $_SERVER['HTTP_HOST'] 
                    . dirname($_SERVER['PHP_SELF']) 
                    . '/reset-password.php?token=' . urlencode($reset_token) . '&email=' . urlencode($email);
                
                // Send email using PHPMailer (SMTP only)
                $mail = new PHPMailer(true);
                
                try {
                    // Validate required SMTP environment variables
                    $smtp_host = $_ENV['SMTP_HOST'] ?? null;
                    $smtp_username = $_ENV['SMTP_USERNAME'] ?? null;
                    $smtp_password = $_ENV['SMTP_PASSWORD'] ?? null;
                    $smtp_port = $_ENV['SMTP_PORT'] ?? '587';
                    $smtp_encryption_raw = $_ENV['SMTP_ENCRYPTION'] ?? 'tls';
                    $mail_from_address = $_ENV['MAIL_FROM_ADDRESS'] ?? null;
                    $mail_from_name = $_ENV['MAIL_FROM_NAME'] ?? 'Golden Z-5 HR System';
                    
                    if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password) || empty($mail_from_address)) {
                        throw new Exception('SMTP configuration is incomplete. Please set SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD, and MAIL_FROM_ADDRESS environment variables in your .env file.');
                    }
                    
                    // Map encryption string to PHPMailer constant
                    $smtp_encryption = PHPMailer::ENCRYPTION_STARTTLS; // Default
                    if (strtolower($smtp_encryption_raw) === 'ssl') {
                        $smtp_encryption = PHPMailer::ENCRYPTION_SMTPS;
                    } elseif (strtolower($smtp_encryption_raw) === 'tls' || strtolower($smtp_encryption_raw) === 'starttls') {
                        $smtp_encryption = PHPMailer::ENCRYPTION_STARTTLS;
                    }
                    
                    // SMTP Server Configuration (Gmail-compatible)
                    $mail->isSMTP();
                    $mail->Host = $smtp_host;
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp_username;
                    $mail->Password = $smtp_password;
                    $mail->SMTPSecure = $smtp_encryption;
                    $mail->Port = (int)$smtp_port;
                    $mail->CharSet = 'UTF-8';
                    
                    // SMTP Debug Options (commented out - enable for troubleshooting)
                    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
                    // $mail->SMTPDebug = SMTP::DEBUG_CLIENT; // Enable client debug output
                    // $mail->SMTPDebug = SMTP::DEBUG_CONNECTION; // Enable connection debug output
                    // $mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL; // Enable low-level debug output
                    
                    // Additional SMTP Options for Gmail/localhost compatibility
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );
                    
                    // Recipients
                    $mail->setFrom($mail_from_address, $mail_from_name);
                    $mail->addAddress($email, htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'));
                    
                    // Email Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request - Golden Z-5 HR System';
                    
                    // Sanitize user data for HTML email body
                    $user_name_safe = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
                    $reset_url_safe = htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8');
                    
                    $mail->Body = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <style>
                                * { margin: 0; padding: 0; box-sizing: border-box; }
                                body { 
                                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                                    line-height: 1.6; 
                                    color: #1e293b; 
                                    background-color: #f1f5f9;
                                    padding: 20px;
                                }
                                .email-wrapper {
                                    max-width: 600px; 
                                    margin: 0 auto; 
                                    background-color: #ffffff;
                                    border-radius: 8px;
                                    overflow: hidden;
                                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                                }
                                .header { 
                                    background: linear-gradient(135deg, #1e3a8a 0%, #6366f1 50%, #8b5cf6 100%);
                                    color: #fff; 
                                    padding: 40px 20px; 
                                    text-align: center; 
                                }
                                .header-icons {
                                    display: flex;
                                    justify-content: center;
                                    align-items: center;
                                    gap: 8px;
                                    margin-bottom: 16px;
                                }
                                .header-icon {
                                    width: 32px;
                                    height: 32px;
                                    fill: #fbbf24;
                                }
                                .header h1 {
                                    font-size: 28px;
                                    font-weight: 700;
                                    margin: 0;
                                    letter-spacing: -0.5px;
                                }
                                .content { 
                                    background-color: #ffffff; 
                                    padding: 40px 30px; 
                                }
                                .content h2 {
                                    font-size: 24px;
                                    font-weight: 700;
                                    color: #1e293b;
                                    margin-bottom: 20px;
                                }
                                .content p {
                                    font-size: 16px;
                                    color: #475569;
                                    margin-bottom: 16px;
                                    line-height: 1.6;
                                }
                                .button-container {
                                    text-align: center;
                                    margin: 30px 0;
                                }
                                .button { 
                                    display: inline-block; 
                                    padding: 14px 32px; 
                                    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                                    color: #fff !important; 
                                    text-decoration: none; 
                                    border-radius: 8px; 
                                    font-weight: 600;
                                    font-size: 16px;
                                    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
                                    transition: all 0.3s ease;
                                }
                                .button:hover { 
                                    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                                    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
                                    transform: translateY(-2px);
                                }
                                .button-icon {
                                    display: inline-block;
                                    width: 18px;
                                    height: 18px;
                                    fill: #fbbf24;
                                    vertical-align: middle;
                                    margin-right: 8px;
                                }
                                .fallback-section {
                                    background-color: #f8fafc;
                                    border-left: 4px solid #3b82f6;
                                    padding: 20px;
                                    margin: 30px 0;
                                    border-radius: 4px;
                                }
                                .fallback-section p {
                                    margin-bottom: 8px;
                                    font-size: 14px;
                                    color: #64748b;
                                }
                                .fallback-link {
                                    word-break: break-all;
                                    color: #3b82f6 !important;
                                    text-decoration: underline;
                                    font-size: 13px;
                                }
                                .warning {
                                    background-color: #fef3c7;
                                    border-left: 4px solid #f59e0b;
                                    padding: 16px;
                                    margin: 30px 0;
                                    border-radius: 4px;
                                }
                                .warning strong {
                                    color: #92400e;
                                    font-size: 14px;
                                }
                                .warning p {
                                    color: #78350f;
                                    font-size: 14px;
                                    margin: 8px 0 0 0;
                                }
                                .footer { 
                                    text-align: center; 
                                    padding: 30px 20px; 
                                    background-color: #f8fafc;
                                    color: #64748b; 
                                    font-size: 12px; 
                                    border-top: 1px solid #e2e8f0;
                                }
                                .footer p {
                                    margin-bottom: 8px;
                                    color: #64748b;
                                }
                                @media only screen and (max-width: 600px) {
                                    .content {
                                        padding: 30px 20px;
                                    }
                                    .header {
                                        padding: 30px 20px;
                                    }
                                    .header h1 {
                                        font-size: 24px;
                                    }
                                    .content h2 {
                                        font-size: 20px;
                                    }
                                }
                            </style>
                        </head>
                        <body>
                            <div class="email-wrapper">
                                <div class="header">
                                    <div class="header-icons">
                                        <svg class="header-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                                        </svg>
                                        <svg class="header-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M17 8h-1V6c0-2.76-2.24-5-5-5S6 3.24 6 6v2H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm4 10.91c0 .55-.45 1-1 1s-1-.45-1-1v-3c0-.55.45-1 1-1s1 .45 1 1v3z"/>
                                        </svg>
                                    </div>
                                    <h1>Golden Z-5 HR System</h1>
                                </div>
                                <div class="content">
                                    <h2>Password Reset Request</h2>
                                    <p>Hello ' . $user_name_safe . ',</p>
                                    <p>We received a request to reset your password for your account. Click the button below to securely reset your password:</p>
                                    <div class="button-container">
                                        <a href="' . $reset_url_safe . '" class="button">
                                            <svg class="button-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M17 8h-1V6c0-2.76-2.24-5-5-5S6 3.24 6 6v2H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm4 10.91c0 .55-.45 1-1 1s-1-.45-1-1v-3c0-.55.45-1 1-1s1 .45 1 1v3z"/>
                                            </svg>
                                            Reset Password
                                        </a>
                                    </div>
                                    <div class="fallback-section">
                                        <p><strong>Or copy and paste this link:</strong></p>
                                        <p><a href="' . $reset_url_safe . '" class="fallback-link">' . $reset_url_safe . '</a></p>
                                    </div>
                                    <div class="warning">
                                        <strong>⚠️ Security Notice:</strong>
                                        <p>This link will expire in 1 hour. If you did not request this password reset, please ignore this email and your password will remain unchanged.</p>
                                    </div>
                                    <p style="margin-top: 30px;">If you have any questions, please contact your system administrator.</p>
                                </div>
                                <div class="footer">
                                    <p>This is an automated message from Golden Z-5 HR Management System.</p>
                                    <p>Please do not reply to this email.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ';
                    
                    // Plain text alternative body (sanitized)
                    $mail->AltBody = "Hello {$user_name_safe},\n\nWe received a request to reset your password. Please click the following link to reset your password:\n\n{$reset_url_safe}\n\nThis link will expire in 1 hour. If you did not request this password reset, please ignore this email.\n\nGolden Z-5 HR System";
                    
                    // Send email and throw exception on failure
                    if (!$mail->send()) {
                        throw new Exception('Failed to send email: ' . $mail->ErrorInfo);
                    }
                    
                    // Log security event
                    if (function_exists('log_security_event')) {
                        log_security_event('Password Reset Requested', "User: {$user['username']} ({$user['email']}) - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
                    }
                    
                    $success = true;
                    $email_sent = true;
                    
                } catch (Exception $e) {
                    error_log('MAIL ERROR: ' . $e->getMessage());
                    echo 'Mailer Error: ' . $e->getMessage(); // TEMPORARY
                }
            } else {
                // Don't reveal if email exists or not (security best practice)
                $success = true;
                $email_sent = true;
            }
        } catch (Exception $e) {
            error_log('Forgot password error: ' . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
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
    <title>Forgot Password</title>
    
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
</head>
<body>
    <div class="login-split-container">
        <!-- Left Branded Panel -->
        <div class="login-branded-panel">
            <div class="branded-content">
                <img src="../public/logo.svg" alt="Golden Z-5 Logo" class="branded-logo" onerror="this.style.display='none'">
                <h1 class="branded-headline">Reset Your Password</h1>
                <p class="branded-description">Enter your email address and we'll send you a link to reset your password.</p>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="login-form-panel">
            <div class="auth-form-container">
                <div class="auth-form-card">
                    <div class="auth-header">
                        <h2 class="auth-title">Forgot Password</h2>
                        <p class="auth-subtitle">Enter your email address to receive a password reset link</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success && $email_sent): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            If an account exists with that email address, we've sent a password reset link. Please check your email inbox and follow the instructions.
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!$email_sent): ?>
                    <form method="POST" action="" id="forgotPasswordForm" class="auth-form">
                        <input type="hidden" name="forgot_password" value="1">
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="Enter your email address" 
                                       required 
                                       autocomplete="email"
                                       autofocus
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="forgot_password" class="btn btn-primary btn-lg" id="submitBtn">
                                <span class="btn-text">Send Reset Link</span>
                                <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
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
        const form = document.getElementById('forgotPasswordForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                
                if (!email) {
                    e.preventDefault();
                    alert('Please enter your email address.');
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
        
        // Auto-redirect to login page after 5 seconds if email was sent successfully
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            let countdown = 5;
            const redirectMessage = document.createElement('div');
            redirectMessage.className = 'text-center mt-3';
            redirectMessage.style.color = '#2563eb';
            redirectMessage.style.fontSize = '14px';
            redirectMessage.innerHTML = `<i class="fas fa-info-circle me-1"></i>Redirecting to login page in <span id="countdown">${countdown}</span> seconds...`;
            successAlert.parentNode.insertBefore(redirectMessage, successAlert.nextSibling);
            
            const countdownElement = document.getElementById('countdown');
            const countdownInterval = setInterval(function() {
                countdown--;
                if (countdownElement) {
                    countdownElement.textContent = countdown;
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
