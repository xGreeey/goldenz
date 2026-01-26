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
$display_role = $current_user['role'] ?? $_SESSION['user_role'] ?? 'User';
$display_department = $current_user['department'] ?? $_SESSION['department'] ?? $employee_data['post'] ?? '';
$display_status = $current_user['status'] ?? 'active';
$last_login = $current_user['last_login'] ?? null;
$member_since = $current_user['created_at'] ?? null;

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

<!-- Profile Hero Section -->
<div class="profile-hero-section">
    <div class="profile-hero-background"></div>
    <div class="profile-hero-content">
        <div class="profile-avatar-hero" id="profileAvatarHero">
            <?php if ($profile_photo): ?>
                <img src="<?php echo htmlspecialchars($profile_photo); ?>" 
                     alt="<?php echo htmlspecialchars($display_first_name . ' ' . $display_last_name); ?>" 
                     id="avatarPreviewHero"
                     class="profile-avatar-hero-img">
            <?php else: ?>
                <div class="profile-avatar-hero-placeholder">
                    <?php echo htmlspecialchars($initials); ?>
                </div>
            <?php endif; ?>
            <div class="profile-avatar-upload-overlay" id="avatarUploadOverlay">
                <button type="button" 
                        class="profile-avatar-upload-btn" 
                        onclick="document.getElementById('avatarInput').click();"
                        aria-label="Upload profile photo">
                    <i class="fas fa-camera" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="profile-hero-info">
            <h1 class="profile-hero-name">
                <?php echo htmlspecialchars(trim($display_first_name . ' ' . $display_last_name) ?: $display_username); ?>
            </h1>
            <div class="profile-hero-meta">
                <?php if ($display_role): ?>
                    <span class="profile-badge profile-badge-role">
                        <i class="fas fa-user-tag" aria-hidden="true"></i>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $display_role))); ?>
                    </span>
                <?php endif; ?>
                <?php if ($display_department): ?>
                    <span class="profile-badge profile-badge-department">
                        <i class="fas fa-building" aria-hidden="true"></i>
                        <?php echo htmlspecialchars($display_department); ?>
                    </span>
                <?php endif; ?>
                <?php if ($display_status): ?>
                    <span class="profile-badge profile-badge-status profile-badge-<?php echo strtolower($display_status); ?>">
                        <i class="fas fa-circle" aria-hidden="true"></i>
                        <?php echo htmlspecialchars(ucfirst($display_status)); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid profile-page">
    <!-- Success/Error Messages -->
    <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
    <div class="profile-alert profile-alert-success" role="alert" aria-live="polite">
        <i class="fas fa-check-circle" aria-hidden="true"></i>
        <span>Profile updated successfully!</span>
        <button type="button" class="profile-alert-close" aria-label="Close alert" onclick="this.parentElement.remove()">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>
    <?php endif; ?>

    <?php 
    $display_error = $update_error ?? ($_SESSION['profile_update_error'] ?? null);
    if ($display_error):
        unset($_SESSION['profile_update_error']);
    ?>
    <div class="profile-alert profile-alert-error" role="alert" aria-live="polite">
        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
        <span><?php echo htmlspecialchars($display_error); ?></span>
        <button type="button" class="profile-alert-close" aria-label="Close alert" onclick="this.parentElement.remove()">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>
    <?php endif; ?>

    <form method="POST" action="?page=profile" id="profileForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile">
        
        <div class="row g-4">
            <!-- Left Column: Profile Information -->
            <div class="col-lg-8">
                <!-- Personal Information Card -->
                <div class="profile-card profile-card-personal">
                    <div class="profile-card-header">
                        <div class="profile-card-header-content">
                            <i class="fas fa-user profile-card-icon" aria-hidden="true"></i>
                            <div>
                                <h3 class="profile-card-title">Personal Information</h3>
                                <p class="profile-card-subtitle">Update your personal details and contact information</p>
                            </div>
                        </div>
                    </div>
                    <div class="profile-card-body">
                        <input type="file" 
                               class="form-control" 
                               id="avatarInput" 
                               name="avatar" 
                               accept="image/jpeg,image/jpg,image/png,image/gif"
                               style="display: none;"
                               aria-label="Upload profile photo">
                        
                        <div class="row g-4">
                            <!-- First Name -->
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label for="first_name" class="profile-field-label">
                                        <i class="fas fa-user" aria-hidden="true"></i>
                                        First Name
                                    </label>
                                    <div class="profile-field-wrapper">
                                        <input type="text" 
                                               class="profile-field-input" 
                                               id="first_name" 
                                               name="first_name" 
                                               value="<?php echo htmlspecialchars($display_first_name); ?>"
                                               placeholder="Enter your first name"
                                               autocomplete="given-name"
                                               readonly>
                                        <button type="button" 
                                                class="profile-field-edit-btn" 
                                                data-field="first_name"
                                                aria-label="Edit first name">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Last Name -->
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label for="last_name" class="profile-field-label">
                                        <i class="fas fa-user" aria-hidden="true"></i>
                                        Last Name
                                    </label>
                                    <div class="profile-field-wrapper">
                                        <input type="text" 
                                               class="profile-field-input" 
                                               id="last_name" 
                                               name="last_name" 
                                               value="<?php echo htmlspecialchars($display_last_name); ?>"
                                               placeholder="Enter your last name"
                                               autocomplete="family-name"
                                               readonly>
                                        <button type="button" 
                                                class="profile-field-edit-btn" 
                                                data-field="last_name"
                                                aria-label="Edit last name">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label for="username" class="profile-field-label">
                                        <i class="fas fa-at" aria-hidden="true"></i>
                                        Username
                                    </label>
                                    <input type="text" 
                                           class="profile-field-input profile-field-readonly" 
                                           id="username" 
                                           value="<?php echo htmlspecialchars($display_username); ?>"
                                           readonly
                                           aria-label="Username (cannot be changed)">
                                    <small class="profile-field-hint">Username cannot be changed</small>
                                </div>
                            </div>

                            <!-- Email Address -->
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label for="email" class="profile-field-label">
                                        <i class="fas fa-envelope" aria-hidden="true"></i>
                                        Email Address
                                    </label>
                                    <div class="profile-field-wrapper">
                                        <input type="email" 
                                               class="profile-field-input" 
                                               id="email" 
                                               name="email" 
                                               value="<?php echo htmlspecialchars($display_email); ?>"
                                               placeholder="Enter your email address"
                                               autocomplete="email"
                                               readonly>
                                        <button type="button" 
                                                class="profile-field-edit-btn" 
                                                data-field="email"
                                                aria-label="Edit email">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Number -->
                            <div class="col-md-6">
                                <div class="profile-field-group">
                                    <label for="contact_number" class="profile-field-label">
                                        <i class="fas fa-phone" aria-hidden="true"></i>
                                        Contact Number
                                    </label>
                                    <input type="tel" 
                                           class="profile-field-input" 
                                           id="contact_number" 
                                           name="contact_number" 
                                           value="<?php echo htmlspecialchars($display_contact); ?>"
                                           placeholder="Enter your contact number"
                                           autocomplete="tel">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Profile Stats & Info -->
            <div class="col-lg-4">
                <!-- Account Information Card -->
                <div class="profile-card profile-card-account">
                    <div class="profile-card-header">
                        <div class="profile-card-header-content">
                            <i class="fas fa-info-circle profile-card-icon" aria-hidden="true"></i>
                            <div>
                                <h3 class="profile-card-title">Account Info</h3>
                            </div>
                        </div>
                    </div>
                    <div class="profile-card-body">
                        <div class="profile-info-item">
                            <div class="profile-info-label">
                                <i class="fas fa-user-tag" aria-hidden="true"></i>
                                Role
                            </div>
                            <div class="profile-info-value">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $display_role))); ?>
                            </div>
                        </div>
                        <?php if ($display_department): ?>
                        <div class="profile-info-item">
                            <div class="profile-info-label">
                                <i class="fas fa-building" aria-hidden="true"></i>
                                Department
                            </div>
                            <div class="profile-info-value">
                                <?php echo htmlspecialchars($display_department); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($member_since): ?>
                        <div class="profile-info-item">
                            <div class="profile-info-label">
                                <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                                Member Since
                            </div>
                            <div class="profile-info-value">
                                <?php echo date('M Y', strtotime($member_since)); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($last_login): ?>
                        <div class="profile-info-item">
                            <div class="profile-info-label">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Last Login
                            </div>
                            <div class="profile-info-value">
                                <?php 
                                $login_time = strtotime($last_login);
                                $now = time();
                                $diff = $now - $login_time;
                                if ($diff < 3600) {
                                    echo floor($diff / 60) . ' minutes ago';
                                } elseif ($diff < 86400) {
                                    echo floor($diff / 3600) . ' hours ago';
                                } else {
                                    echo date('M d, Y', $login_time);
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="profile-card profile-card-actions">
                    <div class="profile-card-header">
                        <div class="profile-card-header-content">
                            <i class="fas fa-bolt profile-card-icon" aria-hidden="true"></i>
                            <div>
                                <h3 class="profile-card-title">Quick Actions</h3>
                            </div>
                        </div>
                    </div>
                    <div class="profile-card-body">
                        <div class="profile-action-buttons">
                            <button type="submit" class="profile-action-btn profile-action-btn-primary" id="saveProfileBtn">
                                <i class="fas fa-save" aria-hidden="true"></i>
                                <span>Save Changes</span>
                            </button>
                            <a href="?page=dashboard" class="profile-action-btn profile-action-btn-secondary">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span>Back to Dashboard</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* ============================================
   EXTRAORDINARY PROFILE PAGE DESIGN
   Modern, unique, world-class UI
   ============================================ */

/* Profile Hero Section - Stunning Header */
.profile-hero-section {
    position: relative;
    width: 100%;
    min-height: 320px;
    margin-bottom: 2rem;
    border-radius: 0 0 24px 24px;
    overflow: hidden;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 30%, #334155 60%, #475569 100%);
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.3);
}

