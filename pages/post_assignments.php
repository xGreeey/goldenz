<?php
// Get post ID from URL
$post_id = $_GET['post_id'] ?? null;
$selected_post = null;

if ($post_id) {
    $selected_post = get_post_by_id($post_id);
}

// Get all posts for dropdown
$all_posts = get_posts(['status' => 'Active']);

// Build a lookup of currently assigned employees per post title
$assigned_counts = [];
try {
    $count_stmt = execute_query(
        "SELECT post, COUNT(*) AS cnt
         FROM employees
         WHERE status = 'Active' AND post IS NOT NULL AND post <> ''
         GROUP BY post"
    );
    foreach ($count_stmt->fetchAll() as $row) {
        $assigned_counts[$row['post']] = (int)$row['cnt'];
    }
} catch (Exception $e) {
    // Fail silently; we'll fall back to 0
    $assigned_counts = [];
}

// Add computed fields expected by the UI to each post
foreach ($all_posts as &$p) {
    $required = (int)($p['required_count'] ?? 0);
    $current = (int)($assigned_counts[$p['post_title']] ?? 0);
    $p['current_employees'] = $current;
    $p['remaining_vacancies'] = max(0, $required - $current);
}
unset($p);

// Get employees assigned to specific post
$assigned_employees = [];
if ($post_id) {
    $sql = "SELECT * FROM employees WHERE post = ? AND status = 'Active' ORDER BY first_name, surname";
    $stmt = execute_query($sql, [$selected_post['post_title']]);
    $assigned_employees = $stmt->fetchAll();

    // Add computed fields to selected post for the info panel
    $required = (int)($selected_post['required_count'] ?? 0);
    $current = (int)count($assigned_employees);
    $selected_post['current_employees'] = $current;
    $selected_post['remaining_vacancies'] = max(0, $required - $current);
}

// Get all employees for assignment
$all_employees = get_employees();
?>

