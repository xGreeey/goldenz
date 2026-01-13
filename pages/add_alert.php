<?php
$page_title = 'Add Alert - Golden Z-5 HR System';
$page = 'add_alert';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect_with_message('?page=add_alert', 'Security error: Invalid request.', 'danger');
    }
    
    $data = [
        'employee_id' => validate_input($_POST['employee_id'] ?? ''),
        'alert_type' => validate_input($_POST['alert_type'] ?? ''),
        'title' => validate_input($_POST['title'] ?? ''),
        'description' => validate_input($_POST['description'] ?? ''),
        'alert_date' => validate_input($_POST['alert_date'] ?? date('Y-m-d')),
        'due_date' => validate_input($_POST['due_date'] ?? ''),
        'priority' => validate_input($_POST['priority'] ?? 'medium'),
        'status' => 'active',
        'created_by' => $_SESSION['user_id'] ?? 1
    ];
    
    // Basic validation
    if (empty($data['employee_id']) || empty($data['alert_type']) || empty($data['title'])) {
        redirect_with_message('?page=add_alert', 'Please fill in all required fields.', 'warning');
    }
    
    try {
        create_alert($data);
        log_security_event('Alert Created', "Alert: {$data['title']} for Employee ID: {$data['employee_id']}");
        redirect_with_message('?page=alerts', 'Alert created successfully!', 'success');
    } catch (Exception $e) {
        log_security_event('Alert Creation Error', $e->getMessage());
        redirect_with_message('?page=add_alert', 'Error creating alert. Please try again.', 'danger');
    }
}

// Get all employees for dropdown
$employees = get_employees();
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Add New Alert</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label">Employee *</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['surname'] . ', ' . $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '')); ?>
                                    (<?php echo $employee['employee_no']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="alert_type" class="form-label">Alert Type *</label>
                            <select class="form-select" id="alert_type" name="alert_type" required>
                                <option value="">Select Alert Type</option>
                                <option value="license_expiry">License Expiry</option>
                                <option value="document_expiry">Document Expiry</option>
                                <option value="missing_document">Missing Document</option>
                                <option value="contract_expiry">Contract Expiry</option>
                                <option value="training_due">Training Due</option>
                                <option value="medical_expiry">Medical Expiry</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority *</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="alert_date" class="form-label">Alert Date</label>
                            <input type="date" class="form-control" id="alert_date" name="alert_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required placeholder="Enter alert title">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter detailed description of the alert"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?page=alerts" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Alerts
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Alert
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-fill title based on alert type
document.getElementById('alert_type').addEventListener('change', function() {
    const alertType = this.value;
    const titleField = document.getElementById('title');
    
    const titles = {
        'license_expiry': 'Security License Expiring Soon',
        'document_expiry': 'Document Expired',
        'missing_document': 'Missing Document Required',
        'contract_expiry': 'Employment Contract Expiring',
        'training_due': 'Training Required',
        'medical_expiry': 'Medical Clearance Expiring',
        'other': ''
    };
    
    if (titles[alertType]) {
        titleField.value = titles[alertType];
    }
});

// Set due date based on alert type
document.getElementById('alert_type').addEventListener('change', function() {
    const alertType = this.value;
    const dueDateField = document.getElementById('due_date');
    const today = new Date();
    
    if (alertType === 'license_expiry') {
        // Set due date to 30 days from now
        const futureDate = new Date(today);
        futureDate.setDate(today.getDate() + 30);
        dueDateField.value = futureDate.toISOString().split('T')[0];
    } else if (alertType === 'training_due') {
        // Set due date to 14 days from now
        const futureDate = new Date(today);
        futureDate.setDate(today.getDate() + 14);
        dueDateField.value = futureDate.toISOString().split('T')[0];
    } else if (alertType === 'medical_expiry') {
        // Set due date to 7 days from now
        const futureDate = new Date(today);
        futureDate.setDate(today.getDate() + 7);
        dueDateField.value = futureDate.toISOString().split('T')[0];
    }
});
</script>

