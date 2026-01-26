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

<div class="container-fluid profile-page">
    <!-- Success/Error Messages -->
    <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
    <div class="profile-alert profile-alert-success profile-alert-auto-hide" role="alert" aria-live="polite" data-auto-hide="3000">
        <i class="fas fa-check-circle" aria-hidden="true"></i>
        <span>Profile updated successfully!</span>
        <button type="button" class="profile-alert-close" aria-label="Close alert" onclick="hideProfileAlert(this.parentElement)">
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
            <!-- Left Column: Profile Summary Card -->
            <div class="col-lg-4">
                <div class="profile-summary-card">
                    <div class="profile-summary-avatar-container" id="profileAvatarHero">
                        <?php if ($profile_photo): ?>
                            <img src="<?php echo htmlspecialchars($profile_photo); ?>" 
                                 alt="<?php echo htmlspecialchars($display_first_name . ' ' . $display_last_name); ?>" 
                                 id="avatarPreviewHero"
                                 class="profile-summary-avatar-img">
                        <?php else: ?>
                            <div class="profile-summary-avatar-placeholder">
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
                        <input type="file" 
                               class="form-control" 
                               id="avatarInput" 
                               name="avatar" 
                               accept="image/jpeg,image/jpg,image/png,image/gif"
                               style="display: none;"
                               aria-label="Upload profile photo">
                    </div>
                    <div class="profile-summary-content">
                        <h1 class="profile-summary-name">
                            <?php echo htmlspecialchars(trim($display_first_name . ' ' . $display_last_name) ?: $display_username); ?>
                        </h1>
                        <div class="profile-summary-details">
                            <?php if ($display_role): ?>
                                <div class="profile-summary-detail-item">
                                    <i class="fas fa-user-tag" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $display_role))); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($display_department): ?>
                                <div class="profile-summary-detail-item">
                                    <i class="fas fa-building" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($display_department); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($display_status): ?>
                                <div class="profile-summary-detail-item profile-summary-status-<?php echo strtolower($display_status); ?>">
                                    <i class="fas fa-circle" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars(ucfirst($display_status)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($member_since || $last_login): ?>
                        <div class="profile-summary-meta">
                            <?php if ($member_since): ?>
                                <div class="profile-summary-meta-item">
                                    <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                                    <span>Member since <?php echo date('M Y', strtotime($member_since)); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($last_login): ?>
                                <div class="profile-summary-meta-item">
                                    <i class="fas fa-clock" aria-hidden="true"></i>
                                    <span>
                                        <?php 
                                        $login_time = strtotime($last_login);
                                        $now = time();
                                        $diff = $now - $login_time;
                                        if ($diff < 3600) {
                                            echo 'Last login ' . floor($diff / 60) . ' minutes ago';
                                        } elseif ($diff < 86400) {
                                            echo 'Last login ' . floor($diff / 3600) . ' hours ago';
                                        } else {
                                            echo 'Last login ' . date('M d, Y', $login_time);
                                        }
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Profile Form Sections -->
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
                        
                        <div class="row g-3">
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
                    </div>
                </div>

                <!-- Save Changes Button -->
                <div class="profile-actions-footer">
                    <button type="submit" class="profile-action-btn profile-action-btn-primary" id="saveProfileBtn">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Profile Field Edit Modal -->
<div class="profile-edit-modal" id="profileEditModal" role="dialog" aria-labelledby="profileEditModalTitle" aria-hidden="true">
    <div class="profile-edit-modal-backdrop"></div>
    <div class="profile-edit-modal-dialog">
        <div class="profile-edit-modal-content">
            <div class="profile-edit-modal-header">
                <h3 class="profile-edit-modal-title" id="profileEditModalTitle">Edit Field</h3>
                <button type="button" class="profile-edit-modal-close" aria-label="Close modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="profile-edit-modal-body">
                <form class="profile-edit-form" id="profileEditForm">
                    <div class="profile-edit-field-group">
                        <label class="profile-edit-label" id="profileEditLabel">Field Label</label>
                        <input type="text" 
                               class="profile-edit-input" 
                               id="profileEditInput" 
                               autocomplete="off"
                               aria-labelledby="profileEditLabel">
                        <small class="profile-edit-hint" id="profileEditHint"></small>
                    </div>
                </form>
            </div>
            <div class="profile-edit-modal-footer">
                <button type="button" class="profile-edit-btn profile-edit-btn-cancel">Cancel</button>
                <button type="button" class="profile-edit-btn profile-edit-btn-save">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   PROFILE PAGE - TWO-COLUMN LAYOUT
   Modern, SaaS-grade, enterprise-friendly
   ============================================ */

/* Profile Summary Card - Left Column */
.profile-summary-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #E5E9F0;
    padding: 0;
    position: sticky;
    top: 2rem;
    height: fit-content;
    display: flex;
    flex-direction: column;
}

