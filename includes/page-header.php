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
];

$pageSubtitle = $pageSubtitles[$page] ?? 'Manage your HR operations';

// Only show header for HR Admin and Super Admin portals
if ($userRole === 'hr_admin' || $userRole === 'super_admin' || $userRole === 'developer'):
?>
<div class="hrdash-welcome">
    <div class="hrdash-welcome__left">
        <h2 class="hrdash-welcome__title"><?php echo htmlspecialchars($pageTitle); ?></h2>
    </div>
    <div class="hrdash-welcome__actions">
        <span id="current-time-global" class="hrdash-welcome__time"><?php echo strtoupper(date('g:i A')); ?></span>

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
                            <a class="dropdown-item hrdash-notification-item" href="?page=alerts&from=header">
                                <div class="d-flex align-items-start">
                                    <i class="fas <?php echo $priorityIcon; ?> <?php echo $priorityClass; ?> me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small"><?php echo htmlspecialchars($msg['title'] ?? 'Alert'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($employeeName); ?></div>
                                        <?php if ($timeAgo): ?>
                                            <div class="text-muted fs-11"><?php echo $timeAgo; ?></div>
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
        // Get current user ID for notification status
        $currentUserId = $_SESSION['user_id'] ?? null;

        // Get different types of notifications
        $recentNotifications = [];
        $licenseNotifications = [];
        $clearanceNotifications = [];
        $totalNotifications = 0;

        // Get expiring/expired licenses
        if (function_exists('get_license_notifications')) {
            try {
                $licenseNotifications = get_license_notifications($currentUserId, 60);
                $licenseNotifications = array_slice($licenseNotifications, 0, 5);
            } catch (Exception $e) {
                $licenseNotifications = [];
            }
        }

        // Get expiring/expired clearances (RLM)
        if (function_exists('get_clearance_notifications')) {
            try {
                $clearanceNotifications = get_clearance_notifications($currentUserId, 60);
                $clearanceNotifications = array_slice($clearanceNotifications, 0, 5);
            } catch (Exception $e) {
                $clearanceNotifications = [];
            }
        }

        // Get other alerts
        if (function_exists('get_employee_alerts')) {
            try {
                $recentNotifications = get_employee_alerts('active', null, $currentUserId);
                $recentNotifications = array_slice($recentNotifications, 0, 5);
            } catch (Exception $e) {
                $recentNotifications = [];
            }
        }

        // Calculate total count
        if (function_exists('get_unread_notification_count') && $currentUserId) {
            try {
                $totalNotifications = get_unread_notification_count($currentUserId);
            } catch (Exception $e) {
                $totalNotifications = count($licenseNotifications) + count($clearanceNotifications) + count($recentNotifications);
            }
        } else {
            $totalNotifications = count($licenseNotifications) + count($clearanceNotifications) + count($recentNotifications);
        }
        ?>
        <div class="dropdown">
            <button class="hrdash-welcome__icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications" aria-label="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($totalNotifications > 0): ?>
                    <span class="hrdash-welcome__badge"><?php echo $totalNotifications > 99 ? '99+' : $totalNotifications; ?></span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end hrdash-notification-dropdown" id="notificationDropdown" style="max-height: 500px; overflow-y: auto; min-width: 380px;">
                <li class="dropdown-header d-flex justify-content-between align-items-center">
                    <strong>Notifications</strong>
                    <div>
                        <?php if ($totalNotifications > 0): ?>
                            <button class="btn btn-sm btn-link text-decoration-none p-0 me-2" onclick="markAllNotificationsRead(event)" title="Mark all as read">
                                <i class="fas fa-check-double"></i>
                            </button>
                            <button class="btn btn-sm btn-link text-decoration-none p-0 me-2" onclick="clearAllNotifications(event)" title="Clear all">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php endif; ?>
                        <a href="?page=alerts&from=header" class="text-decoration-none">View All</a>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>

                <?php if (empty($licenseNotifications) && empty($clearanceNotifications) && empty($recentNotifications)): ?>
                    <li class="dropdown-item-text text-muted text-center py-3">
                        <i class="far fa-bell-slash fa-2x mb-2 d-block"></i>
                        <small>No new notifications</small>
                    </li>
                <?php else: ?>

                    <!-- Expiring/Expired Licenses -->
                    <?php if (!empty($licenseNotifications)): ?>
                        <li class="dropdown-header"><small class="text-uppercase fw-bold">License Expiry</small></li>
                        <?php foreach ($licenseNotifications as $license):
                            $priorityClass = '';
                            $priorityIcon = 'fa-id-card';
                            $statusText = '';
                            $daysUntil = (int)$license['days_until_expiry'];

                            if ($daysUntil < 0) {
                                $priorityClass = 'text-danger';
                                $priorityIcon = 'fa-exclamation-triangle';
                                $statusText = 'Expired ' . abs($daysUntil) . ' days ago';
                            } elseif ($daysUntil <= 7) {
                                $priorityClass = 'text-danger';
                                $priorityIcon = 'fa-exclamation-circle';
                                $statusText = 'Expires in ' . $daysUntil . ' days';
                            } elseif ($daysUntil <= 15) {
                                $priorityClass = 'text-warning';
                                $priorityIcon = 'fa-exclamation-circle';
                                $statusText = 'Expires in ' . $daysUntil . ' days';
                            } else {
                                $priorityClass = 'text-info';
                                $statusText = 'Expires in ' . $daysUntil . ' days';
                            }

                            $employeeName = trim(($license['surname'] ?? '') . ', ' . ($license['first_name'] ?? ''));
                            $isRead = !empty($license['is_read']);
                        ?>
                            <li class="notification-item <?php echo $isRead ? 'read' : 'unread'; ?>" data-notification-id="license_<?php echo $license['employee_id']; ?>" data-notification-type="license">
                                <div class="dropdown-item hrdash-notification-item position-relative">
                                    <div class="d-flex align-items-start">
                                        <i class="fas <?php echo $priorityIcon; ?> <?php echo $priorityClass; ?> me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($employeeName); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($license['license_no'] ?? 'No license #'); ?></div>
                                            <div class="<?php echo $priorityClass; ?> fs-11"><?php echo $statusText; ?></div>
                                        </div>
                                        <div class="notification-actions ms-2">
                                            <?php if (!$isRead): ?>
                                                <button class="btn btn-sm btn-link p-0 me-1" onclick="markNotificationRead(event, 'license_<?php echo $license['employee_id']; ?>', 'license')" title="Mark as read">
                                                    <i class="fas fa-check text-success"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-link p-0" onclick="dismissNotification(event, 'license_<?php echo $license['employee_id']; ?>', 'license')" title="Dismiss">
                                                <i class="fas fa-times text-muted"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>

                    <!-- Expiring/Expired Clearances (RLM) -->
                    <?php if (!empty($clearanceNotifications)): ?>
                        <li class="dropdown-header"><small class="text-uppercase fw-bold">Clearance Expiry (RLM)</small></li>
                        <?php foreach ($clearanceNotifications as $clearance):
                            $priorityClass = '';
                            $priorityIcon = 'fa-file-alt';
                            $statusText = '';
                            $daysUntil = (int)$clearance['days_until_expiry'];

                            if ($daysUntil < 0) {
                                $priorityClass = 'text-danger';
                                $priorityIcon = 'fa-exclamation-triangle';
                                $statusText = 'Expired ' . abs($daysUntil) . ' days ago';
                            } elseif ($daysUntil <= 14) {
                                $priorityClass = 'text-danger';
                                $priorityIcon = 'fa-exclamation-circle';
                                $statusText = 'Expires in ' . $daysUntil . ' days';
                            } elseif ($daysUntil <= 30) {
                                $priorityClass = 'text-warning';
                                $priorityIcon = 'fa-exclamation-circle';
                                $statusText = 'Expires in ' . $daysUntil . ' days';
                            } else {
                                $priorityClass = 'text-info';
                                $statusText = 'Expires in ' . $daysUntil . ' days';
                            }

                            $employeeName = trim(($clearance['surname'] ?? '') . ', ' . ($clearance['first_name'] ?? ''));
                            $isRead = !empty($clearance['is_read']);
                        ?>
                            <li class="notification-item <?php echo $isRead ? 'read' : 'unread'; ?>" data-notification-id="clearance_<?php echo $clearance['employee_id']; ?>" data-notification-type="clearance">
                                <div class="dropdown-item hrdash-notification-item position-relative">
                                    <div class="d-flex align-items-start">
                                        <i class="fas <?php echo $priorityIcon; ?> <?php echo $priorityClass; ?> me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($employeeName); ?></div>
                                            <div class="text-muted small">RLM Clearance</div>
                                            <div class="<?php echo $priorityClass; ?> fs-11"><?php echo $statusText; ?></div>
                                        </div>
                                        <div class="notification-actions ms-2">
                                            <?php if (!$isRead): ?>
                                                <button class="btn btn-sm btn-link p-0 me-1" onclick="markNotificationRead(event, 'clearance_<?php echo $clearance['employee_id']; ?>', 'clearance')" title="Mark as read">
                                                    <i class="fas fa-check text-success"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-link p-0" onclick="dismissNotification(event, 'clearance_<?php echo $clearance['employee_id']; ?>', 'clearance')" title="Dismiss">
                                                <i class="fas fa-times text-muted"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>

                    <!-- Other Alerts -->
                    <?php if (!empty($recentNotifications)): ?>
                        <li class="dropdown-header"><small class="text-uppercase fw-bold">Employee Alerts</small></li>
                        <?php foreach ($recentNotifications as $notif):
                            $priorityClass = '';
                            $priorityIcon = 'fa-info-circle';
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

                            $employeeName = trim(($notif['surname'] ?? '') . ', ' . ($notif['first_name'] ?? ''));
                            $isRead = !empty($notif['is_read']);
                        ?>
                            <li class="notification-item <?php echo $isRead ? 'read' : 'unread'; ?>" data-notification-id="<?php echo $notif['id']; ?>" data-notification-type="alert">
                                <div class="dropdown-item hrdash-notification-item position-relative">
                                    <div class="d-flex align-items-start">
                                        <i class="fas <?php echo $priorityIcon; ?> <?php echo $priorityClass; ?> me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($notif['title'] ?? 'Alert'); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($employeeName); ?></div>
                                            <?php if ($timeAgo): ?>
                                                <div class="text-muted fs-11"><?php echo $timeAgo; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="notification-actions ms-2">
                                            <?php if (!$isRead): ?>
                                                <button class="btn btn-sm btn-link p-0 me-1" onclick="markNotificationRead(event, '<?php echo $notif['id']; ?>', 'alert')" title="Mark as read">
                                                    <i class="fas fa-check text-success"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-link p-0" onclick="dismissNotification(event, '<?php echo $notif['id']; ?>', 'alert')" title="Dismiss">
                                                <i class="fas fa-times text-muted"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php endif; ?>
            </ul>
        </div>

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
                ?>
                <span class="hrdash-welcome__avatar"><?php echo htmlspecialchars($initials); ?></span>
                <i class="fas fa-chevron-down hrdash-welcome__chevron"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
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