.profile-hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(31, 178, 213, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
        linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    animation: gradientShift 15s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { 
        background: 
            radial-gradient(circle at 20% 50%, rgba(31, 178, 213, 0.15) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
            linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    }
    50% { 
        background: 
            radial-gradient(circle at 80% 50%, rgba(31, 178, 213, 0.15) 0%, transparent 50%),
            radial-gradient(circle at 20% 80%, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
            linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .profile-hero-background {
        animation: none;
    }
}

.profile-hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 2.5rem;
    padding: 3rem 2.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

/* Hero Avatar - Large, Beautiful */
.profile-avatar-hero {
    position: relative;
    width: 180px;
    height: 180px;
    flex-shrink: 0;
}

.profile-avatar-hero-img,
.profile-avatar-hero-placeholder {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid rgba(255, 255, 255, 0.2);
    box-shadow: 
        0 0 0 4px rgba(31, 178, 213, 0.3),
        0 20px 40px rgba(0, 0, 0, 0.4),
        inset 0 0 0 1px rgba(255, 255, 255, 0.1);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
}

.profile-avatar-hero:hover .profile-avatar-hero-img,
.profile-avatar-hero:hover .profile-avatar-hero-placeholder {
    transform: scale(1.05);
    box-shadow: 
        0 0 0 4px rgba(31, 178, 213, 0.5),
        0 25px 50px rgba(0, 0, 0, 0.5),
        inset 0 0 0 1px rgba(255, 255, 255, 0.2);
}

.profile-avatar-hero-placeholder {
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 50%, #0284c7 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.profile-avatar-upload-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    backdrop-filter: blur(4px);
}

.profile-avatar-hero:hover .profile-avatar-upload-overlay {
    opacity: 1;
}

.profile-avatar-upload-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    border: 3px solid white;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.4);
}

.profile-avatar-upload-btn i,
.profile-avatar-upload-btn [class*="fa-"] {
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    color: white !important;
}

.profile-avatar-upload-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(31, 178, 213, 0.6);
}

