<?php
/**
 * HR Admin Header Section - Reusable component
 * Includes welcome message, notifications, messages, and profile dropdown
 */
if (($_SESSION['user_role'] ?? '') !== 'hr_admin') {
    return; // Only show for HR admin
}
?>
<div class="hrdash-welcome">
    <div class="hrdash-welcome__left">
        <h2 class="hrdash-welcome__title">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'HR Administrator'); ?></h2>
        <p class="hrdash-welcome__subtitle">Ready to manage your HR tasks today?</p>
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

<script>
// Update time display every minute
(function() {
    function updateTime() {
        const timeElement = document.getElementById('current-time');
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
