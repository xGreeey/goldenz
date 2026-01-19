<?php
$page_title = 'Task - Golden Z-5 HR System';
$page = 'tasks';

// Get current user
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;
$current_user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'System Administrator';

// Handle task actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $task_id = (int)($_POST['task_id'] ?? 0);
        
        switch ($_POST['action']) {
            case 'create':
                $task_data = [
                    'task_title' => $_POST['task_title'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'category' => $_POST['category'] ?? 'Other',
                    'assigned_by' => $current_user_id,
                    'assigned_by_name' => $current_user_name,
                    'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                    'priority' => $_POST['priority'] ?? 'medium',
                    'urgency_level' => $_POST['urgency_level'] ?? 'normal',
                    'location_page' => $_POST['location_page'] ?? '',
                    'notes' => $_POST['notes'] ?? '',
                    'assigned_to' => $current_user_id,
                    'status' => 'pending'
                ];
                
                if (function_exists('create_task')) {
                    $result = create_task($task_data);
                    if ($result) {
                        redirect_with_message('?page=tasks', 'Task created successfully!', 'success');
                    } else {
                        redirect_with_message('?page=tasks', 'Failed to create task.', 'danger');
                    }
                }
                break;
                
            case 'update':
                $task_data = [
                    'task_title' => $_POST['task_title'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'category' => $_POST['category'] ?? 'Other',
                    'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                    'priority' => $_POST['priority'] ?? 'medium',
                    'urgency_level' => $_POST['urgency_level'] ?? 'normal',
                    'location_page' => $_POST['location_page'] ?? '',
                    'notes' => $_POST['notes'] ?? '',
                    'status' => $_POST['status'] ?? 'pending',
                    'assigned_to' => $current_user_id
                ];
                
                if (function_exists('update_task')) {
                    $result = update_task($task_id, $task_data);
                    if ($result) {
                        redirect_with_message('?page=tasks', 'Task updated successfully!', 'success');
                    } else {
                        redirect_with_message('?page=tasks', 'Failed to update task.', 'danger');
                    }
                }
                break;
                
            case 'delete':
                // Only allow deletion if task is completed
                if (function_exists('get_task')) {
                    $task = get_task($task_id);
                    if ($task && strtolower($task['status'] ?? '') === 'completed') {
                        if (function_exists('delete_task')) {
                            $result = delete_task($task_id);
                            if ($result) {
                                redirect_with_message('?page=tasks', 'Task deleted successfully!', 'success');
                            } else {
                                redirect_with_message('?page=tasks', 'Failed to delete task.', 'danger');
                            }
                        }
                    } else {
                        redirect_with_message('?page=tasks', 'Only completed tasks can be deleted.', 'warning');
                    }
                }
                break;
        }
        exit;
    }
}

// Auto-generate tasks for employees needing updates
// DISABLED - Task generation is turned off
// if (function_exists('generate_employee_update_tasks')) {
//     // Task generation disabled
// }

// Remove all auto-generated tasks (where assigned_by_name = 'System')
if (isset($_GET['remove_auto_tasks']) && $_GET['remove_auto_tasks'] === '1') {
    try {
        $sql = "DELETE FROM hr_tasks WHERE assigned_by_name = 'System'";
        $stmt = execute_query($sql);
        $deleted_count = $stmt->rowCount();
        redirect_with_message('?page=tasks', "Successfully removed $deleted_count auto-generated task(s).", 'success');
    } catch (Exception $e) {
        error_log("Error removing auto-generated tasks: " . $e->getMessage());
        redirect_with_message('?page=tasks', 'Error removing auto-generated tasks.', 'danger');
    }
}