.profile-summary-avatar-container {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 1.25rem auto 1rem;
}

.profile-summary-avatar-img,
.profile-summary-avatar-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #E5E9F0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transition: all 0.25s ease;
}

.profile-summary-avatar-container:hover .profile-summary-avatar-img,
.profile-summary-avatar-container:hover .profile-summary-avatar-placeholder {
    border-color: #1fb2d5;
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.15);
}

.profile-summary-avatar-placeholder {
    background: linear-gradient(135deg, #0F1F3D 0%, #1C2F52 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.profile-avatar-upload-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.25s ease;
    backdrop-filter: blur(2px);
}

.profile-summary-avatar-container:hover .profile-avatar-upload-overlay {
    opacity: 1;
}

.profile-avatar-upload-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #1fb2d5;
    border: 2px solid #ffffff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.3);
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
    transform: scale(1.08);
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.4);
    background: #0ea5e9;
}

.profile-avatar-upload-btn:focus-visible {
    outline: 2px solid #1fb2d5;
    outline-offset: 2px;
}

.profile-summary-content {
    text-align: center;
    padding: 0 1.5rem 1.5rem;
    box-sizing: border-box;
}

.profile-summary-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.75rem 0;
    letter-spacing: -0.01em;
    line-height: 1.3;
    color: #0f172a;
}

.profile-summary-details {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #E5E9F0;
}

.profile-summary-detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 500;
}

.profile-summary-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
    margin-top: 0.75rem;
}

.profile-summary-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 400;
}

.profile-summary-meta-item i {
    font-size: 0.75rem;
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    color: #94a3b8 !important;
    background: none;
    border: none;
    padding: 0;
    margin: 0;
}

.profile-summary-detail-item i {
    font-size: 0.875rem;
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    color: #1fb2d5 !important;
    background: none;
    border: none;
    padding: 0;
    margin: 0;
}

.profile-summary-detail-item.profile-summary-status-active i {
    color: #22c55e !important;
}

.profile-summary-detail-item.profile-summary-status-inactive i {
    color: #94a3b8 !important;
}

.profile-summary-detail-item.profile-summary-status-suspended i {
    color: #ef4444 !important;
}

/* Profile Page Container */
.profile-page {
    padding: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
    background: #F4F6F9;
    min-height: calc(100vh - 200px);
    box-sizing: border-box;
}

/* Ensure row columns align at top baseline */
.profile-page .row {
    align-items: flex-start;
}

/* Profile Alerts */
.profile-alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.25rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 1;
    visibility: visible;
    transition: opacity 0.4s ease-out, visibility 0.4s ease-out, margin-bottom 0.4s ease-out;
}

.profile-alert.profile-alert-fading {
    opacity: 0;
    visibility: hidden;
    margin-bottom: 0;
    padding-top: 0;
    padding-bottom: 0;
    overflow: hidden;
    max-height: 0;
    transition: opacity 0.4s ease-out, visibility 0.4s ease-out, margin-bottom 0.4s ease-out, padding 0.4s ease-out, max-height 0.4s ease-out;
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
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #E5E9F0;
    overflow: hidden;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    margin-bottom: 1.5rem;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}

.profile-card:last-of-type {
    margin-bottom: 0;
}

.profile-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    border-color: rgba(31, 178, 213, 0.2);
}

.profile-card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    min-height: 56px;
    display: flex;
    align-items: center;
    box-sizing: border-box;
}

/* Ensure consistent header alignment across all cards */
.profile-card-personal .profile-card-header,
.profile-card-account .profile-card-header {
    padding: 1rem 1.5rem;
    min-height: 56px;
    box-sizing: border-box;
}

.profile-card-header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    min-height: 0;
    box-sizing: border-box;
}

.profile-card-header-content > div {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 0;
}

