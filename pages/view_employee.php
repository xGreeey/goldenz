<?php
$page_title = 'View Employee - Golden Z-5 HR System';
$page = 'view_employee';

// Get employee ID from URL
$employee_id = $_GET['id'] ?? 0;

if (!$employee_id) {
    redirect_with_message('?page=employees', 'Employee ID is required.', 'danger');
}

// Get employee data
$employee = get_employee($employee_id);

if (!$employee) {
    redirect_with_message('?page=employees', 'Employee not found.', 'danger');
}

// Check if in edit mode
$edit_mode = isset($_GET['edit']) && $_GET['edit'] == '1';

// Get logged-in user information
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;
$current_user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'System Administrator';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employee'])) {
    $errors = [];
    
    // Validate required fields
    $required_fields = ['first_name', 'surname', 'employee_no', 'employee_type', 'post', 'date_hired', 'status'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    
    // If no errors, update in database
    if (empty($errors)) {
        try {
            // Prepare employee data with all fields
            $employee_data = [
                'employee_no' => $_POST['employee_no'],
                'employee_type' => $_POST['employee_type'],
                'surname' => $_POST['surname'],
                'first_name' => $_POST['first_name'],
                'middle_name' => $_POST['middle_name'] ?? null,
                'post' => $_POST['post'],
                'license_no' => $_POST['license_no'] ?? null,
                'license_exp_date' => !empty($_POST['license_exp_date']) ? $_POST['license_exp_date'] : null,
                'rlm_exp' => !empty($_POST['rlm_exp']) ? $_POST['rlm_exp'] : null,
                'date_hired' => $_POST['date_hired'],
                'cp_number' => $_POST['cp_number'] ?? null,
                'sss_no' => $_POST['sss_no'] ?? null,
                'pagibig_no' => $_POST['pagibig_no'] ?? null,
                'tin_number' => $_POST['tin_number'] ?? null,
                'philhealth_no' => $_POST['philhealth_no'] ?? null,
                'birth_date' => !empty($_POST['birth_date']) ? $_POST['birth_date'] : null,
                'height' => $_POST['height'] ?? null,
                'weight' => $_POST['weight'] ?? null,
                'address' => $_POST['address'] ?? null,
                'contact_person' => $_POST['contact_person'] ?? null,
                'relationship' => $_POST['relationship'] ?? null,
                'contact_person_address' => $_POST['contact_person_address'] ?? null,
                'contact_person_number' => $_POST['contact_person_number'] ?? null,
                'blood_type' => $_POST['blood_type'] ?? null,
                'religion' => $_POST['religion'] ?? null,
                'status' => $_POST['status'],
                'exit_date' => !empty($_POST['exit_date']) ? $_POST['exit_date'] : null,
                'exit_reason' => $_POST['exit_reason'] ?? null,
                'exit_status' => $_POST['exit_status'] ?? null,
                'final_pay_date' => !empty($_POST['final_pay_date']) ? $_POST['final_pay_date'] : null,
                'property_returned' => $_POST['property_returned'] ?? null,
                'exit_interview' => $_POST['exit_interview'] ?? null,
                'exit_notes' => $_POST['exit_notes'] ?? null,
                'nbi_clearance_no' => $_POST['nbi_clearance_no'] ?? null,
                'nbi_clearance_exp' => !empty($_POST['nbi_clearance_exp']) ? $_POST['nbi_clearance_exp'] : null,
                'police_clearance_no' => $_POST['police_clearance_no'] ?? null,
                'police_clearance_exp' => !empty($_POST['police_clearance_exp']) ? $_POST['police_clearance_exp'] : null,
                'barangay_clearance_no' => $_POST['barangay_clearance_no'] ?? null,
                'barangay_clearance_exp' => !empty($_POST['barangay_clearance_exp']) ? $_POST['barangay_clearance_exp'] : null,
                'other_clearances' => $_POST['other_clearances'] ?? null,
                'bond_amount' => !empty($_POST['bond_amount']) ? $_POST['bond_amount'] : null,
                'bond_date' => !empty($_POST['bond_date']) ? $_POST['bond_date'] : null,
                'bond_status' => $_POST['bond_status'] ?? null,
                'refund_date' => !empty($_POST['refund_date']) ? $_POST['refund_date'] : null,
                'receipt_number' => $_POST['receipt_number'] ?? null,
                'refund_amount' => !empty($_POST['refund_amount']) ? $_POST['refund_amount'] : null,
                'bond_notes' => $_POST['bond_notes'] ?? null
            ];
            
            // Handle photo upload if provided
            if (isset($_FILES['employee_photo']) && $_FILES['employee_photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/employees/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file = $_FILES['employee_photo'];
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = $employee_id . '.' . ($extension === 'jpg' ? 'jpg' : 'png');
                    $target_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        // Update photo path in database if photo column exists
                        try {
                            $pdo = get_db_connection();
                            $check_sql = "SHOW COLUMNS FROM employees LIKE 'photo'";
                            $check_stmt = $pdo->query($check_sql);
                            if ($check_stmt->rowCount() > 0) {
                                $update_sql = "UPDATE employees SET photo = ? WHERE id = ?";
                                $update_stmt = $pdo->prepare($update_sql);
                                $update_stmt->execute(['uploads/employees/' . $filename, $employee_id]);
                                $employee_data['photo'] = 'uploads/employees/' . $filename;
                            }
                        } catch (Exception $e) {
                            // Photo column doesn't exist, continue with file-based approach
                        }
                    }
                }
            }
            
            // Use the update_employee function from database.php
            $result = update_employee($employee_id, $employee_data);
            
            if ($result) {
                // Log to audit trail
                if (function_exists('log_audit_event')) {
                    log_audit_event('UPDATE', 'employees', $employee_id, $employee, $employee_data, $current_user_id);
                }
                
                // Reload employee data
                $employee = get_employee($employee_id);
                redirect_with_message('?page=view_employee&id=' . $employee_id, 'Employee updated successfully!', 'success');
                exit;
            } else {
                $errors[] = 'Failed to update employee. Please try again.';
            }
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
            error_log('Edit Employee Error: ' . $e->getMessage());
        }
    }
    
    // If there are errors, stay in edit mode
    if (!empty($errors)) {
        $edit_mode = true;
    }
}

// Get available posts for dropdown
$posts = get_posts_for_dropdown();
if (empty($posts)) {
    $posts = [
        ['post_title' => 'BENAVIDES', 'location' => 'Manila', 'available_count' => 0],
        ['post_title' => 'SAPPORO', 'location' => 'Makati', 'available_count' => 0],
        ['post_title' => 'MCMC', 'location' => 'Quezon City', 'available_count' => 0],
        ['post_title' => 'HEADQUARTERS', 'location' => 'Main Office', 'available_count' => 0],
        ['post_title' => 'MALL SECURITY', 'location' => 'Various Malls', 'available_count' => 0],
        ['post_title' => 'OFFICE SECURITY', 'location' => 'Office Buildings', 'available_count' => 0],
        ['post_title' => 'FIELD SUPERVISOR', 'location' => 'Field Operations', 'available_count' => 0]
    ];
}

