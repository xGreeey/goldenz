<?php
// HR Admin Settings
$page_title = 'Settings - HR Admin - Golden Z-5 HR System';
$page = 'settings';

// Enforce HR Admin role
$user_role = $_SESSION['user_role'] ?? null;
$allowed_roles = ['hr_admin', 'hr', 'admin', 'accounting', 'operation', 'logistics'];
if (!in_array($user_role, $allowed_roles)) {
    header('Location: ../landing/index.php');
    exit;
}

// Get current user info
$current_user_id = $_SESSION['user_id'] ?? null;
$current_user = null;
if ($current_user_id && function_exists('get_user_by_id')) {
    require_once __DIR__ . '/../includes/database.php';
    $current_user = get_user_by_id($current_user_id);
}

// Check if current user's password has expired
$password_expired = false;
if ($current_user_id && function_exists('check_password_expiry')) {
    require_once __DIR__ . '/../includes/security.php';
    $expiry_check = check_password_expiry($current_user_id);
    $password_expired = !empty($expiry_check['expired']);
}

// Get current user avatar and data for header
$current_user_avatar = null;
$current_user_data = null;
if (!empty($_SESSION['user_id']) && function_exists('get_user_by_id')) {
    require_once __DIR__ . '/../includes/database.php';
    if (!function_exists('get_avatar_url')) {
        require_once __DIR__ . '/../includes/paths.php';
    }
    $current_user_data = get_user_by_id($_SESSION['user_id']);
    if (!empty($current_user_data['avatar'])) {
        $current_user_avatar = get_avatar_url($current_user_data['avatar']);
    }
}
?>

