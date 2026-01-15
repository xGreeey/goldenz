<?php
$page_title = 'Add New Employee - Golden Z-5 HR System';
$page = 'add_employee';

// Check for success message from session
$show_success_popup = false;
$success_message = '';
if (isset($_SESSION['employee_created_success']) && $_SESSION['employee_created_success']) {
    $show_success_popup = true;
    $success_message = $_SESSION['employee_created_message'] ?? 'Employee created successfully!';
    $created_employee_id = $_SESSION['employee_created_id'] ?? null;
    // Clear the session variables
    unset($_SESSION['employee_created_success']);
    unset($_SESSION['employee_created_message']);
    unset($_SESSION['employee_created_id']);
}

// Get logged-in user information
// Try to get from session, or use default system user
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1; // Default to user ID 1 (admin)
$current_user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'System Administrator';

// Try to get user name from database if we have user_id
if ($current_user_id && function_exists('get_db_connection')) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT name, username FROM users WHERE id = ?");
        $stmt->execute([$current_user_id]);
        $user = $stmt->fetch();
        if ($user) {
            $current_user_name = $user['name'] ?? $user['username'] ?? $current_user_name;
        }
    } catch (Exception $e) {
        // Use default if database query fails
    }
}

// Handle form submission
// Note: This page is included, so we can't use header() redirects after output starts
// Instead, we'll use session variables and show a popup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'])) {
    $errors = [];
    $success = false;

    // Auto-generate Employee Number (chronological) if missing
    // This overrides the previous random generator so records stay sequential.
    if (empty($_POST['employee_no'])) {
        try {
            if (function_exists('get_db_connection')) {
                $pdo = get_db_connection();
                $stmt = $pdo->query("SELECT MAX(employee_no) AS max_no FROM employees");
                $row = $stmt ? $stmt->fetch() : null;
                $maxNo = isset($row['max_no']) ? (int)$row['max_no'] : 0;
                $_POST['employee_no'] = (string)max(1, $maxNo + 1);
            }
        } catch (Exception $e) {
            // If DB is unavailable, leave as-is so validation can report it
        }
    }
    
    // Validate required fields
    $required_fields = ['first_name', 'surname', 'employee_no', 'employee_type', 'post', 'date_hired', 'status'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    
    // Validate email format if provided
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Validate phone format if provided
    // The hidden field cp_number should contain just the number part (9XXXXXXXXX)
    if (!empty($_POST['cp_number'])) {
        // Remove any non-digit characters for validation
        $clean_cp = preg_replace('/[^0-9]/', '', $_POST['cp_number']);
        
        // Log for debugging
        if (function_exists('log_db_error')) {
            log_db_error('phone_validation', 'Validating contact phone number', [
                'original' => $_POST['cp_number'],
                'cleaned' => $clean_cp,
                'length' => strlen($clean_cp),
                'matches_pattern' => preg_match('/^9\d{9}$/', $clean_cp) ? 'yes' : 'no'
            ]);
        }
        
        // Check if it's exactly 10 digits starting with 9
        if (strlen($clean_cp) !== 10 || !preg_match('/^9\d{9}$/', $clean_cp)) {
            $errors[] = 'Contact phone number must be a valid Philippine mobile number (9XXXXXXXXX). Received: ' . substr($clean_cp, 0, 20);
        }
    }

    // Validate contact person number format
    if (!empty($_POST['contact_person_number'])) {
        // Remove any non-digit characters for validation
        $clean_contact = preg_replace('/[^0-9]/', '', $_POST['contact_person_number']);
        
        // Log for debugging
        if (function_exists('log_db_error')) {
            log_db_error('phone_validation', 'Validating emergency contact number', [
                'original' => $_POST['contact_person_number'],
                'cleaned' => $clean_contact,
                'length' => strlen($clean_contact),
                'matches_pattern' => preg_match('/^9\d{9}$/', $clean_contact) ? 'yes' : 'no'
            ]);
        }
        
        // Check if it's exactly 10 digits starting with 9
        if (strlen($clean_contact) !== 10 || !preg_match('/^9\d{9}$/', $clean_contact)) {
            $errors[] = 'Emergency contact number must be a valid Philippine mobile number (9XXXXXXXXX). Received: ' . substr($clean_contact, 0, 20);
        }
    }

    // Validate employee_no (should be numeric)
    if (!empty($_POST['employee_no']) && !preg_match('/^\d+$/', $_POST['employee_no'])) {
        $errors[] = 'Employee number must contain numbers only.';
    }

    // Validate government ID formats (they can have dashes)
    // SSS: ##-#######-#
    if (!empty($_POST['sss_no']) && !preg_match('/^\d{2}-\d{7}-\d{1}$/', $_POST['sss_no'])) {
        $errors[] = 'SSS number must be in format ##-#######-# (e.g., 02-1179877-4).';
    }
    
    // PAG-IBIG: ####-####-####
    if (!empty($_POST['pagibig_no']) && !preg_match('/^\d{4}-\d{4}-\d{4}$/', $_POST['pagibig_no'])) {
        $errors[] = 'PAG-IBIG number must be in format ####-####-#### (e.g., 1210-9087-6528).';
    }
    
    // TIN: ###-###-###-###
    if (!empty($_POST['tin_number']) && !preg_match('/^\d{3}-\d{3}-\d{3}-\d{3}$/', $_POST['tin_number'])) {
        $errors[] = 'TIN number must be in format ###-###-###-### (e.g., 360-889-408-000).';
    }
    
    // PhilHealth: ##-#########-#
    if (!empty($_POST['philhealth_no']) && !preg_match('/^\d{2}-\d{9}-\d{1}$/', $_POST['philhealth_no'])) {
        $errors[] = 'PhilHealth number must be in format ##-#########-# (e.g., 21-200190443-1).';
    }
    
    // License number can contain letters, numbers, and hyphens (e.g., R03-202210000014, NCR20221025742)
    if (!empty($_POST['license_no'])) {
        $license_no = strtoupper(trim($_POST['license_no']));
        if (!preg_match('/^[A-Z0-9]{2,4}-?[0-9]{4}[0-9]{5,10}$/', $license_no)) {
            $errors[] = 'License number format is invalid. Expected format: PREFIX-YYYY###### or PREFIXYYYY###### (e.g., R03-202210000014, NCR20221025742).';
        }
    }

    // Additional Personal Information validations (optional fields)
    $allowed_genders = ['Male', 'Female'];
    $allowed_civil_status = ['Single', 'Married', 'Separated', 'Widower'];

    if (!empty($_POST['gender']) && !in_array($_POST['gender'], $allowed_genders, true)) {
        $errors[] = 'Gender must be Male or Female.';
    }

    if (!empty($_POST['civil_status']) && !in_array($_POST['civil_status'], $allowed_civil_status, true)) {
        $errors[] = 'Civil Status is invalid.';
    }

    if (!empty($_POST['birthplace']) && mb_strlen($_POST['birthplace']) > 150) {
        $errors[] = 'Birthplace must be 150 characters or less.';
    }

    if (!empty($_POST['citizenship']) && mb_strlen($_POST['citizenship']) > 80) {
        $errors[] = 'Citizenship must be 80 characters or less.';
    }

    if (!empty($_POST['provincial_address']) && mb_strlen($_POST['provincial_address']) > 255) {
        $errors[] = 'Provincial Address must be 255 characters or less.';
    }

    foreach (['age','spouse_age','father_age','mother_age'] as $ageField) {
        if (isset($_POST[$ageField]) && $_POST[$ageField] !== '') {
            if (!preg_match('/^\d{1,3}$/', (string)$_POST[$ageField])) {
                $errors[] = ucfirst(str_replace('_', ' ', $ageField)) . ' must be a number.';
            } else {
                $ageVal = (int)$_POST[$ageField];
                if ($ageVal < 0 || $ageVal > 120) {
                    $errors[] = ucfirst(str_replace('_', ' ', $ageField)) . ' must be between 0 and 120.';
                }
            }
        }
    }

    // Education years covered validation (YYYY - YYYY), optional
    $education_year_fields = [
        'college_years' => 'College Years Covered',
        'vocational_years' => 'Vocational Years Covered',
        'highschool_years' => 'High School Years Covered',
        'elementary_years' => 'Elementary Years Covered',
    ];

    foreach ($education_year_fields as $field => $label) {
        if (!empty($_POST[$field])) {
            $raw = trim((string)$_POST[$field]);
            $normalized = str_replace('–', '-', $raw); // allow en-dash from copy/paste
            $normalized = preg_replace('/\s+/', ' ', $normalized);

            if (!preg_match('/^\d{4}(?:\s*-\s*\d{4})?$/', $normalized)) {
                $errors[] = $label . ' must be in format YYYY - YYYY (e.g., 2018 - 2022).';
                continue;
            }

            // If a range is provided, validate bounds
            if (strpos($normalized, '-') !== false) {
                [$y1, $y2] = array_map('trim', explode('-', $normalized, 2));
                $start = (int)$y1;
                $end = (int)$y2;
                $maxYear = (int)date('Y') + 1;
                if ($start < 1900 || $end < 1900 || $start > $maxYear || $end > $maxYear || $start > $end) {
                    $errors[] = $label . ' year range is invalid.';
                }
            } else {
                $year = (int)$normalized;
                $maxYear = (int)date('Y') + 1;
                if ($year < 1900 || $year > $maxYear) {
                    $errors[] = $label . ' year is invalid.';
                }
            }
        }
    }

    // Trainings validation (optional)
    $trainings_in = $_POST['trainings'] ?? [];
    if (is_array($trainings_in)) {
        foreach ($trainings_in as $idx => $t) {
            if (!is_array($t)) continue;
            $title = trim((string)($t['title'] ?? ''));
            $by = trim((string)($t['by'] ?? ''));
            $date = trim((string)($t['date'] ?? ''));

            $hasAny = ($title !== '' || $by !== '' || $date !== '');
            if (!$hasAny) continue;

            if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $errors[] = 'Training date must be a valid date.';
            }
        }
    }

    // Government exam validation (optional)
    $govTaken = ($_POST['gov_exam_taken'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
    $govExamsIn = $_POST['gov_exams'] ?? [];
    if ($govTaken === 'Yes' && is_array($govExamsIn)) {
        foreach ($govExamsIn as $e) {
            if (!is_array($e)) continue;
            $type = trim((string)($e['type'] ?? ''));
            $score = trim((string)($e['score'] ?? ''));
            $hasAny = ($type !== '' || $score !== '');
            if (!$hasAny) continue;
            // If any provided, require type at minimum
            if ($type === '') {
                $errors[] = 'Government examination type is required when adding an exam record.';
            }
            if (mb_strlen($score) > 20) {
                $errors[] = 'Government examination score/rating is too long.';
            }
        }
    }

    // Employment history validation (optional, repeatable)
    $jobsIn = $_POST['employment_history'] ?? [];
    if (is_array($jobsIn)) {
        foreach ($jobsIn as $j) {
            if (!is_array($j)) continue;
            $position = trim((string)($j['position'] ?? ''));
            $company_name = trim((string)($j['company_name'] ?? ''));
            $company_address = trim((string)($j['company_address'] ?? ''));
            $company_phone = trim((string)($j['company_phone'] ?? ''));
            $period = trim((string)($j['period'] ?? ''));
            $reason = trim((string)($j['reason'] ?? ''));

            $hasAny = ($position !== '' || $company_name !== '' || $company_address !== '' || $company_phone !== '' || $period !== '' || $reason !== '');
            if (!$hasAny) continue;

            if ($company_phone !== '' && mb_strlen($company_phone) > 30) {
                $errors[] = 'Company Phone Number is too long.';
            }

            if ($period !== '') {
                $raw = str_replace('–', '-', $period);
                $raw = preg_replace('/\s+/', ' ', trim($raw));
                // Accept MM/YYYY - MM/YYYY
                if (!preg_match('/^(0[1-9]|1[0-2])\/\d{4}\s*-\s*(0[1-9]|1[0-2])\/\d{4}$/', $raw)) {
                    $errors[] = 'Employment Period Covered must be in format MM/YYYY - MM/YYYY (e.g., 03/2022 - 11/2024).';
                } else {
                    [$a, $b] = array_map('trim', explode('-', $raw, 2));
                    [$am, $ay] = explode('/', $a);
                    [$bm, $by] = explode('/', $b);
                    $start = ((int)$ay) * 12 + ((int)$am);
                    $end = ((int)$by) * 12 + ((int)$bm);
                    if ($start > $end) {
                        $errors[] = 'Employment period range is invalid (start is after end).';
                    }
                }
            }
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            // Log the attempt
            if (function_exists('log_db_error')) {
                log_db_error('add_employee_page', 'Starting employee creation from form', [
                    'post_data' => $_POST,
                    'files' => isset($_FILES['employee_photo']) ? ['employee_photo' => ['name' => $_FILES['employee_photo']['name'], 'size' => $_FILES['employee_photo']['size'], 'error' => $_FILES['employee_photo']['error']]] : []
                ]);
            }
            
            // Prepare employee data with all fields from database
            $employee_data = [
                'employee_no' => (int)$_POST['employee_no'], // Cast to integer as per database structure
                'employee_type' => strtoupper(trim($_POST['employee_type'])), // Ensure uppercase
                'surname' => strtoupper(trim($_POST['surname'])), // Ensure uppercase
                'first_name' => strtoupper(trim($_POST['first_name'])), // Ensure uppercase
                'middle_name' => !empty($_POST['middle_name']) ? strtoupper(trim($_POST['middle_name'])) : null,
                'post' => strtoupper(trim($_POST['post'])), // Ensure uppercase
                'license_no' => !empty($_POST['license_no']) ? strtoupper(trim($_POST['license_no'])) : null,
                'license_exp_date' => !empty($_POST['license_exp_date']) ? $_POST['license_exp_date'] : null,
                'rlm_exp' => !empty($_POST['rlm_exp']) ? trim($_POST['rlm_exp']) : null,
                'date_hired' => $_POST['date_hired'],
                'cp_number' => !empty($_POST['cp_number']) ? preg_replace('/[^0-9]/', '', $_POST['cp_number']) : null,
                'sss_no' => !empty($_POST['sss_no']) ? trim($_POST['sss_no']) : null,
                'pagibig_no' => !empty($_POST['pagibig_no']) ? trim($_POST['pagibig_no']) : null,
                'tin_number' => !empty($_POST['tin_number']) ? trim($_POST['tin_number']) : null,
                'philhealth_no' => !empty($_POST['philhealth_no']) ? trim($_POST['philhealth_no']) : null,
                'birth_date' => !empty($_POST['birth_date']) ? $_POST['birth_date'] : null,
                'gender' => (!empty($_POST['gender']) && in_array($_POST['gender'], $allowed_genders, true)) ? $_POST['gender'] : null,
                'civil_status' => (!empty($_POST['civil_status']) && in_array($_POST['civil_status'], $allowed_civil_status, true)) ? $_POST['civil_status'] : null,
                'age' => (isset($_POST['age']) && $_POST['age'] !== '' && preg_match('/^\d{1,3}$/', (string)$_POST['age'])) ? (int)$_POST['age'] : null,
                'birthplace' => !empty($_POST['birthplace']) ? strtoupper(trim($_POST['birthplace'])) : null,
                'citizenship' => !empty($_POST['citizenship']) ? strtoupper(trim($_POST['citizenship'])) : null,
                'provincial_address' => !empty($_POST['provincial_address']) ? strtoupper(trim($_POST['provincial_address'])) : null,
                'special_skills' => !empty($_POST['special_skills']) ? trim($_POST['special_skills']) : null,
                'spouse_name' => !empty($_POST['spouse_name']) ? strtoupper(trim($_POST['spouse_name'])) : null,
                'spouse_age' => (isset($_POST['spouse_age']) && $_POST['spouse_age'] !== '' && preg_match('/^\d{1,3}$/', (string)$_POST['spouse_age'])) ? (int)$_POST['spouse_age'] : null,
                'spouse_occupation' => !empty($_POST['spouse_occupation']) ? strtoupper(trim($_POST['spouse_occupation'])) : null,
                'father_name' => !empty($_POST['father_name']) ? strtoupper(trim($_POST['father_name'])) : null,
                'father_age' => (isset($_POST['father_age']) && $_POST['father_age'] !== '' && preg_match('/^\d{1,3}$/', (string)$_POST['father_age'])) ? (int)$_POST['father_age'] : null,
                'father_occupation' => !empty($_POST['father_occupation']) ? strtoupper(trim($_POST['father_occupation'])) : null,
                'mother_name' => !empty($_POST['mother_name']) ? strtoupper(trim($_POST['mother_name'])) : null,
                'mother_age' => (isset($_POST['mother_age']) && $_POST['mother_age'] !== '' && preg_match('/^\d{1,3}$/', (string)$_POST['mother_age'])) ? (int)$_POST['mother_age'] : null,
                'mother_occupation' => !empty($_POST['mother_occupation']) ? strtoupper(trim($_POST['mother_occupation'])) : null,
                'children_names' => !empty($_POST['children_names']) ? trim($_POST['children_names']) : null,
                'college_course' => !empty($_POST['college_course']) ? strtoupper(trim($_POST['college_course'])) : null,
                'college_school_name' => !empty($_POST['college_school_name']) ? strtoupper(trim($_POST['college_school_name'])) : null,
                'college_school_address' => !empty($_POST['college_school_address']) ? strtoupper(trim($_POST['college_school_address'])) : null,
                'college_years' => !empty($_POST['college_years']) ? trim(str_replace('–', '-', $_POST['college_years'])) : null,
                'vocational_course' => !empty($_POST['vocational_course']) ? strtoupper(trim($_POST['vocational_course'])) : null,
                'vocational_school_name' => !empty($_POST['vocational_school_name']) ? strtoupper(trim($_POST['vocational_school_name'])) : null,
                'vocational_school_address' => !empty($_POST['vocational_school_address']) ? strtoupper(trim($_POST['vocational_school_address'])) : null,
                'vocational_years' => !empty($_POST['vocational_years']) ? trim(str_replace('–', '-', $_POST['vocational_years'])) : null,
                'highschool_school_name' => !empty($_POST['highschool_school_name']) ? strtoupper(trim($_POST['highschool_school_name'])) : null,
                'highschool_school_address' => !empty($_POST['highschool_school_address']) ? strtoupper(trim($_POST['highschool_school_address'])) : null,
                'highschool_years' => !empty($_POST['highschool_years']) ? trim(str_replace('–', '-', $_POST['highschool_years'])) : null,
                'elementary_school_name' => !empty($_POST['elementary_school_name']) ? strtoupper(trim($_POST['elementary_school_name'])) : null,
                'elementary_school_address' => !empty($_POST['elementary_school_address']) ? strtoupper(trim($_POST['elementary_school_address'])) : null,
                'elementary_years' => !empty($_POST['elementary_years']) ? trim(str_replace('–', '-', $_POST['elementary_years'])) : null,
                // Trainings / Exams (stored as JSON)
                'trainings_json' => (function () {
                    $trainings = $_POST['trainings'] ?? [];
                    if (!is_array($trainings)) return null;
                    $out = [];
                    foreach ($trainings as $t) {
                        if (!is_array($t)) continue;
                        $title = trim((string)($t['title'] ?? ''));
                        $by = trim((string)($t['by'] ?? ''));
                        $date = trim((string)($t['date'] ?? ''));
                        if ($title === '' && $by === '' && $date === '') continue;
                        $out[] = [
                            'title' => strtoupper($title),
                            'by' => strtoupper($by),
                            'date' => $date
                        ];
                    }
                    return empty($out) ? null : json_encode($out, JSON_UNESCAPED_UNICODE);
                })(),
                'gov_exam_taken' => (($_POST['gov_exam_taken'] ?? 'No') === 'Yes') ? 1 : 0,
                'gov_exam_json' => (function () {
                    if (($_POST['gov_exam_taken'] ?? 'No') !== 'Yes') return null;
                    $exams = $_POST['gov_exams'] ?? [];
                    if (!is_array($exams)) return null;
                    $out = [];
                    foreach ($exams as $e) {
                        if (!is_array($e)) continue;
                        $type = trim((string)($e['type'] ?? ''));
                        $score = trim((string)($e['score'] ?? ''));
                        if ($type === '' && $score === '') continue;
                        $out[] = [
                            'type' => strtoupper($type),
                            'score' => $score
                        ];
                    }
                    return empty($out) ? null : json_encode($out, JSON_UNESCAPED_UNICODE);
                })(),
                'employment_history_json' => (function () {
                    $jobs = $_POST['employment_history'] ?? [];
                    if (!is_array($jobs)) return null;
                    $out = [];
                    foreach ($jobs as $j) {
                        if (!is_array($j)) continue;
                        $position = trim((string)($j['position'] ?? ''));
                        $company_name = trim((string)($j['company_name'] ?? ''));
                        $company_address = trim((string)($j['company_address'] ?? ''));
                        $company_phone = trim((string)($j['company_phone'] ?? ''));
                        $period = trim((string)($j['period'] ?? ''));
                        $reason = trim((string)($j['reason'] ?? ''));
                        if ($position === '' && $company_name === '' && $company_address === '' && $company_phone === '' && $period === '' && $reason === '') continue;
                        $out[] = [
                            'position' => strtoupper($position),
                            'company_name' => strtoupper($company_name),
                            'company_address' => strtoupper($company_address),
                            'company_phone' => $company_phone,
                            'period' => str_replace('–', '-', $period),
                            'reason' => $reason,
                        ];
                    }
                    return empty($out) ? null : json_encode($out, JSON_UNESCAPED_UNICODE);
                })(),
                'height' => !empty($_POST['height']) ? trim($_POST['height']) : null,
                'weight' => !empty($_POST['weight']) ? trim($_POST['weight']) : null,
                'address' => !empty($_POST['address']) ? strtoupper(trim($_POST['address'])) : null,
                'contact_person' => !empty($_POST['contact_person']) ? strtoupper(trim($_POST['contact_person'])) : null,
                'relationship' => !empty($_POST['relationship']) ? trim($_POST['relationship']) : null,
                'contact_person_address' => !empty($_POST['contact_person_address']) ? strtoupper(trim($_POST['contact_person_address'])) : null,
                'contact_person_number' => !empty($_POST['contact_person_number']) ? preg_replace('/[^0-9]/', '', $_POST['contact_person_number']) : null,
                'blood_type' => !empty($_POST['blood_type']) ? trim($_POST['blood_type']) : null,
                'religion' => !empty($_POST['religion']) ? trim($_POST['religion']) : null,
                'status' => $_POST['status'],
                'created_by' => $current_user_id,
                'created_by_name' => $current_user_name
            ];
            
            // Use the add_employee function from database.php
            if (function_exists('log_db_error')) {
                log_db_error('add_employee_page', 'Calling add_employee function', ['employee_data' => $employee_data]);
            }
            
            $result = add_employee($employee_data);
            
            if (function_exists('log_db_error')) {
                log_db_error('add_employee_page', 'add_employee function returned', ['result' => $result ? 'true' : 'false']);
            }
            
            if ($result) {
                // Get the newly created employee ID
                $pdo = get_db_connection();
                $new_employee_id = $pdo->lastInsertId();
                
                // Verify that we got a valid employee ID
                if ($new_employee_id > 0) {
                
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
                        // Use .jpg for jpeg files, .png for png files
                        $extension = strtolower($extension);
                        if ($extension === 'jpeg') {
                            $extension = 'jpg';
                        }
                        $filename = $new_employee_id . '.' . $extension;
                        $target_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $target_path)) {
                            // Update profile_image path in database
                            try {
                                // Check if profile_image column exists
                                $check_sql = "SHOW COLUMNS FROM employees LIKE 'profile_image'";
                                $check_stmt = $pdo->query($check_sql);
                                if ($check_stmt->rowCount() > 0) {
                                    // Store relative path from project root
                                    $image_path = 'uploads/employees/' . $filename;
                                    $update_sql = "UPDATE employees SET profile_image = ? WHERE id = ?";
                                    $update_stmt = $pdo->prepare($update_sql);
                                    $update_stmt->execute([$image_path, $new_employee_id]);
                                    
                                    // Log success
                                    if (function_exists('log_db_error')) {
                                        log_db_error('photo_upload', 'Profile image uploaded successfully', [
                                            'employee_id' => $new_employee_id,
                                            'filename' => $filename,
                                            'path' => $image_path
                                        ]);
                                    }
                                } else {
                                    // Log if column doesn't exist
                                    if (function_exists('log_db_error')) {
                                        log_db_error('photo_upload', 'profile_image column not found in employees table', [
                                            'employee_id' => $new_employee_id,
                                            'filename' => $filename
                                        ]);
                                    }
                                }
                            } catch (Exception $e) {
                                // Log error
                                if (function_exists('log_db_error')) {
                                    log_db_error('photo_upload', 'Error updating profile_image in database', [
                                        'employee_id' => $new_employee_id,
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                }
                                error_log('Error updating profile_image: ' . $e->getMessage());
                            }
                        } else {
                            // Log file move error
                            if (function_exists('log_db_error')) {
                                log_db_error('photo_upload', 'Failed to move uploaded file', [
                                    'employee_id' => $new_employee_id,
                                    'tmp_name' => $file['tmp_name'],
                                    'target_path' => $target_path
                                ]);
                            }
                        }
                    } else {
                        // Log validation error
                        if (function_exists('log_db_error')) {
                            log_db_error('photo_upload', 'File validation failed', [
                                'employee_id' => $new_employee_id,
                                'file_type' => $file['type'],
                                'file_size' => $file['size'],
                                'allowed_types' => $allowed_types,
                                'max_size' => $max_size
                            ]);
                        }
                    }
                } else {
                    // Log if no file or upload error
                    if (isset($_FILES['employee_photo'])) {
                        $upload_error = $_FILES['employee_photo']['error'];
                        if (function_exists('log_db_error')) {
                            log_db_error('photo_upload', 'File upload error', [
                                'employee_id' => $new_employee_id,
                                'error_code' => $upload_error,
                                'error_message' => $upload_error === UPLOAD_ERR_OK ? 'No error' : 'Upload error occurred'
                            ]);
                        }
                    }
                }
                
                // Log the action
                if (function_exists('log_security_event')) {
                    log_security_event('Employee Created', "Employee: {$_POST['first_name']} {$_POST['surname']} (ID: {$_POST['employee_no']}) created by {$current_user_name}");
                }
                
                // Log to audit trail
                if (function_exists('log_audit_event')) {
                    log_audit_event('INSERT', 'employees', $new_employee_id, null, [
                        'employee_no' => $_POST['employee_no'],
                        'first_name' => $_POST['first_name'],
                        'surname' => $_POST['surname'],
                        'employee_type' => $_POST['employee_type'],
                        'post' => $_POST['post'],
                        'status' => $_POST['status'],
                        'created_by' => $current_user_name
                    ], $current_user_id);
                }
                
                // Set success flag and message - show popup instead of redirecting
                // Since this page is included, we can't use header() redirects
                // Instead, set session variables and use JavaScript to reload the page
                $success_message = 'Employee created successfully!';
                
                // Store success info in session
                $_SESSION['employee_created_success'] = true;
                $_SESSION['employee_created_message'] = $success_message;
                $_SESSION['employee_created_id'] = $new_employee_id;
                
                // Build redirect URL using JavaScript since headers may already be sent
                $current_url = $_SERVER['REQUEST_URI'] ?? '';
                // Remove existing query parameters and rebuild
                $base_url = strtok($current_url, '?');
                if (empty($base_url)) {
                    $base_url = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
                }
                $redirect_url = $base_url . '?page=add_employee&success=1';
                
                // Log the redirect for debugging
                if (function_exists('log_db_error')) {
                    log_db_error('employee_created', 'Employee created successfully, redirecting', [
                        'employee_id' => $new_employee_id,
                        'redirect_url' => $redirect_url
                    ]);
                }
                
                // Since this page is included after HTML headers are sent,
                // we need to use JavaScript redirect
                // Store redirect URL in a way that can be accessed by JavaScript
                $_SESSION['redirect_after_employee'] = $redirect_url;
                
                // Output minimal redirect script
                // This will be output before the page content, so we need to handle it carefully
                // The best approach is to set a flag and handle redirect in the page itself
                
                // Instead of redirecting here, we'll set a flag and let the page handle it
                // This prevents breaking the page structure
                $needs_redirect = true;
                $redirect_url_final = $redirect_url;
                
                // We'll output the redirect script at the very beginning of the page output
                // But first, let's prevent any further processing
                // Actually, let's just use a simple approach: output script tag and exit
                
                // Clear any output buffer
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Output redirect script
                echo '<script type="text/javascript">';
                echo 'window.location.replace(' . json_encode($redirect_url) . ');';
                echo '</script>';
                echo '<noscript>';
                echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url, ENT_QUOTES) . '">';
                echo '</noscript>';
                
                // Flush and exit
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                exit;
                } else {
                    $errors[] = 'Employee was created but could not retrieve the employee ID.';
                }
            } else {
                // Log the failure
                if (function_exists('log_db_error')) {
                    log_db_error('add_employee_page', 'add_employee returned false - employee creation failed', [
                        'employee_data' => $employee_data,
                        'employee_no' => $employee_data['employee_no'] ?? 'unknown'
                    ]);
                }
                $errors[] = 'Failed to create employee. Please check all required fields and try again. Check error.log for details.';
            }
        } catch (Exception $e) {
            // Log the exception
            if (function_exists('log_db_error')) {
                log_db_error('add_employee_page', 'Exception caught in add_employee page', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'employee_data' => $employee_data ?? []
                ]);
            }
            
            $error_message = 'Database error: ' . $e->getMessage();
            // In development, show more details
            if (defined('DEBUG') && DEBUG) {
                $error_message .= ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine();
            }
            $errors[] = $error_message . ' Check error.log for details.';
            error_log('Add Employee Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        }
    }
}