<div class="container-fluid hrdash">
    <!-- Header Section with Actions -->
    <?php if (($_SESSION['user_role'] ?? '') === 'hr_admin'): ?>
    <div class="hrdash-welcome">
        <div class="hrdash-welcome__left">
            <h2 class="hrdash-welcome__title">
                <i class="fas fa-users-cog me-2"></i>Post Assignments
            </h2>
            <p class="hrdash-welcome__subtitle">Manage employee assignments to posts and locations</p>
        </div>
        <div class="hrdash-welcome__actions">
            <span id="current-time-post-assignments" class="hrdash-welcome__time"><?php echo strtolower(date('h:i A')); ?></span>
            
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
    <?php endif; ?>

    <!-- Breadcrumb -->
    <nav class="hr-breadcrumb" aria-label="Breadcrumb">
        <ol class="hr-breadcrumb__list">
            <li class="hr-breadcrumb__item">
                <a href="?page=dashboard" class="hr-breadcrumb__link">Dashboard</a>
            </li>
            <li class="hr-breadcrumb__item">
                <a href="?page=posts" class="hr-breadcrumb__link">Posts &amp; Locations</a>
            </li>
            <li class="hr-breadcrumb__item hr-breadcrumb__current" aria-current="page">
                Post Assignments
            </li>
        </ol>
    </nav>

    <!-- Post Selection -->
    <div class="row g-4">
        <div class="col-12">
    <div class="card card-modern">
        <div class="card-header-modern">
            <h5 class="card-title-modern">Select Post to Manage</h5>
        </div>
        <div class="card-body-modern">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="postSelect" class="form-label-modern">Choose a Post</label>
                    <select class="form-select-modern" id="postSelect" onchange="loadPostAssignments(this.value)">
                        <option value="">Select a post to view assignments...</option>
                        <?php foreach ($all_posts as $post): ?>
                            <option value="<?php echo $post['id']; ?>" 
                                    <?php echo $post_id == $post['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($post['post_title']); ?> 
                                (<?php echo htmlspecialchars($post['location']); ?>)
                                - <?php echo $post['current_employees']; ?>/<?php echo $post['required_count']; ?> filled
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label-modern">&nbsp;</label>
                    <div>
                        <button class="btn btn-primary-modern w-100" onclick="loadPostAssignments(document.getElementById('postSelect').value)">
                            <i class="fas fa-search me-2"></i>Load Assignments
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($selected_post): ?>
        <!-- Post Information -->
        <div class="row g-4">
            <div class="col-12">
        <div class="card card-modern">
            <div class="card-header-modern">
                <h5 class="card-title-modern">Post Information</h5>
            </div>
            <div class="card-body-modern">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="post-title-main"><?php echo htmlspecialchars($selected_post['post_title']); ?></h6>
                        <p class="post-info-item mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo htmlspecialchars($selected_post['location']); ?>
                        </p>
                        <p class="post-info-item mb-0">
                            <i class="fas fa-building me-2"></i>
                            <?php echo htmlspecialchars($selected_post['department']); ?> - 
                            <?php 
                            $type_labels = ['SG' => 'Security Guard', 'LG' => 'Lady Guard', 'SO' => 'Security Officer'];
                            echo $type_labels[$selected_post['employee_type']] ?? $selected_post['employee_type'];
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-label">Positions Required:</span>
                            <strong class="info-value"><?php echo $selected_post['required_count']; ?></strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Currently Filled:</span>
                            <strong class="info-value text-success"><?php echo $selected_post['current_employees']; ?></strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Remaining Vacancies:</span>
                            <strong class="info-value text-warning"><?php echo $selected_post['remaining_vacancies']; ?></strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Priority:</span>
                            <span class="priority-badge priority-<?php echo strtolower($selected_post['priority']); ?>">
                                <?php echo htmlspecialchars($selected_post['priority']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
        </div>

        <!-- Assignment Management -->
        <div class="row g-4">
            <!-- Currently Assigned Employees -->
            <div class="col-lg-6">
                <div class="card card-modern h-100">
                    <div class="card-header-modern d-flex justify-content-between align-items-center">
                        <h5 class="card-title-modern mb-0">Currently Assigned (<?php echo count($assigned_employees); ?>)</h5>
                        <span class="badge badge-success-modern"><?php echo count($assigned_employees); ?>/<?php echo $selected_post['required_count']; ?></span>
                    </div>
                    <div class="card-body-modern">
                        <?php if (empty($assigned_employees)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-2x text-muted mb-3"></i>
                                <p class="text-muted">No employees assigned to this post yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="assigned-employees-list">
                                <?php foreach ($assigned_employees as $employee): ?>
                                    <div class="employee-assignment-item">
                                        <div class="d-flex align-items-center">
                                            <div class="employee-avatar me-3">
                                                <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['surname'], 0, 1)); ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['surname']); ?></h6>
                                                <small class="text-muted">
                                                    Employee #<?php echo $employee['employee_no']; ?> | 
                                                    <?php echo $employee['employee_type']; ?>
                                                </small>
                                            </div>
                                            <div class="assignment-actions">
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="removeAssignment(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($selected_post['post_title']); ?>')"
                                                        title="Remove Assignment">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Available Employees for Assignment -->
            <div class="col-lg-6">
                <div class="card card-modern h-100">
                    <div class="card-header-modern">
                        <h5 class="card-title-modern mb-0">Available Employees</h5>
                    </div>
                    <div class="card-body-modern">
                        <div class="mb-3">
                            <div class="search-input-modern">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" id="employeeSearch" class="search-field" placeholder="Search employees..." 
                                       onkeyup="filterEmployees(this.value)">
                            </div>
                        </div>
                        
                        <div class="available-employees-list" id="availableEmployeesList">
                            <?php 
                            $assigned_employee_ids = array_column($assigned_employees, 'id');
                            $available_employees = array_filter($all_employees, function($emp) use ($assigned_employee_ids) {
                                return !in_array($emp['id'], $assigned_employee_ids) && $emp['status'] === 'Active';
                            });
                            ?>
                            
                            <?php if (empty($available_employees)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-user-plus fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">No available employees to assign.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($available_employees as $employee): ?>
                                    <div class="employee-item" data-employee-id="<?php echo $employee['id']; ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="employee-avatar me-3">
                                                <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['surname'], 0, 1)); ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['surname']); ?></h6>
                                                <small class="text-muted">
                                                    Employee #<?php echo $employee['employee_no']; ?> | 
                                                    <?php echo $employee['employee_type']; ?> | 
                                                    Current: <?php echo htmlspecialchars($employee['post']); ?>
                                                </small>
                                            </div>
                                            <div class="assignment-actions">
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="assignEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($selected_post['post_title']); ?>')"
                                                        title="Assign to Post">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- No Post Selected -->
        <div class="row g-4">
            <div class="col-12">
        <div class="card card-modern">
            <div class="card-body-modern text-center py-5">
                <i class="fas fa-map-marker-alt fa-3x text-muted mb-4"></i>
                <h4 class="empty-state-title">Select a Post</h4>
                <p class="empty-state-text">Choose a post from the dropdown above to view and manage employee assignments.</p>
            </div>
        </div>
        </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Update time display every minute for post assignments page (HR Admin only)
