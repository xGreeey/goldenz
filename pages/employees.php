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

// Helper functions for employee export
function get_employee_export_columns() {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->query("SHOW COLUMNS FROM employees");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }
        return $columns;
    } catch (Exception $e) {
        return [];
    }
}

function get_employee_export_label_overrides() {
    return [
        'id' => 'ID',
        'employee_no' => 'Employee No',
        'employee_type' => 'Employee Type',
        'first_name' => 'First Name',
        'middle_name' => 'Middle Name',
        'surname' => 'Surname',
        'post' => 'Post / Assignment',
        'license_no' => 'License No',
        'license_exp_date' => 'License Expiration Date',
        'rlm_exp' => 'RLM Expiration',
        'date_hired' => 'Date Hired',
        'cp_number' => 'Contact Number',
        'sss_no' => 'SSS No',
        'pagibig_no' => 'Pag-IBIG No',
        'tin_number' => 'TIN Number',
        'philhealth_no' => 'PhilHealth No',
        'birth_date' => 'Birth Date',
        'contact_person' => 'Emergency Contact Person',
        'relationship' => 'Emergency Relationship',
        'contact_person_address' => 'Emergency Contact Address',
        'contact_person_number' => 'Emergency Contact Number',
        'drivers_license_no' => 'Driver License No',
        'drivers_license_exp' => 'Driver License Expiration',
        'req_2x2' => '2x2 Photo',
        'req_birth_cert' => 'Birth Certificate',
        'req_barangay' => 'Barangay Clearance',
        'req_police' => 'Police Clearance',
        'req_nbi' => 'NBI Clearance',
        'req_di' => 'Drug Test',
        'req_diploma' => 'Diploma',
        'req_neuro_drug' => 'Neuro/Psych Drug Test',
        'req_sec_license' => 'Security License',
        'sec_lic_no' => 'Security License No',
        'req_sec_lic_no' => 'Security License No (Req)',
        'req_sss' => 'SSS Requirement',
        'req_pagibig' => 'Pag-IBIG Requirement',
        'req_philhealth' => 'PhilHealth Requirement',
        'req_tin' => 'TIN Requirement'
    ];
}

function humanize_employee_column_label($column) {
    $overrides = get_employee_export_label_overrides();
    if (isset($overrides[$column])) {
        return $overrides[$column];
    }
    $label = str_replace('_', ' ', $column);
    $label = ucwords($label);
    $label = preg_replace('/\bId\b/', 'ID', $label);
    $label = preg_replace('/\bRlm\b/', 'RLM', $label);
    $label = preg_replace('/\bSss\b/', 'SSS', $label);
    $label = preg_replace('/\bTin\b/', 'TIN', $label);
    $label = preg_replace('/\bNbi\b/', 'NBI', $label);
    return $label;
}

function format_employee_export_value($value) {
    if ($value === null) {
        return '';
    }
    if (is_bool($value)) {
        return $value ? 'Yes' : 'No';
    }
    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed === '0000-00-00' || $trimmed === '0000-00-00 00:00:00') {
            return '';
        }
        return $value;
    }
    return (string)$value;
}

function get_employee_export_default_columns($available_columns) {
    $defaults = [
        'employee_no',
        'first_name',
        'middle_name',
        'surname',
        'employee_type',
        'post',
        'status',
        'date_hired',
        'license_no',
        'license_exp_date',
        'rlm_exp',
        'cp_number'
    ];
    return array_values(array_filter($defaults, function ($column) use ($available_columns) {
        return in_array($column, $available_columns, true);
    }));
}

// Get all employees directly from database - no filtering, all records included
$all_employees = get_employees();

// Prepare exportable columns and defaults
$employee_export_columns = get_employee_export_columns();
if (empty($employee_export_columns) && !empty($all_employees)) {
    $employee_export_columns = array_keys($all_employees[0]);
}
$employee_export_columns = array_values(array_filter($employee_export_columns, function ($column) {
    return $column !== 'creator_name' && $column !== 'id';
}));
$default_export_columns = get_employee_export_default_columns($employee_export_columns);
$default_export_lookup = array_flip($default_export_columns);

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

// Get database connection for KPI calculations
$pdo = get_db_connection();
?>

