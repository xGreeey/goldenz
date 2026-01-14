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

// Handle AJAX requests BEFORE redirect (to avoid HTML output)
// This must come first to handle POST requests properly
$page = $_GET['page'] ?? 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Prevent any output before JSON response
    ob_clean();
    
    // Set JSON header immediately
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
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
                        'password' => $_POST['password'] ?? '',
                        'name' => trim($_POST['name'] ?? ''),
                        'role' => $_POST['role'] ?? 'hr_admin',
                        'status' => $_POST['status'] ?? 'active',
                        'department' => trim($_POST['department'] ?? ''),
                        'phone' => trim($_POST['phone'] ?? ''),
                        'employee_id' => !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null
                    ];
                    
                    // Validate required fields before processing
                    if (empty($user_data['username']) || empty($user_data['email']) || empty($user_data['password']) || empty($user_data['name'])) {
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
                    
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                } catch (Exception $e) {
                    error_log('Create user exception: ' . $e->getMessage());
                    error_log('Create user exception trace: ' . $e->getTraceAsString());
                    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
                } catch (Error $e) {
                    error_log('Create user fatal error: ' . $e->getMessage());
                    error_log('Create user fatal error trace: ' . $e->getTraceAsString());
                    echo json_encode(['success' => false, 'message' => 'A fatal error occurred: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
                }
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
                            log_security_event('Password Changed', "User ID: $user_id - Username: " . ($_SESSION['username'] ?? 'Unknown') . " - Password changed via settings");
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
    
    // Default: invalid action
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Redirect to dashboard if no page parameter is set (only for GET requests)
if (!isset($_GET['page']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: ?page=dashboard');
    exit;
}

// Include the header which handles routing
include '../includes/headers/super-admin-header.php';
?>