// Auto-remove auto-generated tasks on first load (one-time cleanup)
if (!isset($_SESSION['auto_tasks_cleaned'])) {
    try {
        $sql = "DELETE FROM hr_tasks WHERE assigned_by_name = 'System'";
        $stmt = execute_query($sql);
        $deleted_count = $stmt->rowCount();
        $_SESSION['auto_tasks_cleaned'] = true;
        if ($deleted_count > 0) {
            // Silent cleanup - don't show message to avoid interruption
            error_log("Auto-cleaned $deleted_count auto-generated task(s)");
        }
    } catch (Exception $e) {
        error_log("Error auto-cleaning tasks: " . $e->getMessage());
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Get task statistics
$task_stats = [];
if (function_exists('get_task_statistics')) {
    $task_stats = get_task_statistics();
} else {
    $task_stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'needs_action' => 0,
        'overdue' => 0,
        'urgent' => 0,
        'high_priority' => 0
    ];
}

// Get all tasks
$tasks = [];
if (function_exists('get_all_tasks')) {
    $tasks = get_all_tasks($status_filter ?: null, $priority_filter ?: null, $category_filter ?: null);
}

// Helper function to get priority badge class
function getPriorityBadgeClass($priority) {
    $classes = [
        'urgent' => 'bg-danger',
        'high' => 'bg-warning text-dark',
        'medium' => 'bg-info',
        'low' => 'bg-secondary'
    ];
    return $classes[$priority] ?? 'bg-secondary';
}

// Helper function to get urgency badge class
function getUrgencyBadgeClass($urgency) {
    $classes = [
        'critical' => 'bg-danger',
        'important' => 'bg-warning text-dark',
        'normal' => 'bg-secondary'
    ];
    return $classes[$urgency] ?? 'bg-secondary';
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'bg-warning text-dark',
        'in_progress' => 'bg-info',
        'completed' => 'bg-success',
        'cancelled' => 'bg-secondary'
    ];
    return $classes[$status] ?? 'bg-secondary';
}

// Helper function to format date
function formatTaskDate($date) {
    if (!$date || $date === '0000-00-00' || $date === '') {
        return 'N/A';
    }
    return date('M d, Y', strtotime($date));
}

// Helper function to check if task is overdue
function isTaskOverdue($due_date, $status) {
    if ($status === 'completed' || $status === 'cancelled') {
        return false;
    }
    if (!$due_date || $due_date === '0000-00-00' || $due_date === '') {
        return false;
    }
    return strtotime($due_date) < strtotime('today');
}
?>

