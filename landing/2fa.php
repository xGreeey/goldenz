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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow-lg border-0" style="max-width: 420px; width: 100%;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1">Two-Factor Authentication</h5>
                    <p class="text-muted mb-0">
                        Enter the 6‑digit code from your <strong>Google Authenticator</strong> app to continue.
                    </p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label for="two_factor_code" class="form-label">Authentication Code</label>
                        <input type="text"
                               id="two_factor_code"
                               name="two_factor_code"
                               class="form-control text-center fw-semibold fs-5"
                               inputmode="numeric"
                               autocomplete="one-time-code"
                               pattern="[0-9]{6}"
                               maxlength="6"
                               required
                               placeholder="••••••"
                               oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,6);">
                        <small class="text-muted d-block mt-1">Codes refresh every 30 seconds.</small>
                    </div>

                    <button type="submit" name="verify_2fa" class="btn btn-primary w-100 mt-2">
                        <i class="fas fa-unlock-alt me-2"></i>Verify &amp; Continue
                    </button>

                    <div class="mt-3 text-center">
                        <a href="index.php?logout=1" class="small text-muted">
                            Use a different account
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

