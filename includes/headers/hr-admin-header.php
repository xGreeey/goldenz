<?php
// Include database connection and path helpers
include '../includes/database.php';
include_once '../includes/paths.php';

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
        'posts' => 'Posts & Locations',
        'add_post' => 'Add New Post',
        'edit_post' => 'Edit Post',
        'post_assignments' => 'Post Assignments',
        'settings' => 'System Settings',
        'profile' => 'My Profile',
        'tasks' => 'Tasks',
        'help' => 'Help & Support',
        'attendance' => 'Attendance Management',
        'dtr' => 'Daily Time Record (DTR)',
        'leaves' => 'Leave Requests',
        'leave_balance' => 'Leave Balance',
        'leave_reports' => 'Leave Reports',
        'documents' => '201 Files - Document Management',
        'violations' => 'Employee Violations',
        'violation_types' => 'Violation Types & Sanctions',
        'add_violation' => 'Add New Employee Violation',
        'edit_violation' => 'Edit Employee Violation',
        'violation_history' => 'Violation History'
    ];

    return $titles[$page] ?? 'Dashboard';
}

// Function to get active section based on current page
function getActiveSection($page) {
    $sections = [
        'employees' => 'teams',
        'timeoff' => 'teams',
        'checklist' => 'teams',
        'hiring' => 'hire',
        'onboarding' => 'hire',
        'handbook' => 'hire',
        'posts' => 'posts',
        'add_post' => 'posts',
        'edit_post' => 'posts',
        'post_assignments' => 'posts',
        'attendance' => 'attendance',
        'dtr' => 'attendance',
        'leaves' => 'leaves',
        'leave_balance' => 'leaves',
        'leave_reports' => 'leaves',
        'documents' => 'documents',
        'violations' => 'violations',
        'violation_types' => 'violations',
        'add_violation' => 'violations',
        'edit_violation' => 'violations',
        'violation_history' => 'violations'
    ];

    return $sections[$page] ?? null;
}

