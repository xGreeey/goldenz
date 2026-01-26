<?php
/**
 * Page Header Component
 * Permanent header for all system pages - like the sidebar
 * Displays page title, time, notifications, messages, and user profile
 */

// Get current page information
$page = $_GET['page'] ?? 'dashboard';
$userRole = $_SESSION['user_role'] ?? '';

// Get page title
$pageTitle = getPageTitle($page);

// Get page subtitle based on page
$pageSubtitles = [
    'dashboard' => 'Overview of your HR management system',
    'employees' => 'Manage employee information and records',
    'posts' => 'Manage posts, locations, and assignments',
    'feed' => 'Announcements and status updates',
    'post_assignments' => 'Assign employees to specific posts',
    'alerts' => 'View and manage employee alerts',
    'tasks' => 'Manage your tasks and assignments',
    'settings' => 'Configure system settings and preferences',
    'profile' => 'View and edit your profile information',
    'system_logs' => 'View system activity and audit logs',
    'users' => 'Manage system users and permissions',
    'teams' => 'Manage teams and departments',
    'add_employee' => 'Add a new employee to the system',
    'edit_employee' => 'Edit employee information',
    'view_employee' => 'View employee details',
    'add_post' => 'Create a new post location',
    'edit_post' => 'Edit post information',
    'add_alert' => 'Create a new employee alert',
    'help' => 'Get help and support for the HR system',
    'integrations' => 'Manage third-party integrations',
    'dtr' => 'Track daily time and attendance records',
    'timeoff' => 'Manage time off requests and approvals',
    'checklist' => 'View and manage employee checklists',
    'hiring' => 'Manage the recruitment and hiring process',
    'onboarding' => 'Manage employee onboarding procedures',
    'handbook' => 'Access the employee handbook and policies',
    'documents' => 'Manage employee 201 files and documents',
    'leaves' => 'View and manage leave requests',
    'leave_balance' => 'View employee leave balances',
    'leave_reports' => 'Generate leave reports and analytics',
    'attendance' => 'Track and manage employee attendance',
    'violations' => 'View and manage employee violations',
    'violation_types' => 'Manage violation categories and sanctions',
    'violation_history' => 'Complete history of all violation type changes',
    'chat' => 'Secure one-to-one communication with team members',
];

$pageSubtitle = $pageSubtitles[$page] ?? 'Manage your HR operations';

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

// Only show header for HR Admin and Super Admin portals
if ($userRole === 'hr_admin' || $userRole === 'super_admin' || $userRole === 'developer'):
?>
<div class="hrdash-welcome">
    <div class="hrdash-welcome__left">
        <h2 class="hrdash-welcome__title"><?php echo htmlspecialchars($pageTitle); ?></h2>
        <p class="hrdash-welcome__subtitle"><?php echo htmlspecialchars($pageSubtitle); ?></p>
    </div>
    <div class="hrdash-welcome__actions">
        <span id="current-time-global" class="hrdash-welcome__time"><?php echo strtoupper(date('g:i A')); ?></span>

        <!-- Profile Dropdown -->
        <div class="dropdown">
            <button class="hrdash-welcome__profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Profile menu">
                <?php
                $displayName = trim((string)($_SESSION['name'] ?? ($_SESSION['username'] ?? 'User')));
                $initials = 'U';
                if ($displayName) {
                    $parts = preg_split('/\s+/', $displayName);
                    $first = $parts[0][0] ?? 'U';
                    $last = (count($parts) > 1) ? ($parts[count($parts) - 1][0] ?? 'A') : ($parts[0][1] ?? 'S');
                    $initials = strtoupper($first . $last);
                }
                
                // Get role display name
                $roleDisplayName = 'User';
                if ($userRole === 'hr_admin') {
                    $roleDisplayName = 'HR Administrator';
                } elseif ($userRole === 'super_admin') {
                    $roleDisplayName = 'Super Administrator';
                } elseif ($userRole === 'developer') {
                    $roleDisplayName = 'Developer';
                }
                ?>
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
                        <small style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($roleDisplayName); ?></small>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="?page=profile&from=header"><i class="fas fa-user me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="?page=settings&from=header"><i class="fas fa-cog me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?php echo base_url(); ?>/index.php?logout=1" data-no-transition="true">
                        <i class="fas fa-right-from-bracket me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Dynamic time update script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeElement = document.getElementById('current-time-global');
    if (timeElement) {
        setInterval(function() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            timeElement.textContent = hours + ':' + minutes + ' ' + ampm.toUpperCase();
        }, 1000);
    }
});
</script>
<?php endif; ?>
