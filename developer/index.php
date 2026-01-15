<?php
/**
 * Developer Portal - Golden Z-5 HR Management System
 * Main entry point for developers
 */

// Bootstrap application
require_once __DIR__ . '/../bootstrap/app.php';

// Include legacy functions for backward compatibility
require_once '../includes/security.php';
require_once '../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../landing/index.php');
    exit;
}

// Check if user has developer role
$user_role = $_SESSION['user_role'] ?? null;
if ($user_role !== 'developer') {
    // Invalid role, redirect to login
    session_destroy();
    header('Location: ../landing/index.php');
    exit;
}

// Handle POST requests (AJAX and form submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // Handle password change (AJAX)
    if ($action === 'change_password' && $isAjax) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
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
        
        // Validate password requirements
        $passwordRequirements = [
            'length' => strlen($new_password) >= 8,
            'lowercase' => preg_match('/[a-z]/', $new_password),
            'uppercase' => preg_match('/[A-Z]/', $new_password),
            'number' => preg_match('/[0-9]/', $new_password),
            'symbol' => preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password)
        ];
        
        $missingRequirements = [];
        if (!$passwordRequirements['length']) $missingRequirements[] = 'Minimum 8 characters';
        if (!$passwordRequirements['lowercase']) $missingRequirements[] = 'Lowercase letter';
        if (!$passwordRequirements['uppercase']) $missingRequirements[] = 'Uppercase letter';
        if (!$passwordRequirements['number']) $missingRequirements[] = 'Number';
        if (!$passwordRequirements['symbol']) $missingRequirements[] = 'Symbol';
        
        if (count($missingRequirements) > 0) {
            echo json_encode(['success' => false, 'message' => 'Password must contain: ' . implode(', ', $missingRequirements)]);
            exit;
        }
        
        try {
            $pdo = get_db_connection();
            $user_id = $_SESSION['user_id'] ?? null;
            
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            if (!password_verify($current_password, $user['password_hash'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_changed_at = NOW(), updated_at = NOW() WHERE id = ?");
            $result = $update_stmt->execute([$new_password_hash, $user_id]);
            
            if ($result && $update_stmt->rowCount() > 0) {
                if (function_exists('log_security_event')) {
                    log_security_event('INFO Password Changed', "User ID: $user_id - Username: " . ($_SESSION['username'] ?? 'Unknown') . " - Password changed via settings");
                }
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update password']);
            }
        } catch (Exception $e) {
            error_log('Password change error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while changing password']);
        }
        exit;
    }
    
    // Other POST handlers can be added here
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Redirect to dashboard if no page parameter is set
if (!isset($_GET['page'])) {
    header('Location: ?page=dashboard');
    exit;
}

// Include the header which handles routing
include '../includes/header.php';
?>
