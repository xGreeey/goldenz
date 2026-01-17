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
    
    // Verify CSRF token for all actions
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }
    
    // Handle developer dashboard tool actions
    try {
        switch ($action) {
            case 'clear-sessions':
                // Clear all session files
                $session_path = storage_path('sessions');
                $cleared = 0;
                
                if (is_dir($session_path)) {
                    $files = glob($session_path . '/sess_*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                            $cleared++;
                        }
                    }
                }
                
                // Log to system logs
                if (function_exists('log_system_event')) {
                    log_system_event('info', "Cleared $cleared session files", 'sessions', ['cleared_count' => $cleared]);
                }
                
                if (function_exists('log_security_event')) {
                    log_security_event('INFO Sessions Cleared', "Developer cleared $cleared session files");
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => "Successfully cleared $cleared session files."
                ]);
                exit;
                
            case 'test-email':
                // Test email functionality (placeholder)
                // TODO: Implement actual email test using PHPMailer
                if (function_exists('log_system_event')) {
                    log_system_event('info', 'Test email triggered', 'email', ['status' => 'not_implemented']);
                }
                
                if (function_exists('log_security_event')) {
                    log_security_event('INFO Test Email', 'Developer triggered test email');
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Test email functionality is not yet implemented. Check email configuration.'
                ]);
                exit;
                
            case 'run-diagnostics':
                // Run system diagnostics
                $diagnostics = [];
                
                // Check PHP version
                $diagnostics[] = [
                    'check' => 'PHP Version',
                    'status' => 'ok',
                    'message' => 'PHP ' . PHP_VERSION
                ];
                
                // Check database connection
                try {
                    $pdo = get_db_connection();
                    $pdo->query("SELECT 1");
                    $diagnostics[] = [
                        'check' => 'Database Connection',
                        'status' => 'ok',
                        'message' => 'Connected successfully'
                    ];
                } catch (Exception $e) {
                    $diagnostics[] = [
                        'check' => 'Database Connection',
                        'status' => 'error',
                        'message' => 'Connection failed: ' . $e->getMessage()
                    ];
                }
                
                // Check session directory
                $session_path = storage_path('sessions');
                if (is_dir($session_path) && is_writable($session_path)) {
                    $diagnostics[] = [
                        'check' => 'Session Directory',
                        'status' => 'ok',
                        'message' => 'Directory exists and is writable'
                    ];
                } else {
                    $diagnostics[] = [
                        'check' => 'Session Directory',
                        'status' => 'warning',
                        'message' => 'Directory may not exist or is not writable'
                    ];
                }
                
                // Check storage/logs directory
                $logs_path = storage_path('logs');
                if (is_dir($logs_path) || @mkdir($logs_path, 0755, true)) {
                    $diagnostics[] = [
                        'check' => 'Logs Directory',
                        'status' => 'ok',
                        'message' => 'Directory accessible'
                    ];
                } else {
                    $diagnostics[] = [
                        'check' => 'Logs Directory',
                        'status' => 'warning',
                        'message' => 'Directory may not be accessible'
                    ];
                }
                
                if (function_exists('log_system_event')) {
                    $passed = count(array_filter($diagnostics, function($d) { return $d['status'] === 'ok'; }));
                    $total = count($diagnostics);
                    log_system_event('info', "System diagnostics run: $passed of $total checks passed", 'diagnostics', ['results' => $diagnostics]);
                }
                
                if (function_exists('log_security_event')) {
                    log_security_event('INFO Diagnostics Run', 'Developer ran system diagnostics');
                }
                
                $summary = count(array_filter($diagnostics, function($d) { return $d['status'] === 'ok'; })) . ' of ' . count($diagnostics) . ' checks passed';
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Diagnostics completed. ' . $summary,
                    'diagnostics' => $diagnostics
                ]);
                exit;
                
            case 'view-migrations':
                // View migration status (placeholder)
                // TODO: Implement migration status check if migration system exists
                if (function_exists('log_system_event')) {
                    log_system_event('info', 'Migration status viewed', 'migrations', ['status' => 'not_implemented']);
                }
                
                if (function_exists('log_security_event')) {
                    log_security_event('INFO Migrations Viewed', 'Developer viewed migration status');
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Migration system is not yet implemented. Check database schema manually.'
                ]);
                exit;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }
    } catch (Exception $e) {
        error_log('Developer dashboard action error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred: ' . ($_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : 'Please check server logs.')
        ]);
        exit;
    }
}

// Redirect to developer dashboard if no page parameter is set
if (!isset($_GET['page'])) {
    header('Location: ?page=developer-dashboard');
    exit;
}

// Include the header which handles routing
include '../includes/header.php';
?>