<!-- Header Section (copied from Super Admin Settings) -->
<div class="hrdash-welcome">
    <div class="hrdash-welcome__left">
        <h2 class="hrdash-welcome__title">Account Settings</h2>
        <p class="hrdash-welcome__subtitle">Manage your account security, profile, and preferences</p>
    </div>
    <div class="hrdash-welcome__actions">
        <span id="current-time-settings" class="hrdash-welcome__time"><?php echo strtolower(date('h:i A')); ?></span>
        
        <!-- Messages Dropdown -->
        <?php
        // Get recent messages/alerts (last 5 active alerts)
        $recentMessages = [];
        if (function_exists('get_employee_alerts')) {
            try {
                $recentMessages = get_employee_alerts('active', null);
                $recentMessages = array_slice($recentMessages, 0, 5);
            } catch (Exception $e) {
                $recentMessages = [];
            }
        }
        $messageCount = count($recentMessages);
        ?>
        <div class="dropdown">
            <button class="hrdash-welcome__icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Messages" aria-label="Messages">
                <i class="fas fa-envelope"></i>
                <?php if ($messageCount > 0): ?>
                    <span class="hrdash-welcome__badge"><?php echo $messageCount > 99 ? '99+' : $messageCount; ?></span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end hrdash-notification-dropdown">
                <li class="dropdown-header">
                    <strong>Messages</strong>
                    <a href="?page=alerts" class="text-decoration-none ms-auto">View All</a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <?php if (empty($recentMessages)): ?>
                    <li class="dropdown-item-text text-muted text-center py-3">
                        <i class="far fa-envelope-open fa-2x mb-2 d-block"></i>
                        <small>No new messages</small>
                    </li>
                <?php else: ?>
                    <?php foreach ($recentMessages as $msg): 
                        $priorityClass = '';
                        $priorityIcon = 'fa-info-circle';
                        switch(strtolower($msg['priority'] ?? '')) {
                            case 'urgent':
                                $priorityClass = 'text-danger';
                                $priorityIcon = 'fa-exclamation-triangle';
                                break;
                            case 'high':
                                $priorityClass = 'text-warning';
                                $priorityIcon = 'fa-exclamation-circle';
                                break;
                            default:
                                $priorityClass = 'text-info';
                        }
                        $employeeName = trim(($msg['surname'] ?? '') . ', ' . ($msg['first_name'] ?? '') . ' ' . ($msg['middle_name'] ?? ''));
                        $timeAgo = '';
                        if (!empty($msg['created_at'])) {
                            $created = new DateTime($msg['created_at']);
                            $now = new DateTime();
                            $diff = $now->diff($created);
                            if ($diff->days > 0) {
                                $timeAgo = $diff->days . 'd ago';
                            } elseif ($diff->h > 0) {
                                $timeAgo = $diff->h . 'h ago';
                            } else {
                                $timeAgo = $diff->i . 'm ago';
                            }
                        }
                    ?>
                        <li>
                            <a class="dropdown-item hrdash-notification-item" href="?page=alerts">
                                <div class="d-flex align-items-start">
                                    <i class="fas <?php echo $priorityIcon; ?> <?php echo $priorityClass; ?> me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small"><?php echo htmlspecialchars($msg['title'] ?? 'Alert'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($employeeName); ?></div>
                                        <?php if ($timeAgo): ?>
                                            <div class="text-muted" style="font-size: 0.7rem;"><?php echo $timeAgo; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Notifications Dropdown -->
        <?php
        // Get recent notifications (pending tasks)
        $recentNotifications = [];
        $pendingTasks = 0;
        if (function_exists('get_all_tasks')) {
            try {
                $recentNotifications = get_all_tasks('pending', null, null);
                $recentNotifications = array_slice($recentNotifications, 0, 5);
                $pendingTasks = count($recentNotifications);
            } catch (Exception $e) {
                $recentNotifications = [];
            }
        }
        if (function_exists('get_pending_task_count')) {
            try {
                $pendingTasks = (int) get_pending_task_count();
            } catch (Exception $e) {
                $pendingTasks = 0;
            }
        }
        ?>
        <div class="dropdown">
            <button class="hrdash-welcome__icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications" aria-label="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($pendingTasks > 0): ?>
                    <span class="hrdash-welcome__badge"><?php echo $pendingTasks > 99 ? '99+' : $pendingTasks; ?></span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end hrdash-notification-dropdown">
                <li class="dropdown-header">
                    <strong>Notifications</strong>
                    <a href="?page=tasks" class="text-decoration-none ms-auto">View All</a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <?php if (empty($recentNotifications)): ?>
                    <li class="dropdown-item-text text-muted text-center py-3">
                        <i class="far fa-bell-slash fa-2x mb-2 d-block"></i>
                        <small>No new notifications</small>
                    </li>
                <?php else: ?>
                    <?php foreach ($recentNotifications as $notif): 
                        $priorityClass = '';
                        $priorityIcon = 'fa-circle';
                        switch(strtolower($notif['priority'] ?? '')) {
                            case 'urgent':
                                $priorityClass = 'text-danger';
                                $priorityIcon = 'fa-exclamation-triangle';
                                break;
                            case 'high':
                                $priorityClass = 'text-warning';
                                $priorityIcon = 'fa-exclamation-circle';
                                break;
                            case 'medium':
                                $priorityClass = 'text-info';
                                $priorityIcon = 'fa-info-circle';
                                break;
                            default:
                                $priorityClass = 'text-muted';
                        }
                        $timeAgo = '';
                        if (!empty($notif['created_at'])) {
                            $created = new DateTime($notif['created_at']);
                            $now = new DateTime();
                            $diff = $now->diff($created);
                            if ($diff->days > 0) {
                                $timeAgo = $diff->days . 'd ago';
                            } elseif ($diff->h > 0) {
                                $timeAgo = $diff->h . 'h ago';
                            } else {
                                $timeAgo = $diff->i . 'm ago';
                            }
                        }
                    ?>
                        <li>
                            <a class="dropdown-item hrdash-notification-item" href="?page=tasks">
                                <div class="d-flex align-items-start">
                                    <i class="fas <?php echo $priorityIcon; ?> <?php echo $priorityClass; ?> me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small"><?php echo htmlspecialchars($notif['title'] ?? 'Task'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($notif['category'] ?? 'Task'); ?></div>
                                        <?php if ($timeAgo): ?>
                                            <div class="text-muted" style="font-size: 0.7rem;"><?php echo $timeAgo; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="dropdown">
            <button class="hrdash-welcome__profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Profile menu">
                <?php
                // Get name from first_name and last_name if available, otherwise use session name
                $displayName = trim((string)($_SESSION['name'] ?? ($_SESSION['username'] ?? 'HR Admin')));
                if (!empty($current_user_data)) {
                    $first_name = $current_user_data['first_name'] ?? '';
                    $last_name = $current_user_data['last_name'] ?? '';
                    if (!empty($first_name) || !empty($last_name)) {
                        $displayName = trim($first_name . ' ' . $last_name);
                    }
                }
                
                $initials = 'HA';
                if ($displayName) {
                    $parts = preg_split('/\s+/', $displayName);
                    $first = $parts[0][0] ?? 'H';
                    $last = (count($parts) > 1) ? ($parts[count($parts) - 1][0] ?? 'A') : ($parts[0][1] ?? 'A');
                    $initials = strtoupper($first . $last);
                }
                ?>
                <?php if ($current_user_avatar): ?>
                    <img src="<?php echo htmlspecialchars($current_user_avatar); ?>" 
                         alt="<?php echo htmlspecialchars($displayName); ?>" 
                         class="hrdash-welcome__avatar hrdash-welcome__avatar-img">
                <?php else: ?>
                    <span class="hrdash-welcome__avatar"><?php echo htmlspecialchars($initials); ?></span>
                <?php endif; ?>
                <i class="fas fa-chevron-down hrdash-welcome__chevron"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="?page=profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="?page=settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?php echo base_url(); ?>/index.php?logout=1" data-no-transition="true">
                        <i class="fas fa-right-from-bracket me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="container-fluid hr-admin-settings">

    <div class="row">
        <!-- Left: Navigation -->
        <div class="col-md-3 mb-4">
            <div class="card card-modern">
                <div class="card-body-modern p-0">
                    <div class="list-group settings-nav" id="settingsTabs" role="tablist">
                        <button class="list-group-item list-group-item-action active"
                                id="account-security-tab"
                                data-bs-toggle="list"
                                data-bs-target="#account-security"
                                type="button" role="tab">
                            <i class="fas fa-shield-alt me-2"></i>Account Security
                        </button>
                        <button class="list-group-item list-group-item-action"
                                id="profile-tab"
                                data-bs-toggle="list"
                                data-bs-target="#profile"
                                type="button" role="tab">
                            <i class="fas fa-user me-2"></i>Profile
                        </button>
                        <button class="list-group-item list-group-item-action"
                                id="preferences-tab"
                                data-bs-toggle="list"
                                data-bs-target="#preferences"
                                type="button" role="tab">
                            <i class="fas fa-cog me-2"></i>Preferences
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Content -->
        <div class="col-md-9">
            <div class="tab-content" id="settingsTabContent">
                <!-- Account Security -->
                <div class="tab-pane fade show active" id="account-security" role="tabpanel">
                    <!-- Change Password Section -->
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Change Password</h5>
                                <small class="card-subtitle">Update your account password to keep it secure.</small>
                            </div>

                            <div id="changePasswordAlert"></div>
                            
                            <form id="changePasswordForm" method="post" action="?page=settings">
                                <input type="hidden" name="action" value="change_password">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                        <div class="password-input-wrapper position-relative">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="current_password" 
                                                   name="current_password" 
                                                   required
                                                   autocomplete="current-password"
                                                   placeholder="Enter your current password">
                                            <button type="button" 
                                                    class="btn btn-link password-toggle" 
                                                    data-target="current_password"
                                                    aria-label="Show password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                        <div class="password-input-wrapper position-relative">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="new_password" 
                                                   name="new_password" 
                                                   required
                                                   minlength="8"
                                                   autocomplete="new-password"
                                                   placeholder="Minimum 8 characters">
                                            <button type="button" 
                                                    class="btn btn-link password-toggle" 
                                                    data-target="new_password"
                                                    aria-label="Show password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <!-- Password Strength Indicator -->
                                        <div class="password-strength-container mt-2">
                                            <div class="password-strength-bar">
                                                <div class="password-strength-fill" id="passwordStrengthBar"></div>
                                            </div>
                                            <div class="password-strength-text mt-2" id="passwordStrengthText">
                                                <small class="text-muted">Password strength: <span id="strengthLabel">None</span></small>
                                            </div>
                                            <div class="password-requirements mt-2" id="passwordRequirements">
                                                <small class="d-block mb-1 fw-semibold text-muted">Requirements:</small>
                                                <div class="requirement-item" data-requirement="length">
                                                    <i class="fas fa-circle requirement-icon"></i>
                                                    <span>Minimum 8 characters</span>
                                                </div>
                                                <div class="requirement-item" data-requirement="lowercase">
                                                    <i class="fas fa-circle requirement-icon"></i>
                                                    <span>Contains lowercase letter</span>
                                                </div>
                                                <div class="requirement-item" data-requirement="uppercase">
                                                    <i class="fas fa-circle requirement-icon"></i>
                                                    <span>Contains uppercase letter</span>
                                                </div>
                                                <div class="requirement-item" data-requirement="number">
                                                    <i class="fas fa-circle requirement-icon"></i>
                                                    <span>Contains number</span>
                                                </div>
                                                <div class="requirement-item" data-requirement="symbol">
                                                    <i class="fas fa-circle requirement-icon"></i>
                                                    <span>Contains symbol</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                        <div class="password-input-wrapper position-relative">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   required
                                                   minlength="8"
                                                   autocomplete="new-password"
                                                   placeholder="Re-enter new password">
                                            <button type="button" 
                                                    class="btn btn-link password-toggle" 
                                                    data-target="confirm_password"
                                                    aria-label="Show password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <!-- Password Match Indicator -->
                                        <div class="password-match-indicator mt-2" id="passwordMatchIndicator" style="display: none;">
                                            <div class="match-status">
                                                <i class="match-icon"></i>
                                                <span class="match-text"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary-modern" id="changePasswordBtn">
                                            <i class="fas fa-key me-2"></i>Change Password
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Profile -->
                <div class="tab-pane fade" id="profile" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Profile Information</h5>
                                <small class="card-subtitle">View and manage your account profile details.</small>
                            </div>

                            <!-- Profile Photo -->
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label">Profile Photo</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="profile-photo-preview">
                                            <?php 
                                            // Get profile photo from current user
                                            $profile_photo = null;
                                            if (!empty($current_user_data) && !empty($current_user_data['avatar'])) {
                                                $profile_photo = get_avatar_url($current_user_data['avatar']);
                                            }
                                            
                                            // Generate initials
                                            $profile_initials = 'HA';
                                            if (!empty($current_user_data)) {
                                                $first_name = $current_user_data['first_name'] ?? '';
                                                $last_name = $current_user_data['last_name'] ?? '';
                                                if (!empty($first_name) || !empty($last_name)) {
                                                    $first = strtoupper(substr($first_name, 0, 1));
                                                    $last = strtoupper(substr($last_name, 0, 1));
                                                    $profile_initials = $first . ($last ?: $first);
                                                }
                                            }
                                            ?>
                                            <?php if ($profile_photo): ?>
                                                <img src="<?php echo htmlspecialchars($profile_photo); ?>" 
                                                     alt="Profile Photo" 
                                                     class="profile-photo-img"
                                                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #e2e8f0; display: block;">
                                            <?php else: ?>
                                                <div class="profile-photo-placeholder" 
                                                     style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; border: 3px solid #e2e8f0;">
                                                    <?php echo htmlspecialchars($profile_initials); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted mb-0 small">Your profile photo is displayed in the header and throughout the system.</p>
                                            <p class="text-muted mb-0 small">To update your profile photo, please visit the <a href="?page=profile">Profile page</a>.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['username'] ?? $_SESSION['username'] ?? 'N/A'); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($current_user['email'] ?? $_SESSION['email'] ?? 'N/A'); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['name'] ?? $_SESSION['name'] ?? 'N/A'); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user_role ?? 'N/A'))); ?>" readonly>
                                </div>
                                <?php if (!empty($current_user['department'])): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Department</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['department']); ?>" readonly>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($current_user['phone'])): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['phone']); ?>" readonly>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-6">
                                    <label class="form-label">Account Status</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($current_user['status'] ?? 'Active')); ?>" readonly>
                                </div>
                                <?php if (!empty($current_user['created_at'])): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Member Since</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(date('F j, Y', strtotime($current_user['created_at']))); ?>" readonly>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="alert alert-info mt-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Profile information is managed by system administrators. Please contact your administrator if you need to update your profile details.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preferences -->
                <div class="tab-pane fade" id="preferences" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Display Preferences</h5>
                                <small class="card-subtitle">Customize your interface and display settings.</small>
                            </div>

                            <form class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Theme</label>
                                    <select class="form-select theme-select" id="settingsThemeSelect">
                                        <option value="light" selected>Light</option>
                                        <option value="dark">Dark</option>
                                        <option value="auto">Auto (System)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Table Density</label>
                                    <select class="form-select" disabled>
                                        <option>Comfortable</option>
                                        <option selected>Compact</option>
                                    </select>
                                    <small class="text-muted">Coming soon</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Default Landing Page</label>
                                    <select class="form-select" disabled>
                                        <option selected>Dashboard</option>
                                        <option>Employees</option>
                                        <option>Posts</option>
                                    </select>
                                    <small class="text-muted">Coming soon</small>
                                </div>
                                <div class="col-12 d-flex justify-content-end mt-2">
                                    <button type="button" class="btn btn-primary-modern save-ui-preferences-btn">
                                        <i class="fas fa-check me-2"></i>Save Preferences
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Password Expiry Modal - Forces password change when expired -->
<?php if ($password_expired): ?>
<div class="modal fade show" id="passwordExpiryModal" tabindex="-1" role="dialog" aria-labelledby="passwordExpiryModalLabel" aria-hidden="false" data-bs-backdrop="static" data-bs-keyboard="false" style="display: block !important; background-color: rgba(0,0,0,0.7); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <div class="modal-header bg-danger text-white" style="border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <h5 class="modal-title" id="passwordExpiryModalLabel" style="margin: 0; font-weight: 600;">
                    <i class="fas fa-exclamation-triangle me-2"></i>Password Expired - Action Required
                </h5>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div class="alert alert-warning mb-4">
                    <h6 class="alert-heading"><i class="fas fa-shield-alt me-2"></i>Security Alert</h6>
                    <p class="mb-0">Your password has expired for security reasons. You must change your password immediately to continue using the system.</p>
                </div>
                <p class="text-muted mb-4">
                    <strong>Why is this required?</strong><br>
                    Passwords expire periodically to protect your account from unauthorized access. This is a security best practice to prevent potential breaches.
                </p>
                <p class="mb-0"><strong>Please change your password below to continue.</strong></p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 1rem 1.5rem;">
                <small class="text-muted me-auto">You cannot dismiss this dialog until your password is changed.</small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.hr-admin-settings {
    padding: 2rem 2.5rem;
    max-width: 100%;
    background: #f8fafc;
    min-height: 100vh;
}

