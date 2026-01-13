<?php
/**
 * Golden Z-5 HR Management System - Main Entry Point
 * Role-Based Access Control System
 * Routes users to appropriate role-based interfaces
 */

// Bootstrap application
require_once __DIR__ . '/bootstrap/app.php';

// Include legacy functions for backward compatibility
require_once 'includes/security.php';
require_once 'includes/database.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: landing/index.php');
    exit;
}

// Enforce login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: landing/index.php');
    exit;
}

// Route based on allowed roles only
$user_role = $_SESSION['user_role'] ?? null;
switch ($user_role) {
    case 'super_admin':
        header('Location: super-admin/index.php');
        exit;
    case 'hr_admin':
        header('Location: hr-admin/index.php');
        exit;
    case 'developer':
        header('Location: developer/index.php');
        exit;
    default:
        // Invalid role, redirect to login
        session_destroy();
        header('Location: landing/index.php');
        exit;
}
?>