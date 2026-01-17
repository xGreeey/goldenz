<?php
// Profile Page
$page_title = 'Profile - Golden Z-5 HR System';
$page = 'profile';

// Get current user info
$current_user_id = $_SESSION['user_id'] ?? null;
$current_user = null;
$employee_data = null;

if ($current_user_id && function_exists('get_user_by_id')) {
    require_once __DIR__ . '/../includes/database.php';
    $current_user = get_user_by_id($current_user_id);
    
    // Get employee data if user is linked to an employee
    if (!empty($current_user['employee_id']) && function_exists('get_employee')) {
        $employee_data = get_employee($current_user['employee_id']);
    }
}

// Get post/location data if employee has a post assignment
$post_location = null;
if (!empty($employee_data['post'])) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT post_name, location FROM posts WHERE post_name = ? OR post_title = ? LIMIT 1");
        $stmt->execute([$employee_data['post'], $employee_data['post']]);
        $post_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post_info) {
            $post_location = $post_info['location'] ?? null;
        }
    } catch (Exception $e) {
        // Silently fail if post lookup fails
    }
}

// Handle form submission
$update_success = false;
$update_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    try {
        $pdo = get_db_connection();
        
        // Separate updates for users and employees tables
        $user_updates = [];
        $user_params = [];
        $employee_updates = [];
        $employee_params = [];
        
        // Email update (users table)
        if (isset($_POST['email']) && !empty(trim($_POST['email']))) {
            $email = trim($_POST['email']);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Check if email is already taken by another user
                $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check_stmt->execute([$email, $current_user_id]);
                if ($check_stmt->rowCount() === 0) {
                    $user_updates[] = "email = ?";
                    $user_params[] = $email;
                } else {
                    $update_error = 'Email address is already in use by another account.';
                }
            } else {
                $update_error = 'Invalid email address format.';
            }
        }
        
        // Department update (users table)
        if (isset($_POST['department']) && !empty(trim($_POST['department']))) {
            $user_updates[] = "department = ?";
            $user_params[] = trim($_POST['department']);
        }
        
        // Contact number update (check if users table has phone field, otherwise update employee)
        if (isset($_POST['contact_number']) && !empty(trim($_POST['contact_number']))) {
            try {
                $check_col = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
                if ($check_col->rowCount() > 0) {
                    $user_updates[] = "phone = ?";
                    $user_params[] = trim($_POST['contact_number']);
                } elseif (!empty($current_user['employee_id'])) {
                    // Update in employees table if user is linked to employee
                    $employee_updates[] = "cp_number = ?";
                    $employee_params[] = trim($_POST['contact_number']);
                }
            } catch (Exception $e) {
                // If users table doesn't have phone, try employees table
                if (!empty($current_user['employee_id'])) {
                    $employee_updates[] = "cp_number = ?";
                    $employee_params[] = trim($_POST['contact_number']);
                }
            }
        }
        
        // Employee-specific fields (only if user is linked to employee)
        if (!empty($current_user['employee_id'])) {
            if (isset($_POST['first_name']) && !empty(trim($_POST['first_name']))) {
                $employee_updates[] = "first_name = ?";
                $employee_params[] = trim($_POST['first_name']);
            }
            
            if (isset($_POST['last_name']) && !empty(trim($_POST['last_name']))) {
                $employee_updates[] = "surname = ?";
                $employee_params[] = trim($_POST['last_name']);
            }
            
            if (isset($_POST['position']) && !empty(trim($_POST['position']))) {
                $employee_updates[] = "post = ?";
                $employee_params[] = trim($_POST['position']);
            }
            
            if (isset($_POST['date_hired']) && !empty(trim($_POST['date_hired']))) {
                $employee_updates[] = "date_hired = ?";
                $employee_params[] = trim($_POST['date_hired']);
            }
        }
        
        // Update users table
        if (!empty($user_updates) && empty($update_error)) {
            $user_params[] = $current_user_id;
            $user_sql = "UPDATE users SET " . implode(", ", $user_updates) . ", updated_at = NOW() WHERE id = ?";
            $user_stmt = $pdo->prepare($user_sql);
            $user_stmt->execute($user_params);
        }
        
        // Update employees table if user is linked to an employee
        if (!empty($current_user['employee_id']) && !empty($employee_updates) && empty($update_error)) {
            $employee_params[] = $current_user['employee_id'];
            $employee_sql = "UPDATE employees SET " . implode(", ", $employee_updates) . ", updated_at = NOW() WHERE id = ?";
            $employee_stmt = $pdo->prepare($employee_sql);
            $employee_stmt->execute($employee_params);
        }
        
        // Only proceed with success if no errors occurred
        if (empty($update_error)) {
            // Refresh user data
            $current_user = get_user_by_id($current_user_id);
            if (!empty($current_user['employee_id']) && function_exists('get_employee')) {
                $employee_data = get_employee($current_user['employee_id']);
            }
            
            $update_success = true;
            
            // Update session if name changed
            if (isset($_POST['first_name']) || isset($_POST['last_name'])) {
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                if (!empty($first_name) || !empty($last_name)) {
                    $_SESSION['name'] = trim($first_name . ' ' . $last_name);
                }
            }
            
            if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['email'] = trim($_POST['email']);
            }
        }
    } catch (Exception $e) {
        error_log('Profile update error: ' . $e->getMessage());
        $update_error = 'An error occurred while updating your profile. Please try again.';
    }
}

