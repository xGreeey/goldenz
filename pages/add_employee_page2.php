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
    
    // Validate Basic Requirements section - All 5 fields are mandatory
    $basic_requirements_fields = [
        'sec_lic_no' => 'Security License No.',
        'sss_no_page2' => 'SSS No.',
        'pagibig_no_page2' => 'Pag-IBIG No.',
        'philhealth_no_page2' => 'PhilHealth No.',
        'tin_no_page2' => 'TIN No.'
    ];
    
    foreach ($basic_requirements_fields as $field => $label) {
        if (empty($_POST[$field]) || trim($_POST[$field]) === '') {
            $errors[] = $label . ' is required.';
        } else {
            $value = trim($_POST[$field]);
            // Validate format
            switch ($field) {
                case 'sec_lic_no':
                    // Security License: PREFIX-YYYY###### or PREFIXYYYY######
                    if (!preg_match('/^[A-Z0-9]{2,4}-?[0-9]{4}[0-9]{5,10}$/i', $value)) {
                        $errors[] = $label . ' must be in format PREFIX-YYYY###### or PREFIXYYYY###### (e.g., R03-202210000014, NCR20221025742).';
                    }
                    break;
                case 'sss_no_page2':
                    // SSS: ##-#######-#
                    if (!preg_match('/^\d{2}-\d{7}-\d{1}$/', $value)) {
                        $errors[] = $label . ' must be in format ##-#######-# (e.g., 02-1179877-4).';
                    }
                    break;
                case 'pagibig_no_page2':
                    // Pag-IBIG: ####-####-####
                    if (!preg_match('/^\d{4}-\d{4}-\d{4}$/', $value)) {
                        $errors[] = $label . ' must be in format ####-####-#### (e.g., 1210-9087-6528).';
                    }
                    break;
                case 'philhealth_no_page2':
                    // PhilHealth: ##-#########-#
                    if (!preg_match('/^\d{2}-\d{9}-\d{1}$/', $value)) {
                        $errors[] = $label . ' must be in format ##-#########-# (e.g., 21-200190443-1).';
                    }
                    break;
                case 'tin_no_page2':
                    // TIN: ###-###-###-###
                    if (!preg_match('/^\d{3}-\d{3}-\d{3}-\d{3}$/', $value)) {
                        $errors[] = $label . ' must be in format ###-###-###-### (e.g., 360-889-408-000).';
                    }
                    break;
            }
        }
    }
    
    // If we have Page 1 data, proceed with INSERT
    if (empty($errors) && !empty($page1_data)) {
        try {
            $pdo = get_db_connection();
            
            // Ensure all Page 2 columns exist in the database
            if (function_exists('ensure_employee_columns')) {
                ensure_employee_columns([
                    // General Information
                    'vacancy_source' => 'TEXT NULL',
                    'referral_name' => 'VARCHAR(150) NULL',
                    'knows_agency_person' => "ENUM('Yes','No') NULL",
                    'agency_person_name' => 'VARCHAR(200) NULL',
                    'physical_defect' => "ENUM('Yes','No') NULL",
                    'physical_defect_specify' => 'TEXT NULL',
                    'drives' => "ENUM('Yes','No') NULL",
                    'drivers_license_no' => 'VARCHAR(50) NULL',
                    'drivers_license_exp' => 'VARCHAR(50) NULL',
                    'drinks_alcohol' => "ENUM('Yes','No') NULL",
                    'alcohol_frequency' => 'VARCHAR(100) NULL',
                    'prohibited_drugs' => "ENUM('Yes','No') NULL",
                    'security_guard_experience' => 'VARCHAR(100) NULL',
                    'convicted' => "ENUM('Yes','No') NULL",
                    'conviction_details' => 'TEXT NULL',
                    'filed_case' => "ENUM('Yes','No') NULL",
                    'case_specify' => 'TEXT NULL',
                    'action_after_termination' => 'TEXT NULL',
                    // Signatures and Initials
                    'signature_1' => 'VARCHAR(200) NULL',
                    'signature_2' => 'VARCHAR(200) NULL',
                    'signature_3' => 'VARCHAR(200) NULL',
                    'initial_1' => 'VARCHAR(100) NULL',
                    'initial_2' => 'VARCHAR(100) NULL',
                    'initial_3' => 'VARCHAR(100) NULL',
                    // Requirements
                    'requirements_signature' => 'VARCHAR(200) NULL',
                    'req_2x2' => "ENUM('YO','NO') NULL",
                    'req_birth_cert' => "ENUM('YO','NO') NULL",
                    'req_barangay' => "ENUM('YO','NO') NULL",
                    'req_police' => "ENUM('YO','NO') NULL",
                    'req_nbi' => "ENUM('YO','NO') NULL",
                    'req_di' => "ENUM('YO','NO') NULL",
                    'req_diploma' => "ENUM('YO','NO') NULL",
                    'req_neuro_drug' => "ENUM('YO','NO') NULL",
                    'req_sec_license' => "ENUM('YO','NO') NULL",
                    'sec_lic_no' => 'VARCHAR(50) NULL',
                    'req_sec_lic_no' => "ENUM('YO','NO') NULL",
                    'req_sss' => "ENUM('YO','NO') NULL",
                    'req_pagibig' => "ENUM('YO','NO') NULL",
                    'req_philhealth' => "ENUM('YO','NO') NULL",
                    'req_tin' => "ENUM('YO','NO') NULL",
                    // Sworn Statement
                    'sworn_day' => 'VARCHAR(10) NULL',
                    'sworn_month' => 'VARCHAR(50) NULL',
                    'sworn_year' => 'VARCHAR(10) NULL',
                    'tax_cert_no' => 'VARCHAR(100) NULL',
                    'tax_cert_issued_at' => 'VARCHAR(200) NULL',
                    'sworn_signature' => 'VARCHAR(200) NULL',
                    'affiant_community' => 'VARCHAR(200) NULL',
                    // Form Footer
                    'doc_no' => 'VARCHAR(50) NULL',
                    'page_no' => 'VARCHAR(10) NULL',
                    'book_no' => 'VARCHAR(50) NULL',
                    'series_of' => 'VARCHAR(50) NULL',
                    // Fingerprints
                    'fingerprint_right_thumb' => 'VARCHAR(255) NULL',
                    'fingerprint_right_index' => 'VARCHAR(255) NULL',
                    'fingerprint_right_middle' => 'VARCHAR(255) NULL',
                    'fingerprint_right_ring' => 'VARCHAR(255) NULL',
                    'fingerprint_right_little' => 'VARCHAR(255) NULL',
                    'fingerprint_left_thumb' => 'VARCHAR(255) NULL',
                    'fingerprint_left_index' => 'VARCHAR(255) NULL',
                    'fingerprint_left_middle' => 'VARCHAR(255) NULL',
                    'fingerprint_left_ring' => 'VARCHAR(255) NULL',
                    'fingerprint_left_little' => 'VARCHAR(255) NULL',
                ]);
            }
            
            // Prepare page 2 data
            $page2_data = [];
            
            // General Information
            $page2_data['vacancy_source'] = !empty($_POST['vacancy_source']) ? json_encode($_POST['vacancy_source']) : null;
            // referral_name is VARCHAR(150) - truncate to 150 characters
            $page2_data['referral_name'] = !empty($_POST['referral_name']) ? mb_substr(trim($_POST['referral_name']), 0, 150) : null;
            $page2_data['knows_agency_person'] = !empty($_POST['knows_agency_person']) ? $_POST['knows_agency_person'] : null;
            // agency_person_name is VARCHAR(200) - truncate to 200 characters
            $page2_data['agency_person_name'] = !empty($_POST['agency_person_name']) ? mb_substr(trim($_POST['agency_person_name']), 0, 200) : null;
            $page2_data['physical_defect'] = !empty($_POST['physical_defect']) ? $_POST['physical_defect'] : null;
            $page2_data['physical_defect_specify'] = !empty($_POST['physical_defect_specify']) ? trim($_POST['physical_defect_specify']) : null;
            $page2_data['drives'] = !empty($_POST['drives']) ? $_POST['drives'] : null;
            // drivers_license_no is VARCHAR(50) - truncate to 50 characters
            $page2_data['drivers_license_no'] = !empty($_POST['drivers_license_no']) ? mb_substr(trim($_POST['drivers_license_no']), 0, 50) : null;
            // drivers_license_exp is VARCHAR(50) - truncate to 50 characters
            $page2_data['drivers_license_exp'] = !empty($_POST['drivers_license_exp']) ? mb_substr(trim($_POST['drivers_license_exp']), 0, 50) : null;
            $page2_data['drinks_alcohol'] = !empty($_POST['drinks_alcohol']) ? $_POST['drinks_alcohol'] : null;
            // alcohol_frequency is VARCHAR(100) - truncate to 100 characters
            $page2_data['alcohol_frequency'] = !empty($_POST['alcohol_frequency']) ? mb_substr(trim($_POST['alcohol_frequency']), 0, 100) : null;
            $page2_data['prohibited_drugs'] = !empty($_POST['prohibited_drugs']) ? $_POST['prohibited_drugs'] : null;
            // security_guard_experience is VARCHAR(100) - truncate to 100 characters
            $page2_data['security_guard_experience'] = !empty($_POST['security_guard_experience']) ? mb_substr(trim($_POST['security_guard_experience']), 0, 100) : null;
            $page2_data['convicted'] = !empty($_POST['convicted']) ? $_POST['convicted'] : null;
            $page2_data['conviction_details'] = !empty($_POST['conviction_details']) ? trim($_POST['conviction_details']) : null;
            $page2_data['filed_case'] = !empty($_POST['filed_case']) ? $_POST['filed_case'] : null;
            $page2_data['case_specify'] = !empty($_POST['case_specify']) ? trim($_POST['case_specify']) : null;
            $page2_data['action_after_termination'] = !empty($_POST['action_after_termination']) ? trim($_POST['action_after_termination']) : null;
            
            // Specimen Signature and Initial
            // signature_1, signature_2, signature_3 are VARCHAR(200) - truncate to 200 characters
            $page2_data['signature_1'] = !empty($_POST['signature_1']) ? mb_substr(trim($_POST['signature_1']), 0, 200) : null;
            $page2_data['signature_2'] = !empty($_POST['signature_2']) ? mb_substr(trim($_POST['signature_2']), 0, 200) : null;
            $page2_data['signature_3'] = !empty($_POST['signature_3']) ? mb_substr(trim($_POST['signature_3']), 0, 200) : null;
            // initial_1, initial_2, initial_3 are VARCHAR(100) - truncate to 100 characters
            $page2_data['initial_1'] = !empty($_POST['initial_1']) ? mb_substr(trim($_POST['initial_1']), 0, 100) : null;
            $page2_data['initial_2'] = !empty($_POST['initial_2']) ? mb_substr(trim($_POST['initial_2']), 0, 100) : null;
            $page2_data['initial_3'] = !empty($_POST['initial_3']) ? mb_substr(trim($_POST['initial_3']), 0, 100) : null;
            
            // Basic Requirements
            // requirements_signature is VARCHAR(200) - truncate to 200 characters
            $page2_data['requirements_signature'] = !empty($_POST['requirements_signature']) ? mb_substr(trim($_POST['requirements_signature']), 0, 200) : null;
            $page2_data['req_2x2'] = !empty($_POST['req_2x2']) ? $_POST['req_2x2'] : null;
            $page2_data['req_birth_cert'] = !empty($_POST['req_birth_cert']) ? $_POST['req_birth_cert'] : null;
            $page2_data['req_barangay'] = !empty($_POST['req_barangay']) ? $_POST['req_barangay'] : null;
            $page2_data['req_police'] = !empty($_POST['req_police']) ? $_POST['req_police'] : null;
            $page2_data['req_nbi'] = !empty($_POST['req_nbi']) ? $_POST['req_nbi'] : null;
            $page2_data['req_di'] = !empty($_POST['req_di']) ? $_POST['req_di'] : null;
            $page2_data['req_diploma'] = !empty($_POST['req_diploma']) ? $_POST['req_diploma'] : null;
            $page2_data['req_neuro_drug'] = !empty($_POST['req_neuro_drug']) ? $_POST['req_neuro_drug'] : null;
            $page2_data['req_sec_license'] = !empty($_POST['req_sec_license']) ? $_POST['req_sec_license'] : null;
            // sec_lic_no is VARCHAR(50) - truncate to 50 characters
            $page2_data['sec_lic_no'] = !empty($_POST['sec_lic_no']) ? mb_substr(trim($_POST['sec_lic_no']), 0, 50) : null;
            $page2_data['req_sec_lic_no'] = !empty($_POST['req_sec_lic_no']) ? $_POST['req_sec_lic_no'] : null;
            $page2_data['req_sss'] = !empty($_POST['req_sss']) ? $_POST['req_sss'] : null;
            $page2_data['req_pagibig'] = !empty($_POST['req_pagibig']) ? $_POST['req_pagibig'] : null;
            $page2_data['req_philhealth'] = !empty($_POST['req_philhealth']) ? $_POST['req_philhealth'] : null;
            $page2_data['req_tin'] = !empty($_POST['req_tin']) ? $_POST['req_tin'] : null;
            
            // Sworn Statement
            // sworn_day is VARCHAR(10) - truncate to 10 characters
            $page2_data['sworn_day'] = !empty($_POST['sworn_day']) ? mb_substr(trim($_POST['sworn_day']), 0, 10) : null;
            // Handle sworn_month_year - split if it contains both month and year
            if (!empty($_POST['sworn_month_year'])) {
                $sworn_month_year = trim($_POST['sworn_month_year']);
                // Try to split if it contains a space or separator
                if (preg_match('/^(.+?)\s+(\d{4})$/', $sworn_month_year, $matches)) {
                    // sworn_month is VARCHAR(50) - truncate to 50 characters
                    $page2_data['sworn_month'] = mb_substr(trim($matches[1]), 0, 50);
                    // sworn_year is VARCHAR(10) - truncate to 10 characters
                    $page2_data['sworn_year'] = mb_substr(trim($matches[2]), 0, 10);
                } else {
                    // If no clear separator, try to extract year (4 digits at end)
                    if (preg_match('/(\d{4})$/', $sworn_month_year, $year_match)) {
                        $page2_data['sworn_year'] = mb_substr($year_match[1], 0, 10);
                        $page2_data['sworn_month'] = mb_substr(trim(str_replace($year_match[1], '', $sworn_month_year)), 0, 50);
                    } else {
                        // If no year found, treat entire value as month
                        $page2_data['sworn_month'] = mb_substr($sworn_month_year, 0, 50);
                        $page2_data['sworn_year'] = null;
                    }
                }
            } else {
                // Fallback to separate fields if they exist
                // sworn_month is VARCHAR(50) - truncate to 50 characters
                $page2_data['sworn_month'] = !empty($_POST['sworn_month']) ? mb_substr(trim($_POST['sworn_month']), 0, 50) : null;
                // sworn_year is VARCHAR(10) - truncate to 10 characters
                $page2_data['sworn_year'] = !empty($_POST['sworn_year']) ? mb_substr(trim($_POST['sworn_year']), 0, 10) : null;
            }
            // tax_cert_no is VARCHAR(100) - truncate to 100 characters
            $page2_data['tax_cert_no'] = !empty($_POST['tax_cert_no']) ? mb_substr(trim($_POST['tax_cert_no']), 0, 100) : null;
            // tax_cert_issued_at is VARCHAR(200) - truncate to 200 characters
            $page2_data['tax_cert_issued_at'] = !empty($_POST['tax_cert_issued_at']) ? mb_substr(trim($_POST['tax_cert_issued_at']), 0, 200) : null;
            // sworn_signature is VARCHAR(200) - truncate to 200 characters
            $page2_data['sworn_signature'] = !empty($_POST['sworn_signature']) ? mb_substr(trim($_POST['sworn_signature']), 0, 200) : null;
            // affiant_community is VARCHAR(200) - truncate to 200 characters
            $page2_data['affiant_community'] = !empty($_POST['affiant_community']) ? mb_substr(trim($_POST['affiant_community']), 0, 200) : null;
            
            // Form Footer
            // doc_no is VARCHAR(50) - truncate to 50 characters
            $page2_data['doc_no'] = !empty($_POST['doc_no']) ? mb_substr(trim($_POST['doc_no']), 0, 50) : null;
            // page_no is VARCHAR(10) - truncate to 10 characters
            $page2_data['page_no'] = !empty($_POST['page_no']) ? mb_substr(trim($_POST['page_no']), 0, 10) : null;
            // book_no is VARCHAR(50) - truncate to 50 characters
            $page2_data['book_no'] = !empty($_POST['book_no']) ? mb_substr(trim($_POST['book_no']), 0, 50) : null;
            // series_of is VARCHAR(50) - truncate to 50 characters
            $page2_data['series_of'] = !empty($_POST['series_of']) ? mb_substr(trim($_POST['series_of']), 0, 50) : null;
            
            // Handle Page 2 ID number fields - these may update Page 1 fields if provided
            // Only update if the Page 2 values are different and not empty
            // Note: These fields are VARCHAR(20) in the database - truncate to 20 characters
            if (!empty($_POST['sss_no_page2']) && trim($_POST['sss_no_page2']) !== '') {
                // Update sss_no if provided in Page 2 (for verification/update)
                $page2_data['sss_no'] = mb_substr(trim($_POST['sss_no_page2']), 0, 20);
            }
            if (!empty($_POST['pagibig_no_page2']) && trim($_POST['pagibig_no_page2']) !== '') {
                // Update pagibig_no if provided in Page 2
                $page2_data['pagibig_no'] = mb_substr(trim($_POST['pagibig_no_page2']), 0, 20);
            }
            if (!empty($_POST['philhealth_no_page2']) && trim($_POST['philhealth_no_page2']) !== '') {
                // Update philhealth_no if provided in Page 2
                $page2_data['philhealth_no'] = mb_substr(trim($_POST['philhealth_no_page2']), 0, 20);
            }
            if (!empty($_POST['tin_no_page2']) && trim($_POST['tin_no_page2']) !== '') {
                // Update tin_number if provided in Page 2
                $page2_data['tin_number'] = mb_substr(trim($_POST['tin_no_page2']), 0, 20);
            }
            
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

            // Check for duplicate license number before insert (license_no is UNIQUE)
            if (!empty($page1_data['license_no'])) {
                try {
                    $dup_stmt = $pdo->prepare("SELECT id FROM employees WHERE license_no = ? LIMIT 1");
                    $dup_stmt->execute([$page1_data['license_no']]);
                    if ($dup_stmt->fetch()) {
                        $errors[] = 'License number already exists. Please use a unique License No.';
                    }
                } catch (Exception $dup_e) {
                    // Ignore duplicate check errors and proceed with insert
                }
            }

            // First, insert Page 1 data using the add_employee function
            $new_employee_id = empty($errors) ? add_employee($page1_data) : false;
            
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

                <!-- Specimen Signature and Initial Section - Formal Document Style -->
                <div class="specimen-section">
                    <!-- Section Title -->
                    <div class="specimen-title">
                        <h4>SPECIMEN SIGNATURE AND INITIAL</h4>
                    </div>
                    
                    <!-- Two-Column Layout -->
                    <div class="specimen-columns">
                        <!-- Left Column: Signature -->
                        <div class="specimen-column">
                            <div class="specimen-column-header">SIGNATURE</div>
                            <div class="specimen-row">
                                <span class="specimen-number">1.</span>
                                <input type="text" class="specimen-underline" id="signature_1" name="signature_1">
                            </div>
                            <div class="specimen-row">
                                <span class="specimen-number">2.</span>
                                <input type="text" class="specimen-underline" id="signature_2" name="signature_2">
                        </div>
                    </div>
                    
                        <!-- Right Column: Initial -->
                        <div class="specimen-column">
                            <div class="specimen-column-header">INITIAL (PINAIKLING PIRMA)</div>
                            <div class="specimen-row">
                                <span class="specimen-number">1.</span>
                                <input type="text" class="specimen-underline" id="initial_1" name="initial_1">
                            </div>
                            <div class="specimen-row">
                                <span class="specimen-number">2.</span>
                                <input type="text" class="specimen-underline" id="initial_2" name="initial_2">
                            </div>
                        </div>
                    </div>

                    <!-- Certification Statement -->
                    <div class="specimen-certification">
                        <p>I HEREBY CERTIFY that the above specimen is my <strong>OFFICIAL</strong> signatures and initial of which I <strong>CONFIRM</strong> by my signature below.</p>
                    </div>
                </div>

                <style>
                /* Specimen Signature Section - Formal Document Style */
                .specimen-section {
                    margin: 2rem 0;
                    padding: 1.5rem;
                    background: #ffffff;
                    border: 1px solid #d1d5db;
                    page-break-inside: avoid;
                }

                .specimen-title {
                    text-align: center;
                    margin-bottom: 1rem;
                    padding-bottom: 0.5rem;
                    border-bottom: 1px solid #d1d5db;
                }

                .specimen-title h4 {
                    margin: 0;
                    font-size: 0.875rem;
                    font-weight: 700;
                    letter-spacing: 0.5px;
                    text-transform: uppercase;
                    color: #1f2937;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }

                .specimen-columns {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 2rem;
                    margin-bottom: 1rem;
                }

                .specimen-column {
                    min-width: 0;
                }

                .specimen-column-header {
                    font-size: 0.75rem;
                    font-weight: 600;
                    text-transform: uppercase;
                    color: #374151;
                    margin-bottom: 0.75rem;
                    letter-spacing: 0.3px;
                }

                .specimen-row {
                    display: flex;
                    align-items: center;
                    margin-bottom: 0.875rem;
                    gap: 0.5rem;
                }

                .specimen-number {
                    font-size: 0.813rem;
                    font-weight: 500;
                    color: #374151;
                    min-width: 1.25rem;
                    flex-shrink: 0;
                }

                .specimen-underline {
                    flex: 1;
                    border: none;
                    border-bottom: 1px solid #d1d5db;
                    padding: 0.25rem 0.5rem;
                    font-size: 0.875rem;
                    font-family: "Courier New", monospace;
                    background: transparent;
                    outline: none;
                    transition: border-color 0.2s ease;
                }

                .specimen-underline:focus {
                    border-bottom-color: #3b82f6;
                    background-color: #f9fafb;
                }

                .specimen-underline::placeholder {
                    color: #9ca3af;
                    font-style: italic;
                }

                .specimen-certification {
                    margin-top: 1.5rem;
                    padding-top: 1rem;
                    border-top: 1px solid #e5e7eb;
                }

                .specimen-certification p {
                    margin: 0;
                    font-size: 0.75rem;
                    line-height: 1.5;
                    color: #374151;
                    text-align: justify;
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .specimen-columns {
                        grid-template-columns: 1fr;
                        gap: 1.5rem;
                    }
                    
                    .specimen-section {
                        padding: 1rem;
                    }
                }

                /* Print Styles */
                @media print {
                    .specimen-section {
                        border: 1px solid #000;
                        box-shadow: none;
                    }
                    
                    .specimen-title {
                        border-bottom-color: #000;
                    }
                    
                    .specimen-underline {
                        border-bottom: 1px solid #000;
                    }
                    
                    .specimen-certification {
                        border-top-color: #000;
                    }
                }

                /* Fingerprints and Requirements - Side by Side Layout */
                .formal-sections-container {
                    display: grid;
                    grid-template-columns: 1.5fr 1fr;
                    gap: 2rem;
                    margin: 2rem 0;
                    page-break-inside: avoid;
                    align-items: stretch;
                }

                .formal-section-title {
                    text-align: center;
                    margin-bottom: 0.75rem;
                    padding-bottom: 0.375rem;
                    border-bottom: 1px solid #374151;
                }

                .formal-section-title h4 {
                    margin: 0;
                    font-size: 0.813rem;
                    font-weight: 700;
                    letter-spacing: 0.5px;
                    text-transform: uppercase;
                    color: #1f2937;
                }

                /* Fingerprints Section */
                .fingerprints-section {
                    border: 1px solid #d1d5db;
                    padding: 1rem;
                    background: #ffffff;
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                }

                .fingerprints-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 0.25rem;
                    flex: 1;
                    display: table;
                }

                .fingerprints-table tbody {
                    height: 100%;
                }

                .fingerprints-table tbody tr {
                    height: 50%;
                }

                .fingerprint-cell {
                    border: 1px solid #374151;
                    padding: 1rem 0.5rem;
                    text-align: center;
                    vertical-align: middle;
                    width: 20%;
                }

                .fingerprint-label {
                    font-size: 0.625rem;
                    font-weight: 600;
                    text-transform: uppercase;
                    color: #374151;
                    line-height: 1.2;
                    margin-bottom: 0.5rem;
                    display: block;
                }

                .fingerprint-upload {
                    width: 100%;
                    font-size: 0.625rem;
                    padding: 0.25rem;
                    border: none;
                    background: transparent;
                    margin-top: 0.25rem;
                }

                .fingerprint-upload::-webkit-file-upload-button {
                    font-size: 0.625rem;
                    padding: 0.25rem 0.5rem;
                    border: 1px solid #d1d5db;
                    background: #f9fafb;
                    cursor: pointer;
                }

                /* Basic Requirements Section */
                .requirements-section {
                    border: 1px solid #d1d5db;
                    padding: 1rem;
                    background: #ffffff;
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                }

                .requirements-signature {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    margin-bottom: 0.75rem;
                    padding-bottom: 0.75rem;
                    border-bottom: 1px solid #e5e7eb;
                }

                .req-sig-label {
                    font-size: 0.75rem;
                    font-weight: 500;
                    color: #374151;
                    white-space: nowrap;
                }

                .req-signature-underline {
                    flex: 1;
                    border: none;
                    border-bottom: 1px solid #d1d5db;
                    padding: 0.25rem 0.5rem;
                    font-size: 0.75rem;
                    background: transparent;
                    outline: none;
                }

                .requirements-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 0.5rem;
                    margin-top: 0.75rem;
                    padding-bottom: 0.25rem;
                    border-bottom: 1px solid #e5e7eb;
                }

                .requirements-header:first-of-type {
                    margin-top: 0.25rem;
                }

                .req-header-label {
                    font-size: 0.75rem;
                    font-weight: 600;
                    color: #374151;
                }

                .req-header-checkboxes {
                    display: flex;
                    gap: 1rem;
                    font-size: 0.75rem;
                    font-weight: 600;
                    color: #374151;
                }

                .requirements-list {
                    display: flex;
                    flex-direction: column;
                    gap: 0.5rem;
                    flex: 1;
                    justify-content: space-evenly;
                }

                .req-item {
                    display: grid;
                    grid-template-columns: auto 1fr auto;
                    gap: 0.5rem;
                    align-items: center;
                    padding: 0.125rem 0;
                }

                .req-number {
                    font-size: 0.75rem;
                    font-weight: 500;
                    color: #374151;
                    min-width: 1.25rem;
                }

                .req-label {
                    font-size: 0.75rem;
                    color: #374151;
                }

                .req-checkboxes {
                    display: flex;
                    gap: 0.75rem;
                    align-items: center;
                }

                .req-checkboxes input[type="checkbox"] {
                    width: 14px;
                    height: 14px;
                    margin: 0;
                    cursor: pointer;
                }

                .req-item-with-input {
                    display: grid;
                    grid-template-columns: auto auto 1fr auto;
                    gap: 0.375rem;
                    align-items: center;
                    padding: 0.25rem 0;
                }

                .req-input-wrapper {
                    position: relative;
                    display: inline-block;
                    min-width: 200px;
                }
                
                .req-input-underline {
                    border: none;
                    border-bottom: 1px solid #d1d5db;
                    padding: 0.125rem 0.25rem;
                    font-size: 0.75rem;
                    background: transparent;
                    width: 100%;
                    transition: border-color 0.2s ease;
                    outline: none;
                    min-width: 120px;
                }

                .req-input-underline:focus {
                    border-bottom-color: #3b82f6;
                    background-color: #f9fafb;
                    outline: none;
                }
                
                .req-input-underline.is-invalid {
                    border-bottom-color: #dc3545;
                    background-color: #fff5f5;
                }
                
                .req-input-underline.is-valid {
                    border-bottom-color: #28a745;
                    background-color: #f0fff4;
                }
                
                .req-input-wrapper .invalid-feedback {
                    display: none;
                    width: 100%;
                    margin-top: 0.25rem;
                    font-size: 0.7rem;
                    color: #dc3545;
                    position: absolute;
                    top: 100%;
                    left: 0;
                    z-index: 10;
                    background: white;
                    padding: 0.25rem 0.5rem;
                    border-radius: 0.25rem;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    white-space: nowrap;
                }
                
                .req-input-wrapper .invalid-feedback[style*="block"] {
                    display: block !important;
                }
                }

                /* Responsive */
                @media (max-width: 992px) {
                    .formal-sections-container {
                        grid-template-columns: 1fr;
                    }
                    
                    .fingerprints-section,
                    .requirements-section {
                        margin-bottom: 1.5rem;
                    }
                }

                /* Print Styles */
                @media print {
                    .formal-sections-container {
                        border: 1px solid #000;
                    }
                    
                    .fingerprints-section,
                    .requirements-section {
                        border-color: #000;
                    }
                    
                    .fingerprint-cell {
                        border-color: #000;
                    }
                    
                    .formal-section-title {
                        border-bottom-color: #000;
                    }
                    
                    .req-signature-underline,
                    .req-input-underline {
                        border-bottom-color: #000;
                    }
                }

                /* Sworn Statement Section - Formal Legal Affidavit Style */
                .sworn-statement-section {
                    margin: 2.5rem 0;
                    padding: 2rem;
                    background: #ffffff;
                    border: 1px solid #d1d5db;
                    page-break-inside: avoid;
                }

                .sworn-title {
                    text-align: center;
                    margin-bottom: 1.25rem;
                    padding-bottom: 0.5rem;
                    border-bottom: 1px solid #374151;
                }

                .sworn-title h4 {
                    margin: 0;
                    font-size: 0.938rem;
                    font-weight: 700;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                    color: #1f2937;
                }

                .sworn-body {
                    margin-bottom: 2rem;
                }

                .sworn-body p {
                    margin: 0 0 1rem 0;
                    font-size: 0.813rem;
                    line-height: 1.65;
                    text-align: justify;
                    color: #1f2937;
                    text-indent: 2rem;
                }

                .sworn-body p:last-child {
                    margin-bottom: 0;
                }

                .sworn-signature-line {
                    display: flex;
                    flex-direction: column;
                    align-items: flex-end;
                    margin: 2rem 0 1.5rem 0;
                    padding-right: 2rem;
                }

                .sworn-signature-input {
                    width: 100%;
                    max-width: 400px;
                    border: none;
                    border-bottom: 1px solid #374151;
                    padding: 0.5rem 0;
                    font-size: 0.875rem;
                    background: transparent;
                    outline: none;
                    text-align: center;
                }

                .sworn-signature-label {
                    font-size: 0.75rem;
                    color: #374151;
                    margin-top: 0.25rem;
                    font-style: italic;
                }

                .sworn-subscription {
                    margin: 1.5rem 0;
                    font-size: 0.813rem;
                    line-height: 1.8;
                    color: #1f2937;
                    display: flex;
                    flex-wrap: wrap;
                    align-items: baseline;
                    gap: 0.375rem;
                }

                .subscription-text {
                    white-space: nowrap;
                }

                .subscription-input {
                    border: none;
                    border-bottom: 1px solid #374151;
                    padding: 0.125rem 0.5rem;
                    font-size: 0.813rem;
                    background: transparent;
                    outline: none;
                    min-width: 60px;
                    width: auto;
                }

                .subscription-input-wide {
                    min-width: 120px;
                }

                .subscription-input:focus {
                    border-bottom-color: #3b82f6;
                    background-color: #f9fafb;
                }

                .sworn-footer {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 2rem;
                    margin-top: 2.5rem;
                    align-items: end;
                }

                .notarial-details-left {
                    display: flex;
                    flex-direction: column;
                    gap: 0.25rem;
                }

                .notarial-field {
                    display: flex;
                    align-items: baseline;
                    gap: 0.5rem;
                    font-size: 0.813rem;
                    color: #374151;
                }

                .notarial-label {
                    font-weight: 500;
                    white-space: nowrap;
                }

                .notarial-underline {
                    flex: 1;
                    border: none;
                    border-bottom: 1px solid #374151;
                    padding: 0.125rem 0.25rem;
                    font-size: 0.813rem;
                    background: transparent;
                    outline: none;
                    max-width: 200px;
                }

                .notarial-underline:focus {
                    border-bottom-color: #3b82f6;
                    background-color: #f9fafb;
                }

                .notarial-semicolon {
                    font-weight: 600;
                    color: #374151;
                }

                .notarial-details-right {
                    text-align: right;
                }

                .notary-public-label {
                    font-size: 0.875rem;
                    font-weight: 700;
                    letter-spacing: 0.5px;
                    color: #1f2937;
                    text-transform: uppercase;
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .sworn-statement-section {
                        padding: 1.5rem;
                    }
                    
                    .sworn-body p {
                        font-size: 0.75rem;
                        text-indent: 1.5rem;
                    }
                    
                    .sworn-signature-line {
                        padding-right: 0;
                    }
                    
                    .sworn-footer {
                        grid-template-columns: 1fr;
                        gap: 1.5rem;
                    }
                    
                    .notarial-details-right {
                        text-align: left;
                    }
                }

                /* Print Styles for Sworn Statement */
                @media print {
                    .sworn-statement-section {
                        border: 1px solid #000;
                        box-shadow: none;
                    }
                    
                    .sworn-title {
                        border-bottom-color: #000;
                    }
                    
                    .sworn-signature-input,
                    .subscription-input,
                    .notarial-underline {
                        border-bottom-color: #000;
                    }
                }
                </style>

                <!-- Fingerprints and Basic Requirements Section - Side by Side -->
                <div class="formal-sections-container">
                    <!-- Left Side: Fingerprints -->
                    <div class="fingerprints-section">
                        <div class="formal-section-title">
                            <h4>FINGERPRINTS</h4>
                    </div>
                    
                        <table class="fingerprints-table">
                                <tbody>
                                <!-- RIGHT Hand Row -->
                                    <tr>
                                    <td class="fingerprint-cell">
                                        <div class="fingerprint-label">RIGHT<br>THUMB</div>
                                        <input type="file" class="fingerprint-upload" name="fingerprint_right_thumb" accept="image/*">
                                        </td>
                                    <td class="fingerprint-cell">
                                        <div class="fingerprint-label">RIGHT<br>INDEX FINGER</div>
                                        <input type="file" class="fingerprint-upload" name="fingerprint_right_index" accept="image/*">
                                        </td>
                                    <td class="fingerprint-cell">
                                        <div class="fingerprint-label">RIGHT<br>MIDDLE FINGER</div>
                                        <input type="file" class="fingerprint-upload" name="fingerprint_right_middle" accept="image/*">
                                        </td>
                                    <td class="fingerprint-cell">
                                        <div class="fingerprint-label">RIGHT<br>RING FINGER</div>
                                        <input type="file" class="fingerprint-upload" name="fingerprint_right_ring" accept="image/*">
                                        </td>
                                    <td class="fingerprint-cell">
                                        <div class="fingerprint-label">RIGHT<br>LITTLE FINGER</div>
                                        <input type="file" class="fingerprint-upload" name="fingerprint_right_little" accept="image/*">
                                        </td>
                                    </tr>
                                
                                <!-- LEFT Hand Row -->
                                <tr>
                                    <td class="fingerprint-cell">
                                        <div class="fingerprint-label">LEFT<br>THUMB</div>
                                        <input type="file" class="fingerprint-upload" name="fingerprint_left_thumb" accept="image/*">
                                        </td>
                                    <td class="fingerprint-cell">
                                        <div class="fingerprint-label">LEFT<br>INDEX FINGER</div>
                                        <input type="file" class="fingerprint-upload" name="fingerprint_left_index" accept="image/*">
                                        </td>
                                    <td class="fingerprint-cell">
                                        <div class="fingerprint-label">LEFT<br>MIDDLE FINGER</div>
                                        <input type="file" class="fingerprint-upload" name="fingerprint_left_middle" accept="image/*">
                                        </td>
                                    <td class="fingerprint-cell">
                                        <div class="fingerprint-label">LEFT<br>RING FINGER</div>
                                        <input type="file" class="fingerprint-upload" name="fingerprint_left_ring" accept="image/*">
                                        </td>
                                    <td class="fingerprint-cell">
                                        <div class="fingerprint-label">LEFT<br>LITTLE FINGER</div>
                                        <input type="file" class="fingerprint-upload" name="fingerprint_left_little" accept="image/*">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                </div>

                    <!-- Right Side: Basic Requirements -->
                    <div class="requirements-section">
                        <div class="formal-section-title">
                            <h4>BASIC REQUIREMENTS</h4>
                    </div>
                    
                        <!-- Signature Line -->
                        <div class="requirements-signature">
                            <span class="req-sig-label">Signature Over Printed Name:</span>
                            <input type="text" class="req-signature-underline" id="requirements_signature" name="requirements_signature">
                    </div>

                        <!-- Requirements List -->
                        <div class="requirements-header">
                            <span class="req-header-label">Provided on Application:</span>
                            <div class="req-header-checkboxes">
                                <span>Y</span>
                                <span>N</span>
                        </div>
                    </div>

                        <div class="requirements-list">
                            <!-- Items 1-9 -->
                            <div class="req-item">
                                <span class="req-number">1.</span>
                                <span class="req-label">Close up 2x2 (2pcs)</span>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_2x2" id="req_2x2_y" value="YO">
                                    <input type="checkbox" name="req_2x2" id="req_2x2_n" value="NO">
                                        </div>
                                        </div>
                            
                            <div class="req-item">
                                <span class="req-number">2.</span>
                                <span class="req-label">NSO, Birth Certificate</span>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_birth_cert" id="req_birth_cert_y" value="YO">
                                    <input type="checkbox" name="req_birth_cert" id="req_birth_cert_n" value="NO">
                                    </div>
                                </div>
                            
                            <div class="req-item">
                                <span class="req-number">3.</span>
                                <span class="req-label">Barangay Clearance</span>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_barangay" id="req_barangay_y" value="YO">
                                    <input type="checkbox" name="req_barangay" id="req_barangay_n" value="NO">
                            </div>
                                        </div>
                            
                            <div class="req-item">
                                <span class="req-number">4.</span>
                                <span class="req-label">Police Clearance (local)</span>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_police" id="req_police_y" value="YO">
                                    <input type="checkbox" name="req_police" id="req_police_n" value="NO">
                                        </div>
                                    </div>
                            
                            <div class="req-item">
                                <span class="req-number">5.</span>
                                <span class="req-label">NBI (for cases purposes)</span>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_nbi" id="req_nbi_y" value="YO">
                                    <input type="checkbox" name="req_nbi" id="req_nbi_n" value="NO">
                                </div>
                            </div>
                            
                            <div class="req-item">
                                <span class="req-number">6.</span>
                                <span class="req-label">D.I. Clearance</span>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_di" id="req_di_y" value="YO">
                                    <input type="checkbox" name="req_di" id="req_di_n" value="NO">
                                        </div>
                                        </div>
                            
                            <div class="req-item">
                                <span class="req-number">7.</span>
                                <span class="req-label">High School / College Diploma</span>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_diploma" id="req_diploma_y" value="YO">
                                    <input type="checkbox" name="req_diploma" id="req_diploma_n" value="NO">
                                    </div>
                                </div>
                            
                            <div class="req-item">
                                <span class="req-number">8.</span>
                                <span class="req-label">Neuro & Drug test result</span>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_neuro_drug" id="req_neuro_drug_y" value="YO">
                                    <input type="checkbox" name="req_neuro_drug" id="req_neuro_drug_n" value="NO">
                            </div>
                                        </div>
                            
                            <div class="req-item">
                                <span class="req-number">9.</span>
                                <span class="req-label">Sec.License Certi. fr. SOSIA</span>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_sec_license" id="req_sec_license_y" value="YO">
                                    <input type="checkbox" name="req_sec_license" id="req_sec_license_n" value="NO">
                                        </div>
                                    </div>
                                </div>
                        
                        <!-- ID Copy Provision Section -->
                        <div class="requirements-header">
                            <span class="req-header-label">I.D. copy provision:</span>
                            </div>
                        
                        <div class="requirements-list">
                            <!-- Items 10-14 with ID number inputs -->
                            <div class="req-item-with-input">
                                <span class="req-number">10.</span>
                                <span class="req-label">Sec. Lic. No.</span>
                                <div class="req-input-wrapper">
                                    <input type="text" class="req-input-underline" id="sec_lic_no" name="sec_lic_no" placeholder="_______________" required>
                                    <div class="invalid-feedback" id="sec_lic_no_error"></div>
                                </div>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_sec_lic_no" id="req_sec_lic_no_y" value="YO">
                                    <input type="checkbox" name="req_sec_lic_no" id="req_sec_lic_no_n" value="NO">
                                        </div>
                                        </div>
                            
                            <div class="req-item-with-input">
                                <span class="req-number">11.</span>
                                <span class="req-label">SSS No.</span>
                                <div class="req-input-wrapper">
                                    <input type="text" class="req-input-underline" id="sss_no_page2" name="sss_no_page2" placeholder="##-#######-#" pattern="\d{2}-\d{7}-\d{1}" maxlength="12" required>
                                    <div class="invalid-feedback" id="sss_no_page2_error"></div>
                                </div>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_sss" id="req_sss_y" value="YO">
                                    <input type="checkbox" name="req_sss" id="req_sss_n" value="NO">
                                    </div>
                                </div>
                            
                            <div class="req-item-with-input">
                                <span class="req-number">12.</span>
                                <span class="req-label">Pag-Ibig No.</span>
                                <div class="req-input-wrapper">
                                    <input type="text" class="req-input-underline" id="pagibig_no_page2" name="pagibig_no_page2" placeholder="####-####-####" pattern="\d{4}-\d{4}-\d{4}" maxlength="14" required>
                                    <div class="invalid-feedback" id="pagibig_no_page2_error"></div>
                                </div>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_pagibig" id="req_pagibig_y" value="YO">
                                    <input type="checkbox" name="req_pagibig" id="req_pagibig_n" value="NO">
                            </div>
                                        </div>
                            
                            <div class="req-item-with-input">
                                <span class="req-number">13.</span>
                                <span class="req-label">PhilHealth No.</span>
                                <div class="req-input-wrapper">
                                    <input type="text" class="req-input-underline" id="philhealth_no_page2" name="philhealth_no_page2" placeholder="##-#########-#" pattern="\d{2}-\d{9}-\d{1}" maxlength="14" required>
                                    <div class="invalid-feedback" id="philhealth_no_page2_error"></div>
                                </div>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_philhealth" id="req_philhealth_y" value="YO">
                                    <input type="checkbox" name="req_philhealth" id="req_philhealth_n" value="NO">
                                        </div>
                                    </div>
                            
                            <div class="req-item-with-input">
                                <span class="req-number">14.</span>
                                <span class="req-label">TIN No.</span>
                                <div class="req-input-wrapper">
                                    <input type="text" class="req-input-underline" id="tin_no_page2" name="tin_no_page2" placeholder="###-###-###-###" pattern="\d{3}-\d{3}-\d{3}-\d{3}" maxlength="15" required>
                                    <div class="invalid-feedback" id="tin_no_page2_error"></div>
                                </div>
                                <div class="req-checkboxes">
                                    <input type="checkbox" name="req_tin" id="req_tin_y" value="YO">
                                    <input type="checkbox" name="req_tin" id="req_tin_n" value="NO">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sworn Statement Section - Formal Legal Affidavit Style -->
                <div class="sworn-statement-section">
                    <!-- Title -->
                    <div class="sworn-title">
                        <h4>SWORN STATEMENT</h4>
                    </div>
                    
                    <!-- Statement Body -->
                    <div class="sworn-body">
                        <p>I HEREBY AUTHORIZE the Company, <strong>GOLDEN Z-5 SECURITY & INVESTIGATION AGENCY, INC.</strong> to conduct further investigation and inquiry as to my personal, past employment and such other related background Information. I hereby release from any and all liabilities all persons, companies, corporations, and institutions supplying any information with respect to my background, character, and employment history. I understand that any misinterpretation or omission of facts found in this application form shall be sufficient be sufficient ground for revocation of my applications and/or summary dismissal of my ????????????</p>
                        
                        <p>I UNDERSTAND that if my application is considered, my appointment will be on a <strong>PROBATIONARY</strong> basis for a period not more than six (6) months to be determined at the discretion of the Company and subject to satisfactory performance. I will abide with all the policies, submit myself freely to disciplinary action, including termination of my employment without any benefits.</p>
                        
                        <p>I HEREBY CERTIFY that all information given in this application form are true and correct and any false statement or misrepresentation shall be a ground for the termination of my employment with the Company without prejudice to the filing of <strong>APPROPRIATE CRIMINAL PROCEEDINGS</strong> by reason thereof.</p>
                    </div>

                    <!-- Signature Line -->
                    <div class="sworn-signature-line">
                        <input type="text" class="sworn-signature-input" id="sworn_signature" name="sworn_signature" placeholder="">
                        <label class="sworn-signature-label">Signature Over Printed Name</label>
                    </div>

                    <!-- Subscription Clause -->
                    <div class="sworn-subscription">
                        <span class="subscription-text">SUBSCRIBED AND SWORN to before me this</span>
                        <input type="text" class="subscription-input" id="sworn_day" name="sworn_day" placeholder="">
                        <span class="subscription-text">day of</span>
                        <input type="text" class="subscription-input subscription-input-wide" id="sworn_month_year" name="sworn_month_year" placeholder="">
                        <span class="subscription-text">. Affiant exhibited to me his/her Community Tax Certificate No.</span>
                        <input type="text" class="subscription-input subscription-input-wide" id="tax_cert_no" name="tax_cert_no" placeholder="">
                        <span class="subscription-text">issued at</span>
                        <input type="text" class="subscription-input subscription-input-wide" id="tax_cert_issued_at" name="tax_cert_issued_at" placeholder="">
                        <span class="subscription-text">.</span>
                    </div>

                    <!-- Bottom Section: Notarial Details -->
                    <div class="sworn-footer">
                        <!-- Left: Doc/Page/Book/Series -->
                        <div class="notarial-details-left">
                            <div class="notarial-field">
                                <span class="notarial-label">Doc No.</span>
                                <input type="text" class="notarial-underline" id="doc_no" name="doc_no" placeholder="">
                                <span class="notarial-semicolon">;</span>
                        </div>
                            <div class="notarial-field">
                                <span class="notarial-label">Page No.</span>
                                <input type="text" class="notarial-underline" id="page_no" name="page_no" placeholder="" value="2" readonly>
                                <span class="notarial-semicolon">;</span>
                    </div>
                            <div class="notarial-field">
                                <span class="notarial-label">Book No.</span>
                                <input type="text" class="notarial-underline" id="book_no" name="book_no" placeholder="">
                                <span class="notarial-semicolon">;</span>
                        </div>
                            <div class="notarial-field">
                                <span class="notarial-label">Series of</span>
                                <input type="text" class="notarial-underline" id="series_of" name="series_of" placeholder="">
                                <span class="notarial-semicolon">;</span>
                    </div>
                </div>

                        <!-- Right: Notary Public -->
                        <div class="notarial-details-right">
                            <div class="notary-public-label">NOTARY PUBLIC</div>
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
            
            // Validate Basic Requirements section - All 5 fields are mandatory
            const basicRequirementsFields = [
                {
                    id: 'sec_lic_no',
                    name: 'sec_lic_no',
                    label: 'Security License No.',
                    pattern: /^[A-Z0-9]{2,4}-?[0-9]{4}[0-9]{5,10}$/i,
                    errorMsg: 'Security License No. is required and must be in format PREFIX-YYYY###### or PREFIXYYYY###### (e.g., R03-202210000014, NCR20221025742)'
                },
                {
                    id: 'sss_no_page2',
                    name: 'sss_no_page2',
                    label: 'SSS No.',
                    pattern: /^\d{2}-\d{7}-\d{1}$/,
                    errorMsg: 'SSS No. is required and must be in format ##-#######-# (e.g., 02-1179877-4)'
                },
                {
                    id: 'pagibig_no_page2',
                    name: 'pagibig_no_page2',
                    label: 'Pag-IBIG No.',
                    pattern: /^\d{4}-\d{4}-\d{4}$/,
                    errorMsg: 'Pag-IBIG No. is required and must be in format ####-####-#### (e.g., 1210-9087-6528)'
                },
                {
                    id: 'philhealth_no_page2',
                    name: 'philhealth_no_page2',
                    label: 'PhilHealth No.',
                    pattern: /^\d{2}-\d{9}-\d{1}$/,
                    errorMsg: 'PhilHealth No. is required and must be in format ##-#########-# (e.g., 21-200190443-1)'
                },
                {
                    id: 'tin_no_page2',
                    name: 'tin_no_page2',
                    label: 'TIN No.',
                    pattern: /^\d{3}-\d{3}-\d{3}-\d{3}$/,
                    errorMsg: 'TIN No. is required and must be in format ###-###-###-### (e.g., 360-889-408-000)'
                }
            ];
            
            basicRequirementsFields.forEach(field => {
                const input = document.getElementById(field.id);
                const errorDiv = document.getElementById(field.id + '_error');
                
                if (!input) return;
                
                const value = input.value.trim();
                
                // Check if field is empty
                if (!value || value === '') {
                    isValid = false;
                    errors.push(field.label + ' is required.');
                    input.classList.add('is-invalid');
                    if (errorDiv) {
                        errorDiv.textContent = field.label + ' is required.';
                        errorDiv.style.display = 'block';
                    }
                } else {
                    // Validate format
                    if (!field.pattern.test(value)) {
                        isValid = false;
                        errors.push(field.errorMsg);
                        input.classList.add('is-invalid');
                        if (errorDiv) {
                            errorDiv.textContent = field.errorMsg;
                            errorDiv.style.display = 'block';
                        }
                    } else {
                        // Valid - remove error styling
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                        if (errorDiv) {
                            errorDiv.style.display = 'none';
                        }
                    }
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
                    setTimeout(() => firstError.focus(), 300);
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
        
        // Real-time validation for Basic Requirements fields
        const basicRequirementsFields = [
            {
                id: 'sec_lic_no',
                pattern: /^[A-Z0-9]{2,4}-?[0-9]{4}[0-9]{5,10}$/i,
                errorMsg: 'Security License No. must be in format PREFIX-YYYY###### or PREFIXYYYY###### (e.g., R03-202210000014, NCR20221025742)'
            },
            {
                id: 'sss_no_page2',
                pattern: /^\d{2}-\d{7}-\d{1}$/,
                errorMsg: 'SSS No. must be in format ##-#######-# (e.g., 02-1179877-4)'
            },
            {
                id: 'pagibig_no_page2',
                pattern: /^\d{4}-\d{4}-\d{4}$/,
                errorMsg: 'Pag-IBIG No. must be in format ####-####-#### (e.g., 1210-9087-6528)'
            },
            {
                id: 'philhealth_no_page2',
                pattern: /^\d{2}-\d{9}-\d{1}$/,
                errorMsg: 'PhilHealth No. must be in format ##-#########-# (e.g., 21-200190443-1)'
            },
            {
                id: 'tin_no_page2',
                pattern: /^\d{3}-\d{3}-\d{3}-\d{3}$/,
                errorMsg: 'TIN No. must be in format ###-###-###-### (e.g., 360-889-408-000)'
            }
        ];
        
        // Auto-format inputs with dashes
        const formatInput = (input, pattern) => {
            let value = input.value.replace(/[^0-9A-Za-z-]/g, ''); // Remove invalid characters
            
            // Auto-format based on field type
            if (input.id === 'sss_no_page2') {
                // Format: ##-#######-#
                value = value.replace(/\D/g, '');
                if (value.length > 2) value = value.slice(0, 2) + '-' + value.slice(2);
                if (value.length > 10) value = value.slice(0, 10) + '-' + value.slice(10);
                if (value.length > 12) value = value.slice(0, 12);
            } else if (input.id === 'pagibig_no_page2') {
                // Format: ####-####-####
                value = value.replace(/\D/g, '');
                if (value.length > 4) value = value.slice(0, 4) + '-' + value.slice(4);
                if (value.length > 9) value = value.slice(0, 9) + '-' + value.slice(9);
                if (value.length > 14) value = value.slice(0, 14);
            } else if (input.id === 'philhealth_no_page2') {
                // Format: ##-#########-#
                value = value.replace(/\D/g, '');
                if (value.length > 2) value = value.slice(0, 2) + '-' + value.slice(2);
                if (value.length > 11) value = value.slice(0, 11) + '-' + value.slice(11);
                if (value.length > 14) value = value.slice(0, 14);
            } else if (input.id === 'tin_no_page2') {
                // Format: ###-###-###-###
                value = value.replace(/\D/g, '');
                if (value.length > 3) value = value.slice(0, 3) + '-' + value.slice(3);
                if (value.length > 7) value = value.slice(0, 7) + '-' + value.slice(7);
                if (value.length > 11) value = value.slice(0, 11) + '-' + value.slice(11);
                if (value.length > 15) value = value.slice(0, 15);
            }
            
            input.value = value;
        };
        
        basicRequirementsFields.forEach(fieldConfig => {
            const input = document.getElementById(fieldConfig.id);
            const errorDiv = document.getElementById(fieldConfig.id + '_error');
            
            if (!input) return;
            
            // Validate on blur
            input.addEventListener('blur', function() {
                const value = this.value.trim();
                const fieldLabel = fieldConfig.id === 'sec_lic_no' ? 'Security License No.' :
                                  fieldConfig.id === 'sss_no_page2' ? 'SSS No.' :
                                  fieldConfig.id === 'pagibig_no_page2' ? 'Pag-IBIG No.' :
                                  fieldConfig.id === 'philhealth_no_page2' ? 'PhilHealth No.' :
                                  fieldConfig.id === 'tin_no_page2' ? 'TIN No.' : 'This field';
                
                if (!value || value === '') {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    if (errorDiv) {
                        errorDiv.textContent = fieldLabel + ' is required.';
                        errorDiv.style.display = 'block';
                    }
                } else if (!fieldConfig.pattern.test(value)) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    if (errorDiv) {
                        errorDiv.textContent = fieldConfig.errorMsg;
                        errorDiv.style.display = 'block';
                    }
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                    }
                }
            });
            
            // Auto-format on input (for fields with dashes)
            if (fieldConfig.id !== 'sec_lic_no') {
                input.addEventListener('input', function() {
                    formatInput(this, fieldConfig.pattern);
                });
            }
            
            // Clear validation on input (for Security License - allow free typing)
            if (fieldConfig.id === 'sec_lic_no') {
                input.addEventListener('input', function() {
                    // Convert to uppercase
                    this.value = this.value.toUpperCase();
                    // Remove validation classes while typing
                    this.classList.remove('is-invalid', 'is-valid');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                    }
                });
            }
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