<?php if (($_SESSION['user_role'] ?? '') === 'hr_admin'): ?>
(function() {
    function updateTime() {
        const timeElement = document.getElementById('current-time-post-assignments');
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
<?php endif; ?>
</script>

<style>
/* ============================================
   MODERN POST ASSIGNMENTS PAGE STYLES
   ============================================ */

/* Hide the main header with black background */
.main-content .header {
    display: none !important;
}

/* Container */
.post-assignments-modern {
    /* Use portal-wide spacing system (font-override.css) instead of page-local padding */
    padding: 0;
    max-width: 100%;
    overflow-x: hidden;
    min-height: 100vh;
    background: #ffffff; /* default for non HR-Admin portals */
}

/* HR-Admin: use light separated background */
body.portal-hr-admin .post-assignments-modern {
    background: #f8fafc;
}

/* Page Header */
.page-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.page-title-modern {
    flex: 1;
}

.page-title-main {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    letter-spacing: -0.02em;
}

.page-subtitle {
    font-size: 0.9375rem;
    color: #64748b;
    margin: 0;
    font-weight: 400;
}

.page-actions-modern {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

/* Buttons */
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
}

.btn-primary-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.35);
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
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
}

.btn-outline-modern:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Cards */
.card-modern {
    border: none;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    background: #ffffff;
    overflow: hidden;
    transition: all 0.3s ease;
}

.card-modern:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
}

.card-header-modern {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 1.25rem 1.5rem;
}

.card-title-modern {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    letter-spacing: -0.01em;
}

.card-body-modern {
    padding: 1.5rem;
}

/* Form Controls */
.form-label-modern {
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
    margin-bottom: 0.5rem;
    display: block;
}

.form-select-modern {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #475569;
    background: #ffffff;
    transition: all 0.2s ease;
    cursor: pointer;
}

.form-select-modern:focus {
    outline: none;
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

/* Search Input */
.search-input-modern {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 1rem;
    color: #94a3b8;
    font-size: 0.875rem;
    z-index: 2;
}

.search-field {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #475569;
    background: #ffffff;
    transition: all 0.2s ease;
}

.search-field:focus {
    outline: none;
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

/* Post Info */
.post-title-main {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
}

.post-info-item {
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.post-info-item i {
    color: #94a3b8;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #64748b;
    font-size: 0.875rem;
}

.info-value {
    color: #1e293b;
    font-weight: 600;
    font-size: 0.9375rem;
}

/* Employee Items */
.employee-assignment-item,
.employee-item {
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 0.75rem;
    background: #ffffff;
    transition: all 0.2s ease;
}

.employee-assignment-item:hover,
.employee-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-color: #1fb2d5;
    transform: translateX(2px);
}

.employee-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.2);
}

