<?php
// Get post ID from URL
$post_id = $_GET['id'] ?? 0;

if (!$post_id) {
    redirect_with_message('?page=posts', 'Post ID is required.', 'danger');
}

// Get post data
$post = get_post_by_id($post_id);

if (!$post) {
    redirect_with_message('?page=posts', 'Post not found.', 'danger');
}

// Handle form submission
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $post_data = [
        'post_title' => trim($_POST['post_title'] ?? ''),
        'post_code' => !empty($_POST['post_code']) ? trim($_POST['post_code']) : null,
        'department' => !empty($_POST['department']) ? trim($_POST['department']) : null,
        'employee_type' => trim($_POST['employee_type'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'description' => !empty($_POST['description']) ? trim($_POST['description']) : null,
        'requirements' => !empty($_POST['requirements']) ? trim($_POST['requirements']) : null,
        'responsibilities' => !empty($_POST['responsibilities']) ? trim($_POST['responsibilities']) : null,
        'required_count' => (int)($_POST['required_count'] ?? 1),
        'filled_count' => (int)($_POST['filled_count'] ?? 0),
        'priority' => in_array($_POST['priority'] ?? 'Medium', ['Low', 'Medium', 'High', 'Urgent']) ? $_POST['priority'] : 'Medium',
        'status' => in_array($_POST['status'] ?? 'Active', ['Active', 'Inactive', 'Closed']) ? $_POST['status'] : 'Active',
        'shift_type' => !empty($_POST['shift_type']) ? trim($_POST['shift_type']) : null,
        'work_hours' => !empty($_POST['work_hours']) ? trim($_POST['work_hours']) : null,
        'salary_range' => !empty($_POST['salary_range']) ? trim($_POST['salary_range']) : null,
        'benefits' => !empty($_POST['benefits']) ? trim($_POST['benefits']) : null,
        'reporting_to' => !empty($_POST['reporting_to']) ? trim($_POST['reporting_to']) : null,
        'expires_at' => !empty($_POST['expires_at']) ? trim($_POST['expires_at']) : null
    ];
    
    // Validate required fields
    if (empty($post_data['post_title'])) {
        $errors[] = 'Post Title is required.';
    }
    if (empty($post_data['employee_type'])) {
        $errors[] = 'Employee Type is required.';
    }
    if (empty($post_data['location'])) {
        $errors[] = 'Location is required.';
    }
    if ($post_data['required_count'] <= 0) {
        $errors[] = 'Number of positions must be greater than 0.';
    }
    if ($post_data['filled_count'] < 0) {
        $errors[] = 'Filled count cannot be negative.';
    }
    if ($post_data['filled_count'] > $post_data['required_count']) {
        $errors[] = 'Filled count cannot exceed required count.';
    }
    
    if (empty($errors)) {
        if (update_post($post_id, $post_data)) {
            $_SESSION['message'] = 'Post updated successfully!';
            $_SESSION['message_type'] = 'success';
            header('Location: ?page=posts');
            exit;
        } else {
            $errors[] = 'Failed to update post. Please try again.';
        }
    }
}
?>

<div class="add-post-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>Edit Post</h1>
            <p class="text-muted">Update post information and details</p>
        </div>
        <div class="page-actions">
            <a href="?page=posts" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>Back to Posts
            </a>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2" aria-hidden="true"></i>
            <strong>Error:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Edit Post Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Post Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="editPostForm" novalidate>
                        <input type="hidden" name="action" value="update">
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="post_title" class="form-label">Post Title *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="post_title" 
                                       name="post_title" 
                                       value="<?php echo htmlspecialchars($post['post_title'] ?? ''); ?>" 
                                       required>
                                <div class="form-text">e.g., Security Guard - Main Gate</div>
                            </div>
                            <div class="col-md-6">
                                <label for="post_code" class="form-label">Post Code</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="post_code" 
                                       name="post_code" 
                                       value="<?php echo htmlspecialchars($post['post_code'] ?? ''); ?>">
                                <div class="form-text">e.g., SG001, LG001, SO001 (optional)</div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="">Select Department (Optional)</option>
                                    <option value="Security" <?php echo ($post['department'] ?? '') === 'Security' ? 'selected' : ''; ?>>Security</option>
                                    <option value="Administration" <?php echo ($post['department'] ?? '') === 'Administration' ? 'selected' : ''; ?>>Administration</option>
                                    <option value="Operations" <?php echo ($post['department'] ?? '') === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                    <option value="Management" <?php echo ($post['department'] ?? '') === 'Management' ? 'selected' : ''; ?>>Management</option>
                                    <option value="Support" <?php echo ($post['department'] ?? '') === 'Support' ? 'selected' : ''; ?>>Support</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="employee_type" class="form-label">Employee Type *</label>
                                <select class="form-select" id="employee_type" name="employee_type" required>
                                    <option value="">Select Employee Type</option>
                                    <option value="SG" <?php echo ($post['employee_type'] ?? '') === 'SG' ? 'selected' : ''; ?>>Security Guard (SG)</option>
                                    <option value="LG" <?php echo ($post['employee_type'] ?? '') === 'LG' ? 'selected' : ''; ?>>Lady Guard (LG)</option>
                                    <option value="SO" <?php echo ($post['employee_type'] ?? '') === 'SO' ? 'selected' : ''; ?>>Security Officer (SO)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="location" class="form-label">Assignment Location *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="location" 
                                   name="location" 
                                   value="<?php echo htmlspecialchars($post['location'] ?? ''); ?>" 
                                   required>
                            <div class="form-text">e.g., Main Entrance Gate, Building A - 3rd Floor</div>
                        </div>

                        <!-- Job Details -->
                        <div class="mb-4">
                            <label for="description" class="form-label">Job Description</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4" 
                                      placeholder="Describe the main duties and responsibilities of this position..."><?php echo htmlspecialchars($post['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="requirements" class="form-label">Requirements & Qualifications</label>
                            <textarea class="form-control" 
                                      id="requirements" 
                                      name="requirements" 
                                      rows="3" 
                                      placeholder="List the required qualifications, skills, and experience..."><?php echo htmlspecialchars($post['requirements'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="responsibilities" class="form-label">Key Responsibilities</label>
                            <textarea class="form-control" 
                                      id="responsibilities" 
                                      name="responsibilities" 
                                      rows="3" 
                                      placeholder="List the main responsibilities and duties..."><?php echo htmlspecialchars($post['responsibilities'] ?? ''); ?></textarea>
                        </div>

                        <!-- Position Details -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="required_count" class="form-label">Number of Positions Needed *</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="required_count" 
                                       name="required_count" 
                                       value="<?php echo htmlspecialchars($post['required_count'] ?? 1); ?>" 
                                       min="1" 
                                       required>
                            </div>
                            <div class="col-md-3">
                                <label for="filled_count" class="form-label">Filled Positions</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="filled_count" 
                                       name="filled_count" 
                                       value="<?php echo htmlspecialchars($post['filled_count'] ?? 0); ?>" 
                                       min="0">
                                <div class="form-text">Number of positions already filled</div>
                            </div>
                            <div class="col-md-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="Low" <?php echo ($post['priority'] ?? 'Medium') === 'Low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="Medium" <?php echo ($post['priority'] ?? 'Medium') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="High" <?php echo ($post['priority'] ?? 'Medium') === 'High' ? 'selected' : ''; ?>>High</option>
                                    <option value="Urgent" <?php echo ($post['priority'] ?? 'Medium') === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="Active" <?php echo ($post['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo ($post['status'] ?? 'Active') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="Closed" <?php echo ($post['status'] ?? 'Active') === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                        </div>

                        <!-- Work Schedule -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="shift_type" class="form-label">Shift Type</label>
                                <select class="form-select" id="shift_type" name="shift_type">
                                    <option value="Day" <?php echo ($post['shift_type'] ?? 'Day') === 'Day' ? 'selected' : ''; ?>>Day</option>
                                    <option value="Night" <?php echo ($post['shift_type'] ?? 'Day') === 'Night' ? 'selected' : ''; ?>>Night</option>
                                    <option value="Rotating" <?php echo ($post['shift_type'] ?? 'Day') === 'Rotating' ? 'selected' : ''; ?>>Rotating</option>
                                    <option value="Flexible" <?php echo ($post['shift_type'] ?? 'Day') === 'Flexible' ? 'selected' : ''; ?>>Flexible</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="work_hours" class="form-label">Work Hours</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="work_hours" 
                                       name="work_hours" 
                                       value="<?php echo htmlspecialchars($post['work_hours'] ?? '8 hours'); ?>" 
                                       placeholder="e.g., 8 hours, 12 hours">
                            </div>
                        </div>

                        <!-- Compensation -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="salary_range" class="form-label">Salary Range</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="salary_range" 
                                       name="salary_range" 
                                       value="<?php echo htmlspecialchars($post['salary_range'] ?? ''); ?>" 
                                       placeholder="e.g., 15000-20000">
                            </div>
                            <div class="col-md-6">
                                <label for="reporting_to" class="form-label">Reports To</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="reporting_to" 
                                       name="reporting_to" 
                                       value="<?php echo htmlspecialchars($post['reporting_to'] ?? ''); ?>" 
                                       placeholder="e.g., Security Supervisor">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="benefits" class="form-label">Benefits & Perks</label>
                            <textarea class="form-control" 
                                      id="benefits" 
                                      name="benefits" 
                                      rows="2" 
                                      placeholder="List benefits, allowances, and perks..."><?php echo htmlspecialchars($post['benefits'] ?? ''); ?></textarea>
                        </div>

                        <!-- Expiration Date -->
                        <div class="mb-4">
                            <label for="expires_at" class="form-label">Post Expiration Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="expires_at" 
                                   name="expires_at" 
                                   value="<?php echo !empty($post['expires_at']) ? htmlspecialchars($post['expires_at']) : ''; ?>">
                            <div class="form-text">Leave empty if post has no expiration date</div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2" aria-hidden="true"></i>Update Post
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-refresh me-2" aria-hidden="true"></i>Reset
                            </button>
                            <a href="?page=posts" class="btn btn-outline-danger">
                                <i class="fas fa-times me-2" aria-hidden="true"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Panel -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Help & Guidelines</h6>
                </div>
                <div class="card-body">
                    <h6>Post Code Format</h6>
                    <ul class="list-unstyled small">
                        <li><strong>SG</strong> - Security Guard (SG001, SG002...)</li>
                        <li><strong>LG</strong> - Lady Guard (LG001, LG002...)</li>
                        <li><strong>SO</strong> - Security Officer (SO001, SO002...)</li>
                    </ul>

                    <h6 class="mt-3">Priority Levels</h6>
                    <ul class="list-unstyled small">
                        <li><span class="badge bg-danger">Urgent</span> - Critical positions</li>
                        <li><span class="badge bg-warning">High</span> - Important positions</li>
                        <li><span class="badge bg-info">Medium</span> - Standard positions</li>
                        <li><span class="badge bg-secondary">Low</span> - Optional positions</li>
                    </ul>

                    <h6 class="mt-3">Department Guidelines</h6>
                    <ul class="list-unstyled small">
                        <li><strong>Security</strong> - All security-related positions</li>
                        <li><strong>Administration</strong> - Office and admin roles</li>
                        <li><strong>Operations</strong> - Operational and field roles</li>
                        <li><strong>Management</strong> - Supervisory and management roles</li>
                        <li><strong>Support</strong> - Support and auxiliary roles</li>
                    </ul>
                </div>
            </div>

            <!-- Post Info Card -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Post Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Post ID:</small>
                        <strong class="d-block"><?php echo htmlspecialchars($post['id']); ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Created:</small>
                        <strong class="d-block"><?php echo !empty($post['created_at']) ? date('M d, Y', strtotime($post['created_at'])) : 'N/A'; ?></strong>
                    </div>
                    <?php if (!empty($post['updated_at'])): ?>
                    <div class="mb-2">
                        <small class="text-muted">Last Updated:</small>
                        <strong class="d-block"><?php echo date('M d, Y', strtotime($post['updated_at'])); ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.add-post-container {
    padding: var(--spacing-xl);
    max-width: 100%;
    background: var(--interface-bg);
}

.form-label {
    font-weight: 600;
    color: var(--interface-text);
    margin-bottom: var(--spacing-sm);
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

.card-header h5,
.card-header h6 {
    color: var(--interface-text);
    font-weight: 600;
}

/* Card styling to match HR admin dashboard */
.card-modern,
.card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    background: #ffffff;
    overflow: hidden;
    transition: all 0.3s ease;
}

.card-modern:hover,
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
}

/* Page Header */
.add-post-container .page-header,
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1.5rem 2rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
}

.add-post-container .page-header .page-title h1,
.page-header .page-title h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
}

.add-post-container .page-header .page-title .text-muted,
.page-header .page-title .text-muted {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
}

.add-post-container .page-header .page-actions,
.page-header .page-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

/* Ensure all Font Awesome icons are visible */
.add-post-container i[class*="fa-"],
.add-post-container [class*="fa-"] {
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free', 'Font Awesome 6 Brands', 'Font Awesome 5 Brands' !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.add-post-container i.fas,
.add-post-container [class*="fa-"].fas {
    font-weight: 900 !important;
}

.add-post-container i.far,
.add-post-container [class*="fa-"].far {
    font-weight: 400 !important;
}
</style>

<script>
function resetForm() {
    if (confirm('Are you sure you want to reset the form? All changes will be lost.')) {
        window.location.reload();
    }
}

// Form validation
document.getElementById('editPostForm').addEventListener('submit', function(e) {
    // Remove any existing validation states
    this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    
    const requiredFields = ['post_title', 'employee_type', 'location', 'required_count'];
    let hasErrors = false;
    const errors = [];
    const firstErrorField = [];
    
    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field) {
            // Handle different field types
            let isEmpty = false;
            if (field.tagName === 'SELECT') {
                isEmpty = !field.value || field.value === '';
            } else if (field.type === 'number') {
                isEmpty = !field.value || field.value <= 0;
            } else {
                isEmpty = !field.value || !field.value.trim();
            }
            
            if (isEmpty) {
                field.classList.add('is-invalid');
                hasErrors = true;
                if (firstErrorField.length === 0) {
                    firstErrorField.push(field);
                }
                const fieldLabel = field.previousElementSibling?.textContent?.replace('*', '').trim() || fieldName;
                errors.push(fieldLabel + ' is required');
            } else {
                field.classList.remove('is-invalid');
            }
        }
    });
    
    // Validate filled_count doesn't exceed required_count
    const requiredCount = parseInt(document.getElementById('required_count').value) || 0;
    const filledCount = parseInt(document.getElementById('filled_count').value) || 0;
    const filledCountField = document.getElementById('filled_count');
    
    if (filledCount < 0) {
        filledCountField.classList.add('is-invalid');
        hasErrors = true;
        if (firstErrorField.length === 0) {
            firstErrorField.push(filledCountField);
        }
        errors.push('Filled count cannot be negative');
    } else if (filledCount > requiredCount) {
        filledCountField.classList.add('is-invalid');
        hasErrors = true;
        if (firstErrorField.length === 0) {
            firstErrorField.push(filledCountField);
        }
        errors.push('Filled count cannot exceed required count');
    } else {
        filledCountField.classList.remove('is-invalid');
    }
    
    if (hasErrors) {
        e.preventDefault();
        e.stopPropagation();
        alert('Please fill in all required fields:\n\n' + errors.join('\n'));
        if (firstErrorField.length > 0) {
            firstErrorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstErrorField[0].focus();
        }
        return false;
    }
});
</script>
