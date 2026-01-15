<?php
/**
 * Two-Factor Authentication Verification
 * Step shown after password login for Super Admin / Admin with 2FA enabled
 */

ob_start();

// Start session (reuse same session path as login page if possible)
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../storage/sessions';
    if (is_dir($sessionPath) || @mkdir($sessionPath, 0755, true)) {
        session_save_path($sessionPath);
    }
    session_start();
}

// If there is no pending 2FA user, go back to login
if (empty($_SESSION['pending_2fa_user_id'])) {
    header('Location: index.php');
    exit;
}

// Load dependencies
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/security.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_2fa'])) {
    $code = $_POST['two_factor_code'] ?? '';
    // Keep only digits and limit to 6
    $code = preg_replace('/\D+/', '', $code);
    $code = substr($code, 0, 6);

    $user_id = (int)$_SESSION['pending_2fa_user_id'];

    try {
        $pdo = get_db_connection();
        $sql = "SELECT id, username, name, role, status, employee_id, department,
                       two_factor_enabled, two_factor_secret
                FROM users
                WHERE id = ? AND status = 'active'
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['two_factor_enabled']) || empty($user['two_factor_secret'])) {
            // 2FA no longer enabled or user missing – reset and send to login
            unset(
                $_SESSION['pending_2fa_user_id'],
                $_SESSION['pending_2fa_username'],
                $_SESSION['pending_2fa_name'],
                $_SESSION['pending_2fa_role'],
                $_SESSION['pending_2fa_employee_id'],
                $_SESSION['pending_2fa_department']
            );
            header('Location: index.php');
            exit;
        }

        if (!verify_totp_code($user['two_factor_secret'], $code)) {
            $error = 'Invalid 2FA code. Please try again.';
        } else {
            // 2FA successful – complete login
            $update_sql = "UPDATE users SET last_login = NOW(), last_login_ip = ?, 
                                          failed_login_attempts = 0, locked_until = NULL 
                           WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);

            if (function_exists('log_security_event')) {
                log_security_event('2FA Success', "User: {$user['username']} ({$user['name']}) - Role: {$user['role']} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
            }

            // Set main session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['employee_id'] = $user['employee_id'] ?? null;
            $_SESSION['department'] = $user['department'] ?? null;

            // Clear pending 2FA data
            unset(
                $_SESSION['pending_2fa_user_id'],
                $_SESSION['pending_2fa_username'],
                $_SESSION['pending_2fa_name'],
                $_SESSION['pending_2fa_role'],
                $_SESSION['pending_2fa_employee_id'],
                $_SESSION['pending_2fa_department']
            );

            // Redirect based on role
            if ($user['role'] === 'super_admin') {
                header('Location: ../super-admin/index.php');
                exit;
            } elseif ($user['role'] === 'developer') {
                header('Location: ../developer/index.php');
                exit;
            } else {
                header('Location: ../hr-admin/index.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $error = 'Verification failed. Please try again.';
        error_log('2FA verify error: ' . $e->getMessage());
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <title>Two-Factor Authentication - Golden Z-5 HR</title>

    <!-- Favicon (match login page) -->
    <link rel="icon" type="image/svg+xml" href="../public/logo.svg">
    <link rel="icon" type="image/x-icon" href="../public/favicon.ico">
    <link rel="apple-touch-icon" href="../public/logo.svg">

    <!-- CSS (match login page stack) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/landing.css" rel="stylesheet">
    <link href="../assets/css/font-override.css" rel="stylesheet">

    <!-- Security headers equivalents -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">

<style>
        /* Slight tweaks for 2FA card within shared layout */
        .auth-form-card-2fa .auth-title {
            margin-bottom: 0.25rem;
        }
        .auth-form-card-2fa .auth-subtitle {
            font-size: 0.925rem;
        }
        .twofa-code-input {
            letter-spacing: 0.35em;
        }

        /* Shake animation when code is invalid */
        @keyframes shake-card {
            0% { transform: translateX(0); }
            15% { transform: translateX(-6px); }
            30% { transform: translateX(6px); }
            45% { transform: translateX(-5px); }
            60% { transform: translateX(5px); }
            75% { transform: translateX(-3px); }
            90% { transform: translateX(3px); }
            100% { transform: translateX(0); }
        }
        .auth-form-card-2fa.shake {
            animation: shake-card 0.4s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="login-split-container">
        <!-- Left Branded Panel (same as login) -->
        <div class="login-branded-panel">
            <div class="branded-content">
                <img src="../public/logo.svg" alt="Golden Z-5 Logo" class="branded-logo" onerror="this.style.display='none'">
                <h1 class="branded-headline">Secure Sign-in</h1>
                <p class="branded-description">Verify your identity with a one-time code to keep your account protected.</p>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="login-form-panel">
            <div class="auth-form-container">
                <div class="auth-form-card auth-form-card-2fa<?php echo !empty($error) ? ' shake' : ''; ?>">
                    <div class="auth-header text-start text-md-start text-center">
                        <h2 class="auth-title">Two-Factor Authentication</h2>
                        <p class="auth-subtitle">
                            Enter the 6‑digit code from your <strong>Google Authenticator</strong> app to continue.
                        </p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off" class="auth-form mt-3" id="twofaForm">
                        <div class="form-group mb-3">
                            <label for="two_factor_code" class="form-label">Authentication Code</label>
                            <input type="text"
                                   id="two_factor_code"
                                   name="two_factor_code"
                                   class="form-control text-center fw-semibold fs-5 twofa-code-input"
                                   inputmode="numeric"
                                   autocomplete="one-time-code"
                                   pattern="[0-9]{6}"
                                   maxlength="6"
                                   required
                                   placeholder="••••••"
                                   oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,6);">
                            <small class="text-muted d-block mt-1">Codes refresh every 30 seconds.</small>
                        </div>

                        <div class="text-center text-muted small mt-3">
                            <a href="index.php?logout=1" class="text-decoration-none" style="color: #2563eb; font-weight: 500;">
                                Use a different account
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('two_factor_code');
            const form = document.getElementById('twofaForm');

            if (codeInput && form) {
                codeInput.focus();

                codeInput.addEventListener('input', function () {
                    const value = codeInput.value.replace(/[^0-9]/g, '').slice(0, 6);
                    codeInput.value = value;

                    if (value.length === 6) {
                        // Automatically submit when 6 digits are entered
                        // Add a tiny delay so the last digit visibly renders
                        setTimeout(function () {
                            // Ensure the expected POST field is set
                            if (!form.querySelector('input[name=\"verify_2fa\"]')) {
                                const hidden = document.createElement('input');
                                hidden.type = 'hidden';
                                hidden.name = 'verify_2fa';
                                hidden.value = '1';
                                form.appendChild(hidden);
                            }
                            form.submit();
                        }, 80);
                    }
                });
            }
        });
    </script>
</body>
</html>

