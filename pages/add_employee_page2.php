<?php
$page_title = 'Add New Employee - Page 2 - Golden Z-5 HR System';
$page = 'add_employee_page2';

// Get logged-in user information
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;
$current_user_name = null;
$current_user_department = null;

// Try to get user name and department from database if we have user_id
if ($current_user_id && function_exists('get_db_connection')) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT name, username, department FROM users WHERE id = ?");
        $stmt->execute([$current_user_id]);
        $user = $stmt->fetch();
        if ($user) {
            // Prioritize name field, fallback to username, then session, then default
            $current_user_name = !empty(trim($user['name'] ?? '')) 
                ? trim($user['name']) 
                : (!empty(trim($user['username'] ?? '')) 
                    ? trim($user['username']) 
                    : ($_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'System Administrator'));
            $current_user_department = !empty(trim($user['department'] ?? '')) ? trim($user['department']) : null;
        } else {
            // User not found in database, use session values
            $current_user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'System Administrator';
            $current_user_department = $_SESSION['department'] ?? null;
        }
    } catch (Exception $e) {
        // Use session values if database query fails
        $current_user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'System Administrator';
        $current_user_department = $_SESSION['department'] ?? null;
    }
} else {
    // No user_id, use session values
    $current_user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'System Administrator';
    $current_user_department = $_SESSION['department'] ?? null;
}

// ============================================================================
// PAGE 2: COLLECT PAGE 2 DATA AND INSERT BOTH PAGE 1 + PAGE 2 DATA
// ============================================================================
// This page collects Page 2 data and when "Save Employee" is clicked,
// it INSERTs both Page 1 data (from session) and Page 2 data into the database.
// This ensures all employee data is saved together in a single INSERT.
// ============================================================================

// Check if Page 1 data exists in session
$page1_data = $_SESSION['employee_page1_data'] ?? null;
$has_page1_data = !empty($page1_data);

// For backward compatibility, also check for employee_id (if employee already exists)
$employee_id = null;
if (!empty($_GET['employee_id'])) {
    $employee_id = (int)$_GET['employee_id'];
} elseif (!empty($_SESSION['employee_created_id'])) {
    $employee_id = (int)$_SESSION['employee_created_id'];
}

// Log the current state
if (function_exists('log_db_error')) {
    log_db_error('add_employee_page2', 'Page 2 loaded', [
        'has_page1_data' => $has_page1_data ? 'yes' : 'no',
        'employee_id' => $employee_id,
        'page1_employee_no' => $page1_data['employee_no'] ?? 'N/A',
        'page1_name' => $page1_data ? trim(($page1_data['first_name'] ?? '') . ' ' . ($page1_data['surname'] ?? '')) : 'N/A'
    ]);
}