/* Navigation Sidebar - Card Style */
.hr-admin-settings .settings-nav {
    background: transparent;
    border: none;
    padding: 0.5rem;
}

.hr-admin-settings .settings-nav .list-group-item {
    border: none;
    border-radius: 8px;
    padding: 0.875rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
    transition: all 0.2s ease;
    color: #64748b;
}

.hr-admin-settings .settings-nav .list-group-item:hover {
    background: #f8fafc;
    color: #1e293b;
}

.hr-admin-settings .settings-nav .list-group-item i {
    width: 1.25rem;
    text-align: center;
    color: #64748b;
    font-size: 1rem;
}

.hr-admin-settings .settings-nav .list-group-item.active {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%);
    color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(30, 58, 138, 0.2);
}

.hr-admin-settings .settings-nav .list-group-item.active i {
    color: #ffffff;
}

/* Ensure cards match HR admin style */
.hr-admin-settings .card-modern {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    background: #ffffff;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.hr-admin-settings .card-modern:hover {
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
}

.hr-admin-settings .card-body-modern {
    padding: 1.5rem;
}

.hr-admin-settings .card-header-modern {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.hr-admin-settings .card-title-modern {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

.hr-admin-settings .card-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

/* Password Toggle Styles */
.hr-admin-settings .password-input-wrapper {
    position: relative;
}

.hr-admin-settings .password-input-wrapper .form-control {
    padding-right: 50px;
}

.hr-admin-settings .password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: none !important;
    background: transparent !important;
    padding: 0.5rem 0.625rem !important;
    color: #475569 !important;
    cursor: pointer;
    z-index: 10;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border-radius: 6px;
    display: flex !important;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    min-height: 36px;
    box-shadow: none !important;
    outline: none !important;
}

.hr-admin-settings .password-toggle:hover {
    color: #1e3a8a !important;
    background: transparent !important;
    transform: translateY(-50%) scale(1.1);
    box-shadow: none !important;
}

.hr-admin-settings .password-toggle:active {
    transform: translateY(-50%) scale(0.95);
    background: transparent !important;
    box-shadow: none !important;
}

.hr-admin-settings .password-toggle:focus,
.hr-admin-settings .password-toggle:active,
.hr-admin-settings .password-toggle:focus-visible {
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    border: none !important;
}

.hr-admin-settings .password-toggle i {
    font-size: 1.125rem !important;
    color: #475569 !important;
    transition: all 0.2s ease;
    display: block !important;
    line-height: 1 !important;
}

.hr-admin-settings .password-toggle:hover i {
    color: #1e3a8a !important;
}

.hr-admin-settings .password-toggle.btn-link {
    color: #475569 !important;
    text-decoration: none !important;
}

.hr-admin-settings .password-toggle.btn-link:hover {
    color: #1e3a8a !important;
    text-decoration: none !important;
}

/* Make Change Password button highly visible */
.hr-admin-settings #changePasswordBtn,
.hr-admin-settings .btn-primary-modern {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%) !important;
    color: #ffffff !important;
    border: none !important;
    padding: 0.75rem 1.5rem !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    font-size: 0.9375rem !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.35) !important;
    cursor: pointer !important;
    min-height: 44px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.hr-admin-settings #changePasswordBtn:hover,