// Helper function to format dates
function formatDate($date) {
    if (!$date || $date === '0000-00-00' || $date === '') {
        return 'N/A';
    }
    return date('F j, Y', strtotime($date));
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

// Helper function to format phone number
function formatPhone($phone) {
    if (!$phone || $phone === '') {
        return 'N/A';
    }
    return $phone;
}

// Helper function to calculate days until expiration
function getDaysUntilExpiration($date) {
    if (!$date || $date === '0000-00-00' || $date === '') {
        return null;
    }
    $expDate = strtotime($date);
    $now = strtotime('today');
    $diff = $expDate - $now;
    return floor($diff / (60 * 60 * 24));
}

// Helper function to get expiration indicator
function getExpirationIndicator($date, $isRequired = false) {
    if (!$date || $date === '0000-00-00' || $date === '') {
        return $isRequired ? ['type' => 'missing', 'badge' => 'bg-danger', 'text' => 'Missing', 'icon' => 'fa-exclamation-circle'] : null;
    }
    
    $days = getDaysUntilExpiration($date);
    
    if ($days < 0) {
        return ['type' => 'expired', 'badge' => 'bg-danger', 'text' => 'Expired (' . abs($days) . ' days ago)', 'icon' => 'fa-times-circle'];
    } elseif ($days <= 30) {
        return ['type' => 'expiring', 'badge' => 'bg-warning', 'text' => 'Expires in ' . $days . ' days', 'icon' => 'fa-clock'];
    } elseif ($days <= 90) {
        return ['type' => 'warning', 'badge' => 'bg-info', 'text' => 'Expires in ' . $days . ' days', 'icon' => 'fa-info-circle'];
    }
    
    return null;
}

// Helper function to check if field is missing (required)
function isFieldMissing($value, $isRequired = false) {
    if (!$isRequired) {
        return false; // Optional fields are not considered "missing"
    }
    return empty($value) || $value === 'N/A' || $value === '';
}

// Get full name
$full_name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['surname'] ?? ''));

// Determine update status
$update_status = 'up_to_date';
$update_status_text = 'Up to date';
$update_status_class = 'bg-success';

$now = strtotime('today');
$urgent_issues = 0;
$needs_update = false;

// Check for urgent issues (expired licenses)
$licenseExp = isset($employee['license_exp_date']) && $employee['license_exp_date'] ? strtotime($employee['license_exp_date']) : null;
if ($licenseExp && $licenseExp < $now) {
    $urgent_issues++;
}

$rlmExp = isset($employee['rlm_exp']) && $employee['rlm_exp'] ? strtotime($employee['rlm_exp']) : null;
if ($rlmExp && $rlmExp < $now) {
    $urgent_issues++;
}

