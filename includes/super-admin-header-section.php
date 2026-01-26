<?php
/**
 * Super Admin Header Section - Reusable component
 * Includes welcome message, notifications, messages, and profile dropdown
 */
if (($_SESSION['user_role'] ?? '') !== 'super_admin') {
    return; // Only show for super admin
}

// Get current user avatar and data
$current_user_avatar = null;
$current_user_data = null;
if (!empty($_SESSION['user_id']) && function_exists('get_user_by_id')) {
    // Include database if not already included
    if (!function_exists('get_db_connection')) {
        require_once __DIR__ . '/database.php';
    }
    // Include paths helper for avatar URL resolution
    if (!function_exists('get_avatar_url')) {
        require_once __DIR__ . '/paths.php';
    }
    $current_user_data = get_user_by_id($_SESSION['user_id']);
    if (!empty($current_user_data['avatar'])) {
        $current_user_avatar = get_avatar_url($current_user_data['avatar']);
    }
}

// Get page title for dynamic header
$current_page = $_GET['page'] ?? 'dashboard';
$page_titles = [
    'dashboard' => 'Super Admin Dashboard',
    'employees' => 'Employee Management',
    'teams' => 'Teams',
    'posts' => 'Posts & Locations',
    'post_assignments' => 'Post Assignments',
    'users' => 'User Management',
    'system_logs' => 'System Logs',
    'audit_trail' => 'Audit Trail',
    'settings' => 'System Settings',
    'profile' => 'My Profile',
    'alerts' => 'Employee Alerts',
    'help' => 'Help & Support',
];
$page_title = $page_titles[$current_page] ?? 'Super Admin Portal';
$page_subtitle = $current_page === 'dashboard' 
    ? 'Ready to manage your system today?' 
    : 'Manage and monitor system operations';
?>
<div class="hrdash-welcome">
    <div class="hrdash-welcome__left">
        <h2 class="hrdash-welcome__title"><?php echo htmlspecialchars($page_title); ?></h2>
        <p class="hrdash-welcome__subtitle"><?php echo htmlspecialchars($page_subtitle); ?></p>
    </div>
    <div class="hrdash-welcome__actions">
        <span id="current-time-super-admin" class="hrdash-welcome__time"><?php echo strtolower(date('g:i A')); ?></span>
        <?php
        // Get name from first_name and last_name if available, otherwise use session name
        $displayName = trim((string)($_SESSION['name'] ?? ($_SESSION['username'] ?? 'Super Admin')));
        $headerFirstName = '';
        $headerLastName = '';
        if (!empty($current_user_data)) {
            $headerFirstName = $current_user_data['first_name'] ?? '';
            $headerLastName = $current_user_data['last_name'] ?? '';
            if (!empty($headerFirstName) || !empty($headerLastName)) {
                $displayName = trim($headerFirstName . ' ' . $headerLastName);
            }
        }
        
        // Format name as "FirstName, LastName" for header display
        $headerDisplayName = '';
        if (!empty($headerFirstName) && !empty($headerLastName)) {
            $headerDisplayName = $headerFirstName . ', ' . $headerLastName;
        } elseif (!empty($headerFirstName)) {
            $headerDisplayName = $headerFirstName;
        } elseif (!empty($headerLastName)) {
            $headerDisplayName = $headerLastName;
        } else {
            $headerDisplayName = $displayName;
        }
        
        $initials = 'SA';
        if ($displayName) {
            $parts = preg_split('/\s+/', $displayName);
            $first = $parts[0][0] ?? 'S';
            $last = (count($parts) > 1) ? ($parts[count($parts) - 1][0] ?? 'A') : ($parts[0][1] ?? 'A');
            $initials = strtoupper($first . $last);
        }
        ?>
        <div class="dropdown">
            <button class="hrdash-welcome__profile-btn dropdown-toggle d-inline-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Profile menu">
                <?php if ($current_user_avatar): ?>
                    <img src="<?php echo htmlspecialchars($current_user_avatar); ?>" 
                         alt="<?php echo htmlspecialchars($displayName); ?>" 
                         class="hrdash-welcome__avatar hrdash-welcome__avatar-img">
                <?php else: ?>
                    <span class="hrdash-welcome__avatar"><?php echo htmlspecialchars($initials); ?></span>
                <?php endif; ?>
                <i class="fas fa-chevron-down hrdash-welcome__chevron"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="dropdown-header d-flex align-items-center gap-2 px-3 py-2">
                    <?php if ($current_user_avatar): ?>
                        <img src="<?php echo htmlspecialchars($current_user_avatar); ?>" 
                             alt="<?php echo htmlspecialchars($displayName); ?>" 
                             class="hrdash-welcome__avatar hrdash-welcome__avatar-img" 
                             style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <span class="hrdash-welcome__avatar" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;"><?php echo htmlspecialchars($initials); ?></span>
                    <?php endif; ?>
                    <div class="d-flex flex-column">
                        <strong style="font-size: 0.875rem; color: #1e293b;"><?php echo htmlspecialchars($displayName); ?></strong>
                        <small style="font-size: 0.75rem; color: #64748b;">Super Administrator</small>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="?page=profile&from=header"><i class="fas fa-user me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="?page=settings&from=header"><i class="fas fa-cog me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?php echo base_url(); ?>/super-admin/index.php?logout=1" data-no-transition="true">
                        <i class="fas fa-right-from-bracket me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
// Update time display every minute
(function() {
    function updateTime() {
        const timeElement = document.getElementById('current-time-super-admin');
        if (timeElement) {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHours = hours % 12 || 12;
            const displayMinutes = minutes < 10 ? '0' + minutes : minutes;
            timeElement.textContent = displayHours + ':' + displayMinutes + ' ' + ampm.toLowerCase();
        }
    }
    
    // Update immediately
    updateTime();
    
    // Update every minute
    setInterval(updateTime, 60000);
})();
</script>
