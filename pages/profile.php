<?php
// Profile Page
$page_title = 'Profile - Golden Z-5 HR System';
$page = 'profile';

// Ensure paths.php is included for get_avatar_url() function
if (!function_exists('get_avatar_url')) {
    require_once __DIR__ . '/../includes/paths.php';
}

// Include storage helper for MinIO uploads
require_once __DIR__ . '/../includes/storage.php';

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


// Handle form submission
$update_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    try {
        $pdo = get_db_connection();
        
        // Separate updates for users and employees tables
        $user_updates = [];
        $user_params = [];
        $employee_updates = [];
        $employee_params = [];
        
        // Handle avatar upload (users table)
        if (isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $filename = 'user_' . $current_user_id . '_' . time() . '.' . $extension;
                    $destination_path = 'uploads/users/' . $filename;
                    
                    // Upload to storage (MinIO or local)
                    $uploaded_path = upload_to_storage($file['tmp_name'], $destination_path, [
                        'content_type' => $file['type']
                    ]);
                    
                    if ($uploaded_path !== false) {
                        // Delete old avatar if exists
                        if (!empty($current_user['avatar'])) {
                            delete_from_storage($current_user['avatar']);
                        }
                        
                        $avatar_path = $uploaded_path;
                        $user_updates[] = "avatar = ?";
                        $user_params[] = $avatar_path;
                    } else {
                        $update_error = 'Failed to upload file to storage. Please try again.';
                        error_log('Avatar upload failed: Cannot upload file from ' . $file['tmp_name'] . ' to ' . $destination_path);
                    }
                } else {
                    if (!in_array($file['type'], $allowed_types)) {
                        $update_error = 'Invalid file type. Please upload a JPG, PNG, or GIF image.';
                    } elseif ($file['size'] > $max_size) {
                            $update_error = 'File size too large. Maximum size is 2MB.';
                        }
                    }
                }
            } else {
                // Handle upload errors
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
                ];
                $error_code = $_FILES['avatar']['error'];
                $update_error = $upload_errors[$error_code] ?? 'Unknown upload error occurred (Error code: ' . $error_code . ').';
                error_log('Avatar upload error: ' . $update_error);
            }
        }
        
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
        
        // First name and last name updates (users table - check if columns exist)
        try {
            $check_first_name = $pdo->query("SHOW COLUMNS FROM users LIKE 'first_name'");
            $check_last_name = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_name'");
            $has_first_name = $check_first_name->rowCount() > 0;
            $has_last_name = $check_last_name->rowCount() > 0;
            
            if ($has_first_name && isset($_POST['first_name']) && !empty(trim($_POST['first_name']))) {
                $user_updates[] = "first_name = ?";
                $user_params[] = trim($_POST['first_name']);
            }
            
            if ($has_last_name && isset($_POST['last_name']) && !empty(trim($_POST['last_name']))) {
                $user_updates[] = "last_name = ?";
                $user_params[] = trim($_POST['last_name']);
            }
        } catch (Exception $e) {
            error_log('Error checking first_name/last_name columns: ' . $e->getMessage());
        }
        
        // Update users table
        if (!empty($user_updates) && empty($update_error)) {
            $user_params[] = $current_user_id;
            $user_sql = "UPDATE users SET " . implode(", ", $user_updates) . ", updated_at = NOW() WHERE id = ?";
            try {
                // Check if avatar column exists
                $check_avatar_col = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
                if ($check_avatar_col->rowCount() === 0) {
                    $update_error = 'Avatar column does not exist in users table.';
                    error_log('Avatar column missing in users table');
                } else {
                    $user_stmt = $pdo->prepare($user_sql);
                    if ($user_stmt->execute($user_params)) {
                        // Success - avatar or other fields updated
                        $rows_affected = $user_stmt->rowCount();
                        
                        // Verify avatar was saved if it was in the update
                        if (in_array('avatar = ?', $user_updates)) {
                            $avatar_index = array_search('avatar = ?', $user_updates);
                            $saved_avatar_path = $user_params[$avatar_index];
                            
                            // Verify the avatar was actually saved to database
                            $verify_stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                            $verify_stmt->execute([$current_user_id]);
                            $verify_result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($verify_result && $verify_result['avatar'] !== $saved_avatar_path) {
                                error_log('Avatar verification failed: Expected ' . $saved_avatar_path . ' but got ' . ($verify_result['avatar'] ?? 'NULL'));
                                $update_error = 'Avatar was uploaded but not saved to database correctly.';
                            } else {
                                // Avatar saved successfully
                                error_log('Avatar saved successfully: ' . $saved_avatar_path);
                            }
                        }
                        
                        if ($rows_affected === 0) {
                            error_log('Profile update: No rows affected. SQL: ' . $user_sql . ' Params: ' . print_r($user_params, true));
                        }
                    } else {
                        $error_info = $user_stmt->errorInfo();
                        error_log('Profile update failed: ' . print_r($error_info, true));
                        $update_error = 'Failed to update profile in database: ' . ($error_info[2] ?? 'Unknown error');
                    }
                }
            } catch (PDOException $e) {
                error_log('Profile update PDO exception: ' . $e->getMessage());
                $update_error = 'Database error while updating profile: ' . $e->getMessage();
            }
        } elseif (!empty($user_updates) && !empty($update_error)) {
            // If there was an error but we have updates, log it
            error_log('Profile update skipped due to error: ' . $update_error . ' Updates: ' . print_r($user_updates, true));
        }
        
        // Update employees table if user is linked to an employee
        if (!empty($current_user['employee_id']) && !empty($employee_updates) && empty($update_error)) {
            $employee_params[] = $current_user['employee_id'];
            $employee_sql = "UPDATE employees SET " . implode(", ", $employee_updates) . ", updated_at = NOW() WHERE id = ?";
            $employee_stmt = $pdo->prepare($employee_sql);
            $employee_stmt->execute($employee_params);
        }
        
        // Only proceed with success if no errors occurred
        // Note: Redirect is now handled in index.php before header output
        if (empty($update_error)) {
            // Refresh user data
            $current_user = get_user_by_id($current_user_id);
            if (!empty($current_user['employee_id']) && function_exists('get_employee')) {
                $employee_data = get_employee($current_user['employee_id']);
            }
            
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

// Prepare display values - fetch from users table first, fallback to employees if linked
$display_first_name = $current_user['first_name'] ?? $employee_data['first_name'] ?? '';
$display_last_name = $current_user['last_name'] ?? $employee_data['surname'] ?? '';
$display_username = $current_user['username'] ?? $_SESSION['username'] ?? '';
$display_email = $current_user['email'] ?? $_SESSION['email'] ?? '';
$display_contact = $current_user['phone'] ?? $employee_data['cp_number'] ?? '';

// Get profile photo - check users.avatar first, then employee photos
$profile_photo = null;
if (!empty($current_user['avatar'])) {
    $profile_photo = get_avatar_url($current_user['avatar']);
}

// If no avatar from users table, check employee photos
if (!$profile_photo && !empty($employee_data['id'])) {
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
    <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>Profile updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php 
    // Check for error from session (set by index.php redirect)
    $display_error = $update_error ?? ($_SESSION['profile_update_error'] ?? null);
    if ($display_error):
        unset($_SESSION['profile_update_error']); // Clear after displaying
    ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($display_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <form method="POST" action="?page=profile" id="profileForm" enctype="multipart/form-data">
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
                            <div class="profile-photo-preview" id="photoPreviewContainer">
                                <!-- Preview image (hidden initially if no photo) -->
                                <img src="<?php echo $profile_photo ? htmlspecialchars($profile_photo) : ''; ?>" 
                                     alt="Profile Photo" 
                                     id="avatarPreview"
                                     class="profile-photo-img"
                                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #e2e8f0; <?php echo $profile_photo ? '' : 'display: none;'; ?>">
                                <!-- Placeholder with initials (shown if no photo) -->
                                <div class="profile-photo-placeholder" 
                                     id="avatarPlaceholder"
                                     class="fs-40 fw-bold" style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%); color: white; display: <?php echo $profile_photo ? 'none' : 'flex'; ?>; align-items: center; justify-content: center; border: 3px solid #e2e8f0;">
                                    <?php echo htmlspecialchars($initials); ?>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" 
                                       class="form-control" 
                                       id="avatarInput" 
                                       name="avatar" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif"
                                       style="display: none;">
                                <button type="button" 
                                        class="btn btn-outline-modern btn-sm" 
                                        onclick="document.getElementById('avatarInput').click();"
                                        style="margin-bottom: 0.5rem;">
                                    <i class="fas fa-upload me-2"></i>Upload Photo
                                </button>
                                <p class="text-muted small mb-0">JPG, PNG, or GIF. Max size: 2MB</p>
                                <div id="avatarFileName" class="text-muted small mt-1" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- First Name -->
                    <div class="col-md-6">
                        <label for="first_name" class="form-label d-flex justify-content-between align-items-center">
                            <span>First Name</span>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-secondary edit-field-btn" 
                                    data-field="first_name"
                                    title="Edit First Name">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="first_name" 
                               name="first_name" 
                               value="<?php echo htmlspecialchars($display_first_name); ?>"
                               placeholder="Enter your first name"
                               readonly>
                    </div>

                    <!-- Last Name -->
                    <div class="col-md-6">
                        <label for="last_name" class="form-label d-flex justify-content-between align-items-center">
                            <span>Last Name</span>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-secondary edit-field-btn" 
                                    data-field="last_name"
                                    title="Edit Last Name">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="last_name" 
                               name="last_name" 
                               value="<?php echo htmlspecialchars($display_last_name); ?>"
                               placeholder="Enter your last name"
                               readonly>
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
                        <label for="email" class="form-label d-flex justify-content-between align-items-center">
                            <span>Email Address</span>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-secondary edit-field-btn" 
                                    data-field="email"
                                    title="Edit Email">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($display_email); ?>"
                               placeholder="Enter your email address"
                               readonly
                               style="background-color: #f8fafc;">
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
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarPlaceholder = document.getElementById('avatarPlaceholder');
    const avatarFileName = document.getElementById('avatarFileName');
    
    // Handle edit buttons for first_name, last_name, and email
    const editButtons = document.querySelectorAll('.edit-field-btn');
    editButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const fieldName = this.getAttribute('data-field');
            const inputField = document.getElementById(fieldName);
            
            if (inputField) {
                if (inputField.readOnly) {
                    // Enable editing
                    inputField.readOnly = false;
                    inputField.style.backgroundColor = '#ffffff';
                    inputField.focus();
                    this.innerHTML = '<i class="fas fa-check"></i> Done';
                    this.classList.remove('btn-outline-secondary');
                    this.classList.add('btn-success');
                } else {
                    // Disable editing
                    inputField.readOnly = true;
                    inputField.style.backgroundColor = '#f8fafc';
                    this.innerHTML = '<i class="fas fa-edit"></i> Edit';
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-secondary');
                }
            }
        });
    });
    
    // Handle avatar preview
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please upload a JPG, PNG, or GIF image.');
                    avatarInput.value = '';
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size too large. Maximum size is 2MB.');
                    avatarInput.value = '';
                    return;
                }
                
                // Show file name
                if (avatarFileName) {
                    avatarFileName.textContent = 'Selected: ' + file.name;
                    avatarFileName.style.display = 'block';
                }
                
                // Create preview using FileReader
                const reader = new FileReader();
                reader.onload = function(readerEvent) {
                    // Get elements fresh in case they weren't available at DOMContentLoaded
                    const previewImg = document.getElementById('avatarPreview');
                    const placeholder = document.getElementById('avatarPlaceholder');
                    
                    // Hide placeholder
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    
                    // Show preview image with the selected file
                    if (previewImg) {
                        previewImg.src = readerEvent.target.result;
                        previewImg.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            // Basic validation - email is optional but if provided must be valid
            const email = document.getElementById('email').value.trim();
            
            if (email && !email.includes('@')) {
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
