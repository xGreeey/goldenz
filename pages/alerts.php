<?php
$page_title = 'Employee Alerts - Golden Z-5 HR System';
$page = 'alerts';

// Handle alert actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $alert_id = $_POST['alert_id'] ?? '';
    
    if ($action === 'acknowledge' && $alert_id) {
        $alert = get_alert($alert_id);
        acknowledge_alert($alert_id, $_SESSION['user_id'] ?? 1);
        log_security_event('Alert Acknowledged', "Alert ID: $alert_id");
        if (function_exists('log_audit_event')) {
            log_audit_event('ACKNOWLEDGE', 'employee_alerts', $alert_id, 
                ['status' => $alert['status'] ?? 'active'], 
                ['status' => 'acknowledged']);
        }
        redirect_with_message('?page=alerts', 'Alert acknowledged successfully!', 'success');
    } elseif ($action === 'resolve' && $alert_id) {
        $alert = get_alert($alert_id);
        resolve_alert($alert_id, $_SESSION['user_id'] ?? 1);
        log_security_event('Alert Resolved', "Alert ID: $alert_id");
        if (function_exists('log_audit_event')) {
            log_audit_event('RESOLVE', 'employee_alerts', $alert_id, 
                ['status' => $alert['status'] ?? 'active'], 
                ['status' => 'resolved']);
        }
        redirect_with_message('?page=alerts', 'Alert resolved successfully!', 'success');
    } elseif ($action === 'dismiss' && $alert_id) {
        $alert = get_alert($alert_id);
        dismiss_alert($alert_id);
        log_security_event('Alert Dismissed', "Alert ID: $alert_id");
        if (function_exists('log_audit_event')) {
            log_audit_event('DISMISS', 'employee_alerts', $alert_id, 
                ['status' => $alert['status'] ?? 'active'], 
                ['status' => 'dismissed']);
        }
        redirect_with_message('?page=alerts', 'Alert dismissed successfully!', 'info');
    }
}

// Handle GET actions (like generate license alerts)
if (isset($_GET['action']) && $_GET['action'] === 'generate_license_alerts') {
    $alerts_created = generate_license_expiry_alerts();
    log_security_event('License Alerts Generated', "Created $alerts_created alerts");
    if (function_exists('log_audit_event')) {
        log_audit_event('GENERATE_LICENSE_ALERTS', 'employee_alerts', null, null, 
            ['alerts_created' => $alerts_created]);
    }
    redirect_with_message('?page=alerts', "Successfully generated $alerts_created license expiry alerts!", 'success');
}

