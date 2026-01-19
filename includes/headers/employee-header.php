<?php
// Include database connection and path helpers
include '../includes/database.php';
include_once '../includes/paths.php';

// Function to get page title based on current page
function getPageTitle($page) {
    $titles = [
        'dashboard' => 'Dashboard',
        'profile' => 'My Profile',
        'dtr' => 'My Attendance',
        'timeoff' => 'Time Off Requests',
        'payslips' => 'Payslips',
        'documents' => 'My Documents',
        'help' => 'Help & Support'
    ];
    
    return $titles[$page] ?? 'Dashboard';
}

// Function to get active section based on current page
function getActiveSection($page) {
    $sections = [
        'profile' => 'personal',
        'dtr' => 'personal',
        'timeoff' => 'personal',
        'payslips' => 'payroll',
        'documents' => 'payroll'
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
            <small>Employee Portal</small>
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
            
            <!-- Personal Section -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'personal') ? 'active' : ''; ?>" data-section="personal">
                <button class="nav-toggle <?php echo ($activeSection === 'personal') ? 'active' : ''; ?>" 
                        type="button"
                        role="button" 
                        aria-expanded="<?php echo ($activeSection === 'personal') ? 'true' : 'false'; ?>" 
                        aria-controls="personal-submenu"
                        tabindex="0"
                        data-target="personal-submenu">
                    <i class="fas fa-user" aria-hidden="true"></i>
                    <span>Personal</span>
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'personal') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'personal') ? 'expanded' : ''; ?>" id="personal-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=profile" 
                           class="nav-link <?php echo ($page === 'profile') ? 'active' : ''; ?>"
                           data-page="profile">
                            <i class="fas fa-id-card" aria-hidden="true"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=dtr" 
                           class="nav-link <?php echo ($page === 'dtr') ? 'active' : ''; ?>"
                           data-page="dtr">
                            <i class="fas fa-clock" aria-hidden="true"></i>
                            <span>My Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=timeoff" 
                           class="nav-link <?php echo ($page === 'timeoff') ? 'active' : ''; ?>"
                           data-page="timeoff">
                            <i class="fas fa-calendar-times" aria-hidden="true"></i>
                            <span>Time Off Requests</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Payroll Section -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'payroll') ? 'active' : ''; ?>" data-section="payroll">
                <button class="nav-toggle <?php echo ($activeSection === 'payroll') ? 'active' : ''; ?>" 
                        type="button"
                        role="button" 
                        aria-expanded="<?php echo ($activeSection === 'payroll') ? 'true' : 'false'; ?>" 
                        aria-controls="payroll-submenu"
                        tabindex="0"
                        data-target="payroll-submenu">
                    <i class="fas fa-money-check-alt" aria-hidden="true"></i>
                    <span>Payroll</span>
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'payroll') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'payroll') ? 'expanded' : ''; ?>" id="payroll-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=payslips" 
                           class="nav-link <?php echo ($page === 'payslips') ? 'active' : ''; ?>"
                           data-page="payslips">
                            <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>
                            <span>Payslips</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=documents" 
                           class="nav-link <?php echo ($page === 'documents') ? 'active' : ''; ?>"
                           data-page="documents">
                            <i class="fas fa-file-alt" aria-hidden="true"></i>
                            <span>My Documents</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
        
        <!-- Bottom Navigation Section -->
        <ul class="sidebar-menu sidebar-bottom" role="menubar">
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
                case 'profile':
                    include $pages_path . 'profile.php';
                    break;
                case 'payslips':
                case 'documents':
                    // Employee specific pages - create these later
                    echo '<div class="container-fluid"><div class="alert alert-info">Page under construction: ' . htmlspecialchars($page) . '</div></div>';
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