/* Clean standalone icon - vertically centered with title */
.profile-card-header-content .profile-card-icon {
    align-self: center;
    margin-top: 0;
    flex-shrink: 0;
}

/* For cards with subtitle, align icon with first line of title */
.profile-card-header-content:has(.profile-card-subtitle) {
    align-items: flex-start;
}

.profile-card-header-content:has(.profile-card-subtitle) .profile-card-icon {
    align-self: flex-start;
    margin-top: 0.1875rem; /* Fine-tune to align with title baseline */
}

/* For cards without subtitle, center everything */
.profile-card-account .profile-card-header-content {
    align-items: center;
}

.profile-card-account .profile-card-title {
    margin: 0;
    line-height: 1.3;
}

/* Clean standalone icon - no background, no borders, no wrapper */
.profile-card-icon {
    width: auto;
    height: auto;
    min-width: 0;
    min-height: 0;
    border-radius: 0;
    background: none;
    border: none;
    box-shadow: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
    margin-top: 0;
    line-height: 1;
    padding: 0;
}

.profile-card-icon i,
.profile-card-icon [class*="fa-"] {
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    color: #1fb2d5 !important;
}

.profile-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    padding: 0;
    letter-spacing: -0.01em;
    line-height: 1.3;
    display: block;
}

.profile-card-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0.25rem 0 0 0;
    line-height: 1.4;
}

.profile-card-body {
    padding: 1.5rem;
    background: #ffffff;
    box-sizing: border-box;
}

/* Ensure consistent spacing in all card bodies */
.profile-card-personal .profile-card-body,
.profile-card-account .profile-card-body {
    padding: 1.5rem;
    box-sizing: border-box;
}

/* Profile Field Groups */
.profile-field-group {
    margin-bottom: 1.5rem;
}

.profile-field-group:last-child {
    margin-bottom: 0;
}

.profile-field-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
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
    /* Clean standalone icon - no wrapper */
    background: none;
    border: none;
    padding: 0;
    margin: 0;
}

.profile-field-wrapper {
    position: relative;
    display: flex;
    align-items: stretch;
}

.profile-field-input {
    width: 100%;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.75rem 3rem 0.75rem 1rem;
    font-size: 0.9375rem;
    color: #1e293b;
    background: #ffffff;
    transition: border-color 0.2s cubic-bezier(0.4, 0, 0.2, 1), 
                box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1),
                background 0.2s ease;
    line-height: 1.5;
    display: flex;
    align-items: center;
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
    top: 50%;
    transform: translateY(-50%);
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
    transform: translateY(-50%) scale(1.05);
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
    padding: 0.625rem 0;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.profile-info-item:first-child {
    padding-top: 0;
}

.profile-info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.profile-info-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #64748b;
    margin-bottom: 0;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.4;
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
    /* Clean standalone icon - no wrapper */
    background: none;
    border: none;
    padding: 0;
    margin: 0;
}

.profile-info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.5;
    padding-left: 1.625rem; /* Align with label text (icon width + gap) */
}

/* Profile Action Buttons */
.profile-actions-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 0;
    padding-top: 1.5rem;
    padding-left: 0;
    padding-right: 0;
    border-top: none;
    box-sizing: border-box;
}

/* Align actions footer with card content padding */
.profile-card-account + .profile-actions-footer {
    padding-left: 0;
    padding-right: 0;
}

.profile-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
}

.profile-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), 
                box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1),
                background 0.2s ease;
    border: none;
    cursor: pointer;
    text-decoration: none;
    touch-action: manipulation;
    min-width: auto;
}

.profile-action-btn-primary {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
}

.profile-action-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.35);
    background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
    color: white;
}

.profile-action-btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
}

