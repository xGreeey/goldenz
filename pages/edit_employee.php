<?php
$page_title = 'Edit Employee - Golden Z-5 HR System';
$page = 'edit_employee';

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

// Get logged-in user information
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;
$current_user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'System Administrator';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('Edit Employee: POST request received for employee ID: ' . ($employee_id ?? 'unknown'));
    error_log('Edit Employee: POST data: ' . print_r($_POST, true));
    $errors = [];
    
    // Validate required fields
    $required_fields = ['first_name', 'surname', 'employee_no', 'employee_type', 'post', 'date_hired', 'status', 'cp_number'];
    $required_labels = [
        'first_name' => 'First Name',
        'surname' => 'Last Name',
        'employee_no' => 'Employee Number',
        'employee_type' => 'Employee Type',
        'post' => 'Post / Position',
        'date_hired' => 'Date Hired',
        'status' => 'Status',
        'cp_number' => 'Contact Phone Number'
    ];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $label = $required_labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $errors[] = $label . ' is required.';
        }
    }
    
    // If no errors, update in database
    if (empty($errors)) {
        try {
            // Helper function to convert empty strings to null
            $nullIfEmpty = function($value) {
                return ($value === '' || $value === null) ? null : trim($value);
            };
            
            // Prepare employee data with all fields - convert empty strings to null
            $employee_data = [
                'employee_no' => trim($_POST['employee_no']),
                'employee_type' => $_POST['employee_type'],
                'surname' => trim($_POST['surname']),
                'first_name' => trim($_POST['first_name']),
                'middle_name' => $nullIfEmpty($_POST['middle_name'] ?? ''),
                'post' => trim($_POST['post']),
                'license_no' => $nullIfEmpty($_POST['license_no'] ?? ''),
                'license_exp_date' => $nullIfEmpty($_POST['license_exp_date'] ?? ''),
                'rlm_exp' => $nullIfEmpty($_POST['rlm_exp'] ?? ''),
                'date_hired' => $_POST['date_hired'],
                'cp_number' => $nullIfEmpty($_POST['cp_number'] ?? ''),
                'sss_no' => $nullIfEmpty($_POST['sss_no'] ?? ''),
                'pagibig_no' => $nullIfEmpty($_POST['pagibig_no'] ?? ''),
                'tin_number' => $nullIfEmpty($_POST['tin_number'] ?? ''),
                'philhealth_no' => $nullIfEmpty($_POST['philhealth_no'] ?? ''),
                'birth_date' => $nullIfEmpty($_POST['birth_date'] ?? ''),
                'height' => $nullIfEmpty($_POST['height'] ?? ''),
                'weight' => $nullIfEmpty($_POST['weight'] ?? ''),
                'address' => $nullIfEmpty($_POST['address'] ?? ''),
                'contact_person' => $nullIfEmpty($_POST['contact_person'] ?? ''),
                'relationship' => $nullIfEmpty($_POST['relationship'] ?? ''),
                'contact_person_address' => $nullIfEmpty($_POST['contact_person_address'] ?? ''),
                'contact_person_number' => $nullIfEmpty($_POST['contact_person_number'] ?? ''),
                'blood_type' => $nullIfEmpty($_POST['blood_type'] ?? ''),
                'religion' => $nullIfEmpty($_POST['religion'] ?? ''),
                'status' => $_POST['status']
            ];
            
            // Log the update attempt
            error_log('Updating employee ID: ' . $employee_id . ' with data: ' . print_r($employee_data, true));
            
            // Use the update_employee function from database.php
            $result = update_employee($employee_id, $employee_data);
            
            if ($result) {
                // Log to audit trail
                if (function_exists('log_audit_event')) {
                    log_audit_event('UPDATE', 'employees', $employee_id, $employee, $employee_data, $current_user_id);
                }
                
                error_log('Employee ID ' . $employee_id . ' updated successfully');
                
                // Store success message in session
                $_SESSION['message'] = 'Employee updated successfully!';
                $_SESSION['message_type'] = 'success';
                
                // Use JavaScript redirect to ensure full page reload (bypasses AJAX transition system)
                $redirect_url = '?page=employees';
                echo '<script>
                    window.location.href = ' . json_encode($redirect_url) . ';
                </script>';
                exit;
            } else {
                error_log('Failed to update employee ID: ' . $employee_id);
                $errors[] = 'Failed to update employee. Please check the error logs for details.';
            }
        } catch (Exception $e) {
            $error_msg = 'Database error: ' . $e->getMessage();
            $errors[] = $error_msg;
            error_log('Edit Employee Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        } catch (PDOException $e) {
            $error_msg = 'Database error: ' . $e->getMessage();
            $errors[] = $error_msg;
            error_log('Edit Employee PDO Error: ' . $e->getMessage());
            error_log('SQL State: ' . $e->getCode());
        }
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

// Parse height if it exists (format: 5'7")
$height_ft = '';
$height_in = '';
if (!empty($employee['height'])) {
    if (preg_match("/(\d+)'(\d+)\"/", $employee['height'], $matches)) {
        $height_ft = $matches[1];
        $height_in = $matches[2];
    }
}

// Parse phone numbers if they exist
$cp_number_display = '';
if (!empty($employee['cp_number'])) {
    $cp_parts = explode('-', $employee['cp_number']);
    if (count($cp_parts) > 1) {
        $cp_number_display = $cp_parts[1] ?? '';
    } else {
        $cp_number_display = $employee['cp_number'];
    }
}

$contact_person_number_display = '';
if (!empty($employee['contact_person_number'])) {
    $contact_parts = explode('-', $employee['contact_person_number']);
    if (count($contact_parts) > 1) {
        $contact_person_number_display = $contact_parts[1] ?? '';
    } else {
        $contact_person_number_display = $employee['contact_person_number'];
    }
}
?>

<div class="add-employee-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-actions">
            <a href="?page=employees" class="btn btn-outline-secondary">
                Back to Employees
            </a>
        </div>
    </div>

    <!-- Edit Employee Form -->
    <div class="card">
        <div class="card-header">
            <h3>Employee Information</h3>
        </div>
        <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" id="errorAlert" style="margin-bottom: 1.5rem;">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <script>
                        // Scroll to error message when page loads with errors
                        document.addEventListener('DOMContentLoaded', function() {
                            const errorAlert = document.getElementById('errorAlert');
                            if (errorAlert) {
                                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                // Also highlight invalid fields
                                const requiredFields = ['first_name', 'surname', 'employee_no', 'employee_type', 'post', 'date_hired', 'status'];
                                requiredFields.forEach(fieldName => {
                                    const field = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
                                    if (field && !field.value.trim()) {
                                        field.classList.add('is-invalid');
                                        field.style.borderColor = '#dc3545';
                                    }
                                });
                            }
                        });
                    </script>
                <?php endif; ?>

                <form method="POST" action="?page=edit_employee&id=<?php echo htmlspecialchars($employee_id); ?>" id="editEmployeeForm" data-no-transition="true" novalidate>
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
                                    class="form-control numeric-only <?php echo (isset($errors) && empty($_POST['employee_no'] ?? '')) ? 'is-invalid' : ''; ?>" 
                                    id="employee_no" 
                                    name="employee_no" 
                                    inputmode="numeric" 
                                    pattern="\\d{1,5}"
                                    maxlength="5"
                                    value="<?php echo htmlspecialchars($employee['employee_no'] ?? ''); ?>" 
                                    required
                                >
                                <div class="invalid-feedback">Employee Number is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="employee_type" class="form-label">Employee Type <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo (isset($errors) && empty($_POST['employee_type'] ?? '')) ? 'is-invalid' : ''; ?>" id="employee_type" name="employee_type" required>
                                    <option value="">Select Employee Type</option>
                                    <option value="SG" <?php echo (($employee['employee_type'] ?? '') === 'SG') ? 'selected' : ''; ?>>Security Guard (SG)</option>
                                    <option value="LG" <?php echo (($employee['employee_type'] ?? '') === 'LG') ? 'selected' : ''; ?>>Lady Guard (LG)</option>
                                    <option value="SO" <?php echo (($employee['employee_type'] ?? '') === 'SO') ? 'selected' : ''; ?>>Security Officer (SO)</option>
                                </select>
                                <div class="invalid-feedback">Employee Type is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo (isset($errors) && empty($_POST['status'] ?? '')) ? 'is-invalid' : ''; ?>" id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="Active" <?php echo (($employee['status'] ?? '') === 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo (($employee['status'] ?? '') === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="Terminated" <?php echo (($employee['status'] ?? '') === 'Terminated') ? 'selected' : ''; ?>>Terminated</option>
                                    <option value="Suspended" <?php echo (($employee['status'] ?? '') === 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                                <div class="invalid-feedback">Status is required.</div>
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
                                <input type="text" class="form-control text-uppercase <?php echo (isset($errors) && empty($_POST['surname'] ?? '')) ? 'is-invalid' : ''; ?>" id="surname" name="surname" maxlength="50"
                                       value="<?php echo htmlspecialchars($employee['surname'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Last Name is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase <?php echo (isset($errors) && empty($_POST['first_name'] ?? '')) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" maxlength="50"
                                       value="<?php echo htmlspecialchars($employee['first_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">First Name is required.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control text-uppercase" id="middle_name" name="middle_name" maxlength="50"
                                       value="<?php echo htmlspecialchars($employee['middle_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="birth_date" class="form-label">Birth Date</label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                       value="<?php echo htmlspecialchars($employee['birth_date'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">Height</label>
                                <div class="d-flex gap-2">
                                    <input type="number" class="form-control" id="height_ft" min="0" max="9" step="1" placeholder="ft" value="<?php echo htmlspecialchars($height_ft); ?>">
                                    <input type="number" class="form-control" id="height_in" min="0" max="11" step="1" placeholder="in" value="<?php echo htmlspecialchars($height_in); ?>">
                                </div>
                                <input type="hidden" id="height" name="height" value="<?php echo htmlspecialchars($employee['height'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="weight" class="form-label">Weight</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="weight" name="weight" step="0.1" min="0" placeholder="0.0" value="<?php echo htmlspecialchars($employee['weight'] ?? ''); ?>">
                                    <span class="input-group-text">kg</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="blood_type" class="form-label">Blood Type</label>
                                <select class="form-select" id="blood_type" name="blood_type">
                                    <option value="">Select Blood Type</option>
                                    <option value="A+" <?php echo (($employee['blood_type'] ?? '') === 'A+') ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?php echo (($employee['blood_type'] ?? '') === 'A-') ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?php echo (($employee['blood_type'] ?? '') === 'B+') ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?php echo (($employee['blood_type'] ?? '') === 'B-') ? 'selected' : ''; ?>>B-</option>
                                    <option value="AB+" <?php echo (($employee['blood_type'] ?? '') === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?php echo (($employee['blood_type'] ?? '') === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                    <option value="O+" <?php echo (($employee['blood_type'] ?? '') === 'O+') ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?php echo (($employee['blood_type'] ?? '') === 'O-') ? 'selected' : ''; ?>>O-</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="religion" class="form-label">Religion</label>
                                <select class="form-select" id="religion" name="religion">
                                    <option value="">Select Religion</option>
                                    <?php
                                    $religions = [
                                        'Roman Catholic','Iglesia ni Cristo','Aglipayan / Philippine Independent Church',
                                        'Evangelical','Baptist','Methodist','Adventist','Born Again Christian',
                                        'Jehovahs Witness','Lutheran','Pentecostal','Protestant (Other)',
                                        'Muslim','Buddhist','Hindu','Sikh','Taoist',
                                        'Indigenous / Tribal','No Religion','Other'
                                    ];
                                    $selRel = $employee['religion'] ?? '';
                                    foreach ($religions as $rel):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($rel); ?>" <?php echo ($selRel === $rel) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($rel); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control text-uppercase" id="address" name="address" rows="2"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h4 class="form-section-title">Contact Information</h4>
                        </div>
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
                                        <input type="tel" class="form-control" id="num_cp_full" inputmode="numeric" pattern="^9\d{9}$" maxlength="10" placeholder="9XXXXXXXXX" required style="height: 38px;" value="<?php echo htmlspecialchars($cp_number_display); ?>">
                                    </div>
                                </div>
                                <input type="hidden" id="cp_number" name="cp_number" value="<?php echo htmlspecialchars($employee['cp_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="contact_person" class="form-label">Emergency Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                       value="<?php echo htmlspecialchars($employee['contact_person'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="relationship" class="form-label">Relationship</label>
                                <select class="form-select" id="relationship" name="relationship">
                                    <?php
                                    $relationships = [
                                        'Mother','Father','Spouse','Partner','Sibling','Child',
                                        'Relative','Friend','Colleague','Guardian','Other'
                                    ];
                                    $relSel = $employee['relationship'] ?? '';
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
                                <label class="form-label">Emergency Contact Number</label>
                                <div class="row g-2 align-items-start">
                                    <div class="col-4 col-sm-3">
                                        <select class="form-select" id="cc_em" disabled style="height: 38px;">
                                            <option value="+63" selected>+63 PH</option>
                                        </select>
                                    </div>
                                    <div class="col-8 col-sm-9">
                                        <input type="tel" class="form-control" id="num_em_full" inputmode="numeric" pattern="^9\d{9}$" maxlength="10" placeholder="9XXXXXXXXX" style="height: 38px;" value="<?php echo htmlspecialchars($contact_person_number_display); ?>">
                                    </div>
                                </div>
                                <input type="hidden" id="contact_person_number" name="contact_person_number" value="<?php echo htmlspecialchars($employee['contact_person_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="contact_person_address" class="form-label">Contact Address</label>
                                <textarea class="form-control text-uppercase" id="contact_person_address" name="contact_person_address" rows="2"><?php echo htmlspecialchars($employee['contact_person_address'] ?? ''); ?></textarea>
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
                                <input type="text" class="form-control text-uppercase <?php echo (isset($errors) && empty($_POST['post'] ?? '')) ? 'is-invalid' : ''; ?>" id="post" name="post" maxlength="100" placeholder="Unassigned or current post"
                                       value="<?php echo htmlspecialchars($employee['post'] ?? ''); ?>" list="postSuggestions" required>
                                <datalist id="postSuggestions">
                                    <option value="Unassigned"></option>
                                    <?php foreach ($posts as $post): ?>
                                        <option value="<?php echo htmlspecialchars($post['post_title']); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                                <div class="invalid-feedback">Post / Position is required.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_hired" class="form-label">Date Hired <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo (isset($errors) && empty($_POST['date_hired'] ?? '')) ? 'is-invalid' : ''; ?>" id="date_hired" name="date_hired" 
                                       value="<?php echo htmlspecialchars($employee['date_hired'] ?? ''); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                                <div class="invalid-feedback">Date Hired is required.</div>
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
                                <label for="license_no" class="form-label">License Number</label>
                                <input 
                                    type="text" 
                                    class="form-control text-uppercase" 
                                    id="license_no" 
                                    name="license_no" 
                                    inputmode="text"
                                    maxlength="25"
                                    placeholder="R03-202210000014 or NCR20221025742" 
                                    value="<?php echo htmlspecialchars($employee['license_no'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="license_exp_date" class="form-label">License Expiration Date</label>
                                <input type="date" class="form-control" id="license_exp_date" name="license_exp_date" 
                                       value="<?php echo htmlspecialchars($employee['license_exp_date'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="rlm_exp" class="form-label">RLM Expiration</label>
                                <input type="date" class="form-control" id="rlm_exp" name="rlm_exp" 
                                       value="<?php echo htmlspecialchars($employee['rlm_exp'] ?? ''); ?>">
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
                                    maxlength="12"
                                    placeholder="02-1179877-4" 
                                    value="<?php echo htmlspecialchars($employee['sss_no'] ?? ''); ?>"
                                >
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
                                    maxlength="14"
                                    placeholder="1210-9087-6528" 
                                    value="<?php echo htmlspecialchars($employee['pagibig_no'] ?? ''); ?>"
                                >
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
                                    maxlength="15"
                                    placeholder="360-889-408-000" 
                                    value="<?php echo htmlspecialchars($employee['tin_number'] ?? ''); ?>"
                                >
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
                                    maxlength="14"
                                    placeholder="21-200190443-1" 
                                    value="<?php echo htmlspecialchars($employee['philhealth_no'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions d-flex justify-content-end">
                        <a href="?page=employees" class="btn btn-outline-secondary me-2">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Update Employee
                        </button>
                    </div>
                </form>
        </div>
    </div>
</div>

<style>
.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--interface-text, #212529);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--interface-border, #dee2e6);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enforce numeric-only inputs
    const numericInputs = document.querySelectorAll('.numeric-only');
    numericInputs.forEach(input => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/[^0-9]/g, '');
        });
    });

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

    // Phone formatting: PH mobile format - must start with 9, 10 digits total
    const limitDigits = (val, len) => val.replace(/\D/g, '').slice(0, len);
    
    const bindPhoneSingle = (hiddenId, ccId, numId, numLen) => {
        const hidden = document.getElementById(hiddenId);
        const cc = document.getElementById(ccId);
        const num = document.getElementById(numId);
        if (!hidden || !cc || !num) return;
        
        const validatePHFormat = (value) => {
            if (value.length === 0) return value;
            if (value.length > 0 && value[0] !== '9') {
                return '9' + value.replace(/[^0-9]/g, '').slice(0, numLen - 1);
            }
            return value;
        };
        
        const sync = () => {
            let value = num.value.replace(/\D/g, '');
            if (value.startsWith('0')) {
                value = value.substring(1);
            }
            value = validatePHFormat(value);
            value = limitDigits(value, numLen);
            num.value = value;
            const n = num.value;
            hidden.value = n ? `${cc.value}-${n}` : '';
        };
        
        num.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('0')) {
                value = value.substring(1);
            }
            if (value.length === 1 && value[0] !== '9') {
                value = '9';
            } else if (value.length > 1 && value[0] !== '9') {
                value = '9' + value.replace(/[^0-9]/g, '').slice(1, numLen);
            }
            value = limitDigits(value, numLen);
            e.target.value = value;
            const n = e.target.value;
            hidden.value = n ? `${cc.value}-${n}` : '';
        });
        
        num.addEventListener('blur', sync);
    };

    bindPhoneSingle('cp_number', 'cc_cp', 'num_cp_full', 10);
    bindPhoneSingle('contact_person_number', 'cc_em', 'num_em_full', 10);

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
            const clean = input.value.replace(/\D/g, '');
            const formatted = formatWithBlocks(clean, cfg.blocks);
            input.value = formatted;
        });
    });

    // License number format enforcement
    const licenseInput = document.getElementById('license_no');
    if (licenseInput) {
        licenseInput.addEventListener('input', (e) => {
            let value = e.target.value.toUpperCase();
            value = value.replace(/[^A-Z0-9\-]/g, '');
            value = value.slice(0, 25);
            e.target.value = value;
        });
    }

    // Enforce letters-only on name fields (allow spaces, apostrophe, hyphen)
    const lettersOnly = (val) => val.replace(/[^A-Za-z\s'\-]/g, '');
    ['surname','first_name','middle_name'].forEach(id => {
        const input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('input', () => {
            input.value = lettersOnly(input.value);
        });
    });

    // Form submission handler - ensure all hidden fields are synced before submit
    const editForm = document.getElementById('editEmployeeForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            console.log('Form submitting...');
            
            // Normalize and sync phone numbers before submission
            const normalizePhNumber = (value) => {
                let digits = value.replace(/\D/g, '');
                if (digits.startsWith('0')) {
                    digits = digits.substring(1);
                }
                return digits;
            };
            const syncPhoneBeforeSubmit = (hiddenId, ccId, numId) => {
                const hidden = document.getElementById(hiddenId);
                const cc = document.getElementById(ccId);
                const num = document.getElementById(numId);
                if (hidden && cc && num) {
                    const n = normalizePhNumber(num.value || '');
                    num.value = n;
                    hidden.value = n ? `${cc.value}-${n}` : '';
                }
            };
            
            syncPhoneBeforeSubmit('cp_number', 'cc_cp', 'num_cp_full');
            syncPhoneBeforeSubmit('contact_person_number', 'cc_em', 'num_em_full');
            
            // Sync height before submission
            if (heightHidden && (heightFt || heightIn)) {
                syncHeight();
            }
            
            // Validate required fields before submission
            const requiredFields = ['first_name', 'surname', 'employee_no', 'employee_type', 'post', 'date_hired', 'status', 'num_cp_full'];
            let hasErrors = false;
            let firstInvalidField = null;
            
            // Clear previous invalid states
            editForm.querySelectorAll('.is-invalid').forEach(field => {
                field.classList.remove('is-invalid');
            });
            
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    let value = field.value ? field.value.trim() : '';
                    if (fieldName === 'num_cp_full') {
                        value = normalizePhNumber(value);
                        field.value = value;
                    }
                    if (!value) {
                        hasErrors = true;
                        field.classList.add('is-invalid');
                        field.style.borderColor = '#dc3545';
                        if (!firstInvalidField) {
                            firstInvalidField = field;
                        }
                    } else {
                        field.classList.remove('is-invalid');
                        field.style.borderColor = '';
                    }
                }
            });
            
            // Validate PH mobile format (must start with 9 and be 10 digits)
            const cpInput = document.getElementById('num_cp_full');
            if (cpInput) {
                const digits = normalizePhNumber(cpInput.value || '');
                const isValid = digits.length === 10 && digits.startsWith('9');
                if (!isValid) {
                    hasErrors = true;
                    cpInput.classList.add('is-invalid');
                    cpInput.style.borderColor = '#dc3545';
                    if (!firstInvalidField) {
                        firstInvalidField = cpInput;
                    }
                }
            }
            
            if (hasErrors) {
                e.preventDefault();
                e.stopPropagation();
                
                // Create or update error alert
                let errorAlert = document.getElementById('errorAlert');
                if (!errorAlert) {
                    errorAlert = document.createElement('div');
                    errorAlert.id = 'errorAlert';
                    errorAlert.className = 'alert alert-danger';
                    errorAlert.style.marginBottom = '1.5rem';
                    errorAlert.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i><strong>Please fix the following errors:</strong><ul class="mb-0 mt-2" id="errorList"></ul>';
                    editForm.insertBefore(errorAlert, editForm.firstChild);
                }
                
                const errorList = document.getElementById('errorList') || errorAlert.querySelector('ul');
                errorList.innerHTML = '';
                
                requiredFields.forEach(fieldName => {
                    const field = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
                    if (field && !field.value.trim()) {
                        const label = document.querySelector(`label[for="${fieldName}"]`) || 
                                     field.closest('.form-group')?.querySelector('label');
                        const fieldLabel = label ? label.textContent.replace('*', '').trim() : fieldName.replace('_', ' ');
                        const li = document.createElement('li');
                        li.textContent = fieldLabel + ' is required.';
                        errorList.appendChild(li);
                    }
                });
                
                if (cpInput) {
                    const digits = normalizePhNumber(cpInput.value || '');
                    if (digits && !(digits.length === 10 && digits.startsWith('9'))) {
                        const li = document.createElement('li');
                        li.textContent = 'Contact Phone Number must be 10 digits and start with 9.';
                        errorList.appendChild(li);
                    }
                }
                
                // Scroll to error message
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Focus on first invalid field
                if (firstInvalidField) {
                    setTimeout(() => {
                        firstInvalidField.focus();
                        firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 300);
                }
                
                return false;
            }
            
            // Show loading state
            const submitBtn = editForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
                
                // Re-enable after 10 seconds as a failsafe (in case of redirect issues)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 10000);
            }
            
            // Allow form to submit normally (don't prevent default)
            console.log('Form submission allowed, proceeding...');
        });
    }
});
</script>

