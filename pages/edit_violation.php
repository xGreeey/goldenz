<?php
$page_title = 'Edit Employee Violation - Golden Z-5 HR System';
$page = 'edit_violation';

// Get violation ID from URL
$violation_id = $_GET['id'] ?? 0;

if (!$violation_id) {
    redirect_with_message('?page=violations', 'Violation ID is required.', 'danger');
}

// Get database connection
$pdo = get_db_connection();

// Get violation data
try {
    $stmt = $pdo->prepare("
        SELECT ev.*, 
               e.first_name, e.surname, e.post,
               CONCAT(e.surname, ', ', e.first_name) as employee_name,
               vt.name as violation_type_name,
               vt.category as violation_category
        FROM employee_violations ev
        LEFT JOIN employees e ON ev.employee_id = e.id
        LEFT JOIN violation_types vt ON ev.violation_type_id = vt.id
        WHERE ev.id = ?
    ");
    $stmt->execute([$violation_id]);
    $violation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$violation) {
        redirect_with_message('?page=violations', 'Violation not found.', 'danger');
    }
} catch (PDOException $e) {
    error_log("Error fetching violation: " . $e->getMessage());
    redirect_with_message('?page=violations', 'Error loading violation record.', 'danger');
}

// Handle form submission
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    // If no errors, update in database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE employee_violations SET
                    employee_id = ?,
                    violation_type_id = ?,
                    violation_date = ?,
                    description = ?,
                    severity = ?,
                    sanction = ?,
                    sanction_date = ?,
                    reported_by = ?,
                    status = ?
                WHERE id = ?
            ");
            
            $sanction_date = !empty($_POST['sanction_date']) ? $_POST['sanction_date'] : null;
            
            $stmt->execute([
                $_POST['employee_id'],
                $_POST['violation_type_id'],
                $_POST['violation_date'],
                trim($_POST['description']),
                $_POST['severity'],
                trim($_POST['sanction']),
                $sanction_date,
                trim($_POST['reported_by']),
                $_POST['status'] ?? 'Pending',
                $violation_id
            ]);
            
            // Log to audit trail if function exists
            if (function_exists('log_audit_event')) {
                $old_data = $violation;
                $new_data = [
                    'employee_id' => $_POST['employee_id'],
                    'violation_type_id' => $_POST['violation_type_id'],
                    'violation_date' => $_POST['violation_date'],
                    'description' => trim($_POST['description']),
                    'severity' => $_POST['severity'],
                    'sanction' => trim($_POST['sanction']),
                    'sanction_date' => $sanction_date,
                    'reported_by' => trim($_POST['reported_by']),
                    'status' => $_POST['status'] ?? 'Pending'
                ];
                $current_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;
                log_audit_event('UPDATE', 'employee_violations', $violation_id, $old_data, $new_data, $current_user_id);
            }
            
            redirect_with_message('?page=violations', 'Employee violation record updated successfully!', 'success');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
            error_log('Edit Violation Error: ' . $e->getMessage());
        }
    }
}

// Get all employees for dropdown
$employees = get_employees();

