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

// Include the header which handles routing
include '../includes/headers/super-admin-header.php';
?>
