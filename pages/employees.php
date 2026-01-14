<?php
$page_title = 'Employees - Golden Z-5 HR System';
$page = 'employees';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $employee_id = (int)$_GET['id'];
    
    // Check if user has permission to delete
    if (!\App\Core\Auth::can('employees.delete')) {
        header('Location: ?page=employees&error=permission_denied');
        exit;
    }
    
    try {
        $result = delete_employee($employee_id);
        if ($result) {
            header('Location: ?page=employees&success=deleted');
            exit;
        } else {
            header('Location: ?page=employees&error=delete_failed');
            exit;
        }
    } catch (Exception $e) {
        header('Location: ?page=employees&error=delete_error');
        exit;
    }
}

// Handle AJAX request for employee details
if (isset($_GET['action']) && $_GET['action'] === 'get_employee' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $employee_id = (int)$_GET['id'];
    $employee = get_employee($employee_id);
    
    if ($employee) {
        echo json_encode(['success' => true, 'employee' => $employee]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
    exit;
}

// Get filter parameters from URL
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'name';
$sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'asc';

// Pagination parameters
$page_num = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($per_page, [10, 25, 50, 100])) {
    $per_page = 10;
}

// Helper function to get employment status (Probationary/Regular)
function getEmploymentStatus($date_hired) {
    if (!$date_hired || $date_hired === '0000-00-00') {
        return 'N/A';
    }
    $hired_date = strtotime($date_hired);
    $six_months_ago = strtotime('-6 months');
    return $hired_date >= $six_months_ago ? 'Probationary' : 'Regular';
}

// Helper function to get license expiration indicator
function getLicenseExpirationIndicator($exp_date) {
    if (!$exp_date || $exp_date === '0000-00-00' || $exp_date === '') {
        return ['class' => 'text-muted', 'badge' => '', 'text' => 'N/A'];
    }
    
    // Try to parse as date, if it fails, return as-is
    $exp_timestamp = strtotime($exp_date);
    if ($exp_timestamp === false) {
        // If not a valid date, return as string
        return ['class' => 'text-muted', 'badge' => '', 'text' => htmlspecialchars($exp_date)];
    }
    
    $now = strtotime('today');
    $days_until_exp = floor(($exp_timestamp - $now) / (60 * 60 * 24));
    
    if ($days_until_exp < 0) {
        return ['class' => 'text-danger fw-bold', 'badge' => 'bg-danger', 'text' => 'Expired (' . abs($days_until_exp) . ' days ago)', 'icon' => 'fa-exclamation-triangle'];
    } elseif ($days_until_exp <= 30) {
        return ['class' => 'text-danger fw-bold', 'badge' => 'bg-danger', 'text' => 'Expires in ' . $days_until_exp . ' days', 'icon' => 'fa-circle-exclamation'];
    } elseif ($days_until_exp <= 90) {
        return ['class' => 'text-warning fw-bold', 'badge' => 'bg-warning text-dark', 'text' => 'Expires in ' . $days_until_exp . ' days', 'icon' => 'fa-clock'];
    } else {
        return ['class' => 'text-success', 'badge' => '', 'text' => date('M d, Y', $exp_timestamp), 'icon' => ''];
    }
}

// Helper function to format license expiration date and status
function formatLicenseExpiration($exp_date) {
    if (!$exp_date || $exp_date === '0000-00-00' || $exp_date === '') {
        return null;
    }
    
    $exp_timestamp = strtotime($exp_date);
    if ($exp_timestamp === false) {
        return null;
    }
    
    // Format the date as "March 25, 2025"
    $formatted_date = date('F j, Y', $exp_timestamp);
    
    // Calculate days difference
    $now = strtotime('today');
    $days_until_exp = floor(($exp_timestamp - $now) / (60 * 60 * 24));
    
    // Create status text
    if ($days_until_exp < 0) {
        $status_text = 'Expired (' . abs($days_until_exp) . ' days ago)';
    } elseif ($days_until_exp <= 30) {
        $status_text = 'Expires in ' . $days_until_exp . ' days';
    } elseif ($days_until_exp <= 90) {
        $status_text = 'Expires in ' . $days_until_exp . ' days';
    } else {
        $status_text = 'Valid';
    }
    
    return [
        'text' => $formatted_date,
        'status_text' => $status_text,
        'days' => $days_until_exp
    ];
}

// Helper function to format RLM expiration date and status
function formatRLMExpiration($exp_date) {
    if (!$exp_date || $exp_date === '0000-00-00' || $exp_date === '') {
        return null;
    }
    
    $exp_timestamp = strtotime($exp_date);
    if ($exp_timestamp === false) {
        return null;
    }
    
    // Format the date as "March 25, 2025"
    $formatted_date = date('F j, Y', $exp_timestamp);
    
    // Calculate days difference
    $now = strtotime('today');
    $days_until_exp = floor(($exp_timestamp - $now) / (60 * 60 * 24));
    
    // Create status text
    if ($days_until_exp < 0) {
        $status_text = 'Expired (' . abs($days_until_exp) . ' days ago)';
    } elseif ($days_until_exp <= 30) {
        $status_text = 'Expires in ' . $days_until_exp . ' days';
    } elseif ($days_until_exp <= 90) {
        $status_text = 'Expires in ' . $days_until_exp . ' days';
    } else {
        $status_text = 'Valid';
    }
    
    return [
        'text' => $formatted_date,
        'status_text' => $status_text,
        'days' => $days_until_exp
    ];
}

// Helper function to get employee type label
function getEmployeeTypeLabel($type) {
    $types = [
        'SG' => 'Security Guard',
        'LG' => 'Lady Guard',
        'SO' => 'Security Officer'
    ];
    return $types[$type] ?? $type;
}

// Get all employees
$all_employees = get_employees();

// Apply filters
$filtered_employees = $all_employees;

// Search filter
if (!empty($search)) {
    $filtered_employees = array_filter($filtered_employees, function($emp) use ($search) {
        $search_lower = strtolower($search);
        return strpos(strtolower($emp['first_name'] . ' ' . $emp['surname']), $search_lower) !== false ||
               strpos(strtolower($emp['employee_no']), $search_lower) !== false ||
               strpos(strtolower($emp['post']), $search_lower) !== false ||
               (isset($emp['email']) && strpos(strtolower($emp['email']), $search_lower) !== false);
    });
}

// Status filter
if (!empty($status_filter)) {
    $filtered_employees = array_filter($filtered_employees, function($emp) use ($status_filter) {
        return strtolower($emp['status']) === strtolower($status_filter);
    });
}

// Employee type filter
if (!empty($type_filter)) {
    $filtered_employees = array_filter($filtered_employees, function($emp) use ($type_filter) {
        return $emp['employee_type'] === $type_filter;
    });
}

// Re-index array after filtering
$filtered_employees = array_values($filtered_employees);

// Apply sorting
usort($filtered_employees, function($a, $b) use ($sort_by, $sort_order) {
    $result = 0;
    
    switch($sort_by) {
        case 'name':
            $a_val = strtolower($a['first_name'] . ' ' . $a['surname']);
            $b_val = strtolower($b['first_name'] . ' ' . $b['surname']);
            $result = strcmp($a_val, $b_val);
            break;
        case 'employee_id':
            $result = strcmp($a['employee_no'], $b['employee_no']);
            break;
        case 'job_title':
            $result = strcmp($a['employee_type'], $b['employee_type']);
            break;
        case 'department':
            $result = strcmp($a['post'], $b['post']);
            break;
        case 'join_date':
            $a_date = strtotime($a['date_hired']);
            $b_date = strtotime($b['date_hired']);
            $result = $a_date - $b_date;
            break;
        case 'status':
            $result = strcmp(strtolower($a['status']), strtolower($b['status']));
            break;
        default:
            $result = 0;
    }
    
    return $sort_order === 'desc' ? -$result : $result;
});

// Calculate totals
$total_employees = count($filtered_employees);
$total_pages = $total_employees > 0 ? ceil($total_employees / $per_page) : 1;
$page_num = min($page_num, max(1, $total_pages)); // Ensure page is within range
$offset = ($page_num - 1) * $per_page;

// Get paginated employees
$paginated_employees = array_slice($filtered_employees, $offset, $per_page);

// Get statistics from database (accurate counts)
$employee_stats = get_employee_statistics();
$total_all_employees = $employee_stats['total_employees'];
$active_employees = $employee_stats['active_employees'];
$inactive_employees = $employee_stats['inactive_employees'];
$onboarding_employees = $employee_stats['onboarding_employees'];
?>

<div class="employees-modern">
    <!-- Page Header -->
    <div class="page-header-modern">
        <div class="page-title-modern">
            <h1 class="page-title-main">Employee Management</h1>
            <p class="page-subtitle-modern">Manage employee information and records</p>
        </div>
        <div class="page-actions-modern">
            <button class="btn btn-outline-modern" id="exportBtn" title="Export employee list">
                <i class="fas fa-download me-2"></i>Export CSV
            </button>
            <a href="?page=add_employee" class="btn btn-primary-modern">
                <i class="fas fa-plus me-2"></i>Add New Employee
            </a>
        </div>
    </div>

    <!-- Employee List (single view) -->
    <div class="tab-content">
        <div class="tab-pane active" id="employee-list">
    <!-- Success Message -->
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="alert alert-success-modern alert-dismissible fade show">
            <i class="fas fa-circle-check me-2"></i>
            Employee created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="summary-cards-modern">
        <div class="card stat-card-modern h-100">
            <div class="card-body-modern">
                <div class="stat-header">
                    <span class="stat-label">Total employees</span>
                    <i class="fas fa-users stat-icon"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo number_format($total_all_employees); ?></h3>
                    <span class="badge badge-success-modern">+<?php echo max(1, ($active_employees ?? 0) > 0 ? 1 : 0); ?>%</span>
                </div>
                <small class="stat-footer">vs last period</small>
            </div>
        </div>

        <div class="card stat-card-modern h-100">
            <div class="card-body-modern">
                <div class="stat-header">
                    <span class="stat-label">Active</span>
                    <i class="fas fa-user-check stat-icon"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo number_format($active_employees); ?></h3>
                    <span class="badge badge-primary-modern">
                        <?php echo ($total_all_employees ?? 0) > 0 ? round(($active_employees / max(1, $total_all_employees)) * 100) : 0; ?>%
                    </span>
                </div>
                <small class="stat-footer">Currently on roster</small>
            </div>
        </div>

        <div class="card stat-card-modern h-100">
            <div class="card-body-modern">
                <div class="stat-header">
                    <span class="stat-label">Inactive</span>
                    <i class="fas fa-user-times stat-icon text-warning"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number text-warning"><?php echo number_format($inactive_employees); ?></h3>
                    <span class="badge badge-warning-modern">Monitor</span>
                </div>
                <small class="stat-footer">Off roster</small>
            </div>
        </div>
    </div>

            <!-- Employee Table -->
            <div class="table-container">
                <table class="employees-table">
                        <thead>
                            <tr>
                            <th>
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th class="sortable" data-sort="name">
                                EMPLOYEE INFO
                                <i class="fas fa-sort ms-1"></i>
                            </th>
                            <th class="sortable" data-sort="department">
                                POST
                                <i class="fas fa-sort ms-1"></i>
                            </th>
                            <th class="sortable" data-sort="license_exp">
                                LICENSE & EXPIRY
                                <i class="fas fa-sort ms-1"></i>
                            </th>
                            <th class="sortable" data-sort="join_date">
                                EMPLOYMENT DETAILS
                                <i class="fas fa-sort ms-1"></i>
                            </th>
                            <th class="sortable" data-sort="status">
                                STATUS
                                <i class="fas fa-sort ms-1"></i>
                            </th>
                            <th>CREATED BY</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php if (empty($paginated_employees)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No employees found</h5>
                                        <p class="text-muted mb-0">
                                            <?php if (!empty($search) || !empty($status_filter) || !empty($type_filter)): ?>
                                                Try adjusting your filters or search criteria.
                                            <?php else: ?>
                                                No employees are currently in the system.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paginated_employees as $employee): 
                                $full_name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['surname'] ?? ''));
                                $employment_status = getEmploymentStatus($employee['date_hired'] ?? '');
                                $license_indicator = getLicenseExpirationIndicator($employee['license_exp_date'] ?? '');
                                $license_formatted = !empty($employee['license_exp_date']) ? formatLicenseExpiration($employee['license_exp_date']) : null;
                                $rlm_indicator = !empty($employee['rlm_exp']) ? getLicenseExpirationIndicator($employee['rlm_exp']) : null;
                                $rlm_formatted = !empty($employee['rlm_exp']) ? formatRLMExpiration($employee['rlm_exp']) : null;
                            ?>
                            <tr class="employee-row" data-employee-id="<?php echo $employee['id']; ?>" style="cursor: pointer;">
                                <td onclick="event.stopPropagation();">
                                    <input type="checkbox" class="form-check-input employee-checkbox" value="<?php echo $employee['id']; ?>">
                                </td>
                                <!-- Employee Info -->
                                <td>
                                    <div class="employee-info">
                                        <div class="employee-avatar">
                                            <?php echo strtoupper(substr($employee['first_name'] ?? '', 0, 1) . substr($employee['surname'] ?? '', 0, 1)); ?>
                                        </div>
                                        <div class="employee-details">
                                            <div class="employee-name fw-semibold"><?php echo htmlspecialchars($full_name); ?></div>
                                            <div class="employee-email text-muted small">
                                                <?php echo getEmployeeTypeLabel($employee['employee_type'] ?? ''); ?>
                                            </div>
                                            <div class="employee-number text-muted" style="font-size: 0.75rem; margin-top: 0.25rem;">
                                                #<?php echo htmlspecialchars($employee['employee_no'] ?? $employee['id']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Post -->
                                <td>
                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($employee['post'] ?? 'Unassigned'); ?></span>
                                </td>
                                <!-- License & Expiry -->
                                <td>
                                    <div class="license-info">
                                        <?php if (!empty($employee['license_no'])): ?>
                                            <div class="mb-2">
                                                <small class="text-muted d-block mb-1">License:</small>
                                                <strong><?php echo htmlspecialchars($employee['license_no']); ?></strong>
                                                <?php if ($license_formatted): ?>
                                                    <div class="mt-1">
                                                        <div class="<?php echo $license_indicator['class']; ?>">
                                                            <?php if (!empty($license_indicator['icon'])): ?>
                                                                <i class="fas <?php echo $license_indicator['icon']; ?> me-1"></i>
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars($license_formatted['status_text']); ?>
                                                        </div>
                                                        <div class="<?php echo ($license_formatted['days'] < 0 || $license_formatted['days'] <= 30) ? 'text-danger' : ''; ?>">
                                                            <?php echo htmlspecialchars($license_formatted['text']); ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-muted small mt-1">No expiration date</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="mb-2">
                                                <small class="text-muted d-block mb-1">License:</small>
                                                <span class="text-danger fw-bold">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>No License
                                                    <span class="badge bg-danger ms-1">URGENT</span>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($rlm_formatted && !empty($employee['rlm_exp'])): ?>
                                            <div>
                                                <small class="text-muted d-block mb-1">RLM:</small>
                                                <div>
                                                    <div class="<?php echo $rlm_indicator['class']; ?>">
                                                        <?php if (!empty($rlm_indicator['icon'])): ?>
                                                            <i class="fas <?php echo $rlm_indicator['icon']; ?> me-1"></i>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($rlm_formatted['status_text']); ?>
                                                    </div>
                                                    <div class="<?php echo ($rlm_formatted['days'] < 0 || $rlm_formatted['days'] <= 30) ? 'text-danger' : ''; ?>">
                                                        <?php echo htmlspecialchars($rlm_formatted['text']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <!-- Employment Details -->
                                <td>
                                    <div class="employment-details">
                                        <div class="mb-1">
                                            <small class="text-muted d-block">Hired:</small>
                                            <span><?php echo !empty($employee['date_hired']) ? date('M d, Y', strtotime($employee['date_hired'])) : 'N/A'; ?></span>
                                        </div>
                                        <div class="mb-1">
                                            <small class="text-muted d-block">Status:</small>
                                            <span class="badge <?php echo strtolower($employment_status) === 'probationary' ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                                <?php echo htmlspecialchars($employment_status); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Shift:</small>
                                            <span class="text-muted">N/A</span>
                                        </div>
                                    </div>
                                </td>
                                <!-- Status -->
                                <td>
                                    <span class="status-badge <?php echo strtolower($employee['status'] ?? ''); ?>">
                                        <i class="fas"></i>
                                        <?php echo htmlspecialchars($employee['status'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <!-- Created By -->
                                <td>
                                    <small class="text-muted" title="Created: <?php echo isset($employee['created_at']) ? date('M j, Y', strtotime($employee['created_at'])) : 'N/A'; ?>">
                                        <i class="fas fa-user-plus me-1"></i>
                                        <?php 
                                        if (isset($employee['created_by_name']) && !empty($employee['created_by_name'])) {
                                            echo htmlspecialchars($employee['created_by_name']);
                                        } elseif (isset($employee['created_by']) && $employee['created_by']) {
                                            // Try to get user name from database
                                            try {
                                                $pdo = get_db_connection();
                                                $stmt = $pdo->prepare("SELECT name, username FROM users WHERE id = ?");
                                                $stmt->execute([$employee['created_by']]);
                                                $user = $stmt->fetch();
                                                if ($user) {
                                                    echo htmlspecialchars($user['name'] ?? $user['username'] ?? 'User #' . $employee['created_by']);
                                                } else {
                                                    echo 'User #' . $employee['created_by'];
                                                }
                                            } catch (Exception $e) {
                                                echo 'System';
                                            }
                                        } else {
                                            echo 'System';
                                        }
                                        ?>
                                    </small>
                                </td>
                                </tr>
                                <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
            </div>

            <!-- Employee Details Modal -->
            <div class="modal fade" id="employeeDetailsModal" tabindex="-1" aria-labelledby="employeeDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="employeeDetailsModalLabel">
                                <i class="fas fa-user me-2"></i>Employee Details
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="employeeDetailsContent">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3 text-muted">Loading employee details...</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="#" id="editEmployeeBtn" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Edit Employee
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <select class="form-select form-select-sm" id="perPageSelect">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10 records</option>
                        <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25 records</option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50 records</option>
                        <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100 records</option>
                    </select>
                </div>
                <div class="pagination-controls">
                    <button class="btn btn-outline-secondary btn-sm" <?php echo $page_num <= 1 ? 'disabled' : ''; ?> onclick="changePage(<?php echo max(1, $page_num - 1); ?>)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <?php if ($total_pages > 0): 
                        $start_page = max(1, $page_num - 2);
                        $end_page = min($total_pages, $page_num + 2);
                    ?>
                        <?php if ($start_page > 1): ?>
                            <button class="btn btn-outline-secondary btn-sm" onclick="changePage(1)">1</button>
                            <?php if ($start_page > 2): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <button class="btn <?php echo $i == $page_num ? 'btn-primary' : 'btn-outline-secondary'; ?> btn-sm" onclick="changePage(<?php echo $i; ?>)">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                            <button class="btn btn-outline-secondary btn-sm" onclick="changePage(<?php echo $total_pages; ?>)"><?php echo $total_pages; ?></button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-primary btn-sm" disabled>1</button>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline-secondary btn-sm" <?php echo $page_num >= $total_pages ? 'disabled' : ''; ?> onclick="changePage(<?php echo min($total_pages, $page_num + 1); ?>)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="pagination-info">
                    <span><?php echo $total_employees > 0 ? ($offset + 1) : 0; ?> - <?php echo min($offset + $per_page, $total_employees); ?> of <?php echo $total_employees; ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* Modern Employees Page Styling */
.employees-modern {
    padding: 1rem 2.5rem 2rem 2.5rem;
    max-width: 100%;
    overflow-x: hidden;
    background: #f8fafc;
    min-height: 100vh;
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
}

.stat-footer {
    font-size: 0.8125rem;
    color: #94a3b8;
    display: block;
    margin-top: 0.5rem;
}

/* Badges */
.badge-success-modern,
.badge-primary-modern,
.badge-warning-modern,
.badge-danger-modern {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    letter-spacing: 0.01em;
}

.badge-success-modern {
    background: #dcfce7;
    color: #16a34a;
}

.badge-primary-modern {
    background: #dbeafe;
    color: #2563eb;
}

.badge-warning-modern {
    background: #fef3c7;
    color: #d97706;
}

.badge-danger-modern {
    background: #fee2e2;
    color: #dc2626;
}

/* Table Container */
.table-container {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-bottom: 1.5rem;
    margin-top: 0;
    width: 100%;
    box-sizing: border-box;
}

/* Employees Table */
.employees-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.employees-table thead {
    background: #f8fafc;
}

.employees-table thead th {
    padding: 1rem 1.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.employees-table thead th.sortable {
    cursor: pointer;
    user-select: none;
    transition: color 0.2s ease;
}

.employees-table thead th.sortable:hover {
    color: #1fb2d5;
}

.employees-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}

.employees-table tbody tr:last-child {
    border-bottom: none;
}

.employees-table tbody td {
    padding: 1rem 1.25rem;
    vertical-align: middle;
    color: #475569;
    font-size: 0.875rem;
}

/* Employee Info */
.employees-table .employee-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.employees-table .employee-avatar {
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

.employees-table .employee-details {
    flex: 1;
    min-width: 0;
}

.employees-table .employee-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
    line-height: 1.3;
    font-size: 0.9375rem;
}

.employees-table .employee-email {
    color: #64748b;
    font-size: 0.8125rem;
    line-height: 1.3;
    margin-top: 0.125rem;
}

/* Employee Detail Modal Styling */
.employee-row {
    transition: all 0.2s ease;
}

.employee-row:hover {
    background-color: #f8fafc;
    transform: translateX(2px);
}

.license-info {
    min-width: 180px;
    line-height: 1.6;
}

.license-info > div {
    margin-bottom: 0.5rem;
}

.license-info > div:last-child {
    margin-bottom: 0;
}

.license-info small {
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.25rem;
    color: #6c757d;
}

.license-info strong {
    font-size: 0.875rem;
    font-weight: 600;
    display: block;
    margin-bottom: 0.25rem;
}

.employment-details {
    min-width: 150px;
    line-height: 1.6;
}

.employment-details > div {
    margin-bottom: 0.5rem;
}

.employment-details > div:last-child {
    margin-bottom: 0;
}

.employment-details small {
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.25rem;
    color: #6c757d;
}

.employment-details span:not(.badge) {
    font-size: 0.875rem;
    display: block;
}

/* Employee info spacing in table */
.employees-table .employee-info {
    gap: 0.75rem;
}

.employees-table .employee-avatar {
    flex-shrink: 0;
}

.employees-table .employee-name {
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.employees-table .employee-email {
    line-height: 1.3;
    margin-top: 0.125rem;
}

/* Badge spacing */
.employees-table .badge {
    margin-top: 0.125rem;
    margin-bottom: 0.125rem;
    display: inline-block;
}

/* Post badge spacing */
.employees-table td:nth-child(3) .badge {
    margin: 0;
}

/* Status badge spacing */
.employees-table td:nth-child(6) .status-badge {
    margin: 0;
}

.employee-detail-view {
    padding: 0.5rem;
}

.employee-detail-view h5 {
    color: var(--interface-text);
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--interface-border);
}

.employee-detail-view label.text-muted {
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
    display: block;
}

.employee-detail-view .fw-semibold {
    font-weight: 600;
    color: var(--interface-text);
    font-size: 0.9375rem;
}

.employee-avatar-large {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
    flex-shrink: 0;
}

#employeeDetailsModal .modal-body {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

#employeeDetailsModal .modal-xl {
    max-width: 1200px;
}

/* Modern Alert Styling */
.alert-success-modern {
    background-color: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
    border-radius: 8px;
    padding: 0.875rem 1rem;
    margin-bottom: 1.25rem;
    font-size: 0.875rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.alert-success-modern .btn-close {
    filter: brightness(0) saturate(100%) invert(27%) sepia(95%) saturate(1234%) hue-rotate(95deg) brightness(95%) contrast(87%);
}

/* Pagination Modern Styling */
.pagination-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    padding: 1rem;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.pagination-controls .btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.pagination-controls .btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pagination-controls .btn-primary {
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    border: none;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.25);
}

.pagination-controls .btn-primary:hover {
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.35);
}

.pagination-info {
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .employees-modern {
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
    
    .table-container {
        overflow-x: auto;
    }
}
</style>

<script>
// Employee Table Filtering and Sorting System
class EmployeeTableManager {
    constructor() {
        this.searchTimeout = null;
        this.bindEvents();
        this.updateSortIcons();
    }
    
    // Helper function to update URL and reload
    updateURL(params = {}) {
        const url = new URL(window.location);
        
        // Get current parameters
        const currentParams = {
            page: url.searchParams.get('page') || 'employees',
            search: url.searchParams.get('search') || '',
            status: url.searchParams.get('status') || '',
            type: url.searchParams.get('type') || '',
            sort_by: url.searchParams.get('sort_by') || 'name',
            sort_order: url.searchParams.get('sort_order') || 'asc',
            page_num: url.searchParams.get('page_num') || '1',
            per_page: url.searchParams.get('per_page') || '10'
        };
        
        // Merge with new parameters
        Object.assign(currentParams, params);
        
        // Reset to page 1 when filters change (unless explicitly setting page_num)
        if (params.search !== undefined || params.status !== undefined || params.type !== undefined || 
            params.sort_by !== undefined || params.sort_order !== undefined) {
            if (params.page_num === undefined) {
                currentParams.page_num = '1';
            }
        }
        
        // Build new URL
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('page', currentParams.page);
        
        // Add parameters only if they have values
        if (currentParams.search) newUrl.searchParams.set('search', currentParams.search);
        else newUrl.searchParams.delete('search');
        
        if (currentParams.status) newUrl.searchParams.set('status', currentParams.status);
        else newUrl.searchParams.delete('status');
        
        if (currentParams.type) newUrl.searchParams.set('type', currentParams.type);
        else newUrl.searchParams.delete('type');
        
        if (currentParams.sort_by !== 'name') newUrl.searchParams.set('sort_by', currentParams.sort_by);
        else newUrl.searchParams.delete('sort_by');
        
        if (currentParams.sort_order !== 'asc') newUrl.searchParams.set('sort_order', currentParams.sort_order);
        else newUrl.searchParams.delete('sort_order');
        
        if (currentParams.page_num !== '1') newUrl.searchParams.set('page_num', currentParams.page_num);
        else newUrl.searchParams.delete('page_num');
        
        if (currentParams.per_page !== '10') newUrl.searchParams.set('per_page', currentParams.per_page);
        else newUrl.searchParams.delete('per_page');
        
        // Reload page with new parameters
        window.location.href = newUrl.toString();
    }
    
    bindEvents() {
        // Search input with debounce
        const searchInput = document.getElementById('employeeSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.updateURL({ search: e.target.value.trim() });
                }, 500); // Wait 500ms after user stops typing
            });
        }
        
        // Status filter
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.updateURL({ status: e.target.value });
            });
        }
        
        // Department/Type filter
        const deptFilter = document.getElementById('departmentFilter');
        if (deptFilter) {
            deptFilter.addEventListener('change', (e) => {
                this.updateURL({ type: e.target.value });
            });
        }
        
        // Sort controls
        const sortBy = document.getElementById('sortBy');
        if (sortBy) {
            sortBy.addEventListener('change', (e) => {
                const url = new URL(window.location);
                const currentOrder = url.searchParams.get('sort_order') || 'asc';
                this.updateURL({ sort_by: e.target.value, sort_order: currentOrder });
            });
        }
        
        const sortOrder = document.getElementById('sortOrder');
        if (sortOrder) {
            sortOrder.addEventListener('change', (e) => {
                const url = new URL(window.location);
                const currentSort = url.searchParams.get('sort_by') || 'name';
                this.updateURL({ sort_by: currentSort, sort_order: e.target.value });
            });
        }
        
        // Clear filters
        const clearBtn = document.getElementById('clearFilters');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.updateURL({ search: '', status: '', type: '', sort_by: 'name', sort_order: 'asc', page_num: '1' });
            });
        }
        
        // Select all functionality
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                const checkboxes = document.querySelectorAll('.employee-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = e.target.checked;
                });
            });
        }
        
        // Column header sorting
        document.querySelectorAll('.sortable').forEach(header => {
            header.addEventListener('click', () => {
                const column = header.dataset.sort;
                const url = new URL(window.location);
                const currentSort = url.searchParams.get('sort_by') || 'name';
                const currentOrder = url.searchParams.get('sort_order') || 'asc';
                
                let newOrder = 'asc';
                if (currentSort === column) {
                    newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                }
                
                // Update sort controls
                if (sortBy) sortBy.value = column;
                if (sortOrder) sortOrder.value = newOrder;
                
                this.updateURL({ sort_by: column, sort_order: newOrder });
            });
        });
        
        // Export functionality - export current page data
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportToCSV();
            });
        }
        
        // Records per page
        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', (e) => {
                this.updateURL({ per_page: e.target.value, page_num: '1' });
            });
        }
    }
    
    updateSortIcons() {
        const url = new URL(window.location);
        const sortBy = url.searchParams.get('sort_by') || 'name';
        const sortOrder = url.searchParams.get('sort_order') || 'asc';
        
        // Remove all sort icons
        document.querySelectorAll('.sortable i').forEach(icon => {
            icon.className = 'fas fa-sort ms-1';
        });
        
        // Add active sort icon
        const activeHeader = document.querySelector(`[data-sort="${sortBy}"]`);
        if (activeHeader) {
            const icon = activeHeader.querySelector('i');
            if (icon) {
                icon.className = sortOrder === 'asc' ? 'fas fa-sort-up ms-1' : 'fas fa-sort-down ms-1';
            }
        }
    }
    
    exportToCSV() {
        // Get all visible employee data from the table
        const rows = document.querySelectorAll('.employees-table tbody tr');
        let csv = 'Name,Email,Employee ID,Employee Type,Post/Assignment,Join Date,Status\n';
        
        rows.forEach(row => {
            const name = row.querySelector('.employee-name')?.textContent.trim() || '';
            const email = row.querySelector('.employee-email')?.textContent.trim() || '';
            const employeeId = row.cells[2]?.textContent.trim() || '';
            const jobTitle = row.cells[3]?.textContent.trim() || '';
            const department = row.cells[4]?.textContent.trim() || '';
            const joinDate = row.cells[5]?.textContent.trim() || '';
            const status = row.cells[6]?.textContent.trim() || '';
            
            csv += `"${name}","${email}","${employeeId}","${jobTitle}","${department}","${joinDate}","${status}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `employees_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
}

// Tab Management System
class TabManager {
    constructor() {
        this.currentTab = 'employee-list';
        this.directoryManager = null;
        this.orgChartManager = null;
        this.bindTabEvents();
    }
    
    bindTabEvents() {
        const tabButtons = document.querySelectorAll('.tab-button');
        if (!tabButtons.length) return;
        
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const tabId = button.dataset.tab || button.getAttribute('data-tab');
                if (tabId) {
                    this.switchTab(tabId);
                }
            });
        });
    }
    
    switchTab(tabId) {
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        const activeButton = document.querySelector(`[data-tab="${tabId}"]`);
        if (activeButton) {
            activeButton.classList.add('active');
        }
        
        // Update tab panes
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
            pane.style.display = 'none';
        });
        const activePane = document.getElementById(tabId);
        if (activePane) {
            activePane.classList.add('active');
            activePane.style.display = 'block';
        }
        
        this.currentTab = tabId;
        
        // Initialize managers when their tabs are shown
        if (tabId === 'directory' && !this.directoryManager) {
            setTimeout(() => {
                this.directoryManager = new DirectoryManager();
            }, 100);
        }
        if (tabId === 'org-chart' && !this.orgChartManager) {
            setTimeout(() => {
                this.orgChartManager = new OrgChartManager();
            }, 100);
        }
    }
}

