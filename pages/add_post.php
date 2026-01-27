<?php
// Check for success message from session
$show_success_popup = false;
$success_message = '';
$created_post_id = null;
if (isset($_SESSION['post_created_success']) && $_SESSION['post_created_success']) {
    $show_success_popup = true;
    $success_message = $_SESSION['post_created_message'] ?? 'Post created successfully!';
    $created_post_id = $_SESSION['post_created_id'] ?? null;
    // Clear the session variables
    unset($_SESSION['post_created_success']);
    unset($_SESSION['post_created_message']);
    unset($_SESSION['post_created_id']);
}

// Handle form submission
if ($_POST['action'] ?? '' === 'create') {
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
    
    // Validate required fields before attempting to create
    $validation_errors = [];
    if (empty($post_data['post_title'])) {
        $validation_errors[] = 'Post Title is required.';
    }
    if (empty($post_data['employee_type'])) {
        $validation_errors[] = 'Employee Type is required.';
    }
    if (empty($post_data['location'])) {
        $validation_errors[] = 'Location is required.';
    }
    if ($post_data['required_count'] <= 0) {
        $validation_errors[] = 'Number of positions must be greater than 0.';
    }
    if ($post_data['filled_count'] < 0) {
        $validation_errors[] = 'Filled count cannot be negative.';
    }
    if ($post_data['filled_count'] > $post_data['required_count']) {
        $validation_errors[] = 'Filled count cannot exceed required count.';
    }
    
    if (!empty($validation_errors)) {
        $error_message = 'Validation errors:\n' . implode('\n', $validation_errors);
        echo '<script>alert(' . json_encode($error_message) . ');</script>';
    } else {
        // Clear any previous error
        global $last_post_error;
        $last_post_error = null;
        
        if (create_post($post_data)) {
            // Get the newly created post ID if available
            $pdo = get_db_connection();
            $new_post_id = $pdo->lastInsertId();
            
            // Set success flag and message - show popup instead of redirecting
            $success_message = 'Post created successfully!';
            
            // Store success info in session
            $_SESSION['post_created_success'] = true;
            $_SESSION['post_created_message'] = $success_message;
            $_SESSION['post_created_id'] = $new_post_id;
            
            // Build redirect URL using JavaScript since headers may already be sent
            $current_url = $_SERVER['REQUEST_URI'] ?? '';
            // Remove existing query parameters and rebuild
            $base_url = strtok($current_url, '?');
            if (empty($base_url)) {
                $base_url = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
            }
            $redirect_url = $base_url . '?page=add_post&success=1';
            
            // Use JavaScript to redirect (works even if headers are sent)
            echo '<script>
                window.location.href = ' . json_encode($redirect_url) . ';
            </script>';
            // Stop execution to prevent any further output
            exit;
        } else {
            // Get detailed error message if available
            global $last_post_error;
            $error_message = 'Error creating post.';
            
            // Debug: Log what we received
            error_log("add_post.php: create_post returned false");
            error_log("add_post.php: last_post_error = " . var_export($last_post_error, true));
            error_log("add_post.php: POST data = " . print_r($_POST, true));
            
            if (!empty($last_post_error)) {
                // Check for common database errors
                if (stripos($last_post_error, 'Duplicate entry') !== false) {
                    if (stripos($last_post_error, 'post_code') !== false) {
                        $error_message = 'Error: A post with this Post Code already exists. Please use a different Post Code.';
                    } else {
                        $error_message = 'Error: Duplicate entry. ' . htmlspecialchars($last_post_error);
                    }
                } else if (stripos($last_post_error, 'SQLSTATE') !== false || stripos($last_post_error, 'PDO Error') !== false) {
                    // Extract the actual error message from PDO error
                    $error_message = 'Database error: ' . htmlspecialchars($last_post_error);
                } else if (stripos($last_post_error, 'Invalid') !== false) {
                    $error_message = htmlspecialchars($last_post_error);
                } else {
                    $error_message = 'Error: ' . htmlspecialchars($last_post_error);
                }
            } else {
                $error_message = 'Error creating post. Please check that all required fields are filled correctly and try again. If the problem persists, check the server error logs.';
            }
            
            // Also log to help debug
            error_log("add_post.php: Final error message - " . $error_message);
            
            echo '<script>alert(' . json_encode($error_message) . ');</script>';
        }
    }
}
?>

