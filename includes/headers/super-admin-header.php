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
    <title><?php echo getPageTitle($page); ?> - Super Admin - Golden Z-5 HR System</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0&family=Google+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?php echo asset_url('css/style.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/font-override.css'); ?>" rel="stylesheet">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body>
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
                        data-target="posts-submenu"
                        onclick="event.preventDefault(); if(window.sidebarNav){window.sidebarNav.toggleSection(event);} else {const submenu=document.getElementById('posts-submenu'); const arrow=this.querySelector('.nav-arrow'); if(submenu.classList.contains('expanded')){submenu.classList.remove('expanded');arrow.classList.remove('rotated');this.setAttribute('aria-expanded','false');}else{submenu.classList.add('expanded');arrow.classList.add('rotated');this.setAttribute('aria-expanded','true');}}">
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
                    <li class="nav-item">
                        <a href="?page=post_assignments" 
                           class="nav-link <?php echo ($page === 'post_assignments') ? 'active' : ''; ?>"
                           data-page="post_assignments">
                            <i class="fas fa-users-cog" aria-hidden="true"></i>
                            <span>Assignments</span>
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
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'administration') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
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
                <a href="?page=integrations" 
                   class="nav-link <?php echo ($page === 'integrations') ? 'active' : ''; ?>"
                   data-page="integrations">
                    <i class="fas fa-cloud" aria-hidden="true"></i>
                    <span>Integrations</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="?page=help" 
                   class="nav-link <?php echo ($page === 'help') ? 'active' : ''; ?>"
                   data-page="help">
                    <i class="fas fa-headset" aria-hidden="true"></i>
                    <span>Help and support</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../index.php?logout=1" 
                   class="nav-link">
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
                    include $pages_path . 'super-admin-dashboard.php';
                    break;
                case 'employees':
                    include $pages_path . 'employees.php';
                    break;
                case 'add_employee':
                    include $pages_path . 'add_employee.php';
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
                    include $pages_path . 'dtr.php';
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
                case 'integrations':
                    include $pages_path . 'integrations.php';
                    break;
                case 'help':
                    include $pages_path . 'help.php';
                    break;
                case 'settings':
                    include $pages_path . 'settings.php';
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
                case 'audit_trail':
                    // Super admin specific pages - create these later
                    echo '<div class="container-fluid"><div class="alert alert-info">Page under construction: ' . htmlspecialchars($page) . '</div></div>';
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
    // Ensure Administration and Posts sections toggle work
    document.addEventListener('DOMContentLoaded', function() {
        // Administration section toggle
        const adminToggle = document.querySelector('[data-target="administration-submenu"]');
        const adminSubmenu = document.getElementById('administration-submenu');
        
        if (adminToggle && adminSubmenu) {
            // Re-attach click handler if needed
            adminToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const isExpanded = adminSubmenu.classList.contains('expanded');
                const arrow = this.querySelector('.nav-arrow');
                
                if (isExpanded) {
                    adminSubmenu.classList.remove('expanded');
                    arrow.classList.remove('rotated');
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    adminSubmenu.classList.add('expanded');
                    arrow.classList.add('rotated');
                    this.setAttribute('aria-expanded', 'true');
                }
                
                // Also trigger the sidebar navigation if available
                if (window.sidebarNav && typeof window.sidebarNav.toggleSection === 'function') {
                    window.sidebarNav.toggleSection(e);
                }
            });
            
            // Auto-expand if users page is active
            const usersLink = document.querySelector('.nav-link[data-page="users"]');
            if (usersLink && usersLink.classList.contains('active')) {
                adminSubmenu.classList.add('expanded');
                const arrow = adminToggle.querySelector('.nav-arrow');
                if (arrow) arrow.classList.add('rotated');
                adminToggle.setAttribute('aria-expanded', 'true');
            }
        }
        
        // Posts section toggle
        const postsToggle = document.querySelector('[data-target="posts-submenu"]');
        const postsSubmenu = document.getElementById('posts-submenu');
        
        if (postsToggle && postsSubmenu) {
            // Re-attach click handler if needed
            postsToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const isExpanded = postsSubmenu.classList.contains('expanded');
                const arrow = this.querySelector('.nav-arrow');
                
                if (isExpanded) {
                    postsSubmenu.classList.remove('expanded');
                    arrow.classList.remove('rotated');
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    postsSubmenu.classList.add('expanded');
                    arrow.classList.add('rotated');
                    this.setAttribute('aria-expanded', 'true');
                }
                
                // Also trigger the sidebar navigation if available
                if (window.sidebarNav && typeof window.sidebarNav.toggleSection === 'function') {
                    window.sidebarNav.toggleSection(e);
                }
            });
            
            // Auto-expand if posts/add_post/post_assignments page is active
            const postsLink = document.querySelector('.nav-link[data-page="posts"]');
            const addPostLink = document.querySelector('.nav-link[data-page="add_post"]');
            const assignmentsLink = document.querySelector('.nav-link[data-page="post_assignments"]');
            
            if ((postsLink && postsLink.classList.contains('active')) ||
                (addPostLink && addPostLink.classList.contains('active')) ||
                (assignmentsLink && assignmentsLink.classList.contains('active'))) {
                postsSubmenu.classList.add('expanded');
                const arrow = postsToggle.querySelector('.nav-arrow');
                if (arrow) arrow.classList.add('rotated');
                postsToggle.setAttribute('aria-expanded', 'true');
            }
        }
    });
    </script>