// Directory Management System
class DirectoryManager {
    constructor() {
        this.allEmployees = [];
        this.filteredEmployees = [];
        this.filters = {
            search: '',
            status: '',
            department: ''
        };
        
        this.initializeDirectory();
        this.bindEvents();
    }
    
    initializeDirectory() {
        const cards = document.querySelectorAll('.directory-card');
        if (!cards.length) return;
        
        this.allEmployees = Array.from(cards).map(card => ({
            element: card,
            name: card.dataset.name || '',
            status: card.dataset.status || '',
            department: card.dataset.department || '' // This is employee_type (SG, LG, SO)
        }));
        
        this.filteredEmployees = [...this.allEmployees];
    }
    
    bindEvents() {
        // Search input
        const searchInput = document.getElementById('directorySearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filters.search = e.target.value.toLowerCase();
                this.applyFilters();
            });
        }
        
        // Status filter
        const statusFilter = document.getElementById('directoryStatusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.filters.status = e.target.value;
                this.applyFilters();
            });
        }
        
        // Department filter
        const deptFilter = document.getElementById('directoryDepartmentFilter');
        if (deptFilter) {
            deptFilter.addEventListener('change', (e) => {
                this.filters.department = e.target.value;
                this.applyFilters();
            });
        }
        
        // Export button
        const exportBtn = document.getElementById('exportDirectoryBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportToCSV();
            });
        }
    }
    
    applyFilters() {
        this.filteredEmployees = this.allEmployees.filter(employee => {
            const matchesSearch = !this.filters.search || 
                employee.name.includes(this.filters.search);
            
            const matchesStatus = !this.filters.status || employee.status === this.filters.status;
            // Filter by employee type (department contains SG, LG, SO)
            const matchesDepartment = !this.filters.department || employee.department === this.filters.department;
            
            return matchesSearch && matchesStatus && matchesDepartment;
        });
        
        this.updateDisplay();
    }
    
    updateDisplay() {
        // Hide all cards first
        this.allEmployees.forEach(employee => {
            employee.element.style.display = 'none';
        });
        
        // Show filtered cards
        this.filteredEmployees.forEach(employee => {
            employee.element.style.display = 'block';
        });
    }
    
    exportToCSV() {
        let csv = 'Name,Employee Type,Employee ID,Email,Phone,Status,Post/Assignment\n';
        
        this.filteredEmployees.forEach(employee => {
            const card = employee.element;
            const name = card.querySelector('.employee-name').textContent.trim();
            const title = card.querySelector('.employee-title').textContent.trim();
            const id = card.querySelector('.employee-id').textContent.replace('ID: ', '').trim();
            const email = card.querySelector('.employee-email').textContent.trim();
            const phone = card.querySelector('.employee-phone').textContent.trim();
            const status = employee.status;
            const department = employee.department;
            
            csv += `"${name}","${title}","${id}","${email}","${phone}","${status}","${department}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `directory_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
}

// ORG Chart Management System
class OrgChartManager {
    constructor() {
        this.expanded = true;
        this.bindEvents();
        this.initializeOrgChart();
    }
    
    initializeOrgChart() {
        // Make org chart nodes clickable
        const orgNodes = document.querySelectorAll('.org-node');
        orgNodes.forEach(node => {
            node.style.cursor = 'pointer';
            node.addEventListener('click', (e) => {
                e.stopPropagation();
                this.handleNodeClick(node);
            });
            
            // Add hover effect
            node.addEventListener('mouseenter', () => {
                node.style.transform = 'scale(1.05)';
                node.style.transition = 'transform 0.2s';
            });
            
            node.addEventListener('mouseleave', () => {
                node.style.transform = 'scale(1)';
            });
        });
    }
    
    handleNodeClick(node) {
        const nodeInfo = node.querySelector('.node-info');
        if (nodeInfo) {
            const title = nodeInfo.querySelector('h4, h5')?.textContent || '';
            const subtitle = nodeInfo.querySelector('p')?.textContent || '';
            
            // Show employee details or navigate
            if (node.classList.contains('employee')) {
                const employeeId = node.dataset.employeeId;
                if (employeeId) {
                    // Navigate to employee view page
                    window.location.href = `?page=view_employee&id=${employeeId}`;
                } else {
                    // Fallback: show employee name
                    const employeeName = node.dataset.employeeName || title.trim();
                    if (confirm(`View details for ${employeeName}?`)) {
                        // Try to find employee by name or show search
                        console.log('Employee:', employeeName);
                    }
                }
            } else {
                // For management/department nodes, show info in a more elegant way
                const info = `${title}\n${subtitle}`;
                // You could replace this with a modal or tooltip
                console.log('Department/Management:', info);
            }
        }
    }
    
    bindEvents() {
        const expandBtn = document.getElementById('expandAll');
        if (expandBtn) {
            expandBtn.addEventListener('click', () => {
                this.expandAll();
            });
        }
        
        const collapseBtn = document.getElementById('collapseAll');
        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                this.collapseAll();
            });
        }
    }
    
    expandAll() {
        const orgLevels = document.querySelectorAll('.org-level');
        orgLevels.forEach(level => {
            level.style.display = 'flex';
            level.style.opacity = '1';
        });
        this.expanded = true;
        this.updateButtonStates();
    }
    
    collapseAll() {
        // Hide lower levels (level 2 and 3)
        const level2 = document.querySelector('.org-level.level-2');
        const level3 = document.querySelector('.org-level.level-3');
        
        if (level2) level2.style.display = 'none';
        if (level3) level3.style.display = 'none';
        
        this.expanded = false;
        this.updateButtonStates();
    }
    
    updateButtonStates() {
        const expandBtn = document.getElementById('expandAll');
        const collapseBtn = document.getElementById('collapseAll');
        
        if (expandBtn && collapseBtn) {
            if (this.expanded) {
                expandBtn.disabled = true;
                collapseBtn.disabled = false;
            } else {
                expandBtn.disabled = false;
                collapseBtn.disabled = true;
            }
        }
    }
}