.hr-admin-settings .btn-primary-modern:hover {
    background: linear-gradient(135deg, #1e40af 0%, #1e293b 50%, #1e3a8a 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(30, 58, 138, 0.45) !important;
}

.hr-admin-settings #changePasswordBtn:active,
.hr-admin-settings .btn-primary-modern:active {
    transform: translateY(0) !important;
    box-shadow: 0 2px 8px rgba(30, 58, 138, 0.3) !important;
}

.hr-admin-settings #changePasswordBtn:focus,
.hr-admin-settings .btn-primary-modern:focus,
.hr-admin-settings #changePasswordBtn:focus-visible,
.hr-admin-settings .btn-primary-modern:focus-visible {
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.2), 0 4px 12px rgba(30, 58, 138, 0.35) !important;
}

/* Password Strength Indicator */
.hr-admin-settings .password-strength-container {
    margin-top: 0.75rem;
}

.hr-admin-settings .password-strength-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

.hr-admin-settings .password-strength-fill {
    height: 100%;
    width: 0%;
    background: #ef4444;
    border-radius: 4px;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s ease;
    position: relative;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
    display: block;
}

.hr-admin-settings .password-strength-fill.weak {
    width: 33.33%;
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 0 12px rgba(239, 68, 68, 0.4);
}