// Prepare display values
$display_first_name = $employee_data['first_name'] ?? $current_user['first_name'] ?? '';
$display_last_name = $employee_data['surname'] ?? '';
$display_username = $current_user['username'] ?? $_SESSION['username'] ?? '';
$display_email = $current_user['email'] ?? $_SESSION['email'] ?? '';
$display_contact = $employee_data['cp_number'] ?? $current_user['phone'] ?? '';
$display_employee_id = $employee_data['employee_no'] ?? $current_user['employee_no'] ?? '';
$display_company_name = 'Golden Z-5 Security and Investigation Agency, Inc.'; // Default company name
$display_department = $current_user['department'] ?? '';
$display_position = $employee_data['post'] ?? '';
$display_office_location = $post_location ?? '';
$display_date_hired = $employee_data['date_hired'] ?? '';

// Get profile photo
$profile_photo = null;
if (!empty($employee_data['id'])) {
    $photo_path = __DIR__ . '/../uploads/employees/' . $employee_data['id'] . '.png';
    if (!file_exists($photo_path)) {
        $photo_path = __DIR__ . '/../uploads/employees/' . $employee_data['id'] . '.jpg';
    }
    if (file_exists($photo_path)) {
        $profile_photo = '../uploads/employees/' . basename($photo_path);
    }
}

// Generate initials for avatar
$initials = 'U';
if (!empty($display_first_name) || !empty($display_last_name)) {
    $first = strtoupper(substr($display_first_name, 0, 1));
    $last = strtoupper(substr($display_last_name, 0, 1));
    $initials = $first . ($last ?: $first);
} elseif (!empty($display_username)) {
    $initials = strtoupper(substr($display_username, 0, 2));
}
?>