.profile-action-btn-primary:focus-visible {
    outline: 2px solid #0f172a;
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
.profile-summary-card i[class*="fa-"],
.profile-summary-card [class*="fa-"] {
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
.profile-summary-card i.fas,
.profile-summary-card [class*="fa-"].fas {
    font-weight: 900 !important;
}

.profile-page i.far,
.profile-page [class*="fa-"].far,
.profile-summary-card i.far,
.profile-summary-card [class*="fa-"].far {
    font-weight: 400 !important;
}

.profile-page i.fal,
.profile-page [class*="fa-"].fal,
.profile-summary-card i.fal,
.profile-summary-card [class*="fa-"].fal {
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
    .profile-summary-card {
        position: static;
        margin-bottom: 1.5rem;
    }
    
    .profile-page {
        padding: 1.5rem 1rem;
    }
    
    .profile-actions-footer {
        justify-content: stretch;
    }
    
    .profile-action-btn-primary {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .profile-summary-avatar-container {
        width: 80px;
        height: 80px;
    }
    
    .profile-summary-avatar-img,
    .profile-summary-avatar-placeholder {
        width: 80px;
        height: 80px;
    }
    
    .profile-summary-avatar-placeholder {
        font-size: 1.75rem;
    }
    
    .profile-summary-name {
        font-size: 1.125rem;
    }
    
    .profile-summary-card {
        padding: 1.25rem;
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

/* ============================================
   PROFILE EDIT MODAL
   Clean, centered popup with smooth animations
   ============================================ */

.profile-edit-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.2s ease, visibility 0.2s ease;
}

.profile-edit-modal.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

.profile-edit-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
}

.profile-edit-modal-dialog {
    position: relative;
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    transform: scale(0.95) translateY(-10px);
    opacity: 0;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.profile-edit-modal.active .profile-edit-modal-dialog {
    transform: scale(1) translateY(0);
    opacity: 1;
}

.profile-edit-modal-content {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12),
                0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid #E5E9F0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

.profile-edit-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid #E5E9F0;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}

.profile-edit-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.01em;
    line-height: 1.3;
}

.profile-edit-modal-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
    flex-shrink: 0;
}

.profile-edit-modal-close:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.profile-edit-modal-close:focus-visible {
    outline: 2px solid #1fb2d5;
    outline-offset: 2px;
}

.profile-edit-modal-close i {
    font-size: 1rem;
    font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free' !important;
    font-weight: 900 !important;
    font-style: normal !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.profile-edit-modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.profile-edit-field-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.profile-edit-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
    line-height: 1.4;
}

.profile-edit-input {
    width: 100%;
    padding: 0.875rem 1rem;
    font-size: 1rem;
    font-weight: 400;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #E5E9F0;
    border-radius: 8px;
    transition: all 0.2s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.profile-edit-input:focus {
    outline: none;
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

.profile-edit-input::placeholder {
    color: #94a3b8;
}

.profile-edit-hint {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
}

.profile-edit-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid #E5E9F0;
    background: #f8fafc;
}

.profile-edit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    text-decoration: none;
    touch-action: manipulation;
    min-width: 100px;
    font-family: inherit;
}

.profile-edit-btn-cancel {
    background: #ffffff;
    color: #64748b;
    border: 1px solid #E5E9F0;
}

.profile-edit-btn-cancel:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #cbd5e1;
}

.profile-edit-btn-cancel:focus-visible {
    outline: 2px solid #1fb2d5;
    outline-offset: 2px;
}

.profile-edit-btn-save {
    background: #1fb2d5;
    color: white;
    box-shadow: 0 2px 8px rgba(31, 178, 213, 0.3);
}

.profile-edit-btn-save:hover {
    background: #0ea5e9;
    box-shadow: 0 4px 12px rgba(31, 178, 213, 0.4);
    transform: translateY(-1px);
}

.profile-edit-btn-save:active {
    transform: translateY(0);
}

.profile-edit-btn-save:focus-visible {
    outline: 2px solid #1fb2d5;
    outline-offset: 2px;
}

.profile-edit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

/* Dark theme support for modal */
html[data-theme="dark"] .profile-edit-modal-backdrop {
    background: rgba(0, 0, 0, 0.7);
}

html[data-theme="dark"] .profile-edit-modal-content {
    background: #1e293b;
    border-color: rgba(226, 232, 240, 0.1);
}

html[data-theme="dark"] .profile-edit-modal-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-color: rgba(226, 232, 240, 0.1);
}

html[data-theme="dark"] .profile-edit-modal-title {
    color: #f1f5f9;
}

html[data-theme="dark"] .profile-edit-modal-close {
    color: #94a3b8;
}

html[data-theme="dark"] .profile-edit-modal-close:hover {
    background: #334155;
    color: #f1f5f9;
}

html[data-theme="dark"] .profile-edit-input {
    background: #0f172a;
    border-color: rgba(226, 232, 240, 0.2);
    color: #f1f5f9;
}

html[data-theme="dark"] .profile-edit-input:focus {
    border-color: #1fb2d5;
}