.hr-admin-settings .password-strength-fill.normal {
    width: 66.66%;
    background: linear-gradient(90deg, #f59e0b 0%, #f97316 100%);
    box-shadow: 0 0 12px rgba(245, 158, 11, 0.4);
}

.hr-admin-settings .password-strength-fill.strong {
    width: 100%;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
}

.hr-admin-settings .password-strength-text {
    font-size: 0.8125rem;
}

.hr-admin-settings #strengthLabel {
    font-weight: 600;
}

.hr-admin-settings .password-requirements {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.hr-admin-settings .requirement-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.375rem;
    font-size: 0.8125rem;
    color: #64748b;
    transition: all 0.2s ease;
}

.hr-admin-settings .requirement-item:last-child {
    margin-bottom: 0;
}

.hr-admin-settings .requirement-icon {
    font-size: 0.5rem;
    color: #cbd5e1;
    transition: all 0.2s ease;
}

.hr-admin-settings .requirement-item.met .requirement-icon {
    color: #10b981;
}

.hr-admin-settings .requirement-item.met {
    color: #059669;
}

.hr-admin-settings .requirement-item.met .requirement-icon::before {
    content: "\f00c";
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    font-size: 0.75rem;
}

/* Password Match Indicator */
.hr-admin-settings .password-match-indicator {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    transition: all 0.3s ease;
}

