<?php
// Include database connection and path helpers
include __DIR__ . '/database.php';
include_once __DIR__ . '/paths.php';

// Resolve base paths regardless of entrypoint directory (developer, hr-admin, etc.)
$basePath = dirname(__DIR__);
$pagesPath = $basePath . '/pages/';

// Function to get page title based on current page
function getPageTitle($page) {
    $titles = [
        'dashboard' => 'Dashboard',
        'employees' => 'Employee Management',
        'add_employee' => 'Add New Employee',
        'add_employee_page2' => 'Add New Employee - Page 2',
        'edit_employee' => 'Edit Employee',
        'view_employee' => 'View Employee',
        'dtr' => 'Daily Time Record',
        'timeoff' => 'Time Off Management',
        'checklist' => 'Employee Checklist',
        'hiring' => 'Hiring Process',
        'onboarding' => 'Employee Onboarding',
        'handbook' => 'Hiring Handbook',
        'alerts' => 'Employee Alerts',
        'add_alert' => 'Add New Alert',
        'tasks' => 'Tasks',
        'posts' => 'Posts & Locations',
        'add_post' => 'Add New Post',
        'edit_post' => 'Edit Post',
        'post_assignments' => 'Post Assignments',
        'settings' => 'System Settings',
        'profile' => 'My Profile',
        'integrations' => 'Integrations',
        'help' => 'Help & Support',
        'system_logs' => 'System Logs',
        'documents' => '201 Files - Document Management',
        'leaves' => 'Leave Requests',
        'leave_balance' => 'Leave Balance',
        'leave_reports' => 'Leave Reports',
        'attendance' => 'Attendance Management',
        'violations' => 'Employee Violations',
        'add_violation' => 'Add New Employee Violation',
        'edit_violation' => 'Edit Employee Violation',
        'violation_types' => 'Violation Types & Sanctions',
        'violation_history' => 'Violation History',
    ];
    
    return $titles[$page] ?? 'Dashboard';
}

// Function to get active section based on current page
function getActiveSection($page) {
    $sections = [
        'employees' => 'teams',
        'dtr' => 'teams',
        'timeoff' => 'teams',
        'checklist' => 'teams',
        'hiring' => 'hire',
        'onboarding' => 'hire',
        'handbook' => 'hire',
        'posts' => 'posts',
        'add_post' => 'posts',
        'edit_post' => 'posts',
        'post_assignments' => 'posts',
        'leaves' => 'leaves',
        'leave_balance' => 'leaves',
        'leave_reports' => 'leaves',
        'violations' => 'violations',
        'violation_types' => 'violations'
    ];
    
    return $sections[$page] ?? null;
}

// Get current page
$page = $_GET['page'] ?? 'dashboard';
$activeSection = getActiveSection($page);

// Portal-scoped body class (used for portal-only styling overrides)
$portalBodyClass = '';
$userRole = $_SESSION['user_role'] ?? '';
if ($userRole === 'hr_admin') {
    $portalBodyClass = 'portal-hr-admin';
} elseif ($userRole === 'developer') {
    $portalBodyClass = 'portal-developer';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getPageTitle($page); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo public_url('logo.svg'); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo public_url('favicon.ico'); ?>">
    <link rel="apple-touch-icon" href="<?php echo public_url('logo.svg'); ?>">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0&family=Google+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?php echo asset_url('css/style.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/font-override.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/utilities.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/notifications.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/logout-animation.css'); ?>" rel="stylesheet">
    <!-- number-rendering-fix.css merged into font-override.css -->
    
    <!-- Page-specific CSS -->
    <?php if ($page === 'employees'): ?>
    <link href="<?php echo asset_url('css/employees.css'); ?>" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body<?php echo $portalBodyClass ? ' class="' . htmlspecialchars($portalBodyClass) . '"' : ''; ?>>
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Permanent Page Header (like sidebar) -->
        <?php include __DIR__ . '/page-header.php'; ?>

        <!-- Content -->
        <main class="content">
            <?php display_message(); ?>
            <!-- Page content will be included here -->
            <?php
            // Include the requested page content
            switch ($page) {
                case 'dashboard':
                    include $pagesPath . 'dashboard.php';
                    break;
                case 'employees':
                    include $pagesPath . 'employees.php';
                    break;
                case 'add_employee':
                    include $pagesPath . 'add_employee.php';
                    break;
                case 'add_employee_page2':
                    include $pagesPath . 'add_employee_page2.php';
                    break;
                case 'edit_employee':
                    include $pagesPath . 'edit_employee.php';
                    break;
                case 'view_employee':
                    include $pagesPath . 'view_employee.php';
                    break;
                case 'alerts':
                    include $pagesPath . 'alerts.php';
                    break;
                case 'add_alert':
                    include $pagesPath . 'add_alert.php';
                    break;
                case 'tasks':
                    include $pagesPath . 'tasks.php';
                    break;
                case 'help':
                    include $pagesPath . 'hr-help.php';
                    break;
                case 'posts':
                    include $pagesPath . 'posts.php';
                    break;
                case 'add_post':
                    include $pagesPath . 'add_post.php';
                    break;
                case 'edit_post':
                    include $pagesPath . 'edit_post.php';
                    break;
                case 'post_assignments':
                    include $pagesPath . 'post_assignments.php';
                    break;
                case 'settings':
                    include $pagesPath . 'hr-admin-settings.php';
                    break;
                case 'profile':
                    include $pagesPath . 'profile.php';
                    break;
                case 'system_logs':
                    // Developer-specific system logs
                    if (($userRole ?? '') === 'developer') {
                        include $pagesPath . 'developer-system-logs.php';
                    } else {
                        include $pagesPath . 'system_logs.php';
                    }
                    break;
                case 'documents':
                    include $pagesPath . 'documents.php';
                    break;
                case 'leaves':
                    include $pagesPath . 'leaves.php';
                    break;
                case 'leave_balance':
                    include $pagesPath . 'leave_balance.php';
                    break;
                case 'leave_reports':
                    include $pagesPath . 'leave_reports.php';
                    break;
                case 'attendance':
                    include $pagesPath . 'attendance.php';
                    break;
                case 'violations':
                    include $pagesPath . 'violations.php';
                    break;
                case 'violation_types':
                    include $pagesPath . 'violation_types.php';
                    break;
                case 'add_violation':
                    include $pagesPath . 'add_violation.php';
                    break;
                case 'edit_violation':
                    include $pagesPath . 'edit_violation.php';
                    break;
                case 'violation_history':
                    include $pagesPath . 'violation_history.php';
                    break;
                default:
                    include $pagesPath . 'dashboard.php';
                    break;
            }
            ?>
        </main>
    </div>


    <?php include __DIR__ . '/footer.php'; ?>
