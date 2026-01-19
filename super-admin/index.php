<?php
/**
 * Super Admin Portal - Golden Z-5 HR Management System
 * Main entry point for Super Administrators
 */

// Bootstrap application
require_once __DIR__ . '/../bootstrap/app.php';

// Include legacy functions for backward compatibility
require_once '../includes/security.php';
require_once '../includes/database.php';

// Set security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Handle logout
if (isset($_GET['logout'])) {
    // Clear all session data
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: ../landing/index.php');
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../landing/index.php');
    exit;
}

// Check if user has super_admin role
$user_role = $_SESSION['user_role'] ?? null;
if ($user_role !== 'super_admin') {
    // Invalid role, redirect to login
    session_destroy();
    header('Location: ../landing/index.php');
    exit;
}

/**
 * One-time auto-refresh after login/session start
 *
 * Some pages are loaded dynamically and certain JS bindings may require a fresh
 * request right after login. To avoid manual Ctrl+R, we do a single redirect
 * per session with a cache-busting query param.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $alreadyRefreshed = !empty($_SESSION['super_admin_autorefresh_done']);
    $hasRefreshParam = isset($_GET['_r']);

    if (!$alreadyRefreshed && !$hasRefreshParam) {
        $_SESSION['super_admin_autorefresh_done'] = true;

        // Rebuild current URL + query string, add cache-buster
        $query = $_GET;
        $query['_r'] = time();
        $location = strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($query);

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Location: ' . $location);
        exit;
    }
}

// Handle AJAX requests BEFORE redirect (to avoid HTML output)
// This must come first to handle POST requests properly
$page = $_GET['page'] ?? 'dashboard';

// Handle system log downloads (GET) before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $page === 'system_logs' && isset($_GET['download'])) {
    $logKey = $_GET['log'] ?? 'security';
    $basePath = dirname(__DIR__);
    $logsDir = $basePath . '/storage/logs/';

    $map = [
        'security' => $logsDir . 'security.log',
        'error' => $logsDir . 'error.log',
    ];

    $path = $map[$logKey] ?? $map['security'];
    if (!is_file($path)) {
        header('HTTP/1.1 404 Not Found');
        echo 'Log file not found.';
        exit;
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $logKey . '-' . date('Ymd-His') . '.log"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Ensure session is started and active (bootstrap/app.php should have started it, but double-check)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Regenerate session ID periodically for security (but not on every request to avoid issues)
    // Only regenerate if session is older than 5 minutes
    if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    // If this is a normal form POST (non-AJAX), handle settings change_password with a redirect+flash message
    $action = $_POST['action'] ?? '';
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // Handle profile update (non-AJAX form submission with file upload)
    // Process the update here before header output, then redirect
    if (!$isAjax && $page === 'profile' && $action === 'update_profile') {
        // Process profile update before header output
        $current_user_id = $_SESSION['user_id'] ?? null;
        if ($current_user_id) {
            require_once __DIR__ . '/../includes/database.php';
            $pdo = get_db_connection();
            $current_user = get_user_by_id($current_user_id);
            
            $user_updates = [];
            $user_params = [];
            $employee_updates = [];
            $employee_params = [];
            $update_error = null;
            
            // Handle avatar upload
            if (isset($_FILES['avatar']) && !empty($_FILES['avatar']['tmp_name']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
                error_log('Avatar upload attempt - File info: ' . json_encode($_FILES['avatar']));
                
                if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../uploads/users/';
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0755, true)) {
                            error_log('Failed to create upload directory: ' . $upload_dir);
                            $update_error = 'Failed to create upload directory.';
                        }
                    }
                    
                    if (empty($update_error)) {
                        $file = $_FILES['avatar'];
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        $max_size = 2 * 1024 * 1024; // 2MB
                        
                        // Also check by extension as a fallback
                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if ((in_array($file['type'], $allowed_types) || in_array($extension, $allowed_extensions)) && $file['size'] <= $max_size) {
                            $filename = 'user_' . $current_user_id . '_' . time() . '.' . $extension;
                            $target_path = $upload_dir . $filename;
                            
                            error_log('Attempting to move file to: ' . $target_path);
                            
                            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                                error_log('File moved successfully to: ' . $target_path);
                                
                                // Delete old avatar if exists
                                if (!empty($current_user['avatar'])) {
                                    $old_avatar_path = __DIR__ . '/../' . $current_user['avatar'];
                                    if (file_exists($old_avatar_path)) {
                                        @unlink($old_avatar_path);
                                        error_log('Deleted old avatar: ' . $old_avatar_path);
                                    }
                                }
                                
                                $avatar_path = 'uploads/users/' . $filename;
                                $user_updates[] = "avatar = ?";
                                $user_params[] = $avatar_path;
                                error_log('Avatar path set for database update: ' . $avatar_path);
                            } else {
                                error_log('Failed to move uploaded file from ' . $file['tmp_name'] . ' to ' . $target_path);
                                $update_error = 'Failed to move uploaded file. Please check file permissions.';
                            }
                        } else {
                            if (!in_array($file['type'], $allowed_types) && !in_array($extension, $allowed_extensions)) {
                                error_log('Invalid file type: ' . $file['type'] . ', extension: ' . $extension);
                                $update_error = 'Invalid file type. Please upload a JPG, PNG, or GIF image.';
                            } elseif ($file['size'] > $max_size) {
                                error_log('File too large: ' . $file['size'] . ' bytes');
                                $update_error = 'File size too large. Maximum size is 2MB.';
                            }
                        }
                    }
                } else {
                    $upload_errors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
                    ];
                    $error_code = $_FILES['avatar']['error'];
                    $update_error = $upload_errors[$error_code] ?? 'Unknown upload error (code: ' . $error_code . ')';
                    error_log('Avatar upload error: ' . $update_error);
                }
            }
            
            // Email update (optional but must be valid if provided)
            if (isset($_POST['email'])) {
                $email = trim($_POST['email']);
                if (!empty($email)) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $check_stmt->execute([$email, $current_user_id]);
                        if ($check_stmt->rowCount() === 0) {
                            $user_updates[] = "email = ?";
                            $user_params[] = $email;
                        } else {
                            $update_error = 'Email address is already in use by another account.';
                        }
                    } else {
                        $update_error = 'Invalid email address format.';
                    }
                }
                // If email is empty, we don't update it (leave existing value)
            }
            
            // First name and last name update (users table)
            // Check if columns exist before updating
            try {
                $check_first_name = $pdo->query("SHOW COLUMNS FROM users LIKE 'first_name'");
                $check_last_name = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_name'");
                $has_first_name = $check_first_name->rowCount() > 0;
                $has_last_name = $check_last_name->rowCount() > 0;
                
                if ($has_first_name && isset($_POST['first_name'])) {
                    $user_updates[] = "first_name = ?";
                    $user_params[] = trim($_POST['first_name']);
                }
                
                if ($has_last_name && isset($_POST['last_name'])) {
                    $user_updates[] = "last_name = ?";
                    $user_params[] = trim($_POST['last_name']);
                }
            } catch (Exception $e) {
                error_log('Error checking first_name/last_name columns: ' . $e->getMessage());
            }
            
            // Department update
            if (isset($_POST['department']) && !empty(trim($_POST['department']))) {
                $user_updates[] = "department = ?";
                $user_params[] = trim($_POST['department']);
            }
            
            // Contact number update
            if (isset($_POST['contact_number']) && !empty(trim($_POST['contact_number']))) {
                try {
                    $check_col = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
                    if ($check_col->rowCount() > 0) {
                        $user_updates[] = "phone = ?";
                        $user_params[] = trim($_POST['contact_number']);
                    } elseif (!empty($current_user['employee_id'])) {
                        $employee_updates[] = "cp_number = ?";
                        $employee_params[] = trim($_POST['contact_number']);
                    }
                } catch (Exception $e) {
                    if (!empty($current_user['employee_id'])) {
                        $employee_updates[] = "cp_number = ?";
                        $employee_params[] = trim($_POST['contact_number']);
                    }
                }
            }
            
            // Employee-specific fields (only position and date_hired, not name)
            if (!empty($current_user['employee_id'])) {
                if (isset($_POST['position']) && !empty(trim($_POST['position']))) {
                    $employee_updates[] = "post = ?";
                    $employee_params[] = trim($_POST['position']);
                }
                
                if (isset($_POST['date_hired']) && !empty(trim($_POST['date_hired']))) {
                    $employee_updates[] = "date_hired = ?";
                    $employee_params[] = trim($_POST['date_hired']);
                }
            }
            
            // Update users table
            if (!empty($user_updates) && empty($update_error)) {
                try {
                    $user_params[] = $current_user_id;
                    $user_sql = "UPDATE users SET " . implode(", ", $user_updates) . ", updated_at = NOW() WHERE id = ?";
                    
                    error_log('Profile update SQL: ' . $user_sql);
                    error_log('Profile update params: ' . json_encode($user_params));
                    
                    $user_stmt = $pdo->prepare($user_sql);
                    $result = $user_stmt->execute($user_params);
                    $rows_affected = $user_stmt->rowCount();
                    
                    error_log('Profile update result: ' . ($result ? 'success' : 'failed') . ', rows affected: ' . $rows_affected);
                    
                    if (!$result) {
                        $error_info = $user_stmt->errorInfo();
                        error_log('Profile update error info: ' . json_encode($error_info));
                        $update_error = 'Failed to update profile in database.';
                    }
                    
                    // Update session
                    if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                        $_SESSION['email'] = trim($_POST['email']);
                    }
                    
                    // Update session name from first_name and last_name
                    $first_name = trim($_POST['first_name'] ?? '');
                    $last_name = trim($_POST['last_name'] ?? '');
                    if (!empty($first_name) || !empty($last_name)) {
                        $_SESSION['name'] = trim($first_name . ' ' . $last_name);
                    } elseif (!empty($first_name)) {
                        $_SESSION['name'] = $first_name;
                    } elseif (!empty($last_name)) {
                        $_SESSION['name'] = $last_name;
                    }
                } catch (Exception $e) {
                    error_log('Profile update exception: ' . $e->getMessage());
                    $update_error = 'An error occurred while updating your profile.';
                }
            } else {
                if (empty($user_updates)) {
                    error_log('Profile update: No updates to apply');
                }
                if (!empty($update_error)) {
                    error_log('Profile update skipped due to error: ' . $update_error);
                }
            }
            
            // Update employees table
            if (!empty($current_user['employee_id']) && !empty($employee_updates) && empty($update_error)) {
                try {
                    $employee_params[] = $current_user['employee_id'];
                    $employee_sql = "UPDATE employees SET " . implode(", ", $employee_updates) . ", updated_at = NOW() WHERE id = ?";
                    $employee_stmt = $pdo->prepare($employee_sql);
                    $employee_stmt->execute($employee_params);
                } catch (Exception $e) {
                    error_log('Employee update error: ' . $e->getMessage());
                }
            }
            
            // Redirect after update (before header output)
            if (empty($update_error)) {
                header('Location: ?page=profile&updated=1');
                exit;
            } else {
                $_SESSION['profile_update_error'] = $update_error;
                header('Location: ?page=profile');
                exit;
            }
        }
    } elseif (!$isAjax && $page === 'settings' && $action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            redirect_with_message('?page=settings', 'All password fields are required', 'error');
        }
        if (strlen($new_password) < 8) {
            redirect_with_message('?page=settings', 'New password must be at least 8 characters long', 'error');
        }
        if ($new_password !== $confirm_password) {
            redirect_with_message('?page=settings', 'New password and confirmation do not match', 'error');
        }
        if ($new_password === $current_password) {
            redirect_with_message('?page=settings', 'New password must be different from current password', 'error');
        }

        try {
            $pdo = get_db_connection();
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                redirect_with_message('?page=settings', 'User not authenticated', 'error');
            }

            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                redirect_with_message('?page=settings', 'User not found', 'error');
            }
            if (!password_verify($current_password, $user['password_hash'])) {
                redirect_with_message('?page=settings', 'Current password is incorrect', 'error');
            }

            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_changed_at = NOW(), updated_at = NOW() WHERE id = ?");
            $result = $update_stmt->execute([$new_password_hash, $user_id]);

            if ($result && $update_stmt->rowCount() > 0) {
                if (function_exists('log_security_event')) {
                    log_security_event('INFO Password Changed', "User ID: $user_id - Username: " . ($_SESSION['username'] ?? 'Unknown') . " - Password changed via settings");
                }
                redirect_with_message('?page=settings', 'Password changed successfully', 'success');
            }

            redirect_with_message('?page=settings', 'Failed to update password. No rows were updated.', 'error');
        } catch (Exception $e) {
            error_log('Password change error: ' . $e->getMessage());
            redirect_with_message('?page=settings', 'An error occurred while changing password', 'error');
        }
    }

    // Handle password policy update (non-AJAX, from settings page)
    if (!$isAjax && $page === 'settings' && $action === 'update_password_policy') {
        $min_length = isset($_POST['password_min_length']) ? (int)$_POST['password_min_length'] : 8;
        $expiry_days = isset($_POST['password_expiry_days']) ? (int)$_POST['password_expiry_days'] : 90;
        $require_special = isset($_POST['password_require_special']) && $_POST['password_require_special'] === '1';

        // Basic validation
        if ($min_length < 4) {
            redirect_with_message('?page=settings', 'Minimum length must be at least 4 characters', 'error');
        }
        if ($expiry_days < 0) {
            redirect_with_message('?page=settings', 'Password expiry days cannot be negative', 'error');
        }

        if (!function_exists('update_password_policy')) {
            require_once __DIR__ . '/../includes/database.php';
        }

        $result = update_password_policy($min_length, $require_special, $expiry_days);
        if ($result) {
            redirect_with_message('?page=settings', 'Password policy updated successfully', 'success');
        } else {
            redirect_with_message('?page=settings', 'Failed to update password policy', 'error');
        }
    }

    // Start 2FA setup: generate a temporary secret and store in session (but do not enable yet)
    if (!$isAjax && $page === 'settings' && $action === 'start_2fa_setup') {
        if (!function_exists('generate_two_factor_secret')) {
            require_once __DIR__ . '/../includes/security.php';
        }
        $_SESSION['pending_2fa_secret'] = generate_two_factor_secret(16);
        redirect_with_message('?page=settings', 'Scan the QR code with Google Authenticator and enter the code to enable 2FA.', 'info');
    }

    // Confirm enabling 2FA for the current user
    if (!$isAjax && $page === 'settings' && $action === 'confirm_enable_2fa') {
        $user_id = $_SESSION['user_id'] ?? null;
        $pendingSecret = $_SESSION['pending_2fa_secret'] ?? '';
        $code = trim($_POST['two_factor_code'] ?? '');

        if (!$user_id || $pendingSecret === '') {
            redirect_with_message('?page=settings', '2FA setup session has expired. Please start again.', 'error');
        }

        if (!function_exists('verify_totp_code')) {
            require_once __DIR__ . '/../includes/security.php';
        }

        if (!verify_totp_code($pendingSecret, $code)) {
            redirect_with_message('?page=settings&setup_2fa=1', 'Invalid 2FA code. Please try again.', 'error');
        }

        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$pendingSecret, $user_id]);
            unset($_SESSION['pending_2fa_secret']);
            redirect_with_message('?page=settings', 'Two-factor authentication has been enabled for your account.', 'success');
        } catch (Exception $e) {
            error_log('Enable 2FA error: ' . $e->getMessage());
            redirect_with_message('?page=settings', 'Failed to enable two-factor authentication.', 'error');
        }
    }

    // Disable 2FA for the current user
    if (!$isAjax && $page === 'settings' && $action === 'disable_2fa') {
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            redirect_with_message('?page=settings', 'User not authenticated.', 'error');
        }

        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$user_id]);
            unset($_SESSION['pending_2fa_secret']);
            redirect_with_message('?page=settings', 'Two-factor authentication has been disabled for your account.', 'success');
        } catch (Exception $e) {
            error_log('Disable 2FA error: ' . $e->getMessage());
            redirect_with_message('?page=settings', 'Failed to disable two-factor authentication.', 'error');
        }
    }
    
    // Skip JSON response for profile updates (non-AJAX) - let the page handle it
    if (!$isAjax && $page === 'profile' && $action === 'update_profile') {
        // Don't set JSON headers or exit - let the page process normally
    } else {
        // Prevent any output before JSON response
        // Clean output buffer if it exists, otherwise start one
        if (ob_get_level() > 0) {
            ob_clean();
        } else {
            ob_start();
        }
        
        // Set comprehensive cache control headers to prevent browser caching
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    }
    
    $action = $_POST['action'] ?? '';
    $current_user_id = $_SESSION['user_id'] ?? null;
    
    // Handle user management AJAX
    if ($page === 'users') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        switch ($action) {
            case 'update_role':
                $new_role = $_POST['role'] ?? '';
                $result = update_user_role($user_id, $new_role, $current_user_id);
                echo json_encode($result);
                exit;
                
            case 'update_status':
                $new_status = $_POST['status'] ?? '';
                $result = update_user_status($user_id, $new_status, $current_user_id);
                echo json_encode($result);
                exit;

            case 'delete_user':
                $result = delete_user($user_id, $current_user_id);
                echo json_encode($result);
                exit;
                
            case 'create_user':
                try {
                    // Validate that this is an AJAX request
                    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
                        // Still allow it, but log it
                        error_log('Create user request without X-Requested-With header');
                    }
                    
                    $user_data = [
                        'username' => trim($_POST['username'] ?? ''),
                        'email' => trim($_POST['email'] ?? ''),
                        'first_name' => trim($_POST['first_name'] ?? ''),
                        'last_name' => trim($_POST['last_name'] ?? ''),
                        'role' => $_POST['role'] ?? 'hr_admin',
                        'status' => $_POST['status'] ?? 'active',
                        'department' => !empty(trim($_POST['department'] ?? '')) ? trim($_POST['department']) : null,
                        'phone' => !empty(trim($_POST['phone'] ?? '')) ? trim($_POST['phone']) : null,
                        'employee_id' => !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null
                    ];
                    
                    // Validate required fields before processing (password is auto-generated)
                    if (empty($user_data['username']) || empty($user_data['email']) || empty($user_data['first_name']) || empty($user_data['last_name'])) {
                        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                        exit;
                    }
                    
                    // Log the attempt
                    error_log('Create user attempt: ' . json_encode($user_data));
                    
                    $result = create_user($user_data, $current_user_id);
                    
                    // Log the result
                    error_log('Create user result: ' . json_encode($result));
                    
                    // Ensure result is always an array
                    if (!is_array($result)) {
                        $result = ['success' => false, 'message' => 'Unexpected response from create_user function'];
                    }
                    
                    // Session will be automatically saved when script ends
                    // No need to explicitly close it here
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    exit;
                } catch (Exception $e) {
                    error_log('Create user exception: ' . $e->getMessage());
                    error_log('Create user exception trace: ' . $e->getTraceAsString());
                    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
                    exit;
                } catch (Error $e) {
                    error_log('Create user fatal error: ' . $e->getMessage());
                    error_log('Create user fatal error trace: ' . $e->getTraceAsString());
                    echo json_encode(['success' => false, 'message' => 'A fatal error occurred: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
                    exit;
                }
        }
    }

    // Handle system logs AJAX
    if ($page === 'system_logs') {
        $action = $_POST['action'] ?? '';
        $logKey = $_POST['log'] ?? 'security';
        $search = trim($_POST['search'] ?? '');
        $level = strtoupper(trim($_POST['level'] ?? ''));
        if ($level === 'ALL') $level = '';
        $p = max(1, (int)($_POST['p'] ?? 1));
        $perPage = max(10, min(500, (int)($_POST['per_page'] ?? 100)));

        $basePath = dirname(__DIR__);
        $logsDir = $basePath . '/storage/logs/';
        $map = [
            'security' => $logsDir . 'security.log',
            'error' => $logsDir . 'error.log',
        ];
        $path = $map[$logKey] ?? $map['security'];

        switch ($action) {
            case 'clear_log':
                if (!is_file($path)) {
                    echo json_encode(['success' => true, 'message' => 'Log does not exist yet']);
                    exit;
                }
                $ok = @file_put_contents($path, '');
                echo json_encode(['success' => $ok !== false, 'message' => $ok !== false ? 'Log cleared' : 'Failed to clear log']);
                exit;

            case 'fetch_log':
                // Returns latest filtered lines (newest-first) for live refresh
                $lines = [];
                if (is_file($path)) {
                    $raw = @file($path, FILE_IGNORE_NEW_LINES);
                    if (is_array($raw)) {
                        $lines = array_reverse($raw);
                    }
                }

                $filtered = [];
                foreach ($lines as $ln) {
                    if ($search !== '' && stripos($ln, $search) === false) continue;
                    if ($level !== '' && stripos($ln, $level) === false) continue;
                    $filtered[] = $ln;
                }

                $total = count($filtered);
                $totalPages = max(1, (int)ceil($total / $perPage));
                $p = min($p, $totalPages);
                $offset = ($p - 1) * $perPage;
                $pageLines = array_slice($filtered, $offset, $perPage);

                // Render minimal HTML for the log view (same classes as system_logs.php)
                $html = '';
                foreach ($pageLines as $ln) {
                    $cls = 'log-line';
                    $u = strtoupper($ln);
                    if (strpos($u, 'ERROR') !== false) $cls .= ' is-error';
                    elseif (strpos($u, 'WARN') !== false) $cls .= ' is-warn';
                    elseif (strpos($u, 'INFO') !== false) $cls .= ' is-info';
                    $html .= '<div class="' . $cls . '">' . htmlspecialchars($ln, ENT_QUOTES, 'UTF-8') . '</div>';
                }

                echo json_encode([
                    'success' => true,
                    'html' => $html,
                    'total' => $total,
                    'shown' => count($pageLines),
                    'page' => $p,
                    'total_pages' => $totalPages,
                    'file_exists' => is_file($path),
                    'file_mtime' => is_file($path) ? filemtime($path) : null,
                ]);
                exit;
        }
    }
    
    // Handle settings AJAX
    if ($page === 'settings') {
        switch ($action) {
            case 'change_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                // Validate inputs
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    echo json_encode(['success' => false, 'message' => 'All password fields are required']);
                    exit;
                }
                
                if (strlen($new_password) < 8) {
                    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long']);
                    exit;
                }
                
                if ($new_password !== $confirm_password) {
                    echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match']);
                    exit;
                }
                
                if ($new_password === $current_password) {
                    echo json_encode(['success' => false, 'message' => 'New password must be different from current password']);
                    exit;
                }
                
                // Verify current password and update
                try {
                    $pdo = get_db_connection();
                    $user_id = $_SESSION['user_id'] ?? null;
                    
                    if (!$user_id) {
                        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                        exit;
                    }
                    
                    // Get current password hash
                    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$user) {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        exit;
                    }
                    
                    // Verify current password
                    if (!password_verify($current_password, $user['password_hash'])) {
                        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                        exit;
                    }
                    
                    // Hash new password
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password in users table
                    // Updates: password_hash, password_changed_at, and updated_at
                    $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_changed_at = NOW(), updated_at = NOW() WHERE id = ?");
                    $result = $update_stmt->execute([$new_password_hash, $user_id]);
                    
                    if ($result && $update_stmt->rowCount() > 0) {
                        // Log security event
                        if (function_exists('log_security_event')) {
                            log_security_event('INFO Password Changed', "User ID: $user_id - Username: " . ($_SESSION['username'] ?? 'Unknown') . " - Password changed via settings");
                        }
                        
                        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                    } else {
                        error_log("Password update failed: No rows affected for user ID: $user_id");
                        echo json_encode(['success' => false, 'message' => 'Failed to update password. No rows were updated.']);
                    }
                } catch (Exception $e) {
                    error_log('Password change error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred while changing password']);
                }
                exit;

            case 'update_backup_settings':
                if (!function_exists('update_backup_settings')) {
                    require_once __DIR__ . '/../includes/database.php';
                }
                
                $frequency = $_POST['backup_frequency'] ?? 'daily';
                $retention_days = isset($_POST['backup_retention_days']) ? (int)$_POST['backup_retention_days'] : 90;
                $backup_location = trim($_POST['backup_location'] ?? 'storage/backups');
                
                // Validate retention days
                if ($retention_days < 0) {
                    echo json_encode(['success' => false, 'message' => 'Retention period cannot be negative']);
                    exit;
                }
                
                // Convert retention days: 0 = forever, otherwise use the value
                if ($_POST['backup_retention_days'] == '0') {
                    $retention_days = 0; // Forever
                }
                
                $result = update_backup_settings($frequency, $retention_days, $backup_location);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Backup settings updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update backup settings']);
                }
                exit;

            case 'create_backup':
                if (!function_exists('create_database_backup')) {
                    require_once __DIR__ . '/../includes/database.php';
                }
                
                $result = create_database_backup();
                echo json_encode($result);
                exit;

            case 'get_backup_list':
                if (!function_exists('get_backup_list')) {
                    require_once __DIR__ . '/../includes/database.php';
                }
                
                $backups = get_backup_list();
                echo json_encode(['success' => true, 'backups' => $backups]);
                exit;
        }
    }
    
    // Handle help/tickets AJAX
    if ($page === 'help') {
        $ticket_id = (int)($_POST['ticket_id'] ?? 0);
        
        switch ($action) {
            case 'reply':
                if (!empty($_POST['message'])) {
                    $result = add_ticket_reply($ticket_id, [
                        'user_id' => $current_user_id,
                        'user_name' => $_SESSION['user_name'] ?? 'Super Admin',
                        'user_role' => 'super_admin',
                        'message' => trim($_POST['message']),
                        'is_internal' => isset($_POST['is_internal']) ? 1 : 0
                    ]);
                    echo json_encode($result);
                    exit;
                }
                break;
                
            case 'update_status':
                if (!empty($_POST['status'])) {
                    $result = update_ticket_status($ticket_id, $_POST['status'], [
                        'id' => $current_user_id,
                        'name' => $_SESSION['user_name'] ?? 'Super Admin',
                        'role' => 'super_admin'
                    ]);
                    echo json_encode($result);
                    exit;
                }
                break;
        }
    }
    
    // Default: invalid action (but skip for profile page non-AJAX requests)
    if (!(!$isAjax && $page === 'profile' && $action === 'update_profile')) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    // For profile page, continue to include the page normally
}

// Redirect to dashboard if no page parameter is set (only for GET requests)
if (!isset($_GET['page']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: ?page=dashboard');
    exit;
}

// Include the header which handles routing
include '../includes/headers/super-admin-header.php';
?>