// Initialize all managers when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize employee table manager (always needed)
    if (document.querySelector('.employees-table')) {
        new EmployeeTableManager();
    }
    
    // Initialize tab manager (handles tab switching and lazy-loads other managers)
    const tabManager = new TabManager();
    
    // Pre-initialize directory manager if directory tab is visible
    if (document.getElementById('directory')?.classList.contains('active')) {
        tabManager.directoryManager = new DirectoryManager();
    }
    
    // Pre-initialize org chart manager if org-chart tab is visible
    if (document.getElementById('org-chart')?.classList.contains('active')) {
        tabManager.orgChartManager = new OrgChartManager();
    }
    
    // Handle employee row clicks to navigate to employee view page
    document.querySelectorAll('.employee-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking checkbox or other interactive elements
            if (e.target.closest('input[type="checkbox"]') || 
                e.target.closest('a') ||
                e.target.closest('button')) {
                return;
            }
            
            const employeeId = this.dataset.employeeId;
            if (employeeId) {
                // Navigate to employee view page
                window.location.href = `?page=view_employee&id=${employeeId}`;
            }
        });
    });
});

// Pagination functions
function changePage(page) {
    const url = new URL(window.location);
    if (page > 0) {
        url.searchParams.set('page_num', page);
    } else {
        url.searchParams.delete('page_num');
    }
    window.location.href = url.toString();
}