// Get current page
$page = $_GET['page'] ?? 'dashboard';
$activeSection = getActiveSection($page);
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
    <link href="<?php echo asset_url('css/custom-icons.css'); ?>" rel="stylesheet">
    <!-- number-rendering-fix.css merged into font-override.css -->
    <style>
    /* Override icon paths with absolute URLs for SVG icons */
    <?php
    $iconUrl = function($icon) { return asset_url('icons/' . $icon . '.svg'); };
    ?>
    .hr-icon-plus {
        background-image: url('<?php echo asset_url('icons/plus-icon.png'); ?>') !important;
    }
    .hr-icon-edit {
        background-image: url('<?php echo $iconUrl('edit-icon'); ?>') !important;
    }
    .hr-icon-view, .hr-icon-eye {
        background-image: url('<?php echo $iconUrl('view-icon_eye-icon'); ?>') !important;
    }
    .hr-icon-check {
        background-image: url('<?php echo $iconUrl('check-icon'); ?>') !important;
    }
    .hr-icon-double-check {
        background-image: url('<?php echo $iconUrl('double-check-icon'); ?>') !important;
    }
    .hr-icon-dismiss, .hr-icon-remove, .hr-icon-times {
        background-image: url('<?php echo $iconUrl('dismiss-icon_remove-icon'); ?>') !important;
    }
    .hr-icon-minus {
        background-image: url('<?php echo asset_url('icons/minus-icon.png'); ?>') !important;
    }
    .hr-icon-notification, .hr-icon-bell {
        background-image: url('<?php echo $iconUrl('notif-icon'); ?>') !important;
    }
    .hr-icon-message {
        background-image: url('<?php echo $iconUrl('message-icon'); ?>') !important;
    }

    /* Apply filters to colorize icons for action buttons */
    .btn-action-modern.btn-info-modern .hr-icon {
        filter: brightness(0) saturate(100%) invert(27%) sepia(96%) saturate(2595%) hue-rotate(212deg) brightness(95%) contrast(91%) !important;
    }
    .btn-action-modern.btn-success-modern .hr-icon {
        filter: brightness(0) saturate(100%) invert(45%) sepia(93%) saturate(1352%) hue-rotate(88deg) brightness(98%) contrast(86%) !important;
    }
    .btn-action-modern.btn-secondary-modern .hr-icon {
        filter: brightness(0) saturate(100%) invert(48%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%) !important;
    }
    .btn-action-modern.btn-primary-modern .hr-icon {
        filter: brightness(0) invert(1) !important;
    }

    /* Ensure icons are displayed and visible */
    .hr-icon {
        display: inline-block !important;
        visibility: visible !important;
    }

    /* Make notification and message icons visible in header buttons */
    .hrdash-welcome__icon-btn .hr-icon-notification,
    .hrdash-welcome__icon-btn .hr-icon-message {
        width: 24px !important;
        height: 24px !important;
        opacity: 1 !important;
        filter: brightness(0) saturate(100%) invert(40%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%) !important;
        background-size: contain !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
    }

    .hrdash-welcome__icon-btn:hover .hr-icon-notification,
    .hrdash-welcome__icon-btn:hover .hr-icon-message {
        filter: brightness(0) saturate(100%) invert(15%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%) !important;
    }
    </style>

    <!-- Enhanced Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <?php if ($page === 'add_employee'): ?>
    <!-- Preload icon images for add employee page to prevent flashing -->
    <link rel="preload" as="image" href="<?php echo asset_url('icons/plus-icon.png'); ?>?v=2">
    <link rel="preload" as="image" href="<?php echo asset_url('icons/minus-icon.png'); ?>?v=2">
    <?php endif; ?>

    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body class="portal-hr-admin">
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
        <div class="sidebar-header">
            <div class="logo-container mb-3">
                <img src="<?php echo public_url('logo.svg'); ?>" alt="Golden Z-5 Logo" class="logo-img" style="max-width: 120px; height: auto;">
            </div>
            <h3>Golden Z-5</h3>
            <small>HR / Admin Portal</small>
            <button class="sidebar-toggle d-md-none" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Search Bar -->
        <div class="sidebar-search">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text"
                       placeholder="Search..."
                       id="sidebarSearch"
                       aria-label="Search menu items"
                       autocomplete="off">
                <button class="search-clear d-none" id="searchClear" aria-label="Clear search">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="search-results" id="searchResults" aria-live="polite" aria-atomic="true"></div>
        </div>

        <ul class="sidebar-menu" id="sidebarMenu">
            <li class="nav-item">
                <a href="?page=dashboard"
                   class="nav-link <?php echo ($page === 'dashboard') ? 'active' : ''; ?>"
                   data-page="dashboard">
                    <i class="fas fa-home" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Teams Section -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'teams') ? 'active' : ''; ?>" data-section="teams">
                <button class="nav-toggle <?php echo ($activeSection === 'teams') ? 'active' : ''; ?>"
                        type="button"
                        role="button"
                        aria-expanded="<?php echo ($activeSection === 'teams') ? 'true' : 'false'; ?>"
                        aria-controls="teams-submenu"
                        tabindex="0"
                        data-target="teams-submenu">
                    <i class="fas fa-users" aria-hidden="true"></i>
                    <span>Teams</span>
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'teams') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'teams') ? 'expanded' : ''; ?>" id="teams-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=employees"
                           class="nav-link <?php echo ($page === 'employees') ? 'active' : ''; ?>"
                           data-page="employees">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <span>Employee</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=checklist"
                           class="nav-link <?php echo ($page === 'checklist') ? 'active' : ''; ?>"
                           data-page="checklist">
                            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                            <span>Checklist</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=timeoff"
                           class="nav-link <?php echo ($page === 'timeoff') ? 'active' : ''; ?>"
                           data-page="timeoff">
                            <i class="fas fa-calendar-times" aria-hidden="true"></i>
                            <span>Time off</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Hire Section -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'hire') ? 'active' : ''; ?>" data-section="hire">
                <button class="nav-toggle <?php echo ($activeSection === 'hire') ? 'active' : ''; ?>"
                        type="button"
                        role="button"
                        aria-expanded="<?php echo ($activeSection === 'hire') ? 'true' : 'false'; ?>"
                        aria-controls="hire-submenu"
                        tabindex="0"
                        data-target="hire-submenu">
                    <i class="fas fa-briefcase" aria-hidden="true"></i>
                    <span>Hire</span>
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'hire') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'hire') ? 'expanded' : ''; ?>" id="hire-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=hiring"
                           class="nav-link <?php echo ($page === 'hiring') ? 'active' : ''; ?>"
                           data-page="hiring">
                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                            <span>Hiring</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=onboarding"
                           class="nav-link <?php echo ($page === 'onboarding') ? 'active' : ''; ?>"
                           data-page="onboarding">
                            <i class="fas fa-user-check" aria-hidden="true"></i>
                            <span>Onboarding</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=handbook"
                           class="nav-link <?php echo ($page === 'handbook') ? 'active' : ''; ?>"
                           data-page="handbook">
                            <i class="fas fa-book" aria-hidden="true"></i>
                            <span>Hiring handbook</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Posts Section -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'posts') ? 'active' : ''; ?>" data-section="posts">
                <button class="nav-toggle <?php echo ($activeSection === 'posts') ? 'active' : ''; ?>"
                        type="button"
                        role="button"
                        aria-expanded="<?php echo ($activeSection === 'posts') ? 'true' : 'false'; ?>"
                        aria-controls="posts-submenu"
                        tabindex="0"
                        data-target="posts-submenu">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    <span>Posts & Locations</span>
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'posts') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'posts') ? 'expanded' : ''; ?>" id="posts-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=posts"
                           class="nav-link <?php echo ($page === 'posts') ? 'active' : ''; ?>"
                           data-page="posts">
                            <i class="fas fa-list" aria-hidden="true"></i>
                            <span>All Posts</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=add_post"
                           class="nav-link <?php echo ($page === 'add_post') ? 'active' : ''; ?>"
                           data-page="add_post">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            <span>Add New Post</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Attendance Section -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'attendance') ? 'active' : ''; ?>" data-section="attendance">
                <button class="nav-toggle <?php echo ($activeSection === 'attendance') ? 'active' : ''; ?>"
                        type="button"
                        role="button"
                        aria-expanded="<?php echo ($activeSection === 'attendance') ? 'true' : 'false'; ?>"
                        aria-controls="attendance-submenu"
                        tabindex="0"
                        data-target="attendance-submenu">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <span>Attendance</span>
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'attendance') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'attendance') ? 'expanded' : ''; ?>" id="attendance-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=attendance"
                           class="nav-link <?php echo ($page === 'attendance') ? 'active' : ''; ?>"
                           data-page="attendance">
                            <i class="fas fa-calendar-check" aria-hidden="true"></i>
                            <span>Daily Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=dtr"
                           class="nav-link <?php echo ($page === 'dtr') ? 'active' : ''; ?>"
                           data-page="dtr">
                            <i class="fas fa-file-alt" aria-hidden="true"></i>
                            <span>Daily Time Record (DTR)</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Alerts Section -->
            <li class="nav-item">
                <a href="?page=alerts"
                   class="nav-link <?php echo ($page === 'alerts') ? 'active' : ''; ?>"
                   data-page="alerts">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    <span>Alerts</span>
                    <?php
                    $alert_stats = get_alert_statistics();
                    if ($alert_stats['total_active'] > 0) {
                        echo '<span class="nav-badge" aria-label="' . $alert_stats['total_active'] . ' active alerts">' . $alert_stats['total_active'] . '</span>';
                    }
                    ?>
                </a>
            </li>
        </ul>

        <!-- Bottom Navigation Section -->
        <ul class="sidebar-menu sidebar-bottom" role="menubar">
            <li class="nav-item">
                <a href="?page=settings"
                   class="nav-link <?php echo ($page === 'settings') ? 'active' : ''; ?>"
                   data-page="settings">
                    <i class="fas fa-cog" aria-hidden="true"></i>
                    <span>Settings</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="?page=tasks"
                   class="nav-link <?php echo ($page === 'tasks') ? 'active' : ''; ?>"
                   data-page="tasks">
                    <i class="fas fa-tasks" aria-hidden="true"></i>
                    <span>Tasks</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="?page=help"
                   class="nav-link <?php echo ($page === 'help') ? 'active' : ''; ?>"
                   data-page="help">
                    <i class="fas fa-headset" aria-hidden="true"></i>
                    <span>Help & Support</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../index.php?logout=1"
                   class="nav-link"
                   data-no-transition="true">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Mobile Overlay -->
    <div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <header class="header">
            <div class="d-flex align-items-center">
                <h1 class="mb-0" id="pageTitle"><?php echo getPageTitle($page); ?></h1>
            </div>
        </header>

        <!-- Content -->
        <main class="content">
            <?php display_message(); ?>
            <!-- Page content will be included here -->
            <?php
            // Include the requested page content
            $pages_path = '../pages/';
            switch ($page) {
                case 'dashboard':
                    include $pages_path . 'dashboard.php';
                    break;
                case 'employees':
                    include $pages_path . 'employees.php';
                    break;
                case 'add_employee':
                    include $pages_path . 'add_employee.php';
                    break;
                case 'add_employee_page2':
                    include $pages_path . 'add_employee_page2.php';
                    break;
                case 'edit_employee':
                    include $pages_path . 'edit_employee.php';
                    break;
                case 'alerts':
                    include $pages_path . 'alerts.php';
                    break;
                case 'add_alert':
                    include $pages_path . 'add_alert.php';
                    break;
                case 'timeoff':
                    include $pages_path . 'timeoff.php';
                    break;
                case 'checklist':
                    include $pages_path . 'checklist.php';
                    break;
                case 'hiring':
                    include $pages_path . 'hiring.php';
                    break;
                case 'onboarding':
                    include $pages_path . 'onboarding.php';
                    break;
                case 'handbook':
                    include $pages_path . 'handbook.php';
                    break;
                case 'tasks':
                    include $pages_path . 'tasks.php';
                    break;
                case 'help':
                    include $pages_path . 'hr-help.php';
                    break;
                case 'permissions':
                    include $pages_path . 'permissions.php';
                    break;
                case 'settings':
                    include $pages_path . 'settings.php';
                    break;
                case 'profile':
                    include $pages_path . 'profile.php';
                    break;
                case 'posts':
                    include $pages_path . 'posts.php';
                    break;
                case 'add_post':
                    include $pages_path . 'add_post.php';
                    break;
                case 'edit_post':
                    include $pages_path . 'edit_post.php';
                    break;
                case 'post_assignments':
                    include $pages_path . 'post_assignments.php';
                    break;
                case 'attendance':
                    include $pages_path . 'attendance.php';
                    break;
                case 'dtr':
                    include $pages_path . 'dtr.php';
                    break;
                case 'leaves':
                    include $pages_path . 'leaves.php';
                    break;
                case 'leave_balance':
                    include $pages_path . 'leave_balance.php';
                    break;
                case 'leave_reports':
                    include $pages_path . 'leave_reports.php';
                    break;
                case 'documents':
                    include $pages_path . 'documents.php';
                    break;
                case 'violations':
                    include $pages_path . 'violations.php';
                    break;
                case 'violation_types':
                    include $pages_path . 'violation_types.php';
                    break;
                case 'add_violation':
                    include $pages_path . 'add_violation.php';
                    break;
                case 'edit_violation':
                    include $pages_path . 'edit_violation.php';
                    break;
                case 'violation_history':
                    include $pages_path . 'violation_history.php';
                    break;
                default:
                    include $pages_path . 'dashboard.php';
                    break;
            }
            ?>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>

