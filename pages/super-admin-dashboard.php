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

<div class="container-fluid dashboard-modern super-admin-dashboard">
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

<style>
/* Super Admin Dashboard Specific Styles */
.super-admin-dashboard {
    padding: 2rem 2.5rem;
    max-width: 100%;
    overflow-x: hidden;
    background: #f8fafc;
    min-height: 100vh;
}

/* Ensure cards match HR admin style */
.super-admin-dashboard .card-modern,
.super-admin-dashboard .stat-card-modern {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    background: #ffffff;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.super-admin-dashboard .card-modern:hover,
.super-admin-dashboard .stat-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
}

.super-admin-dashboard .card-body-modern {
    padding: 1.5rem;
}

.super-admin-dashboard .card-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.super-admin-dashboard .card-title-modern {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

.super-admin-dashboard .card-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

/* Audit log action badge - black text */
.super-admin-dashboard .audit-action-badge {
    color: #1e293b !important;
    background: #dbeafe !important;
    border: 1px solid #bfdbfe;
}

.role-breakdown-list,
.status-breakdown-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.role-breakdown-item,
.status-breakdown-item {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.role-breakdown-header,
.status-breakdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.role-breakdown-label,
.status-breakdown-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
}

.role-badge,
.status-badge {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}

.role-badge-danger,
.status-badge-danger {
    background: #ef4444;
}

.role-badge-primary,
.status-badge-primary {
    background: #1fb2d5;
}

.role-badge-info {
    background: #06b6d4;
}

.role-badge-success,
.status-badge-success {
    background: #22c55e;
}

.role-badge-warning,
.status-badge-warning {
    background: #f59e0b;
}

.role-badge-secondary,
.status-badge-secondary {
    background: #64748b;
}

.role-name,
.status-name {
    font-weight: 500;
}

.role-breakdown-value,
.status-breakdown-value {
    font-size: 0.875rem;
    color: #1e293b;
}

.role-breakdown-value strong,
.status-breakdown-value strong {
    font-size: 1rem;
    font-weight: 700;
}

.role-breakdown-progress,
.status-breakdown-progress {
    width: 100%;
}

.progress-bar-success {
    background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
}

.progress-bar-secondary {
    background: linear-gradient(90deg, #64748b 0%, #475569 100%);
}

.progress-bar-danger {
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.progress-bar-warning {
    background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.progress-bar-primary {
    background: linear-gradient(90deg, #1fb2d5 0%, #0ea5e9 100%);
}

.progress-bar-info {
    background: linear-gradient(90deg, #06b6d4 0%, #0891b2 100%);
}

.quick-stats-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.quick-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.quick-stat-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateX(2px);
}

.quick-stat-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

.quick-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    /* Number rendering fix - ensures digits display correctly on Windows 10/11 */
    font-family: 'Segoe UI', Arial, Helvetica, sans-serif !important;
    font-variant-numeric: tabular-nums !important;
    font-feature-settings: 'tnum' !important;
    -webkit-font-feature-settings: 'tnum' !important;
    -moz-font-feature-settings: 'tnum' !important;
    text-rendering: optimizeLegibility !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
}

.table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
    color: #64748b;
    padding: 1rem;
}

.table td {
    padding: 1rem;
    font-size: 0.875rem;
    color: #475569;
    vertical-align: middle;
}

/* Dark theme support for Super Admin Dashboard */
html[data-theme="dark"] .super-admin-dashboard {
    background: var(--interface-bg) !important;
}

html[data-theme="dark"] .super-admin-dashboard .card-modern,
html[data-theme="dark"] .super-admin-dashboard .stat-card-modern {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-dashboard .card-header-modern {
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .super-admin-dashboard .card-title-modern {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-dashboard .card-subtitle {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .super-admin-dashboard .audit-action-badge {
    color: var(--interface-text) !important;
    background: #1e3a5f !important;
    border-color: #2563eb !important;
}

html[data-theme="dark"] .super-admin-dashboard .role-breakdown-label,
html[data-theme="dark"] .super-admin-dashboard .status-breakdown-label {
    color: var(--interface-text-light) !important;
}

html[data-theme="dark"] .super-admin-dashboard .role-breakdown-value,
html[data-theme="dark"] .super-admin-dashboard .status-breakdown-value {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-dashboard .quick-stat-item {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .super-admin-dashboard .quick-stat-item:hover {
    background: #1e293b !important;
    border-color: var(--interface-border-light) !important;
}

html[data-theme="dark"] .super-admin-dashboard .quick-stat-label {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .super-admin-dashboard .quick-stat-value {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-dashboard .table thead {
    background: #1e293b !important;
}

html[data-theme="dark"] .super-admin-dashboard .table thead.table-light {
    background: #1e293b !important;
}

html[data-theme="dark"] .super-admin-dashboard .table th {
    background: #1e293b !important;
    color: var(--interface-text-muted) !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .super-admin-dashboard .table tbody tr {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .super-admin-dashboard .table tbody tr:hover {
    background: #1e293b !important;
}

html[data-theme="dark"] .super-admin-dashboard .table td {
    background: transparent !important;
    color: var(--interface-text-light) !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .super-admin-dashboard .btn-link-modern {
    color: var(--interface-text-light) !important;
}

html[data-theme="dark"] .super-admin-dashboard .btn-link-modern:hover {
    color: var(--interface-text) !important;
    background: #1e293b !important;
}

html[data-theme="dark"] .super-admin-dashboard .page-header-modern {
    background-color: #1a1d23 !important;
    border: 1px solid var(--interface-border) !important;
    border-radius: 14px; /* Rounded rectangle */
    padding: 1.5rem 2rem; /* Adjusted padding */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04); /* Added shadow */
    color: var(--interface-text) !important;
}

@media (max-width: 768px) {
    .super-admin-dashboard {
        padding: 1.5rem 1rem;
    }
    
    .stat-number {
        font-size: 1.75rem;
    }
}
</style>