.profile-avatar-upload-btn:focus-visible {
    outline: 2px solid white;
    outline-offset: 2px;
}

/* Hero Info */
.profile-hero-info {
    flex: 1;
    color: white;
}

.profile-hero-name {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    letter-spacing: -0.02em;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    line-height: 1.2;
}

.profile-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
}

.profile-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    transition: transform 0.2s ease, background 0.2s ease;
}

.profile-badge:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.25);
}

.profile-badge i {
    font-size: 0.75rem;
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    color: white !important;
}

.profile-badge-status.profile-badge-active i {
    color: #22c55e;
}

.profile-badge-status.profile-badge-inactive i {
    color: #94a3b8;
}

.profile-badge-status.profile-badge-suspended i {
    color: #ef4444;
}

/* Profile Page Container */
.profile-page {
    padding: 0 2.5rem 3rem 2.5rem;
    max-width: 1400px;
    margin: 0 auto;
    background: #f8fafc;
    min-height: calc(100vh - 320px);
}

/* Profile Alerts */
.profile-alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.profile-alert-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.profile-alert-error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.profile-alert-close {
    margin-left: auto;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s ease, transform 0.2s ease;
}

.profile-alert-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.profile-alert-close:focus-visible {
    outline: 2px solid white;
    outline-offset: 2px;
}

