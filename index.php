<?php
/**
 * Golden Z-5 HR Management System - Root Entry Point
 * Handles logout and redirects to landing page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/storage/sessions';
    if (is_dir($sessionPath) || mkdir($sessionPath, 0755, true)) {
        session_save_path($sessionPath);
    }
    session_start();
}

// Bootstrap application
require_once __DIR__ . '/bootstrap/app.php';

// Include legacy functions for backward compatibility
require_once 'includes/security.php';
require_once 'includes/database.php';

// Handle logout before redirecting
if (isset($_GET['logout'])) {
    // Clear remember token from database if user is logged in
    if (isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/includes/database.php';
            $pdo = get_db_connection();
            $clear_sql = "UPDATE users SET remember_token = NULL WHERE id = ?";
            $clear_stmt = $pdo->prepare($clear_sql);
            $clear_stmt->execute([$_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log('Error clearing remember token on logout: ' . $e->getMessage());
        }
    }
    
    // Clear remember token cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    session_unset();
    session_destroy();
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    header('Location: /landing/');
    exit;
}

// Redirect all other requests to landing page
header('Location: /landing/');
exit;
?>