<div class="container-fluid hrdash">
    <!-- Employee List (single view) -->
    <div class="tab-content">
        <div class="tab-pane active" id="employee-list">
    <!-- Success Messages -->
    <?php 
    // Display session messages (from redirect_with_message)
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ][$type] ?? 'alert-info';
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-check-circle me-2"></i>' . htmlspecialchars($message);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['message'], $_SESSION['message_type']);
    }
    ?>
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
    <div class="row g-4 mb-4 justify-content-center">
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat hrdash-stat--primary">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Total Employees</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php echo number_format($total_all_employees); ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>5%</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">The total number of active employees currently in the company.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Regular</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php 
                        // Calculate regular employees (hired more than 6 months ago)
                        $regularCount = 0;
                        try {
                            $regularStmt = $pdo->query("SELECT COUNT(*) as total
                                                        FROM employees
                                                        WHERE status = 'Active'
                                                          AND date_hired IS NOT NULL
                                                          AND date_hired != ''
                                                          AND date_hired != '0000-00-00'
                                                          AND LENGTH(TRIM(COALESCE(date_hired, ''))) > 0
                                                          AND TRIM(date_hired) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
                                                          AND STR_TO_DATE(TRIM(date_hired), '%Y-%m-%d') IS NOT NULL
                                                          AND STR_TO_DATE(TRIM(date_hired), '%Y-%m-%d') < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)");
                            $regularRow = $regularStmt->fetch(PDO::FETCH_ASSOC);
                            $regularCount = (int)($regularRow['total'] ?? 0);
                        } catch (Exception $e) {
                            $regularCount = 0;
                        }
                        echo number_format($regularCount);
                    ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>2%</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">The number of employees who have completed their probation period.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card hrdash-stat">
                <div class="hrdash-stat__header">
                    <div class="hrdash-stat__label">Probation</div>
                </div>
                <div class="hrdash-stat__content">
                    <div class="hrdash-stat__value"><?php 
                        // Calculate probationary employees (hired within last 6 months)
                        $probationCount = 0;
                        try {
                            $probStmt = $pdo->query("SELECT COUNT(*) as total
                                                     FROM employees
                                                     WHERE status = 'Active'
                                                       AND date_hired IS NOT NULL
                                                       AND date_hired != ''
                                                       AND date_hired != '0000-00-00'
                                                       AND LENGTH(TRIM(COALESCE(date_hired, ''))) > 0
                                                       AND TRIM(date_hired) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
                                                       AND STR_TO_DATE(TRIM(date_hired), '%Y-%m-%d') IS NOT NULL
                                                       AND STR_TO_DATE(TRIM(date_hired), '%Y-%m-%d') >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)");
                            $probRow = $probStmt->fetch(PDO::FETCH_ASSOC);
                            $probationCount = (int)($probRow['total'] ?? 0);
                        } catch (Exception $e) {
                            $probationCount = 0;
                        }
                        echo number_format($probationCount);
                    ?></div>
                    <div class="hrdash-stat__trend hrdash-stat__trend--positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>10%</span>
                    </div>
                </div>
                <div class="hrdash-stat__meta">The number of employees currently in their probation period.</div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern">
            <form method="GET" action="" id="employeeFilterForm" class="d-flex gap-2 align-items-end" style="flex-wrap: nowrap;">
                <input type="hidden" name="page" value="employees">
                <div class="flex-grow-1" style="min-width: 0;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Search</label>
                    <div class="search-input-wrapper">
                        <div class="search-icon-container">
                            <span class="hr-icon hr-icon-search search-icon"></span>
                        </div>
                        <input type="text" 
                               name="search" 
                               id="employeeSearch"
                               class="form-control search-input" 
                               placeholder="search by name, employee #, post, or email"
                               value="<?php echo htmlspecialchars($search); ?>"
                               autocomplete="off">
                        <button type="button" class="search-clear-btn" id="search-clear-btn" style="display: <?php echo !empty($search) ? 'flex' : 'none'; ?>;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div style="flex: 0 0 auto; min-width: 140px;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Status</label>
                    <select name="status" id="statusFilter" class="form-select form-select-sm" style="padding: 0.375rem 0.5rem; font-size: 0.8125rem;">
                        <option value="">All</option>
                        <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Onboarding" <?php echo $status_filter === 'Onboarding' ? 'selected' : ''; ?>>Onboarding</option>
                    </select>
                </div>
                <div style="flex: 0 0 auto; min-width: 140px;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500;">Employee Type</label>
                    <select name="type" id="departmentFilter" class="form-select form-select-sm" style="padding: 0.375rem 0.5rem; font-size: 0.8125rem;">
                        <option value="">All</option>
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
                <div style="flex: 0 0 auto;">
                    <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; font-weight: 500; visibility: hidden;">Reset</label>
                    <button type="button" class="btn-reset-icon" id="clearFilters" title="Reset filters">
                        <span class="hr-icon hr-icon-dismiss"></span>
                    </button>
                </div>
                <div style="flex: 0 0 30%; min-width: 120px; text-align: right; margin-left: auto;">
                    <div style="font-size: 0.6875rem; color: #64748b; margin-bottom: 0.125rem;">Results</div>
                    <div id="employee-count" style="font-size: 1rem; font-weight: 600; color: #1e3a8a;"><?php echo number_format(count($filtered_employees)); ?></div>
                </div>
                <!-- Hidden sort controls - will be handled via table headers or separate UI -->
                <input type="hidden" name="sort_by" id="sortBy" value="<?php echo htmlspecialchars($sort_by); ?>">
                <input type="hidden" name="sort_order" id="sortOrder" value="<?php echo htmlspecialchars($sort_order); ?>">
                <input type="hidden" id="total-filtered-employees" value="<?php echo count($filtered_employees); ?>">
            </form>
        </div>
    </div>

    <!-- Employee Table -->
    <div class="card card-modern mb-4 employee-list-container">
        <div class="card-body-modern">
            <div class="card-header-modern mb-2 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title-modern">Employee List</h5>
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
                                <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-sort="license_exp">
                                LICENSE / RLM
                                <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-sort="status">
                                STATUS
                                <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th>CREATED BY</th>
                            <th>ACTIONS</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php if (empty($paginated_employees)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
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
                                            // Match violation history color scheme
                                            if ($employment_status === 'Probationary') {
                                                $status_class = 'badge-employment-probationary';
                                            } else {
                                                $status_class = 'badge-employment-regular';
                                            }
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
                                                <?php echo htmlspecialchars($employee['license_no']); ?>
                                            <?php else: ?>
                                                <span class="text-danger">No License</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Second line: License expiration date (no label, no margin) -->
                                        <?php if ($license_formatted): ?>
                                            <div class="employee-post text-muted fs-13" style="margin-top: 0; line-height: 1.3;">
                                                <?php
                                                // Match violation history color scheme
                                                if ($license_formatted['days'] < 0) {
                                                    $exp_color_class = 'license-expired';
                                                } elseif ($license_formatted['days'] <= 30) {
                                                    $exp_color_class = 'license-expiring';
                                                } else {
                                                    $exp_color_class = 'license-valid';
                                                }
                                                ?>
                                                <span class="<?php echo $exp_color_class; ?>">
                                                    <?php echo htmlspecialchars($license_formatted['text']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Third line: RLM info, only if present -->
                                        <?php if (!empty($employee['rlm_exp'])): ?>
                                            <div class="employee-status-badge" style="margin-top: 0.125rem;">
                                                <?php if ($rlm_formatted): ?>
                                                    <?php
                                                    // Match violation history color scheme for RLM
                                                    if ($rlm_formatted['days'] < 0) {
                                                        $rlm_class = 'badge-rlm-expired';
                                                    } elseif ($rlm_formatted['days'] <= 30) {
                                                        $rlm_class = 'badge-rlm-expiring';
                                                    } else {
                                                        $rlm_class = 'badge-rlm-valid';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $rlm_class; ?> fs-11 px-2 py-1">
                                                        RLM <span style="margin-left: 0.375rem;"><?php echo htmlspecialchars($rlm_formatted['text']); ?></span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-rlm-valid fs-11 px-2 py-1">
                                                        RLM Present
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <!-- Status -->
                                <td>
                                    <?php
                                    $status = $employee['status'] ?? 'N/A';
                                    // Match violation history color scheme
                                    if ($status === 'Active') {
                                        $status_badge_class = 'badge-status-active';
                                    } elseif ($status === 'Inactive') {
                                        $status_badge_class = 'badge-status-inactive';
                                    } elseif ($status === 'Terminated') {
                                        $status_badge_class = 'badge-status-terminated';
                                    } elseif ($status === 'Onboarding') {
                                        $status_badge_class = 'badge-status-onboarding';
                                    } else {
                                        $status_badge_class = 'badge-status-default';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_badge_class; ?> fs-11 px-2 py-1">
                                        <?php echo htmlspecialchars($status); ?>
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
                                    <div class="employee-actions">
                                        <a href="?page=view_employee&id=<?php echo $employee['id']; ?>" 
                                           class="btn btn-sm btn-primary-modern" 
                                           title="View Employee Details">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                        <a href="?page=edit_employee&id=<?php echo $employee['id']; ?>" 
                                           class="btn btn-sm btn-outline-modern" 
                                           title="Edit Employee">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
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

            <!-- Employee Export Modal -->
            <div class="modal fade" id="employeeExportModal" tabindex="-1" aria-labelledby="employeeExportModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <form method="POST" action="?page=employees" id="employeeExportForm">
                            <input type="hidden" name="action" value="export_employees">
                            <div class="modal-header">
                                <h5 class="modal-title" id="employeeExportModalLabel">
                                    <i class="fas fa-file-export me-2"></i>Export Employees
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-4">
                                    <div class="col-lg-5">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label mb-0">Employees</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="exportAllEmployeesToggle" name="export_all_employees" value="1" checked>
                                                <label class="form-check-label" for="exportAllEmployeesToggle">Export all</label>
                                            </div>
                                        </div>
                                        <input type="text" id="exportEmployeeSearch" class="form-control form-control-sm mb-2" placeholder="Search by name or employee no...">
                                        <select id="exportEmployeeSelect" class="form-select" name="employee_ids[]" multiple size="10" aria-label="Select employees">
                                            <?php foreach ($all_employees as $emp): 
                                                $employee_name_parts = [];
                                                if (!empty($emp['surname'])) {
                                                    $employee_name_parts[] = $emp['surname'];
                                                }
                                                if (!empty($emp['first_name'])) {
                                                    $employee_name_parts[] = $emp['first_name'];
                                                }
                                                $employee_name = implode(', ', $employee_name_parts);
                                                if (!empty($emp['middle_name'])) {
                                                    $employee_name .= ' ' . $emp['middle_name'];
                                                }
                                                $employee_name = trim($employee_name);
                                                if ($employee_name === '') {
                                                    $employee_name = 'Employee #' . ($emp['id'] ?? '');
                                                }
                                            ?>
                                                <option value="<?php echo htmlspecialchars($emp['id'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars($employee_name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted d-block mt-2">
                                            Tip: Use table checkboxes to preselect employees before exporting.
                                        </small>
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label mb-0">Columns</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-modern" id="selectCommonColumnsBtn">Common</button>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" id="exportAllColumnsToggle" name="export_all_columns" value="1">
                                                    <label class="form-check-label" for="exportAllColumnsToggle">All columns</label>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="text" id="exportColumnSearch" class="form-control form-control-sm mb-2" placeholder="Search columns...">
                                        <div class="border rounded p-3" style="max-height: 320px; overflow-y: auto;">
                                            <?php if (empty($employee_export_columns)): ?>
                                                <div class="text-muted">No exportable columns found.</div>
                                            <?php else: ?>
                                                <div class="row g-2">
                                                    <?php foreach ($employee_export_columns as $column): 
                                                        $column_id = 'export-col-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $column);
                                                        $column_label = humanize_employee_column_label($column);
                                                        $is_common = isset($default_export_lookup[$column]);
                                                    ?>
                                                        <div class="col-md-6 export-column-item">
                                                            <div class="form-check">
                                                                <input
                                                                    class="form-check-input export-column-checkbox"
                                                                    type="checkbox"
                                                                    name="columns[]"
                                                                    value="<?php echo htmlspecialchars($column); ?>"
                                                                    id="<?php echo htmlspecialchars($column_id); ?>"
                                                                    data-common="<?php echo $is_common ? '1' : '0'; ?>"
                                                                    <?php echo $is_common ? 'checked' : ''; ?>
                                                                >
                                                                <label class="form-check-label" for="<?php echo htmlspecialchars($column_id); ?>">
                                                                    <?php echo htmlspecialchars($column_label); ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block mt-2">Select the fields to include in the export.</small>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <label class="form-label">File Format</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="file_format" id="formatCSV" value="csv" checked>
                                                <label class="form-check-label" for="formatCSV">
                                                    <i class="fas fa-file-csv me-1"></i>CSV (.csv)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="file_format" id="formatXLSX" value="xlsx">
                                                <label class="form-check-label" for="formatXLSX">
                                                    <i class="fas fa-file-excel me-1"></i>Excel (.xlsx)
                                                </label>
                                            </div>
                                        </div>
                                        <small class="text-muted">Choose the export file format</small>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-outline-modern" id="exportAllQuickBtn">
                                    <i class="fas fa-download me-2"></i>Export All
                                </button>
                                <button type="submit" class="btn btn-primary-modern">
                                    <i class="fas fa-file-export me-2"></i>Export Selected
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="card card-modern">
        <div class="card-body-modern">
            <div class="pagination-container-modern">
                <div class="pagination-controls-modern">
                    <!-- First Page Button -->
                    <button class="pagination-btn" <?php echo $page_num <= 1 ? 'disabled' : ''; ?> onclick="changePage(1)" title="First page">
                        &laquo;
                    </button>
                    <!-- Previous Page Button -->
                    <button class="pagination-btn" <?php echo $page_num <= 1 ? 'disabled' : ''; ?> onclick="changePage(<?php echo max(1, $page_num - 1); ?>)" title="Previous page">
                        &lt;
                    </button>
                    
                    <?php if ($total_pages > 0): 
                        // Show up to 5 page numbers around current page
                        $start_page = max(1, $page_num - 2);
                        $end_page = min($total_pages, $page_num + 2);
                        
                        // Adjust if we're near the start
                        if ($page_num <= 2) {
                            $end_page = min($total_pages, 5);
                        }
                        // Adjust if we're near the end
                        if ($page_num >= $total_pages - 1) {
                            $start_page = max(1, $total_pages - 4);
                        }
                    ?>
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <button class="pagination-btn <?php echo $i == $page_num ? 'pagination-btn-active' : ''; ?>" onclick="changePage(<?php echo $i; ?>)">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                    <?php else: ?>
                        <button class="pagination-btn pagination-btn-active" disabled>1</button>
                    <?php endif; ?>
                    
                    <!-- Next Page Button -->
                    <button class="pagination-btn" <?php echo $page_num >= $total_pages ? 'disabled' : ''; ?> onclick="changePage(<?php echo min($total_pages, $page_num + 1); ?>)" title="Next page">
                        &gt;
                    </button>
                    
                    <!-- Page Number Input -->
                    <input type="text" class="pagination-page-input" id="pageNumberInput" placeholder="Page #" value="<?php echo $page_num; ?>" onkeypress="handlePageInputKeyPress(event)">
                </div>
                
                <!-- Items Per Page Dropdown -->
                <div class="pagination-per-page">
                    <select class="pagination-select" id="perPageSelect">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Employee Filter Form Styles - Matching Violation Types Design */
#employeeFilterForm {
    display: flex;
    flex-wrap: nowrap !important;
    gap: 0.75rem;
    align-items: flex-end;
    width: 100%;
}

#employeeFilterForm > div {
    flex-shrink: 0;
}

#employeeFilterForm > div:first-child {
    flex: 1 1 0;
    min-width: 0;
}

#employeeFilterForm .form-label {
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
    font-weight: 500;
    color: #374151;
}

