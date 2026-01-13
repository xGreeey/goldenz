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

// Redirect to dashboard if no page parameter is set
if (!isset($_GET['page'])) {
    header('Location: ?page=dashboard');
    exit;
}

// Handle AJAX requests BEFORE including header (to avoid HTML output)
$page = $_GET['page'] ?? 'dashboard';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Set JSON header immediately
    header('Content-Type: application/json');
    
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
                $result = create_user($user_data, $current_user_id);
                echo json_encode($result);
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

// Include the header which handles routing
include '../includes/headers/super-admin-header.php';
?>
