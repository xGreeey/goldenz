<?php
// Shared sidebar for all portals
// Expects $page (current page) and optional $activeSection

include_once __DIR__ . '/paths.php';

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
        'title' => 'Post Assignments',
        'page' => 'post_assignments',
        'section' => null,
    ],
    [
        'title' => 'Alerts',
        'page' => 'alerts',
        'section' => null,
    ],
    [
        'title' => 'Permissions',
        'page' => 'permissions',
        'section' => null,
        'icon' => 'fa-shield-alt',
    ],
];
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
                           class="nav-link <?php echo ($page === $child['page']) ? 'active' : ''; ?>"
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
                       class="nav-link <?php echo ($page === $item['page']) ? 'active' : ''; ?>"
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
    <ul class="sidebar-menu sidebar-bottom" role="menubar">
        <li class="nav-item">
            <a href="?page=tasks"
               class="nav-link <?php echo ($page === 'tasks') ? 'active' : ''; ?>"
               data-page="tasks">
                <i class="fas fa-inbox me-2"></i>
                <span>Task</span>
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
            <a href="<?php echo base_url(); ?>/index.php?logout=1"
               class="nav-link"
               data-no-transition="true">
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Mobile Overlay -->
<div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