#employeeFilterForm .form-control-sm,
#employeeFilterForm .form-select-sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    height: 38px;
    transition: all 0.2s ease;
}

#employeeFilterForm .form-select-sm:focus,
#employeeFilterForm .form-control-sm:focus {
    border-color: #1e3a8a;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    outline: none;
}

/* Search Input Wrapper Styles */
.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: stretch;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #ffffff;
    transition: all 0.2s ease;
    height: 38px;
    overflow: hidden;
}

.search-input-wrapper:hover {
    border-color: #9ca3af;
}

.search-input-wrapper:focus-within {
    border-color: #1e3a8a;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    outline: none;
}

.search-icon-container {
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.75rem;
    min-width: 44px;
    flex-shrink: 0;
    border-right: 1px solid #d1d5db;
}

.search-icon {
    width: 16px !important;
    height: 16px !important;
    display: inline-block !important;
    background-size: contain !important;
    background-repeat: no-repeat !important;
    background-position: center !important;
    opacity: 0.6 !important;
    visibility: visible !important;
    filter: brightness(0) saturate(100%) invert(40%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%);
    transition: opacity 0.2s ease;
}

.search-input-wrapper:focus-within .search-icon {
    opacity: 0.8;
    filter: brightness(0) saturate(100%) invert(15%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%);
}