<div class="add-post-container">
    <!-- Success Popup Modal - Positioned at Top -->
    <?php if ($show_success_popup): ?>
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
                    <?php if ($created_post_id): ?>
                        <p class="text-muted small mt-2 mb-0 text-center">Post ID: <strong><?php echo htmlspecialchars($created_post_id); ?></strong></p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 1rem 1.5rem; border-radius: 0 0 8px 8px;">
                    <button type="button" class="btn btn-secondary" onclick="closeSuccessModal()">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                    <a href="?page=posts" class="btn btn-primary">
                        <i class="fas fa-list me-2"></i>View All Posts
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
            const form = document.getElementById('addPostForm');
            if (form && <?php echo $show_success_popup ? 'true' : 'false'; ?>) {
                // Reset the form
                form.reset();
                // Clear any validation classes
                form.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
                    el.classList.remove('is-invalid', 'is-valid');
                });
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

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>Add New Post</h1>
            <p class="text-muted">Create a new job position and assignment location</p>
        </div>
        <div class="page-actions">
            <a href="?page=posts" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Posts
            </a>
        </div>
    </div>

    <!-- Add Post Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Post Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="addPostForm" novalidate>
                        <input type="hidden" name="action" value="create">
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="post_title" class="form-label">Post Title *</label>
                                <input type="text" class="form-control" id="post_title" name="post_title" required>
                                <div class="form-text">e.g., Security Guard - Main Gate</div>
                            </div>
                            <div class="col-md-6">
                                <label for="post_code" class="form-label">Post Code</label>
                                <input type="text" class="form-control" id="post_code" name="post_code">
                                <div class="form-text">e.g., SG001, LG001, SO001 (optional)</div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="">Select Department (Optional)</option>
                                    <option value="Security">Security</option>
                                    <option value="Administration">Administration</option>
                                    <option value="Operations">Operations</option>
                                    <option value="Management">Management</option>
                                    <option value="Support">Support</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="employee_type" class="form-label">Employee Type *</label>
                                <select class="form-select" id="employee_type" name="employee_type" required>
                                    <option value="">Select Employee Type</option>
                                    <option value="SG">Security Guard (SG)</option>
                                    <option value="LG">Lady Guard (LG)</option>
                                    <option value="SO">Security Officer (SO)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="location" class="form-label">Assignment Location *</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                            <div class="form-text">e.g., Main Entrance Gate, Building A - 3rd Floor</div>
                        </div>

                        <!-- Job Details -->
                        <div class="mb-4">
                            <label for="description" class="form-label">Job Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" 
                                      placeholder="Describe the main duties and responsibilities of this position..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="requirements" class="form-label">Requirements & Qualifications</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="3" 
                                      placeholder="List the required qualifications, skills, and experience..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="responsibilities" class="form-label">Key Responsibilities</label>
                            <textarea class="form-control" id="responsibilities" name="responsibilities" rows="3" 
                                      placeholder="List the main responsibilities and duties..."></textarea>
                        </div>

                        <!-- Position Details -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="required_count" class="form-label">Number of Positions Needed *</label>
                                <input type="number" class="form-control" id="required_count" name="required_count" 
                                       value="1" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label for="filled_count" class="form-label">Filled Positions</label>
                                <input type="number" class="form-control" id="filled_count" name="filled_count" 
                                       value="0" min="0">
                                <div class="form-text">Number of positions already filled</div>
                            </div>
                            <div class="col-md-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="Active" selected>Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                        </div>

                        <!-- Work Schedule -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="shift_type" class="form-label">Shift Type</label>
                                <input type="text" class="form-control" id="shift_type" name="shift_type" 
                                       placeholder="e.g., Day, Night, Rotating">
                            </div>
                            <div class="col-md-6">
                                <label for="work_hours" class="form-label">Work Hours</label>
                                <input type="text" class="form-control" id="work_hours" name="work_hours" 
                                       placeholder="e.g., 8 hours, 12 hours">
                            </div>
                        </div>

                        <!-- Compensation -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="salary_range" class="form-label">Salary Range</label>
                                <input type="text" class="form-control" id="salary_range" name="salary_range" 
                                       placeholder="e.g., 15000-20000">
                            </div>
                            <div class="col-md-6">
                                <label for="reporting_to" class="form-label">Reports To</label>
                                <input type="text" class="form-control" id="reporting_to" name="reporting_to" 
                                       placeholder="e.g., Security Supervisor">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="benefits" class="form-label">Benefits & Perks</label>
                            <textarea class="form-control" id="benefits" name="benefits" rows="2" 
                                      placeholder="List benefits, allowances, and perks..."></textarea>
                        </div>

                        <!-- Expiration Date -->
                        <div class="mb-4">
                            <label for="expires_at" class="form-label">Post Expiration Date</label>
                            <input type="date" class="form-control" id="expires_at" name="expires_at">
                            <div class="form-text">Leave empty if post has no expiration date</div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Post
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-refresh me-2"></i>Reset
                            </button>
                            <a href="?page=posts" class="btn btn-outline-danger">
                                <i class="fas fa-times me-2"></i>Cancel
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

            <!-- Quick Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Current Statistics</h6>
                </div>
                <div class="card-body">
                    <?php 
                    $stats = get_post_statistics();
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Posts:</span>
                        <strong><?php echo $stats['total_posts']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Active Posts:</span>
                        <strong><?php echo $stats['active_posts']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Vacant Positions:</span>
                        <strong class="text-warning"><?php echo $stats['total_vacant']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Urgent Posts:</span>
                        <strong class="text-danger"><?php echo $stats['urgent_posts']; ?></strong>
                    </div>
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
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.card-header h5,
.card-header h6 {
    color: var(--interface-text);
    font-weight: 600;
}

