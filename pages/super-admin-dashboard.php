<?php
$page_title = 'Super Admin Dashboard - Golden Z-5 HR System';
$page = 'dashboard';

// Get database connection
$pdo = get_db_connection();

// Get filters from request
$filters = [
    'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
    'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
    'role' => $_GET['role'] ?? null,
    'status' => $_GET['status'] ?? null,
];

// Get comprehensive statistics (optimized - minimal data loading)
$stats = get_super_admin_stats($filters);
$recent_audit_logs = get_recent_audit_logs(5); // Reduced from 10 to 5

// Skip security log parsing to save memory - calculate simple count instead
$security_log_file = __DIR__ . '/../storage/logs/security.log';
$security_event_count = 0;
if (file_exists($security_log_file)) {
    // Just count lines, don't parse content
    $lines = file($security_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $security_event_count = min(count($lines), 1000); // Cap at 1000 to prevent memory issues
}

// Time-based greeting
$hourNow = (int) date('G');
if ($hourNow < 12) {
    $greeting = 'Good morning';
} elseif ($hourNow < 18) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

// Prepare lightweight data (no chart data)
$employees_by_status = $stats['employees_by_status'] ?? [];
$users_by_role = $stats['users_by_role'] ?? [];
?>

<div class="container-fluid dashboard-modern">
    <!-- Welcome Header Section -->
    <div class="hrdash-welcome">
        <div class="hrdash-welcome__left">
            <h2 class="hrdash-welcome__title">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Super Administrator'); ?></h2>
            <p class="hrdash-welcome__subtitle">Ready to manage your system today?</p>
        </div>
        <div class="hrdash-welcome__actions">
            <span id="current-time" class="hrdash-welcome__time"><?php echo strtolower(date('h:i A')); ?></span>
            
            <!-- Messages Dropdown -->
            <?php
            // Get recent messages/alerts (last 5 active alerts)
            $recentMessages = [];
            if (function_exists('get_employee_alerts')) {
                try {
                    $recentMessages = get_employee_alerts('active', null);
                    $recentMessages = array_slice($recentMessages, 0, 5);
                } catch (Exception $e) {
                    $recentMessages = [];
                }
            }
            $messageCount = count($recentMessages);
            ?>
            <div class="dropdown">
                <button class="hrdash-welcome__icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Messages" aria-label="Messages">
                    <i class="fas fa-envelope"></i>
                    <?php if ($messageCount > 0): ?>
                        <span class="hrdash-welcome__badge"><?php echo $messageCount > 99 ? '99+' : $messageCount; ?></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end hrdash-notification-dropdown">
                    <li class="dropdown-header">
                        <strong>Messages</strong>
                        <a href="?page=alerts" class="text-decoration-none ms-auto">View All</a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if (empty($recentMessages)): ?>
                        <li class="dropdown-item-text text-muted text-center py-3">
                            <i class="far fa-envelope-open fa-2x mb-2 d-block"></i>
                            <small>No new messages</small>
                        </li>
                    <?php else: ?>
                        <?php foreach ($recentMessages as $msg): 
                            $priorityClass = '';
                            $priorityIcon = 'fa-info-circle';
                            switch(strtolower($msg['priority'] ?? '')) {
                                case 'urgent':
                                    $priorityClass = 'text-danger';
                                    $priorityIcon = 'fa-exclamation-triangle';
                                    break;
                                case 'high':
                                    $priorityClass = 'text-warning';
                                    $priorityIcon = 'fa-exclamation-circle';
                                    break;
                                default:
                                    $priorityClass = 'text-info';
                            }
                            $employeeName = trim(($msg['surname'] ?? '') . ', ' . ($msg['first_name'] ?? '') . ' ' . ($msg['middle_name'] ?? ''));
                            $timeAgo = '';
                            if (!empty($msg['created_at'])) {
                                $created = new DateTime($msg['created_at']);
                                $now = new DateTime();
                                $diff = $now->diff($created);
                                if ($diff->days > 0) {
                                    $timeAgo = $diff->days . 'd ago';
                                } elseif ($diff->h > 0) {
                                    $timeAgo = $diff->h . 'h ago';
                                } else {
                                    $timeAgo = $diff->i . 'm ago';
                                }
                            }
                        ?>
                            <li>
                                <a class="dropdown-item hrdash-notification-item" href="?page=alerts">
                                    <div class="d-flex align-items-start">
                                        <i class="fas <?php echo $priorityIcon; ?> <?php echo $priorityClass; ?> me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($msg['title'] ?? 'Alert'); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($employeeName); ?></div>
                                            <?php if ($timeAgo): ?>
                                                <div class="text-muted" style="font-size: 0.7rem;"><?php echo $timeAgo; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Notifications Dropdown -->
            <?php
            // Get recent notifications (pending tasks)
            $recentNotifications = [];
            $pendingTasks = 0;
            if (function_exists('get_all_tasks')) {
                try {
                    $recentNotifications = get_all_tasks('pending', null, null);
                    $recentNotifications = array_slice($recentNotifications, 0, 5);
                    $pendingTasks = count($recentNotifications);
                } catch (Exception $e) {
                    $recentNotifications = [];
                }
            }
            if (function_exists('get_pending_task_count')) {
                try {
                    $pendingTasks = (int) get_pending_task_count();
                } catch (Exception $e) {
                    $pendingTasks = 0;
                }
            }
            ?>
            <div class="dropdown">
                <button class="hrdash-welcome__icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($pendingTasks > 0): ?>
                        <span class="hrdash-welcome__badge"><?php echo $pendingTasks > 99 ? '99+' : $pendingTasks; ?></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end hrdash-notification-dropdown">
                    <li class="dropdown-header">
                        <strong>Notifications</strong>
                        <a href="?page=tasks" class="text-decoration-none ms-auto">View All</a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if (empty($recentNotifications)): ?>
                        <li class="dropdown-item-text text-muted text-center py-3">
                            <i class="far fa-bell-slash fa-2x mb-2 d-block"></i>
                            <small>No new notifications</small>
                        </li>
                    <?php else: ?>
                        <?php foreach ($recentNotifications as $notif): 
                            $priorityClass = '';
                            $priorityIcon = 'fa-circle';
                            switch(strtolower($notif['priority'] ?? '')) {
                                case 'urgent':
                                    $priorityClass = 'text-danger';
                                    $priorityIcon = 'fa-exclamation-triangle';
                                    break;
                                case 'high':
                                    $priorityClass = 'text-warning';
                                    $priorityIcon = 'fa-exclamation-circle';
                                    break;
                                case 'medium':
                                    $priorityClass = 'text-info';
                                    $priorityIcon = 'fa-info-circle';
                                    break;
                                default:
                                    $priorityClass = 'text-muted';
                            }
                            $timeAgo = '';
                            if (!empty($notif['created_at'])) {
                                $created = new DateTime($notif['created_at']);
                                $now = new DateTime();
                                $diff = $now->diff($created);
                                if ($diff->days > 0) {
                                    $timeAgo = $diff->days . 'd ago';
                                } elseif ($diff->h > 0) {
                                    $timeAgo = $diff->h . 'h ago';
                                } else {
                                    $timeAgo = $diff->i . 'm ago';
                                }
                            }
                        ?>
                            <li>
                                <a class="dropdown-item hrdash-notification-item" href="?page=tasks">
                                    <div class="d-flex align-items-start">
                                        <i class="fas <?php echo $priorityIcon; ?> <?php echo $priorityClass; ?> me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($notif['title'] ?? 'Task'); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($notif['category'] ?? 'Task'); ?></div>
                                            <?php if ($timeAgo): ?>
                                                <div class="text-muted" style="font-size: 0.7rem;"><?php echo $timeAgo; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="dropdown">
                <button class="hrdash-welcome__profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Profile menu">
                    <?php
                    $displayName = trim((string)($_SESSION['name'] ?? ($_SESSION['username'] ?? 'Super Admin')));
                    $initials = 'SA';
                    if ($displayName) {
                        $parts = preg_split('/\s+/', $displayName);
                        $first = $parts[0][0] ?? 'S';
                        $last = (count($parts) > 1) ? ($parts[count($parts) - 1][0] ?? 'A') : ($parts[0][1] ?? 'A');
                        $initials = strtoupper($first . $last);
                    }
                    ?>
                    <span class="hrdash-welcome__avatar"><?php echo htmlspecialchars($initials); ?></span>
                    <i class="fas fa-chevron-down hrdash-welcome__chevron"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileSettingsModal" data-tab="profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="?page=settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
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

    <!-- Page Header -->
    <div class="page-header-modern mb-5">
        <div class="page-title-modern">
            <h1 class="page-title-main">Super Admin Dashboard</h1>
            <p class="page-subtitle"><?php echo $greeting; ?>! Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Super Admin'); ?>!</p>
        </div>
        <div class="page-actions-modern">
            <button class="btn btn-outline-modern" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false">
                <i class="fas fa-filter me-2"></i>Filters
            </button>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="collapse mb-4" id="filtersCollapse">
        <div class="card card-modern">
            <div class="card-body-modern">
                <form method="GET" action="" class="row g-3">
                    <input type="hidden" name="page" value="dashboard">
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">User Role</label>
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="super_admin" <?php echo $filters['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                            <option value="hr_admin" <?php echo $filters['role'] === 'hr_admin' ? 'selected' : ''; ?>>HR Admin</option>
                            <option value="hr" <?php echo $filters['role'] === 'hr' ? 'selected' : ''; ?>>HR Staff</option>
                            <option value="admin" <?php echo $filters['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="developer" <?php echo $filters['role'] === 'developer' ? 'selected' : ''; ?>>Developer</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $filters['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary-modern">
                            <i class="fas fa-search me-2"></i>Apply Filters
                        </button>
                        <a href="?page=dashboard" class="btn btn-outline-modern ms-2">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Top Statistics Cards -->
    <div class="row g-4 mb-5">
        <!-- Total Users -->
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card-modern h-100">
                <div class="card-body-modern">
                    <div class="stat-header">
                        <span class="stat-label">Total Users</span>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo number_format($stats['total_users'] ?? 0); ?></h3>
                        <span class="badge badge-primary-modern"><?php echo number_format($stats['active_users'] ?? 0); ?> Active</span>
                    </div>
                    <small class="stat-footer"><?php echo number_format($stats['users_logged_in_today'] ?? 0); ?> logged in today</small>
                </div>
            </div>
        </div>

        <!-- Total Employees -->
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card-modern h-100">
                <div class="card-body-modern">
                    <div class="stat-header">
                        <span class="stat-label">Total Employees</span>
                        <i class="fas fa-user-tie stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo number_format($stats['total_employees'] ?? 0); ?></h3>
                        <span class="badge badge-success-modern"><?php echo number_format($stats['active_employees'] ?? 0); ?> Active</span>
                    </div>
                    <small class="stat-footer"><?php echo number_format($stats['new_employees'] ?? 0); ?> new in period</small>
                </div>
            </div>
        </div>

        <!-- Audit Logs -->
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card-modern h-100">
                <div class="card-body-modern">
                    <div class="stat-header">
                        <span class="stat-label">Audit Logs</span>
                        <i class="fas fa-history stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo number_format($stats['total_audit_logs'] ?? 0); ?></h3>
                        <span class="badge badge-primary-modern"><?php echo number_format($stats['active_users_period'] ?? 0); ?> active users</span>
                    </div>
                    <small class="stat-footer">In selected period</small>
                </div>
            </div>
        </div>

        <!-- Security Events -->
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card-modern h-100">
                <div class="card-body-modern">
                    <div class="stat-header">
                        <span class="stat-label">Security Events</span>
                        <i class="fas fa-shield-alt stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo number_format($security_event_count); ?></h3>
                        <span class="badge badge-warning-modern">Last 7 days</span>
                    </div>
                    <small class="stat-footer">Security log entries</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- Users by Role -->
        <div class="col-xl-6">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-4">
                        <div>
                            <h5 class="card-title-modern">Users by Role</h5>
                            <small class="card-subtitle">System user distribution</small>
                        </div>
                    </div>
                    <div class="role-breakdown-list">
                        <?php 
                        $role_colors = [
                            'super_admin' => 'danger',
                            'hr_admin' => 'primary',
                            'hr' => 'info',
                            'admin' => 'success',
                            'developer' => 'warning',
                            'accounting' => 'secondary',
                            'operation' => 'primary'
                        ];
                        $total_users = array_sum($users_by_role);
                        foreach ($users_by_role as $role => $count): 
                            $percentage = $total_users > 0 ? round(($count / $total_users) * 100, 1) : 0;
                            $color = $role_colors[$role] ?? 'primary';
                        ?>
                            <div class="role-breakdown-item">
                                <div class="role-breakdown-header">
                                    <div class="role-breakdown-label">
                                        <span class="role-badge role-badge-<?php echo $color; ?>"></span>
                                        <span class="role-name"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role))); ?></span>
                                    </div>
                                    <div class="role-breakdown-value">
                                        <strong><?php echo number_format($count); ?></strong>
                                        <small class="text-muted ms-2">(<?php echo $percentage; ?>%)</small>
                                    </div>
                                </div>
                                <div class="role-breakdown-progress">
                                    <div class="progress progress-modern" style="height: 8px;">
                                        <div class="progress-bar progress-bar-<?php echo $color; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%;"
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($users_by_role)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-2"></i>No user role data available
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employees by Status -->
        <div class="col-xl-6">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-4">
                        <div>
                            <h5 class="card-title-modern">Employees by Status</h5>
                            <small class="card-subtitle">Workforce status breakdown</small>
                        </div>
                    </div>
                    <div class="status-breakdown-list">
                        <?php 
                        $status_colors = [
                            'Active' => 'success',
                            'Inactive' => 'secondary',
                            'Terminated' => 'danger',
                            'Suspended' => 'warning'
                        ];
                        $total_emp = array_sum($employees_by_status);
                        foreach ($employees_by_status as $status => $count): 
                            $percentage = $total_emp > 0 ? round(($count / $total_emp) * 100, 1) : 0;
                            $color = $status_colors[$status] ?? 'primary';
                        ?>
                            <div class="status-breakdown-item">
                                <div class="status-breakdown-header">
                                    <div class="status-breakdown-label">
                                        <span class="status-badge status-badge-<?php echo $color; ?>"></span>
                                        <span class="status-name"><?php echo htmlspecialchars($status); ?></span>
                                    </div>
                                    <div class="status-breakdown-value">
                                        <strong><?php echo number_format($count); ?></strong>
                                        <small class="text-muted ms-2">(<?php echo $percentage; ?>%)</small>
                                    </div>
                                </div>
                                <div class="status-breakdown-progress">
                                    <div class="progress progress-modern" style="height: 8px;">
                                        <div class="progress-bar progress-bar-<?php echo $color; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%;"
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($employees_by_status)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-2"></i>No employee status data available
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- Quick Stats -->
        <div class="col-xl-12">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-4">
                        <div>
                            <h5 class="card-title-modern">Quick Stats</h5>
                            <small class="card-subtitle">System overview</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="quick-stat-item">
                                <div class="quick-stat-label">
                                    <i class="fas fa-map-marker-alt text-primary"></i>
                                    <span>Total Posts</span>
                                </div>
                                <div class="quick-stat-value"><?php echo number_format($stats['total_posts'] ?? 0); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="quick-stat-item">
                                <div class="quick-stat-label">
                                    <i class="fas fa-bell text-warning"></i>
                                    <span>Active Alerts</span>
                                </div>
                                <div class="quick-stat-value"><?php echo number_format($stats['active_alerts'] ?? 0); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="quick-stat-item">
                                <div class="quick-stat-label">
                                    <i class="fas fa-id-card text-danger"></i>
                                    <span>Expired Licenses</span>
                                </div>
                                <div class="quick-stat-value"><?php echo number_format($stats['expired_licenses'] ?? 0); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="quick-stat-item">
                                <div class="quick-stat-label">
                                    <i class="fas fa-clock text-warning"></i>
                                    <span>Expiring Licenses</span>
                                </div>
                                <div class="quick-stat-value"><?php echo number_format($stats['expiring_licenses'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Audit Logs -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card card-modern">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-4">
                        <div>
                            <h5 class="card-title-modern">Recent Audit Logs</h5>
                            <small class="card-subtitle">Latest system activity (last 5 entries)</small>
                        </div>
                        <a href="?page=audit_trail" class="btn btn-link-modern">
                            <i class="fas fa-external-link-alt me-2"></i>View All
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_audit_logs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">No audit logs found</h5>
                                                <p class="text-muted mb-0">No recent activity recorded.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_audit_logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($log['role'] ?? 'N/A'); ?>)</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary-modern audit-action-badge"><?php echo htmlspecialchars($log['action']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                                            <td><?php echo $log['record_id'] ? '#' . $log['record_id'] : 'N/A'; ?></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update current time display
    function initTimeDisplay() {
        const timeEl = document.getElementById('current-time');
        if (timeEl) {
            function updateTime() {
                const now = new Date();
                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const ampm = hours >= 12 ? 'pm' : 'am';
                hours = hours % 12;
                hours = hours ? hours : 12; // the hour '0' should be '12'
                hours = String(hours).padStart(2, '0');
                timeEl.textContent = `${hours}:${minutes} ${ampm}`;
            }
            updateTime(); // Set initial time
            setInterval(updateTime, 60000); // Update every minute
        }
    }
    
    initTimeDisplay();
    
    // Ensure dropdowns are properly positioned
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(function(dropdown) {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        if (toggle && menu) {
            toggle.addEventListener('shown.bs.dropdown', function() {
                // Ensure dropdown is properly aligned
                menu.style.display = 'block';
            });
        }
    });
});
</script>
