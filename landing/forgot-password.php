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
                
                // Generate reset URL
                $reset_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
                    . '://' . $_SERVER['HTTP_HOST'] 
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
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background-color: #0f172a; color: #fff; padding: 20px; text-align: center; }
                                .content { background-color: #f8fafc; padding: 30px; border: 1px solid #e2e8f0; }
                                .button { display: inline-block; padding: 12px 30px; background-color: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                                .button:hover { background-color: #1e40af; }
                                .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
                                .warning { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <div class="header">
                                    <h1>Golden Z-5 HR System</h1>
                                </div>
                                <div class="content">
                                    <h2>Password Reset Request</h2>
                                    <p>Hello ' . $user_name_safe . ',</p>
                                    <p>We received a request to reset your password for your account. Click the button below to reset your password:</p>
                                    <p style="text-align: center;">
                                        <a href="' . $reset_url_safe . '" class="button">Reset Password</a>
                                    </p>
                                    <p>Or copy and paste this link into your browser:</p>
                                    <p style="word-break: break-all; color: #2563eb;">' . $reset_url_safe . '</p>
                                    <div class="warning">
                                        <strong>⚠️ Security Notice:</strong> This link will expire in 1 hour. If you did not request this password reset, please ignore this email and your password will remain unchanged.
                                    </div>
                                    <p>If you have any questions, please contact your system administrator.</p>
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
    <title>Forgot Password - Golden Z-5 HR Management System</title>
    
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
                    if (btnText) btnText.textContent = 'Sending...';
                    if (spinner) spinner.classList.remove('d-none');
                }
            });
        }
    });
    </script>
</body>
</html>