.help-panel .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

.help-panel ul li {
    margin-bottom: 0.25rem;
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
    outline: none !important;
}

.card-modern:hover,
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0 !important;
    outline: none !important;
}

.card-modern:focus,
.card:focus,
.card-modern:focus-visible,
.card:focus-visible {
    outline: none !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0 !important;
}

/* Page Header - Rectangle container with rounded corners */
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

/* Dark theme support for Add Post page header */
html[data-theme="dark"] .page-header {
    background-color: #1a1d23 !important;
    border: 1px solid var(--interface-border) !important;
    border-radius: 14px; /* Rounded rectangle */
    padding: 1.5rem 2rem; /* Adjusted padding */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04); /* Added shadow */
    color: var(--interface-text) !important;
    margin-bottom: var(--spacing-xl) !important;
}

html[data-theme="dark"] .page-header h1 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-header .text-muted {
    color: var(--interface-text-muted) !important;
}
</style>

<script>
function resetForm() {
    if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
        document.getElementById('addPostForm').reset();
    }
}

// Auto-generate post code based on employee type
document.getElementById('employee_type').addEventListener('change', function() {
    const employeeType = this.value;
    const postCodeInput = document.getElementById('post_code');
    
    if (employeeType && !postCodeInput.value) {
        // Generate a basic post code
        const prefix = employeeType;
        const timestamp = Date.now().toString().slice(-3);
        postCodeInput.value = prefix + timestamp;
    }
});

// Function to get human-readable field name
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
                        <i class="fas fa-exclamation-circle text-danger fs-48"></i>
                    </div>
                    <p class="mb-3 text-center fs-md fw-medium">Please fill in all required fields:</p>
                    <ul class="list-unstyled mb-0" style="text-align: left;">
                        ${errors.map(error => `<li class="py-2" style="border-bottom: 1px solid #e9ecef;"><i class="fas fa-circle text-danger me-2 fs-10"></i>${error}</li>`).join('')}
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

// Form validation
document.getElementById('addPostForm').addEventListener('submit', function(e) {
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
                const fieldLabel = getFieldLabel(field);
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
        // Show custom validation error popup
        showValidationErrorPopup(errors, firstErrorField);
        return false;
    }
});

// Real-time validation
document.querySelectorAll('input[required], select[required]').forEach(field => {
    field.addEventListener('blur', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    });
});
</script>