.employee-assignment-item h6,
.employee-item h6 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
}

.employee-assignment-item small,
.employee-item small {
    color: #64748b;
    font-size: 0.8125rem;
}

.assignment-actions .btn {
    padding: 0.5rem;
    min-width: 36px;
    border-radius: 6px;
    transition: all 0.2s ease;
    border: 1.5px solid;
}

.assignment-actions .btn-sm {
    font-size: 0.875rem;
}

.assignment-actions .btn-primary {
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    border-color: #1fb2d5;
    color: #ffffff;
}

.assignment-actions .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.3);
}

.assignment-actions .btn-outline-danger {
    border-color: #ef4444;
    color: #ef4444;
    background: #ffffff;
}

.assignment-actions .btn-outline-danger:hover {
    background: #fee2e2;
    border-color: #ef4444;
    color: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
}

/* Priority Badge */
.priority-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    border: none;
    display: inline-block;
}

.priority-badge.priority-urgent {
    background: #fee2e2;
    color: #dc2626;
}

.priority-badge.priority-high {
    background: #fef3c7;
    color: #d97706;
}

.priority-badge.priority-medium {
    background: #dbeafe;
    color: #2563eb;
}

.priority-badge.priority-low {
    background: #f1f5f9;
    color: #64748b;
}

/* Badges */
.badge-success-modern {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    background: #dcfce7;
    color: #16a34a;
}