.search-icon.hr-icon-search {
    background-image: url('../assets/icons/search-icon.svg') !important;
}

.search-input {
    flex: 1;
    border: none !important;
    border-radius: 0 !important;
    padding: 0.375rem 2.5rem 0.375rem 0.5rem !important;
    font-size: 0.8125rem;
    background: #ffffff;
    box-shadow: none !important;
    transition: all 0.2s ease;
    height: 100%;
    line-height: 1.5;
}

.search-input:focus {
    border: none !important;
    box-shadow: none !important;
    outline: none;
}

.search-input::placeholder {
    color: #9ca3af;
}

.search-clear-btn {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #9ca3af;
    font-size: 0.75rem;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 2;
}

.search-clear-btn:hover {
    background-color: #f3f4f6;
    color: #64748b;
}

/* Reset Button Icon */
.btn-reset-icon {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
}

.btn-reset-icon .hr-icon {
    width: 16px !important;
    height: 16px !important;
    display: inline-block !important;
    background-size: contain !important;
    background-repeat: no-repeat !important;
    background-position: center !important;
    opacity: 0.6 !important;
    filter: brightness(0) saturate(100%) invert(40%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%) !important;
    transition: all 0.2s ease;
    visibility: visible !important;
}

.btn-reset-icon .hr-icon-dismiss {
    background-image: url('../assets/icons/dismiss-icon_remove-icon.svg') !important;
}

