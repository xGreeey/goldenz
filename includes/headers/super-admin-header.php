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
        'edit_employee' => 'Edit Employee',
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
        'integrations' => 'Integrations',
        'help' => 'Help & Support',
        'users' => 'User Management',
        'system_logs' => 'System Logs',
        'audit_trail' => 'Audit Trail'
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
        'users' => 'administration',
        'system_logs' => 'administration',
        'audit_trail' => 'administration'
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
    <link href="<?php echo asset_url('css/logout-animation.css'); ?>" rel="stylesheet">
    <!-- number-rendering-fix.css merged into font-override.css -->
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <!-- Note: X-Frame-Options should be set via HTTP header, not meta tag -->
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body class="portal-super-admin">
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
        <div class="sidebar-header">
            <div class="logo-container mb-3">
                <img src="<?php echo public_url('logo.svg'); ?>" alt="Golden Z-5 Logo" class="logo-img" style="max-width: 120px; height: auto;">
            </div>
            <h3>Golden Z-5</h3>
            <small>Super Admin Portal</small>
            <button class="sidebar-toggle d-md-none" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
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
                        data-target="teams-submenu"
                        onclick="event.preventDefault(); if(window.sidebarNav){window.sidebarNav.toggleSection(event);} else {const submenu=document.getElementById('teams-submenu'); const arrow=this.querySelector('.nav-arrow'); if(submenu.classList.contains('expanded')){submenu.classList.remove('expanded');arrow.classList.remove('rotated');this.setAttribute('aria-expanded','false');}else{submenu.classList.add('expanded');arrow.classList.add('rotated');this.setAttribute('aria-expanded','true');}}">
                    <i class="fas fa-users" aria-hidden="true"></i>
                    <span>Teams</span>
                    <span class="nav-arrow <?php echo ($activeSection === 'teams') ? 'rotated' : ''; ?>" aria-hidden="true">▼</span>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'teams') ? 'expanded' : ''; ?>" id="teams-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=teams" 
                           class="nav-link <?php echo ($page === 'teams') ? 'active' : ''; ?>"
                           data-page="teams">
                            <i class="fas fa-users-cog" aria-hidden="true"></i>
                            <span>Teams</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=employees" 
                           class="nav-link <?php echo ($page === 'employees') ? 'active' : ''; ?>"
                           data-page="employees">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <span>Employee</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=dtr" 
                           class="nav-link <?php echo ($page === 'dtr') ? 'active' : ''; ?>"
                           data-page="dtr">
                            <i class="fas fa-clock" aria-hidden="true"></i>
                            <span>Attendance</span>
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
                        data-target="hire-submenu"
                        onclick="event.preventDefault(); if(window.sidebarNav){window.sidebarNav.toggleSection(event);} else {const submenu=document.getElementById('hire-submenu'); const arrow=this.querySelector('.nav-arrow'); if(submenu.classList.contains('expanded')){submenu.classList.remove('expanded');arrow.classList.remove('rotated');this.setAttribute('aria-expanded','false');}else{submenu.classList.add('expanded');arrow.classList.add('rotated');this.setAttribute('aria-expanded','true');}}">
                    <i class="fas fa-briefcase" aria-hidden="true"></i>
                    <span>Hire</span>
                    <span class="nav-arrow <?php echo ($activeSection === 'hire') ? 'rotated' : ''; ?>" aria-hidden="true">▼</span>
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
                        data-target="posts-submenu"
                        onclick="event.preventDefault(); if(window.sidebarNav){window.sidebarNav.toggleSection(event);} else {const submenu=document.getElementById('posts-submenu'); const arrow=this.querySelector('.nav-arrow'); if(submenu.classList.contains('expanded')){submenu.classList.remove('expanded');arrow.classList.remove('rotated');this.setAttribute('aria-expanded','false');}else{submenu.classList.add('expanded');arrow.classList.add('rotated');this.setAttribute('aria-expanded','true');}}">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    <span>Posts & Locations</span>
                    <span class="nav-arrow <?php echo ($activeSection === 'posts') ? 'rotated' : ''; ?>" aria-hidden="true">▼</span>
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
            
            <!-- Administration Section (Super Admin Only) -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'administration') ? 'active' : ''; ?>" data-section="administration">
                <button class="nav-toggle <?php echo ($activeSection === 'administration') ? 'active' : ''; ?>" 
                        type="button"
                        role="button" 
                        aria-expanded="<?php echo ($activeSection === 'administration') ? 'true' : 'false'; ?>" 
                        aria-controls="administration-submenu"
                        tabindex="0"
                        data-target="administration-submenu"
                        onclick="event.preventDefault(); if(window.sidebarNav){window.sidebarNav.toggleSection(event);} else {const submenu=document.getElementById('administration-submenu'); const arrow=this.querySelector('.nav-arrow'); if(submenu.classList.contains('expanded')){submenu.classList.remove('expanded');arrow.classList.remove('rotated');this.setAttribute('aria-expanded','false');}else{submenu.classList.add('expanded');arrow.classList.add('rotated');this.setAttribute('aria-expanded','true');}}">
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    <span>Administration</span>
                    <span class="nav-arrow <?php echo ($activeSection === 'administration') ? 'rotated' : ''; ?>" aria-hidden="true">▼</span>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'administration') ? 'expanded' : ''; ?>" id="administration-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=users" 
                           class="nav-link <?php echo ($page === 'users') ? 'active' : ''; ?>"
                           data-page="users">
                            <i class="fas fa-user-shield" aria-hidden="true"></i>
                            <span>User Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                    </li>
                    <li class="nav-item">
                        <a href="?page=system_logs" 
                           class="nav-link <?php echo ($page === 'system_logs') ? 'active' : ''; ?>"
                           data-page="system_logs">
                            <i class="fas fa-file-alt" aria-hidden="true"></i>
                            <span>System Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=audit_trail" 
                           class="nav-link <?php echo ($page === 'audit_trail') ? 'active' : ''; ?>"
                           data-page="audit_trail">
                            <i class="fas fa-history" aria-hidden="true"></i>
                            <span>Audit Trail</span>
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
    </nav>
    
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header Section with Icons -->
        <?php include __DIR__ . '/../super-admin-header-section.php'; ?>
        
        <!-- Content -->
        <main class="content">
            <?php display_message(); ?>
            <!-- Page content will be included here -->
            <?php
            // Include the requested page content
            $pages_path = '../pages/';
            switch ($page) {
                case 'dashboard':
                    include $pages_path . 'super-admin-dashboard.php';
                    break;
                case 'employees':
                    include $pages_path . 'employees.php';
                    break;
                case 'teams':
                    include $pages_path . 'teams.php';
                    break;
                case 'add_employee':
                    include $pages_path . 'add_employee.php';
                    break;
                case 'edit_employee':
                    include $pages_path . 'edit_employee.php';
                    break;
                case 'view_employee':
                    include $pages_path . 'view_employee.php';
                    break;
                case 'alerts':
                    include $pages_path . 'alerts.php';
                    break;
                case 'add_alert':
                    include $pages_path . 'add_alert.php';
                    break;
                case 'dtr':
                    if (isset($_POST['action']) && $_POST['action'] === 'save') {
                        $input = json_decode(file_get_contents('php://input'), true);
                        if ($input && save_dtr_entry($input)) {
                            echo json_encode(['success' => true, 'message' => 'DTR entry saved successfully']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to save DTR entry']);
                        }
                        exit;
                    }
                    // DTR lives under pages/archive/
                    include $pages_path . 'archive/dtr.php';
                    break;
                case 'timeoff':
                    // Time Off lives under pages/archive/
                    include $pages_path . 'archive/timeoff.php';
                    break;
                case 'checklist':
                    // Checklist lives under pages/archive/
                    include $pages_path . 'archive/checklist.php';
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
                case 'help':
                    include $pages_path . 'help.php';
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
                case 'users':
                    include $pages_path . 'users.php';
                    break;
                case 'system_logs':
                    include $pages_path . 'system_logs.php';
                    break;
                case 'audit_trail':
                    include $pages_path . 'audit_trail.php';
                    break;
                default:
                    include $pages_path . 'dashboard.php';
                    break;
            }
            ?>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
    // Ensure Teams, Posts, Administration toggles work
    document.addEventListener('DOMContentLoaded', function() {
        function wireToggle(toggleSelector, submenuId, activePages = []) {
            const toggle = document.querySelector(toggleSelector);
            const submenu = document.getElementById(submenuId);
            if (!toggle || !submenu) return;

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const isExpanded = submenu.classList.contains('expanded');
                const arrow = this.querySelector('.nav-arrow');
                if (isExpanded) {
                    submenu.classList.remove('expanded');
                    if (arrow) arrow.classList.remove('rotated');
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    submenu.classList.add('expanded');
                    if (arrow) arrow.classList.add('rotated');
                    this.setAttribute('aria-expanded', 'true');
                }
                if (window.sidebarNav && typeof window.sidebarNav.toggleSection === 'function') {
                    window.sidebarNav.toggleSection(e);
                }
            });

            // Auto-expand if current page matches
            const shouldExpand = activePages.some(p => {
                const link = document.querySelector('.nav-link[data-page="' + p + '"]');
                return link && link.classList.contains('active');
            });
            if (shouldExpand) {
                submenu.classList.add('expanded');
                const arrow = toggle.querySelector('.nav-arrow');
                if (arrow) arrow.classList.add('rotated');
                toggle.setAttribute('aria-expanded', 'true');
            }
        }

        wireToggle('[data-target="administration-submenu"]', 'administration-submenu', ['users', 'system_logs', 'audit_trail']);
        wireToggle('[data-target="posts-submenu"]', 'posts-submenu', ['posts', 'add_post']);
        wireToggle('[data-target="teams-submenu"]', 'teams-submenu', ['teams', 'employees', 'dtr', 'checklist', 'timeoff']);
    });
    </script>

