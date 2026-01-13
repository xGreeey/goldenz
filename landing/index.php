<?php
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

// If already logged in, redirect to appropriate portal
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];
    if ($role === 'hr_admin') {
        header('Location: ../hr-admin/index.php');
        exit;
    }
    if ($role === 'developer') {
        header('Location: ../developer/index.php');
        exit;
    }
}

// Handle login
$error = '';
$debug_info = [];

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
                           failed_login_attempts, locked_until
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
                    
                    // Check role
                    if (!in_array($user['role'], ['hr_admin', 'developer'], true)) {
                        $error = 'This account role is not permitted to sign in. Allowed roles: HR Admin, Developer.';
                        $debug_info[] = "Role not allowed: " . $user['role'];
                    } else {
                        // Update last login
                        $update_sql = "UPDATE users SET last_login = NOW(), last_login_ip = ?, 
                                      failed_login_attempts = 0, locked_until = NULL 
                                      WHERE id = ?";
                        $update_stmt = $pdo->prepare($update_sql);
                        $update_stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['employee_id'] = $user['employee_id'] ?? null;
                        $_SESSION['department'] = $user['department'] ?? null;
                        
                        $debug_info[] = "Session variables set";
                        $debug_info[] = "Redirecting to: " . ($user['role'] === 'developer' ? '../developer/index.php' : '../hr-admin/index.php');
                        
                        // Redirect based on role
                        if ($user['role'] === 'developer') {
                            header('Location: ../developer/index.php');
                            exit;
                        } elseif ($user['role'] === 'hr_admin') {
                            header('Location: ../hr-admin/index.php');
                            exit;
                        }
                    }
                } else {
                    $error = 'Invalid username or password';
                    $debug_info[] = "Password verification failed";
                    
                    // Increment failed login attempts
                    $failed_attempts = ($user['failed_login_attempts'] ?? 0) + 1;
                    $locked_until = null;
                    if ($failed_attempts >= 5) {
                        $locked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
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
        #togglePasswordIcon::after {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            font-style: normal !important;
            font-variant: normal !important;
            text-rendering: auto !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
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
            </div>
        </div>
    </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Simplified JavaScript - minimal interference
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded');
        
        // Toggle Password Visibility
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

