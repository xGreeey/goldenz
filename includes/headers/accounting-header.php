<?php
// Include database connection and path helpers
include '../includes/database.php';
include_once '../includes/paths.php';

// Function to get page title based on current page
function getPageTitle($page) {
    $titles = [
        'dashboard' => 'Dashboard',
        'payroll' => 'Payroll',
        'expenses' => 'Expenses & Revenue',
        'deductions' => 'Deductions',
        'loans' => 'Loan Balances',
        'cash_advances' => 'Cash Advances',
        'tax_reports' => 'Tax Reports',
        'statements' => 'Financial Statements',
        'invoices' => 'Invoice Generation',
        'contributions' => 'Track Contributions',
        'settings' => 'Settings',
        'profile' => 'My Profile',
        'help' => 'Help & Support'
    ];
    
    return $titles[$page] ?? 'Dashboard';
}

// Function to get active section based on current page
function getActiveSection($page) {
    $sections = [
        'payroll' => 'payroll',
        'expenses' => 'financial',
        'deductions' => 'financial',
        'loans' => 'financial',
        'cash_advances' => 'financial',
        'tax_reports' => 'reports',
        'statements' => 'reports',
        'invoices' => 'reports',
        'contributions' => 'reports'
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
    <title><?php echo getPageTitle($page); ?> - Accounting - Golden Z-5 HR System</title>
    
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
            <small>Accounting Portal</small>
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
            
            <!-- Payroll Section -->
            <li class="nav-item">
                <a href="?page=payroll" 
                   class="nav-link <?php echo ($page === 'payroll') ? 'active' : ''; ?>"
                   data-page="payroll">
                    <i class="fas fa-money-check-alt" aria-hidden="true"></i>
                    <span>Payroll</span>
                </a>
            </li>
            
            <!-- Financial Section -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'financial') ? 'active' : ''; ?>" data-section="financial">
                <button class="nav-toggle <?php echo ($activeSection === 'financial') ? 'active' : ''; ?>" 
                        type="button"
                        role="button" 
                        aria-expanded="<?php echo ($activeSection === 'financial') ? 'true' : 'false'; ?>" 
                        aria-controls="financial-submenu"
                        tabindex="0"
                        data-target="financial-submenu">
                    <i class="fas fa-dollar-sign" aria-hidden="true"></i>
                    <span>Financial</span>
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'financial') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'financial') ? 'expanded' : ''; ?>" id="financial-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=expenses" 
                           class="nav-link <?php echo ($page === 'expenses') ? 'active' : ''; ?>"
                           data-page="expenses">
                            <i class="fas fa-chart-line" aria-hidden="true"></i>
                            <span>Expenses & Revenue</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=deductions" 
                           class="nav-link <?php echo ($page === 'deductions') ? 'active' : ''; ?>"
                           data-page="deductions">
                            <i class="fas fa-minus-circle" aria-hidden="true"></i>
                            <span>Deductions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=loans" 
                           class="nav-link <?php echo ($page === 'loans') ? 'active' : ''; ?>"
                           data-page="loans">
                            <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                            <span>Loan Balances</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=cash_advances" 
                           class="nav-link <?php echo ($page === 'cash_advances') ? 'active' : ''; ?>"
                           data-page="cash_advances">
                            <i class="fas fa-money-bill-wave" aria-hidden="true"></i>
                            <span>Cash Advances</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Reports Section -->
            <li class="nav-item nav-section <?php echo ($activeSection === 'reports') ? 'active' : ''; ?>" data-section="reports">
                <button class="nav-toggle <?php echo ($activeSection === 'reports') ? 'active' : ''; ?>" 
                        type="button"
                        role="button" 
                        aria-expanded="<?php echo ($activeSection === 'reports') ? 'true' : 'false'; ?>" 
                        aria-controls="reports-submenu"
                        tabindex="0"
                        data-target="reports-submenu">
                    <i class="fas fa-file-invoice" aria-hidden="true"></i>
                    <span>Reports</span>
                    <i class="fas fa-chevron-down nav-arrow <?php echo ($activeSection === 'reports') ? 'rotated' : ''; ?>" aria-hidden="true"></i>
                </button>
                <ul class="nav-submenu <?php echo ($activeSection === 'reports') ? 'expanded' : ''; ?>" id="reports-submenu" role="menu">
                    <li class="nav-item">
                        <a href="?page=tax_reports" 
                           class="nav-link <?php echo ($page === 'tax_reports') ? 'active' : ''; ?>"
                           data-page="tax_reports">
                            <i class="fas fa-file-alt" aria-hidden="true"></i>
                            <span>Tax Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=statements" 
                           class="nav-link <?php echo ($page === 'statements') ? 'active' : ''; ?>"
                           data-page="statements">
                            <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>
                            <span>Financial Statements</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=invoices" 
                           class="nav-link <?php echo ($page === 'invoices') ? 'active' : ''; ?>"
                           data-page="invoices">
                            <i class="fas fa-receipt" aria-hidden="true"></i>
                            <span>Invoice Generation</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?page=contributions" 
                           class="nav-link <?php echo ($page === 'contributions') ? 'active' : ''; ?>"
                           data-page="contributions">
                            <i class="fas fa-piggy-bank" aria-hidden="true"></i>
                            <span>Track Contributions</span>
                        </a>
                    </li>
                </ul>
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
                case 'payroll':
                case 'expenses':
                case 'deductions':
                case 'loans':
                case 'cash_advances':
                case 'tax_reports':
                case 'statements':
                case 'invoices':
                case 'contributions':
                    // Accounting specific pages - create these later
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