/* Profile Cards - Modern Glassmorphism */
.profile-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 
        0 1px 3px rgba(0, 0, 0, 0.05),
        0 4px 12px rgba(0, 0, 0, 0.04),
        0 0 0 1px rgba(31, 178, 213, 0.08);
    overflow: hidden;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(226, 232, 240, 0.8);
}

.profile-card:hover {
    transform: translateY(-4px);
    box-shadow: 
        0 4px 16px rgba(0, 0, 0, 0.08),
        0 8px 24px rgba(0, 0, 0, 0.06),
        0 0 0 1px rgba(31, 178, 213, 0.15);
}

.profile-card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e2e8f0;
}

.profile-card-header-content {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    width: 100%;
}

.profile-card-header-content > div {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 48px;
}

/* Ensure icon and title are perfectly aligned */
.profile-card-header-content .profile-card-icon {
    align-self: flex-start;
    margin-top: 0;
    flex-shrink: 0;
}

/* Perfect alignment for cards with subtitle */
.profile-card-header-content > div:has(.profile-card-subtitle) {
    justify-content: flex-start;
    padding-top: 0;
}

/* Perfect alignment for cards without subtitle */
.profile-card-account .profile-card-header-content > div,
.profile-card-actions .profile-card-header-content > div {
    justify-content: center;
    padding-top: 0;
}

.profile-card-account .profile-card-title,
.profile-card-actions .profile-card-title {
    margin: 0;
    line-height: 1.2;
}

.profile-card-icon {
    width: 48px;
    height: 48px;
    min-width: 48px;
    min-height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.25);
    flex-shrink: 0;
    margin-top: 0;
    line-height: 1;
}

.profile-card-icon i,
.profile-card-icon [class*="fa-"] {
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    color: white !important;
}

.profile-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    padding: 0;
    letter-spacing: -0.01em;
    line-height: 1.4;
    display: flex;
    align-items: center;
}

.profile-card-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0.25rem 0 0 0;
}

.profile-card-body {
    padding: 2rem;
}

/* Profile Field Groups */
.profile-field-group {
    margin-bottom: 1.5rem;
}

.profile-field-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.625rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-field-label i {
    color: #1fb2d5;
    font-size: 0.875rem;
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.profile-field-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.profile-field-input {
    width: 100%;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.875rem 3rem 0.875rem 1rem;
    font-size: 0.9375rem;
    color: #1e293b;
    background: #ffffff;
    transition: border-color 0.2s cubic-bezier(0.4, 0, 0.2, 1), 
                box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1),
                background 0.2s ease;
}

.profile-field-input:hover:not(:disabled):not([readonly]) {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.08);
}

.profile-field-input:focus-visible {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 4px rgba(31, 178, 213, 0.1), 0 4px 12px rgba(31, 178, 213, 0.15);
    outline: none;
    transform: translateY(-1px);
}

.profile-field-input:focus:not(:focus-visible) {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
    outline: none;
}

.profile-field-input:focus-visible,
.profile-field-input:focus {
    border-color: #1fb2d5 !important;
}

.profile-field-input[readonly] {
    background: #f8fafc;
    cursor: not-allowed;
}

.profile-field-input:not([readonly]) {
    background: #ffffff;
}

.profile-field-edit-btn {
    position: absolute;
    right: 0.75rem;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #f1f5f9;
    border: none;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
    font-size: 0.875rem;
}

.profile-field-edit-btn i,
.profile-field-edit-btn [class*="fa-"] {
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.profile-field-edit-btn:hover {
    background: #1fb2d5;
    color: white;
    transform: scale(1.1);
}

.profile-field-edit-btn:hover i,
.profile-field-edit-btn:hover [class*="fa-"] {
    color: white !important;
}

.profile-field-edit-btn:focus-visible {
    outline: 2px solid #1fb2d5;
    outline-offset: 2px;
}

.profile-field-edit-btn.active {
    background: #1fb2d5;
    color: white;
}

.profile-field-readonly {
    background: #f8fafc !important;
    cursor: not-allowed;
}

.profile-field-hint {
    display: block;
    margin-top: 0.375rem;
    font-size: 0.8125rem;
    color: #94a3b8;
}

/* Profile Info Items */
.profile-info-item {
    padding: 1rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.profile-info-item:last-child {
    border-bottom: none;
}

.profile-info-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #64748b;
    margin-bottom: 0.5rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-info-label i {
    color: #1fb2d5;
    font-size: 0.75rem;
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.profile-info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
}

/* Profile Action Buttons */
.profile-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.profile-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9375rem;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), 
                box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1),
                background 0.2s ease;
    border: none;
    cursor: pointer;
    text-decoration: none;
    touch-action: manipulation;
}

