<?php
// Shared sidebar for all portals
// Expects $page (current page) and optional $activeSection

include_once __DIR__ . '/paths.php';

// Get user role
$user_role = $_SESSION['user_role'] ?? '';

// Check if navigation was triggered from header dropdown
$fromHeader = isset($_GET['from']) && $_GET['from'] === 'header';

// Developer-specific menu (technical monitoring only)
if ($user_role === 'developer') {
    $menu = [
        [
            'title' => 'System Logs',
            'page' => 'system_logs',
            'section' => null,
            'icon' => 'fa-file-lines',
        ],
        [
            'title' => 'Profile',
            'page' => 'profile',
            'section' => null,
            'icon' => 'fa-user',
        ],
    ];
} else {
    // Default HR/Admin menu
    $menu = [
        [
            'title' => 'Dashboard',
            'page' => 'dashboard',
            'section' => null,
        ],
        [
            'title' => 'Employees',
            'page' => 'employees',
            'section' => null,
        ],
        [
            'title' => 'Posts',
            'page' => 'posts',
            'section' => null,
        ],
        [
            'title' => 'Alerts',
            'page' => 'alerts',
            'section' => null,
        ],
        [
            'title' => 'Documents',
            'page' => 'documents',
            'section' => null,
        ],
        [
            'title' => 'Leaves',
            'page' => 'leaves',
            'section' => 'leaves',
            'children' => [
                [
                    'title' => 'Requests Inbox',
                    'page' => 'leaves',
                ],
                [
                    'title' => 'Leave Balance',
                    'page' => 'leave_balance',
                ],
                [
                    'title' => 'Leave Reports',
                    'page' => 'leave_reports',
                ],
            ],
        ],
        [
            'title' => 'Attendance',
            'page' => 'attendance',
            'section' => 'attendance',
            'children' => [
                [
                    'title' => 'Daily Attendance',
                    'page' => 'attendance',
                ],
                [
                    'title' => 'Daily Time Record (DTR)',
                    'page' => 'dtr',
                ],
            ],
        ],
        [
            'title' => 'Violations',
            'page' => 'violations',
            'section' => 'violations',
            'children' => [
                [
                    'title' => 'Violations List',
                    'page' => 'violations',
                ],
                [
                    'title' => 'Violation Types',
                    'page' => 'violation_types',
                ],
            ],
        ],
    ];
}
?>

<nav class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sidebar-header">
        <div class="logo-container mb-3">
            <img src="<?php echo public_url('logo.svg'); ?>" alt="Golden Z-5 Logo" class="logo-img" style="max-width: 150px; height: auto;">
        </div>
        <button class="sidebar-toggle d-md-none" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
            <span class="toggle-text">Menu</span>
        </button>
    </div>

    <ul class="sidebar-menu" id="sidebarMenu">
        <?php foreach ($menu as $item): ?>
            <?php if (!empty($item['children'])): ?>
                <?php $sectionActive = ($activeSection ?? null) === $item['section']; ?>
                <li class="nav-item nav-section <?php echo $sectionActive ? 'active' : ''; ?>" data-section="<?php echo htmlspecialchars($item['section']); ?>">
                    <button class="nav-toggle <?php echo $sectionActive ? 'active' : ''; ?>"
                            type="button"
                            role="button"
                            aria-expanded="<?php echo $sectionActive ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo htmlspecialchars($item['section']); ?>-submenu"
                            tabindex="0"
                            data-target="<?php echo htmlspecialchars($item['section']); ?>-submenu">
                        <span><?php echo htmlspecialchars($item['title']); ?></span>
                        <span class="nav-arrow <?php echo $sectionActive ? 'rotated' : ''; ?>" aria-hidden="true">▼</span>
                    </button>
                    <ul class="nav-submenu <?php echo $sectionActive ? 'expanded' : ''; ?>" id="<?php echo htmlspecialchars($item['section']); ?>-submenu" role="menu">
                        <?php foreach ($item['children'] as $child): ?>
                            <li class="nav-item">
                        <a href="?page=<?php echo urlencode($child['page']); ?>"
                           class="nav-link <?php echo (($page === $child['page']) && !$fromHeader) ? 'active' : ''; ?>"
                           data-page="<?php echo htmlspecialchars($child['page']); ?>">
                        <span><?php echo htmlspecialchars($child['title']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a href="?page=<?php echo urlencode($item['page']); ?>"
                       class="nav-link <?php echo (($page === $item['page']) && !$fromHeader) ? 'active' : ''; ?>"
                       data-page="<?php echo htmlspecialchars($item['page']); ?>">
                        <?php if (!empty($item['icon'])): ?>
                            <i class="fas <?php echo htmlspecialchars($item['icon']); ?> me-2"></i>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($item['title']); ?></span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <!-- Bottom Navigation Section -->
    <!-- HR-Admin: moved to header as icon actions -->
    <?php if ($user_role === 'developer'): ?>
    <!-- Developer bottom menu -->
    <ul class="sidebar-menu sidebar-bottom" role="menubar">
        <li class="nav-item">
            <a href="<?php echo base_url(); ?>/index.php?logout=1"
               class="nav-link"
               data-no-transition="true">
                <i class="fas fa-right-from-bracket me-2"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
    <?php elseif ($user_role !== 'hr_admin'): ?>
    <!-- Other roles bottom menu -->
    <ul class="sidebar-menu sidebar-bottom" role="menubar">
        <li class="nav-item">
            <a href="?page=tasks"
               class="nav-link <?php echo ($page === 'tasks') ? 'active' : ''; ?>"
               data-page="tasks">
                <i class="fas fa-tasks me-2"></i>
                <span>Tasks</span>
                <?php
                // Get pending task count
                if (function_exists('get_pending_task_count')) {
                    $pending_count = get_pending_task_count();
                    if ($pending_count > 0) {
                        echo '<span class="badge bg-danger ms-2">' . $pending_count . '</span>';
                    }
                }
                ?>
            </a>
        </li>
        <li class="nav-item">
            <a href="?page=help"
               class="nav-link <?php echo (($page === 'help') && !$fromHeader) ? 'active' : ''; ?>"
               data-page="help">
                <i class="fas fa-headset me-2"></i>
                <span>Help & Support</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo base_url(); ?>/index.php?logout=1"
               class="nav-link"
               data-no-transition="true">
                <i class="fas fa-right-from-bracket me-2"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>
</nav>

<!-- Mobile Overlay -->
<div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