.hr-admin-settings .password-match-indicator.match {
    background: #d1fae5;
    border: 1px solid #10b981;
    color: #059669;
}

.hr-admin-settings .password-match-indicator.mismatch {
    background: #fee2e2;
    border: 1px solid #ef4444;
    color: #dc2626;
}

.hr-admin-settings .match-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.hr-admin-settings .match-icon {
    font-size: 0.875rem;
    font-weight: 900;
    font-family: "Font Awesome 5 Free";
}

.hr-admin-settings .password-match-indicator.match .match-icon::before {
    content: "\f00c";
    color: #10b981;
}

.hr-admin-settings .password-match-indicator.mismatch .match-icon::before {
    content: "\f00d";
    color: #ef4444;
}

.hr-admin-settings .match-text {
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password Toggle Functionality
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    this.setAttribute('aria-label', 'Hide password');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    this.setAttribute('aria-label', 'Show password');
                }
            }
        });
    });
    
    // Password Strength Checker
    const newPasswordInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthLabel = document.getElementById('strengthLabel');
    const requirementItems = document.querySelectorAll('.requirement-item');
    
    if (newPasswordInput && strengthBar && strengthLabel) {
        function checkPasswordStrength(password) {
            const requirements = {
                length: password.length >= 8,
                lowercase: /[a-z]/.test(password),
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password),
                symbol: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
            };
            
            // Update requirement items
            requirementItems.forEach(item => {
                const requirement = item.getAttribute('data-requirement');
                if (requirements[requirement]) {
                    item.classList.add('met');
                } else {
                    item.classList.remove('met');
                }
            });
            
            // Count met requirements
            const metCount = Object.values(requirements).filter(Boolean).length;
            const totalRequirements = Object.keys(requirements).length;
            
            // Calculate strength - simplified to 3 levels
            let strength = 'None';
            let strengthClass = '';
            
            if (password.length === 0) {
                strength = 'None';
                strengthClass = '';
            } else if (metCount < 3 || password.length < 8) {
                strength = 'Weak';
                strengthClass = 'weak';
            } else if (metCount < 5) {
                strength = 'Normal';
                strengthClass = 'normal';
            } else {
                strength = 'Strong';
                strengthClass = 'strong';
            }
            
            // Update strength bar
            strengthBar.className = 'password-strength-fill ' + strengthClass;
            strengthLabel.textContent = strength;
            
            // Update strength label color
            const strengthText = document.getElementById('passwordStrengthText');
            if (strengthText) {
                const labelColor = strengthClass === 'strong' ? '#10b981' :
                                  strengthClass === 'normal' ? '#f59e0b' :
                                  strengthClass === 'weak' ? '#ef4444' : '#64748b';
                strengthText.querySelector('small').style.color = labelColor;
                strengthLabel.style.color = labelColor;
            }
            
            return {
                strength: strength,
                metCount: metCount,
                totalRequirements: totalRequirements,
                allMet: metCount === totalRequirements && password.length >= 8
            };
        }
        
        // Add event listener for real-time checking
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            checkPasswordStrength(password);
        });
        
        // Also check on keyup for better responsiveness
        newPasswordInput.addEventListener('keyup', function() {
            const password = this.value;
            checkPasswordStrength(password);
        });
        
        // Also check on paste
        newPasswordInput.addEventListener('paste', function() {
            setTimeout(() => {
                const password = this.value;
                checkPasswordStrength(password);
            }, 10);
        });
        
        // Check on focus to ensure it's active
        newPasswordInput.addEventListener('focus', function() {
            const password = this.value;
            checkPasswordStrength(password);
        });
        
        // Initialize on page load
        checkPasswordStrength(newPasswordInput.value || '');
    }
    
    // Password Match Checker
    const confirmPasswordInput = document.getElementById('confirm_password');
    const matchIndicator = document.getElementById('passwordMatchIndicator');
    const matchIcon = matchIndicator ? matchIndicator.querySelector('.match-icon') : null;
    const matchText = matchIndicator ? matchIndicator.querySelector('.match-text') : null;
    
    function checkPasswordMatch() {
        const newPassword = newPasswordInput ? newPasswordInput.value : '';
        const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';
        
        if (!matchIndicator || !matchIcon || !matchText) return;
        
        // Only show indicator if confirm password field has content
        if (confirmPassword.length === 0) {
            matchIndicator.style.display = 'none';
            return;
        }
        
        matchIndicator.style.display = 'block';
        
        if (newPassword === confirmPassword && newPassword.length > 0) {
            // Passwords match
            matchIndicator.className = 'password-match-indicator match mt-2';
            matchText.textContent = 'Passwords match';
            
            // Clear any custom validity
            if (confirmPasswordInput) {
                confirmPasswordInput.setCustomValidity('');
            }
        } else {
            // Passwords don't match
            matchIndicator.className = 'password-match-indicator mismatch mt-2';
            matchText.textContent = 'Passwords do not match';
            
            // Set custom validity
            if (confirmPasswordInput) {
                confirmPasswordInput.setCustomValidity('Passwords do not match');
            }
        }
    }
    
    // Add event listeners for real-time checking
    if (newPasswordInput && confirmPasswordInput) {
        // Check when new password changes
        newPasswordInput.addEventListener('input', checkPasswordMatch);
        newPasswordInput.addEventListener('keyup', checkPasswordMatch);
        
        // Check when confirm password changes
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        confirmPasswordInput.addEventListener('keyup', checkPasswordMatch);
        
        // Check on paste
        confirmPasswordInput.addEventListener('paste', function() {
            setTimeout(checkPasswordMatch, 10);
        });
        
        // Check on focus
        confirmPasswordInput.addEventListener('focus', checkPasswordMatch);
        
        // Initialize on page load
        checkPasswordMatch();
    }
    
    const changePasswordForm = document.getElementById('changePasswordForm');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const alertDiv = document.getElementById('changePasswordAlert');
    
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Clear previous alerts
            if (alertDiv) {
                alertDiv.innerHTML = '';
            }
            
            // Validate form
            if (!changePasswordForm.checkValidity()) {
                changePasswordForm.reportValidity();
                return;
            }
            
            // Get form values
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Validate password requirements
            const passwordRequirements = {
                length: newPassword.length >= 8,
                lowercase: /[a-z]/.test(newPassword),
                uppercase: /[A-Z]/.test(newPassword),
                number: /[0-9]/.test(newPassword),
                symbol: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(newPassword)
            };
            
            const missingRequirements = [];
            if (!passwordRequirements.length) missingRequirements.push('Minimum 8 characters');
            if (!passwordRequirements.lowercase) missingRequirements.push('Lowercase letter');
            if (!passwordRequirements.uppercase) missingRequirements.push('Uppercase letter');
            if (!passwordRequirements.number) missingRequirements.push('Number');
            if (!passwordRequirements.symbol) missingRequirements.push('Symbol');
            
            if (missingRequirements.length > 0) {
                if (alertDiv) {
                    alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Password must contain: ' + missingRequirements.join(', ') + '</div>';
                }
                return;
            }
            
            // Validate password match
            if (newPassword !== confirmPassword) {
                if (alertDiv) {
                    alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>New password and confirmation do not match</div>';
                }
                return;
            }
            
            // Disable submit button
            if (changePasswordBtn) {
                changePasswordBtn.disabled = true;
                changePasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Changing Password...';
            }
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);
            formData.append('confirm_password', confirmPassword);
            
            // Submit via AJAX
            let formAction = window.location.pathname + '?page=settings';
            
            fetch(formAction, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Server error: ' + response.status);
                    });
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (changePasswordBtn) {
                    changePasswordBtn.disabled = false;
                    changePasswordBtn.innerHTML = '<i class="fas fa-key me-2"></i>Change Password';
                }
                
                if (data && data.success) {
                    // Show success message
                    if (alertDiv) {
                        alertDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + (data.message || 'Password changed successfully') + '</div>';
                    }
                    
                    // Close password expiry modal if it exists
                    const expiryModal = document.getElementById('passwordExpiryModal');
                    if (expiryModal) {
                        const modalInstance = bootstrap.Modal.getInstance(expiryModal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                        expiryModal.style.display = 'none';
                        expiryModal.remove();
                    }
                    
                    // Reset form
                    changePasswordForm.reset();
                    
                    // Reset password strength indicator
                    const strengthBar = document.getElementById('passwordStrengthBar');
                    const strengthLabel = document.getElementById('strengthLabel');
                    if (strengthBar) {
                        strengthBar.className = 'password-strength-fill';
                        strengthBar.style.width = '0%';
                    }
                    if (strengthLabel) {
                        strengthLabel.textContent = 'None';
                    }
                    
                    // Hide password match indicator
                    const matchIndicator = document.getElementById('passwordMatchIndicator');
                    if (matchIndicator) {
                        matchIndicator.style.display = 'none';
                    }
                    
                    // Clear alert after 5 seconds
                    setTimeout(() => {
                        if (alertDiv) {
                            alertDiv.innerHTML = '';
                        }
                    }, 5000);
                } else {
                    // Show error message
                    const errorMsg = (data && data.message) ? data.message : 'Failed to change password. Please try again.';
                    if (alertDiv) {
                        alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + errorMsg + '</div>';
                        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }
            })
            .catch(error => {
                console.error('Change Password Error:', error);
                if (changePasswordBtn) {
                    changePasswordBtn.disabled = false;
                    changePasswordBtn.innerHTML = '<i class="fas fa-key me-2"></i>Change Password';
                }
                if (alertDiv) {
                    alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>An error occurred while changing the password. Please try again.</div>';
                    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });
    }
    
    // Prevent password expiry modal from being closed
    const passwordExpiryModal = document.getElementById('passwordExpiryModal');
    if (passwordExpiryModal) {
        // Prevent backdrop click from closing modal
        passwordExpiryModal.addEventListener('click', function(e) {
            if (e.target === passwordExpiryModal) {
                e.stopPropagation();
            }
        });
        
        // Prevent ESC key from closing modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && passwordExpiryModal.style.display === 'block') {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        
        // Initialize Bootstrap modal with static backdrop and no keyboard
        if (typeof bootstrap !== 'undefined') {
            const modalInstance = new bootstrap.Modal(passwordExpiryModal, {
                backdrop: 'static',
                keyboard: false
            });
            modalInstance.show();
        }
    }
});
</script>

<script>
// Update time display every minute for settings page
(function() {
    function updateTime() {
        const timeElement = document.getElementById('current-time-settings');
        if (timeElement) {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHours = hours % 12 || 12;
            const displayMinutes = minutes < 10 ? '0' + minutes : minutes;
            timeElement.textContent = displayHours + ':' + displayMinutes + ' ' + ampm.toLowerCase();
        }
    }
    
    // Update immediately
    updateTime();
    
    // Update every minute
    setInterval(updateTime, 60000);
})();
</script>