<div class="container-fluid profile-page">
    <div class="page-header-modern mb-4">
        <div class="page-title-modern">
            <h1 class="page-title-main">My Profile</h1>
            <p class="page-subtitle">View and manage your personal information and work details.</p>
        </div>
    </div>

    <?php if ($update_success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>Profile updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($update_error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($update_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <form method="POST" action="?page=profile" id="profileForm">
        <input type="hidden" name="action" value="update_profile">
        
        <!-- Personal / User Info Section -->
        <div class="card card-modern mb-4">
            <div class="card-body-modern">
                <div class="card-header-modern mb-3">
                    <h5 class="card-title-modern">Personal / User Info</h5>
                    <small class="card-subtitle">Your personal information and account details.</small>
                </div>

                <div class="row g-3">
                    <!-- Profile Photo -->
                    <div class="col-12 mb-3">
                        <label class="form-label">Profile Photo</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="profile-photo-preview">
                                <?php if ($profile_photo): ?>
                                    <img src="<?php echo htmlspecialchars($profile_photo); ?>" 
                                         alt="Profile Photo" 
                                         class="profile-photo-img"
                                         style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #e2e8f0;">
                                <?php else: ?>
                                    <div class="profile-photo-placeholder" 
                                         style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; border: 3px solid #e2e8f0;">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-0">Profile photo is managed through employee records.</p>
                            </div>
                        </div>
                    </div>

                    <!-- First Name -->
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="first_name" 
                               name="first_name" 
                               value="<?php echo htmlspecialchars($display_first_name); ?>"
                               required
                               placeholder="Enter your first name">
                    </div>

                    <!-- Last Name -->
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="last_name" 
                               name="last_name" 
                               value="<?php echo htmlspecialchars($display_last_name); ?>"
                               required
                               placeholder="Enter your last name">
                    </div>

                    <!-- Username -->
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               value="<?php echo htmlspecialchars($display_username); ?>"
                               readonly
                               style="background-color: #f8fafc;">
                        <small class="text-muted">Username cannot be changed.</small>
                    </div>

                    <!-- Email Address -->
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($display_email); ?>"
                               required
                               placeholder="Enter your email address">
                    </div>

                    <!-- Contact Number -->
                    <div class="col-md-6">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <input type="tel" 
                               class="form-control" 
                               id="contact_number" 
                               name="contact_number" 
                               value="<?php echo htmlspecialchars($display_contact); ?>"
                               placeholder="Enter your contact number">
                    </div>

                    <!-- Employee ID -->
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input type="text" 
                               class="form-control" 
                               id="employee_id" 
                               value="<?php echo htmlspecialchars($display_employee_id); ?>"
                               readonly
                               style="background-color: #f8fafc;">
                        <small class="text-muted">Employee ID is assigned by the system.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Company & Work Information Section -->
        <div class="card card-modern mb-4">
            <div class="card-body-modern">
                <div class="card-header-modern mb-3">
                    <h5 class="card-title-modern">Company & Work Information</h5>
                    <small class="card-subtitle">Your employment and work-related details.</small>
                </div>

                <div class="row g-3">
                    <!-- Company Name -->
                    <div class="col-md-6">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" 
                               class="form-control" 
                               id="company_name" 
                               value="<?php echo htmlspecialchars($display_company_name); ?>"
                               readonly
                               style="background-color: #f8fafc;">
                    </div>

                    <!-- Department -->
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" 
                                id="department" 
                                name="department">
                            <option value="">Select Department</option>
                            <option value="IT" <?php echo ($display_department === 'IT') ? 'selected' : ''; ?>>IT</option>
                            <option value="HR" <?php echo ($display_department === 'HR') ? 'selected' : ''; ?>>HR</option>
                            <option value="Finance" <?php echo ($display_department === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                            <option value="Operations" <?php echo ($display_department === 'Operations') ? 'selected' : ''; ?>>Operations</option>
                            <option value="Accounting" <?php echo ($display_department === 'Accounting') ? 'selected' : ''; ?>>Accounting</option>
                            <option value="Logistics" <?php echo ($display_department === 'Logistics') ? 'selected' : ''; ?>>Logistics</option>
                            <option value="Security" <?php echo ($display_department === 'Security') ? 'selected' : ''; ?>>Security</option>
                        </select>
                    </div>

                    <!-- Position / Job Title -->
                    <div class="col-md-6">
                        <label for="position" class="form-label">Position / Job Title</label>
                        <input type="text" 
                               class="form-control" 
                               id="position" 
                               name="position" 
                               value="<?php echo htmlspecialchars($display_position); ?>"
                               placeholder="Enter your position or job title">
                    </div>

                    <!-- Office / Branch Location -->
                    <div class="col-md-6">
                        <label for="office_location" class="form-label">Office / Branch Location</label>
                        <input type="text" 
                               class="form-control" 
                               id="office_location" 
                               name="office_location" 
                               value="<?php echo htmlspecialchars($display_office_location); ?>"
                               placeholder="Enter office or branch location">
                    </div>

                    <!-- Date Hired -->
                    <div class="col-md-6">
                        <label for="date_hired" class="form-label">Date Hired</label>
                        <input type="date" 
                               class="form-control" 
                               id="date_hired" 
                               name="date_hired" 
                               value="<?php echo htmlspecialchars($display_date_hired); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="?page=dashboard" class="btn btn-outline-modern">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
            <button type="submit" class="btn btn-primary-modern" id="saveProfileBtn">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </form>
</div>

<style>
.profile-page {
    padding: 2rem 2.5rem;
    max-width: 100%;
    background: #f8fafc;
    min-height: 100vh;
}

.profile-page .card-modern {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    background: #ffffff;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.profile-page .card-modern:hover {
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
}

.profile-page .card-body-modern {
    padding: 1.5rem;
}

.profile-page .card-header-modern {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.profile-page .card-title-modern {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

.profile-page .card-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

.profile-page .form-label {
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.profile-page .form-control,
.profile-page .form-select {
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.profile-page .form-control:focus,
.profile-page .form-select:focus {
    border-color: #1e3a8a;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    outline: none;
}

.profile-page .btn-primary-modern {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%);
    color: #ffffff;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9375rem;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.35);
}

.profile-page .btn-primary-modern:hover {
    background: linear-gradient(135deg, #1e40af 0%, #1e293b 50%, #1e3a8a 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(30, 58, 138, 0.45);
}

.profile-page .btn-outline-modern {
    border: 1.5px solid #e2e8f0;
    color: #475569;
    background: #ffffff;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9375rem;
    transition: all 0.2s ease;
}

.profile-page .btn-outline-modern:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #1e293b;
}

/* Dark theme support */
html[data-theme="dark"] .profile-page {
    background: var(--interface-bg) !important;
}

html[data-theme="dark"] .profile-page .card-modern {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .profile-page .card-title-modern {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .profile-page .card-subtitle {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .profile-page .form-control,
html[data-theme="dark"] .profile-page .form-select {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .profile-page .form-control:focus,
html[data-theme="dark"] .profile-page .form-select:focus {
    border-color: #1e3a8a !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            // Basic validation
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!firstName || !lastName) {
                e.preventDefault();
                alert('First name and last name are required.');
                return false;
            }
            
            if (!email || !email.includes('@')) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            // Disable button and show loading state
            if (saveProfileBtn) {
                saveProfileBtn.disabled = true;
                saveProfileBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            }
        });
    }
});
</script>