.profile-action-btn-primary {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
}

.profile-action-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.35);
    background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
    color: white;
}

.profile-action-btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
}

.profile-action-btn-primary:focus-visible {
    outline: 2px solid #1fb2d5;
    outline-offset: 2px;
}

.profile-action-btn-secondary {
    background: #ffffff;
    color: #475569;
    border: 2px solid #e2e8f0;
}

.profile-action-btn-secondary:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #334155;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.profile-action-btn-secondary:focus-visible {
    outline: 2px solid #1fb2d5;
    outline-offset: 2px;
}

.profile-action-btn i {
    font-size: 1rem;
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Ensure all Font Awesome icons are visible - Comprehensive Fix */
.profile-page i[class*="fa-"],
.profile-page [class*="fa-"],
.profile-hero-section i[class*="fa-"],
.profile-hero-section [class*="fa-"] {
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free', 'Font Awesome 6 Brands', 'Font Awesome 5 Brands' !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    speak: none !important;
}

.profile-page i.fas,
.profile-page [class*="fa-"].fas,
.profile-hero-section i.fas,
.profile-hero-section [class*="fa-"].fas {
    font-weight: 900 !important;
}

.profile-page i.far,
.profile-page [class*="fa-"].far,
.profile-hero-section i.far,
.profile-hero-section [class*="fa-"].far {
    font-weight: 400 !important;
}

.profile-page i.fal,
.profile-page [class*="fa-"].fal,
.profile-hero-section i.fal,
.profile-hero-section [class*="fa-"].fal {
    font-weight: 300 !important;
}

/* Specific icon visibility fixes */
.profile-badge i,
.profile-card-icon i,
.profile-field-label i,
.profile-info-label i,
.profile-action-btn i,
.profile-avatar-upload-btn i,
.profile-alert i,
.profile-alert-close i {
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
}

/* Responsive Design */
@media (max-width: 991px) {
    .profile-hero-content {
        flex-direction: column;
        text-align: center;
        padding: 2rem 1.5rem;
    }
    
    .profile-hero-name {
        font-size: 2rem;
    }
    
    .profile-hero-meta {
        justify-content: center;
    }
    
    .profile-page {
        padding: 0 1rem 2rem 1rem;
    }
}

@media (max-width: 576px) {
    .profile-hero-section {
        min-height: 280px;
    }
    
    .profile-avatar-hero {
        width: 140px;
        height: 140px;
    }
    
    .profile-avatar-hero-img,
    .profile-avatar-hero-placeholder {
        width: 140px;
        height: 140px;
    }
    
    .profile-avatar-hero-placeholder {
        font-size: 3rem;
    }
    
    .profile-hero-name {
        font-size: 1.75rem;
    }
    
    .profile-card-body {
        padding: 1.5rem;
    }
    
    .profile-card-header {
        padding: 1.25rem 1.5rem;
    }
}

/* Dark Theme Support */
html[data-theme="dark"] .profile-page {
    background: #0f172a !important;
}

html[data-theme="dark"] .profile-card {
    background: #1e293b !important;
    border-color: rgba(226, 232, 240, 0.1) !important;
}

html[data-theme="dark"] .profile-card-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
    border-color: rgba(226, 232, 240, 0.1) !important;
}

html[data-theme="dark"] .profile-card-title {
    color: #f1f5f9 !important;
}

html[data-theme="dark"] .profile-card-subtitle {
    color: #94a3b8 !important;
}

html[data-theme="dark"] .profile-field-input {
    background: #1e293b !important;
    border-color: rgba(226, 232, 240, 0.2) !important;
    color: #f1f5f9 !important;
}

html[data-theme="dark"] .profile-field-input[readonly] {
    background: #0f172a !important;
}

html[data-theme="dark"] .profile-field-label {
    color: #cbd5e1 !important;
}