.btn-reset-icon:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.btn-reset-icon:hover .hr-icon {
    opacity: 0.8;
    filter: brightness(0) saturate(100%) invert(15%) sepia(8%) saturate(750%) hue-rotate(177deg) brightness(94%) contrast(88%) !important;
}

#employee-count {
    color: #1e3a8a;
    font-size: 1rem;
    font-weight: 600;
}

/* Compact License / RLM Column Styling */
.employees-table .license-rlm-info {
    display: flex;
    flex-direction: column;
    gap: 0 !important;
}

.employees-table .license-rlm-info .employee-info-line {
    margin-bottom: 0;
    line-height: 1.3;
}

.employees-table .license-rlm-info .employee-post {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    line-height: 1.3;
}

.employees-table .license-rlm-info .employee-status-badge {
    margin-top: 0.125rem !important;
    margin-bottom: 0 !important;
}

/* Status Badge Colors - Matching Violation History */
.employees-table .badge-status-active {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    font-weight: 600;
}

.employees-table .badge-status-inactive {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
    font-weight: 600;
}

.employees-table .badge-status-terminated {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
    font-weight: 600;
}

.employees-table .badge-status-onboarding {
    background-color: #e0e7ff;
    color: #6366f1;
    border: 1px solid #c7d2fe;
    font-weight: 600;
}

.employees-table .badge-status-default {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    font-weight: 600;
}

/* Employment Status Badge Colors */
.employees-table .badge-employment-probationary {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
    font-weight: 600;
}

.employees-table .badge-employment-regular {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    font-weight: 600;
}

/* RLM Badge Colors */
.employees-table .badge-rlm-expired {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
    font-weight: 600;
}

.employees-table .badge-rlm-expiring {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
    font-weight: 600;
}

.employees-table .badge-rlm-valid {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    font-weight: 600;
}

/* License Expiration Text Colors - Matching Violation History */
.employees-table .license-expired {
    color: #991b1b !important;
    font-weight: 600;
}

.employees-table .license-expiring {
    color: #92400e !important;
    font-weight: 600;
}