// Get available posts for dropdown from posts table
$posts = get_posts_for_dropdown();
if (empty($posts)) {
    // Fallback: Use common posts if table is empty
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
?>

<div class="add-employee-container">
    <!-- Page Header (avoid repeating the global \"Add New Employee\" title) -->
    <div class="page-header">
        <div class="page-title">
            <h1>Employee Details</h1>
            <p class="page-subtitle">Create a new employee record in the system</p>
        </div>
        <div class="page-actions">
            <a href="?page=employees" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Employees
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success) && $success): ?>
        <div class="alert alert-success">
            <i class="fas fa-circle-check me-2"></i>
            Employee created successfully!
        </div>
    <?php endif; ?>
    
    <!-- Success Popup Modal - Positioned at Top -->
    <?php if ($show_success_popup): ?>
    <div class="modal fade show" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="false" style="display: block !important; background-color: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1050;">
        <div class="modal-dialog" role="document" style="margin: 2rem auto auto auto; max-width: 500px; position: relative;">
            <div class="modal-content" style="border: none; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <div class="modal-header bg-success text-white" style="border-radius: 8px 8px 0 0; padding: 1rem 1.5rem;">
                    <h5 class="modal-title" id="successModalLabel" style="margin: 0; font-weight: 600;">
                        <i class="fas fa-circle-check me-2"></i>Success!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="closeSuccessModal()" style="margin: 0;"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="text-center mb-3">
                        <i class="fas fa-circle-check text-success" style="font-size: 3rem;"></i>
                    </div>
                    <p class="mb-0 text-center" style="font-size: 1.1rem;"><?php echo htmlspecialchars($success_message); ?></p>
                    <?php if ($created_employee_id): ?>
                        <p class="text-muted small mt-2 mb-0 text-center">Employee ID: <strong><?php echo htmlspecialchars($created_employee_id); ?></strong></p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 1rem 1.5rem; border-radius: 0 0 8px 8px;">
                    <button type="button" class="btn btn-secondary" onclick="closeSuccessModal()">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                    <a href="?page=employees" class="btn btn-primary">
                        <i class="fas fa-list me-2"></i>View All Employees
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.remove();
                }, 300);
            }
        }
        
        // Reset form after successful creation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('addEmployeeForm');
            if (form && <?php echo $show_success_popup ? 'true' : 'false'; ?>) {
                // Reset the form
                form.reset();
                // Clear any validation classes
                form.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
                    el.classList.remove('is-invalid', 'is-valid');
                });
                // Reset photo preview
                const photoPreview = document.getElementById('photo_preview');
                const photoPreviewImg = document.getElementById('photo_preview_img');
                if (photoPreview) photoPreview.style.display = 'flex';
                if (photoPreviewImg) photoPreviewImg.style.display = 'none';
            }
        });
        
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSuccessModal();
            }
        });
        
        // Close on backdrop click
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('successModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeSuccessModal();
                    }
                });
            }
        });
    </script>
    <?php endif; ?>

    <?php if (($_SESSION['user_role'] ?? '') === 'hr_admin'): ?>
    <div class="container-fluid hrdash mb-3">
        <div class="hrdash-welcome">
            <div class="hrdash-welcome__left">
                <h2 class="hrdash-welcome__title">Add New Employee</h2>
                <p class="hrdash-welcome__subtitle">Create and file a new employee record in the Golden Z-5 HR system.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add Employee Document Layout -->
    <div class="add-employee-document-page">
        <div class="add-employee-document-shell">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fas fa-circle-exclamation me-2"></i>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="add-employee-document-header">
                <div class="add-employee-document-title">
                    <h1 class="add-employee-title">New Employee Record</h1>
                    <p class="add-employee-subtitle">Complete this HR personnel sheet to add a new employee to the Golden Z-5 system.</p>
                </div>
                <div class="add-employee-document-meta">
                    <div class="add-employee-meta-item">
                        <span class="add-employee-meta-label">Form</span>
                        <span class="add-employee-meta-value">HR-EMP-001</span>
                    </div>
                    <div class="add-employee-meta-item">
                        <span class="add-employee-meta-label">Date</span>
                        <span class="add-employee-meta-value"><?php echo date('M d, Y'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Add Employee Form -->
            <div class="card add-employee-document-card">
                <div class="card-header add-employee-card-header">
                    <h3 class="mb-0">Employee Information Sheet</h3>
                </div>
                <div class="card-body add-employee-card-body">
                <form method="POST" id="addEmployeeForm" enctype="multipart/form-data" action="?page=add_employee" novalidate>
                <!-- Employee Created By Info -->
                <div class="alert alert-info mb-4">
                    <i class="fas fa-circle-info me-2"></i>
                    <strong>Recorded By:</strong> <?php echo htmlspecialchars($current_user_name); ?> 
                    <?php if ($current_user_id): ?>
                        (User ID: <?php echo $current_user_id; ?>)
                    <?php endif; ?>
                </div>

                <!-- Basic Information Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">Basic Information</h4>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="employee_no" class="form-label">Employee Number <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control numeric-only" 
                                id="employee_no" 
                                name="employee_no" 
                                inputmode="numeric" 
                                pattern="\\d{1,5}"
                                maxlength="5"
                                placeholder="Up to 5 digits" 
                                value="<?php echo htmlspecialchars($_POST['employee_no'] ?? ''); ?>" 
                                required
                            >
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="employee_type" class="form-label">Employee Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="employee_type" name="employee_type" required>
                                <option value="">Select Employee Type</option>
                                <option value="SG" <?php echo (($_POST['employee_type'] ?? '') === 'SG') ? 'selected' : ''; ?>>Security Guard (SG)</option>
                                <option value="LG" <?php echo (($_POST['employee_type'] ?? '') === 'LG') ? 'selected' : ''; ?>>Lady Guard (LG)</option>
                                <option value="SO" <?php echo (($_POST['employee_type'] ?? '') === 'SO') ? 'selected' : ''; ?>>Security Officer (SO)</option>
                            </select>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="Active" <?php echo (($_POST['status'] ?? '') === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo (($_POST['status'] ?? '') === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Terminated" <?php echo (($_POST['status'] ?? '') === 'Terminated') ? 'selected' : ''; ?>>Terminated</option>
                                <option value="Suspended" <?php echo (($_POST['status'] ?? '') === 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>

                        <!-- Photo Upload Section (stacked to avoid shrinking Status select) -->
                        <div class="employee-photo-container mt-2">
                            <label class="form-label d-block">2x2 Photo</label>
                            <div class="employee-photo-wrapper mb-2">
                                <div class="employee-photo-placeholder" id="photo_preview">
                                    <span class="photo-placeholder-text">2X2 PHOTO</span>
                                </div>
                                <img id="photo_preview_img" src="" alt="Preview" class="employee-photo-img" style="display: none;">
                            </div>
                            <input type="file" class="form-control form-control-sm" id="employee_photo" name="employee_photo" accept="image/jpeg,image/jpg,image/png" onchange="previewPhoto(this)">
                            <small class="form-text text-muted">JPG or PNG, max 2MB</small>
                        </div>
                    </div>
                </div>

                <!-- Personal Information Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">Personal Information</h4>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="surname" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="surname" name="surname" maxlength="50"
                                   value="<?php echo htmlspecialchars($_POST['surname'] ?? ''); ?>" required>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="first_name" name="first_name" maxlength="50"
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" class="form-control text-uppercase" id="middle_name" name="middle_name" maxlength="50"
                                   value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="birth_date" class="form-label">Birth Date</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                   value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>">
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" min="0" max="120" step="1"
                                   value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" placeholder="0" aria-label="Age">
                            <small class="form-text text-muted">Auto-calculated from Birth Date.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label d-block">Gender</label>
                            <?php $selGender = $_POST['gender'] ?? ''; ?>
                            <div class="d-flex gap-4 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_male" value="Male" <?php echo ($selGender === 'Male') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="gender_male">Male</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_female" value="Female" <?php echo ($selGender === 'Female') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="gender_female">Female</label>
                                </div>
                            </div>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="civil_status" class="form-label">Civil Status</label>
                            <?php $selCivil = $_POST['civil_status'] ?? ''; ?>
                            <select class="form-select" id="civil_status" name="civil_status">
                                <option value="">Select</option>
                                <option value="Single" <?php echo ($selCivil === 'Single') ? 'selected' : ''; ?>>Single</option>
                                <option value="Married" <?php echo ($selCivil === 'Married') ? 'selected' : ''; ?>>Married</option>
                                <option value="Separated" <?php echo ($selCivil === 'Separated') ? 'selected' : ''; ?>>Separated</option>
                                <option value="Widower" <?php echo ($selCivil === 'Widower') ? 'selected' : ''; ?>>Widower</option>
                            </select>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="birthplace" class="form-label">Birthplace</label>
                            <input type="text" class="form-control text-uppercase" id="birthplace" name="birthplace" maxlength="150"
                                   value="<?php echo htmlspecialchars($_POST['birthplace'] ?? ''); ?>">
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="citizenship" class="form-label">Citizenship</label>
                            <input type="text" class="form-control text-uppercase" id="citizenship" name="citizenship" maxlength="80"
                                   value="<?php echo htmlspecialchars($_POST['citizenship'] ?? ''); ?>">
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="address" class="form-label">City Address</label>
                            <textarea class="form-control text-uppercase" id="address" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="provincial_address" class="form-label">Provincial Address</label>
                            <textarea class="form-control text-uppercase" id="provincial_address" name="provincial_address" rows="2"><?php echo htmlspecialchars($_POST['provincial_address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Height</label>
                            <div class="d-flex gap-2">
                                <input type="number" class="form-control" id="height_ft" min="0" max="9" step="1" placeholder="ft" aria-label="Height feet">
                                <input type="number" class="form-control" id="height_in" min="0" max="11" step="1" placeholder="in" aria-label="Height inches">
                            </div>
                            <input type="hidden" id="height" name="height" value="<?php echo htmlspecialchars($_POST['height'] ?? ''); ?>">
                            <small class="form-text text-muted">Separate feet and inches; stored as 5'7".</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="weight" class="form-label">Weight</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="weight" name="weight" step="0.1" min="0" placeholder="0.0"
                                       value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>">
                                <span class="input-group-text">kg</span>
                            </div>
                            <small class="form-text text-muted">Numbers only; unit is kilograms.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="blood_type" class="form-label">Blood Type</label>
                            <select class="form-select" id="blood_type" name="blood_type" required>
                                <option value="">Select Blood Type</option>
                                <option value="A+" <?php echo (($_POST['blood_type'] ?? '') === 'A+') ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo (($_POST['blood_type'] ?? '') === 'A-') ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo (($_POST['blood_type'] ?? '') === 'B+') ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo (($_POST['blood_type'] ?? '') === 'B-') ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo (($_POST['blood_type'] ?? '') === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo (($_POST['blood_type'] ?? '') === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo (($_POST['blood_type'] ?? '') === 'O+') ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo (($_POST['blood_type'] ?? '') === 'O-') ? 'selected' : ''; ?>>O-</option>
                            </select>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="religion" class="form-label">Religion</label>
                            <select class="form-select" id="religion" name="religion" required>
                                <option value="">Select Religion</option>
                                <?php
                                $religions = [
                                    'Roman Catholic','Iglesia ni Cristo','Aglipayan / Philippine Independent Church',
                                    'Evangelical','Baptist','Methodist','Adventist','Born Again Christian',
                                    'Jehovahs Witness','Lutheran','Pentecostal','Protestant (Other)',
                                    'Muslim','Buddhist','Hindu','Sikh','Taoist',
                                    'Indigenous / Tribal','No Religion','Other'
                                ];
                                $selRel = $_POST['religion'] ?? '';
                                foreach ($religions as $rel):
                                ?>
                                    <option value="<?php echo htmlspecialchars($rel); ?>" <?php echo ($selRel === $rel) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="special_skills" class="form-label">Special Skills / Interests</label>
                            <textarea class="form-control" id="special_skills" name="special_skills" rows="2" maxlength="500"
                                      placeholder="e.g., First Aid, Driving, Computer Literate, Sports"><?php echo htmlspecialchars($_POST['special_skills'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <h5 class="form-section-title">Family Background</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="spouse_name" class="form-label">Name of Spouse</label>
                            <input type="text" class="form-control text-uppercase" id="spouse_name" name="spouse_name" maxlength="150"
                                   value="<?php echo htmlspecialchars($_POST['spouse_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="spouse_age" class="form-label">Spouse Age</label>
                            <input type="number" class="form-control" id="spouse_age" name="spouse_age" min="0" max="120" step="1"
                                   value="<?php echo htmlspecialchars($_POST['spouse_age'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="spouse_occupation" class="form-label">Spouse Occupation</label>
                            <input type="text" class="form-control text-uppercase" id="spouse_occupation" name="spouse_occupation" maxlength="150"
                                   value="<?php echo htmlspecialchars($_POST['spouse_occupation'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="father_name" class="form-label">Name of Father</label>
                            <input type="text" class="form-control text-uppercase" id="father_name" name="father_name" maxlength="150"
                                   value="<?php echo htmlspecialchars($_POST['father_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="father_age" class="form-label">Father Age</label>
                            <input type="number" class="form-control" id="father_age" name="father_age" min="0" max="120" step="1"
                                   value="<?php echo htmlspecialchars($_POST['father_age'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="father_occupation" class="form-label">Father Occupation</label>
                            <input type="text" class="form-control text-uppercase" id="father_occupation" name="father_occupation" maxlength="150"
                                   value="<?php echo htmlspecialchars($_POST['father_occupation'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mother_name" class="form-label">Name of Mother</label>
                            <input type="text" class="form-control text-uppercase" id="mother_name" name="mother_name" maxlength="150"
                                   value="<?php echo htmlspecialchars($_POST['mother_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="mother_age" class="form-label">Mother Age</label>
                            <input type="number" class="form-control" id="mother_age" name="mother_age" min="0" max="120" step="1"
                                   value="<?php echo htmlspecialchars($_POST['mother_age'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="mother_occupation" class="form-label">Mother Occupation</label>
                            <input type="text" class="form-control text-uppercase" id="mother_occupation" name="mother_occupation" maxlength="150"
                                   value="<?php echo htmlspecialchars($_POST['mother_occupation'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="children_names" class="form-label">Name of Children</label>
                            <textarea class="form-control" id="children_names" name="children_names" rows="2" maxlength="800"
                                      placeholder="List children names, one per line"><?php echo htmlspecialchars($_POST['children_names'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Education Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">Education</h4>
                    </div>

                    <!-- College (Optional) -->
                    <div class="col-12">
                        <div class="fw-semibold text-uppercase small mb-2">College (Optional)</div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="college_course" class="form-label">Course</label>
                            <input type="text" class="form-control text-uppercase" id="college_course" name="college_course" maxlength="150"
                                   placeholder="e.g., BS Information Technology"
                                   value="<?php echo htmlspecialchars($_POST['college_course'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="college_school_name" class="form-label">School Name</label>
                            <input type="text" class="form-control text-uppercase" id="college_school_name" name="college_school_name" maxlength="200"
                                   value="<?php echo htmlspecialchars($_POST['college_school_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="college_years" class="form-label">Years Covered</label>
                            <input type="text" class="form-control" id="college_years" name="college_years" maxlength="15" inputmode="numeric"
                                   placeholder="e.g., 2018 - 2022"
                                   value="<?php echo htmlspecialchars($_POST['college_years'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="college_school_address" class="form-label">School Address</label>
                            <input type="text" class="form-control text-uppercase" id="college_school_address" name="college_school_address" maxlength="255"
                                   value="<?php echo htmlspecialchars($_POST['college_school_address'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Vocational (Optional) -->
                    <div class="col-12">
                        <div class="fw-semibold text-uppercase small mb-2 mt-2">Vocational (Optional)</div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vocational_course" class="form-label">Course</label>
                            <input type="text" class="form-control text-uppercase" id="vocational_course" name="vocational_course" maxlength="150"
                                   placeholder="e.g., Automotive Servicing NC II"
                                   value="<?php echo htmlspecialchars($_POST['vocational_course'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vocational_school_name" class="form-label">School Name</label>
                            <input type="text" class="form-control text-uppercase" id="vocational_school_name" name="vocational_school_name" maxlength="200"
                                   value="<?php echo htmlspecialchars($_POST['vocational_school_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vocational_years" class="form-label">Years Covered</label>
                            <input type="text" class="form-control" id="vocational_years" name="vocational_years" maxlength="15" inputmode="numeric"
                                   placeholder="e.g., 2016 - 2017"
                                   value="<?php echo htmlspecialchars($_POST['vocational_years'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="vocational_school_address" class="form-label">School Address</label>
                            <input type="text" class="form-control text-uppercase" id="vocational_school_address" name="vocational_school_address" maxlength="255"
                                   value="<?php echo htmlspecialchars($_POST['vocational_school_address'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- High School -->
                    <div class="col-12">
                        <div class="fw-semibold text-uppercase small mb-2 mt-2">High School</div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="highschool_school_name" class="form-label">School Name</label>
                            <input type="text" class="form-control text-uppercase" id="highschool_school_name" name="highschool_school_name" maxlength="200"
                                   value="<?php echo htmlspecialchars($_POST['highschool_school_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="highschool_years" class="form-label">Years Covered</label>
                            <input type="text" class="form-control" id="highschool_years" name="highschool_years" maxlength="15" inputmode="numeric"
                                   placeholder="e.g., 2012 - 2016"
                                   value="<?php echo htmlspecialchars($_POST['highschool_years'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="highschool_school_address" class="form-label">School Address</label>
                            <input type="text" class="form-control text-uppercase" id="highschool_school_address" name="highschool_school_address" maxlength="255"
                                   value="<?php echo htmlspecialchars($_POST['highschool_school_address'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Elementary -->
                    <div class="col-12">
                        <div class="fw-semibold text-uppercase small mb-2 mt-2">Elementary</div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="elementary_school_name" class="form-label">School Name</label>
                            <input type="text" class="form-control text-uppercase" id="elementary_school_name" name="elementary_school_name" maxlength="200"
                                   value="<?php echo htmlspecialchars($_POST['elementary_school_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="elementary_years" class="form-label">Years Covered</label>
                            <input type="text" class="form-control" id="elementary_years" name="elementary_years" maxlength="15" inputmode="numeric"
                                   placeholder="e.g., 2006 - 2012"
                                   value="<?php echo htmlspecialchars($_POST['elementary_years'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="elementary_school_address" class="form-label">School Address</label>
                            <input type="text" class="form-control text-uppercase" id="elementary_school_address" name="elementary_school_address" maxlength="255"
                                   value="<?php echo htmlspecialchars($_POST['elementary_school_address'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Trainings / Seminars Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">Trainings / Seminars</h4>
                    </div>

                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-2" id="trainingsTable">
                                <thead>
                                    <tr class="text-muted small">
                                        <th style="min-width: 260px;">Program / Title</th>
                                        <th style="min-width: 220px;">Conducted By</th>
                                        <th style="min-width: 240px;">Date of Training</th>
                                    </tr>
                                </thead>
                                <tbody id="trainingsTbody">
                                    <?php
                                    $trainings = $_POST['trainings'] ?? [];
                                    if (!is_array($trainings)) $trainings = [];
                                    if (count($trainings) === 0) $trainings = [[]]; // at least one blank row
                                    foreach ($trainings as $i => $t):
                                        $title = is_array($t) ? ($t['title'] ?? '') : '';
                                        $by = is_array($t) ? ($t['by'] ?? '') : '';
                                        $date = is_array($t) ? ($t['date'] ?? '') : '';
                                    ?>
                                    <tr class="training-row">
                                        <td>
                                            <input type="text" class="form-control text-uppercase"
                                                   name="trainings[<?php echo (int)$i; ?>][title]"
                                                   value="<?php echo htmlspecialchars($title); ?>"
                                                   placeholder="Program or Title of Training / Seminar" maxlength="200">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control text-uppercase"
                                                   name="trainings[<?php echo (int)$i; ?>][by]"
                                                   value="<?php echo htmlspecialchars($by); ?>"
                                                   placeholder="Conducted By" maxlength="200">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="date" class="form-control flex-grow-1"
                                                       name="trainings[<?php echo (int)$i; ?>][date]"
                                                       value="<?php echo htmlspecialchars($date); ?>">
                                                <button type="button"
                                                        class="btn btn-sm p-0 training-remove"
                                                        aria-label="Remove training"
                                                        style="min-width: 32px; line-height: 1;">
                                                    <span class="text-danger fw-bold" aria-hidden="true" style="font-size: 1.35rem; line-height: 1;">&minus;</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addTrainingBtn">
                                <i class="fas fa-plus me-2"></i>Add Training
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Employment History Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">EMPLOYMENT HISTORY <span class="text-muted fw-normal">(Last employment should be listed first)</span></h4>
                    </div>

                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-2" id="employmentTable">
                                <thead>
                                    <tr class="text-muted small">
                                        <th style="min-width: 180px;">Position</th>
                                        <th style="min-width: 320px;">Company</th>
                                        <th style="min-width: 190px;">Period Covered</th>
                                        <th style="min-width: 260px;">Reasons for Leaving</th>
                                    </tr>
                                </thead>
                                <tbody id="employmentTbody">
                                    <?php
                                    $jobs = $_POST['employment_history'] ?? [];
                                    if (!is_array($jobs)) $jobs = [];
                                    if (count($jobs) === 0) $jobs = [[]]; // at least one blank record
                                    foreach ($jobs as $i => $j):
                                        $position = is_array($j) ? ($j['position'] ?? '') : '';
                                        $company_name = is_array($j) ? ($j['company_name'] ?? '') : '';
                                        $company_address = is_array($j) ? ($j['company_address'] ?? '') : '';
                                        $company_phone = is_array($j) ? ($j['company_phone'] ?? '') : '';
                                        $period = is_array($j) ? ($j['period'] ?? '') : '';
                                        $reason = is_array($j) ? ($j['reason'] ?? '') : '';
                                    ?>
                                    <tr class="employment-row">
                                        <td>
                                            <input type="text" class="form-control text-uppercase"
                                                   name="employment_history[<?php echo (int)$i; ?>][position]"
                                                   value="<?php echo htmlspecialchars($position); ?>"
                                                   maxlength="120" placeholder="Position">
                                        </td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-link p-0 employment-toggle"
                                                    aria-expanded="false">
                                                <span class="fw-semibold">Company Details</span>
                                                <span class="employment-company-summary text-muted">
                                                    <?php echo !empty($company_name) ? ('— ' . htmlspecialchars($company_name)) : ''; ?>
                                                </span>
                                            </button>
                                            <div class="text-muted small employment-toggle-hint">Click to expand</div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="text" class="form-control flex-grow-1"
                                                       name="employment_history[<?php echo (int)$i; ?>][period]"
                                                       value="<?php echo htmlspecialchars($period); ?>"
                                                       maxlength="30" placeholder="e.g., 03/2022 - 11/2024">
                                                <button type="button"
                                                        class="btn btn-sm p-0 employment-remove"
                                                        aria-label="Remove employment record"
                                                        style="min-width: 32px; line-height: 1;">
                                                    <span class="text-danger fw-bold" aria-hidden="true" style="font-size: 1.35rem; line-height: 1;">&minus;</span>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <textarea class="form-control"
                                                      name="employment_history[<?php echo (int)$i; ?>][reason]"
                                                      rows="2" maxlength="300"
                                                      placeholder="Reason for leaving"><?php echo htmlspecialchars($reason); ?></textarea>
                                        </td>
                                    </tr>
                                    <tr class="employment-details-row d-none">
                                        <td colspan="4" class="pt-0">
                                            <div class="p-3 mt-2">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-group mb-0">
                                                            <label class="form-label">Company Name</label>
                                                            <input type="text" class="form-control text-uppercase employment-company-name"
                                                                   name="employment_history[<?php echo (int)$i; ?>][company_name]"
                                                                   value="<?php echo htmlspecialchars($company_name); ?>"
                                                                   maxlength="200">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group mb-0">
                                                            <label class="form-label">Company Phone Number</label>
                                                            <input type="tel" class="form-control"
                                                                   name="employment_history[<?php echo (int)$i; ?>][company_phone]"
                                                                   value="<?php echo htmlspecialchars($company_phone); ?>"
                                                                   maxlength="30">
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="form-group mb-0">
                                                            <label class="form-label">Company Address</label>
                                                            <textarea class="form-control text-uppercase"
                                                                      name="employment_history[<?php echo (int)$i; ?>][company_address]"
                                                                      rows="2" maxlength="255"><?php echo htmlspecialchars($company_address); ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addEmploymentBtn">
                                <i class="fas fa-plus me-2"></i>Add Employment
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Government Examination Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">Government Examination</h4>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label d-block">Have you taken any government examination?</label>
                            <?php $govTaken = $_POST['gov_exam_taken'] ?? 'No'; ?>
                            <div class="d-flex gap-4 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gov_exam_taken" id="gov_exam_yes" value="Yes" <?php echo ($govTaken === 'Yes') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="gov_exam_yes">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gov_exam_taken" id="gov_exam_no" value="No" <?php echo ($govTaken !== 'Yes') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="gov_exam_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12" id="govExamDetails" style="<?php echo ($govTaken === 'Yes') ? '' : 'display:none;'; ?>">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-2" id="govExamTable">
                                <thead>
                                    <tr class="text-muted small">
                                        <th style="min-width: 260px;">Type of Examination</th>
                                        <th style="min-width: 180px;">Score / Rating</th>
                                        <th style="width: 60px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="govExamTbody">
                                    <?php
                                    $exams = $_POST['gov_exams'] ?? [];
                                    if (!is_array($exams)) $exams = [];
                                    if (count($exams) === 0) $exams = [[]];
                                    foreach ($exams as $i => $e):
                                        $type = is_array($e) ? ($e['type'] ?? '') : '';
                                        $score = is_array($e) ? ($e['score'] ?? '') : '';
                                    ?>
                                    <tr class="exam-row">
                                        <td>
                                            <input type="text" class="form-control text-uppercase"
                                                   name="gov_exams[<?php echo (int)$i; ?>][type]"
                                                   value="<?php echo htmlspecialchars($type); ?>"
                                                   placeholder="e.g., Civil Service Exam" maxlength="200">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control"
                                                   name="gov_exams[<?php echo (int)$i; ?>][score]"
                                                   value="<?php echo htmlspecialchars($score); ?>"
                                                   placeholder="e.g., 85.00" maxlength="20" inputmode="decimal">
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-secondary btn-sm exam-remove" aria-label="Remove exam record">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addGovExamBtn">
                                <i class="fas fa-plus me-2"></i>Add Exam
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">Contact Information</h4>
                    </div>
                    <!-- Employee Contact Phone Number - Separate row -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Contact Phone Number <span class="text-danger">*</span></label>
                            <div class="row g-2 align-items-start">
                                <div class="col-4 col-sm-3">
                                    <select class="form-select" id="cc_cp" disabled style="height: 38px;">
                                        <option value="+63" selected>+63 PH</option>
                                    </select>
                                </div>
                                <div class="col-8 col-sm-9">
                                    <input type="tel" class="form-control" id="num_cp_full" inputmode="numeric" pattern="^9\d{9}$" maxlength="10" placeholder="9XXXXXXXXX" required style="height: 38px;">
                                </div>
                            </div>
                            <input type="hidden" id="cp_number" name="cp_number" value="<?php echo htmlspecialchars($_POST['cp_number'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <!-- Emergency Contact Fields - All on same row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="contact_person" class="form-label">In Case of Emergency – Contact Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="contact_person" name="contact_person" maxlength="150"
                                   placeholder="Full Name"
                                   value="<?php echo htmlspecialchars($_POST['contact_person'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="relationship" class="form-label">Relationship <span class="text-danger">*</span></label>
                            <select class="form-select" id="relationship" name="relationship" required>
                                <?php
                                $relationships = [
                                    'Mother','Father','Spouse','Partner','Sibling','Child',
                                    'Relative','Friend','Colleague','Guardian','Other'
                                ];
                                $relSel = $_POST['relationship'] ?? '';
                                ?>
                                <option value="">Select</option>
                                <?php foreach ($relationships as $rel): ?>
                                    <option value="<?php echo htmlspecialchars($rel); ?>" <?php echo ($relSel === $rel) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">Emergency Contact Number <span class="text-danger">*</span></label>
                            <div class="row g-2 align-items-start">
                                <div class="col-4 col-sm-3">
                                    <select class="form-select" id="cc_em" disabled style="height: 38px;">
                                        <option value="+63" selected>+63 PH</option>
                                    </select>
                                </div>
                                <div class="col-8 col-sm-9">
                                    <input type="tel" class="form-control" id="num_em_full" inputmode="numeric" pattern="^9\d{9}$" maxlength="10" placeholder="9XXXXXXXXX" required style="height: 38px;">
                                </div>
                            </div>
                            <input type="hidden" id="contact_person_number" name="contact_person_number" value="<?php echo htmlspecialchars($_POST['contact_person_number'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="contact_person_address" class="form-label">Emergency Address <span class="text-danger">*</span></label>
                            <textarea class="form-control text-uppercase" id="contact_person_address" name="contact_person_address" rows="2" required><?php echo htmlspecialchars($_POST['contact_person_address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addContactBtn">Add another contact</button>
                    </div>
                </div>

                <!-- Secondary Contact (optional) -->
                <div class="row g-3 mb-4 d-none" id="secondaryContact">
                    <div class="col-12">
                        <h5 class="form-section-title">Additional Contact (Optional)</h5>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="contact_person_alt" class="form-label">Emergency Contact Person</label>
                            <input type="text" class="form-control text-uppercase" id="contact_person_alt" name="contact_person_alt" maxlength="150"
                                   placeholder="Full Name"
                                   value="<?php echo htmlspecialchars($_POST['contact_person_alt'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="relationship_alt" class="form-label">Relationship</label>
                            <select class="form-select" id="relationship_alt" name="relationship_alt">
                                <option value="">Select</option>
                                <?php
                                $relationships = [
                                    'Mother','Father','Spouse','Partner','Sibling','Child',
                                    'Relative','Friend','Colleague','Guardian','Other'
                                ];
                                $relSelAlt = $_POST['relationship_alt'] ?? '';
                                foreach ($relationships as $rel): ?>
                                    <option value="<?php echo htmlspecialchars($rel); ?>" <?php echo ($relSelAlt === $rel) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">Emergency Contact Number</label>
                            <div class="row g-2 align-items-start">
                                <div class="col-4 col-sm-3">
                                    <select class="form-select" id="cc_em2" disabled style="height: 38px;">
                                        <option value="+63" selected>+63 PH</option>
                                    </select>
                                </div>
                                <div class="col-8 col-sm-9">
                                    <input type="tel" class="form-control" id="num_em2_full" inputmode="numeric" pattern="^9\d{9}$" maxlength="10" placeholder="9XXXXXXXXX" style="height: 38px;">
                                </div>
                            </div>
                            <input type="hidden" id="contact_person_number_alt" name="contact_person_number_alt" value="<?php echo htmlspecialchars($_POST['contact_person_number_alt'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="contact_person_address_alt" class="form-label">Contact Address</label>
                            <textarea class="form-control text-uppercase" id="contact_person_address_alt" name="contact_person_address_alt" rows="2"><?php echo htmlspecialchars($_POST['contact_person_address_alt'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-danger btn-sm" id="removeContactBtn">Remove this contact</button>
                    </div>
                </div>
                </div>

                <!-- Employment Information Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">Employment Information</h4>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="post" class="form-label">Post / Position <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="post" name="post" maxlength="50" placeholder="Unassigned or current post"
                                   value="<?php echo htmlspecialchars($_POST['post'] ?? ''); ?>" list="postSuggestions" required>
                            <datalist id="postSuggestions">
                                <option value="Unassigned"></option>
                                <?php foreach ($posts as $post): ?>
                                    <option value="<?php echo htmlspecialchars($post['post_title']); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <small class="form-text text-muted">Up to 50 chars.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date_hired" class="form-label">Date Hired <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_hired" name="date_hired" 
                                   value="<?php echo htmlspecialchars($_POST['date_hired'] ?? ''); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                </div>

                <!-- License Information Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">License Information</h4>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="license_no" class="form-label">License Number <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control text-uppercase" 
                                id="license_no" 
                                name="license_no" 
                                inputmode="text"
                                maxlength="25"
                                placeholder="R03-202210000014 or NCR20221025742" 
                                value="<?php echo htmlspecialchars($_POST['license_no'] ?? ''); ?>"
                                required
                            >
                            <small class="form-text text-muted">Format: PREFIX-YYYY###### or PREFIXYYYY###### (e.g., R03-202210000014, NCR20221025742)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="license_exp_date" class="form-label">License Expiration Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="license_exp_date" name="license_exp_date" 
                                   value="<?php echo htmlspecialchars($_POST['license_exp_date'] ?? ''); ?>" required>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="rlm_exp" class="form-label" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="form-check d-flex align-items-center mb-0" style="margin: 0;">
                                    <input class="form-check-input" type="checkbox" id="has_rlm" name="has_rlm" style="margin: 0; margin-right: 0.25rem;" <?php echo !empty($_POST['has_rlm']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label mb-0" for="has_rlm" style="font-weight: normal; margin: 0;">Has RLM</label>
                                </span>
                                <span>RLM Expiration</span>
                            </label>
                            <input type="date" class="form-control" id="rlm_exp" name="rlm_exp" 
                                   value="<?php echo htmlspecialchars($_POST['rlm_exp'] ?? ''); ?>">
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                </div>

                <!-- Government IDs Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">Government Identification Numbers</h4>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sss_no" class="form-label">SSS Number</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="sss_no" 
                                name="sss_no" 
                                inputmode="numeric" 
                                pattern="[0-9]{2}-[0-9]{7}-[0-9]{1}"
                                placeholder="02-1179877-4" 
                                value="<?php echo htmlspecialchars($_POST['sss_no'] ?? ''); ?>"
                            >
                            <small class="form-text text-muted">Format: ##-#######-#</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pagibig_no" class="form-label">PAG-IBIG Number</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="pagibig_no" 
                                name="pagibig_no" 
                                inputmode="numeric" 
                                pattern="[0-9]{4}-[0-9]{4}-[0-9]{4}"
                                placeholder="1210-9087-6528" 
                                value="<?php echo htmlspecialchars($_POST['pagibig_no'] ?? ''); ?>"
                            >
                            <small class="form-text text-muted">Format: ####-####-####</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tin_number" class="form-label">TIN Number</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="tin_number" 
                                name="tin_number" 
                                inputmode="numeric" 
                                pattern="[0-9]{3}-[0-9]{3}-[0-9]{3}-[0-9]{3}"
                                placeholder="360-889-408-000" 
                                value="<?php echo htmlspecialchars($_POST['tin_number'] ?? ''); ?>"
                            >
                            <small class="form-text text-muted">Format: ###-###-###-###</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="philhealth_no" class="form-label">PhilHealth Number</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="philhealth_no" 
                                name="philhealth_no" 
                                inputmode="numeric" 
                                pattern="[0-9]{2}-[0-9]{9}-[0-9]{1}"
                                placeholder="21-200190443-1" 
                                value="<?php echo htmlspecialchars($_POST['philhealth_no'] ?? ''); ?>"
                            >
                            <small class="form-text text-muted">Format: ##-#########-#</small>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    Category 5 (Exit requirements), Category 6 (Clearances), and Category 7 (Cash bond) will be recorded later inside each employee profile. You can update these after the employee is created.
                </div>

                <!-- HR Final Remarks -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">HR Final Remarks</h4>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="hr_remarks" class="form-label">HR Remarks</label>
                            <textarea class="form-control" id="hr_remarks" name="hr_remarks" rows="3" maxlength="300" placeholder="Notes / flags"><?php echo htmlspecialchars($_POST['hr_remarks'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status_summary" class="form-label">Status Summary</label>
                            <select class="form-select" id="status_summary" name="status_summary">
                                <option value="">Select</option>
                                <option value="Completed" <?php echo (($_POST['status_summary'] ?? '') === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Incomplete" <?php echo (($_POST['status_summary'] ?? '') === 'Incomplete') ? 'selected' : ''; ?>>Incomplete</option>
                                <option value="For Follow-Up" <?php echo (($_POST['status_summary'] ?? '') === 'For Follow-Up') ? 'selected' : ''; ?>>For Follow-Up</option>
                            </select>
                            <small class="form-text text-muted" style="visibility: hidden;">Placeholder</small>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="text-muted small text-end mb-3" style="padding-left: 0; padding-right: 0;">Submission will be attributed to: <?php echo htmlspecialchars($current_user_name); ?><?php echo $current_user_id ? " (User ID: {$current_user_id})" : ''; ?></div>
                <div class="form-actions d-flex justify-content-end">
                    <a href="?page=employees" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create Employee</button>
                </div>
            </form>
        </div>
    </div>
        </div>
    </div>

<!-- Google Maps API - Optional, only loads if API key is configured -->
<!-- <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places"></script> -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Employee Number is generated on the server (chronological).
    const employeeTypeSelect = document.getElementById('employee_type');
    const employeeNoInput = document.getElementById('employee_no');
    const birthDateInput = document.getElementById('birth_date');
    const ageInput = document.getElementById('age');
    
    // (Removed random generator)

    // Auto-calculate age from Birth Date (recommended)
    const calcAge = (isoDate) => {
        if (!isoDate) return '';
        const d = new Date(isoDate);
        if (Number.isNaN(d.getTime())) return '';
        const today = new Date();
        let age = today.getFullYear() - d.getFullYear();
        const m = today.getMonth() - d.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < d.getDate())) {
            age--;
        }
        if (age < 0 || age > 120) return '';
        return String(age);
    };

    const syncAge = () => {
        if (!birthDateInput || !ageInput) return;
        ageInput.value = calcAge(birthDateInput.value);
    };

    if (birthDateInput && ageInput) {
        // Make age derived from birthdate
        ageInput.readOnly = true;
        ageInput.setAttribute('tabindex', '-1');

        // Live update while picking/typing a date
        birthDateInput.addEventListener('input', syncAge);
        birthDateInput.addEventListener('change', syncAge);
        syncAge();
    }

    // Trainings / Seminars (repeatable rows)
    const trainingsTbody = document.getElementById('trainingsTbody');
    const addTrainingBtn = document.getElementById('addTrainingBtn');

    const reindexRows = (tbody, rowClass, baseName) => {
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll(rowClass));
        rows.forEach((row, idx) => {
            row.querySelectorAll('input').forEach((inp) => {
                const name = inp.getAttribute('name') || '';
                // Replace trainings[OLD] with trainings[idx]
                const updated = name.replace(new RegExp(`^${baseName}\\[\\d+\\]`), `${baseName}[${idx}]`);
                inp.setAttribute('name', updated);
            });
        });
    };

    const removeRowOrClear = (tbody, row, rowSelector, baseName) => {
        const rows = tbody.querySelectorAll(rowSelector);
        if (rows.length <= 1) {
            // Clear instead of removing last row
            row.querySelectorAll('input').forEach(i => i.value = '');
            return;
        }
        row.remove();
        reindexRows(tbody, rowSelector, baseName);
    };

    if (trainingsTbody) {
        trainingsTbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.training-remove');
            if (!btn) return;
            const row = btn.closest('tr');
            if (!row) return;
            removeRowOrClear(trainingsTbody, row, '.training-row', 'trainings');
        });
    }

    if (addTrainingBtn && trainingsTbody) {
        addTrainingBtn.addEventListener('click', () => {
            const idx = trainingsTbody.querySelectorAll('.training-row').length;
            const tr = document.createElement('tr');
            tr.className = 'training-row';
            tr.innerHTML = `
                <td>
                    <input type="text" class="form-control text-uppercase"
                           name="trainings[${idx}][title]"
                           placeholder="Program or Title of Training / Seminar" maxlength="200">
                </td>
                <td>
                    <input type="text" class="form-control text-uppercase"
                           name="trainings[${idx}][by]"
                           placeholder="Conducted By" maxlength="200">
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <input type="date" class="form-control flex-grow-1"
                               name="trainings[${idx}][date]">
                        <button type="button"
                                class="btn btn-sm p-0 training-remove"
                                aria-label="Remove training"
                                style="min-width: 32px; line-height: 1;">
                            <span class="text-danger fw-bold" aria-hidden="true" style="font-size: 1.35rem; line-height: 1;">&minus;</span>
                        </button>
                    </div>
                </td>
            `;
            trainingsTbody.appendChild(tr);
        });
    }

    // Employment History (repeatable rows with expandable company details; newest first)
    const employmentTbody = document.getElementById('employmentTbody');
    const addEmploymentBtn = document.getElementById('addEmploymentBtn');

    const reindexEmployment = () => {
        if (!employmentTbody) return;
        const mainRows = Array.from(employmentTbody.querySelectorAll('tr.employment-row'));
        mainRows.forEach((mainRow, idx) => {
            mainRow.querySelectorAll('input, textarea').forEach((el) => {
                const name = el.getAttribute('name') || '';
                if (!name) return;
                el.setAttribute('name', name.replace(/^employment_history\[\d+\]/, `employment_history[${idx}]`));
            });

            const detailsRow = mainRow.nextElementSibling;
            if (detailsRow && detailsRow.classList.contains('employment-details-row')) {
                detailsRow.querySelectorAll('input, textarea').forEach((el) => {
                    const name = el.getAttribute('name') || '';
                    if (!name) return;
                    el.setAttribute('name', name.replace(/^employment_history\[\d+\]/, `employment_history[${idx}]`));
                });
            }
        });
    };

    const setEmploymentExpanded = (mainRow, expanded) => {
        const detailsRow = mainRow?.nextElementSibling;
        if (!detailsRow || !detailsRow.classList.contains('employment-details-row')) return;
        const toggle = mainRow.querySelector('.employment-toggle');
        const hint = mainRow.querySelector('.employment-toggle-hint');
        if (expanded) {
            detailsRow.classList.remove('d-none');
            if (toggle) toggle.setAttribute('aria-expanded', 'true');
            if (hint) hint.textContent = 'Click to collapse';
        } else {
            detailsRow.classList.add('d-none');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
            if (hint) hint.textContent = 'Click to expand';
        }
    };

    const getEmploymentRowPair = (target) => {
        const mainRow = target.closest('tr.employment-row');
        if (!mainRow) return { mainRow: null, detailsRow: null };
        const detailsRow = mainRow.nextElementSibling && mainRow.nextElementSibling.classList.contains('employment-details-row')
            ? mainRow.nextElementSibling
            : null;
        return { mainRow, detailsRow };
    };

    if (employmentTbody) {
        const updateEmploymentSummary = (detailsRow) => {
            if (!detailsRow || !detailsRow.classList?.contains('employment-details-row')) return;
            const mainRow = detailsRow.previousElementSibling && detailsRow.previousElementSibling.classList.contains('employment-row')
                ? detailsRow.previousElementSibling
                : null;
            if (!mainRow) return;
            const inp = detailsRow.querySelector('.employment-company-name');
            const summary = mainRow.querySelector('.employment-company-summary');
            if (!inp || !summary) return;
            const v = (inp.value || '').trim();
            summary.textContent = v ? `— ${v.toUpperCase()}` : '';
        };

        employmentTbody.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.employment-remove');
            if (removeBtn) {
                const { mainRow, detailsRow } = getEmploymentRowPair(removeBtn);
                if (!mainRow) return;
                const allMain = employmentTbody.querySelectorAll('tr.employment-row');
                if (allMain.length <= 1) {
                    // Clear last record instead of removing
                    mainRow.querySelectorAll('input, textarea').forEach(el => el.value = '');
                    if (detailsRow) detailsRow.querySelectorAll('input, textarea').forEach(el => el.value = '');
                    const summary = mainRow.querySelector('.employment-company-summary');
                    if (summary) summary.textContent = '';
                    setEmploymentExpanded(mainRow, false);
                    return;
                }
                if (detailsRow) detailsRow.remove();
                mainRow.remove();
                reindexEmployment();
                return;
            }

            const toggleBtn = e.target.closest('.employment-toggle');
            if (toggleBtn) {
                const { mainRow, detailsRow } = getEmploymentRowPair(toggleBtn);
                if (!mainRow || !detailsRow) return;
                const isHidden = detailsRow.classList.contains('d-none');
                setEmploymentExpanded(mainRow, isHidden);
                updateEmploymentSummary(detailsRow);
            }
        });

        const onCompanyNameChange = (e) => {
            const t = e.target;
            if (!t || !(t instanceof HTMLElement)) return;
            if (!t.classList.contains('employment-company-name')) return;
            const detailsRow = t.closest('tr.employment-details-row');
            updateEmploymentSummary(detailsRow);
        };
        employmentTbody.addEventListener('input', onCompanyNameChange);
        employmentTbody.addEventListener('change', onCompanyNameChange);
        employmentTbody.addEventListener('keyup', onCompanyNameChange);

        // Initialize summaries on load (for prefilled POST values)
        employmentTbody.querySelectorAll('tr.employment-details-row').forEach((dr) => updateEmploymentSummary(dr));
    }

    if (addEmploymentBtn && employmentTbody) {
        addEmploymentBtn.addEventListener('click', () => {
            const idx = 0; // will be reindexed after prepend

            const mainRow = document.createElement('tr');
            mainRow.className = 'employment-row';
            mainRow.innerHTML = `
                <td>
                    <input type="text" class="form-control text-uppercase"
                           name="employment_history[${idx}][position]"
                           maxlength="120" placeholder="Position">
                </td>
                <td>
                    <button type="button"
                            class="btn btn-link p-0 employment-toggle"
                            aria-expanded="true">
                        <span class="fw-semibold">Company Details</span>
                        <span class="employment-company-summary text-muted"></span>
                    </button>
                    <div class="text-muted small employment-toggle-hint">Click to collapse</div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" class="form-control flex-grow-1"
                               name="employment_history[${idx}][period]"
                               maxlength="30" placeholder="e.g., 03/2022 - 11/2024">
                        <button type="button"
                                class="btn btn-sm p-0 employment-remove"
                                aria-label="Remove employment record"
                                style="min-width: 32px; line-height: 1;">
                            <span class="text-danger fw-bold" aria-hidden="true" style="font-size: 1.35rem; line-height: 1;">&minus;</span>
                        </button>
                    </div>
                </td>
                <td>
                    <textarea class="form-control"
                              name="employment_history[${idx}][reason]"
                              rows="2" maxlength="300"
                              placeholder="Reason for leaving"></textarea>
                </td>
            `;

            const detailsRow = document.createElement('tr');
            detailsRow.className = 'employment-details-row';
            detailsRow.innerHTML = `
                <td colspan="4" class="pt-0">
                    <div class="p-3 mt-2">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" class="form-control text-uppercase employment-company-name"
                                           name="employment_history[${idx}][company_name]"
                                           maxlength="200">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="form-label">Company Phone Number</label>
                                    <input type="tel" class="form-control"
                                           name="employment_history[${idx}][company_phone]"
                                           maxlength="30">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label class="form-label">Company Address</label>
                                    <textarea class="form-control text-uppercase"
                                              name="employment_history[${idx}][company_address]"
                                              rows="2" maxlength="255"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            `;

            // Prepend newest first (details must follow its main row)
            employmentTbody.prepend(detailsRow);
            employmentTbody.prepend(mainRow);
            reindexEmployment();
            setEmploymentExpanded(mainRow, true);
        });
    }

    // Government Examination (conditional + repeatable rows)
    const govYes = document.getElementById('gov_exam_yes');
    const govNo = document.getElementById('gov_exam_no');
    const govDetails = document.getElementById('govExamDetails');
    const govTbody = document.getElementById('govExamTbody');
    const addGovExamBtn = document.getElementById('addGovExamBtn');

    const setGovDetailsVisible = (visible) => {
        if (!govDetails) return;
        govDetails.style.display = visible ? '' : 'none';
        // Disable inputs when hidden so they won't submit
        govDetails.querySelectorAll('input').forEach(inp => {
            inp.disabled = !visible;
        });
    };

    if (govYes && govNo) {
        const syncGov = () => setGovDetailsVisible(govYes.checked);
        govYes.addEventListener('change', syncGov);
        govNo.addEventListener('change', syncGov);
        syncGov();
    }

    if (govTbody) {
        govTbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.exam-remove');
            if (!btn) return;
            const row = btn.closest('tr');
            if (!row) return;
            removeRowOrClear(govTbody, row, '.exam-row', 'gov_exams');
        });
    }

    if (addGovExamBtn && govTbody) {
        addGovExamBtn.addEventListener('click', () => {
            const idx = govTbody.querySelectorAll('.exam-row').length;
            const tr = document.createElement('tr');
            tr.className = 'exam-row';
            tr.innerHTML = `
                <td>
                    <input type="text" class="form-control text-uppercase"
                           name="gov_exams[${idx}][type]"
                           placeholder="e.g., Civil Service Exam" maxlength="200">
                </td>
                <td>
                    <input type="text" class="form-control"
                           name="gov_exams[${idx}][score]"
                           placeholder="e.g., 85.00" maxlength="20" inputmode="decimal">
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm exam-remove" aria-label="Remove exam record">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            govTbody.appendChild(tr);
        });
    }
    
    // Function to show validation error popup
    function showValidationErrorPopup(errors, firstErrorField) {
        // Remove any existing validation popup
        const existingPopup = document.getElementById('validationErrorModal');
        if (existingPopup) {
            existingPopup.remove();
        }
        
        // Create popup modal
        const modal = document.createElement('div');
        modal.id = 'validationErrorModal';
        modal.className = 'modal fade show';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('role', 'dialog');
        modal.style.cssText = 'display: block !important; background-color: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1050;';
        
        modal.innerHTML = `
            <div class="modal-dialog" role="document" style="margin: 2rem auto auto auto; max-width: 500px; position: relative;">
                <div class="modal-content" style="border: none; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                    <div class="modal-header bg-danger text-white" style="border-radius: 8px 8px 0 0; padding: 1rem 1.5rem;">
                        <h5 class="modal-title" style="margin: 0; font-weight: 600;">
                            <i class="fas fa-exclamation-triangle me-2"></i>Validation Error
                        </h5>
                        <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="closeValidationModal()" style="margin: 0;"></button>
                    </div>
                    <div class="modal-body" style="padding: 1.5rem;">
                        <div class="text-center mb-3">
                            <i class="fas fa-circle-exclamation text-danger" style="font-size: 3rem;"></i>
                        </div>
                        <p class="mb-3 text-center" style="font-size: 1.1rem; font-weight: 500;">Please fill in all required fields:</p>
                        <ul class="list-unstyled mb-0" style="text-align: left;">
                            ${errors.map(error => `<li style="padding: 0.5rem 0; border-bottom: 1px solid #e9ecef;"><i class="fas fa-circle text-danger me-2" style="font-size: 0.5rem;"></i>${error}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 1rem 1.5rem; border-radius: 0 0 8px 8px;">
                        <button type="button" class="btn btn-primary" onclick="closeValidationModal()">
                            <i class="fas fa-check me-2"></i>OK, I'll fix it
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add to body
        document.body.appendChild(modal);
        
        // Scroll to first error field
        if (firstErrorField.length > 0) {
            setTimeout(function() {
                firstErrorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField[0].focus();
            }, 300);
        }
        
        // Close on escape key
        const escapeHandler = function(e) {
            if (e.key === 'Escape') {
                closeValidationModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
        
        // Close on backdrop click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeValidationModal();
            }
        });
    }
    
    // Function to close validation modal
    function closeValidationModal() {
        const modal = document.getElementById('validationErrorModal');
        if (modal) {
            modal.style.opacity = '0';
            modal.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                modal.style.display = 'none';
                modal.remove();
            }, 300);
        }
    }
    
    // Make function globally accessible
    window.closeValidationModal = closeValidationModal;
    
    // Form submission handler - SIMPLIFIED AND FIXED
    // Form submission handler - IMPROVED
const form = document.getElementById('addEmployeeForm');
if (form) {
    form.addEventListener('submit', function(e) {
        // Remove any existing validation states
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        let hasErrors = false;
        const errors = [];
        const firstErrorField = [];
        
        // Validate visible required fields
        const requiredFields = form.querySelectorAll('input[required]:not([type="hidden"]):not([disabled]), select[required]:not([disabled]), textarea[required]:not([disabled])');
        
        // Helper function to get human-readable field name
        function getFieldLabel(field) {
            // Try to get label from associated label element
            const label = field.labels && field.labels.length > 0 ? field.labels[0] : null;
            if (label) {
                let labelText = label.textContent || label.innerText;
                // Remove asterisk and extra whitespace
                labelText = labelText.replace(/\*/g, '').trim();
                return labelText;
            }
            // Fallback to field name or ID
            const name = field.name || field.id;
            // Convert snake_case or camelCase to readable text
            return name.replace(/_/g, ' ').replace(/([A-Z])/g, ' $1').trim()
                .split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');
        }
        
        requiredFields.forEach(function(field) {
            // Handle different field types
            let isEmpty = false;
            if (field.tagName === 'SELECT') {
                isEmpty = !field.value || field.value === '';
            } else if (field.type === 'checkbox' || field.type === 'radio') {
                isEmpty = !field.checked;
            } else {
                isEmpty = !field.value || !field.value.trim();
            }
            
            if (isEmpty) {
                field.classList.add('is-invalid');
                hasErrors = true;
                if (firstErrorField.length === 0) {
                    firstErrorField.push(field);
                }
                const fieldLabel = getFieldLabel(field);
                errors.push(fieldLabel + ' is required');
            }
        });
        
        // Validate hidden phone number fields manually
        const cpNumber = document.getElementById('cp_number');
        const contactPersonNumber = document.getElementById('contact_person_number');
        
        if (cpNumber && (!cpNumber.value || cpNumber.value.trim() === '')) {
            hasErrors = true;
            const cpInput = document.getElementById('num_cp_full');
            if (cpInput) {
                cpInput.classList.add('is-invalid');
                if (firstErrorField.length === 0) {
                    firstErrorField.push(cpInput);
                }
            }
            errors.push('Contact Phone Number is required');
        }
        
        if (contactPersonNumber && (!contactPersonNumber.value || contactPersonNumber.value.trim() === '')) {
            hasErrors = true;
            const emInput = document.getElementById('num_em_full');
            if (emInput) {
                emInput.classList.add('is-invalid');
                if (firstErrorField.length === 0) {
                    firstErrorField.push(emInput);
                }
            }
            errors.push('Emergency Contact Number is required');
        }
        
        // If validation fails
        if (hasErrors) {
            e.preventDefault();
            e.stopPropagation();
            
            // Show custom validation error popup
            showValidationErrorPopup(errors, firstErrorField);
            
            return false;
        }
        
        // Validation passed - disable submit button
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Employee...';
        }
        
        // Allow form to submit
        return true;
    });
    
    // Real-time validation on blur
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required')) {
                // Handle different input types
                let isEmpty = false;
                if (this.tagName === 'SELECT') {
                    // For select elements, check if value is empty
                    isEmpty = !this.value || this.value === '';
                } else if (this.tagName === 'TEXTAREA' || this.type === 'text' || this.type === 'email' || this.type === 'tel') {
                    // For text inputs and textareas, use trim
                    isEmpty = !this.value || !this.value.trim();
                } else {
                    // For other input types (date, number, etc.), just check if empty
                    isEmpty = !this.value || this.value === '';
                }
                
                if (isEmpty) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            } else {
                // Remove validation classes if field is not required
                this.classList.remove('is-invalid', 'is-valid');
            }
        });
    });
}

    // Enforce numeric-only inputs on specific fields
    const numericInputs = document.querySelectorAll('.numeric-only');
    numericInputs.forEach(input => {
        input.addEventListener('input', () => {
            // Keep only digits
            input.value = input.value.replace(/[^0-9]/g, '');
        });
    });
    
    // Show post details when selected
    const postSelect = document.getElementById('post');
    const postDetailsDiv = document.getElementById('postDetails');
    
    if (postSelect && postDetailsDiv) {
        postSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const location = selectedOption.getAttribute('data-location');
                const available = selectedOption.getAttribute('data-available');
                
                if (location || available) {
                    postDetailsDiv.innerHTML = `
                        <div class="post-details-info">
                            ${location ? '<strong>Location:</strong> ' + location + '<br>' : ''}
                            ${available !== null ? '<strong>Available Positions:</strong> ' + available : ''}
                        </div>
                    `;
                    postDetailsDiv.style.display = 'block';
                } else {
                    postDetailsDiv.style.display = 'none';
                }
            } else {
                postDetailsDiv.style.display = 'none';
            }
        });
    }

    // Auto-format government IDs with dashes as user types
    const formatWithBlocks = (value, blocks) => {
        const digits = value.replace(/\D/g, '');
        const parts = [];
        let idx = 0;
        blocks.forEach(len => {
            if (idx < digits.length) {
                parts.push(digits.slice(idx, idx + len));
                idx += len;
            }
        });
        return parts.filter(Boolean).join('-');
    };

    const blockFormats = [
        { id: 'sss_no', blocks: [2, 7, 1] },
        { id: 'pagibig_no', blocks: [4, 4, 4] },
        { id: 'tin_number', blocks: [3, 3, 3, 3] },
        { id: 'philhealth_no', blocks: [2, 9, 1] },
    ];

    blockFormats.forEach(cfg => {
        const input = document.getElementById(cfg.id);
        if (!input) return;
        input.addEventListener('input', () => {
            // Strip non-digits before formatting
            const clean = input.value.replace(/\D/g, '');
            const formatted = formatWithBlocks(clean, cfg.blocks);
            input.value = formatted;
        });
    });

    // License number format enforcement with automatic dash insertion
    // Format: PREFIX-YYYY###### (e.g., R03-202210000014, NCR-20221025742)
    const licenseInput = document.getElementById('license_no');
    if (licenseInput) {
        licenseInput.addEventListener('input', function(e) {
            const cursorPosition = this.selectionStart;
            let value = this.value.toUpperCase();
            
            // Remove invalid characters (keep alphanumeric and hyphens)
            value = value.replace(/[^A-Z0-9\-]/g, '');
            
            // Remove existing dashes to reformat
            const valueWithoutDash = value.replace(/-/g, '');
            
            // Auto-insert dash after prefix (2-4 alphanumeric) when digits start
            // Format: PREFIX-YYYY######
            let formattedValue = valueWithoutDash;
            
            if (valueWithoutDash.length >= 2) {
                // Match prefix (2-4 alphanumeric) followed by digits
                const match = valueWithoutDash.match(/^([A-Z0-9]{2,4})(\d+)$/);
                if (match) {
                    // Format as PREFIX-YYYY######
                    formattedValue = match[1] + '-' + match[2];
                }
            }
            
            // Limit total length to 25 characters
            formattedValue = formattedValue.slice(0, 25);
            
            // Update value
            const oldValue = this.value;
            this.value = formattedValue;
            
            // Adjust cursor position if dash was inserted
            let newCursorPosition = cursorPosition;
            if (formattedValue.length > oldValue.length && formattedValue.includes('-') && !oldValue.includes('-')) {
                // Dash was just inserted
                const dashPosition = formattedValue.indexOf('-');
                if (cursorPosition > dashPosition) {
                    newCursorPosition = cursorPosition + 1;
                } else {
                    newCursorPosition = cursorPosition;
                }
            } else if (formattedValue.length < oldValue.length) {
                // Characters were removed
                newCursorPosition = Math.min(cursorPosition, formattedValue.length);
            } else if (formattedValue !== oldValue) {
                // Value changed but length same (dash moved or reformatted)
                const dashPos = formattedValue.indexOf('-');
                if (dashPos >= 0 && cursorPosition > dashPos) {
                    newCursorPosition = cursorPosition;
                }
            }
            
            // Ensure cursor doesn't go beyond value length
            newCursorPosition = Math.min(newCursorPosition, formattedValue.length);
            
            // Set cursor position
            this.setSelectionRange(newCursorPosition, newCursorPosition);
        });
        
        // Handle paste for license
        licenseInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            let value = pasted.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
            
            // Remove existing dashes to reformat
            const valueWithoutDash = value.replace(/-/g, '');
            
            // Auto-format as PREFIX-YYYY######
            let formattedValue = valueWithoutDash;
            if (valueWithoutDash.length >= 2) {
                const match = valueWithoutDash.match(/^([A-Z0-9]{2,4})(\d+)$/);
                if (match) {
                    formattedValue = match[1] + '-' + match[2];
                }
            }
            
            this.value = formattedValue.slice(0, 25);
            this.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    // Enforce numeric-only on fields with .numeric-only (remove letters/specials)
    document.querySelectorAll('.numeric-only').forEach(el => {
        el.addEventListener('input', () => {
            const digits = el.value.replace(/\D/g, '');
            el.value = digits;
        });
    });

    // Enforce letters-only on name fields (allow spaces, apostrophe, hyphen)
    const lettersOnly = (val) => val.replace(/[^A-Za-z\s'\-]/g, '');
    ['surname','first_name','middle_name'].forEach(id => {
        const input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('input', () => {
            input.value = lettersOnly(input.value);
        });
    });

    // Date constraints: birth_date cannot be in the future; expiration dates remain unrestricted
    const today = new Date().toISOString().split('T')[0];
    const birthInput = document.getElementById('birth_date');
    if (birthInput) {
        birthInput.setAttribute('max', today);
    }
    const hiredInput = document.getElementById('date_hired');
    if (hiredInput) {
        hiredInput.setAttribute('max', today);
        
        // Allow backspace to clear the date field
        hiredInput.addEventListener('keydown', (e) => {
            // Handle backspace key
            if (e.key === 'Backspace' || e.keyCode === 8) {
                // If field has value and cursor is at the start or entire field is selected
                if (hiredInput.value) {
                    const start = hiredInput.selectionStart;
                    const end = hiredInput.selectionEnd;
                    
                    // If entire field is selected or cursor is at start, clear the field
                    if (start === 0 && end === hiredInput.value.length) {
                        e.preventDefault();
                        hiredInput.value = '';
                        hiredInput.dispatchEvent(new Event('input', { bubbles: true }));
                        hiredInput.dispatchEvent(new Event('change', { bubbles: true }));
                    } else if (start === end && start === 0) {
                        // Cursor at start, clear field
                        e.preventDefault();
                        hiredInput.value = '';
                        hiredInput.dispatchEvent(new Event('input', { bubbles: true }));
                        hiredInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }
            
            // Handle Delete key
            if (e.key === 'Delete' || e.keyCode === 46) {
                if (hiredInput.value) {
                    const start = hiredInput.selectionStart;
                    const end = hiredInput.selectionEnd;
                    
                    // If entire field is selected, clear it
                    if (start === 0 && end === hiredInput.value.length) {
                        e.preventDefault();
                        hiredInput.value = '';
                        hiredInput.dispatchEvent(new Event('input', { bubbles: true }));
                        hiredInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }
        });
        
        // Also handle when user selects all and presses backspace
        hiredInput.addEventListener('keyup', (e) => {
            if ((e.key === 'Backspace' || e.keyCode === 8) && hiredInput.value === '') {
                // Field was cleared, trigger validation update
                hiredInput.dispatchEvent(new Event('input', { bubbles: true }));
                hiredInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    // Address textarea - ensure it's always fully editable
    const addressInput = document.getElementById('address');
    if (addressInput) {
        // Explicitly ensure textarea is editable (remove any readonly/disabled attributes)
        addressInput.removeAttribute('readonly');
        addressInput.removeAttribute('disabled');
        
        // Optional: Address autocomplete using Google Places (only if API is properly configured)
        // This is completely optional - manual typing always works
        try {
            if (window.google && google.maps && google.maps.places && google.maps.places.Autocomplete) {
                const autocomplete = new google.maps.places.Autocomplete(addressInput, {
                    fields: ['formatted_address'],
                    types: ['address']
                });
                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    if (place && place.formatted_address) {
                        addressInput.value = place.formatted_address.toUpperCase();
                    }
                });
                // Ensure textarea remains editable after autocomplete initialization
                addressInput.removeAttribute('readonly');
            }
        } catch (e) {
            // Google Places not available - manual entry is fully enabled
            console.log('Google Places autocomplete not available, manual entry enabled');
        }
    }

    // Simple wizard controller
    const panels = Array.from(document.querySelectorAll('.wizard-panel'));
    const nextBtn = document.getElementById('nextStep');
    const prevBtn = document.getElementById('prevStep');
    const submitBtn = document.getElementById('submitForm');
    let currentStep = 1;

    const showStep = (step) => {
        panels.forEach(panel => {
            const s = parseInt(panel.getAttribute('data-step'), 10);
            panel.classList.toggle('d-none', s !== step);
        });
        if (prevBtn) prevBtn.disabled = step === 1;
        if (nextBtn) nextBtn.classList.toggle('d-none', step === panels.length);
        if (submitBtn) submitBtn.classList.toggle('d-none', step !== panels.length);
    };

    if (panels.length && nextBtn && prevBtn && submitBtn) {
        showStep(currentStep);
        nextBtn.addEventListener('click', () => {
            if (currentStep < panels.length) {
                currentStep += 1;
                showStep(currentStep);
            }
        });
        prevBtn.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep -= 1;
                showStep(currentStep);
            }
        });
    }

    // Height compose: keep hidden height field as 5'7" from ft/in inputs
    const heightHidden = document.getElementById('height');
    const heightFt = document.getElementById('height_ft');
    const heightIn = document.getElementById('height_in');
    const syncHeight = () => {
        if (!heightHidden) return;
        const ft = heightFt && heightFt.value ? heightFt.value.replace(/\D/g, '') : '';
        const inch = heightIn && heightIn.value ? heightIn.value.replace(/\D/g, '') : '';
        if (ft || inch) {
            heightHidden.value = `${ft || 0}'${inch || 0}"`;
        } else {
            heightHidden.value = '';
        }
    };
    if (heightFt) heightFt.addEventListener('input', syncHeight);
    if (heightIn) heightIn.addEventListener('input', syncHeight);
    syncHeight();

    // Phone formatting: PH mobile format - must start with 9, 10 digits total
    // Only allows numbers, enforces PH format (9XXXXXXXXX)
    const limitDigits = (val, len) => val.replace(/\D/g, '').slice(0, len);
    
    const bindPhoneSingle = (hiddenId, ccId, numId, numLen) => {
        const hidden = document.getElementById(hiddenId);
        const cc = document.getElementById(ccId);
        const num = document.getElementById(numId);
        if (!hidden || !cc || !num) return;
        
        // Validate PH format: must start with 9
        const validatePHFormat = (value) => {
            if (value.length === 0) return value;
            // If first digit is not 9, remove it
            if (value.length > 0 && value[0] !== '9') {
                return '9' + value.replace(/[^0-9]/g, '').slice(0, numLen - 1);
            }
            return value;
        };
        
        const sync = () => {
            // Get value from input field
            let value = num.value.replace(/\D/g, '');
            
            // If hidden field has old format (+63-9XXXXXXXXX), extract just the number
            if (hidden.value && hidden.value.includes('-')) {
                const parts = hidden.value.split('-');
                if (parts.length > 1) {
                    // Extract the number part (last part after dash)
                    value = parts[parts.length - 1].replace(/\D/g, '');
                } else {
                    // Just clean the hidden value
                    value = hidden.value.replace(/\D/g, '');
                }
            }
            
            // Remove leading "0" if present (since +63 already includes it)
            if (value.startsWith('0')) {
                value = value.substring(1);
            }
            // Remove country code if present (63 at the start)
            if (value.startsWith('63') && value.length > 10) {
                value = value.substring(2);
            }
            // Enforce PH format: must start with 9
            value = validatePHFormat(value);
            // Limit to max length
            value = limitDigits(value, numLen);
            num.value = value;
            const n = num.value;
            // Store only the 10-digit number in hidden field (without country code)
            // The country code is just for display
            hidden.value = n || '';
            
            // Add validation styling
            if (n.length > 0 && n.length < numLen) {
                num.classList.add('is-invalid');
                num.classList.remove('is-valid');
            } else if (n.length === numLen && n.startsWith('9')) {
                num.classList.remove('is-invalid');
                num.classList.add('is-valid');
            } else if (n.length === 0) {
                num.classList.remove('is-invalid', 'is-valid');
            } else if (n.length > 0 && !n.startsWith('9')) {
                num.classList.add('is-invalid');
                num.classList.remove('is-valid');
            }
        };
        
        // Handle input events - enforce numbers only and PH format
        num.addEventListener('input', (e) => {
            // Remove all non-numeric characters
            let value = e.target.value.replace(/\D/g, '');
            
            // Remove leading "0" if present
            if (value.startsWith('0')) {
                value = value.substring(1);
            }
            
            // Enforce PH format: must start with 9
            // If first digit is not 9 and user is typing, replace it with 9
            if (value.length === 1 && value[0] !== '9') {
                value = '9';
            } else if (value.length > 1 && value[0] !== '9') {
                // If user pastes or types multiple digits and first isn't 9, fix it
                value = '9' + value.replace(/[^0-9]/g, '').slice(1, numLen);
            }
            
            // Limit to max length
            value = limitDigits(value, numLen);
            
            // Update the input value
            e.target.value = value;
            
            // Sync hidden field - store only the 10-digit number (without country code)
            const n = e.target.value;
            hidden.value = n || '';
            
            // Add validation styling
            if (n.length > 0 && n.length < numLen) {
                e.target.classList.add('is-invalid');
                e.target.classList.remove('is-valid');
            } else if (n.length === numLen && n.startsWith('9')) {
                e.target.classList.remove('is-invalid');
                e.target.classList.add('is-valid');
            } else if (n.length === 0) {
                e.target.classList.remove('is-invalid', 'is-valid');
            } else if (n.length > 0 && !n.startsWith('9')) {
                e.target.classList.add('is-invalid');
                e.target.classList.remove('is-valid');
            }
        });
        
        // Validate on blur
        num.addEventListener('blur', () => {
            const value = num.value.replace(/\D/g, '');
            if (value.length > 0 && (!value.startsWith('9') || value.length !== numLen)) {
                num.classList.add('is-invalid');
                num.classList.remove('is-valid');
            } else if (value.length === numLen && value.startsWith('9')) {
                num.classList.remove('is-invalid');
                num.classList.add('is-valid');
            }
        });
        
        // Prevent non-numeric input on keypress
        num.addEventListener('keypress', (e) => {
            // Allow: backspace, delete, tab, escape, enter, decimal point
            if ([8, 9, 27, 13, 46, 110, 190].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true) ||
                // Allow: home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
        
        // Handle paste events
        num.addEventListener('paste', (e) => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            // Remove all non-digits
            let value = paste.replace(/\D/g, '');
            // Remove leading "0" if present
            if (value.startsWith('0')) {
                value = value.substring(1);
            }
            // Enforce PH format
            value = validatePHFormat(value);
            // Limit to max length
            value = limitDigits(value, numLen);
            num.value = value;
            sync();
        });
        
        // Sync on country code change
        cc.addEventListener('change', sync);
        // Initial sync
        sync();
    };

    // Bind phone number inputs
    bindPhoneSingle('cp_number', 'cc_cp', 'num_cp_full', 10);
    bindPhoneSingle('contact_person_number', 'cc_em', 'num_em_full', 10);
    bindPhoneSingle('contact_person_number_alt', 'cc_em2', 'num_em2_full', 10);

    // Emergency contact name (text) - light validation feedback
    const contactPersonField = document.getElementById('contact_person');
    if (contactPersonField) {
        const validateContactName = () => {
            const value = contactPersonField.value.trim();
            if (value.length >= 2) {
                contactPersonField.classList.remove('is-invalid');
                contactPersonField.classList.add('is-valid');
                return true;
            }
            contactPersonField.classList.remove('is-valid');
            contactPersonField.classList.add('is-invalid');
            return false;
        };

        contactPersonField.addEventListener('blur', validateContactName);
        contactPersonField.addEventListener('input', () => {
            contactPersonField.classList.remove('is-invalid');
        });
    }

    // Additional contact name (text) - light validation feedback
    const contactPersonAltField = document.getElementById('contact_person_alt');
    if (contactPersonAltField) {
        const validateContactNameAlt = () => {
            const value = contactPersonAltField.value.trim();
            if (value.length === 0) {
                contactPersonAltField.classList.remove('is-valid', 'is-invalid');
                return true;
            }
            if (value.length >= 2) {
                contactPersonAltField.classList.remove('is-invalid');
                contactPersonAltField.classList.add('is-valid');
                return true;
            }
            contactPersonAltField.classList.remove('is-valid');
            contactPersonAltField.classList.add('is-invalid');
            return false;
        };

        contactPersonAltField.addEventListener('blur', validateContactNameAlt);
        contactPersonAltField.addEventListener('input', () => {
            contactPersonAltField.classList.remove('is-invalid');
        });
    }
        
    // (Emergency numbers are validated via the dedicated phone inputs and hidden fields)
    // Validation for Government Identification Numbers
    const govIdFields = [
        {
            id: 'sss_no',
            pattern: /^[0-9]{2}-[0-9]{7}-[0-9]{1}$/,
            maxLength: 12
        },
        {
            id: 'pagibig_no',
            pattern: /^[0-9]{4}-[0-9]{4}-[0-9]{4}$/,
            maxLength: 14
        },
        {
            id: 'tin_number',
            pattern: /^[0-9]{3}-[0-9]{3}-[0-9]{3}-[0-9]{3}$/,
            maxLength: 15
        },
        {
            id: 'philhealth_no',
            pattern: /^[0-9]{2}-[0-9]{9}-[0-9]{1}$/,
            maxLength: 14
        }
    ];

    govIdFields.forEach(fieldConfig => {
        const field = document.getElementById(fieldConfig.id);
        if (!field) return;

        const validateGovId = () => {
            const value = field.value.trim();
            
            // Valid if matches pattern exactly
            if (value && fieldConfig.pattern.test(value)) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
                return true;
            } else if (value && value.length > 0) {
                // Has value but doesn't match pattern
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
                return false;
            } else {
                // Empty field - remove validation classes (optional fields)
                field.classList.remove('is-valid', 'is-invalid');
                return false;
            }
        };

        // Validate on input (real-time)
        field.addEventListener('input', validateGovId);
        
        // Validate on blur (when user leaves field)
        field.addEventListener('blur', validateGovId);
        
        // Initial validation if field has value
        if (field.value.trim().length > 0) {
            validateGovId();
        }
    });

    // Validation for License Number
    // Supports formats: R03-202210000014, NCR20221025742, R01202302200767, etc.
    const licenseField = document.getElementById('license_no');
    if (licenseField) {
        const validateLicense = () => {
            const value = licenseField.value.trim().toUpperCase();
            
            // Pattern: Prefix (2-4 alphanumeric) + optional hyphen + Year (4 digits) + Sequence (5-10 digits)
            // Examples: R03-202210000014, NCR20221025742, R01202302200767, CAR-202306000018
            // Prefix patterns: R01-R13, R4A, R4B, NCR, CAR, BAR, ARM, etc.
            const licensePattern = /^[A-Z0-9]{2,4}-?[0-9]{4}[0-9]{5,10}$/;
            
            // Additional check: Must have at least 12 characters total (prefix + year + sequence)
            const minLength = 12;
            const maxLength = 25;
            
            // Valid if matches pattern and length requirements
            if (value && value.length >= minLength && value.length <= maxLength && licensePattern.test(value)) {
                licenseField.classList.remove('is-invalid');
                licenseField.classList.add('is-valid');
                return true;
            } else if (value && value.length > 0) {
                // Has value but doesn't match pattern
                licenseField.classList.remove('is-valid');
                licenseField.classList.add('is-invalid');
                return false;
            } else {
                // Empty field - remove validation classes (will show invalid on submit if required)
                licenseField.classList.remove('is-valid', 'is-invalid');
                return false;
            }
        };

        // Validate on input (real-time) - with small delay to allow formatting
        licenseField.addEventListener('input', () => {
            setTimeout(validateLicense, 10);
        });
        
        // Validate on blur (when user leaves field)
        licenseField.addEventListener('blur', validateLicense);
        
        // Note: Form submit validation is handled in the main form submit handler
        // This prevents multiple submit handlers from conflicting
        
        // Initial validation if field has value
        if (licenseField.value.trim().length > 0) {
            validateLicense();
        }
    }

    // Auto-uppercase for all text inputs and textareas with text-uppercase class
    // This ensures all text input is automatically converted to uppercase
    const textInputs = document.querySelectorAll('input[type="text"].text-uppercase, textarea.text-uppercase');
    textInputs.forEach(input => {
        // Convert to uppercase on input
        input.addEventListener('input', (e) => {
            const cursorPos = e.target.selectionStart;
            const originalValue = e.target.value;
            e.target.value = originalValue.toUpperCase();
            // Restore cursor position
            e.target.setSelectionRange(cursorPos, cursorPos);
        });
        // Convert to uppercase on paste
        input.addEventListener('paste', (e) => {
            setTimeout(() => {
                const cursorPos = e.target.selectionStart;
                const originalValue = e.target.value;
                e.target.value = originalValue.toUpperCase();
                e.target.setSelectionRange(cursorPos, cursorPos);
            }, 0);
        });
    });

    // Secondary contact toggle
    const addContactBtn = document.getElementById('addContactBtn');
    const removeContactBtn = document.getElementById('removeContactBtn');
    const secondaryContact = document.getElementById('secondaryContact');
    if (addContactBtn && secondaryContact) {
        addContactBtn.addEventListener('click', () => {
            // Show the secondary contact section
            secondaryContact.classList.remove('d-none');
            // Hide the "Add another contact" button
            addContactBtn.classList.add('d-none');
        });
    }
    if (removeContactBtn && secondaryContact) {
        removeContactBtn.addEventListener('click', () => {
            // Hide the secondary contact section
            secondaryContact.classList.add('d-none');
            // Show the "Add another contact" button again
            if (addContactBtn) {
                addContactBtn.classList.remove('d-none');
            }
            // Clear all fields in the secondary contact section
            const clearIds = ['contact_person_alt','relationship_alt','num_em2_full','contact_person_number_alt','contact_person_address_alt'];
            clearIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        });
    }

    // RLM toggle: show/require expiration only if has_rlm checked
    const hasRlm = document.getElementById('has_rlm');
    const rlmExp = document.getElementById('rlm_exp');
    const syncRlm = () => {
        if (!hasRlm || !rlmExp) return;
        const enabled = hasRlm.checked;
        rlmExp.disabled = !enabled;
        rlmExp.required = enabled;
        if (!enabled) rlmExp.value = '';
    };
    if (hasRlm) {
        hasRlm.addEventListener('change', syncRlm);
        syncRlm();
    }
});

// Photo preview function
function previewPhoto(input) {
    const preview = document.getElementById('photo_preview');
    const previewImg = document.getElementById('photo_preview_img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.style.display = 'none';
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'flex';
        previewImg.style.display = 'none';
    }
}
</script>
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