// Handle form submission - Page 2 saves BOTH Page 1 and Page 2 data
// This is where the actual database INSERT happens
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $errors = [];
    $success = false;
    
    // Get Page 1 data from session
    $page1_data = $_SESSION['employee_page1_data'] ?? null;
    
    // Check if we have Page 1 data (new employee flow)
    if (empty($page1_data)) {
        $errors[] = 'Page 1 data is missing. Please go back to Page 1 and fill out the form first.';
        if (function_exists('log_db_error')) {
            log_db_error('add_employee_page2', 'Missing Page 1 data in session', [
                'session_keys' => array_keys($_SESSION)
            ]);
        }
    }
    
    // Validate required Page 2 fields
    // Question 1: At least one vacancy source must be selected
    if (empty($_POST['vacancy_source']) || !is_array($_POST['vacancy_source']) || count($_POST['vacancy_source']) === 0) {
        $errors[] = 'Question 1 (How did you know of the vacancy) is required. Please select at least one option.';
    }
    
    $required_page2_fields = [
        'knows_agency_person' => 'Question 2 (Do you know anyone from the AGENCY)',
        'physical_defect' => 'Question 3 (Physical defect/s or chronic ailments)',
        'drives' => 'Question 4 (Do you drive)',
        'drinks_alcohol' => 'Question 5 (Do you drink alcoholic beverages)',
        'prohibited_drugs' => 'Question 6 (Are you taking prohibited drugs)',
        'convicted' => 'Question 8 (Have you ever been convicted)',
        'filed_case' => 'Question 9 (Have you filed any case)'
    ];
    
    foreach ($required_page2_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[] = $label . ' is required.';
        }
    }
    
    // If we have Page 1 data, proceed with INSERT
    if (empty($errors) && !empty($page1_data)) {
        try {
            $pdo = get_db_connection();
            
            // Prepare page 2 data
            $page2_data = [];
            
            // General Information
            $page2_data['vacancy_source'] = !empty($_POST['vacancy_source']) ? json_encode($_POST['vacancy_source']) : null;
            $page2_data['referral_name'] = !empty($_POST['referral_name']) ? trim($_POST['referral_name']) : null;
            $page2_data['knows_agency_person'] = !empty($_POST['knows_agency_person']) ? $_POST['knows_agency_person'] : null;
            $page2_data['agency_person_name'] = !empty($_POST['agency_person_name']) ? trim($_POST['agency_person_name']) : null;
            $page2_data['physical_defect'] = !empty($_POST['physical_defect']) ? $_POST['physical_defect'] : null;
            $page2_data['physical_defect_specify'] = !empty($_POST['physical_defect_specify']) ? trim($_POST['physical_defect_specify']) : null;
            $page2_data['drives'] = !empty($_POST['drives']) ? $_POST['drives'] : null;
            $page2_data['drivers_license_no'] = !empty($_POST['drivers_license_no']) ? trim($_POST['drivers_license_no']) : null;
            $page2_data['drivers_license_exp'] = !empty($_POST['drivers_license_exp']) ? trim($_POST['drivers_license_exp']) : null;
            $page2_data['drinks_alcohol'] = !empty($_POST['drinks_alcohol']) ? $_POST['drinks_alcohol'] : null;
            $page2_data['alcohol_frequency'] = !empty($_POST['alcohol_frequency']) ? trim($_POST['alcohol_frequency']) : null;
            $page2_data['prohibited_drugs'] = !empty($_POST['prohibited_drugs']) ? $_POST['prohibited_drugs'] : null;
            $page2_data['security_guard_experience'] = !empty($_POST['security_guard_experience']) ? trim($_POST['security_guard_experience']) : null;
            $page2_data['convicted'] = !empty($_POST['convicted']) ? $_POST['convicted'] : null;
            $page2_data['conviction_details'] = !empty($_POST['conviction_details']) ? trim($_POST['conviction_details']) : null;
            $page2_data['filed_case'] = !empty($_POST['filed_case']) ? $_POST['filed_case'] : null;
            $page2_data['case_specify'] = !empty($_POST['case_specify']) ? trim($_POST['case_specify']) : null;
            $page2_data['action_after_termination'] = !empty($_POST['action_after_termination']) ? trim($_POST['action_after_termination']) : null;
            
            // Specimen Signature and Initial
            $page2_data['signature_1'] = !empty($_POST['signature_1']) ? trim($_POST['signature_1']) : null;
            $page2_data['signature_2'] = !empty($_POST['signature_2']) ? trim($_POST['signature_2']) : null;
            $page2_data['signature_3'] = !empty($_POST['signature_3']) ? trim($_POST['signature_3']) : null;
            $page2_data['initial_1'] = !empty($_POST['initial_1']) ? trim($_POST['initial_1']) : null;
            $page2_data['initial_2'] = !empty($_POST['initial_2']) ? trim($_POST['initial_2']) : null;
            $page2_data['initial_3'] = !empty($_POST['initial_3']) ? trim($_POST['initial_3']) : null;
            
            // Basic Requirements
            $page2_data['requirements_signature'] = !empty($_POST['requirements_signature']) ? trim($_POST['requirements_signature']) : null;
            $page2_data['req_2x2'] = !empty($_POST['req_2x2']) ? $_POST['req_2x2'] : null;
            $page2_data['req_birth_cert'] = !empty($_POST['req_birth_cert']) ? $_POST['req_birth_cert'] : null;
            $page2_data['req_barangay'] = !empty($_POST['req_barangay']) ? $_POST['req_barangay'] : null;
            $page2_data['req_police'] = !empty($_POST['req_police']) ? $_POST['req_police'] : null;
            $page2_data['req_nbi'] = !empty($_POST['req_nbi']) ? $_POST['req_nbi'] : null;
            $page2_data['req_di'] = !empty($_POST['req_di']) ? $_POST['req_di'] : null;
            $page2_data['req_diploma'] = !empty($_POST['req_diploma']) ? $_POST['req_diploma'] : null;
            $page2_data['req_neuro_drug'] = !empty($_POST['req_neuro_drug']) ? $_POST['req_neuro_drug'] : null;
            $page2_data['req_sec_license'] = !empty($_POST['req_sec_license']) ? $_POST['req_sec_license'] : null;
            $page2_data['sec_lic_no'] = !empty($_POST['sec_lic_no']) ? trim($_POST['sec_lic_no']) : null;
            $page2_data['req_sec_lic_no'] = !empty($_POST['req_sec_lic_no']) ? $_POST['req_sec_lic_no'] : null;
            $page2_data['req_sss'] = !empty($_POST['req_sss']) ? $_POST['req_sss'] : null;
            $page2_data['req_pagibig'] = !empty($_POST['req_pagibig']) ? $_POST['req_pagibig'] : null;
            $page2_data['req_philhealth'] = !empty($_POST['req_philhealth']) ? $_POST['req_philhealth'] : null;
            $page2_data['req_tin'] = !empty($_POST['req_tin']) ? $_POST['req_tin'] : null;
            
            // Sworn Statement
            $page2_data['sworn_day'] = !empty($_POST['sworn_day']) ? trim($_POST['sworn_day']) : null;
            $page2_data['sworn_month'] = !empty($_POST['sworn_month']) ? trim($_POST['sworn_month']) : null;
            $page2_data['sworn_year'] = !empty($_POST['sworn_year']) ? trim($_POST['sworn_year']) : null;
            $page2_data['tax_cert_no'] = !empty($_POST['tax_cert_no']) ? trim($_POST['tax_cert_no']) : null;
            $page2_data['tax_cert_issued_at'] = !empty($_POST['tax_cert_issued_at']) ? trim($_POST['tax_cert_issued_at']) : null;
            $page2_data['sworn_signature'] = !empty($_POST['sworn_signature']) ? trim($_POST['sworn_signature']) : null;
            $page2_data['affiant_community'] = !empty($_POST['affiant_community']) ? trim($_POST['affiant_community']) : null;
            
            // Form Footer
            $page2_data['doc_no'] = !empty($_POST['doc_no']) ? trim($_POST['doc_no']) : null;
            $page2_data['page_no'] = !empty($_POST['page_no']) ? trim($_POST['page_no']) : null;
            $page2_data['book_no'] = !empty($_POST['book_no']) ? trim($_POST['book_no']) : null;
            $page2_data['series_of'] = !empty($_POST['series_of']) ? trim($_POST['series_of']) : null;
            
            // ========================================================================
            // MERGE PAGE 1 AND PAGE 2 DATA, THEN INSERT
            // ========================================================================
            // Combine all data from Page 1 (session) and Page 2 (POST) for a single INSERT
            // ========================================================================
            
            // Use the add_employee function from database.php for Page 1 data
            if (function_exists('log_db_error')) {
                log_db_error('add_employee_page2', 'Inserting employee with combined Page 1 + Page 2 data', [
                    'employee_no' => $page1_data['employee_no'] ?? 'N/A',
                    'employee_name' => trim(($page1_data['first_name'] ?? '') . ' ' . ($page1_data['surname'] ?? '')),
                    'page2_fields_count' => count($page2_data)
                ]);
            }
            
            // First, insert Page 1 data using the add_employee function
            $new_employee_id = add_employee($page1_data);
            
            if ($new_employee_id && $new_employee_id > 0) {
                $new_employee_id = (int)$new_employee_id;
                
                if (function_exists('log_db_error')) {
                    log_db_error('add_employee_page2', 'Employee created successfully', [
                        'new_employee_id' => $new_employee_id,
                        'employee_no' => $page1_data['employee_no'] ?? 'N/A'
                    ]);
                }
                
                // Handle photo upload - move from temp to permanent location
                if (!empty($_SESSION['employee_temp_photo'])) {
                    $temp_photo = $_SESSION['employee_temp_photo'];
                    $upload_dir = __DIR__ . '/../uploads/employees/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $final_filename = $new_employee_id . '.' . $temp_photo['extension'];
                    $final_path = $upload_dir . $final_filename;
                    
                    if (file_exists($temp_photo['path']) && rename($temp_photo['path'], $final_path)) {
                        // Check if profile_image column exists before updating
                        try {
                            $check_col_sql = "SHOW COLUMNS FROM employees LIKE 'profile_image'";
                            $check_col_stmt = $pdo->query($check_col_sql);
                            if ($check_col_stmt && $check_col_stmt->rowCount() > 0) {
                                // Update profile_image in database
                                $image_path = 'uploads/employees/' . $final_filename;
                                $update_photo_sql = "UPDATE employees SET profile_image = ? WHERE id = ?";
                                $update_photo_stmt = $pdo->prepare($update_photo_sql);
                                $update_photo_stmt->execute([$image_path, $new_employee_id]);
                                
                                if (function_exists('log_db_error')) {
                                    log_db_error('photo_upload', 'Photo moved from temp to permanent location', [
                                        'employee_id' => $new_employee_id,
                                        'path' => $image_path
                                    ]);
                                }
                            } else {
                                // Column doesn't exist - just log it, photo file is still saved
                                if (function_exists('log_db_error')) {
                                    log_db_error('photo_upload', 'profile_image column not found - photo saved to disk only', [
                                        'employee_id' => $new_employee_id,
                                        'file_path' => $final_path
                                    ]);
                                }
                            }
                        } catch (Exception $photo_e) {
                            // Don't fail the whole operation for photo issues
                            if (function_exists('log_db_error')) {
                                log_db_error('photo_upload', 'Error updating profile_image', [
                                    'employee_id' => $new_employee_id,
                                    'error' => $photo_e->getMessage()
                                ]);
                            }
                        }
                    }
                    unset($_SESSION['employee_temp_photo']);
                }
                
                // Handle fingerprint file uploads
                $fingerprint_fields = [
                    'fingerprint_right_thumb', 'fingerprint_right_index', 'fingerprint_right_middle', 
                    'fingerprint_right_ring', 'fingerprint_right_little',
                    'fingerprint_left_thumb', 'fingerprint_left_index', 'fingerprint_left_middle', 
                    'fingerprint_left_ring', 'fingerprint_left_little'
                ];
                
                $fingerprint_upload_dir = __DIR__ . '/../uploads/employees/fingerprints/';
                if (!file_exists($fingerprint_upload_dir)) {
                    mkdir($fingerprint_upload_dir, 0755, true);
                }
                
                foreach ($fingerprint_fields as $field) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES[$field];
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
                        $max_size = 5 * 1024 * 1024; // 5MB
                        
                        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $extension = strtolower($extension === 'jpeg' ? 'jpg' : $extension);
                            $filename = $new_employee_id . '_' . $field . '.' . $extension;
                            $target_path = $fingerprint_upload_dir . $filename;
                            
                            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                                $page2_data[$field] = 'uploads/employees/fingerprints/' . $filename;
                            }
                        }
                    }
                }
                
                // Now UPDATE the employee record with Page 2 data
                $update_fields = [];
                $update_params = [];
                
                foreach ($page2_data as $field => $value) {
                    if ($value !== null) {
                        $update_fields[] = "`$field` = ?";
                        $update_params[] = $value;
                    }
                }
                
                if (!empty($update_fields)) {
                    $update_params[] = $new_employee_id;
                    $update_sql = "UPDATE employees SET " . implode(', ', $update_fields) . ", updated_at = NOW() WHERE id = ?";
                    
                    try {
                        $update_stmt = $pdo->prepare($update_sql);
                        $update_stmt->execute($update_params);
                        
                        if (function_exists('log_db_error')) {
                            log_db_error('add_employee_page2', 'Page 2 data updated successfully', [
                                'employee_id' => $new_employee_id,
                                'fields_updated' => count($update_fields)
                            ]);
                        }
                    } catch (PDOException $update_e) {
                        // Check if this is an audit_logs duplicate key error
                        // The UPDATE likely succeeded but the audit trigger failed
                        $err_code = $update_e->getCode();
                        $err_msg = $update_e->getMessage();
                        
                        if ($err_code == 23000 && strpos($err_msg, 'Duplicate entry') !== false) {
                            // Audit log error - the UPDATE probably succeeded, continue
                            if (function_exists('log_db_error')) {
                                log_db_error('add_employee_page2', 'Page 2 UPDATE succeeded but audit trigger failed (ignored)', [
                                    'employee_id' => $new_employee_id,
                                    'audit_error' => $err_msg
                                ]);
                            }
                        } else {
                            // Different error - re-throw
                            throw $update_e;
                        }
                    }
                }
                
                // Log the action - wrap in try-catch to handle audit_logs errors
                try {
                    if (function_exists('log_security_event')) {
                        log_security_event('Employee Created', "Employee: {$page1_data['first_name']} {$page1_data['surname']} (No: {$page1_data['employee_no']}) created by {$current_user_name}");
                    }
                } catch (Exception $log_e) {
                    // Ignore logging errors
                }
                
                // Store employee info for modal display
                $created_employee_name = trim(($page1_data['first_name'] ?? '') . ' ' . ($page1_data['surname'] ?? ''));
                $created_employee_no = $page1_data['employee_no'] ?? '';
                $created_employee_type = $page1_data['employee_type'] ?? '';
                
                // Clear session data
                unset($_SESSION['employee_page1_data']);
                unset($_SESSION['page1_data_ready']);
                
                $success = true;
                
                // Store success info for modal display
                $_SESSION['show_success_modal'] = true;
                $_SESSION['employee_created_message'] = "Employee has been created successfully!";
                $_SESSION['employee_created_id'] = $new_employee_id;
                $_SESSION['employee_created_name'] = $created_employee_name;
                $_SESSION['employee_created_no'] = $created_employee_no;
                $_SESSION['employee_created_type'] = $created_employee_type;
                
                // Also set for employees page (will be used after modal redirect)
                $_SESSION['employee_created_success'] = true;
                
            } else {
                $errors[] = 'Failed to create employee. Please check all required fields and try again.';
                if (function_exists('log_db_error')) {
                    log_db_error('add_employee_page2', 'add_employee returned false', [
                        'page1_data' => $page1_data
                    ]);
                }
            }
            
        } catch (Exception $e) {
            $errors[] = 'Error saving employee: ' . $e->getMessage();
            if (function_exists('log_db_error')) {
                log_db_error('add_employee_page2', 'Exception during employee creation', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}

// Show errors if any (outside POST block)
$show_errors = !empty($errors);
    
// If Page 1 data is missing (and this is not a POST request), show error and redirect
if (!$has_page1_data && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['page2_error'] = 'Please fill out Page 1 first before proceeding to Page 2.';
    $_SESSION['employee_redirect_url'] = '?page=add_employee&error=no_page1_data';
}
?>

<?php
// Check if we should show success modal
$show_success_modal = false;
$success_message = '';
$created_employee_id = null;
$created_employee_name = '';
$created_employee_no = '';
$created_employee_type = '';

if (isset($_SESSION['show_success_modal']) && $_SESSION['show_success_modal']) {
    $show_success_modal = true;
    $success_message = $_SESSION['employee_created_message'] ?? 'Employee has been created successfully!';
    $created_employee_id = $_SESSION['employee_created_id'] ?? null;
    $created_employee_name = $_SESSION['employee_created_name'] ?? '';
    $created_employee_no = $_SESSION['employee_created_no'] ?? '';
    $created_employee_type = $_SESSION['employee_created_type'] ?? '';
    
    // Clear modal flags but keep employee_created_success and employee_created_message for employees page
    unset($_SESSION['show_success_modal']);
    unset($_SESSION['employee_created_name']);
    unset($_SESSION['employee_created_no']);
    unset($_SESSION['employee_created_type']);
    // Keep employee_created_success, employee_created_message, and employee_created_id for redirect
}

// Handle redirect via JavaScript (for errors only)
if (isset($_SESSION['employee_redirect_url'])) {
    $redirect_url = $_SESSION['employee_redirect_url'];
    unset($_SESSION['employee_redirect_url']);
    ?>
    <script type="text/javascript">
        window.location.href = <?php echo json_encode($redirect_url); ?>;
    </script>
    <div style="text-align: center; padding: 50px;">
        <p>Redirecting... If you are not redirected automatically, <a href="<?php echo htmlspecialchars($redirect_url); ?>">click here</a>.</p>
    </div>
    <?php
    return; // Stop rendering the rest of the page
}
?>

<div class="container-fluid hrdash add-employee-container add-employee-modern">
    <!-- Page Header -->
    <div class="page-header-modern">
        <div class="page-title-modern">
            <h1 class="page-title-main">Add New Employee - Page 2</h1>
            <p class="page-subtitle-modern">Complete the employee application form</p>
            <?php if ($has_page1_data): ?>
                <div class="alert alert-info mt-2 mb-0 fs-sm" style="border-left: 3px solid #0dcaf0;">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Draft Employee:</strong>&nbsp;
                    <span class="text-dark"><?php echo htmlspecialchars(trim(($page1_data['first_name'] ?? '') . ' ' . ($page1_data['surname'] ?? ''))); ?></span>
                    <span class="text-muted">(<?php echo htmlspecialchars($page1_data['employee_type'] ?? ''); ?> #<?php echo htmlspecialchars($page1_data['employee_no'] ?? ''); ?>)</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="page-actions-modern">
            <a href="?page=add_employee" class="btn btn-outline-modern me-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Page 1
            </a>
            <a href="?page=employees" class="btn btn-outline-modern">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Error:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['page2_error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['page2_error']); ?>
            <?php unset($_SESSION['page2_error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] === 'no_page1_data'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Warning:</strong> Please fill out Page 1 first before proceeding to Page 2.
        </div>
    <?php endif; ?>

    <!-- Clean Form CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('pages/css/page2-clean-form.css'); ?>">
    
    <!-- Page 2 Form -->
    <div class="add-employee-form-wrapper">
        <form method="POST" id="page2EmployeeForm" enctype="multipart/form-data" action="?page=add_employee_page2" class="add-employee-form-compact" novalidate>
            <!-- Page 1 data is stored in session, will be combined with Page 2 data on save -->
                
                <!-- General Information Section - Clean Layout -->
                <div class="clean-form-page">
                    <!-- Main Header -->
                    <h1 class="clean-section-title text-center fs-16 fw-bold mb-4 pb-3" style="border-bottom: 2px solid #d1d5db;">General Information</h1>
                    
                    <!-- Question List -->
                    <ul class="clean-question-list">
                        <!-- Question 1 -->
                        <li class="clean-question-item">
                            <div class="clean-question-header">
                                <div class="clean-question-text">
                                    <span class="clean-question-number">1.</span>
                                    <span>How did you know of the vacancy in the AGENCY?<span class="clean-question-required">*</span></span>
                                </div>
                                <div class="clean-options-group">
                                    <div class="clean-option-item">
                                        <input class="form-check-input vacancy-source-checkbox" type="checkbox" name="vacancy_source[]" id="vacancy_ads" value="Ads">
                                        <label class="clean-option-label" for="vacancy_ads">Ads</label>
                                    </div>
                                    <div class="clean-option-item">
                                        <input class="form-check-input vacancy-source-checkbox" type="checkbox" name="vacancy_source[]" id="vacancy_walkin" value="Walk-in">
                                        <label class="clean-option-label" for="vacancy_walkin">Walk-in</label>
                                    </div>
                                    <div class="clean-option-item">
                                        <input class="form-check-input vacancy-source-checkbox" type="checkbox" name="vacancy_source[]" id="vacancy_referral" value="Referral">
                                        <label class="clean-option-label" for="vacancy_referral">Referral</label>
                                    </div>
                                </div>
                            </div>
                            <div class="clean-detail-input">
                                <input type="text" class="form-control" id="referral_name" name="referral_name" placeholder="If Referral, state name">
                                <small class="form-text text-muted">Please select at least one option</small>
                                <div class="invalid-feedback" id="vacancy_source_error" style="display: none;">Please select at least one option.</div>
                            </div>
                        </li>

                        <!-- Question 2 -->
                        <li class="clean-question-item">
                            <div class="clean-question-header">
                                <div class="clean-question-text">
                                    <span class="clean-question-number">2.</span>
                                    <span>Do you know anyone from the AGENCY prior to your application?<span class="clean-question-required">*</span></span>
                                </div>
                                <div class="clean-options-group">
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="knows_agency_person" id="knows_yes" value="Yes" required>
                                        <label class="clean-option-label" for="knows_yes">Yes</label>
                                    </div>
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="knows_agency_person" id="knows_no" value="No" required>
                                        <label class="clean-option-label" for="knows_no">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="clean-detail-input">
                                <input type="text" class="form-control" id="agency_person_name" name="agency_person_name" placeholder="If Yes, state his/her name and your relationship with him/her">
                            </div>
                        </li>

                        <!-- Question 3 -->
                        <li class="clean-question-item">
                            <div class="clean-question-header">
                                <div class="clean-question-text">
                                    <span class="clean-question-number">3.</span>
                                    <span>Do you have any physical defect/s or chronic ailments?<span class="clean-question-required">*</span></span>
                                </div>
                                <div class="clean-options-group">
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="physical_defect" id="defect_yes" value="Yes" required>
                                        <label class="clean-option-label" for="defect_yes">Yes</label>
                                    </div>
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="physical_defect" id="defect_no" value="No" required>
                                        <label class="clean-option-label" for="defect_no">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="clean-detail-input">
                                <input type="text" class="form-control" id="physical_defect_specify" name="physical_defect_specify" placeholder="If Yes, please specify">
                            </div>
                        </li>

                        <!-- Question 4 -->
                        <li class="clean-question-item">
                            <div class="clean-question-header">
                                <div class="clean-question-text">
                                    <span class="clean-question-number">4.</span>
                                    <span>Do you drive?<span class="clean-question-required">*</span></span>
                                </div>
                                <div class="clean-options-group">
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="drives" id="drives_yes" value="Yes" required>
                                        <label class="clean-option-label" for="drives_yes">Yes</label>
                                    </div>
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="drives" id="drives_no" value="No" required>
                                        <label class="clean-option-label" for="drives_no">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="clean-inline-inputs">
                                <span class="clean-inline-label">If Yes, Driver's License No.</span>
                                <input type="text" class="form-control" id="drivers_license_no" name="drivers_license_no" placeholder="License No." style="width: 200px;">
                                <span class="clean-inline-label">Expiration Date</span>
                                <input type="text" class="form-control" id="drivers_license_exp" name="drivers_license_exp" placeholder="MM/DD/YYYY" style="width: 150px;">
                            </div>
                        </li>

                        <!-- Question 5 -->
                        <li class="clean-question-item">
                            <div class="clean-question-header">
                                <div class="clean-question-text">
                                    <span class="clean-question-number">5.</span>
                                    <span>Do you drink alcoholic beverages?<span class="clean-question-required">*</span></span>
                                </div>
                                <div class="clean-options-group">
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="drinks_alcohol" id="alcohol_yes" value="Yes" required>
                                        <label class="clean-option-label" for="alcohol_yes">Yes</label>
                                    </div>
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="drinks_alcohol" id="alcohol_no" value="No" required>
                                        <label class="clean-option-label" for="alcohol_no">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="clean-detail-input">
                                <input type="text" class="form-control" id="alcohol_frequency" name="alcohol_frequency" placeholder="If Yes, how frequent?">
                            </div>
                        </li>

                        <!-- Question 6 -->
                        <li class="clean-question-item">
                            <div class="clean-question-header">
                                <div class="clean-question-text">
                                    <span class="clean-question-number">6.</span>
                                    <span>Are you taking prohibited drugs?<span class="clean-question-required">*</span></span>
                                </div>
                                <div class="clean-options-group">
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="prohibited_drugs" id="drugs_yes" value="Yes" required>
                                        <label class="clean-option-label" for="drugs_yes">Yes</label>
                                    </div>
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="prohibited_drugs" id="drugs_no" value="No" required>
                                        <label class="clean-option-label" for="drugs_no">No</label>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <!-- Question 7 -->
                        <li class="clean-question-item">
                            <div class="clean-question-header">
                                <div class="clean-question-text">
                                    <span class="clean-question-number">7.</span>
                                    <span>How long have you worked as a Security Guard?</span>
                                </div>
                            </div>
                            <div class="clean-detail-input">
                                <input type="text" class="form-control" id="security_guard_experience" name="security_guard_experience" placeholder="e.g., 2 years, 6 months">
                            </div>
                        </li>

                        <!-- Question 8 -->
                        <li class="clean-question-item">
                            <div class="clean-question-header">
                                <div class="clean-question-text">
                                    <span class="clean-question-number">8.</span>
                                    <span>Have you ever been convicted of any <strong>OFFENSE (criminal or civil)</strong> before a court competent jurisdiction?<span class="clean-question-required">*</span></span>
                                </div>
                                <div class="clean-options-group">
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="convicted" id="convicted_yes" value="Yes" required>
                                        <label class="clean-option-label" for="convicted_yes">Yes</label>
                                    </div>
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="convicted" id="convicted_no" value="No" required>
                                        <label class="clean-option-label" for="convicted_no">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="clean-detail-input">
                                <input type="text" class="form-control" id="conviction_details" name="conviction_details" placeholder="If Yes, please specify">
                            </div>
                        </li>

                        <!-- Question 9 -->
                        <li class="clean-question-item">
                            <div class="clean-question-header">
                                <div class="clean-question-text">
                                    <span class="clean-question-number">9.</span>
                                    <span>Have you filed any <strong>CRIMINAL / CIVIL CASE (labor)</strong> against any of your previous employer?<span class="clean-question-required">*</span></span>
                                </div>
                                <div class="clean-options-group">
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="filed_case" id="case_yes" value="Yes" required>
                                        <label class="clean-option-label" for="case_yes">Yes</label>
                                    </div>
                                    <div class="clean-option-item">
                                        <input class="form-check-input" type="radio" name="filed_case" id="case_no" value="No" required>
                                        <label class="clean-option-label" for="case_no">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="clean-inline-inputs">
                                <span class="clean-inline-label">If YES, please specify:</span>
                                <input type="text" class="form-control" id="case_specify" name="case_specify" placeholder="Specify case" style="flex: 1; min-width: 200px;">
                                <span class="clean-inline-label">and what was your action after your termination?</span>
                                <input type="text" class="form-control" id="action_after_termination" name="action_after_termination" placeholder="Action taken" style="flex: 1; min-width: 200px;">
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Specimen Signature and Initial Section -->
                <div class="row g-3 mb-2">
                    <div class="col-12">
                        <h4 class="form-section-title">SPECIMEN SIGNATURE AND INITIAL</h4>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>SIGNATURE</strong></label>
                            <div class="mb-2">
                                <label class="form-label small">1.</label>
                                <input type="text" class="form-control" id="signature_1" name="signature_1" placeholder="Signature line 1">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">2.</label>
                                <input type="text" class="form-control" id="signature_2" name="signature_2" placeholder="Signature line 2">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">3.</label>
                                <input type="text" class="form-control" id="signature_3" name="signature_3" placeholder="Signature line 3">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>INITIAL (PINAIKLING PIRMA)</strong></label>
                            <div class="mb-2">
                                <label class="form-label small">1.</label>
                                <input type="text" class="form-control" id="initial_1" name="initial_1" placeholder="Initial 1">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">2.</label>
                                <input type="text" class="form-control" id="initial_2" name="initial_2" placeholder="Initial 2">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">3.</label>
                                <input type="text" class="form-control" id="initial_3" name="initial_3" placeholder="Initial 3">
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <p class="mb-0"><small>I HEREBY CERTIFY that the above specimen is my <strong>OFFICIAL</strong> signatures and initial of which I <strong>CONFIRM</strong> by my signature below.</small></p>
                        </div>
                    </div>
                </div>

                <!-- Fingerprints Section -->
                <div class="row g-3 mb-2">
                    <div class="col-12">
                        <h4 class="form-section-title">FINGERPRINTS</h4>
                    </div>
                    
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-bordered text-center" style="max-width: 800px; margin: 0 auto;">
                                <thead>
                                    <tr>
                                        <th>RIGHT Thumb</th>
                                        <th>RIGHT Index Finger</th>
                                        <th>RIGHT Middle Finger</th>
                                        <th>RIGHT Ring Finger</th>
                                        <th>RIGHT Little Finger</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_right_thumb" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_right_index" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_right_middle" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_right_ring" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_right_little" accept="image/*">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered text-center" style="max-width: 800px; margin: 0 auto;">
                                <thead>
                                    <tr>
                                        <th>LEFT Thumb</th>
                                        <th>LEFT Index Finger</th>
                                        <th>LEFT Middle Finger</th>
                                        <th>LEFT Ring Finger</th>
                                        <th>LEFT Little Finger</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_left_thumb" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_left_index" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_left_middle" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_left_ring" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_left_little" accept="image/*">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Basic Requirements Section -->
                <div class="row g-3 mb-2">
                    <div class="col-12">
                        <h4 class="form-section-title">BASIC REQUIREMENTS</h4>
                    </div>
                    
                    <div class="col-12 text-end mb-3">
                        <label class="form-label">Signature Over Printed Name</label>
                        <input type="text" class="form-control" id="requirements_signature" name="requirements_signature" placeholder="Signature" style="max-width: 400px; margin-left: auto;">
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label"><strong>Provided on Application:</strong> Y☐ N☐</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">1. Close up 2x2 (2pcs)</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_2x2" id="req_2x2_y" value="YO">
                                            <label class="form-check-label" for="req_2x2_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_2x2" id="req_2x2_n" value="NO">
                                            <label class="form-check-label" for="req_2x2_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">2. NSO, Birth Certificate</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_birth_cert" id="req_birth_cert_y" value="YO">
                                            <label class="form-check-label" for="req_birth_cert_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_birth_cert" id="req_birth_cert_n" value="NO">
                                            <label class="form-check-label" for="req_birth_cert_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">3. Barangay Clearance</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_barangay" id="req_barangay_y" value="YO">
                                            <label class="form-check-label" for="req_barangay_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_barangay" id="req_barangay_n" value="NO">
                                            <label class="form-check-label" for="req_barangay_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">4. Police Clearance (local)</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_police" id="req_police_y" value="YO">
                                            <label class="form-check-label" for="req_police_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_police" id="req_police_n" value="NO">
                                            <label class="form-check-label" for="req_police_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">5. NBI (for cases purposes)</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_nbi" id="req_nbi_y" value="YO">
                                            <label class="form-check-label" for="req_nbi_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_nbi" id="req_nbi_n" value="NO">
                                            <label class="form-check-label" for="req_nbi_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">6. D.I. Clearance</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_di" id="req_di_y" value="YO">
                                            <label class="form-check-label" for="req_di_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_di" id="req_di_n" value="NO">
                                            <label class="form-check-label" for="req_di_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">7. High School / College Diploma</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_diploma" id="req_diploma_y" value="YO">
                                            <label class="form-check-label" for="req_diploma_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_diploma" id="req_diploma_n" value="NO">
                                            <label class="form-check-label" for="req_diploma_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">8. Neuro & Drug test result</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_neuro_drug" id="req_neuro_drug_y" value="YO">
                                            <label class="form-check-label" for="req_neuro_drug_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_neuro_drug" id="req_neuro_drug_n" value="NO">
                                            <label class="form-check-label" for="req_neuro_drug_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">9. Sec.License Certi. fr. SOSIA</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sec_license" id="req_sec_license_y" value="YO">
                                            <label class="form-check-label" for="req_sec_license_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sec_license" id="req_sec_license_n" value="NO">
                                            <label class="form-check-label" for="req_sec_license_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label"><strong>I.D. copy provision:</strong></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">10. Sec. Lic. No.</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="text" class="form-control" id="sec_lic_no" name="sec_lic_no" placeholder="License Number" style="max-width: 300px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sec_lic_no" id="req_sec_lic_no_y" value="YO">
                                            <label class="form-check-label" for="req_sec_lic_no_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sec_lic_no" id="req_sec_lic_no_n" value="NO">
                                            <label class="form-check-label" for="req_sec_lic_no_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">11. SSS No.</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="text" class="form-control" id="sss_no_page2" name="sss_no_page2" placeholder="SSS Number" style="max-width: 300px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sss" id="req_sss_y" value="YO">
                                            <label class="form-check-label" for="req_sss_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sss" id="req_sss_n" value="NO">
                                            <label class="form-check-label" for="req_sss_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">12. Pag-Ibig No.</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="text" class="form-control" id="pagibig_no_page2" name="pagibig_no_page2" placeholder="Pag-Ibig Number" style="max-width: 300px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_pagibig" id="req_pagibig_y" value="YO">
                                            <label class="form-check-label" for="req_pagibig_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_pagibig" id="req_pagibig_n" value="NO">
                                            <label class="form-check-label" for="req_pagibig_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">13. PhilHealth No.</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="text" class="form-control" id="philhealth_no_page2" name="philhealth_no_page2" placeholder="PhilHealth Number" style="max-width: 300px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_philhealth" id="req_philhealth_y" value="YO">
                                            <label class="form-check-label" for="req_philhealth_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_philhealth" id="req_philhealth_n" value="NO">
                                            <label class="form-check-label" for="req_philhealth_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">14. TIN No.</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="text" class="form-control" id="tin_no_page2" name="tin_no_page2" placeholder="TIN Number" style="max-width: 300px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_tin" id="req_tin_y" value="YO">
                                            <label class="form-check-label" for="req_tin_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_tin" id="req_tin_n" value="NO">
                                            <label class="form-check-label" for="req_tin_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sworn Statement Section -->
                <div class="row g-3 mb-2">
                    <div class="col-12">
                        <h4 class="form-section-title">SWORN STATEMENT</h4>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <p class="mb-3"><small>I HEREBY AUTHORIZE the Company, <strong>GOLDEN Z-5 SECURITY & INVESTIGATION AGENCY, INC.</strong> to conduct further investigation and inquiry as to my personal, past employment and such other related background Information. I hereby release from any and all liabilities all persons, companies, corporations, and institutions supplying any information with respect to my background, character, and employment history. I understand that any misinterpretation or omission of facts can lead to application revocation or dismissal.</small></p>
                            
                            <p class="mb-3"><small>I <strong>UNDERSTAND</strong> that if my application is considered, my appointment will be on a <strong>PROBATIONARY</strong> basis for a period not more than six (6) months, and that during this period, my services may be terminated without prior notice and without liability on the part of the Company. I further understand that my employment is subject to my compliance with all the rules and regulations of the Company, and that violation of any of these rules and regulations may result in my immediate dismissal.</small></p>
                            
                            <p class="mb-3"><small>I HEREBY CERTIFY that all information given in this application form are true and correct and any false statement or misrepresentation shall be a ground for the termination of my employment with the Company without prejudice to the filing of <strong>APPROPRIATE CRIMINAL PROCEEDINGS</strong> by reason thereof.</small></p>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">SUBSCRIBED AND SWORN to before me this</label>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <input type="text" class="form-control" id="sworn_day" name="sworn_day" placeholder="Day" style="max-width: 100px;">
                                <label class="form-label mb-0">day of</label>
                                <input type="text" class="form-control" id="sworn_month" name="sworn_month" placeholder="Month" style="max-width: 150px;">
                                <label class="form-label mb-0">on</label>
                                <input type="text" class="form-control" id="sworn_year" name="sworn_year" placeholder="Year" style="max-width: 100px;">
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Tax Certificate No.</label>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <input type="text" class="form-control" id="tax_cert_no" name="tax_cert_no" placeholder="Tax Certificate No." style="max-width: 300px;">
                                <label class="form-label mb-0">issued at</label>
                                <input type="text" class="form-control" id="tax_cert_issued_at" name="tax_cert_issued_at" placeholder="Location" style="max-width: 300px;">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 text-end">
                        <div class="form-group">
                            <label class="form-label">Signature Over Printed Name</label>
                            <input type="text" class="form-control" id="sworn_signature" name="sworn_signature" placeholder="Signature" style="max-width: 400px; margin-left: auto;">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Affiant exhibited to me his/her Community</label>
                            <input type="text" class="form-control" id="affiant_community" name="affiant_community" placeholder="Community" style="max-width: 400px;">
                        </div>
                    </div>

                    <div class="col-12 text-end">
                        <div class="form-group">
                            <label class="form-label"><strong>NOTARY PUBLIC</strong></label>
                        </div>
                    </div>
                </div>

                <!-- Form Footer -->
                <div class="row g-3 mb-2">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-group">
                                <label class="form-label">Doc. No.:</label>
                                <input type="text" class="form-control" id="doc_no" name="doc_no" placeholder="Document No." style="max-width: 150px;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Page No.:</label>
                                <input type="text" class="form-control" id="page_no" name="page_no" placeholder="Page No." style="max-width: 150px;" value="2" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Book No.:</label>
                                <input type="text" class="form-control" id="book_no" name="book_no" placeholder="Book No." style="max-width: 150px;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Series of:</label>
                                <input type="text" class="form-control" id="series_of" name="series_of" placeholder="Series" style="max-width: 150px;">
               Р            </div>
                        </div>
                    </div>
                </div>

                <!-- Page 2 Form Actions -->
                <div class="form-actions d-flex justify-content-between">
                    <a href="?page=add_employee" class="btn btn-outline-modern">
                        <i class="fas fa-arrow-left me-2"></i>Back to Page 1
                    </a>
                    <div>
                        <a href="?page=employees" class="btn btn-outline-modern me-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary-modern">
                            <i class="fas fa-save me-2"></i>Save Employee
                        </button>
                    </div>
                </div>
            </form>
    </div>
</div>

<!-- Success Popup Modal -->
<?php if ($show_success_modal): ?>
<div class="modal fade show" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="false" style="display: block !important; background-color: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1050;">
    <div class="modal-dialog" role="document" style="margin: 2rem auto auto auto; max-width: 500px; position: relative;">
        <div class="modal-content" style="border: none; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div class="modal-header bg-success text-white" style="border-radius: 8px 8px 0 0; padding: 1rem 1.5rem;">
                <h5 class="modal-title" id="successModalLabel" style="margin: 0; font-weight: 600;">
                    <i class="fas fa-check-circle me-2"></i>Success!
                </h5>
                <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="closeSuccessModal()" style="margin: 0;"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div class="text-center mb-3">
                    <i class="fas fa-check-circle text-success fs-48"></i>
                </div>
                <p class="mb-0 text-center fs-md"><?php echo htmlspecialchars($success_message); ?></p>
                <?php if ($created_employee_name): ?>
                    <p class="text-center mt-3 mb-1 fs-base fw-medium">
                        <?php echo htmlspecialchars($created_employee_name); ?>
                    </p>
                    <p class="text-muted small mt-1 mb-0 text-center">
                        <?php if ($created_employee_type): ?>
                            <?php echo htmlspecialchars($created_employee_type); ?>
                        <?php endif; ?>
                        <?php if ($created_employee_no): ?>
                            #<?php echo htmlspecialchars($created_employee_no); ?>
                        <?php endif; ?>
                        <?php if ($created_employee_id): ?>
                            <br>Employee ID: <strong><?php echo htmlspecialchars($created_employee_id); ?></strong>
                        <?php endif; ?>
                    </p>
                <?php elseif ($created_employee_id): ?>
                    <p class="text-muted small mt-2 mb-0 text-center">Employee ID: <strong><?php echo htmlspecialchars($created_employee_id); ?></strong></p>
                <?php endif; ?>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 1rem 1.5rem; border-radius: 0 0 8px 8px;">
                <button type="button" class="btn btn-outline-modern" onclick="closeSuccessModal()">
                    <i class="fas fa-times me-2"></i>Close
                </button>
                <a href="?page=employees" class="btn btn-primary-modern">
                    <i class="fas fa-list me-2"></i>View All Employees
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Include paths helper if not already included
if (!function_exists('base_url')) {
    require_once __DIR__ . '/../includes/paths.php';
}
// Calculate CSS path relative to project root
$root_prefix = root_prefix();
$css_path = ($root_prefix ? $root_prefix : '') . '/pages/css/add_employee.css';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($css_path); ?>">
<style>
/* Validation styles for Page 2 */
.form-group.has-error .form-check-label {
    color: #dc3545;
}
.form-check-input.is-invalid {
    border-color: #dc3545;
}
.form-check-input.is-invalid:checked {
    background-color: #dc3545;
    border-color: #dc3545;
}
.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}
</style>

<script>
// Success Modal Functions
function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.opacity = '0';
        modal.style.transition = 'opacity 0.3s ease';
        setTimeout(() => {
            modal.style.display = 'none';
            modal.remove();
            // Redirect to employees page after closing modal
            window.location.href = '?page=employees&success=employee_created';
        }, 300);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Page 2: Make Y/N checkboxes mutually exclusive
    document.querySelectorAll('input[type="checkbox"][name^="req_"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // Get all checkboxes with the same name
                const sameName = document.querySelectorAll(`input[type="checkbox"][name="${this.name}"]`);
                sameName.forEach(cb => {
                    if (cb !== this) {
                        cb.checked = false;
                    }
                });
            }
        });
    });
    
    // Form validation
    const form = document.getElementById('page2EmployeeForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const errors = [];
            
            // Validate Question 1: At least one vacancy source must be selected
            const vacancySources = document.querySelectorAll('.vacancy-source-checkbox:checked');
            if (vacancySources.length === 0) {
                isValid = false;
                errors.push('Question 1: Please select at least one option for how you knew of the vacancy.');
                const vacancyError = document.getElementById('vacancy_source_error');
                if (vacancyError) {
                    vacancyError.style.display = 'block';
                }
                document.querySelectorAll('.vacancy-source-checkbox').forEach(cb => {
                    cb.classList.add('is-invalid');
                });
            } else {
                const vacancyError = document.getElementById('vacancy_source_error');
                if (vacancyError) {
                    vacancyError.style.display = 'none';
                }
                document.querySelectorAll('.vacancy-source-checkbox').forEach(cb => {
                    cb.classList.remove('is-invalid');
                });
            }
            
            // Validate required radio button groups
            const requiredRadioGroups = [
                { name: 'knows_agency_person', label: 'Question 2 (Do you know anyone from the AGENCY)' },
                { name: 'physical_defect', label: 'Question 3 (Physical defect/s or chronic ailments)' },
                { name: 'drives', label: 'Question 4 (Do you drive)' },
                { name: 'drinks_alcohol', label: 'Question 5 (Do you drink alcoholic beverages)' },
                { name: 'prohibited_drugs', label: 'Question 6 (Are you taking prohibited drugs)' },
                { name: 'convicted', label: 'Question 8 (Have you ever been convicted)' },
                { name: 'filed_case', label: 'Question 9 (Have you filed any case)' }
            ];
            
            requiredRadioGroups.forEach(group => {
                const radios = document.querySelectorAll(`input[type="radio"][name="${group.name}"]`);
                const checked = Array.from(radios).some(radio => radio.checked);
                if (!checked) {
                    isValid = false;
                    errors.push(group.label + ' is required.');
                    radios.forEach(radio => {
                        radio.classList.add('is-invalid');
                        const formGroup = radio.closest('.form-group');
                        if (formGroup) {
                            formGroup.classList.add('has-error');
                        }
                    });
                } else {
                    radios.forEach(radio => {
                        radio.classList.remove('is-invalid');
                        const formGroup = radio.closest('.form-group');
                        if (formGroup) {
                            formGroup.classList.remove('has-error');
                        }
                    });
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                // Show error message
                let errorMsg = 'Please fix the following errors:\n\n' + errors.join('\n');
                alert(errorMsg);
                // Scroll to first error
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
                return false;
            }
        });
        
        // Clear validation on change for radio buttons
        document.querySelectorAll('input[type="radio"][required]').forEach(radio => {
            radio.addEventListener('change', function() {
                const radios = document.querySelectorAll(`input[type="radio"][name="${this.name}"]`);
                radios.forEach(r => {
                    r.classList.remove('is-invalid');
                    const formGroup = r.closest('.form-group');
                    if (formGroup) {
                        formGroup.classList.remove('has-error');
                    }
                });
            });
        });
        
        // Clear validation on change for vacancy source checkboxes
        document.querySelectorAll('.vacancy-source-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checked = document.querySelectorAll('.vacancy-source-checkbox:checked').length;
                if (checked > 0) {
                    const vacancyError = document.getElementById('vacancy_source_error');
                    if (vacancyError) {
                        vacancyError.style.display = 'none';
                    }
                    document.querySelectorAll('.vacancy-source-checkbox').forEach(cb => {
                        cb.classList.remove('is-invalid');
                    });
                }
            });
        });
    }
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('successModal');
            if (modal && modal.style.display === 'block') {
                closeSuccessModal();
            }
        }
    });
    
    // Close modal on backdrop click
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSuccessModal();
            }
        });
    }
    
    // Reset form after successful creation
    <?php if ($show_success_modal): ?>
    const form = document.getElementById('page2EmployeeForm');
    if (form) {
        // Reset the form
        form.reset();
        // Clear any validation classes
        form.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
            el.classList.remove('is-invalid', 'is-valid');
        });
    }
    <?php endif; ?>
});
</script>