.employees-table .license-valid {
    color: #166534 !important;
}

/* Table Header Styling - Matching Modern Design */
.employees-table thead {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.employees-table thead th {
    background: #f9fafb;
    padding: 0.75rem 1rem;
    font-weight: 600;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
    white-space: nowrap;
    position: relative;
}

.employees-table thead th:first-child {
    padding: 0.75rem;
    text-align: center;
}

.employees-table thead th.sortable {
    cursor: pointer;
    user-select: none;
    transition: all 0.2s ease;
    padding-right: 2.25rem;
    position: relative;
}

.employees-table thead th.sortable:hover {
    background: #f3f4f6;
    color: #1f2937;
}

.employees-table thead th.sortable .sort-icon {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.75rem;
    color: #6b7280;
    pointer-events: none;
    transition: all 0.2s ease;
    opacity: 0.85;
    display: inline-block;
}

.employees-table thead th.sortable:hover .sort-icon {
    color: #4b5563;
    opacity: 1;
}

.employees-table thead th.sortable .sort-icon.fa-sort {
    opacity: 0.85;
}

.employees-table thead th.sortable .sort-icon.fa-sort-up,
.employees-table thead th.sortable .sort-icon.fa-sort-down {
    color: #4b5563;
    opacity: 1;
    font-weight: 600;
}

.employees-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.15s ease;
}

.employees-table tbody tr:hover {
    background-color: #f9fafb;
}

.employees-table tbody tr:last-child {
    border-bottom: none;
}

/* Compact Employee List Container */
.employee-list-container .card-body-modern {
    padding: 0.75rem !important;
}

.employee-list-container .card-header-modern {
    padding: 0.5rem 0 !important;
    margin-bottom: 0.75rem !important;
}

.employee-list-container .card-title-modern {
    font-size: 1rem !important;
    margin-bottom: 0 !important;
}

.employee-list-container .table-container {
    margin: 0 !important;
}

.employee-list-container .employees-table thead th {
    padding: 0.5rem 0.75rem !important;
    font-size: 0.75rem !important;
}

.employee-list-container .employees-table thead th:first-child {
    padding: 0.5rem !important;
}

.employee-list-container .employees-table tbody td {
    padding: 0.5rem 0.75rem !important;
    font-size: 0.8125rem !important;
}

.employee-list-container .employees-table tbody td:first-child {
    padding: 0.5rem !important;
}

