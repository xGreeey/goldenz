<?php
$page_title = 'Add New Employee Violation - Golden Z-5 HR System';
$page = 'add_violation';

// Helper function to get ordinal number (1st, 2nd, 3rd, etc.)
if (!function_exists('get_ordinal')) {
    function get_ordinal($number) {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }
        return $number . $ends[$number % 10];
    }
}

// Handle AJAX request for offense count
if (isset($_GET['action']) && $_GET['action'] === 'get_offense_count' && isset($_GET['employee_id']) && isset($_GET['violation_type_id'])) {
    header('Content-Type: application/json');
    $employee_id = (int)$_GET['employee_id'];
    $violation_type_id = (int)$_GET['violation_type_id'];
    
    if (function_exists('get_employee_violation_count')) {
        $count = get_employee_violation_count($employee_id, $violation_type_id);
        $offense_number = $count + 1;
        echo json_encode([
            'success' => true, 
            'offense_count' => $count,
            'offense_number' => $offense_number
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Function not available']);
    }
    exit;
}

// Get database connection
$pdo = get_db_connection();

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_id'])) {
    // Validate required fields
    if (empty($_POST['employee_id'])) {
        $errors[] = 'Employee is required.';
    }
    if (empty($_POST['violation_date'])) {
        $errors[] = 'Violation date is required.';
    }
    if (empty($_POST['violation_type_id'])) {
        $errors[] = 'Violation type is required.';
    }
    if (empty($_POST['severity']) || !in_array($_POST['severity'], ['Major', 'Minor'])) {
        $errors[] = 'Severity is required and must be Major or Minor.';
    }
    if (empty(trim($_POST['description'] ?? ''))) {
        $errors[] = 'Description is required.';
    }
    if (empty(trim($_POST['sanction'] ?? ''))) {
        $errors[] = 'Sanction is required.';
    }
    if (empty(trim($_POST['reported_by'] ?? ''))) {
        $errors[] = 'Reported by is required.';
    }
    
    // If no errors, insert the violation
    if (empty($errors)) {
        try {
            // Use the new add_employee_violation function which automatically calculates offense number and suggests sanction
            $violation_data = [
                'employee_id' => $_POST['employee_id'],
                'violation_type_id' => $_POST['violation_type_id'],
                'violation_date' => $_POST['violation_date'],
                'description' => trim($_POST['description']),
                'severity' => $_POST['severity'],
                'sanction' => !empty(trim($_POST['sanction'] ?? '')) ? trim($_POST['sanction']) : null,
                'sanction_date' => !empty($_POST['sanction_date']) ? $_POST['sanction_date'] : null,
                'reported_by' => trim($_POST['reported_by']),
                'status' => $_POST['status'] ?? 'Pending'
            ];
            
            $result = add_employee_violation($violation_data);
            
            $success = true;
            $message = 'Employee violation record added successfully! ' . 
                'This is the ' . get_ordinal($result['offense_number']) . ' offense for this violation type.';
            
            // Set session message
            $_SESSION['violation_created_success'] = true;
            $_SESSION['violation_created_message'] = $message;
            
            // Try to redirect - if headers already sent, we'll use JavaScript redirect in the page
            if (!headers_sent()) {
                // Clear any output buffers
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Location: ?page=violations&success=created');
                exit;
            }
            // If headers already sent, set a flag to use JavaScript redirect
            // The redirect will happen via JavaScript in the page output
            $redirect_needed = true;
            $redirect_url = '?page=violations&success=created';
        } catch (Exception $e) {
            $errors[] = 'Error saving violation: ' . $e->getMessage();
            error_log("Error adding violation: " . $e->getMessage());
        }
    }
}

// Get all employees for dropdown
$employees = get_employees();

// Get violation types from database with sanction information
$violation_types_list = [];
try {
    $stmt = $pdo->query("SELECT id, name, category, reference_no, first_offense, second_offense, third_offense, fourth_offense, fifth_offense 
                         FROM violation_types 
                         WHERE is_active = 1 
                         ORDER BY category, reference_no");
    $violation_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($violation_types as $vt) {
        $violation_types_list[$vt['id']] = [
            'name' => $vt['name'],
            'category' => $vt['category'],
            'reference_no' => $vt['reference_no'],
            'first_offense' => $vt['first_offense'],
            'second_offense' => $vt['second_offense'],
            'third_offense' => $vt['third_offense'],
            'fourth_offense' => $vt['fourth_offense'],
            'fifth_offense' => $vt['fifth_offense']
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching violation types: " . $e->getMessage());
}

// Get current user info for display
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;
$current_user_name = 'System Administrator';

if ($current_user_id && function_exists('get_db_connection')) {
    try {
        $pdo_user = get_db_connection();
        $stmt = $pdo_user->prepare("SELECT name, username FROM users WHERE id = ?");
        $stmt->execute([$current_user_id]);
        $user = $stmt->fetch();
        if ($user) {
            $current_user_name = !empty(trim($user['name'] ?? '')) 
                ? trim($user['name']) 
                : (!empty(trim($user['username'] ?? '')) 
                    ? trim($user['username']) 
                    : 'System Administrator');
        }
    } catch (Exception $e) {
        // Use default
    }
}
?>

<?php if (isset($redirect_needed) && $redirect_needed): ?>
<script type="text/javascript">
    window.location.href = "<?php echo htmlspecialchars($redirect_url); ?>";
</script>
<noscript>
    <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($redirect_url); ?>">
</noscript>
<?php endif; ?>

<div class="container-fluid hrdash">
    <div class="card hrdash-card hrdash-license">
        <div class="hrdash-card__header">
            <div>
                <h5 class="hrdash-card__title">Add New Employee Violation</h5>
                <div class="hrdash-card__subtitle">Record a new violation committed by an employee</div>
            </div>
            <div>
                <a href="?page=violations" class="btn btn-outline-modern">
                    <i class="fas fa-arrow-left me-2"></i>Back to Employee Violations
                </a>
            </div>
        </div>

        <div class="hrdash-license__body">
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

            <form method="POST" id="addViolationForm" class="add-violation-form">
                <div class="row g-3">
                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="form-section-title">
                            <i class="fas fa-info-circle me-2"></i>Violation Information
                        </h6>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo (isset($_POST['employee_id']) && $_POST['employee_id'] == $emp['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(($emp['surname'] ?? '') . ', ' . ($emp['first_name'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Select the employee who committed the violation</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Violation Date <span class="text-danger">*</span></label>
                        <input type="date" name="violation_date" class="form-control" required 
                               value="<?php echo isset($_POST['violation_date']) ? htmlspecialchars($_POST['violation_date']) : date('Y-m-d'); ?>">
                        <small class="form-text text-muted">Date when the violation occurred</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Violation Type <span class="text-danger">*</span></label>
                        <select name="violation_type_id" id="violation_type_select" class="form-select" required>
                            <option value="">Select Violation Type</option>
                            <?php 
                            $current_category = '';
                            foreach ($violation_types_list as $vt_id => $vt): 
                                if ($current_category !== $vt['category']):
                                    if ($current_category !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($vt['category']) . ' Violations">';
                                    $current_category = $vt['category'];
                                endif;
                            ?>
                                <option value="<?php echo $vt_id; ?>" 
                                        data-category="<?php echo htmlspecialchars($vt['category']); ?>"
                                        <?php echo (isset($_POST['violation_type_id']) && $_POST['violation_type_id'] == $vt_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vt['reference_no'] ?? ''); ?> - <?php echo htmlspecialchars($vt['name']); ?>
                                </option>
                            <?php 
                            endforeach; 
                            if ($current_category !== '') echo '</optgroup>';
                            ?>
                        </select>
                        <small class="form-text text-muted">Select the type of violation committed</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Severity <span class="text-danger">*</span></label>
                        <select name="severity" id="severity_select" class="form-select" required>
                            <option value="">Select Severity</option>
                            <option value="Major" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Major') ? 'selected' : ''; ?>>Major</option>
                            <option value="Minor" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Minor') ? 'selected' : ''; ?>>Minor</option>
                        </select>
                        <small class="form-text text-muted">Severity is automatically determined based on violation type</small>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" 
                                  placeholder="Provide detailed description of the violation..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <small class="form-text text-muted">Describe the circumstances and details of the violation</small>
                    </div>
                    
                    <!-- Sanction Information -->
                    <div class="col-12 mt-4">
                        <h6 class="form-section-title">
                            <i class="fas fa-gavel me-2"></i>Sanction Information
                        </h6>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Sanction <span class="text-danger">*</span></label>
                        <input type="text" name="sanction" id="sanction_input" class="form-control" required 
                               value="<?php echo isset($_POST['sanction']) ? htmlspecialchars($_POST['sanction']) : ''; ?>"
                               placeholder="e.g., 15 days suspension, Written Warning">
                        <small class="form-text text-muted">
                            <span id="sanction_hint">Sanction will be auto-suggested based on violation type and offense number</span>
                            <span id="offense_info" class="text-primary fw-bold" style="display: none;"></span>
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Sanction Date</label>
                        <input type="date" name="sanction_date" class="form-control"
                               value="<?php echo isset($_POST['sanction_date']) ? htmlspecialchars($_POST['sanction_date']) : ''; ?>">
                        <small class="form-text text-muted">Date when the sanction was applied (optional)</small>
                    </div>
                    
                    <!-- Additional Information -->
                    <div class="col-12 mt-4">
                        <h6 class="form-section-title">
                            <i class="fas fa-user-shield me-2"></i>Additional Information
                        </h6>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Reported By <span class="text-danger">*</span></label>
                        <input type="text" name="reported_by" class="form-control" required 
                               value="<?php echo isset($_POST['reported_by']) ? htmlspecialchars($_POST['reported_by']) : ''; ?>"
                               placeholder="Supervisor name">
                        <small class="form-text text-muted">Name of the person who reported the violation</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Pending" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Under Review" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Under Review') ? 'selected' : ''; ?>>Under Review</option>
                            <option value="Resolved" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                        <small class="form-text text-muted">Current status of the violation record</small>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between">
                        <a href="?page=violations" class="btn btn-outline-modern">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary-modern">
                            <i class="fas fa-save me-2"></i>Save Violation Record
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.add-violation-form {
    max-width: 100%;
}

.form-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
}

.form-section-title i {
    color: #64748b;
    font-size: 0.875rem;
}

.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-control,
.form-select {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
    outline: none;
}

.form-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
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
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: #ffffff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.btn-primary-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.35);
    background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
    color: #ffffff;
}

.form-actions {
    border-top: 1px solid #e2e8f0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Violation types data from PHP
    const violationTypes = <?php echo json_encode($violation_types_list); ?>;
    
    const violationTypeSelect = document.getElementById('violation_type_select');
    const severitySelect = document.getElementById('severity_select');
    const sanctionInput = document.getElementById('sanction_input');
    const employeeSelect = document.querySelector('select[name="employee_id"]');
    const offenseInfo = document.getElementById('offense_info');
    const sanctionHint = document.getElementById('sanction_hint');
    
    // Auto-set severity based on violation type selection
    if (violationTypeSelect && severitySelect) {
        violationTypeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const category = selectedOption.getAttribute('data-category');
                if (category) {
                    severitySelect.value = category;
                }
                updateSanctionSuggestion();
            }
        });
    }
    
    // Update sanction when employee or violation type changes
    if (employeeSelect) {
        employeeSelect.addEventListener('change', function() {
            if (this.value && violationTypeSelect.value) {
                updateSanctionSuggestion();
            }
        });
    }
    
    // Function to update sanction suggestion based on violation type and offense count
    async function updateSanctionSuggestion() {
        const employeeId = employeeSelect?.value;
        const violationTypeId = violationTypeSelect?.value;
        
        if (!employeeId || !violationTypeId) {
            offenseInfo.style.display = 'none';
            sanctionHint.style.display = 'inline';
            return;
        }
        
        try {
            // Get offense count via AJAX
            const response = await fetch('?page=add_violation&action=get_offense_count&employee_id=' + employeeId + '&violation_type_id=' + violationTypeId);
            const data = await response.json();
            
            if (data.success) {
                const offenseNumber = data.offense_number;
                const violationType = violationTypes[violationTypeId];
                
                if (violationType) {
                    let suggestedSanction = '';
                    switch(offenseNumber) {
                        case 1:
                            suggestedSanction = violationType.first_offense || '';
                            break;
                        case 2:
                            suggestedSanction = violationType.second_offense || violationType.first_offense || '';
                            break;
                        case 3:
                            suggestedSanction = violationType.third_offense || violationType.second_offense || violationType.first_offense || '';
                            break;
                        case 4:
                            suggestedSanction = violationType.fourth_offense || violationType.third_offense || violationType.second_offense || '';
                            break;
                        case 5:
                        default:
                            suggestedSanction = violationType.fifth_offense || violationType.fourth_offense || violationType.third_offense || '';
                            break;
                    }
                    
                    if (suggestedSanction) {
                        sanctionInput.value = suggestedSanction;
                        offenseInfo.textContent = 'This will be the ' + getOrdinal(offenseNumber) + ' offense for this violation type';
                        offenseInfo.style.display = 'inline';
                        sanctionHint.style.display = 'none';
                    } else {
                        offenseInfo.style.display = 'none';
                        sanctionHint.style.display = 'inline';
                    }
                }
            }
        } catch (error) {
            console.error('Error fetching offense count:', error);
            // Fallback: just show the first offense sanction
            const violationType = violationTypes[violationTypeId];
            if (violationType && violationType.first_offense) {
                sanctionInput.value = violationType.first_offense;
            }
        }
    }
    
    // Helper function to get ordinal number
    function getOrdinal(number) {
        const ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((number % 100 >= 11) && (number % 100 <= 13)) {
            return number + 'th';
        }
        return number + ends[number % 10];
    }
    
    // Initial update if form is pre-filled
    if (violationTypeSelect?.value && employeeSelect?.value) {
        updateSanctionSuggestion();
    }
});
</script>