// Check for missing required fields
$required_fields_missing = 0;
if (isFieldMissing($employee['cp_number'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['contact_person'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['contact_person_number'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['license_no'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['license_exp_date'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['sss_no'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['pagibig_no'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['tin_number'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['philhealth_no'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['nbi_clearance_no'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['police_clearance_no'] ?? '', true)) $required_fields_missing++;
if (isFieldMissing($employee['barangay_clearance_no'] ?? '', true)) $required_fields_missing++;

// Check for expiring licenses (within 30 days)
$expiring_soon = 0;
if ($licenseExp && $licenseExp >= $now && $licenseExp < strtotime('+30 days', $now)) {
    $expiring_soon++;
}
if ($rlmExp && $rlmExp >= $now && $rlmExp < strtotime('+30 days', $now)) {
    $expiring_soon++;
}

// Determine status
if ($urgent_issues > 0) {
    $update_status = 'urgent';
    $update_status_text = 'Urgent';
    $update_status_class = 'bg-danger';
} elseif ($required_fields_missing > 0 || $expiring_soon > 0) {
    $update_status = 'needs_update';
    $update_status_text = 'Needs Update';
    $update_status_class = 'bg-warning text-dark';
}

// Get created by information
$created_by_name = 'System';
$created_by_id = null;
if (isset($employee['created_by_name']) && !empty($employee['created_by_name'])) {
    $created_by_name = $employee['created_by_name'];
} elseif (isset($employee['created_by']) && $employee['created_by']) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT name, username FROM users WHERE id = ?");
        $stmt->execute([$employee['created_by']]);
        $user = $stmt->fetch();
        if ($user) {
            $created_by_name = $user['name'] ?? $user['username'] ?? 'User #' . $employee['created_by'];
        }
    } catch (Exception $e) {
        // Use default
    }
    $created_by_id = $employee['created_by'];
}

// Parse height if it exists (format: 5'7")
$height_ft = '';
$height_in = '';
if (!empty($employee['height'])) {
    if (preg_match("/(\d+)'(\d+)\"/", $employee['height'], $matches)) {
        $height_ft = $matches[1];
        $height_in = $matches[2];
    }
}

// Helper function to render field in view or edit mode
function renderField($label, $name, $value, $type = 'text', $required = false, $options = [], $edit_mode = false, $col_class = 'col-md-4') {
    $value = $value ?? '';
    $display_value = $value === '' || $value === null ? 'N/A' : htmlspecialchars($value);
    
    if ($edit_mode) {
        $required_attr = $required ? 'required' : '';
        $required_indicator = $required ? ' <span class="text-danger">*</span>' : '';
        
        echo '<div class="' . $col_class . '">';
        echo '<div class="form-group">';
        echo '<label class="text-muted small">' . htmlspecialchars($label) . $required_indicator . '</label>';
        
        if ($type === 'select') {
            echo '<select class="form-select form-select-sm" name="' . htmlspecialchars($name) . '" ' . $required_attr . '>';
            foreach ($options as $opt_value => $opt_label) {
                $selected = ($value == $opt_value) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($opt_value) . '" ' . $selected . '>' . htmlspecialchars($opt_label) . '</option>';
            }
            echo '</select>';
        } elseif ($type === 'date') {
            $date_value = ($value && $value !== 'N/A' && $value !== '0000-00-00') ? date('Y-m-d', strtotime($value)) : '';
            echo '<input type="date" class="form-control form-control-sm" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($date_value) . '" ' . $required_attr . '>';
        } elseif ($type === 'textarea') {
            echo '<textarea class="form-control form-control-sm" name="' . htmlspecialchars($name) . '" rows="3" ' . $required_attr . '>' . htmlspecialchars($value) . '</textarea>';
        } else {
            $input_type = ($type === 'number') ? 'number' : 'text';
            echo '<input type="' . $input_type . '" class="form-control form-control-sm" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" ' . $required_attr . '>';
        }
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="' . $col_class . '">';
        echo '<label class="text-muted small">' . htmlspecialchars($label);
        if ($required) echo ' <span class="text-danger">*</span>';
        echo '</label>';
        echo '<p class="mb-0 fw-semibold">' . $display_value . '</p>';
        echo '</div>';
    }
}
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="page-header-modern mb-4">
        <div class="page-header-content">
            <div class="page-title-modern">
                <h1 class="page-title-main">Employee Details</h1>
                <p class="page-subtitle">View complete employee information</p>
            </div>
            <div class="page-actions-modern">
                <a href="?page=employees&_r=<?php echo time(); ?>" class="btn btn-outline-modern">
                    <i class="fas fa-arrow-left me-2"></i>Back to Employees
                </a>
                <?php if ($edit_mode): ?>
                    <a href="?page=view_employee&id=<?php echo htmlspecialchars($employee_id); ?>" class="btn btn-outline-modern">
                        Cancel
                    </a>
                    <button type="submit" form="employeeEditForm" class="btn btn-primary-modern">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                <?php else: ?>
                    <a href="?page=view_employee&id=<?php echo htmlspecialchars($employee_id); ?>&edit=1" class="btn btn-primary-modern">
                        <i class="fas fa-edit me-1"></i>Edit Employee
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Employee Information Card - Form Style -->
    <div class="card card-modern mb-4" id="employeeDetailsForm">
        <div class="card-header-modern employee-header-gold">
            <div class="d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <h2 class="employee-name-gold mb-0">
                        <i class="fas fa-user me-2"></i>
                        <span class="employee-last-name"><?php echo htmlspecialchars($employee['surname'] ?? ''); ?></span>
                        <span class="employee-first-name"><?php echo htmlspecialchars($employee['first_name'] ?? ''); ?></span>
                        <?php if (!empty($employee['middle_name'])): ?>
                            <span class="employee-middle-name"><?php echo htmlspecialchars($employee['middle_name']); ?></span>
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark">
                        <?php echo htmlspecialchars($employee['status'] ?? 'N/A'); ?>
                    </span>
                    <span class="badge <?php echo $update_status_class; ?>" title="<?php 
                        if ($urgent_issues > 0) {
                            echo htmlspecialchars($urgent_issues . ' urgent issue(s)');
                        } elseif ($required_fields_missing > 0) {
                            echo htmlspecialchars($required_fields_missing . ' required field(s) missing');
                        } elseif ($expiring_soon > 0) {
                            echo htmlspecialchars($expiring_soon . ' license(s) expiring soon');
                        } else {
                            echo 'All information is up to date';
                        }
                    ?>">
                        <?php if ($urgent_issues > 0): ?>
                            <i class="fas fa-exclamation-triangle me-1"></i>
                        <?php elseif ($required_fields_missing > 0 || $expiring_soon > 0): ?>
                            <i class="fas fa-exclamation-circle me-1"></i>
                        <?php else: ?>
                            <i class="fas fa-check-circle me-1"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($update_status_text); ?>
                    </span>
                    <button type="button" class="btn btn-outline-modern btn-sm" onclick="saveEmployeeDetailsPDF()" title="Save Employee Details as PDF">
                        <i class="fas fa-file-pdf me-2"></i>Save PDF
                    </button>
                </div>
            </div>
        </div>
        <?php if ($edit_mode): ?>
        <form method="POST" id="employeeEditForm" enctype="multipart/form-data">
            <input type="hidden" name="update_employee" value="1">
        <?php endif; ?>
        <div class="card-body-modern">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Basic Information Section -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h4 class="form-section-title">Basic Information</h4>
                </div>
                <?php if ($edit_mode): ?>
                    <?php renderField('Employee Number', 'employee_no', $employee['employee_no'] ?? '', 'text', true, [], $edit_mode, 'col-md-4'); ?>
                    <?php 
                    $employee_type_options = ['SG' => 'Security Guard (SG)', 'LG' => 'Lady Guard (LG)', 'SO' => 'Security Officer (SO)'];
                    $current_type = $employee['employee_type'] ?? '';
                    ?>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="text-muted small">Employee Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="employee_type" required>
                                <option value="">Select Employee Type</option>
                                <?php foreach ($employee_type_options as $val => $label): ?>
                                    <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($current_type === $val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php 
                    $status_options = ['Active' => 'Active', 'Inactive' => 'Inactive', 'Terminated' => 'Terminated', 'Suspended' => 'Suspended'];
                    $current_status = $employee['status'] ?? '';
                    ?>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="form-group flex-grow-1 me-3">
                                <label class="text-muted small">Status <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="status" required>
                                    <option value="">Select Status</option>
                                    <?php foreach ($status_options as $val => $label): ?>
                                        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($current_status === $val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Photo Section -->
                            <div class="employee-photo-container ms-3">
                                <?php 
                                // Use the helper function to get the correct photo URL
                                $photo_path = get_employee_photo_url($employee['photo'] ?? null, $employee_id);
                                ?>
                                <div class="employee-photo-wrapper">
                                    <div id="photoPreview" style="position: relative;">
                                        <?php if ($photo_path): ?>
                                            <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($full_name); ?>" class="employee-photo-img" id="currentPhoto" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="employee-photo-placeholder" id="photoPlaceholder" style="display: none;">
                                                <span class="photo-placeholder-text">2X2 PHOTO</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="employee-photo-placeholder" id="photoPlaceholder">
                                                <span class="photo-placeholder-text">2X2 PHOTO</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="employee_photo" id="employee_photo" accept="image/jpeg,image/jpg,image/png" class="form-control form-control-sm mt-2 fs-xs" style="width: 140px;" onchange="previewPhoto(this)">
                                    <small class="text-muted d-block mt-1 fs-11">Max 2MB (JPG/PNG)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="col-md-4">
                        <label class="text-muted small">
                            Employee Number <span class="text-danger">*</span>
                        </label>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['employee_no'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">
                            Employee Type <span class="text-danger">*</span>
                        </label>
                        <p class="mb-0 fw-semibold"><?php echo getEmployeeTypeLabel($employee['employee_type'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <label class="text-muted small">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <p class="mb-0">
                                    <span class="badge <?php 
                                        $status = strtolower($employee['status'] ?? '');
                                        echo $status === 'active' ? 'bg-success' : ($status === 'inactive' ? 'bg-secondary' : ($status === 'terminated' ? 'bg-danger' : 'bg-warning'));
                                    ?>">
                                        <?php echo htmlspecialchars($employee['status'] ?? 'N/A'); ?>
                                    </span>
                                </p>
                            </div>
                            <!-- Photo Section -->
                            <div class="employee-photo-container ms-3">
                                <?php 
                                // Use the helper function to get the correct photo URL
                                $photo_path = get_employee_photo_url($employee['photo'] ?? null, $employee_id);
                                ?>
                                <div class="employee-photo-wrapper">
                                    <?php if ($photo_path): ?>
                                        <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($full_name); ?>" class="employee-photo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="employee-photo-placeholder" style="display: none;">
                                            <span class="photo-placeholder-text">2X2 PHOTO</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="employee-photo-placeholder">
                                            <span class="photo-placeholder-text">2X2 PHOTO</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Personal Information Section -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h4 class="form-section-title">Personal Information</h4>
                </div>
                <?php if ($edit_mode): ?>
                    <?php renderField('Last Name', 'surname', $employee['surname'] ?? '', 'text', true, [], $edit_mode, 'col-md-4'); ?>
                    <?php renderField('First Name', 'first_name', $employee['first_name'] ?? '', 'text', true, [], $edit_mode, 'col-md-4'); ?>
                    <?php renderField('Middle Name', 'middle_name', $employee['middle_name'] ?? '', 'text', false, [], $edit_mode, 'col-md-4'); ?>
                    <?php renderField('Birth Date', 'birth_date', $employee['birth_date'] ?? '', 'date', false, [], $edit_mode, 'col-md-3'); ?>
                    <?php renderField('Height', 'height', $employee['height'] ?? '', 'text', false, [], $edit_mode, 'col-md-3'); ?>
                    <?php renderField('Weight', 'weight', $employee['weight'] ?? '', 'text', false, [], $edit_mode, 'col-md-3'); ?>
                    <?php 
                    $blood_types = ['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-'];
                    $current_blood = $employee['blood_type'] ?? '';
                    ?>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="text-muted small">Blood Type</label>
                            <select class="form-select form-select-sm" name="blood_type">
                                <option value="">Select Blood Type</option>
                                <?php foreach ($blood_types as $val => $label): ?>
                                    <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($current_blood === $val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php renderField('Religion', 'religion', $employee['religion'] ?? '', 'text', false, [], $edit_mode, 'col-md-6'); ?>
                    <?php renderField('Address', 'address', $employee['address'] ?? '', 'textarea', false, [], $edit_mode, 'col-md-6'); ?>
                <?php else: ?>
                    <div class="col-md-4">
                        <label class="text-muted small">
                            Last Name <span class="text-danger">*</span>
                        </label>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['surname'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">
                            First Name <span class="text-danger">*</span>
                        </label>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['first_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">Middle Name</label>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['middle_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small">Birth Date</label>
                        <p class="mb-0 fw-semibold"><?php echo formatDate($employee['birth_date'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small">Height</label>
                        <p class="mb-0 fw-semibold">
                            <?php if ($height_ft || $height_in): ?>
                                <?php echo htmlspecialchars($height_ft); ?>'<?php echo htmlspecialchars($height_in); ?>" 
                            <?php else: ?>
                                <?php echo htmlspecialchars($employee['height'] ?? 'N/A'); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small">Weight</label>
                        <p class="mb-0 fw-semibold">
                            <?php echo !empty($employee['weight']) ? htmlspecialchars($employee['weight']) . ' kg' : 'N/A'; ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small">Blood Type</label>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['blood_type'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Religion</label>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['religion'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            Address
                            <?php if (isFieldMissing($employee['address'] ?? '', false)): ?>
                                <span class="badge bg-secondary text-white ms-2" title="Not provided"><i class="fas fa-minus-circle"></i> N/A</span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['address'] ?? '', false) ? 'text-muted' : ''; ?>">
                            <?php echo htmlspecialchars($employee['address'] ?? 'N/A'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contact Information Section -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h4 class="form-section-title">Contact Information</h4>
                </div>
                <?php if ($edit_mode): ?>
                    <?php renderField('Contact Phone Number', 'cp_number', $employee['cp_number'] ?? '', 'text', true, [], $edit_mode, 'col-md-6'); ?>
                    <?php renderField('Emergency Contact Person', 'contact_person', $employee['contact_person'] ?? '', 'text', true, [], $edit_mode, 'col-md-6'); ?>
                    <?php renderField('Relationship', 'relationship', $employee['relationship'] ?? '', 'text', true, [], $edit_mode, 'col-md-3'); ?>
                    <?php renderField('Emergency Contact Number', 'contact_person_number', $employee['contact_person_number'] ?? '', 'text', true, [], $edit_mode, 'col-md-3'); ?>
                    <?php renderField('Contact Address', 'contact_person_address', $employee['contact_person_address'] ?? '', 'textarea', false, [], $edit_mode, 'col-md-6'); ?>
                <?php else: ?>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            Contact Phone Number <span class="text-danger">*</span>
                            <?php if (isFieldMissing($employee['cp_number'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['cp_number'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo formatPhone($employee['cp_number'] ?? ''); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            Emergency Contact Person <span class="text-danger">*</span>
                            <?php if (isFieldMissing($employee['contact_person'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['contact_person'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo htmlspecialchars($employee['contact_person'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small">
                            Relationship <span class="text-danger">*</span>
                        </label>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['relationship'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small">
                            Emergency Contact Number <span class="text-danger">*</span>
                            <?php if (isFieldMissing($employee['contact_person_number'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['contact_person_number'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo formatPhone($employee['contact_person_number'] ?? ''); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Contact Address</label>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['contact_person_address'] ?? 'N/A'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Employment Information Section -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h4 class="form-section-title">Employment Information</h4>
                </div>
                <?php if ($edit_mode): ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted small">Post / Position <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="post" value="<?php echo htmlspecialchars($employee['post'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <?php renderField('Date Hired', 'date_hired', $employee['date_hired'] ?? '', 'date', true, [], $edit_mode, 'col-md-6'); ?>
                <?php else: ?>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            Post / Position <span class="text-danger">*</span>
                        </label>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['post'] ?? 'Unassigned'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            Date Hired <span class="text-danger">*</span>
                        </label>
                        <p class="mb-0 fw-semibold"><?php echo formatDate($employee['date_hired'] ?? ''); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- License Information Section -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h4 class="form-section-title">License & Regulatory Information</h4>
                </div>
                <?php if ($edit_mode): ?>
                    <?php renderField('License Number', 'license_no', $employee['license_no'] ?? '', 'text', true, [], $edit_mode, 'col-md-4'); ?>
                    <?php 
                    $license_exp_value = !empty($employee['license_exp_date']) && $employee['license_exp_date'] !== '0000-00-00' ? $employee['license_exp_date'] : '';
                    renderField('License Expiration Date', 'license_exp_date', $license_exp_value, 'date', true, [], $edit_mode, 'col-md-4'); 
                    ?>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="text-muted small">Has RLM</label>
                            <select class="form-select form-select-sm" name="has_rlm" id="has_rlm" onchange="toggleRLMExpiration(this.value)">
                                <option value="0" <?php echo empty($employee['rlm_exp']) ? 'selected' : ''; ?>>No</option>
                                <option value="1" <?php echo !empty($employee['rlm_exp']) ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4" id="rlm_exp_container" style="display: <?php echo !empty($employee['rlm_exp']) ? 'block' : 'none'; ?>;">
                        <?php 
                        $rlm_exp_value = !empty($employee['rlm_exp']) && $employee['rlm_exp'] !== '0000-00-00' ? $employee['rlm_exp'] : '';
                        renderField('RLM Expiration', 'rlm_exp', $rlm_exp_value, 'date', false, [], $edit_mode, 'col-md-12'); 
                        ?>
                    </div>
                <?php else: ?>
                    <div class="col-md-4">
                        <label class="text-muted small">
                            License Number <span class="text-danger">*</span>
                            <?php if (isFieldMissing($employee['license_no'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['license_no'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo htmlspecialchars($employee['license_no'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">
                            License Expiration Date <span class="text-danger">*</span>
                            <?php 
                            $licenseIndicator = getExpirationIndicator($employee['license_exp_date'] ?? '', true);
                            if ($licenseIndicator): ?>
                                <span class="badge <?php echo $licenseIndicator['badge']; ?> text-dark ms-2" title="<?php echo htmlspecialchars($licenseIndicator['text']); ?>">
                                    <i class="fas <?php echo $licenseIndicator['icon']; ?>"></i> <?php echo htmlspecialchars($licenseIndicator['text']); ?>
                                </span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php 
                            if ($licenseIndicator && $licenseIndicator['type'] === 'expired') echo 'text-danger';
                            elseif ($licenseIndicator && $licenseIndicator['type'] === 'expiring') echo 'text-warning';
                            ?>">
                            <?php echo formatDate($employee['license_exp_date'] ?? ''); ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">
                            Has RLM
                        </label>
                        <p class="mb-0 fw-semibold"><?php echo !empty($employee['rlm_exp']) ? 'Yes' : 'No'; ?></p>
                    </div>
                    <?php if (!empty($employee['rlm_exp'])): ?>
                    <div class="col-md-4">
                        <label class="text-muted small">
                            RLM Expiration
                            <?php 
                            $rlmIndicator = getExpirationIndicator($employee['rlm_exp'] ?? '', false);
                            if ($rlmIndicator): ?>
                                <span class="badge <?php echo $rlmIndicator['badge']; ?> text-dark ms-2" title="<?php echo htmlspecialchars($rlmIndicator['text']); ?>">
                                    <i class="fas <?php echo $rlmIndicator['icon']; ?>"></i> <?php echo htmlspecialchars($rlmIndicator['text']); ?>
                                </span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php 
                            if ($rlmIndicator && $rlmIndicator['type'] === 'expired') echo 'text-danger';
                            elseif ($rlmIndicator && $rlmIndicator['type'] === 'expiring') echo 'text-warning';
                            ?>">
                            <?php echo formatDate($employee['rlm_exp'] ?? ''); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Government IDs Section -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h4 class="form-section-title">Government Identification Numbers</h4>
                </div>
                <?php if ($edit_mode): ?>
                    <?php renderField('SSS Number', 'sss_no', $employee['sss_no'] ?? '', 'text', true, [], $edit_mode, 'col-md-6'); ?>
                    <?php renderField('PAG-IBIG Number', 'pagibig_no', $employee['pagibig_no'] ?? '', 'text', true, [], $edit_mode, 'col-md-6'); ?>
                    <?php renderField('TIN Number', 'tin_number', $employee['tin_number'] ?? '', 'text', true, [], $edit_mode, 'col-md-6'); ?>
                    <?php renderField('PhilHealth Number', 'philhealth_no', $employee['philhealth_no'] ?? '', 'text', true, [], $edit_mode, 'col-md-6'); ?>
                <?php else: ?>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            SSS Number <span class="text-danger">*</span>
                            <?php if (isFieldMissing($employee['sss_no'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['sss_no'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo htmlspecialchars($employee['sss_no'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            PAG-IBIG Number <span class="text-danger">*</span>
                            <?php if (isFieldMissing($employee['pagibig_no'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['pagibig_no'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo htmlspecialchars($employee['pagibig_no'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            TIN Number <span class="text-danger">*</span>
                            <?php if (isFieldMissing($employee['tin_number'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['tin_number'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo htmlspecialchars($employee['tin_number'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            PhilHealth Number <span class="text-danger">*</span>
                            <?php if (isFieldMissing($employee['philhealth_no'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['philhealth_no'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo htmlspecialchars($employee['philhealth_no'] ?? 'N/A'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Exit Requirements -->
            <div class="mb-4">
                <div class="col-12 mb-3">
                    <h4 class="form-section-title collapsible-section" data-target="exit-requirements-content" style="cursor: pointer;">
                        <i class="fas fa-chevron-down me-2"></i>Exit Requirements
                        <small class="text-muted ms-2">(Optional)</small>
                    </h4>
                </div>
                <div id="exit-requirements-content" class="collapse show">
                    <div class="row g-3">
                        <?php if ($edit_mode): ?>
                            <?php 
                            $exit_date_value = !empty($employee['exit_date']) && $employee['exit_date'] !== '0000-00-00' ? $employee['exit_date'] : '';
                            renderField('Exit Date', 'exit_date', $exit_date_value, 'date', false, [], $edit_mode, 'col-md-4'); 
                            ?>
                            <?php renderField('Exit Reason', 'exit_reason', $employee['exit_reason'] ?? '', 'text', false, [], $edit_mode, 'col-md-4'); ?>
                            <?php renderField('Exit Status', 'exit_status', $employee['exit_status'] ?? '', 'text', false, [], $edit_mode, 'col-md-4'); ?>
                            <?php 
                            $final_pay_date_value = !empty($employee['final_pay_date']) && $employee['final_pay_date'] !== '0000-00-00' ? $employee['final_pay_date'] : '';
                            renderField('Final Pay Date', 'final_pay_date', $final_pay_date_value, 'date', false, [], $edit_mode, 'col-md-4'); 
                            ?>
                            <?php renderField('Company Property Returned', 'property_returned', $employee['property_returned'] ?? '', 'text', false, [], $edit_mode, 'col-md-4'); ?>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-muted small">Exit Interview Completed</label>
                                    <select class="form-select form-select-sm" name="exit_interview">
                                        <option value="">Select</option>
                                        <option value="1" <?php echo (!empty($employee['exit_interview']) && $employee['exit_interview'] === '1') ? 'selected' : ''; ?>>Yes</option>
                                        <option value="0" <?php echo (!empty($employee['exit_interview']) && $employee['exit_interview'] === '0') ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                            </div>
                            <?php renderField('Exit Notes', 'exit_notes', $employee['exit_notes'] ?? '', 'textarea', false, [], $edit_mode, 'col-md-12'); ?>
                        <?php else: ?>
                            <div class="col-md-4">
                                <label class="text-muted small">Exit Date</label>
                                <p class="mb-0 fw-semibold"><?php echo formatDate($employee['exit_date'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Exit Reason</label>
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['exit_reason'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Exit Status</label>
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['exit_status'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Final Pay Date</label>
                                <p class="mb-0 fw-semibold"><?php echo formatDate($employee['final_pay_date'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Company Property Returned</label>
                                <p class="mb-0 fw-semibold"><?php echo !empty($employee['property_returned']) ? htmlspecialchars($employee['property_returned']) : 'N/A'; ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Exit Interview Completed</label>
                                <p class="mb-0 fw-semibold"><?php echo !empty($employee['exit_interview']) ? ($employee['exit_interview'] === '1' ? 'Yes' : 'No') : 'N/A'; ?></p>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted small">Exit Notes</label>
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['exit_notes'] ?? 'N/A'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Clearances -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h4 class="form-section-title">Clearances</h4>
                </div>
                <?php if ($edit_mode): ?>
                    <?php renderField('NBI Clearance', 'nbi_clearance_no', $employee['nbi_clearance_no'] ?? '', 'text', true, [], $edit_mode, 'col-md-6'); ?>
                    <?php 
                    $nbi_exp_value = !empty($employee['nbi_clearance_exp']) && $employee['nbi_clearance_exp'] !== '0000-00-00' ? $employee['nbi_clearance_exp'] : '';
                    renderField('NBI Clearance Expiration', 'nbi_clearance_exp', $nbi_exp_value, 'date', false, [], $edit_mode, 'col-md-6'); 
                    ?>
                    <?php renderField('Police Clearance', 'police_clearance_no', $employee['police_clearance_no'] ?? '', 'text', true, [], $edit_mode, 'col-md-6'); ?>
                    <?php 
                    $police_exp_value = !empty($employee['police_clearance_exp']) && $employee['police_clearance_exp'] !== '0000-00-00' ? $employee['police_clearance_exp'] : '';
                    renderField('Police Clearance Expiration', 'police_clearance_exp', $police_exp_value, 'date', false, [], $edit_mode, 'col-md-6'); 
                    ?>
                    <?php renderField('Barangay Clearance', 'barangay_clearance_no', $employee['barangay_clearance_no'] ?? '', 'text', true, [], $edit_mode, 'col-md-6'); ?>
                    <?php 
                    $barangay_exp_value = !empty($employee['barangay_clearance_exp']) && $employee['barangay_clearance_exp'] !== '0000-00-00' ? $employee['barangay_clearance_exp'] : '';
                    renderField('Barangay Clearance Expiration', 'barangay_clearance_exp', $barangay_exp_value, 'date', false, [], $edit_mode, 'col-md-6'); 
                    ?>
                    <?php renderField('Other Clearances', 'other_clearances', $employee['other_clearances'] ?? '', 'textarea', false, [], $edit_mode, 'col-md-12'); ?>
                <?php else: ?>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            NBI Clearance <span class="text-danger">*</span>
                            <?php 
                            if (isFieldMissing($employee['nbi_clearance_no'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif;
                            $nbiExp = !empty($employee['nbi_clearance_exp']) ? getExpirationIndicator($employee['nbi_clearance_exp'], false) : null;
                            if ($nbiExp): ?>
                                <span class="badge <?php echo $nbiExp['badge']; ?> text-dark ms-2" title="<?php echo htmlspecialchars($nbiExp['text']); ?>">
                                    <i class="fas <?php echo $nbiExp['icon']; ?>"></i> <?php echo htmlspecialchars($nbiExp['text']); ?>
                                </span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['nbi_clearance_no'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo htmlspecialchars($employee['nbi_clearance_no'] ?? 'N/A'); ?>
                            <?php if (!empty($employee['nbi_clearance_exp'])): ?>
                                <br><small class="text-muted">Expires: <?php echo formatDate($employee['nbi_clearance_exp']); ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            Police Clearance <span class="text-danger">*</span>
                            <?php 
                            if (isFieldMissing($employee['police_clearance_no'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif;
                            $policeExp = !empty($employee['police_clearance_exp']) ? getExpirationIndicator($employee['police_clearance_exp'], false) : null;
                            if ($policeExp): ?>
                                <span class="badge <?php echo $policeExp['badge']; ?> text-dark ms-2" title="<?php echo htmlspecialchars($policeExp['text']); ?>">
                                    <i class="fas <?php echo $policeExp['icon']; ?>"></i> <?php echo htmlspecialchars($policeExp['text']); ?>
                                </span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['police_clearance_no'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo htmlspecialchars($employee['police_clearance_no'] ?? 'N/A'); ?>
                            <?php if (!empty($employee['police_clearance_exp'])): ?>
                                <br><small class="text-muted">Expires: <?php echo formatDate($employee['police_clearance_exp']); ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">
                            Barangay Clearance <span class="text-danger">*</span>
                            <?php 
                            if (isFieldMissing($employee['barangay_clearance_no'] ?? '', true)): ?>
                                <span class="badge bg-danger ms-2" title="Required field missing"><i class="fas fa-exclamation-circle"></i> Missing</span>
                            <?php endif;
                            $barangayExp = !empty($employee['barangay_clearance_exp']) ? getExpirationIndicator($employee['barangay_clearance_exp'], false) : null;
                            if ($barangayExp): ?>
                                <span class="badge <?php echo $barangayExp['badge']; ?> text-dark ms-2" title="<?php echo htmlspecialchars($barangayExp['text']); ?>">
                                    <i class="fas <?php echo $barangayExp['icon']; ?>"></i> <?php echo htmlspecialchars($barangayExp['text']); ?>
                                </span>
                            <?php endif; ?>
                        </label>
                        <p class="mb-0 fw-semibold <?php echo isFieldMissing($employee['barangay_clearance_no'] ?? '', true) ? 'text-danger' : ''; ?>">
                            <?php echo htmlspecialchars($employee['barangay_clearance_no'] ?? 'N/A'); ?>
                            <?php if (!empty($employee['barangay_clearance_exp'])): ?>
                                <br><small class="text-muted">Expires: <?php echo formatDate($employee['barangay_clearance_exp']); ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Other Clearances</label>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['other_clearances'] ?? 'N/A'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cash Bond -->
            <div class="mb-4">
                <div class="col-12 mb-3">
                    <h4 class="form-section-title collapsible-section" data-target="cash-bond-content" style="cursor: pointer;">
                        <i class="fas fa-chevron-down me-2"></i>Cash Bond
                    </h4>
                </div>
                <div id="cash-bond-content" class="collapse show">
                    <div class="row g-3">
                        <?php if ($edit_mode): ?>
                            <?php renderField('Bond Amount', 'bond_amount', $employee['bond_amount'] ?? '', 'number', false, [], $edit_mode, 'col-md-4'); ?>
                            <?php 
                            $bond_date_value = !empty($employee['bond_date']) && $employee['bond_date'] !== '0000-00-00' ? $employee['bond_date'] : '';
                            renderField('Bond Date', 'bond_date', $bond_date_value, 'date', false, [], $edit_mode, 'col-md-4'); 
                            ?>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-muted small">Bond Status</label>
                                    <select class="form-select form-select-sm" name="bond_status">
                                        <option value="">Select Status</option>
                                        <option value="Active" <?php echo (!empty($employee['bond_status']) && strtolower($employee['bond_status']) === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="Refunded" <?php echo (!empty($employee['bond_status']) && strtolower($employee['bond_status']) === 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                                        <option value="Forfeited" <?php echo (!empty($employee['bond_status']) && strtolower($employee['bond_status']) === 'forfeited') ? 'selected' : ''; ?>>Forfeited</option>
                                    </select>
                                </div>
                            </div>
                            <?php 
                            $refund_date_value = !empty($employee['refund_date']) && $employee['refund_date'] !== '0000-00-00' ? $employee['refund_date'] : '';
                            if (empty($refund_date_value) && !empty($employee['bond_refund_date']) && $employee['bond_refund_date'] !== '0000-00-00') {
                                $refund_date_value = $employee['bond_refund_date'];
                            }
                            renderField('Refund Date', 'refund_date', $refund_date_value, 'date', false, [], $edit_mode, 'col-md-4'); 
                            ?>
                            <?php 
                            $receipt_number = $employee['receipt_number'] ?? '';
                            if (empty($receipt_number) && !empty($employee['bond_receipt_no'])) {
                                $receipt_number = $employee['bond_receipt_no'];
                            }
                            renderField('Receipt Number', 'receipt_number', $receipt_number, 'text', false, [], $edit_mode, 'col-md-4'); 
                            ?>
                            <?php 
                            $refund_amount = $employee['refund_amount'] ?? '';
                            if (empty($refund_amount) && !empty($employee['bond_refund_amount'])) {
                                $refund_amount = $employee['bond_refund_amount'];
                            }
                            renderField('Refund Amount', 'refund_amount', $refund_amount, 'number', false, [], $edit_mode, 'col-md-4'); 
                            ?>
                            <?php renderField('Bond Notes', 'bond_notes', $employee['bond_notes'] ?? '', 'textarea', false, [], $edit_mode, 'col-md-12'); ?>
                        <?php else: ?>
                            <div class="col-md-4">
                                <label class="text-muted small">Bond Amount</label>
                                <p class="mb-0 fw-semibold">
                                    <?php echo !empty($employee['bond_amount']) ? '₱' . number_format($employee['bond_amount'], 2) : 'N/A'; ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Bond Date</label>
                                <p class="mb-0 fw-semibold"><?php echo formatDate($employee['bond_date'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Bond Status</label>
                                <p class="mb-0">
                                    <?php if (!empty($employee['bond_status'])): ?>
                                        <span class="badge <?php 
                                            $bondStatus = strtolower($employee['bond_status']);
                                            echo $bondStatus === 'active' ? 'bg-success' : ($bondStatus === 'refunded' ? 'bg-info' : ($bondStatus === 'forfeited' ? 'bg-danger' : 'bg-secondary'));
                                        ?>">
                                            <?php echo htmlspecialchars($employee['bond_status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="fw-semibold">N/A</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Refund Date</label>
                                <p class="mb-0 fw-semibold"><?php echo formatDate($employee['refund_date'] ?? $employee['bond_refund_date'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Receipt Number</label>
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['receipt_number'] ?? $employee['bond_receipt_no'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Refund Amount</label>
                                <p class="mb-0 fw-semibold">
                                    <?php 
                                    $refund_amt = $employee['refund_amount'] ?? $employee['bond_refund_amount'] ?? '';
                                    echo !empty($refund_amt) ? '₱' . number_format($refund_amt, 2) : 'N/A'; 
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted small">Bond Notes</label>
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($employee['bond_notes'] ?? 'N/A'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Record Information Section -->
            <div class="row g-3">
                <div class="col-12">
                    <h4 class="form-section-title">Record Information</h4>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Status</label>
                    <p class="mb-0">
                        <span class="badge <?php 
                            $status = strtolower($employee['status'] ?? '');
                            echo $status === 'active' ? 'bg-success' : ($status === 'inactive' ? 'bg-secondary' : ($status === 'terminated' ? 'bg-danger' : 'bg-warning'));
                        ?>">
                            <?php echo htmlspecialchars($employee['status'] ?? 'N/A'); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Created At</label>
                    <p class="mb-0 fw-semibold"><?php echo formatDate($employee['created_at'] ?? ''); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Last Updated</label>
                    <p class="mb-0 fw-semibold"><?php echo formatDate($employee['updated_at'] ?? ''); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Recorded By</label>
                    <p class="mb-0 fw-semibold">
                        <?php echo htmlspecialchars($created_by_name); ?>
                        <?php if ($created_by_id): ?>
                            <br><small class="text-muted">(User ID: <?php echo $created_by_id; ?>)</small>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        <?php if ($edit_mode): ?>
        </form>
        <?php endif; ?>
    </div>
</div>

<style>
/* Page Header - Card Style with Centered/Compressed Layout */
.page-header-modern {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    margin-bottom: 1.5rem;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
}

.page-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
}

.page-title-modern {
    flex: 1;
}

.page-title-main {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.page-subtitle {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0;
    line-height: 1.5;
}

.page-actions-modern {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-shrink: 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

/* Subtle Card Shadows for Depth */
.container-fluid .card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    transition: box-shadow 0.2s ease;
}

.container-fluid .card:hover {
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08), 0 8px 16px rgba(0, 0, 0, 0.06);
}

.page-title h1 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 600;
}

.page-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

/* Modern Button Styling */
.page-actions .btn-primary {
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.25);
    color: #ffffff;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.page-actions .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.35);
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: #ffffff;
}

.page-actions .btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(31, 178, 213, 0.25);
}

.page-actions .btn-outline-secondary {
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

.page-actions .btn-outline-secondary:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Modern Button Styles for Page Header */
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

/* Responsive Design for Page Header */
@media (max-width: 768px) {
    .page-header-modern {
        padding: 1rem;
    }
    
    .page-header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .page-actions-modern {
        width: 100%;
        flex-wrap: wrap;
    }
    
    .page-actions-modern .btn {
        flex: 1;
        min-width: 120px;
    }
}

/* Card Modern Styling */
.card-modern {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    background: #ffffff;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

.card-header-modern {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
}

.card-body-modern {
    padding: 2rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-top: none;
}

/* Form Style Styling */
.card-body-modern .form-section-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.card-body-modern .form-group {
    margin-bottom: 1.25rem;
}

.card-body-modern .form-group label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.card-body-modern .form-control,
.card-body-modern .form-select {
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background: #ffffff;
}

.card-body-modern .form-control:focus,
.card-body-modern .form-select:focus {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
    outline: none;
}

.card-body-modern .row {
    margin-bottom: 1rem;
}

.card-body-modern .row:last-child {
    margin-bottom: 0;
}

/* Form field display (non-edit mode) */
.card-body-modern .form-field-display {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 0.75rem;
}

.card-body-modern .form-field-display label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
    display: block;
}

.card-body-modern .form-field-display .field-value {
    font-size: 0.9375rem;
    color: #1e293b;
    font-weight: 500;
    padding: 0.25rem 0;
}

/* Form input styling */
.card-body-modern input[type="text"],
.card-body-modern input[type="date"],
.card-body-modern input[type="email"],
.card-body-modern input[type="tel"],
.card-body-modern input[type="number"],
.card-body-modern textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    background: #ffffff;
    transition: all 0.2s ease;
}

.card-body-modern input[type="text"]:focus,
.card-body-modern input[type="date"]:focus,
.card-body-modern input[type="email"]:focus,
.card-body-modern input[type="tel"]:focus,
.card-body-modern input[type="number"]:focus,
.card-body-modern textarea:focus {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
    outline: none;
}

/* Form sections spacing */
.card-body-modern .row.g-3 {
    margin-bottom: 1.25rem;
}

.card-body-modern .row.g-3:last-child {
    margin-bottom: 0;
}

/* Form grid styling */
.card-body-modern .col-md-3,
.card-body-modern .col-md-4,
.card-body-modern .col-md-6,
.card-body-modern .col-md-12 {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

/* Reduce row gap */
.card-body-modern .row.g-3 {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 0.75rem;
}

/* Read-only form fields (view mode) */
.card-body-modern .text-muted.small {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
    display: block;
}

.card-body-modern .fw-semibold,
.card-body-modern .fw-bold {
    font-size: 0.9375rem;
    color: #1e293b;
    font-weight: 600;
}

.employee-header-gold {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 25%, #ffd700 50%, #ffc107 75%, #ffd700 100%);
    border-bottom: 3px solid #daa520;
    padding: 1rem 1.25rem;
    box-shadow: 0 2px 8px rgba(218, 165, 32, 0.3);
    border-radius: 14px 14px 0 0;
}

.employee-name-gold {
    font-size: 2rem;
    font-weight: 700;
    color: #1a1a1a;
    text-shadow: 1px 1px 3px rgba(255, 255, 255, 0.5), 0 0 10px rgba(255, 215, 0, 0.3);
    margin: 0;
    letter-spacing: 0.5px;
}

.employee-first-name {
    font-family: 'Arial', 'Helvetica', sans-serif;
    font-weight: 500;
    font-size: 1.5rem;
    color: #1a1a1a;
    margin-right: 0.5rem;
}

.employee-middle-name {
    font-family: 'Arial', 'Helvetica', sans-serif;
    font-weight: 400;
    font-size: 1.5rem;
    color: #1a1a1a;
    margin-right: 0.5rem;
}

.employee-last-name {
    font-family: 'Arial', 'Helvetica', sans-serif;
    font-weight: 700;
    font-size: 1.5rem;
    color: #1a1a1a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.employee-photo-container {
    flex-shrink: 0;
}

.employee-photo-wrapper {
    width: 100px;
    height: 100px;
    border-radius: 8px;
}

.employee-photo-img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}

.employee-photo-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 8px;
    overflow: hidden;
    border: 3px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
}

.employee-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.employee-photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.employee-photo-placeholder i {
    font-size: 2.5rem;
    margin-bottom: 0.25rem;
    color: #adb5bd;
}

.employee-photo-placeholder small {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #868e96;
}

.employee-id-gold {
    font-size: 0.95rem;
    color: #8b6914;
    font-weight: 600;
    margin-top: 0.25rem;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.3);
}

.text-muted.small {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 0.5rem;
    display: block;
    color: #495057;
    font-family: 'Arial', 'Helvetica', sans-serif;
}

.fw-semibold {
    font-weight: 400;
    color: var(--interface-text, #212529);
    font-size: 1.05rem;
    font-family: 'Georgia', 'Times New Roman', serif;
    line-height: 1.6;
    font-style: normal;
}

.form-section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--interface-text, #212529);
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 3px solid var(--interface-border, #dee2e6);
    font-family: 'Arial', 'Helvetica', sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.collapsible-section {
    user-select: none;
    transition: all 0.3s ease;
}

.collapsible-section:hover {
    color: var(--primary-color, #0d6efd);
}

.collapsible-section i {
    transition: transform 0.3s ease;
}

.collapsible-section.collapsed i {
    transform: rotate(-90deg);
}

/* Print Styles for Legal Paper - Compact Format */
@media print {
    /* Set page size to legal portrait with compact margins */
    @page {
        size: legal portrait;
        margin: 0.4in 0.5in;
    }
    
    /* Compact body styling */
    body {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
        font-size: 9pt !important;
        color: #000 !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow: visible !important;
        line-height: 1.3 !important;
    }
    
    /* Hide everything except card-body-modern */
    body * {
        visibility: hidden;
    }
    
    .card-body-modern,
    .card-body-modern * {
        visibility: visible;
    }
    
    /* Hide non-print UI elements */
    .page-header-modern,
    .page-actions-modern,
    .page-header-content,
    .page-title-modern,
    .page-actions-modern,
    .btn,
    button,
    .sidebar,
    .main-content header,
    nav,
    .navbar,
    .pagination-controls,
    .alert,
    .card-header-modern,
    .employee-header-gold,
    .employee-photo-container input[type="file"],
    .form-control[type="file"],
    input[type="file"],
    input[type="submit"],
    input[type="button"],
    .badge:empty {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Container and layout adjustments */
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    
    /* Main profile container - remove shadows, transforms, fixed widths */
    #employeeDetailsForm,
    .card-modern,
    .card {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        transform: none !important;
        position: relative !important;
        left: auto !important;
        top: auto !important;
        page-break-inside: avoid;
    }
    
    /* Card body styling - compact padding */
    .card-body-modern {
        padding: 0.5rem 0.75rem !important;
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        border-radius: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        position: relative !important;
        left: auto !important;
        top: auto !important;
        transform: none !important;
        page-break-inside: avoid;
    }
    
    /* Remove all shadows and transforms */
    * {
        box-shadow: none !important;
        text-shadow: none !important;
        transform: none !important;
    }
    
    /* Ensure all sections are visible */
    .collapsible-section {
        display: block !important;
    }
    
    .collapse,
    .collapse.show {
        display: block !important;
        visibility: visible !important;
    }
    
    .collapsible-content {
        display: block !important;
    }
    
    /* Compact section titles - reduced spacing */
    .form-section-title {
        border-bottom: 1px solid #000 !important;
        padding-bottom: 0.3rem !important;
        margin-bottom: 0.5rem !important;
        margin-top: 0.5rem !important;
        page-break-after: avoid;
        font-size: 0.95rem !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        text-align: left !important;
    }
    
    /* Compact field labels */
    .text-muted.small {
        font-size: 0.7rem !important;
        margin-bottom: 0.15rem !important;
        line-height: 1.2 !important;
        text-align: left !important;
    }
    
    /* Compact field values */
    .fw-semibold,
    .fw-bold {
        font-size: 0.85rem !important;
        margin-bottom: 0.35rem !important;
        line-height: 1.3 !important;
        text-align: left !important;
    }
    
    /* Compact rows - reduced spacing */
    .row {
        page-break-inside: avoid;
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin-bottom: 0.4rem !important;
    }
    
    .row.g-3 {
        margin-bottom: 0.4rem !important;
    }
    
    [class*="col-"] {
        padding-left: 0.4rem !important;
        padding-right: 0.4rem !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .mb-4 {
        margin-bottom: 0.5rem !important;
    }
    
    /* Compact form groups */
    .form-group {
        margin-bottom: 0.4rem !important;
    }
    
    /* Compact tables */
    table {
        border-collapse: collapse !important;
        width: 100% !important;
        max-width: 100% !important;
        font-size: 0.75rem !important;
        margin-bottom: 0.4rem !important;
        page-break-inside: avoid;
    }
    
    table th,
    table td {
        border: 1px solid #000 !important;
        padding: 0.25rem 0.4rem !important;
        text-align: left !important;
        vertical-align: top !important;
    }
    
    /* Compact badges */
    .badge {
        font-size: 0.65rem !important;
        padding: 0.15rem 0.3rem !important;
        display: inline-block !important;
    }
    
    /* Compact photo */
    .employee-photo-container {
        max-width: 80px !important;
    }
    
    .employee-photo-wrapper,
    .employee-photo-img,
    .employee-photo-placeholder {
        max-width: 80px !important;
        max-height: 80px !important;
    }
    
    /* Compact paragraphs */
    p {
        margin-bottom: 0.25rem !important;
        line-height: 1.3 !important;
    }
    
    /* Ensure proper alignment */
    .text-center {
        text-align: center !important;
    }
    
    .text-left {
        text-align: left !important;
    }
    
    .text-right {
        text-align: right !important;
    }
    
    /* Page break handling */
    .row,
    .form-section-title,
    .card-body-modern > .row:first-child {
        page-break-inside: avoid;
    }
    
    /* Prevent content from being cut off */
    img {
        max-width: 100% !important;
        height: auto !important;
        page-break-inside: avoid;
    }
}
</style>

<script>
// Save Employee Details as PDF - compact format
function saveEmployeeDetailsPDF() {
    // Get the card-body-modern element
    const cardBody = document.querySelector('.card-body-modern');
    if (!cardBody) {
        alert('Employee details not found.');
        return;
    }
    
    // Clone the card body content
    const printContent = cardBody.cloneNode(true);
    
    // Expand all collapsed sections in the clone
    const collapsedSections = printContent.querySelectorAll('.collapse');
    collapsedSections.forEach(section => {
        section.classList.add('show');
        section.classList.remove('collapse');
    });
    
    // Create a new window for printing/saving PDF
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    // Collect all stylesheet links from the current page
    const stylesheetLinks = [];
    document.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
        const href = link.href;
        if (href) {
            stylesheetLinks.push(`<link rel="stylesheet" href="${href}">`);
        }
    });
    
    // Get all inline styles from style tags
    let inlineStyles = '';
    document.querySelectorAll('style').forEach(styleTag => {
        inlineStyles += styleTag.innerHTML;
    });
    
    // Get employee name for PDF filename
    const employeeName = document.querySelector('.employee-name-gold')?.textContent?.trim() || 'Employee';
    const sanitizedName = employeeName.replace(/[^a-z0-9]/gi, '_').toLowerCase();
    
    // Add compact print-specific styles for PDF
    const printOverrides = `
        <style>
            @page {
                margin: 0.4in 0.5in;
                size: legal portrait;
            }
            
            /* Compact body styling */
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 9pt !important;
                line-height: 1.3 !important;
            }
            
            /* Hide buttons and interactive elements */
            .btn,
            button,
            input[type="file"],
            input[type="submit"],
            input[type="button"],
            .alert {
                display: none !important;
            }
            
            /* Ensure all collapsed sections are visible */
            .collapse,
            .collapse.show {
                display: block !important;
            }
            
            /* Compact card body - reduce padding */
            .card-body-modern {
                padding: 0.5rem 0.75rem !important;
                margin: 0 !important;
            }
            
            /* Compact section titles - reduce spacing */
            .form-section-title {
                font-size: 0.95rem !important;
                margin-top: 0.5rem !important;
                margin-bottom: 0.5rem !important;
                padding-bottom: 0.3rem !important;
                page-break-after: avoid;
            }
            
            /* Compact field labels */
            .text-muted.small {
                font-size: 0.7rem !important;
                margin-bottom: 0.15rem !important;
                line-height: 1.2 !important;
            }
            
            /* Compact field values */
            .fw-semibold,
            .fw-bold {
                font-size: 0.85rem !important;
                margin-bottom: 0.35rem !important;
                line-height: 1.3 !important;
            }
            
            /* Compact rows - reduce spacing */
            .row {
                margin-bottom: 0.4rem !important;
                page-break-inside: avoid;
            }
            
            .row.g-3 {
                margin-bottom: 0.4rem !important;
            }
            
            .mb-4 {
                margin-bottom: 0.5rem !important;
            }
            
            /* Compact columns */
            [class*="col-"] {
                padding-left: 0.4rem !important;
                padding-right: 0.4rem !important;
            }
            
            /* Compact form groups */
            .form-group {
                margin-bottom: 0.4rem !important;
            }
            
            /* Compact tables */
            table {
                font-size: 0.75rem !important;
                margin-bottom: 0.4rem !important;
            }
            
            table th,
            table td {
                padding: 0.25rem 0.4rem !important;
            }
            
            /* Compact badges */
            .badge {
                font-size: 0.65rem !important;
                padding: 0.15rem 0.3rem !important;
            }
            
            /* Compact photo */
            .employee-photo-container {
                max-width: 80px !important;
            }
            
            .employee-photo-wrapper,
            .employee-photo-img,
            .employee-photo-placeholder {
                max-width: 80px !important;
                max-height: 80px !important;
            }
            
            /* Remove unnecessary spacing */
            p {
                margin-bottom: 0.25rem !important;
            }
            
            /* Page break handling */
            .form-section-title {
                page-break-after: avoid;
            }
            
            .row {
                page-break-inside: avoid;
            }
        </style>
    `;
    
    // Write the HTML content with all original stylesheets and compact styles
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Employee Details - ${employeeName}</title>
            <meta charset="UTF-8">
            ${stylesheetLinks.join('\n')}
            <style>${inlineStyles}</style>
            ${printOverrides}
        </head>
        <body>
            ${printContent.outerHTML}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Wait for content to load, then trigger print dialog (user can save as PDF)
    printWindow.onload = function() {
        setTimeout(function() {
            printWindow.print();
            // Note: User will see print dialog where they can choose "Save as PDF"
        }, 500);
    };
}

// Photo preview function
function previewPhoto(input) {
    const previewContainer = document.getElementById('photoPreview');
    const currentPhoto = document.getElementById('currentPhoto');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Hide placeholder if exists
            if (photoPlaceholder) {
                photoPlaceholder.style.display = 'none';
            }
            
            // Create or update preview image
            let previewImg = document.getElementById('photoPreviewImg');
            if (!previewImg) {
                previewImg = document.createElement('img');
                previewImg.id = 'photoPreviewImg';
                previewImg.className = 'employee-photo-img';
                previewImg.alt = 'Photo Preview';
                previewContainer.appendChild(previewImg);
            }
            
            // Hide current photo if exists
            if (currentPhoto) {
                currentPhoto.style.display = 'none';
            }
            
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        // Reset to original photo
        if (currentPhoto) {
            currentPhoto.style.display = 'block';
        }
        if (photoPlaceholder) {
            photoPlaceholder.style.display = 'flex';
        }
        const previewImg = document.getElementById('photoPreviewImg');
        if (previewImg) {
            previewImg.style.display = 'none';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle collapsible sections
    const collapsibleHeaders = document.querySelectorAll('.collapsible-section');
    collapsibleHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetContent = document.getElementById(targetId);
            
            if (targetContent) {
                const isCollapsed = targetContent.classList.contains('show');
                
                if (isCollapsed) {
                    targetContent.classList.remove('show');
                    this.classList.add('collapsed');
                } else {
                    targetContent.classList.add('show');
                    this.classList.remove('collapsed');
                }
            }
        });
    });
});
</script>