/* Modern Pagination Styling */
.pagination-container-modern {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.pagination-controls-modern {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.pagination-btn {
    min-width: 36px;
    height: 36px;
    padding: 0 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background-color: #ffffff;
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pagination-btn:hover:not(:disabled) {
    background-color: #f9fafb;
    border-color: #d1d5db;
    color: #374151;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: #f9fafb;
    color: #9ca3af;
}

.pagination-btn-active {
    background-color: #6366f1;
    border-color: #6366f1;
    color: #ffffff;
    font-weight: 600;
}

.pagination-btn-active:hover {
    background-color: #4f46e5;
    border-color: #4f46e5;
    color: #ffffff;
}

.pagination-page-input {
    width: 80px;
    height: 36px;
    padding: 0 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background-color: #ffffff;
    color: #374151;
    font-size: 0.875rem;
    text-align: center;
    transition: all 0.2s ease;
}

.pagination-page-input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.pagination-page-input::placeholder {
    color: #9ca3af;
}

.pagination-per-page {
    display: flex;
    align-items: center;
}

.pagination-select {
    width: 80px;
    height: 36px;
    padding: 0 2rem 0 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background-color: #ffffff;
    color: #374151;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 12px;
}

.pagination-select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.pagination-select:hover {
    border-color: #d1d5db;
}

@media (max-width: 768px) {
    .pagination-container-modern {
        flex-direction: column;
        align-items: stretch;
    }
    
    .pagination-controls-modern {
        justify-content: center;
    }
    
    .pagination-per-page {
        justify-content: center;
    }
}
</style>

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
        // Search input with debounce and clear button
        const searchInput = document.getElementById('employeeSearch');
        const searchClearBtn = document.getElementById('search-clear-btn');
        
        if (searchInput) {
            // Show/hide clear button
            const updateClearButton = () => {
                if (searchClearBtn) {
                    const hasValue = searchInput.value && searchInput.value.trim().length > 0;
                    searchClearBtn.style.display = hasValue ? 'flex' : 'none';
                }
            };
            
            // Initial state - check if there's a value on page load
            if (searchInput.value && searchInput.value.trim().length > 0) {
                updateClearButton();
            }
            
            // Live search with debounce
            searchInput.addEventListener('input', (e) => {
                updateClearButton();
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.updateURL({ search: e.target.value.trim() });
                }, 300); // Wait 300ms after user stops typing
            });
            
            // Clear button functionality
            if (searchClearBtn) {
                searchClearBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    searchInput.value = '';
                    searchClearBtn.style.display = 'none';
                    searchInput.focus();
                    clearTimeout(this.searchTimeout);
                    this.updateURL({ search: '' });
                });
            }
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
        
        // Sort controls - now hidden, handled via table headers or default sorting
        
        // Clear filters (reset button)
        const clearBtn = document.getElementById('clearFilters');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (searchInput) {
                    searchInput.value = '';
                    if (searchClearBtn) searchClearBtn.style.display = 'none';
                }
                if (statusFilter) statusFilter.value = '';
                if (deptFilter) deptFilter.value = '';
                const sortBy = document.getElementById('sortBy');
                const sortOrder = document.getElementById('sortOrder');
                if (sortBy) sortBy.value = 'name';
                if (sortOrder) sortOrder.value = 'asc';
                this.updateURL({ 
                    search: '', 
                    status: '', 
                    type: '', 
                    sort_by: 'name', 
                    sort_order: 'asc', 
                    page_num: '1' 
                });
            });
        }
        
        // Ensure employee count is always visible and correct
        const updateEmployeeCount = () => {
            const employeeCount = document.getElementById('employee-count');
            const totalHidden = document.getElementById('total-filtered-employees');
            if (employeeCount) {
                if (totalHidden) {
                    const total = parseInt(totalHidden.value) || 0;
                    employeeCount.textContent = total.toLocaleString();
                } else {
                    // Fallback: count visible table rows
                    const table = document.querySelector('.employees-table');
                    if (table) {
                        const rows = table.querySelectorAll('tbody tr:not(.no-results)');
                        employeeCount.textContent = rows.length.toLocaleString();
                    }
                }
            }
        };
        
        // Initialize count on page load
        updateEmployeeCount();
        
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
        
        // Export modal controls
        this.bindExportModal();
        
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
        
        // Reset all sort icons to default
        document.querySelectorAll('.employees-table thead th.sortable .sort-icon').forEach(icon => {
            icon.className = 'fas fa-sort sort-icon';
        });
        
        // Add active sort icon
        const activeHeader = document.querySelector(`[data-sort="${sortBy}"]`);
        if (activeHeader) {
            const icon = activeHeader.querySelector('.sort-icon');
            if (icon) {
                icon.className = sortOrder === 'asc' ? 'fas fa-sort-up sort-icon' : 'fas fa-sort-down sort-icon';
            }
        }
    }

    bindExportModal() {
        const exportBtn = document.getElementById('exportBtn');
        const exportModal = document.getElementById('employeeExportModal');
        if (exportBtn && exportModal && !exportBtn.dataset.bound) {
            exportBtn.dataset.bound = 'true';
            exportBtn.addEventListener('click', () => {
                this.openExportModal(exportModal);
            });
        }

        const exportAllEmployeesToggle = document.getElementById('exportAllEmployeesToggle');
        const employeeSelect = document.getElementById('exportEmployeeSelect');
        if (exportAllEmployeesToggle && employeeSelect && !exportAllEmployeesToggle.dataset.bound) {
            exportAllEmployeesToggle.dataset.bound = 'true';
            const handleEmployeeToggle = () => {
                this.toggleExportEmployeeSelect(exportAllEmployeesToggle, employeeSelect);
            };
            exportAllEmployeesToggle.addEventListener('change', handleEmployeeToggle);
            handleEmployeeToggle();
        }

        const employeeSearch = document.getElementById('exportEmployeeSearch');
        if (employeeSearch && employeeSelect && !employeeSearch.dataset.bound) {
            employeeSearch.dataset.bound = 'true';
            employeeSearch.addEventListener('input', () => {
                const term = employeeSearch.value.trim().toLowerCase();
                Array.from(employeeSelect.options).forEach(option => {
                    const label = option.textContent.toLowerCase();
                    option.hidden = term.length > 0 && !label.includes(term);
                });
            });
        }

        const exportAllColumnsToggle = document.getElementById('exportAllColumnsToggle');
        const columnCheckboxes = Array.from(document.querySelectorAll('.export-column-checkbox'));
        const columnSearch = document.getElementById('exportColumnSearch');
        if (exportAllColumnsToggle && columnCheckboxes.length && !exportAllColumnsToggle.dataset.bound) {
            exportAllColumnsToggle.dataset.bound = 'true';
            const handleColumnToggle = () => {
                this.toggleExportColumnSelect(exportAllColumnsToggle, columnCheckboxes, columnSearch);
            };
            exportAllColumnsToggle.addEventListener('change', handleColumnToggle);
            handleColumnToggle();
        }

        if (columnSearch && !columnSearch.dataset.bound) {
            columnSearch.dataset.bound = 'true';
            columnSearch.addEventListener('input', () => {
                const term = columnSearch.value.trim().toLowerCase();
                document.querySelectorAll('.export-column-item').forEach(item => {
                    const label = item.textContent.toLowerCase();
                    item.style.display = term.length === 0 || label.includes(term) ? '' : 'none';
                });
            });
        }

        const selectCommonBtn = document.getElementById('selectCommonColumnsBtn');
        if (selectCommonBtn && !selectCommonBtn.dataset.bound) {
            selectCommonBtn.dataset.bound = 'true';
            selectCommonBtn.addEventListener('click', () => {
                if (exportAllColumnsToggle) {
                    exportAllColumnsToggle.checked = false;
                }
                columnCheckboxes.forEach(checkbox => {
                    checkbox.checked = checkbox.dataset.common === '1';
                    checkbox.disabled = false;
                });
                if (columnSearch) {
                    columnSearch.disabled = false;
                }
            });
        }

        const exportAllQuickBtn = document.getElementById('exportAllQuickBtn');
        const exportForm = document.getElementById('employeeExportForm');
        
        // Add form validation on submit
        if (exportForm && !exportForm.dataset.submitBound) {
            exportForm.dataset.submitBound = 'true';
            exportForm.addEventListener('submit', function(e) {
                const formData = new FormData(exportForm);
                const exportAllEmployees = formData.get('export_all_employees');
                const exportAllColumns = formData.get('export_all_columns');
                const employeeIds = formData.getAll('employee_ids[]');
                const columns = formData.getAll('columns[]');
                
                // Validate employees
                if (!exportAllEmployees && employeeIds.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one employee or enable "Export all employees"');
                    return false;
                }
                
                // Validate columns
                if (!exportAllColumns && columns.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one column or enable "All columns"');
                    return false;
                }
                
                return true;
            });
        }
        
        if (exportAllQuickBtn && exportForm && !exportAllQuickBtn.dataset.bound) {
            exportAllQuickBtn.dataset.bound = 'true';
            exportAllQuickBtn.addEventListener('click', () => {
                if (exportAllEmployeesToggle) {
                    exportAllEmployeesToggle.checked = true;
                }
                if (exportAllColumnsToggle) {
                    exportAllColumnsToggle.checked = true;
                }
                if (employeeSelect) {
                    this.toggleExportEmployeeSelect(exportAllEmployeesToggle, employeeSelect);
                }
                if (columnCheckboxes.length) {
                    this.toggleExportColumnSelect(exportAllColumnsToggle, columnCheckboxes, columnSearch);
                }
                exportForm.submit();
            });
        }
    }

    openExportModal(modalElement) {
        const exportAllEmployeesToggle = document.getElementById('exportAllEmployeesToggle');
        const employeeSelect = document.getElementById('exportEmployeeSelect');
        if (exportAllEmployeesToggle && employeeSelect) {
            const selectedIds = Array.from(document.querySelectorAll('.employee-checkbox:checked'))
                .map(checkbox => checkbox.value)
                .filter(Boolean);

            if (selectedIds.length > 0) {
                exportAllEmployeesToggle.checked = false;
                Array.from(employeeSelect.options).forEach(option => {
                    option.selected = selectedIds.includes(option.value);
                });
            }

            this.toggleExportEmployeeSelect(exportAllEmployeesToggle, employeeSelect);
        }

        const exportAllColumnsToggle = document.getElementById('exportAllColumnsToggle');
        const columnCheckboxes = Array.from(document.querySelectorAll('.export-column-checkbox'));
        const columnSearch = document.getElementById('exportColumnSearch');
        if (exportAllColumnsToggle && columnCheckboxes.length) {
            this.toggleExportColumnSelect(exportAllColumnsToggle, columnCheckboxes, columnSearch);
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    }

    toggleExportEmployeeSelect(toggle, selectElement) {
        if (!toggle || !selectElement) return;
        const shouldDisable = toggle.checked;
        selectElement.disabled = shouldDisable;
        if (shouldDisable) {
            Array.from(selectElement.options).forEach(option => {
                option.selected = false;
            });
        }
    }

    toggleExportColumnSelect(toggle, checkboxes, searchInput) {
        if (!toggle) return;
        const shouldDisable = toggle.checked;
        checkboxes.forEach(checkbox => {
            checkbox.disabled = shouldDisable;
            if (shouldDisable) {
                checkbox.checked = true;
            }
        });
        if (searchInput) {
            searchInput.disabled = shouldDisable;
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

function handlePageInputKeyPress(event) {
    if (event.key === 'Enter') {
        const input = event.target;
        const pageNum = parseInt(input.value);
        const totalPages = <?php echo isset($total_pages) ? $total_pages : 1; ?>;
        
        if (pageNum > 0 && pageNum <= totalPages) {
            changePage(pageNum);
        } else {
            // Reset to current page if invalid
            input.value = <?php echo isset($page_num) ? $page_num : 1; ?>;
            alert('Please enter a valid page number between 1 and ' + totalPages);
        }
    }
}

// Update page input when page changes
document.addEventListener('DOMContentLoaded', function() {
    const pageInput = document.getElementById('pageNumberInput');
    if (pageInput) {
        pageInput.addEventListener('blur', function() {
            const pageNum = parseInt(this.value);
            const totalPages = <?php echo isset($total_pages) ? $total_pages : 1; ?>;
            const currentPage = <?php echo isset($page_num) ? $page_num : 1; ?>;
            
            if (isNaN(pageNum) || pageNum < 1 || pageNum > totalPages) {
                this.value = currentPage;
            }
        });
    }
});

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