<div class="container-fluid hrdash">
    <!-- Page Header -->
    <div class="page-header-modern">
        <div class="page-title">
            <div class="d-flex align-items-center gap-2">
                <h1 class="mb-0">Task</h1>
                <button type="button" class="btn btn-link p-0 text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="bottom" 
                        data-bs-content="Tasks are automatically generated when employee records need updates. Tasks can only be removed once they are accomplished (completed). Click on the location page link to go directly to the employee record." 
                        title="How Tasks Work" class="fs-lg" style="line-height: 1;">
                    <i class="fas fa-circle-question"></i>
                </button>
            </div>
            <p class="text-muted mb-0">Manage your assigned tasks and priorities</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="fas fa-plus me-2"></i>Create Task
            </button>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="row g-4">
        <div class="col-12">
    <div class="alert alert-info">
        <i class="fas fa-circle-info me-2"></i>
        <strong>Auto-Generated Tasks:</strong> Tasks are automatically created when employee records require updates (missing required fields, expired licenses, etc.). Click on the location page link to go directly to the employee record.
    </div>

    </div>
    </div>

    <!-- Task Statistics -->
    <div class="row g-4">
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Total Tasks</span>
                        <i class="fas fa-tasks text-muted small"></i>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="mb-0"><?php echo number_format($task_stats['total'] ?? 0); ?></h3>
                        <span class="badge bg-primary-subtle text-primary fw-semibold">All</span>
                    </div>
                    <small class="text-muted">All task records</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Needs Action</span>
                        <i class="fas fa-circle-exclamation text-warning"></i>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="mb-0 text-warning"><?php echo number_format($task_stats['needs_action'] ?? 0); ?></h3>
                        <span class="badge bg-warning-subtle text-warning fw-semibold">
                            <?php echo ($task_stats['total'] ?? 0) > 0 ? round((($task_stats['needs_action'] ?? 0) / $task_stats['total']) * 100) : 0; ?>%
                        </span>
                    </div>
                    <small class="text-muted">Pending + In Progress</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Overdue</span>
                        <i class="fas fa-clock text-danger"></i>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="mb-0 text-danger"><?php echo number_format($task_stats['overdue'] ?? 0); ?></h3>
                        <span class="badge bg-danger-subtle text-danger fw-semibold">Urgent</span>
                    </div>
                    <small class="text-muted">Past due date</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Completed</span>
                        <i class="fas fa-circle-check text-success"></i>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="mb-0 text-success"><?php echo number_format($task_stats['completed'] ?? 0); ?></h3>
                        <span class="badge bg-success-subtle text-success fw-semibold">
                            <?php echo ($task_stats['total'] ?? 0) > 0 ? round((($task_stats['completed'] ?? 0) / $task_stats['total']) * 100) : 0; ?>%
                        </span>
                    </div>
                    <small class="text-muted">Finished tasks</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Stats Row -->
    <div class="row g-4">
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small mb-1">Pending</div>
                    <h4 class="mb-0 text-warning"><?php echo number_format($task_stats['pending'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small mb-1">In Progress</div>
                    <h4 class="mb-0 text-info"><?php echo number_format($task_stats['in_progress'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small mb-1">Urgent</div>
                    <h4 class="mb-0 text-danger"><?php echo number_format($task_stats['urgent'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small mb-1">High Priority</div>
                    <h4 class="mb-0" style="color: #f59e0b;"><?php echo number_format($task_stats['high_priority'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small mb-1">Cancelled</div>
                    <h4 class="mb-0 text-secondary"><?php echo number_format($task_stats['cancelled'] ?? 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small mb-1">Completion Rate</div>
                    <h4 class="mb-0 text-success">
                        <?php 
                        $completion_rate = ($task_stats['total'] ?? 0) > 0 
                            ? round((($task_stats['completed'] ?? 0) / $task_stats['total']) * 100) 
                            : 0;
                        echo $completion_rate . '%';
                        ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row g-4">
        <div class="col-12">
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">Filter by Status</label>
                    <select class="form-select form-select-sm" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Filter by Category</label>
                    <select class="form-select form-select-sm" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="Employee Record" <?php echo $category_filter === 'Employee Record' ? 'selected' : ''; ?>>Employee Record</option>
                        <option value="License" <?php echo $category_filter === 'License' ? 'selected' : ''; ?>>License</option>
                        <option value="Leave Request" <?php echo $category_filter === 'Leave Request' ? 'selected' : ''; ?>>Leave Request</option>
                        <option value="Clearance" <?php echo $category_filter === 'Clearance' ? 'selected' : ''; ?>>Clearance</option>
                        <option value="Cash Bond" <?php echo $category_filter === 'Cash Bond' ? 'selected' : ''; ?>>Cash Bond</option>
                        <option value="Other" <?php echo $category_filter === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Filter by Priority</label>
                    <select class="form-select form-select-sm" id="priorityFilter">
                        <option value="">All Priorities</option>
                        <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary btn-sm" id="clearFilters">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" id="selectAllTasks" class="form-check-input">
                            </th>
                            <th style="width: 120px;">Task Number</th>
                            <th style="width: 150px;">Category</th>
                            <th style="width: 200px;">Task Title</th>
                            <th>Description</th>
                            <th>Due Date / Priority</th>
                            <th>Location Page</th>
                            <th>Notes</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 150px;">Assigned By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No tasks found</h5>
                                    <p class="text-muted mb-0">Create your first task to get started.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): 
                                $is_overdue = isTaskOverdue($task['due_date'] ?? '', $task['status'] ?? '');
                            ?>
                            <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input task-checkbox" value="<?php echo $task['id']; ?>">
                                </td>
                                <td>
                                    <strong class="text-primary"><?php echo htmlspecialchars($task['task_number'] ?? 'N/A'); ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    $category = $task['category'] ?? 'Other';
                                    $category_badges = [
                                        'Employee Record' => 'bg-info',
                                        'License' => 'bg-warning',
                                        'Leave Request' => 'bg-success',
                                        'Clearance' => 'bg-primary',
                                        'Cash Bond' => 'bg-secondary',
                                        'Other' => 'bg-dark'
                                    ];
                                    $badge_class = $category_badges[$category] ?? 'bg-dark';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($category); ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($task['task_title'] ?? 'N/A'); ?></div>
                                </td>
                                <td style="word-wrap: break-word; word-break: break-word;">
                                    <div class="text-muted small" style="white-space: normal; line-height: 1.5;">
                                        <?php echo nl2br(htmlspecialchars($task['description'] ?? 'N/A')); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <small class="text-muted d-block">Due:</small>
                                        <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                            <?php echo formatTaskDate($task['due_date'] ?? ''); ?>
                                        </span>
                                    </div>
                                    <div class="mb-1">
                                        <span class="badge <?php echo getPriorityBadgeClass($task['priority'] ?? 'medium'); ?>">
                                            <?php echo strtoupper($task['priority'] ?? 'medium'); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="badge <?php echo getUrgencyBadgeClass($task['urgency_level'] ?? 'normal'); ?>">
                                            <?php echo strtoupper($task['urgency_level'] ?? 'normal'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($task['location_page'])): ?>
                                        <a href="<?php echo htmlspecialchars($task['location_page']); ?>" class="text-decoration-none" target="_blank">
                                            <i class="fas fa-external-link-alt me-1"></i>
                                            <?php echo htmlspecialchars($task['location_page']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td style="word-wrap: break-word; word-break: break-word;">
                                    <div class="text-muted small" style="white-space: normal; line-height: 1.5;">
                                        <?php echo nl2br(htmlspecialchars($task['notes'] ?? 'N/A')); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($task['status'] ?? 'pending'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'] ?? 'pending')); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="employee-avatar me-2 fs-xs" style="width: 32px; height: 32px;">
                                            <?php 
                                            $name = $task['assigned_by_name'] ?? 'System';
                                            $initials = '';
                                            if (strpos($name, ' ') !== false) {
                                                $parts = explode(' ', $name);
                                                $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
                                            } else {
                                                $initials = strtoupper(substr($name, 0, 2));
                                            }
                                            echo $initials;
                                            ?>
                                        </div>
                                        <div>
                                            <div class="small"><?php echo htmlspecialchars($task['assigned_by_name'] ?? 'System'); ?></div>
                                        </div>
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
</div>

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="createTaskForm">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Task Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="task_title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="category" required>
                            <option value="Employee Record">Employee Record</option>
                            <option value="License">License</option>
                            <option value="Leave Request">Leave Request</option>
                            <option value="Clearance">Clearance</option>
                            <option value="Cash Bond">Cash Bond</option>
                            <option value="Other" selected>Other</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Urgency Level</label>
                            <select class="form-select" name="urgency_level">
                                <option value="normal" selected>Normal</option>
                                <option value="important">Important</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location Page (Where can it be found)</label>
                            <input type="text" class="form-control" name="location_page" placeholder="e.g., ?page=employees&id=14">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Notes created by the person who alerted"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editTaskForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="task_id" id="edit_task_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Task Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="task_title" id="edit_task_title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="category" id="edit_category" required>
                            <option value="Employee Record">Employee Record</option>
                            <option value="License">License</option>
                            <option value="Leave Request">Leave Request</option>
                            <option value="Clearance">Clearance</option>
                            <option value="Cash Bond">Cash Bond</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date" id="edit_due_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority" id="edit_priority">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Urgency Level</label>
                            <select class="form-select" name="urgency_level" id="edit_urgency_level">
                                <option value="normal">Normal</option>
                                <option value="important">Important</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location Page (Where can it be found)</label>
                        <input type="text" class="form-control" name="location_page" id="edit_location_page">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="edit_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Filter functionality
document.getElementById('statusFilter')?.addEventListener('change', function() {
    const url = new URL(window.location);
    if (this.value) {
        url.searchParams.set('status', this.value);
    } else {
        url.searchParams.delete('status');
    }
    window.location.href = url.toString();
});

document.getElementById('categoryFilter')?.addEventListener('change', function() {
    const url = new URL(window.location);
    if (this.value) {
        url.searchParams.set('category', this.value);
    } else {
        url.searchParams.delete('category');
    }
    window.location.href = url.toString();
});

document.getElementById('priorityFilter')?.addEventListener('change', function() {
    const url = new URL(window.location);
    if (this.value) {
        url.searchParams.set('priority', this.value);
    } else {
        url.searchParams.delete('priority');
    }
    window.location.href = url.toString();
});

document.getElementById('clearFilters')?.addEventListener('click', function() {
    window.location.href = '?page=tasks';
});

// Note: Edit and Delete functions removed as Actions column has been removed.
// Tasks can only be deleted when status is "completed" via backend.

// Initialize Bootstrap popover
var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
});

// Select all checkbox
document.getElementById('selectAllTasks')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.task-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

<style>
/* Table responsive - no horizontal scroll */
.table-responsive {
    overflow-x: hidden;
    overflow-y: visible;
    width: 100%;
    max-width: 100%;
}

/* Table styling */
.table {
    width: 100%;
    max-width: 100%;
    table-layout: auto;
}

.table thead th {
    white-space: normal;
    word-wrap: break-word;
    padding: 0.625rem 0.75rem;
    font-size: 0.75rem;
}

/* Ensure description and notes columns display full text */
.table tbody td {
    vertical-align: top;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.table tbody td:nth-child(6), /* Description column */
.table tbody td:nth-child(9) { /* Notes column */
    word-wrap: break-word;
    word-break: break-word;
    white-space: normal;
    max-width: 0;
}

.table tbody td:nth-child(6) div,
.table tbody td:nth-child(9) div {
    white-space: normal;
    line-height: 1.6;
    max-width: 100%;
}

/* Ensure table cells have proper padding for wrapped text */
.table tbody td {
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
}

/* Task Statistics Cards Styling */
.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 12px;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}

.stat-card .card-body {
    padding: 1.25rem;
}

.stat-card h3 {
    font-size: 1.75rem;
    font-weight: 700;
}

.stat-card .badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
}

/* Badge color utilities */
.bg-primary-subtle {
    background-color: #e9f2ff !important;
    color: #1fb2d5 !important;
}

.bg-warning-subtle {
    background-color: #fff7e6 !important;
    color: #f59e0b !important;
}

.bg-danger-subtle {
    background-color: #ffe9e9 !important;
    color: #ef4444 !important;
}

.bg-success-subtle {
    background-color: #e9f7ef !important;
    color: #16a34a !important;
}

/* Responsive adjustments for stats */
@media (max-width: 768px) {
    .stat-card h3 {
        font-size: 1.5rem;
    }
    
    .stat-card .card-body {
        padding: 1rem;
    }
}
</style>

<?php
// Handle AJAX request for task details
if (isset($_GET['action']) && $_GET['action'] === 'get_task' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $task_id = (int)$_GET['id'];
    if (function_exists('get_task')) {
        $task = get_task($task_id);
        if ($task) {
            echo json_encode(['success' => true, 'task' => $task]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Task not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Function not available']);
    }
    exit;
}
?>