// Handle AJAX request for alert details
if (isset($_GET['action']) && $_GET['action'] === 'get_alert' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $alert = get_alert($_GET['id']);
    if ($alert) {
        echo json_encode(['success' => true, 'alert' => $alert]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Alert not found']);
    }
    exit;
}

// Get filter parameters
$status = $_GET['status'] ?? 'active';
$priority = $_GET['priority'] ?? '';
$alert_type = $_GET['alert_type'] ?? '';

// Get audit trail filter parameters
$audit_action = $_GET['audit_action'] ?? '';
$audit_table = $_GET['audit_table'] ?? '';
$audit_date_from = $_GET['audit_date_from'] ?? '';
$audit_date_to = $_GET['audit_date_to'] ?? '';

// Pagination for audit trail
$audit_page = max(1, (int)($_GET['audit_p'] ?? 1));
$audit_per_page = 10;
$audit_offset = ($audit_page - 1) * $audit_per_page;

// Get alerts based on filters
try {
    $alerts = get_employee_alerts($status, $priority ?: null);
    
    // Ensure $alerts is an array
    if (!is_array($alerts)) {
        $alerts = [];
    }
    
    // Filter by alert type if specified
    if ($alert_type && !empty($alerts)) {
        $alerts = array_filter($alerts, function($alert) use ($alert_type) {
            return isset($alert['alert_type']) && $alert['alert_type'] === $alert_type;
        });
        // Re-index array after filtering
        $alerts = array_values($alerts);
    }
} catch (Exception $e) {
    error_log("Error fetching alerts: " . $e->getMessage());
    $alerts = [];
}

// Get alert statistics
try {
    $stats = get_alert_statistics();
    // Ensure stats has default values
    if (!is_array($stats)) {
        $stats = [
            'total_active' => 0,
            'urgent' => 0,
            'high' => 0,
            'overdue' => 0
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching alert statistics: " . $e->getMessage());
    $stats = [
        'total_active' => 0,
        'urgent' => 0,
        'high' => 0,
        'overdue' => 0
    ];
}
?>

<div class="container-fluid hrdash">
    <!-- Header Section with Actions -->
    <?php if (($_SESSION['user_role'] ?? '') === 'hr_admin'): ?>
    <div class="hrdash-welcome">
        <div class="hrdash-welcome__left">
            <h2 class="hrdash-welcome__title">
                <i class="fas fa-bell me-2"></i>Employee Alerts
            </h2>
            <p class="hrdash-welcome__subtitle">Monitor and manage employee alerts and notifications</p>
        </div>
        <div class="hrdash-welcome__actions">
            <span id="current-time-alerts" class="hrdash-welcome__time"><?php echo strtolower(date('h:i A')); ?></span>
            
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
                    $displayName = trim((string)($_SESSION['name'] ?? ($_SESSION['username'] ?? 'HR Admin')));
                    $initials = 'HA';
                    if ($displayName) {
                        $parts = preg_split('/\s+/', $displayName);
                        $first = $parts[0][0] ?? 'H';
                        $last = (count($parts) > 1) ? ($parts[count($parts) - 1][0] ?? 'A') : ($parts[0][1] ?? 'A');
                        $initials = strtoupper($first . $last);
                    }
                    ?>
                    <span class="hrdash-welcome__avatar"><?php echo htmlspecialchars($initials); ?></span>
                    <i class="fas fa-chevron-down hrdash-welcome__chevron"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?page=profile"><i class="fas fa-user me-2"></i>Profile</a></li>
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

    <!-- Breadcrumb -->
    <nav class="hr-breadcrumb" aria-label="Breadcrumb">
        <ol class="hr-breadcrumb__list">
            <li class="hr-breadcrumb__item">
                <a href="?page=dashboard" class="hr-breadcrumb__link">Dashboard</a>
            </li>
            <li class="hr-breadcrumb__item hr-breadcrumb__current" aria-current="page">
                Alerts
            </li>
        </ol>
    </nav>

    <!-- Alert Statistics -->
    <div class="d-flex justify-content-center">
        <div class="alert-stats-container">
            <div class="row g-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card hrdash-stat hrdash-stat--primary">
                        <div class="hrdash-stat__header">
                            <div class="hrdash-stat__label">Total Active</div>
                        </div>
                        <div class="hrdash-stat__content">
                            <div class="hrdash-stat__value"><?php echo number_format($stats['total_active'] ?? 0); ?></div>
                            <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>5%</span>
                            </div>
                        </div>
                        <div class="hrdash-stat__meta">Open alerts currently active in the system.</div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card hrdash-stat">
                        <div class="hrdash-stat__header">
                            <div class="hrdash-stat__label">Urgent</div>
                        </div>
                        <div class="hrdash-stat__content">
                            <div class="hrdash-stat__value"><?php echo number_format($stats['urgent'] ?? 0); ?></div>
                            <div class="hrdash-stat__trend hrdash-stat__trend--negative">
                                <i class="fas fa-arrow-down"></i>
                                <span>2%</span>
                            </div>
                        </div>
                        <div class="hrdash-stat__meta">Alerts that require immediate attention.</div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card hrdash-stat">
                        <div class="hrdash-stat__header">
                            <div class="hrdash-stat__label">High Priority</div>
                        </div>
                        <div class="hrdash-stat__content">
                            <div class="hrdash-stat__value"><?php echo number_format($stats['high'] ?? 0); ?></div>
                            <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>4%</span>
                            </div>
                        </div>
                        <div class="hrdash-stat__meta">High priority alerts that need monitoring.</div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card hrdash-stat">
                        <div class="hrdash-stat__header">
                            <div class="hrdash-stat__label">Overdue</div>
                        </div>
                        <div class="hrdash-stat__content">
                            <div class="hrdash-stat__value"><?php echo number_format($stats['overdue'] ?? 0); ?></div>
                            <div class="hrdash-stat__trend hrdash-stat__trend--negative">
                                <i class="fas fa-arrow-down"></i>
                                <span>3%</span>
                            </div>
                        </div>
                        <div class="hrdash-stat__meta">Alerts that are past their due date.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts Card -->
    <div class="card alerts-card-modern">
        <div class="card-header-modern">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title-modern"><i class="fas fa-bell me-2"></i>Employee Alerts</h5>
                <div class="page-actions-modern">
                    <button class="btn btn-primary-modern btn-sm" data-bs-toggle="modal" data-bs-target="#addAlertModal">
                        <span class="hr-icon hr-icon-plus me-2"></span>Add Alert
                    </button>
                    <button class="btn btn-outline-modern btn-sm" onclick="generateLicenseAlerts()">
                        <i class="fas fa-sync me-2"></i>Generate License Alerts
                    </button>
                </div>
            </div>
        </div>
            <div class="card-body">
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select class="form-select" id="statusFilter" onchange="filterAlerts()">
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="acknowledged" <?php echo $status === 'acknowledged' ? 'selected' : ''; ?>>Acknowledged</option>
                                <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="dismissed" <?php echo $status === 'dismissed' ? 'selected' : ''; ?>>Dismissed</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="priorityFilter" class="form-label">Priority</label>
                            <select class="form-select" id="priorityFilter" onchange="filterAlerts()">
                                <option value="">All Priorities</option>
                                <option value="urgent" <?php echo $priority === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="typeFilter" class="form-label">Alert Type</label>
                            <select class="form-select" id="typeFilter" onchange="filterAlerts()">
                                <option value="">All Types</option>
                                <option value="license_expiry" <?php echo $alert_type === 'license_expiry' ? 'selected' : ''; ?>>License Expiry</option>
                                <option value="document_expiry" <?php echo $alert_type === 'document_expiry' ? 'selected' : ''; ?>>Document Expiry</option>
                                <option value="missing_document" <?php echo $alert_type === 'missing_document' ? 'selected' : ''; ?>>Missing Document</option>
                                <option value="contract_expiry" <?php echo $alert_type === 'contract_expiry' ? 'selected' : ''; ?>>Contract Expiry</option>
                                <option value="training_due" <?php echo $alert_type === 'training_due' ? 'selected' : ''; ?>>Training Due</option>
                                <option value="medical_expiry" <?php echo $alert_type === 'medical_expiry' ? 'selected' : ''; ?>>Medical Expiry</option>
                                <option value="other" <?php echo $alert_type === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                <span class="hr-icon hr-icon-dismiss me-2"></span>Clear Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alerts Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th class="sortable" data-sort="priority">
                                    Priority
                                    <i class="fas fa-sort"></i>
                                </th>
                                <th class="sortable" data-sort="employee">
                                    Employee
                                    <i class="fas fa-sort"></i>
                                </th>
                                <th class="sortable" data-sort="type">
                                    Alert Type
                                    <i class="fas fa-sort"></i>
                                </th>
                                <th class="sortable" data-sort="title">
                                    Title
                                    <i class="fas fa-sort"></i>
                                </th>
                                <th class="sortable" data-sort="due_date">
                                    Due Date
                                    <i class="fas fa-sort"></i>
                                </th>
                                <th class="sortable" data-sort="status">
                                    Status
                                    <i class="fas fa-sort"></i>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($alerts)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-bell-slash fa-3x mb-3" style="opacity: 0.3;"></i>
                                        <h5 class="mb-2">No Alerts Found</h5>
                                        <p class="mb-3">
                                            <?php if ($status !== 'active'): ?>
                                                No <?php echo htmlspecialchars($status); ?> alerts found.
                                            <?php else: ?>
                                                There are currently no active alerts in the system.
                                            <?php endif; ?>
                                        </p>
                                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                                            <a href="?page=add_alert" class="btn btn-primary btn-sm">
                                                <span class="hr-icon hr-icon-plus me-1"></span>Create Alert
                                            </a>
                                            <?php if ($status === 'active'): ?>
                                            <button class="btn btn-info btn-sm" onclick="generateLicenseAlerts()">
                                                <i class="fas fa-sync me-1"></i>Generate License Alerts
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($status !== 'active' || $priority || $alert_type): ?>
                                            <a href="?page=alerts" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-times me-1"></i>Clear Filters
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($alerts as $alert): ?>
                                <tr class="alert-row" data-id="<?php echo htmlspecialchars($alert['id'] ?? ''); ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input alert-checkbox" value="<?php echo htmlspecialchars($alert['id'] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <span class="priority-badge <?php echo htmlspecialchars($alert['priority'] ?? 'medium'); ?>">
                                            <?php echo strtoupper(htmlspecialchars($alert['priority'] ?? 'medium')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars(($alert['surname'] ?? '') . ', ' . ($alert['first_name'] ?? '') . ' ' . ($alert['middle_name'] ?? '')); ?></strong>
                                            <br>
                                            <small class="text-muted">#<?php echo htmlspecialchars($alert['employee_no'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($alert['post'] ?? 'Unassigned'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($alert['alert_type'] ?? 'other'))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($alert['title'] ?? 'No Title'); ?></strong>
                                            <?php if (!empty($alert['description'])): ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($alert['description'], 0, 100)) . (strlen($alert['description']) > 100 ? '...' : ''); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($alert['due_date'])): ?>
                                            <?php 
                                            try {
                                                $due_date = new DateTime($alert['due_date']);
                                                $today = new DateTime();
                                                $is_overdue = $due_date < $today;
                                                $today_copy = new DateTime();
                                                $is_due_soon = $due_date <= $today_copy->modify('+7 days');
                                            } catch (Exception $e) {
                                                $is_overdue = false;
                                                $is_due_soon = false;
                                            }
                                            ?>
                                            <span class="text-<?php echo $is_overdue ? 'danger' : ($is_due_soon ? 'warning' : 'success'); ?>">
                                                <?php echo htmlspecialchars($alert['due_date'] ? date('M j, Y', strtotime($alert['due_date'])) : 'N/A'); ?>
                                            </span>
                                            <?php if ($is_overdue): ?>
                                            <br><small class="text-danger">Overdue</small>
                                            <?php elseif ($is_due_soon): ?>
                                            <br><small class="text-warning">Due Soon</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No due date</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="alert-badge <?php echo htmlspecialchars($alert['status'] ?? 'active'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($alert['status'] ?? 'active')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group-modern" role="group">
                                            <?php if (($alert['status'] ?? '') === 'active'): ?>
                                            <button class="btn btn-action-modern btn-info-modern" onclick="acknowledgeAlert(<?php echo htmlspecialchars($alert['id'] ?? 0); ?>)" title="Acknowledge">
                                                <svg width="16" height="16" viewBox="0 0 375 375" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle;">
                                                    <path d="M 281.199219 118.308594 C 275.351562 112.460938 265.859375 112.460938 260.015625 118.308594 C 260.015625 118.308594 168.90625 209.492188 153.164062 225.25 C 152.554688 225.863281 151.726562 226.207031 150.863281 226.207031 C 150 226.207031 149.171875 225.863281 148.558594 225.25 C 140.582031 217.265625 114.609375 191.273438 114.609375 191.273438 C 108.761719 185.417969 99.269531 185.417969 93.425781 191.273438 C 87.578125 197.121094 87.578125 206.625 93.425781 212.472656 C 93.425781 212.472656 128.542969 247.628906 137.96875 257.054688 C 141.386719 260.476562 146.027344 262.402344 150.863281 262.402344 C 155.699219 262.402344 160.339844 260.476562 163.757812 257.054688 C 180.367188 240.433594 281.199219 139.515625 281.199219 139.515625 C 287.046875 133.660156 287.046875 124.164062 281.199219 118.308594 Z" fill="#2563eb"/>
                                                </svg>
                                            </button>
                                            <button class="btn btn-action-modern btn-success-modern" onclick="resolveAlert(<?php echo htmlspecialchars($alert['id'] ?? 0); ?>)" title="Resolve">
                                                <svg width="16" height="16" viewBox="0 0 375 375" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle;">
                                                    <path d="M 251.175781 118.308594 C 245.332031 112.460938 235.839844 112.460938 229.992188 118.308594 C 229.992188 118.308594 138.882812 209.492188 123.144531 225.25 C 122.53125 225.863281 121.703125 226.207031 120.839844 226.207031 C 119.976562 226.207031 119.148438 225.863281 118.539062 225.25 C 110.558594 217.265625 84.585938 191.273438 84.585938 191.273438 C 78.742188 185.417969 69.25 185.417969 63.402344 191.273438 C 57.554688 197.121094 57.554688 206.625 63.402344 212.472656 C 63.402344 212.472656 98.523438 247.628906 107.945312 257.054688 C 111.363281 260.476562 116.003906 262.402344 120.839844 262.402344 C 125.675781 262.402344 130.316406 260.476562 133.734375 257.054688 C 150.34375 240.433594 251.175781 139.515625 251.175781 139.515625 C 257.023438 133.660156 257.023438 124.160156 251.175781 118.308594 Z" fill="#16a34a"/>
                                                    <path d="M 311.222656 118.308594 C 305.375 112.460938 295.882812 112.460938 290.035156 118.308594 C 290.035156 118.308594 198.929688 209.492188 183.1875 225.25 C 182.578125 225.863281 181.75 226.207031 180.886719 226.207031 C 180.023438 226.207031 179.191406 225.863281 178.582031 225.25 C 170.605469 217.265625 144.632812 191.273438 144.632812 191.273438 C 138.785156 185.417969 129.292969 185.417969 123.445312 191.273438 C 117.601562 197.121094 117.601562 206.625 123.445312 212.472656 C 123.445312 212.472656 158.566406 247.628906 167.988281 257.054688 C 171.410156 260.476562 176.046875 262.402344 180.886719 262.402344 C 185.722656 262.402344 190.359375 260.476562 193.78125 257.054688 C 210.390625 240.433594 311.222656 139.515625 311.222656 139.515625 C 317.066406 133.660156 317.066406 124.160156 311.222656 118.308594 Z" fill="#16a34a"/>
                                                </svg>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-action-modern btn-secondary-modern" onclick="dismissAlert(<?php echo htmlspecialchars($alert['id'] ?? 0); ?>)" title="Dismiss">
                                                <svg width="16" height="16" viewBox="0 0 375 375" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle;">
                                                    <path d="M 133.84375 105.703125 L 187.445312 159.304688 L 241.042969 105.703125 C 259.804688 86.945312 287.945312 115.082031 269.183594 133.84375 L 215.582031 187.445312 L 269.183594 241.042969 C 287.945312 259.804688 259.804688 287.945312 241.042969 269.183594 L 187.445312 215.582031 L 133.84375 269.183594 C 115.082031 287.945312 86.945312 259.804688 105.703125 241.042969 L 159.304688 187.445312 L 105.703125 133.84375 C 86.945312 115.082031 115.082031 86.945312 133.84375 105.703125 Z" fill="#64748b"/>
                                                </svg>
                                            </button>
                                            <button class="btn btn-action-modern btn-primary-modern" onclick="viewAlert(<?php echo htmlspecialchars($alert['id'] ?? 0); ?>)" title="View Details">
                                                <svg width="16" height="16" viewBox="0 0 375 375" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle;">
                                                    <path d="M 312.875 183.980469 C 312.304688 183.25 298.652344 165.8125 276.46875 148.160156 C 246.789062 124.542969 215.96875 112.0625 187.339844 112.0625 C 158.710938 112.0625 127.890625 124.542969 98.214844 148.160156 C 76.03125 165.8125 62.378906 183.25 61.808594 183.980469 L 59.125 187.433594 L 61.808594 190.886719 C 62.378906 191.621094 76.03125 209.058594 98.214844 226.710938 C 127.890625 250.324219 158.710938 262.808594 187.339844 262.808594 C 215.96875 262.808594 246.789062 250.324219 276.46875 226.710938 C 298.652344 209.058594 312.304688 191.621094 312.875 190.886719 L 315.558594 187.433594 Z M 187.339844 229.132812 C 164.320312 229.132812 145.660156 210.464844 145.660156 187.433594 C 145.660156 164.40625 164.320312 145.734375 187.339844 145.734375 C 193.292969 145.730469 198.980469 146.949219 204.410156 149.394531 C 203.757812 150.0625 203.179688 150.785156 202.675781 151.566406 C 202.167969 152.347656 201.742188 153.171875 201.398438 154.035156 C 201.054688 154.902344 200.796875 155.792969 200.628906 156.710938 C 200.460938 157.625 200.382812 158.550781 200.398438 159.480469 C 200.410156 160.410156 200.515625 161.332031 200.714844 162.242188 C 200.910156 163.152344 201.191406 164.035156 201.5625 164.890625 C 201.933594 165.746094 202.382812 166.554688 202.914062 167.320312 C 203.445312 168.085938 204.042969 168.792969 204.714844 169.441406 C 205.382812 170.085938 206.109375 170.660156 206.894531 171.164062 C 207.675781 171.667969 208.503906 172.089844 209.367188 172.429688 C 210.234375 172.769531 211.128906 173.023438 212.042969 173.1875 C 212.960938 173.351562 213.882812 173.425781 214.816406 173.40625 C 215.746094 173.386719 216.664062 173.277344 217.574219 173.078125 C 218.484375 172.875 219.363281 172.589844 220.21875 172.214844 C 221.070312 171.839844 221.878906 171.386719 222.640625 170.851562 C 223.402344 170.316406 224.105469 169.714844 224.75 169.039062 C 227.605469 174.839844 229.03125 180.96875 229.023438 187.433594 C 229.023438 210.464844 210.359375 229.132812 187.339844 229.132812 Z" fill="#ffffff"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Audit Trail Section -->
    <div class="row g-4">
        <div class="col-12">
    <div class="card alerts-card-modern">
        <div class="card-header-modern">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title-modern"><i class="fas fa-history me-2"></i>Audit Trail</h5>
                <div class="page-actions-modern">
                    <button class="btn btn-outline-modern btn-sm" onclick="refreshAuditTrail()">
                        <i class="fas fa-sync me-1"></i>Refresh
                    </button>
                    <button class="btn btn-outline-modern btn-sm" onclick="exportAuditTrail()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </div>
        </div>
            <div class="card-body">
                <!-- Audit Trail Filters -->
                <div class="filter-section">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <label for="auditActionFilter" class="form-label">Action</label>
                            <select class="form-select form-select-sm" id="auditActionFilter" onchange="filterAuditTrail()">
                                <option value="" <?php echo $audit_action === '' ? 'selected' : ''; ?>>All Actions</option>
                                <option value="INSERT" <?php echo $audit_action === 'INSERT' ? 'selected' : ''; ?>>Created</option>
                                <option value="UPDATE" <?php echo $audit_action === 'UPDATE' ? 'selected' : ''; ?>>Updated</option>
                                <option value="DELETE" <?php echo $audit_action === 'DELETE' ? 'selected' : ''; ?>>Deleted</option>
                                <option value="Alert" <?php echo $audit_action === 'Alert' ? 'selected' : ''; ?>>Alert Actions</option>
                                <option value="Employee" <?php echo $audit_action === 'Employee' ? 'selected' : ''; ?>>Employee Actions</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="auditTableFilter" class="form-label">Table</label>
                            <select class="form-select form-select-sm" id="auditTableFilter" onchange="filterAuditTrail()">
                                <option value="" <?php echo $audit_table === '' ? 'selected' : ''; ?>>All Tables</option>
                                <option value="employees" <?php echo $audit_table === 'employees' ? 'selected' : ''; ?>>Employees</option>
                                <option value="employee_alerts" <?php echo $audit_table === 'employee_alerts' ? 'selected' : ''; ?>>Alerts</option>
                                <option value="posts" <?php echo $audit_table === 'posts' ? 'selected' : ''; ?>>Posts</option>
                                <option value="users" <?php echo $audit_table === 'users' ? 'selected' : ''; ?>>Users</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="auditDateFrom" class="form-label">Date From</label>
                            <input type="date" class="form-control form-control-sm" id="auditDateFrom" value="<?php echo htmlspecialchars($audit_date_from); ?>" onchange="filterAuditTrail()">
                        </div>
                        <div class="col-md-2">
                            <label for="auditDateTo" class="form-label">Date To</label>
                            <input type="date" class="form-control form-control-sm" id="auditDateTo" value="<?php echo htmlspecialchars($audit_date_to); ?>" onchange="filterAuditTrail()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearAuditFilters()">
                                <span class="hr-icon hr-icon-dismiss me-1"></span>Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Audit Trail Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th style="width: 12%;">Timestamp</th>
                                <th style="width: 12%;">User</th>
                                <th style="width: 10%;">Action</th>
                                <th style="width: 10%;">Table</th>
                                <th style="width: 12%;">Record</th>
                                <th style="width: 28%;">Changes</th>
                                <th style="width: 10%;">IP Address</th>
                                <th style="width: 6%;">Details</th>
                            </tr>
                        </thead>
                        <tbody id="auditTrailBody">
                            <?php
                            // Get audit trail filters
                            $audit_filters = [
                                'action' => $audit_action,
                                'table_name' => $audit_table,
                                'date_from' => $audit_date_from,
                                'date_to' => $audit_date_to
                            ];
                            
                            // Get audit logs (check if function exists and table exists)
                            $audit_logs = [];
                            $audit_total_count = 0;
                            $audit_total_pages = 1;
                            
                            if (function_exists('get_audit_logs') && function_exists('get_audit_logs_count')) {
                                try {
                                    $audit_logs = get_audit_logs($audit_filters, $audit_per_page, $audit_offset);
                                    $audit_total_count = get_audit_logs_count($audit_filters);
                                    $audit_total_pages = max(1, (int)ceil($audit_total_count / $audit_per_page));
                                    $audit_page = min($audit_page, $audit_total_pages);
                                } catch (Exception $e) {
                                    // Table might not exist yet
                                    error_log("Audit logs error: " . $e->getMessage());
                                }
                            }
                            
                            if (empty($audit_logs)):
                            ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p class="mb-0">No audit logs found</p>
                                        <small>Audit logs will appear here as system activities occur</small>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($audit_logs as $log): ?>
                                <tr>
                                    <td>
                                        <small><?php echo date('M j, Y', strtotime($log['created_at'])); ?></small><br>
                                        <small class="text-muted"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($log['user_name']): ?>
                                            <strong><?php echo htmlspecialchars($log['user_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($log['username'] ?? ''); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $log['action'] === 'INSERT' ? 'success' : 
                                                ($log['action'] === 'UPDATE' ? 'info' : 
                                                ($log['action'] === 'DELETE' ? 'danger' : 'secondary')); 
                                        ?>">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($log['table_name'] ?? 'N/A'); ?></code>
                                    </td>
                                    <td>
                                        <?php if ($log['record_id']): ?>
                                            <span class="badge bg-light text-dark">#<?php echo $log['record_id']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="audit-details">
                                            <?php
                                            $old = null;
                                            $new = null;
                                            
                                            if ($log['old_values']) {
                                                $old = is_string($log['old_values']) ? json_decode($log['old_values'], true) : $log['old_values'];
                                            }
                                            if ($log['new_values']) {
                                                $new = is_string($log['new_values']) ? json_decode($log['new_values'], true) : $log['new_values'];
                                            }
                                            
                                            if ($old || $new):
                                            ?>
                                                <?php if ($old): ?>
                                                <div class="mb-1">
                                                    <small class="text-danger"><strong>Old:</strong></small>
                                                    <small class="text-muted">
                                                        <?php 
                                                        $old_str = [];
                                                        foreach ($old as $key => $value) {
                                                            if ($value !== null) {
                                                                $old_str[] = "<strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value);
                                                            }
                                                        }
                                                        echo implode(', ', $old_str);
                                                        ?>
                                                    </small>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($new): ?>
                                                <div>
                                                    <small class="text-success"><strong>New:</strong></small>
                                                    <small class="text-muted">
                                                        <?php 
                                                        $new_str = [];
                                                        foreach ($new as $key => $value) {
                                                            if ($value !== null) {
                                                                $new_str[] = "<strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value);
                                                            }
                                                        }
                                                        echo implode(', ', $new_str);
                                                        ?>
                                                    </small>
                                                </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted">-</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Audit Trail Pagination -->
                <?php if (!empty($audit_logs) && $audit_total_pages > 1): ?>
                    <nav class="mt-3" aria-label="Audit trail pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <?php
                            $audit_base_query = array_merge(
                                ['page' => 'alerts'],
                                array_filter([
                                    'audit_action' => $audit_action,
                                    'audit_table' => $audit_table,
                                    'audit_date_from' => $audit_date_from,
                                    'audit_date_to' => $audit_date_to,
                                ])
                            );
                            ?>
                            <li class="page-item <?php echo $audit_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($audit_base_query, ['audit_p' => max(1, $audit_page - 1)])); ?>">
                                    &laquo;
                                </a>
                            </li>
                            <?php
                            $start = max(1, $audit_page - 2);
                            $end   = min($audit_total_pages, $audit_page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $audit_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($audit_base_query, ['audit_p' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $audit_page >= $audit_total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($audit_base_query, ['audit_p' => min($audit_total_pages, $audit_page + 1)])); ?>">
                                    &raquo;
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Showing <?php echo number_format(count($audit_logs)); ?> of <?php echo number_format($audit_total_count); ?> events
                            <?php if (!empty(array_filter($audit_filters))): ?>
                                <span class="text-muted">(filtered)</span>
                            <?php endif; ?>
                        </small>
                    </div>
                <?php elseif (!empty($audit_logs)): ?>
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Showing <?php echo number_format(count($audit_logs)); ?> of <?php echo number_format($audit_total_count); ?> events
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
<?php endif; ?>

<!-- Add Alert Modal -->
<div class="modal fade" id="addAlertModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=add_alert">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label">Employee *</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php
                                $employees = get_employees();
                                foreach ($employees as $employee) {
                                    echo '<option value="' . $employee['id'] . '">' . 
                                         htmlspecialchars($employee['surname'] . ', ' . $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '')) . 
                                         ' (#' . $employee['employee_no'] . ')</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="alert_type" class="form-label">Alert Type *</label>
                            <select class="form-select" id="alert_type" name="alert_type" required>
                                <option value="">Select Type</option>
                                <option value="license_expiry">License Expiry</option>
                                <option value="document_expiry">Document Expiry</option>
                                <option value="missing_document">Missing Document</option>
                                <option value="contract_expiry">Contract Expiry</option>
                                <option value="training_due">Training Due</option>
                                <option value="medical_expiry">Medical Expiry</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority *</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Alert</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterAlerts() {
    const status = document.getElementById('statusFilter').value;
    const priority = document.getElementById('priorityFilter').value;
    const type = document.getElementById('typeFilter').value;
    
    let url = '?page=alerts';
    const params = [];
    
    if (status) params.push('status=' + status);
    if (priority) params.push('priority=' + priority);
    if (type) params.push('alert_type=' + type);
    
    if (params.length > 0) {
        url += '&' + params.join('&');
    }
    
    window.location.href = url;
}

function clearFilters() {
    window.location.href = '?page=alerts';
}

function acknowledgeAlert(id) {
    if (confirm('Are you sure you want to acknowledge this alert?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="acknowledge">' +
                        '<input type="hidden" name="alert_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function resolveAlert(id) {
    if (confirm('Are you sure you want to resolve this alert?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="resolve">' +
                        '<input type="hidden" name="alert_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function dismissAlert(id) {
    if (confirm('Are you sure you want to dismiss this alert?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="dismiss">' +
                        '<input type="hidden" name="alert_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function viewAlert(id) {
    // Fetch alert details and show in modal
    fetch(`?page=alerts&action=get_alert&id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.alert) {
                const alert = data.alert;
                document.getElementById('viewAlertTitle').textContent = alert.title || 'Alert Details';
                document.getElementById('viewAlertEmployee').textContent = (alert.surname || '') + ', ' + (alert.first_name || '') + ' ' + (alert.middle_name || '');
                document.getElementById('viewAlertType').textContent = (alert.alert_type || '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                document.getElementById('viewAlertPriority').textContent = (alert.priority || 'medium').toUpperCase();
                document.getElementById('viewAlertPriority').className = 'badge priority-badge ' + (alert.priority || 'medium');
                document.getElementById('viewAlertStatus').textContent = (alert.status || 'active').charAt(0).toUpperCase() + (alert.status || 'active').slice(1);
                document.getElementById('viewAlertStatus').className = 'badge alert-badge ' + (alert.status || 'active');
                document.getElementById('viewAlertDueDate').textContent = alert.due_date ? new Date(alert.due_date).toLocaleDateString() : 'No due date';
                document.getElementById('viewAlertDescription').textContent = alert.description || 'No description provided';
                document.getElementById('viewAlertCreated').textContent = alert.created_at ? new Date(alert.created_at).toLocaleString() : 'N/A';
                
                const modal = new bootstrap.Modal(document.getElementById('viewAlertModal'));
                modal.show();
            } else {
                alert('Error loading alert details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading alert details. Please try again.');
        });
}

function generateLicenseAlerts() {
    if (confirm('Generate automatic license expiry alerts for all employees with licenses expiring in the next 30 days?')) {
        window.location.href = '?page=alerts&action=generate_license_alerts';
    }
}

// Audit Trail Functions
function filterAuditTrail() {
    const action = document.getElementById('auditActionFilter').value;
    const table = document.getElementById('auditTableFilter').value;
    const dateFrom = document.getElementById('auditDateFrom').value;
    const dateTo = document.getElementById('auditDateTo').value;

    let url = '?page=alerts';
    const params = [];

    // Keep existing alert filters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status')) params.push('status=' + urlParams.get('status'));
    if (urlParams.get('priority')) params.push('priority=' + urlParams.get('priority'));
    if (urlParams.get('alert_type')) params.push('alert_type=' + urlParams.get('alert_type'));

    // Add audit filters
    if (action) params.push('audit_action=' + action);
    if (table) params.push('audit_table=' + table);
    if (dateFrom) params.push('audit_date_from=' + dateFrom);
    if (dateTo) params.push('audit_date_to=' + dateTo);

    // Reset to page 1 when filters change
    params.push('audit_p=1');

    if (params.length > 0) {
        url += '&' + params.join('&');
    }

    window.location.href = url;
}

function clearAuditFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    let url = '?page=alerts&audit_p=1';
    
    // Keep alert filters, remove audit filters
    if (urlParams.get('status')) url += '&status=' + urlParams.get('status');
    if (urlParams.get('priority')) url += '&priority=' + urlParams.get('priority');
    if (urlParams.get('alert_type')) url += '&alert_type=' + urlParams.get('alert_type');
    
    window.location.href = url;
}

function refreshAuditTrail() {
    // Scroll to audit trail section and reload
    document.getElementById('auditTrailBody').scrollIntoView({ behavior: 'smooth' });
    setTimeout(() => {
        filterAuditTrail();
    }, 300);
}

function exportAuditTrail() {
    // Export audit trail to CSV
    const table = document.querySelector('#auditTrailBody').closest('table');
    let csv = 'Timestamp,User,Action,Table,Record,Changes,IP Address,Details\n';
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 8) {
            const timestamp = cells[0].textContent.trim().replace(/\n/g, ' ');
            const user = cells[1].textContent.trim().replace(/\n/g, ' ');
            const action = cells[2].textContent.trim();
            const tableName = cells[3].textContent.trim();
            const record = cells[4].textContent.trim();
            const changes = cells[5].textContent.trim();
            const ip = cells[6].textContent.trim();
            const details = cells[7].textContent.trim();
            
            csv += `"${timestamp}","${user}","${action}","${tableName}","${record}","${changes}","${ip}","${details}"\n`;
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `audit_trail_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

function loadMoreAuditLogs() {
    // This would load more audit logs via AJAX
    alert('Load more functionality - to be implemented with pagination');
}
</script>

<script>
// Update time display every minute for alerts page (HR Admin only)
<?php if (($_SESSION['user_role'] ?? '') === 'hr_admin'): ?>
(function() {
    function updateTime() {
        const timeElement = document.getElementById('current-time-alerts');
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

<style>
/* Modern Alerts Page Styling */
.alerts-modern {
    /* Use portal-wide spacing system (font-override.css) instead of page-local padding */
    padding: 0;
    max-width: 100%;
    overflow-x: hidden;
    min-height: 100vh;
    background: #ffffff; /* default for non HR-Admin portals */
}

/* HR-Admin: use light separated background */
body.portal-hr-admin .alerts-modern {
    background: #f8fafc;
}

/* Header removal handled globally for HR-Admin (includes/header.php) */

/* Alert Statistics Container - Centered and Compressed */
.alert-stats-container {
    max-width: 1200px;
    width: 100%;
    margin: 0 auto;
}

/* Page Header */
.page-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    margin-top: 0;
    padding-top: 0;
}

.page-title-modern {
    flex: 1;
}

.page-title-main {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.page-subtitle-modern {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0;
    line-height: 1.5;
}

.page-actions-modern {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

/* Modern Buttons */
.btn-primary-modern {
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    color: #ffffff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.25);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.btn-primary-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.35);
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: #ffffff;
}

.btn-outline-modern {
    border: 1.5px solid #e2e8f0;
    color: #475569;
    background: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.btn-outline-modern:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Summary Cards */
.summary-cards-modern {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.stat-card-modern {
    background: #ffffff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
    overflow: hidden;
}

.stat-card-modern:hover {
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08), 0 8px 16px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
}

.card-body-modern {
    padding: 1.5rem;
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-icon {
    font-size: 1.125rem;
    color: #94a3b8;
}

.stat-content {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    line-height: 1;
    letter-spacing: -0.02em;
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

.stat-footer {
    font-size: 0.8125rem;
    color: #94a3b8;
    display: block;
    margin-top: 0.5rem;
}

/* Badges */
.badge-primary-modern,
.badge-danger-modern,
.badge-warning-modern {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    letter-spacing: 0.01em;
}

.badge-primary-modern {
    background: #dbeafe;
    color: #2563eb;
}

.badge-danger-modern {
    background: #fee2e2;
    color: #dc2626;
}

.badge-warning-modern {
    background: #fef3c7;
    color: #d97706;
}

/* Cards */
.alerts-card-modern {
    background: #ffffff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-bottom: 1.5rem;
    transition: box-shadow 0.2s ease;
}

.alerts-card-modern:hover {
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08), 0 8px 16px rgba(0, 0, 0, 0.06);
}

.card-header-modern {
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
}

.card-title-modern {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    letter-spacing: -0.01em;
}

.card-body {
    padding: 1.5rem;
}

.audit-details {
    max-width: 400px;
    font-size: 0.75rem;
}

.audit-details strong {
    font-weight: 600;
}

.table-sm td {
    padding: 0.5rem;
    vertical-align: middle;
}

/* Fix audit trail table alignment */
.table-responsive table {
    table-layout: fixed;
    width: 100%;
}

.table-responsive table th,
.table-responsive table td {
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.table-responsive table th {
    white-space: nowrap;
    text-align: left;
    font-weight: 600;
}

.table-responsive table td {
    text-align: left;
}

/* Alerts Table Styling */
.table-responsive {
    border-radius: 8px;
    overflow-x: hidden;
    overflow-y: visible;
    background: #ffffff;
    width: 100%;
    max-width: 100%;
}

.table thead th {
    background-color: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.625rem 0.75rem;
    white-space: normal;
    word-wrap: break-word;
    color: #64748b;
}

.table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8fafc;
    transform: translateX(2px);
}

.table tbody td {
    padding: 0.625rem 0.75rem;
    vertical-align: middle;
    color: #475569;
    font-size: 0.875rem;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Empty State Styling */
.table tbody td.text-center {
    padding: 3rem 1rem;
}

.table tbody td.text-center i {
    display: block;
    margin-bottom: 1rem;
}

/* Badge Styling */
.badge.bg-info {
    background-color: #0ea5e9 !important;
    color: white !important;
}

/* Priority Badges */
.priority-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.01em;
    display: inline-block;
}

.priority-badge.urgent {
    background-color: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.priority-badge.high {
    background-color: #fef3c7;
    color: #d97706;
    border: 1px solid #fde68a;
}

.priority-badge.medium {
    background-color: #dbeafe;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

.priority-badge.low {
    background-color: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

/* Alert Status Badges */
.alert-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0.01em;
    display: inline-block;
}

.alert-badge.active {
    background-color: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.alert-badge.acknowledged {
    background-color: #dbeafe;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

.alert-badge.resolved {
    background-color: #dcfce7;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

.alert-badge.dismissed {
    background-color: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

/* Button Group Spacing */
.btn-group .btn {
    margin-right: 0.25rem;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Action Buttons */
.btn-group-modern {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-action-modern {
    border-radius: 6px;
    transition: all 0.2s ease;
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    border: 1.5px solid;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
}

.btn-info-modern {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #2563eb;
}

.btn-info-modern:hover {
    background: #dbeafe;
    border-color: #93c5fd;
    color: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
}

.btn-success-modern {
    background: #dcfce7;
    border-color: #bbf7d0;
    color: #16a34a;
}

.btn-success-modern:hover {
    background: #bbf7d0;
    border-color: #86efac;
    color: #15803d;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(22, 163, 74, 0.2);
}

.btn-secondary-modern {
    background: #f1f5f9;
    border-color: #e2e8f0;
    color: #64748b;
}

.btn-secondary-modern:hover {
    background: #e2e8f0;
    border-color: #cbd5e1;
    color: #475569;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-action-modern.btn-primary-modern {
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    border-color: #1fb2d5;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(31, 178, 213, 0.2);
}

.btn-action-modern.btn-primary-modern:hover {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    border-color: #0ea5e9;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(31, 178, 213, 0.3);
}

/* Filter Section */
.filter-section {
    background: #f8fafc;
    padding: 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.filter-section .form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #334155;
    margin-bottom: 0.5rem;
}

.filter-section .form-select,
.filter-section .form-control {
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.filter-section .form-select:focus,
.filter-section .form-control:focus {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .alerts-modern {
        padding: 1rem 1rem 2rem 1rem;
    }
    
    .page-header-modern {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .page-actions-modern {
        width: 100%;
        flex-wrap: wrap;
    }
    
    .summary-cards-modern {
        grid-template-columns: 1fr;
    }
}

/* Dark theme support for Employee Alerts page */
html[data-theme="dark"] .hrdash {
    background: var(--interface-bg) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-title-main {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-subtitle-modern {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .hrdash-stat {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .hrdash-stat__label {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .hrdash-stat__value {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .hrdash-stat__meta {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .alerts-card-modern {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-header-modern {
    background: #1a1d23 !important;
    border-bottom-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-title-modern {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-body {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .filter-section {
    background: #1a1d23 !important;
    border: 1px solid var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .filter-section .form-label {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .filter-section .form-select,
html[data-theme="dark"] .filter-section .form-control {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .filter-section .form-select option {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .filter-section .form-select:focus,
html[data-theme="dark"] .filter-section .form-control:focus {
    background-color: #0f1114 !important;
    border-color: var(--primary-color) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .filter-section .btn-outline-secondary {
    background-color: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .filter-section .btn-outline-secondary:hover {
    background-color: var(--interface-hover) !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table thead {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table thead th {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .table tbody {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table tbody tr {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .table tbody tr:hover {
    background-color: var(--interface-hover) !important;
}

html[data-theme="dark"] .table td {
    background-color: transparent !important;
    color: var(--interface-text) !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .table td strong {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .text-muted {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .priority-badge {
    border: 1px solid var(--interface-border) !important;
}

html[data-theme="dark"] .priority-badge.urgent {
    background-color: rgba(239, 68, 68, 0.2) !important;
    color: #ef4444 !important;
    border-color: #ef4444 !important;
}

html[data-theme="dark"] .priority-badge.high {
    background-color: rgba(245, 158, 11, 0.2) !important;
    color: #f59e0b !important;
    border-color: #f59e0b !important;
}

html[data-theme="dark"] .priority-badge.medium {
    background-color: rgba(59, 130, 246, 0.2) !important;
    color: #3b82f6 !important;
    border-color: #3b82f6 !important;
}

html[data-theme="dark"] .priority-badge.low {
    background-color: rgba(100, 116, 139, 0.2) !important;
    color: #64748b !important;
    border-color: #64748b !important;
}

html[data-theme="dark"] .badge {
    border: 1px solid var(--interface-border) !important;
}

html[data-theme="dark"] .badge.bg-info {
    background-color: rgba(59, 130, 246, 0.2) !important;
    color: #3b82f6 !important;
    border-color: #3b82f6 !important;
}

html[data-theme="dark"] .alert-badge.active {
    background-color: rgba(236, 72, 153, 0.2) !important;
    color: #ec4899 !important;
    border-color: #ec4899 !important;
}

html[data-theme="dark"] .alert-badge.acknowledged {
    background-color: rgba(34, 197, 94, 0.2) !important;
    color: #22c55e !important;
    border-color: #22c55e !important;
}

html[data-theme="dark"] .alert-badge.resolved {
    background-color: rgba(34, 197, 94, 0.2) !important;
    color: #22c55e !important;
    border-color: #22c55e !important;
}

html[data-theme="dark"] .alert-badge.dismissed {
    background-color: rgba(100, 116, 139, 0.2) !important;
    color: #64748b !important;
    border-color: #64748b !important;
}

html[data-theme="dark"] .text-danger {
    color: #ef4444 !important;
}

html[data-theme="dark"] .text-success {
    color: #22c55e !important;
}

html[data-theme="dark"] .text-warning {
    color: #f59e0b !important;
}

html[data-theme="dark"] .alert-actions .btn {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .alert-actions .btn:hover {
    background: var(--interface-hover) !important;
    border-color: var(--primary-color) !important;
}

html[data-theme="dark"] .alert-actions .btn.btn-success:hover {
    background-color: rgba(34, 197, 94, 0.2) !important;
    border-color: #22c55e !important;
    color: #22c55e !important;
}

html[data-theme="dark"] .alert-actions .btn.btn-danger:hover {
    background-color: rgba(239, 68, 68, 0.2) !important;
    border-color: #ef4444 !important;
    color: #ef4444 !important;
}

html[data-theme="dark"] .alert-actions .btn.btn-info:hover {
    background-color: rgba(59, 130, 246, 0.2) !important;
    border-color: #3b82f6 !important;
    color: #3b82f6 !important;
}

html[data-theme="dark"] .form-check-input {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .form-check-input:checked {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
}

html[data-theme="dark"] .modal-content {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .modal-header {
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .modal-title {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .modal-footer {
    border-top-color: var(--interface-border) !important;
}

html[data-theme="dark"] .modal-body {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .modal-body strong {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .alert {
    background-color: rgba(30, 41, 59, 0.8) !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .alert-info {
    background-color: rgba(31, 178, 213, 0.1) !important;
    border-color: var(--primary-color) !important;
    color: var(--interface-text) !important;
}

</style>

<!-- View Alert Modal -->
<div class="modal fade" id="viewAlertModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewAlertTitle">Alert Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Employee:</strong>
                        <p id="viewAlertEmployee" class="mb-0"></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Alert Type:</strong>
                        <p id="viewAlertType" class="mb-0"></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Priority:</strong>
                        <p class="mb-0"><span id="viewAlertPriority" class="badge"></span></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong>
                        <p class="mb-0"><span id="viewAlertStatus" class="badge"></span></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Due Date:</strong>
                        <p id="viewAlertDueDate" class="mb-0"></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Created:</strong>
                        <p id="viewAlertCreated" class="mb-0"></p>
                    </div>
                </div>
                <div class="mb-3">
                    <strong>Description:</strong>
                    <p id="viewAlertDescription" class="mb-0"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