function deleteEmployee(id) {
    if (confirm('Are you sure you want to delete this employee?')) {
        // Implement delete functionality
        console.log('Delete employee:', id);
    }
}

function viewEmployee(id) {
    // Navigate to employee view page
    window.location.href = `?page=view_employee&id=${id}`;
}

// View employee details in modal
function viewEmployeeDetails(employeeId) {
    const modal = new bootstrap.Modal(document.getElementById('employeeDetailsModal'));
    const contentDiv = document.getElementById('employeeDetailsContent');
    const editBtn = document.getElementById('editEmployeeBtn');
    
    // Show loading state
    contentDiv.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading employee details...</p>
        </div>
    `;
    
    // Update edit button
    editBtn.href = `?page=edit_employee&id=${employeeId}`;
    
    // Show modal
    modal.show();
    
    // Fetch employee details via AJAX
    fetch(`?page=employees&action=get_employee&id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.employee) {
                displayEmployeeDetails(data.employee);
            } else {
                contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load employee details. Please try again.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    An error occurred while loading employee details.
                </div>
            `;
        });
}

// Display employee details in modal
function displayEmployeeDetails(emp) {
    const formatDate = (dateStr) => {
        if (!dateStr || dateStr === '0000-00-00') return 'N/A';
        return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    };
    
    const formatPhone = (phone) => {
        if (!phone) return 'N/A';
        return phone.replace(/-/g, ' ');
    };
    
    const getEmployeeTypeLabel = (type) => {
        const types = { 'SG': 'Security Guard', 'LG': 'Lady Guard', 'SO': 'Security Officer', 'Other': 'Other' };
        return types[type] || type;
    };
    
    const content = `
        <div class="employee-detail-view">
            <!-- Header Section -->
            <div class="row mb-4 pb-3 border-bottom">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="employee-avatar-large me-3" style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold;">
                            ${(emp.first_name?.[0] || '') + (emp.surname?.[0] || '')}
                        </div>
                        <div>
                            <h4 class="mb-1">${(emp.first_name || '') + ' ' + (emp.middle_name ? emp.middle_name + ' ' : '') + (emp.surname || '')}</h4>
                            <p class="text-muted mb-0">Employee #${emp.employee_no || 'N/A'}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge ${emp.status === 'Active' ? 'bg-success' : emp.status === 'Inactive' ? 'bg-secondary' : emp.status === 'Terminated' ? 'bg-danger' : 'bg-warning'} fs-6 px-3 py-2">
                        ${emp.status || 'N/A'}
                    </span>
                </div>
            </div>
            
            <!-- Core Identity -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3"><i class="fas fa-id-card me-2 text-primary"></i>Core Identity</h5>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Employee Number</label>
                    <p class="mb-0 fw-semibold">${emp.employee_no || 'N/A'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Employee Type</label>
                    <p class="mb-0 fw-semibold">${getEmployeeTypeLabel(emp.employee_type)}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Post / Position</label>
                    <p class="mb-0 fw-semibold">${emp.post || 'Unassigned'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Date Hired</label>
                    <p class="mb-0 fw-semibold">${formatDate(emp.date_hired)}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Department</label>
                    <p class="mb-0 fw-semibold">${emp.department || 'N/A'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Email</label>
                    <p class="mb-0 fw-semibold">${emp.email || 'N/A'}</p>
                </div>
            </div>
            
            <!-- License Information -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3"><i class="fas fa-certificate me-2 text-primary"></i>License & Regulatory Information</h5>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">License Number</label>
                    <p class="mb-0 fw-semibold">${emp.license_no || 'N/A'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">License Expiration</label>
                    <p class="mb-0 fw-semibold ${emp.license_exp_date && new Date(emp.license_exp_date) < new Date() ? 'text-danger' : ''}">${formatDate(emp.license_exp_date)}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Has RLM</label>
                    <p class="mb-0 fw-semibold">${emp.has_rlm ? 'Yes' : 'No'}</p>
                </div>
                ${emp.has_rlm ? `
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">RLM Expiration</label>
                    <p class="mb-0 fw-semibold ${emp.rlm_exp && new Date(emp.rlm_exp) < new Date() ? 'text-danger' : ''}">${formatDate(emp.rlm_exp)}</p>
                </div>
                ` : ''}
            </div>
            
            <!-- Personal Details -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3"><i class="fas fa-user me-2 text-primary"></i>Personal Details</h5>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">Birth Date</label>
                    <p class="mb-0 fw-semibold">${formatDate(emp.birth_date)}</p>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">Height</label>
                    <p class="mb-0 fw-semibold">${emp.height || 'N/A'}</p>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">Weight</label>
                    <p class="mb-0 fw-semibold">${emp.weight ? emp.weight + ' kg' : 'N/A'}</p>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">Blood Type</label>
                    <p class="mb-0 fw-semibold">${emp.blood_type || 'N/A'}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted small">Religion</label>
                    <p class="mb-0 fw-semibold">${emp.religion || 'N/A'}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted small">Address</label>
                    <p class="mb-0 fw-semibold">${emp.address || 'N/A'}</p>
                </div>
            </div>
            
            <!-- Government IDs -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3"><i class="fas fa-id-badge me-2 text-primary"></i>Government Identification Numbers</h5>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">SSS Number</label>
                    <p class="mb-0 fw-semibold">${emp.sss_no || 'N/A'}</p>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">PAG-IBIG Number</label>
                    <p class="mb-0 fw-semibold">${emp.pagibig_no || 'N/A'}</p>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">TIN Number</label>
                    <p class="mb-0 fw-semibold">${emp.tin_number || 'N/A'}</p>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="text-muted small">PhilHealth Number</label>
                    <p class="mb-0 fw-semibold">${emp.philhealth_no || 'N/A'}</p>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3"><i class="fas fa-phone me-2 text-primary"></i>Contact Information</h5>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted small">Contact Phone Number</label>
                    <p class="mb-0 fw-semibold">${formatPhone(emp.cp_number)}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted small">Emergency Contact Person</label>
                    <p class="mb-0 fw-semibold">${emp.contact_person || 'N/A'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Relationship</label>
                    <p class="mb-0 fw-semibold">${emp.relationship || 'N/A'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Emergency Contact Number</label>
                    <p class="mb-0 fw-semibold">${formatPhone(emp.contact_person_number)}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Emergency Contact Address</label>
                    <p class="mb-0 fw-semibold">${emp.contact_person_address || 'N/A'}</p>
                </div>
            </div>
            
            ${emp.hr_remarks ? `
            <!-- HR Remarks -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3"><i class="fas fa-sticky-note me-2 text-primary"></i>HR Remarks</h5>
                    <p class="mb-0">${emp.hr_remarks}</p>
                </div>
            </div>
            ` : ''}
            
            <!-- Audit Information -->
            <div class="row">
                <div class="col-12">
                    <h5 class="mb-3"><i class="fas fa-circle-info me-2 text-primary"></i>Record Information</h5>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Created By</label>
                    <p class="mb-0 fw-semibold">${emp.created_by_name || 'System'}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Created At</label>
                    <p class="mb-0 fw-semibold">${formatDate(emp.created_at)}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted small">Last Updated</label>
                    <p class="mb-0 fw-semibold">${formatDate(emp.updated_at)}</p>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('employeeDetailsContent').innerHTML = content;
}

function contactEmployee(id, email = '', phone = '') {
    // Show contact options
    let contactOptions = `Contact Employee #${id}\n\n`;
    
    if (email && email !== 'N/A') {
        contactOptions += `Email: ${email}\n`;
    }
    if (phone && phone !== 'N/A') {
        contactOptions += `Phone: ${phone}\n`;
    }
    
    if (email && email !== 'N/A') {
        if (confirm(contactOptions + '\nOpen email client?')) {
            window.location.href = `mailto:${email}`;
        }
    } else if (phone && phone !== 'N/A') {
        if (confirm(contactOptions + '\nCall this number?')) {
            window.location.href = `tel:${phone}`;
        }
    } else {
        alert(contactOptions || 'Contact information not available');
    }
}
</script>