html[data-theme="dark"] .profile-info-value {
    color: #f1f5f9 !important;
}

html[data-theme="dark"] .profile-info-label {
    color: #94a3b8 !important;
}

html[data-theme="dark"] .profile-action-btn-secondary {
    background: #1e293b !important;
    border-color: rgba(226, 232, 240, 0.2) !important;
    color: #f1f5f9 !important;
}

html[data-theme="dark"] .profile-action-btn-secondary:hover {
    background: #334155 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreviewHero = document.getElementById('avatarPreviewHero');
    const avatarPlaceholder = document.querySelector('.profile-avatar-hero-placeholder');
    
    // Handle edit buttons for first_name, last_name, and email
    const editButtons = document.querySelectorAll('.profile-field-edit-btn');
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
                    this.classList.add('active');
                    this.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i>';
                    this.setAttribute('aria-label', 'Save ' + fieldName);
                } else {
                    // Disable editing
                    inputField.readOnly = true;
                    inputField.style.backgroundColor = '#f8fafc';
                    this.classList.remove('active');
                    this.innerHTML = '<i class="fas fa-edit" aria-hidden="true"></i>';
                    this.setAttribute('aria-label', 'Edit ' + fieldName);
                }
            }
        });
        
        // Keyboard support
        btn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // Handle avatar preview with hero image update
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showProfileNotification('Invalid file type. Please upload a JPG, PNG, or GIF image.', 'error');
                    avatarInput.value = '';
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showProfileNotification('File size too large. Maximum size is 2MB.', 'error');
                    avatarInput.value = '';
                    return;
                }
                
                // Create preview using FileReader
                const reader = new FileReader();
                reader.onload = function(readerEvent) {
                    // Update hero avatar
                    if (avatarPreviewHero) {
                        avatarPreviewHero.src = readerEvent.target.result;
                        avatarPreviewHero.style.display = 'block';
                    } else if (avatarPlaceholder) {
                        // Create new img element if it doesn't exist
                        const newImg = document.createElement('img');
                        newImg.id = 'avatarPreviewHero';
                        newImg.className = 'profile-avatar-hero-img';
                        newImg.src = readerEvent.target.result;
                        newImg.alt = 'Profile Photo';
                        newImg.style.display = 'block';
                        avatarPlaceholder.parentElement.insertBefore(newImg, avatarPlaceholder);
                        avatarPlaceholder.style.display = 'none';
                    }
                    
                    // Show success notification
                    showProfileNotification('Photo selected. Click "Save Changes" to update your profile.', 'success');
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Form submission with enhanced validation
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            // Basic validation - email is optional but if provided must be valid
            const email = document.getElementById('email').value.trim();
            
            if (email && !email.includes('@')) {
                e.preventDefault();
                showProfileNotification('Please enter a valid email address.', 'error');
                document.getElementById('email').focus();
                return false;
            }
            
            // Validate email format if provided
            if (email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    showProfileNotification('Please enter a valid email address format.', 'error');
                    document.getElementById('email').focus();
                    return false;
                }
            }
            
            // Disable button and show loading state
            if (saveProfileBtn) {
                saveProfileBtn.disabled = true;
                const originalContent = saveProfileBtn.innerHTML;
                saveProfileBtn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i><span>Saving Changes…</span>';
                
                // Re-enable if form validation fails (preventDefault was called)
                setTimeout(() => {
                    if (saveProfileBtn.disabled && !profileForm.checkValidity()) {
                        saveProfileBtn.disabled = false;
                        saveProfileBtn.innerHTML = originalContent;
                    }
                }, 100);
            }
        });
    }
    
    // Notification function
    function showProfileNotification(message, type = 'success') {
        // Remove existing notifications
        const existing = document.querySelector('.profile-alert-dynamic');
        if (existing) {
            existing.remove();
        }
        
        const alert = document.createElement('div');
        alert.className = `profile-alert profile-alert-${type} profile-alert-dynamic`;
        alert.setAttribute('role', 'alert');
        alert.setAttribute('aria-live', 'polite');
        alert.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}" aria-hidden="true"></i>
            <span>${message}</span>
            <button type="button" class="profile-alert-close" aria-label="Close alert" onclick="this.parentElement.remove()">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        `;
        
        const profilePage = document.querySelector('.profile-page');
        if (profilePage) {
            profilePage.insertBefore(alert, profilePage.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);
        }
    }
});
</script>