// Get violation types from database
$violation_types_list = [];
try {
    $stmt = $pdo->query("SELECT id, name, category, reference_no FROM violation_types WHERE is_active = 1 ORDER BY category, reference_no");
    $violation_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($violation_types as $vt) {
        $violation_types_list[$vt['id']] = [
            'name' => $vt['name'],
            'category' => $vt['category'],
            'reference_no' => $vt['reference_no']
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

<div class="container-fluid hrdash">
    <!-- Page Header -->
    <header class="edit-violation-header">
        <nav aria-label="Breadcrumb navigation">
            <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="?page=violations" itemprop="item" class="breadcrumb-link">
                        <span itemprop="name">Employee Violations</span>
                    </a>
                    <meta itemprop="position" content="1" />
                </li>
                <li class="breadcrumb-item active" aria-current="page" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span itemprop="name">Edit Violation</span>
                    <meta itemprop="position" content="2" />
                </li>
            </ol>
        </nav>
        <div class="page-header-content">
            <h1 class="page-title">Edit Employee Violation</h1>
            <p class="page-description">Update violation record details and information</p>
        </div>
    </header>

    <!-- Main Form Section -->
    <section class="edit-violation-section" aria-labelledby="edit-violation-heading">
        <div class="edit-violation-card">
            <header class="card-header">
                <div class="card-header-content">
                    <h2 id="edit-violation-heading" class="card-title">Violation Record</h2>
                    <p class="card-subtitle">Employee: <?php echo htmlspecialchars($violation['employee_name'] ?? 'N/A'); ?></p>
                </div>
                <div class="card-header-actions">
                    <a href="?page=violations" class="btn btn-secondary btn-sm" aria-label="Go back to employee violations">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Back</span>
                    </a>
                </div>
            </header>

            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error" role="alert" aria-live="assertive">
                        <div class="alert-icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/>
                                <path d="M10 6v4M10 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="alert-content">
                            <strong class="alert-title">Please fix the following errors:</strong>
                            <ul class="alert-list">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" id="editViolationForm" class="violation-form" novalidate aria-label="Edit employee violation form">
                    <!-- Violation Information Section -->
                    <fieldset class="form-section">
                        <legend class="form-section-title">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M10 6v4M10 14h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span>Violation Information</span>
                        </legend>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="employee_id" class="form-label">
                                    Employee
                                    <span class="required-indicator" aria-label="required">*</span>
                                </label>
                                <select 
                                    name="employee_id" 
                                    id="employee_id" 
                                    class="form-select" 
                                    required
                                    aria-describedby="employee_id-description"
                                    aria-invalid="<?php echo isset($errors) && in_array('Employee is required.', $errors) ? 'true' : 'false'; ?>">
                                    <option value="">Select Employee&hellip;</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>" 
                                                <?php echo (isset($_POST['employee_id']) ? ($_POST['employee_id'] == $emp['id']) : ($violation['employee_id'] == $emp['id'])) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(($emp['surname'] ?? '') . ', ' . ($emp['first_name'] ?? '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span id="employee_id-description" class="form-description">Select the employee who committed the violation</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="violation_date" class="form-label">
                                    Violation Date
                                    <span class="required-indicator" aria-label="required">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    name="violation_date" 
                                    id="violation_date" 
                                    class="form-input" 
                                    required
                                    value="<?php echo isset($_POST['violation_date']) ? htmlspecialchars($_POST['violation_date']) : htmlspecialchars($violation['violation_date']); ?>"
                                    aria-describedby="violation_date-description"
                                    aria-invalid="<?php echo isset($errors) && in_array('Violation date is required.', $errors) ? 'true' : 'false'; ?>">
                                <span id="violation_date-description" class="form-description">Date when the violation occurred</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="violation_type_id" class="form-label">
                                    Violation Type
                                    <span class="required-indicator" aria-label="required">*</span>
                                </label>
                                <select 
                                    name="violation_type_id" 
                                    id="violation_type_select" 
                                    class="form-select" 
                                    required
                                    aria-describedby="violation_type_id-description"
                                    aria-invalid="<?php echo isset($errors) && in_array('Violation type is required.', $errors) ? 'true' : 'false'; ?>">
                                    <option value="">Select Violation Type&hellip;</option>
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
                                                <?php echo (isset($_POST['violation_type_id']) ? ($_POST['violation_type_id'] == $vt_id) : ($violation['violation_type_id'] == $vt_id)) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vt['reference_no'] ?? ''); ?> &mdash; <?php echo htmlspecialchars($vt['name']); ?>
                                        </option>
                                    <?php 
                                    endforeach; 
                                    if ($current_category !== '') echo '</optgroup>';
                                    ?>
                                </select>
                                <span id="violation_type_id-description" class="form-description">Select the type of violation committed</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="severity_select" class="form-label">
                                    Severity
                                    <span class="required-indicator" aria-label="required">*</span>
                                </label>
                                <select 
                                    name="severity" 
                                    id="severity_select" 
                                    class="form-select" 
                                    required
                                    aria-describedby="severity_select-description"
                                    aria-invalid="<?php echo isset($errors) && in_array('Severity is required and must be Major or Minor.', $errors) ? 'true' : 'false'; ?>">
                                    <option value="">Select Severity&hellip;</option>
                                    <option value="Major" <?php echo (isset($_POST['severity']) ? ($_POST['severity'] === 'Major') : ($violation['severity'] === 'Major')) ? 'selected' : ''; ?>>Major</option>
                                    <option value="Minor" <?php echo (isset($_POST['severity']) ? ($_POST['severity'] === 'Minor') : ($violation['severity'] === 'Minor')) ? 'selected' : ''; ?>>Minor</option>
                                </select>
                                <span id="severity_select-description" class="form-description">Severity is automatically determined based on violation type</span>
                            </div>
                            
                            <div class="form-group form-group-full">
                                <label for="description" class="form-label">
                                    Description
                                    <span class="required-indicator" aria-label="required">*</span>
                                </label>
                                <textarea 
                                    name="description" 
                                    id="description" 
                                    class="form-textarea" 
                                    rows="4" 
                                    placeholder="Provide detailed description of the violation&hellip;" 
                                    required
                                    aria-describedby="description-description"
                                    aria-invalid="<?php echo isset($errors) && in_array('Description is required.', $errors) ? 'true' : 'false'; ?>"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($violation['description'] ?? ''); ?></textarea>
                                <span id="description-description" class="form-description">Describe the circumstances and details of the violation</span>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Sanction Information Section -->
                    <fieldset class="form-section">
                        <legend class="form-section-title">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M3 4h14M3 8h14M3 12h14M3 16h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span>Sanction Information</span>
                        </legend>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="sanction" class="form-label">
                                    Sanction
                                    <span class="required-indicator" aria-label="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="sanction" 
                                    id="sanction" 
                                    class="form-input" 
                                    required
                                    value="<?php echo isset($_POST['sanction']) ? htmlspecialchars($_POST['sanction']) : htmlspecialchars($violation['sanction'] ?? ''); ?>"
                                    placeholder="e.g., 15 days suspension, Written Warning&hellip;"
                                    aria-describedby="sanction-description"
                                    aria-invalid="<?php echo isset($errors) && in_array('Sanction is required.', $errors) ? 'true' : 'false'; ?>">
                                <span id="sanction-description" class="form-description">Enter the sanction applied for this violation</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="sanction_date" class="form-label">Sanction Date</label>
                                <input 
                                    type="date" 
                                    name="sanction_date" 
                                    id="sanction_date" 
                                    class="form-input"
                                    value="<?php echo isset($_POST['sanction_date']) ? htmlspecialchars($_POST['sanction_date']) : htmlspecialchars($violation['sanction_date'] ?? ''); ?>"
                                    aria-describedby="sanction_date-description">
                                <span id="sanction_date-description" class="form-description">Date when the sanction was applied (optional)</span>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Additional Information Section -->
                    <fieldset class="form-section">
                        <legend class="form-section-title">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle cx="10" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M5 18c0-2.8 2.2-5 5-5s5 2.2 5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span>Additional Information</span>
                        </legend>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="reported_by" class="form-label">
                                    Reported By
                                    <span class="required-indicator" aria-label="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="reported_by" 
                                    id="reported_by" 
                                    class="form-input" 
                                    required
                                    value="<?php echo isset($_POST['reported_by']) ? htmlspecialchars($_POST['reported_by']) : htmlspecialchars($violation['reported_by'] ?? ''); ?>"
                                    placeholder="Supervisor name&hellip;"
                                    aria-describedby="reported_by-description"
                                    aria-invalid="<?php echo isset($errors) && in_array('Reported by is required.', $errors) ? 'true' : 'false'; ?>">
                                <span id="reported_by-description" class="form-description">Name of the person who reported the violation</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select 
                                    name="status" 
                                    id="status" 
                                    class="form-select"
                                    aria-describedby="status-description">
                                    <option value="Pending" <?php echo (isset($_POST['status']) ? ($_POST['status'] === 'Pending') : (($violation['status'] ?? 'Pending') === 'Pending')) ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Under Review" <?php echo (isset($_POST['status']) ? ($_POST['status'] === 'Under Review') : (($violation['status'] ?? '') === 'Under Review')) ? 'selected' : ''; ?>>Under Review</option>
                                    <option value="Resolved" <?php echo (isset($_POST['status']) ? ($_POST['status'] === 'Resolved') : (($violation['status'] ?? '') === 'Resolved')) ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                                <span id="status-description" class="form-description">Current status of the violation record</span>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="?page=violations" class="btn btn-secondary">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M4 8h8M8 4l-4 4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Cancel</span>
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M13.333 4L6 11.333 2.667 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Update Violation Record</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<style>
/* Edit Violation Page - Design System Compliant */
/* Based on 8pt grid system and Web Interface Guidelines */

/* Design Tokens - 8pt Grid System */
:root {
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --spacing-xl: 32px;
    --spacing-2xl: 48px;
    --spacing-3xl: 64px;
    
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    
    --transition-base: 150ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
    
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    
    --focus-ring: 0 0 0 3px rgba(31, 178, 213, 0.2);
    --error-color: #ef4444;
    --error-bg: rgba(239, 68, 68, 0.1);
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Screen reader only */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

/* Page Header */
.edit-violation-header {
    margin-bottom: var(--spacing-xl);
}

.breadcrumb {
    display: flex;
    flex-wrap: wrap;
    padding: 0;
    margin-bottom: var(--spacing-md);
    list-style: none;
    background: transparent;
    font-size: 0.875rem;
    line-height: 1.5;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "/";
    display: inline-block;
    padding: 0 var(--spacing-sm);
    color: #94a3b8;
}

.breadcrumb-link {
    color: var(--primary-color, #1fb2d5);
    text-decoration: none;
    transition: color var(--transition-base);
}

.breadcrumb-link:hover {
    color: var(--primary-dark, #0e708c);
    text-decoration: underline;
}

.breadcrumb-link:focus-visible {
    outline: 2px solid var(--primary-color, #1fb2d5);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
}

.breadcrumb-item.active {
    color: #64748b;
}

.page-header-content {
    margin-top: var(--spacing-md);
}

.page-title {
    font-size: 1.875rem;
    font-weight: 700;
    line-height: 1.2;
    color: #0f172a;
    margin: 0 0 var(--spacing-sm) 0;
    text-wrap: balance;
}

.page-description {
    font-size: 1rem;
    line-height: 1.5;
    color: #64748b;
    margin: 0;
}

/* Card Section */
.edit-violation-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: var(--spacing-lg);
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.card-header-content {
    flex: 1;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.3;
    color: #0f172a;
    margin: 0 0 var(--spacing-xs) 0;
}

.card-subtitle {
    font-size: 0.875rem;
    line-height: 1.5;
    color: #64748b;
    margin: 0;
}

.card-header-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-left: var(--spacing-md);
}

.card-body {
    padding: var(--spacing-lg);
}

/* Alert */
.alert {
    display: flex;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-lg);
}

.alert-error {
    background-color: var(--error-bg);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: var(--error-color);
}

.alert-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
}

.alert-icon svg {
    width: 100%;
    height: 100%;
}

.alert-content {
    flex: 1;
    min-width: 0;
}

.alert-title {
    display: block;
    font-weight: 600;
    margin-bottom: var(--spacing-sm);
}

.alert-list {
    margin: 0;
    padding-left: var(--spacing-lg);
}

.alert-list li {
    margin-bottom: var(--spacing-xs);
}

.alert-list li:last-child {
    margin-bottom: 0;
}

/* Form */
.violation-form {
    max-width: 100%;
}

.form-section {
    border: none;
    padding: 0;
    margin: 0 0 var(--spacing-xl) 0;
}

.form-section:last-of-type {
    margin-bottom: var(--spacing-lg);
}

.form-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #334155;
    margin: 0 0 var(--spacing-md) 0;
    padding-bottom: var(--spacing-sm);
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    width: 100%;
}

.form-section-title svg {
    width: 20px;
    height: 20px;
    color: var(--primary-color, #1fb2d5);
    flex-shrink: 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-md);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.form-group-full {
    grid-column: 1 / -1;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.required-indicator {
    color: var(--error-color);
    font-weight: 700;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: 0.875rem;
    line-height: 1.5;
    color: #0f172a;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: var(--radius-md);
    transition: border-color var(--transition-base), box-shadow var(--transition-base);
    touch-action: manipulation;
    font-family: inherit;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary-color, #1fb2d5);
    box-shadow: var(--focus-ring);
}

.form-input[aria-invalid="true"],
.form-select[aria-invalid="true"],
.form-textarea[aria-invalid="true"] {
    border-color: var(--error-color);
}

.form-input[aria-invalid="true"]:focus,
.form-select[aria-invalid="true"]:focus,
.form-textarea[aria-invalid="true"]:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-input::placeholder,
.form-textarea::placeholder {
    color: #94a3b8;
}

.form-description {
    font-size: 0.75rem;
    line-height: 1.4;
    color: #64748b;
    margin-top: var(--spacing-xs);
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1.5;
    text-align: center;
    text-decoration: none;
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: background-color var(--transition-base), border-color var(--transition-base), color var(--transition-base), transform var(--transition-base), box-shadow var(--transition-base);
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.btn:focus-visible {
    outline: 2px solid var(--primary-color, #1fb2d5);
    outline-offset: 2px;
}

.btn-secondary {
    background-color: #ffffff;
    border-color: #e2e8f0;
    color: #334155;
}

.btn-secondary:hover {
    background-color: #f8fafc;
    border-color: #cbd5e1;
}

.btn-secondary:active {
    transform: translateY(1px);
}

.btn-primary {
    background-color: var(--primary-color, #1fb2d5);
    border-color: var(--primary-color, #1fb2d5);
    color: #ffffff;
}

.btn-primary:hover {
    background-color: var(--primary-dark, #0e708c);
    border-color: var(--primary-dark, #0e708c);
}

.btn-primary:active {
    transform: translateY(1px);
}

.btn svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.btn-sm {
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: 0.8125rem;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--spacing-md);
    padding-top: var(--spacing-lg);
    margin-top: var(--spacing-lg);
    border-top: 1px solid #e2e8f0;
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        gap: var(--spacing-md);
    }
    
    .card-header-actions {
        margin-left: 0;
        width: 100%;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editViolationForm');
        const violationTypeSelect = document.getElementById('violation_type_select');
        const severitySelect = document.getElementById('severity_select');
        
        if (!form) return;
        
        // Auto-set severity based on violation type selection
        if (violationTypeSelect && severitySelect) {
            violationTypeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    const category = selectedOption.getAttribute('data-category');
                    if (category && (category === 'Major' || category === 'Minor')) {
                        severitySelect.value = category;
                        
                        // Announce change to screen readers
                        const announcement = document.createElement('div');
                        announcement.setAttribute('role', 'status');
                        announcement.setAttribute('aria-live', 'polite');
                        announcement.className = 'sr-only';
                        announcement.textContent = `Severity automatically set to ${category}`;
                        document.body.appendChild(announcement);
                        
                        setTimeout(() => {
                            document.body.removeChild(announcement);
                        }, 1000);
                    }
                }
            });
        }
        
        // Form validation
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                
                // Focus first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'center' });
                }
            }
            
            form.classList.add('was-validated');
        });
        
        // Real-time validation feedback
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.setAttribute('aria-invalid', 'false');
                } else {
                    this.setAttribute('aria-invalid', 'true');
                }
            });
        });
    });
})();
</script>
