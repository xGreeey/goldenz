<?php
// Include database connection and path helpers
include '../includes/database.php';
include_once '../includes/paths.php';

// Function to get page title based on current page
function getPageTitle($page) {
    $titles = [
        'dashboard' => 'Dashboard',
        'deployments' => 'Deployments',
        'dtr' => 'Daily Time Record',
        'incidents' => 'Incident Tracker',
        'overtime' => 'Overtime Requests',
        'inventory' => 'Inventory',
        'switch_guards' => 'Switch Guards',
        'reports' => 'Operation Reports',
        'settings' => 'Settings',
        'profile' => 'My Profile',
        'help' => 'Help & Support'
    ];
    
    return $titles[$page] ?? 'Dashboard';
}

// Function to get active section based on current page
function getActiveSection($page) {
    $sections = [
        'deployments' => 'operations',
        'dtr' => 'operations',
        'incidents' => 'operations',
        'overtime' => 'operations',
        'inventory' => 'resources',
        'switch_guards' => 'resources',
        'reports' => 'reports'
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
    <title><?php echo getPageTitle($page); ?> - Operation - Golden Z-5 HR System</title>
    
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
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
        <div class="sidebar-header">
            <div class="logo-container mb-3">
                <img src="<?php echo public_url('logo.svg'); ?>" alt="Golden Z-5 Logo" class="logo-img" style="max-width: 120px; height: auto;">
            </div>
            <h3>Golden Z-5</h3>
            <small>Operation Portal</small>
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
            
            <!-- Operations Section -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'operations') ? 'active' : ''; ?>" data-section="operations">
                <button class="nav-toggle <?php echo ($activeSection === 'operations') ? 'active' : ''; ?>" 
                        type="button"
                        role="button" 
                        aria-expanded="<?php echo ($activeSection === 'operations') ? 'true' : 'false'; ?>" 
                        aria-controls="operations-submenu"
                        tabindex="0"
                        data-target="operations-submenu">
                    <i class="fas fa-tasks" aria-hidden="true"></i>
                    <span>Operations</span>
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'operations') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'operations') ? 'expanded' : ''; ?>" id="operations-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=deployments" 
                           class="nav-link <?php echo ($page === 'deployments') ? 'active' : ''; ?>"
                           data-page="deployments">
                            <i class="fas fa-map-marked-alt" aria-hidden="true"></i>
                            <span>Deployments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=dtr" 
                           class="nav-link <?php echo ($page === 'dtr') ? 'active' : ''; ?>"
                           data-page="dtr">
                            <i class="fas fa-clock" aria-hidden="true"></i>
                            <span>Daily Time Record</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=incidents" 
                           class="nav-link <?php echo ($page === 'incidents') ? 'active' : ''; ?>"
                           data-page="incidents">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            <span>Incident Tracker</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=overtime" 
                           class="nav-link <?php echo ($page === 'overtime') ? 'active' : ''; ?>"
                           data-page="overtime">
                            <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                            <span>Overtime Requests</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Resources Section -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'resources') ? 'active' : ''; ?>" data-section="resources">
                <button class="nav-toggle <?php echo ($activeSection === 'resources') ? 'active' : ''; ?>" 
                        type="button"
                        role="button" 
                        aria-expanded="<?php echo ($activeSection === 'resources') ? 'true' : 'false'; ?>" 
                        aria-controls="resources-submenu"
                        tabindex="0"
                        data-target="resources-submenu">
                    <i class="fas fa-boxes" aria-hidden="true"></i>
                    <span>Resources</span>
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'resources') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'resources') ? 'expanded' : ''; ?>" id="resources-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=inventory" 
                           class="nav-link <?php echo ($page === 'inventory') ? 'active' : ''; ?>"
                           data-page="inventory">
                            <i class="fas fa-warehouse" aria-hidden="true"></i>
                            <span>Inventory</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=switch_guards" 
                           class="nav-link <?php echo ($page === 'switch_guards') ? 'active' : ''; ?>"
                           data-page="switch_guards">
                            <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                            <span>Switch Guards</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Reports Section -->
            <li class="nav-item">
                <a href="?page=reports" 
                   class="nav-link <?php echo ($page === 'reports') ? 'active' : ''; ?>"
                   data-page="reports">
                    <i class="fas fa-chart-bar" aria-hidden="true"></i>
                    <span>Operation Reports</span>
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
                <a href="?page=help" 
                   class="nav-link <?php echo ($page === 'help') ? 'active' : ''; ?>"
                   data-page="help">
                    <i class="fas fa-headset" aria-hidden="true"></i>
                    <span>Help and support</span>
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
                case 'deployments':
                case 'incidents':
                case 'overtime':
                case 'inventory':
                case 'switch_guards':
                case 'reports':
                    // Operation specific pages - create these later
                    echo '<div class="container-fluid"><div class="alert alert-info">Page under construction: ' . htmlspecialchars($page) . '</div></div>';
                    break;
                case 'settings':
                    include $pages_path . 'settings.php';
                    break;
                case 'profile':
                    include $pages_path . 'profile.php';
                    break;
                case 'help':
                    include $pages_path . 'help.php';
                    break;
                default:
                    include $pages_path . 'dashboard.php';
                    break;
            }
            ?>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>