html[data-theme="dark"] .profile-edit-label {
    color: #cbd5e1;
}

html[data-theme="dark"] .profile-edit-hint {
    color: #94a3b8;
}

html[data-theme="dark"] .profile-edit-modal-footer {
    background: #1e293b;
    border-color: rgba(226, 232, 240, 0.1);
}

html[data-theme="dark"] .profile-edit-btn-cancel {
    background: #334155;
    color: #f1f5f9;
    border-color: rgba(226, 232, 240, 0.2);
}

html[data-theme="dark"] .profile-edit-btn-cancel:hover {
    background: #475569;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .profile-edit-modal-dialog {
        max-width: 100%;
        margin: 0;
    }
    
    .profile-edit-modal-content {
        border-radius: 12px 12px 0 0;
        max-height: 85vh;
    }
    
    .profile-edit-modal-header,
    .profile-edit-modal-body,
    .profile-edit-modal-footer {
        padding: 1.25rem;
    }
    
    .profile-edit-modal-footer {
        flex-direction: column-reverse;
    }
    
    .profile-edit-btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreviewHero = document.getElementById('avatarPreviewHero');
    const avatarPlaceholder = document.querySelector('.profile-summary-avatar-placeholder');
    
    // Auto-hide success alerts after specified duration
    function hideProfileAlert(alertElement) {
        if (!alertElement) return;
        
        alertElement.classList.add('profile-alert-fading');
        
        // Remove from DOM after transition completes
        setTimeout(function() {
            if (alertElement && alertElement.parentNode) {
                alertElement.remove();
            }
        }, 400); // Match CSS transition duration
    }
    
    function cleanUrlParameter() {
        // Remove 'updated' parameter from URL without page reload
        if (window.location.search.includes('updated=')) {
            const url = new URL(window.location);
            url.searchParams.delete('updated');
            window.history.replaceState({}, '', url.pathname + url.search);
        }
    }
    
    function initAutoHideAlerts() {
        const autoHideAlerts = document.querySelectorAll('.profile-alert-auto-hide');
        autoHideAlerts.forEach(function(alert) {
            const delay = parseInt(alert.getAttribute('data-auto-hide')) || 3000;
            
            // Clean URL immediately when alert is shown (so refresh won't show it again)
            cleanUrlParameter();
            
            // Set up auto-hide timer
            const timer = setTimeout(function() {
                hideProfileAlert(alert);
            }, delay);
            
            // Clear timer if user manually closes
            const closeBtn = alert.querySelector('.profile-alert-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    clearTimeout(timer);
                });
            }
        });
    }
    
    // Initialize auto-hide for success alerts
    initAutoHideAlerts();
    
    // Also clean URL on page load if parameter exists (fallback)
    if (window.location.search.includes('updated=')) {
        // Small delay to ensure message is visible before cleaning URL
        setTimeout(cleanUrlParameter, 100);
    }
    
    // Profile Edit Modal
    const editModal = document.getElementById('profileEditModal');
    const editModalBackdrop = editModal.querySelector('.profile-edit-modal-backdrop');
    const editModalClose = editModal.querySelector('.profile-edit-modal-close');
    const editModalCancel = editModal.querySelector('.profile-edit-btn-cancel');
    const editModalSave = editModal.querySelector('.profile-edit-btn-save');
    const editModalTitle = editModal.querySelector('.profile-edit-modal-title');
    const editModalLabel = editModal.querySelector('.profile-edit-label');
    const editModalInput = editModal.querySelector('.profile-edit-input');
    const editModalHint = editModal.querySelector('.profile-edit-hint');
    let currentFieldName = null;
    let currentFieldInput = null;
    let currentEditButton = null;
    
    // Field configuration
    const fieldConfig = {
        'first_name': {
            label: 'First Name',
            placeholder: 'Enter your first name',
            hint: '',
            type: 'text',
            autocomplete: 'given-name'
        },
        'last_name': {
            label: 'Last Name',
            placeholder: 'Enter your last name',
            hint: '',
            type: 'text',
            autocomplete: 'family-name'
        },
        'email': {
            label: 'Email Address',
            placeholder: 'Enter your email address',
            hint: 'We\'ll use this email for important notifications.',
            type: 'email',
            autocomplete: 'email'
        },
        'username': {
            label: 'Username',
            placeholder: 'Enter your username',
            hint: 'Username cannot be changed after creation.',
            type: 'text',
            autocomplete: 'username',
            readonly: true
        }
    };
    
    // Open modal function
    function openEditModal(fieldName, editButton) {
        const config = fieldConfig[fieldName];
        if (!config) return;
        
        const originalInput = document.getElementById(fieldName);
        if (!originalInput) return;
        
        currentFieldName = fieldName;
        currentFieldInput = originalInput;
        currentEditButton = editButton;
        
        // Set modal content
        editModalTitle.textContent = 'Edit ' + config.label;
        editModalLabel.textContent = config.label;
        editModalInput.type = config.type;
        editModalInput.value = originalInput.value || '';
        editModalInput.placeholder = config.placeholder;
        editModalInput.autocomplete = config.autocomplete;
        editModalInput.readOnly = config.readonly || false;
        editModalHint.textContent = config.hint || '';
        editModalHint.style.display = config.hint ? 'block' : 'none';
        
        // Disable save button if readonly
        if (config.readonly) {
            editModalSave.disabled = true;
            editModalSave.style.opacity = '0.5';
            editModalInput.style.cursor = 'not-allowed';
        } else {
            editModalSave.disabled = false;
            editModalSave.style.opacity = '1';
            editModalInput.style.cursor = 'text';
        }
        
        // Show modal with animation
        editModal.classList.add('active');
        editModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        // Focus input after animation
        setTimeout(function() {
            if (!config.readonly) {
                editModalInput.focus();
                editModalInput.select();
            }
        }, 100);
    }
    
    // Close modal function
    function closeEditModal() {
        editModal.classList.remove('active');
        editModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        currentFieldName = null;
        currentFieldInput = null;
        currentEditButton = null;
    }
    
    // Save changes function
    function saveEditModal() {
        if (!currentFieldInput || !currentFieldName) return;
        
        const newValue = editModalInput.value.trim();
        const config = fieldConfig[currentFieldName];
        
        // Validation
        if (config.type === 'email' && newValue) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(newValue)) {
                editModalInput.focus();
                editModalInput.style.borderColor = '#ef4444';
                setTimeout(function() {
                    editModalInput.style.borderColor = '';
                }, 2000);
                return;
            }
        }
        
        // Update original input
        currentFieldInput.value = newValue;
        
        // Close modal
        closeEditModal();
        
        // Show success feedback
        if (currentEditButton) {
            currentEditButton.style.transform = 'scale(1.1)';
            setTimeout(function() {
                if (currentEditButton) {
                    currentEditButton.style.transform = '';
                }
            }, 200);
        }
    }
    
    // Event listeners for modal
    editModalBackdrop.addEventListener('click', closeEditModal);
    editModalClose.addEventListener('click', closeEditModal);
    editModalCancel.addEventListener('click', closeEditModal);
    editModalSave.addEventListener('click', saveEditModal);
    
    // Keyboard support
    editModal.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
        } else if (e.key === 'Enter' && e.ctrlKey) {
            e.preventDefault();
            saveEditModal();
        }
    });
    
    // Handle edit buttons for first_name, last_name, email, and username
    const editButtons = document.querySelectorAll('.profile-field-edit-btn');
    editButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const fieldName = this.getAttribute('data-field');
            if (fieldName && fieldConfig[fieldName]) {
                openEditModal(fieldName, this);
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
    
    // Also handle username field if it has an edit button (for future use)
    const usernameInput = document.getElementById('username');
    if (usernameInput && !usernameInput.closest('.profile-field-wrapper')) {
        // If username gets an edit button in the future, it will work
    }
    
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
                        newImg.className = 'profile-summary-avatar-img';
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
        
        // Add auto-hide for success messages
        if (type === 'success') {
            alert.classList.add('profile-alert-auto-hide');
            alert.setAttribute('data-auto-hide', '3000');
        }
        
        alert.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}" aria-hidden="true"></i>
            <span>${message}</span>
            <button type="button" class="profile-alert-close" aria-label="Close alert" onclick="hideProfileAlert(this.parentElement)">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        `;
        
        const profilePage = document.querySelector('.profile-page');
        if (profilePage) {
            profilePage.insertBefore(alert, profilePage.firstChild);
            
            // Auto-hide success messages after 3 seconds
            if (type === 'success') {
                setTimeout(function() {
                    hideProfileAlert(alert);
                }, 3000);
            }
        }
    }
});
</script>
