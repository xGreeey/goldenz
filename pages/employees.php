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

// Get all employees directly from database - no filtering, all records included
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
        case 'license_exp':
            // Sort by license expiration date
            $a_date = !empty($a['license_exp_date']) && $a['license_exp_date'] !== '0000-00-00' ? strtotime($a['license_exp_date']) : 0;
            $b_date = !empty($b['license_exp_date']) && $b['license_exp_date'] !== '0000-00-00' ? strtotime($b['license_exp_date']) : 0;
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

<div class="container-fluid hrdash">
    <!-- Employee List (single view) -->
    <div class="tab-content">
        <div class="tab-pane active" id="employee-list">
    <!-- Success Messages -->
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="alert alert-success-modern alert-dismissible fade show">
            <i class="fas fa-circle-check me-2"></i>
            Employee created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success']) && $_GET['success'] === 'employee_created'): ?>
        <div class="alert alert-success alert-dismissible fade show" style="border-left: 4px solid #28a745;">
            <i class="fas fa-check-circle me-2 text-success"></i>
            <?php 
            if (isset($_SESSION['employee_created_message'])) {
                echo $_SESSION['employee_created_message']; // Already contains HTML formatting
                unset($_SESSION['employee_created_message']);
            } else {
                echo 'New employee has been created successfully!';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['employee_created_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['employee_created_success']) && $_SESSION['employee_created_success']): ?>
        <div class="alert alert-success alert-dismissible fade show" style="border-left: 4px solid #28a745;">
            <i class="fas fa-check-circle me-2 text-success"></i>
            <?php 
            if (isset($_SESSION['employee_created_message'])) {
                echo $_SESSION['employee_created_message'];
                unset($_SESSION['employee_created_message']);
            } else {
                echo 'New employee has been created successfully!';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['employee_created_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_GET['success']) && $_GET['success'] === 'page2_saved'): ?>
        <div class="alert alert-success-modern alert-dismissible fade show">
            <i class="fas fa-circle-check me-2"></i>
            <?php echo isset($_SESSION['page2_message']) ? htmlspecialchars($_SESSION['page2_message']) : 'Employee Page 2 information saved successfully!'; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['page2_message'], $_SESSION['page2_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['page2_success']) && $_SESSION['page2_success']): ?>
        <div class="alert alert-success-modern alert-dismissible fade show">
            <i class="fas fa-circle-check me-2"></i>
            <?php echo isset($_SESSION['page2_message']) ? htmlspecialchars($_SESSION['page2_message']) : 'Employee Page 2 information saved successfully!'; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['page2_message'], $_SESSION['page2_success']); ?>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row g-4">
        <div class="col-xl-4 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Total Employees</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($total_all_employees); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-arrow-up"></i>
                        <span><?php echo ($total_all_employees ?? 0) > 0 ? round(($active_employees / max(1, $total_all_employees)) * 100) : 0; ?>%</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">The total number of employees in the system.</div>
            </div>
        </div>
<<<<<<< HEAD
        <div class="col-xl-4 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Active Employees</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($active_employees); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-user-check"></i>
                        <span><?php echo number_format($active_employees); ?></span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Employees currently active and on roster.</div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Inactive Employees</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($inactive_employees); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--negative">
                        <i class="fas fa-user-slash"></i>
                        <span><?php echo number_format($inactive_employees); ?></span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">Employees currently inactive or off roster.</div>
=======
    </div>

    <!-- Search and Filter Bar -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern">
            <form method="GET" action="" id="employeeFilterForm" class="row g-3">
                <input type="hidden" name="page" value="employees">
                <div class="col-md-4">
                    <label class="form-label">Search Employees</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" 
                               name="search" 
                               id="employeeSearch"
                               class="form-control" 
                               placeholder="Search by name, employee number, post, or email..."
                               value="<?php echo htmlspecialchars($search); ?>"
                               autocomplete="off">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" id="statusFilter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Onboarding" <?php echo $status_filter === 'Onboarding' ? 'selected' : ''; ?>>Onboarding</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Employee Type</label>
                    <select name="type" id="departmentFilter" class="form-select">
                        <option value="">All Types</option>
                        <?php
                        // Get unique employee types
                        $unique_types = [];
                        foreach ($all_employees as $emp) {
                            if (!empty($emp['employee_type'])) {
                                $unique_types[$emp['employee_type']] = true;
                            }
                        }
                        ksort($unique_types);
                        foreach ($unique_types as $type => $val):
                        ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $type_filter === $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select name="sort_by" id="sortBy" class="form-select">
                        <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="license_exp" <?php echo $sort_by === 'license_exp' ? 'selected' : ''; ?>>License Expiration</option>
                        <option value="status" <?php echo $sort_by === 'status' ? 'selected' : ''; ?>>Status</option>
                        <option value="date_hired" <?php echo $sort_by === 'date_hired' ? 'selected' : ''; ?>>Date Hired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Order</label>
                    <select name="sort_order" id="sortOrder" class="form-select">
                        <option value="asc" <?php echo $sort_order === 'asc' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="desc" <?php echo $sort_order === 'desc' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
                <div class="col-md-12 d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-modern" id="clearFilters">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-outline-modern" id="exportBtn" title="Export employee list">
                    <i class="fas fa-download me-2"></i>Export CSV
                </button>
                <a href="?page=add_employee" class="btn btn-primary-modern">
                    <span class="hr-icon hr-icon-plus me-2"></span>Add New Employee
                </a>
>>>>>>> 8e02a0e2b13574f1b99b6c406002082b233c4614
            </div>
        </div>
    </div>

    <!-- Employee Table -->
    <div class="card card-modern mb-4 mt-4">
        <div class="card-body-modern">
            <div class="card-header-modern mb-4 d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="card-title-modern">Employee List</h5>
                    <small class="card-subtitle">View and manage all employees</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-modern" id="exportBtn" title="Export employee list">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <a href="?page=add_employee" class="btn btn-primary-modern">
                        <span class="hr-icon hr-icon-plus me-2"></span>Add Employee
                    </a>
                </div>
            </div>
            <div class="table-container">
                <table class="employees-table">
                        <thead>
                            <tr>
                            <th>
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th class="sortable" data-sort="name">
                                EMPLOYEE INFO
                                <i class="fas fa-sort"></i>
                            </th>
                            <th class="sortable" data-sort="license_exp">
                                LICENSE / RLM
                                <i class="fas fa-sort"></i>
                            </th>
                            <th class="sortable" data-sort="status">
                                STATUS
                                <i class="fas fa-sort"></i>
                            </th>
                            <th>CREATED BY</th>
                            <th>ACTIONS</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php if (empty($paginated_employees)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
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
                            <tr class="employee-row" data-employee-id="<?php echo $employee['id']; ?>">
                                <td onclick="event.stopPropagation();">
                                    <input type="checkbox" class="form-check-input employee-checkbox" value="<?php echo $employee['id']; ?>">
                                </td>
                                <!-- Employee Info (with Post and Employment Status) -->
                                <td>
                                    <div class="employee-info-combined">
                                        <div class="employee-info-line fw-semibold">
                                            <?php 
                                            $employee_type = strtoupper(trim($employee['employee_type'] ?? ''));
                                            $employee_no = htmlspecialchars($employee['employee_no'] ?? $employee['id']);
                                            $full_name = htmlspecialchars($full_name);
                                            
                                            // Format: TYPE | #NUM | NAME
                                            $info_parts = [];
                                            if (!empty($employee_type)) {
                                                $info_parts[] = $employee_type;
                                            }
                                            $info_parts[] = '#' . $employee_no;
                                            $info_parts[] = $full_name;
                                            echo implode(' | ', $info_parts);
                                            ?>
                                        </div>
                                        <div class="employee-post text-muted fs-13 mt-1">
                                            <?php echo htmlspecialchars($employee['post'] ?? 'Unassigned'); ?>
                                        </div>
                                        <div class="employee-status-badge" style="margin-top: 0.25rem;">
                                            <?php 
                                            $status_class = ($employment_status === 'Probationary') ? 'bg-warning text-dark' : 'bg-info text-white';
                                            $status_text = $employment_status;
                                            ?>
                                            <span class="badge <?php echo $status_class; ?> fs-11 px-2 py-1">
                                                <?php echo htmlspecialchars($status_text); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <!-- License and RLM (styled similar to Employee Info column) -->
                                <td>
                                    <div class="employee-info-combined license-rlm-info">
                                        <!-- Top line: License number or No License -->
                                        <div class="employee-info-line fw-semibold">
                                            <?php if (!empty($employee['license_no'])): ?>
                                                <span class="fs-11 fw-semibold text-muted text-uppercase me-1">
                                                    License No
                                                </span>
                                                <span class="fs-13 text-dark">
                                                    <?php echo htmlspecialchars($employee['license_no']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-danger fs-13">
                                                    No License
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Second line: License expiration -->
                                        <div class="employee-post text-muted fs-13 mt-1">
                                            <span class="fs-11 fw-semibold text-uppercase text-muted me-1">
                                                Expiration
                                            </span>
                                            <?php if ($license_formatted): ?>
                                                <span class="<?php echo ($license_formatted['days'] < 0 || $license_formatted['days'] <= 30) ? 'text-danger' : ''; ?>">
                                                    <?php echo htmlspecialchars($license_formatted['text']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Third line: RLM info, only if present -->
                                        <?php if (!empty($employee['rlm_exp'])): ?>
                                            <div class="employee-status-badge" style="margin-top: 0.125rem;">
                                                <?php if ($rlm_formatted): ?>
                                                    <span class="badge <?php echo ($rlm_formatted['days'] < 0 || $rlm_formatted['days'] <= 30) ? 'bg-danger' : 'bg-info'; ?> fs-11 px-2 py-1">
                                                        RLM <?php echo htmlspecialchars($rlm_formatted['text']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info fs-11 px-2 py-1">
                                                        RLM Present
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <!-- Status -->
                                <td>
                                    <span class="status-badge <?php echo strtolower($employee['status'] ?? ''); ?> fs-xs px-2 py-1">
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
                                <!-- Actions -->
                                <td>
                                    <a href="?page=view_employee&id=<?php echo $employee['id']; ?>" 
                                       class="btn btn-sm btn-outline-modern" 
                                       title="View Employee Details">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                </td>
                                </tr>
                                <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
            </div>
        </div>
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
                                <span class="hr-icon hr-icon-edit me-2"></span>Edit Employee
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="card card-modern">
        <div class="card-body-modern">
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

<script>
// Update time display every minute for employees page
(function() {
    function updateTime() {
        const timeElement = document.getElementById('current-time-employees');
        if (timeElement) {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHours = hours % 12 || 12;
            const displayMinutes = minutes < 10 ? '0' + minutes : minutes;
            timeElement.textContent = displayHours + ':' + displayMinutes + ' ' + ampm.toUpperCase();
        }
    }
    
    // Update immediately
    updateTime();
    
    // Update every minute
    setInterval(updateTime, 60000);
})();
</script>

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
        
        // Column header sorting - make entire header clickable
        document.querySelectorAll('.sortable').forEach(header => {
            // Add visual feedback
            header.style.cursor = 'pointer';
            header.style.userSelect = 'none';
            
            header.addEventListener('click', (e) => {
                // Prevent event from bubbling if clicking on interactive elements
                if (e.target.closest('input, button, select, a')) {
                    return;
                }
                
                // Stop propagation to prevent any parent handlers
                e.stopPropagation();
                
                const column = header.dataset.sort;
                if (!column) {
                    console.warn('Sortable header missing data-sort attribute');
                    return;
                }
                
                const url = new URL(window.location);
                const currentSort = url.searchParams.get('sort_by') || 'name';
                const currentOrder = url.searchParams.get('sort_order') || 'asc';
                
                let newOrder = 'asc';
                if (currentSort === column) {
                    // Toggle order if clicking the same column
                    newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                }
                
                // Update sort controls if they exist
                const sortBy = document.getElementById('sortBy');
                const sortOrder = document.getElementById('sortOrder');
                if (sortBy) sortBy.value = column;
                if (sortOrder) sortOrder.value = newOrder;
                
                // Update URL to trigger sorting
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
});

// Also initialize immediately if DOM is already loaded (for cached pages)
if (document.readyState !== 'loading') {
    if (document.querySelector('.employees-table')) {
        new EmployeeTableManager();
    }
    const tabManager = new TabManager();
    if (document.getElementById('directory')?.classList.contains('active')) {
        tabManager.directoryManager = new DirectoryManager();
    }
    if (document.getElementById('org-chart')?.classList.contains('active')) {
        tabManager.orgChartManager = new OrgChartManager();
    }
}

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
                        <div class="employee-avatar-large me-3 fs-24" style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
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

</div> <!-- /.container-fluid -->