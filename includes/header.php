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
        'edit_employee' => 'Edit Employee',
        'dtr' => 'Daily Time Record',
        'timeoff' => 'Time Off Management',
        'checklist' => 'Employee Checklist',
        'hiring' => 'Hiring Process',
        'onboarding' => 'Employee Onboarding',
        'handbook' => 'Hiring Handbook',
        'alerts' => 'Employee Alerts',
        'add_alert' => 'Add New Alert',
        'tasks' => 'Task',
        'posts' => 'Posts & Locations',
        'add_post' => 'Add New Post',
        'edit_post' => 'Edit Post',
        'post_assignments' => 'Post Assignments',
        'settings' => 'System Settings',
        'integrations' => 'Integrations',
        'help' => 'Help & Support',
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
        'post_assignments' => 'posts'
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
    <title><?php echo getPageTitle($page); ?> - Golden Z-5 HR System</title>
    
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
    <!-- number-rendering-fix.css merged into font-override.css -->
    
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
        <!-- Header -->
        <?php 
        // Pages that should not show the header
        $pages_without_header = ['permissions', 'employees', 'dashboard', 'posts', 'post_assignments', 'alerts', 'add_employee', 'view_employee', 'tasks', 'hr-help', 'help'];

        // HR Admin: show a connected top header with profile dropdown + dashboard quick actions
        if (($userRole ?? '') === 'hr_admin'): 
        ?>
        <?php
            $displayName = trim((string)($_SESSION['name'] ?? ($_SESSION['username'] ?? 'HR Admin')));
            $initials = 'HA';
            if ($displayName) {
                $parts = preg_split('/\s+/', $displayName);
                $first = $parts[0][0] ?? 'H';
                $last = (count($parts) > 1) ? ($parts[count($parts) - 1][0] ?? 'A') : ($parts[0][1] ?? 'A');
                $initials = strtoupper($first . $last);
            }

            // Optional counts for badges (safe fallbacks)
            $pendingTasks = 0;
            if (function_exists('get_pending_task_count')) {
                $pendingTasks = (int) get_pending_task_count();
            }
        ?>

        <header class="hr-admin-topbar" aria-label="HR Admin header">
            <div class="hr-admin-topbar__left" aria-hidden="true"></div>

            <div class="hr-admin-topbar__main">
                <div class="hr-admin-topbar__title">
                    <h1 class="mb-0" id="pageTitle"><?php echo getPageTitle($page); ?></h1>
                </div>

                <div class="hr-admin-topbar__actions" role="navigation" aria-label="Header actions">
                    <?php if ($page === 'dashboard'): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-modern btn-sm hr-admin-action-btn dropdown-toggle"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <i class="fas fa-plus me-2"></i>Add
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="?page=add_employee"><i class="fas fa-user-plus me-2"></i>Add Employee</a></li>
                                <li><a class="dropdown-item" href="?page=add_alert"><i class="fas fa-bell me-2"></i>Add Alert</a></li>
                                <li><a class="dropdown-item" href="?page=add_post"><i class="fas fa-briefcase me-2"></i>Add Post</a></li>
                            </ul>
                        </div>

                        <div class="dropdown">
                            <button class="btn btn-outline-modern btn-sm hr-admin-action-btn dropdown-toggle"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <i class="fas fa-eye me-2"></i>View
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="?page=employees"><i class="fas fa-users me-2"></i>Employees</a></li>
                                <li><a class="dropdown-item" href="?page=alerts"><i class="fas fa-bell me-2"></i>Alerts</a></li>
                                <li><a class="dropdown-item" href="?page=posts"><i class="fas fa-briefcase me-2"></i>Posts</a></li>
                                <li><a class="dropdown-item" href="?page=post_assignments"><i class="fas fa-diagram-project me-2"></i>Assignments</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <a class="hr-admin-icon-link <?php echo ($page === 'alerts') ? 'active' : ''; ?>"
                       href="?page=alerts"
                       title="Notifications"
                       aria-label="Notifications">
                        <i class="fas fa-bell" aria-hidden="true"></i>
                    </a>

                    <a class="hr-admin-icon-link <?php echo ($page === 'tasks') ? 'active' : ''; ?>"
                       href="?page=tasks"
                       title="Tasks"
                       aria-label="Tasks">
                        <i class="fas fa-tasks" aria-hidden="true"></i>
                        <?php if ($pendingTasks > 0): ?>
                            <span class="hr-admin-badge" aria-label="<?php echo $pendingTasks; ?> pending tasks"><?php echo $pendingTasks > 99 ? '99+' : $pendingTasks; ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="dropdown">
                        <button class="hr-admin-profile-btn dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="Profile menu">
                            <span class="hr-admin-avatar" aria-hidden="true"><?php echo htmlspecialchars($initials); ?></span>
                            <span class="hr-admin-profile-name d-none d-md-inline"><?php echo htmlspecialchars($displayName); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?page=settings"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger"
                                   href="<?php echo base_url(); ?>/index.php?logout=1"
                                   data-no-transition="true">
                                    <i class="fas fa-right-from-bracket me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        <?php elseif (!in_array($page, $pages_without_header)): ?>
        <header class="header">
            <div class="d-flex align-items-center">
                <h1 class="mb-0" id="pageTitle"><?php echo getPageTitle($page); ?></h1>
            </div>
        </header>
        <?php endif; ?>

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
                default:
                    include $pagesPath . 'dashboard.php';
                    break;
            }
            ?>
        </main>
    </div>


    <?php include __DIR__ . '/footer.php'; ?>