/* Lists */
.assigned-employees-list,
.available-employees-list {
    max-height: 500px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.assigned-employees-list::-webkit-scrollbar,
.available-employees-list::-webkit-scrollbar {
    width: 6px;
}

.assigned-employees-list::-webkit-scrollbar-track,
.available-employees-list::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.assigned-employees-list::-webkit-scrollbar-thumb,
.available-employees-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.assigned-employees-list::-webkit-scrollbar-thumb:hover,
.available-employees-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.employee-item.hidden {
    display: none;
}

/* Empty State */
.empty-state-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.empty-state-text {
    color: #64748b;
    font-size: 0.9375rem;
}

/* Responsive */
@media (max-width: 768px) {
    .post-assignments-modern {
        padding: 1.5rem 1rem;
    }
    
    .page-header-modern {
        flex-direction: column;
        gap: 1rem;
    }
    
    .page-actions-modern {
        width: 100%;
        justify-content: flex-start;
    }
    
    .card-body-modern {
        padding: 1rem;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}

/* Dark theme support for Post Assignments page */
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

html[data-theme="dark"] .btn-outline-modern {
    background-color: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .btn-outline-modern:hover {
    background-color: var(--interface-hover) !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-modern {
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

html[data-theme="dark"] .card-body-modern {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .form-label-modern {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .form-select-modern {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .form-select-modern option {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .search-input-modern {
    background: transparent !important;
}

html[data-theme="dark"] .search-field {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .search-field::placeholder {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .search-icon {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .post-title-main {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .post-info-item {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .post-info-item i {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .info-row {
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .info-label {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .info-value {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .employee-assignment-item,
html[data-theme="dark"] .employee-item {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .employee-assignment-item:hover,
html[data-theme="dark"] .employee-item:hover {
    background: var(--interface-hover) !important;
    border-color: var(--primary-color) !important;
}

html[data-theme="dark"] .employee-assignment-item h6,
html[data-theme="dark"] .employee-item h6 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .employee-assignment-item small,
html[data-theme="dark"] .employee-item small {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .assignment-actions .btn-outline-danger {
    background: #1a1d23 !important;
    border-color: #ef4444 !important;
    color: #ef4444 !important;
}

html[data-theme="dark"] .assignment-actions .btn-outline-danger:hover {
    background: rgba(239, 68, 68, 0.2) !important;
    border-color: #ef4444 !important;
    color: #ef4444 !important;
}

html[data-theme="dark"] .empty-state-title {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .empty-state-text {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .text-muted {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .assigned-employees-list::-webkit-scrollbar-track,
html[data-theme="dark"] .available-employees-list::-webkit-scrollbar-track {
    background: #0f1114 !important;
}

html[data-theme="dark"] .assigned-employees-list::-webkit-scrollbar-thumb,
html[data-theme="dark"] .available-employees-list::-webkit-scrollbar-thumb {
    background: var(--interface-border) !important;
}

html[data-theme="dark"] .assigned-employees-list::-webkit-scrollbar-thumb:hover,
html[data-theme="dark"] .available-employees-list::-webkit-scrollbar-thumb:hover {
    background: var(--interface-text-muted) !important;
}
</style>

<script>
function loadPostAssignments(postId) {
    if (postId) {
        window.location.href = '?page=post_assignments&post_id=' + postId;
    }
}

function filterEmployees(searchTerm) {
    const employeeItems = document.querySelectorAll('.employee-item');
    const term = searchTerm.toLowerCase();
    
    employeeItems.forEach(item => {
        const employeeName = item.querySelector('h6').textContent.toLowerCase();
        const employeeNumber = item.querySelector('small').textContent.toLowerCase();
        
        if (employeeName.includes(term) || employeeNumber.includes(term)) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
}

function assignEmployee(employeeId, postTitle) {
    if (confirm('Are you sure you want to assign this employee to the post?')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=post_assignments&post_id=<?php echo $post_id; ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'assign';
        
        const employeeInput = document.createElement('input');
        employeeInput.type = 'hidden';
        employeeInput.name = 'employee_id';
        employeeInput.value = employeeId;
        
        const postInput = document.createElement('input');
        postInput.type = 'hidden';
        postInput.name = 'post_title';
        postInput.value = postTitle;
        
        form.appendChild(actionInput);
        form.appendChild(employeeInput);
        form.appendChild(postInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function removeAssignment(employeeId, postTitle) {
    if (confirm('Are you sure you want to remove this employee from the post?')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=post_assignments&post_id=<?php echo $post_id; ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'remove';
        
        const employeeInput = document.createElement('input');
        employeeInput.type = 'hidden';
        employeeInput.name = 'employee_id';
        employeeInput.value = employeeId;
        
        const postInput = document.createElement('input');
        postInput.type = 'hidden';
        postInput.name = 'post_title';
        postInput.value = postTitle;
        
        form.appendChild(actionInput);
        form.appendChild(employeeInput);
        form.appendChild(postInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// Handle form submissions
if ($_POST['action'] ?? '' === 'assign') {
    $employee_id = $_POST['employee_id'] ?? 0;
    $post_title = $_POST['post_title'] ?? '';
    
    if ($employee_id && $post_title) {
        $sql = "UPDATE employees SET post = ? WHERE id = ?";
        if (execute_query($sql, [$post_title, $employee_id])) {
            echo '<script>alert("Employee assigned successfully!"); window.location.reload();</script>';
        } else {
            echo '<script>alert("Error assigning employee. Please try again.");</script>';
        }
    }
}

if ($_POST['action'] ?? '' === 'remove') {
    $employee_id = $_POST['employee_id'] ?? 0;
    $post_title = $_POST['post_title'] ?? '';
    
    if ($employee_id && $post_title) {
        $sql = "UPDATE employees SET post = 'Unassigned' WHERE id = ?";
        if (execute_query($sql, [$employee_id])) {
            echo '<script>alert("Employee removed from post successfully!"); window.location.reload();</script>';
        } else {
            echo '<script>alert("Error removing employee. Please try again.");</script>';
        }
    }
}
?>
