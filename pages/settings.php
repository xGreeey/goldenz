<?php
// Super Admin Settings – UI scaffold only (password policy now editable)
$page_title = 'Settings - Super Admin - Golden Z-5 HR System';
$page = 'settings';

// Enforce Super Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../landing/index.php');
    exit;
}

// Load password policy (global for all users) and current user 2FA status
if (!function_exists('get_password_policy') || !function_exists('get_user_by_id') || !function_exists('check_password_expiry')) {
    require_once __DIR__ . '/../includes/database.php';
    require_once __DIR__ . '/../includes/security.php';
}
$password_policy = get_password_policy();
$password_min_length = (int)($password_policy['min_length'] ?? 8);
$password_require_special = !empty($password_policy['require_special']);
$password_expiry_days = (int)($password_policy['expiry_days'] ?? 90);

// Check if current user's password has expired
$password_expired = false;
$current_user_id = $_SESSION['user_id'] ?? null;
if ($current_user_id) {
    $expiry_check = check_password_expiry($current_user_id);
    $password_expired = !empty($expiry_check['expired']);
}

// Current user 2FA state (for super admin)
$two_factor_enabled = false;
$two_factor_secret = null;
$two_factor_pending = false;
$two_factor_otpauth = null;

if ($current_user_id) {
    $current_user = get_user_by_id($current_user_id);
    if ($current_user) {
        $two_factor_enabled = !empty($current_user['two_factor_enabled']);
        $two_factor_secret = $current_user['two_factor_secret'] ?? null;
    }
}

// If there is a pending secret in session, prefer that for setup flow
if (!empty($_SESSION['pending_2fa_secret'])) {
    $two_factor_pending = true;
    $two_factor_secret = $_SESSION['pending_2fa_secret'];
}

if (!empty($two_factor_secret)) {
    $label = rawurlencode('Golden Z-5:' . ($_SESSION['username'] ?? 'superadmin'));
    $issuer = rawurlencode('Golden Z-5 HR');
    $two_factor_otpauth = "otpauth://totp/{$label}?secret={$two_factor_secret}&issuer={$issuer}";
}
?>

<div class="container-fluid super-admin-settings">
    <div class="page-header-modern mb-4">
        <div class="page-title-modern">
            <h1 class="page-title-main">System Settings</h1>
            <p class="page-subtitle">Manage security, roles, policies, configuration, and preferences.</p>
        </div>
    </div>

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
                        id="roles-permissions-tab"
                        data-bs-toggle="list"
                        data-bs-target="#roles-permissions"
                        type="button" role="tab">
                    <i class="fas fa-user-shield me-2"></i>Roles & Permissions
                </button>
                <button class="list-group-item list-group-item-action"
                        id="user-policies-tab"
                        data-bs-toggle="list"
                        data-bs-target="#user-policies"
                        type="button" role="tab">
                    <i class="fas fa-file-contract me-2"></i>User Policies
                </button>
                <button class="list-group-item list-group-item-action"
                        id="system-config-tab"
                        data-bs-toggle="list"
                        data-bs-target="#system-config"
                        type="button" role="tab">
                    <i class="fas fa-sliders-h me-2"></i>System Configuration
                </button>
                <button class="list-group-item list-group-item-action"
                        id="module-access-tab"
                        data-bs-toggle="list"
                        data-bs-target="#module-access"
                        type="button" role="tab">
                    <i class="fas fa-th-large me-2"></i>Module Access
                </button>
                <button class="list-group-item list-group-item-action"
                        id="audit-logs-tab"
                        data-bs-toggle="list"
                        data-bs-target="#audit-logs-settings"
                        type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Audit & Activity
                </button>
                <button class="list-group-item list-group-item-action"
                        id="notifications-tab"
                        data-bs-toggle="list"
                        data-bs-target="#notifications"
                        type="button" role="tab">
                    <i class="fas fa-bell me-2"></i>Notifications
                </button>
                <button class="list-group-item list-group-item-action"
                        id="backup-tab"
                        data-bs-toggle="list"
                        data-bs-target="#backup"
                        type="button" role="tab">
                    <i class="fas fa-database me-2"></i>Backup & Retention
                </button>
                <button class="list-group-item list-group-item-action"
                        id="ui-preferences-tab"
                        data-bs-toggle="list"
                        data-bs-target="#ui-preferences"
                        type="button" role="tab">
                    <i class="fas fa-palette me-2"></i>UI & Preferences
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

                    <!-- Two-Factor Authentication (2FA) for current account -->
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title-modern mb-0">Two-Factor Authentication (2FA)</h5>
                                    <small class="card-subtitle">Add an extra layer of security to your Super Admin account using Google Authenticator.</small>
                                </div>
                                <div>
                                    <?php if ($two_factor_enabled): ?>
                                        <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2 rounded-pill">
                                            <i class="fas fa-shield-alt me-1"></i> Enabled
                                        </span>
                                    <?php elseif ($two_factor_pending): ?>
                                        <span class="badge bg-warning-subtle text-warning fw-semibold px-3 py-2 rounded-pill">
                                            <i class="fas fa-hourglass-half me-1"></i> Setup in progress
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2 rounded-pill">
                                            <i class="fas fa-shield-alt me-1"></i> Not enabled
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($two_factor_enabled): ?>
                                <p class="text-muted mb-3">
                                    Two-factor authentication is currently <strong>enabled</strong> on your account. You will be required to enter a 6‑digit code from Google Authenticator after your password (once the login flow is wired to use 2FA).
                                </p>
                                <form method="post" action="?page=settings" class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <input type="hidden" name="action" value="disable_2fa">
                                    <div class="text-muted small">
                                        Lost access to your authenticator app? Disable 2FA here, then re-enable it with a new device.
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="fas fa-times-circle me-2"></i>Disable 2FA
                                    </button>
                                </form>
                            <?php elseif ($two_factor_pending && $two_factor_otpauth): ?>
                                <div class="row align-items-center">
                                    <div class="col-md-5 text-center mb-3 mb-md-0">
                                        <div class="p-3 rounded-3 bg-light border">
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&amp;data=<?php echo urlencode($two_factor_otpauth); ?>" 
                                                 alt="Scan this QR code with Google Authenticator" 
                                                 class="img-fluid mb-2">
                                            <div class="small text-muted">
                                                Scan this QR code in the <strong>Google Authenticator</strong> app.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <form method="post" action="?page=settings">
                                            <input type="hidden" name="action" value="confirm_enable_2fa">
                                            <div class="mb-3">
                                                <label class="form-label">Secret key (for backup)</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($two_factor_secret); ?>" readonly>
                                                <small class="text-muted">Keep this secret safe. You can use it to restore 2FA on a new device.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label for="two_factor_code" class="form-label">6‑digit code from Google Authenticator</label>
                                                <input type="text" 
                                                       id="two_factor_code" 
                                                       name="two_factor_code" 
                                                       class="form-control" 
                                                       inputmode="numeric"
                                                       autocomplete="one-time-code"
                                                       pattern="[0-9]{6}" 
                                                       maxlength="6" 
                                                       required 
                                                       placeholder="Enter the 6-digit code"
                                                       oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,6);">
                                                <small class="text-muted">Enter the current 6‑digit code shown for this account in your authenticator app.</small>
                                            </div>
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="?page=settings" class="btn btn-outline-secondary">
                                                    Cancel
                                                </a>
                                                <button type="submit" class="btn btn-primary-modern">
                                                    <i class="fas fa-check me-2"></i>Verify &amp; Enable 2FA
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-3">
                                    Protect your Super Admin account with a one‑time code from the <strong>Google Authenticator</strong> app in addition to your password.
                                </p>
                                <ul class="text-muted small mb-3">
                                    <li>We will generate a unique secret and QR code for your account.</li>
                                    <li>You scan the QR code using Google Authenticator and confirm the 6‑digit code.</li>
                                    <li>Once confirmed, 2FA will be enabled for your Super Admin login.</li>
                                </ul>
                                <form method="post" action="?page=settings" class="d-flex justify-content-end">
                                    <input type="hidden" name="action" value="start_2fa_setup">
                                    <button type="submit" class="btn btn-primary-modern">
                                        <i class="fas fa-shield-alt me-2"></i>Set up 2FA for this account
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Password Policy Section -->
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Password Policy</h5>
                                <small class="card-subtitle">System-wide password requirements and settings.</small>
                            </div>

                            <form method="post" action="?page=settings" id="passwordPolicyForm">
                                <input type="hidden" name="action" value="update_password_policy">
                                <h6 class="mb-3">Password Policy</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label">Minimum Length</label>
                                        <input type="number"
                                               class="form-control"
                                               name="password_min_length"
                                               min="4"
                                               value="<?php echo htmlspecialchars($password_min_length); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Require Special Characters</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="password_require_special"
                                                   value="1"
                                                   <?php echo $password_require_special ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Enabled</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Password Expiry (days)</label>
                                        <input type="number"
                                               class="form-control"
                                               name="password_expiry_days"
                                               min="0"
                                               value="<?php echo htmlspecialchars($password_expiry_days); ?>">
                                        <small class="text-muted">0 = never expires</small>
                                        <div class="mt-2 p-2 bg-info bg-opacity-10 border border-info border-opacity-25 rounded">
                                            <small class="text-info">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <strong>How it works:</strong> Passwords expire after <?php echo $password_expiry_days; ?> days. When expired, users are automatically prompted to change their password before accessing the system. This prevents unauthorized access from compromised passwords.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-primary-modern" id="updatePasswordPolicyBtn">
                                        <i class="fas fa-save me-2"></i>Save Password Policy
                                    </button>
                                </div>

                                <h6 class="mb-3 mt-4">Two-Factor Authentication (2FA)</h6>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="require2FA" disabled>
                                        <label class="form-check-label" for="require2FA">
                                            Require 2FA for all admin-level accounts
                                        </label>
                                    </div>
                                    <small class="text-muted d-block">
                                        (UI only – hook this to your 2FA implementation.)
                                    </small>
                                </div>

                                <h6 class="mb-3">Active Sessions</h6>
                                <p class="text-muted small mb-2">
                                    For production, implement a real session manager that lists and revokes active sessions.
                                </p>
                                <button type="button" class="btn btn-outline-modern btn-sm" disabled>
                                    <i class="fas fa-sign-out-alt me-2"></i>Revoke all active sessions
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Roles & Permissions Defaults -->
                <div class="tab-pane fade" id="roles-permissions" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Role & Permission Defaults</h5>
                                <small class="card-subtitle">View and plan default access for each role.</small>
                            </div>

                            <p class="text-muted small mb-3">
                                This panel is a read-only summary of `config/roles.php`. You can extend it later to edit roles dynamically.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Role</th>
                                            <th>Description</th>
                                            <th>Key Permissions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $roles_config = include __DIR__ . '/../config/roles.php';
                                        foreach ($roles_config['roles'] as $key => $role):
                                            $perms = $role['permissions'] ?? [];
                                            $summary = in_array('*', $perms, true)
                                                ? 'Full system access'
                                                : (count($perms) ? implode(', ', array_slice($perms, 0, 4)) . (count($perms) > 4 ? '…' : '') : 'None');
                                        ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($role['name'] ?? ucfirst($key)); ?></strong></td>
                                                <td><?php echo htmlspecialchars($role['description'] ?? ''); ?></td>
                                                <td class="small text-muted"><?php echo htmlspecialchars($summary); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Policies -->
                <div class="tab-pane fade" id="user-policies" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">User Policies</h5>
                                <small class="card-subtitle">Define onboarding, acceptable use, and account rules.</small>
                            </div>

                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Acceptable Use Policy</label>
                                    <textarea class="form-control" rows="4" placeholder="Describe allowed and prohibited system use…" disabled></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password & Account Lock Policy</label>
                                    <textarea class="form-control" rows="3" placeholder="Describe lockout thresholds, reset rules, etc…" disabled></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Data Privacy Notice</label>
                                    <textarea class="form-control" rows="3" placeholder="Describe how employee data is used and protected…" disabled></textarea>
                                </div>
                                <button type="button" class="btn btn-primary-modern" disabled>
                                    <i class="fas fa-save me-2"></i>Save Policies (wire later)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- System Configuration -->
                <div class="tab-pane fade" id="system-config" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">System Configuration</h5>
                                <small class="card-subtitle">High-level system options (environment, URLs, mail, etc.).</small>
                            </div>

                            <form class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Environment</label>
                                    <select class="form-select" disabled>
                                        <option selected>Production</option>
                                        <option>Staging</option>
                                        <option>Local</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Base URL</label>
                                    <input type="text" class="form-control" placeholder="https://your-hr-system.example.com" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Outgoing Email (From)</label>
                                    <input type="email" class="form-control" placeholder="no-reply@goldenz5.com" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" placeholder="smtp.example.com" disabled>
                                </div>
                                <div class="col-md-12">
                                    <small class="text-muted">
                                        These fields are UI-only placeholders. In production, back them with a config table or `.env` file.
                                    </small>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Module Access -->
                <div class="tab-pane fade" id="module-access" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Module Access</h5>
                                <small class="card-subtitle">Toggle major modules on or off for the whole system.</small>
                            </div>

                            <p class="text-muted small">
                                This is a visual control panel. To fully enforce module access, connect these toggles to permission checks.
                            </p>

                            <div class="row g-3">
                                <?php
                                $modules = [
                                    'Employees & HR Core',
                                    'Posts & Locations',
                                    'Time & Attendance',
                                    'Leave & Time Off',
                                    'Alerts & Messaging',
                                    'Integrations & API',
                                ];
                                foreach ($modules as $module):
                                ?>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($module); ?></strong><br>
                                                <small class="text-muted">System-wide module visibility.</small>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" checked disabled>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Audit Logs Settings -->
                <div class="tab-pane fade" id="audit-logs-settings" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Audit & Activity Logs</h5>
                                <small class="card-subtitle">Configure how long to retain audit and security logs.</small>
                            </div>

                            <form class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Audit Log Retention</label>
                                    <select class="form-select" disabled>
                                        <option>30 days</option>
                                        <option selected>90 days</option>
                                        <option>180 days</option>
                                        <option>1 year</option>
                                        <option>Forever</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Security Log Retention</label>
                                    <select class="form-select" disabled>
                                        <option>30 days</option>
                                        <option>90 days</option>
                                        <option selected>180 days</option>
                                        <option>1 year</option>
                                        <option>Forever</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Log Level</label>
                                    <select class="form-select" disabled>
                                        <option>Errors only</option>
                                        <option>Warnings & errors</option>
                                        <option selected>All activity</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted">Hook these controls into a log rotation / cleanup job later.</small>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="tab-pane fade" id="notifications" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Notification Settings</h5>
                                <small class="card-subtitle">Control email and in-app alerts for critical events.</small>
                            </div>

                            <form class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">System Alerts</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked disabled>
                                        <label class="form-check-label">License / clearance expiry alerts</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked disabled>
                                        <label class="form-check-label">Security events & suspicious logins</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" disabled>
                                        <label class="form-check-label">Daily summary email</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Delivery Channels</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked disabled>
                                        <label class="form-check-label">In-app notifications</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked disabled>
                                        <label class="form-check-label">Email</label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Backup & Retention -->
                <div class="tab-pane fade" id="backup" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Data Backup & Retention</h5>
                                <small class="card-subtitle">Plan backups and data lifecycle. (UI only for now.)</small>
                            </div>

                            <form class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Backup Frequency</label>
                                    <select class="form-select" disabled>
                                        <option>Manual only</option>
                                        <option selected>Daily</option>
                                        <option>Weekly</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Retention Period</label>
                                    <select class="form-select" disabled>
                                        <option>30 days</option>
                                        <option selected>90 days</option>
                                        <option>1 year</option>
                                        <option>Forever</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Backup Location</label>
                                    <input type="text" class="form-control" placeholder="/path/to/backups or S3 bucket" disabled>
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn btn-outline-modern btn-sm" disabled>
                                        <i class="fas fa-download me-2"></i>Download latest backup
                                    </button>
                                    <button type="button" class="btn btn-outline-modern btn-sm ms-2" disabled>
                                        <i class="fas fa-play me-2"></i>Trigger manual backup
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- UI & Preferences -->
                <div class="tab-pane fade" id="ui-preferences" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">UI & System Preferences</h5>
                                <small class="card-subtitle">Adjust theme, density, and default views.</small>
                            </div>

                            <form class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Theme</label>
                                    <select class="form-select" disabled>
                                        <option selected>Light</option>
                                        <option>Dark</option>
                                        <option>Auto</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Table Density</label>
                                    <select class="form-select" disabled>
                                        <option>Comfortable</option>
                                        <option selected>Compact</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Default Landing Page</label>
                                    <select class="form-select" disabled>
                                        <option selected>Super Admin Dashboard</option>
                                        <option>Posts & Locations</option>
                                        <option>Users</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Password Policy Confirmation Modal -->
<div class="modal fade" id="passwordPolicyConfirmModal" tabindex="-1" role="dialog" aria-labelledby="passwordPolicyConfirmModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%); color: #ffffff; border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <h5 class="modal-title" id="passwordPolicyConfirmModalLabel" style="margin: 0; font-weight: 600;">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Password Policy Changes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <p class="mb-3" style="font-size: 1rem; color: #475569;">
                    You are about to update the password policy settings. This will affect <strong>all users</strong> in the system.
                </p>
                <div class="alert alert-warning mb-0" style="border-left: 4px solid #f59e0b;">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Important:</strong> Changes to password expiry will immediately affect users whose passwords are due to expire based on the new policy.
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary-modern" id="confirmPasswordPolicyBtn">
                    <i class="fas fa-check me-2"></i>Confirm & Save
                </button>
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
                    Passwords expire after <?php echo $password_expiry_days; ?> days to protect your account from unauthorized access. This is a security best practice to prevent potential breaches.
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
.super-admin-settings {
    padding: 2rem 2.5rem;
    max-width: 100%;
    background: #f8fafc;
    min-height: 100vh;
}

/* Navigation Sidebar - Card Style */
.settings-nav {
    background: transparent;
    border: none;
    padding: 0.5rem;
}

.settings-nav .list-group-item {
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

.settings-nav .list-group-item:hover {
    background: #f8fafc;
    color: #1e293b;
}

.settings-nav .list-group-item i {
    width: 1.25rem;
    text-align: center;
    color: #64748b;
    font-size: 1rem;
}

.settings-nav .list-group-item.active {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%);
    color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(30, 58, 138, 0.2);
}

.settings-nav .list-group-item.active i {
    color: #ffffff;
}

/* Ensure cards match HR admin style */
.super-admin-settings .card-modern {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    background: #ffffff;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.super-admin-settings .card-modern:hover {
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
}

.super-admin-settings .card-body-modern {
    padding: 1.5rem;
}

.super-admin-settings .card-header-modern {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.super-admin-settings .card-title-modern {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

.super-admin-settings .card-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

/* Password Toggle Styles */
.password-input-wrapper {
    position: relative;
}

.password-input-wrapper .form-control {
    padding-right: 50px;
}

.password-toggle {
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

.password-toggle:hover {
    color: #1e3a8a !important;
    background: transparent !important;
    transform: translateY(-50%) scale(1.1);
    box-shadow: none !important;
}

.password-toggle:active {
    transform: translateY(-50%) scale(0.95);
    background: transparent !important;
    box-shadow: none !important;
}

.password-toggle:focus,
.password-toggle:active,
.password-toggle:focus-visible {
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    border: none !important;
}

.password-toggle i {
    font-size: 1.125rem !important;
    color: #475569 !important;
    transition: all 0.2s ease;
    display: block !important;
    line-height: 1 !important;
}

.password-toggle:hover i {
    color: #1e3a8a !important;
}

.password-toggle.btn-link {
    color: #475569 !important;
    text-decoration: none !important;
}

.password-toggle.btn-link:hover {
    color: #1e3a8a !important;
    text-decoration: none !important;
}

/* Make Change Password button highly visible */
.super-admin-settings #changePasswordBtn,
.super-admin-settings .btn-primary-modern {
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

.super-admin-settings #changePasswordBtn:hover,
.super-admin-settings .btn-primary-modern:hover {
    background: linear-gradient(135deg, #1e40af 0%, #1e293b 50%, #1e3a8a 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(30, 58, 138, 0.45) !important;
}

.super-admin-settings #changePasswordBtn:active,
.super-admin-settings .btn-primary-modern:active {
    transform: translateY(0) !important;
    box-shadow: 0 2px 8px rgba(30, 58, 138, 0.3) !important;
}

.super-admin-settings #changePasswordBtn:focus,
.super-admin-settings .btn-primary-modern:focus,
.super-admin-settings #changePasswordBtn:focus-visible,
.super-admin-settings .btn-primary-modern:focus-visible {
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.2), 0 4px 12px rgba(30, 58, 138, 0.35) !important;
}

/* Password Strength Indicator */
.password-strength-container {
    margin-top: 0.75rem;
}

.password-strength-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

.password-strength-fill {
    height: 100%;
    width: 0%;
    background: #ef4444;
    border-radius: 4px;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s ease;
    position: relative;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
    display: block;
}

.password-strength-fill.weak {
    width: 33.33%;
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 0 12px rgba(239, 68, 68, 0.4);
}

.password-strength-fill.normal {
    width: 66.66%;
    background: linear-gradient(90deg, #f59e0b 0%, #f97316 100%);
    box-shadow: 0 0 12px rgba(245, 158, 11, 0.4);
}

.password-strength-fill.strong {
    width: 100%;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
}

.password-strength-text {
    font-size: 0.8125rem;
}

#strengthLabel {
    font-weight: 600;
}

.password-requirements {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.requirement-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.375rem;
    font-size: 0.8125rem;
    color: #64748b;
    transition: all 0.2s ease;
}

.requirement-item:last-child {
    margin-bottom: 0;
}

.requirement-icon {
    font-size: 0.5rem;
    color: #cbd5e1;
    transition: all 0.2s ease;
}

.requirement-item.met .requirement-icon {
    color: #10b981;
}

.requirement-item.met {
    color: #059669;
}

.requirement-item.met .requirement-icon::before {
    content: "\f00c";
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    font-size: 0.75rem;
}

/* Password Match Indicator */
.password-match-indicator {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    transition: all 0.3s ease;
}

.password-match-indicator.match {
    background: #d1fae5;
    border: 1px solid #10b981;
    color: #059669;
}

.password-match-indicator.mismatch {
    background: #fee2e2;
    border: 1px solid #ef4444;
    color: #dc2626;
}

.match-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.match-icon {
    font-size: 0.875rem;
    font-weight: 900;
    font-family: "Font Awesome 5 Free";
}

.password-match-indicator.match .match-icon::before {
    content: "\f00c";
    color: #10b981;
}

.password-match-indicator.mismatch .match-icon::before {
    content: "\f00d";
    color: #ef4444;
}

.match-text {
    font-weight: 500;
}
/* Remove backdrop for password policy confirmation modal */
#passwordPolicyConfirmModal.show ~ .modal-backdrop,
.modal-backdrop.show:has(~ #passwordPolicyConfirmModal.show),
body:has(#passwordPolicyConfirmModal.show) .modal-backdrop {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
}

/* Ensure modal is clickable and properly positioned */
#passwordPolicyConfirmModal {
    pointer-events: auto !important;
}

#passwordPolicyConfirmModal .modal-dialog {
    pointer-events: auto !important;
    z-index: 1055 !important;
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
            
            // Submit via AJAX directly to the settings endpoint for the current portal
            // Always force page=settings to avoid stray query params (_r, etc.) breaking routing
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
        
        // Real-time password confirmation validation
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        if (confirmPasswordInput && newPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value && this.value !== newPasswordInput.value) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            newPasswordInput.addEventListener('input', function() {
                if (confirmPasswordInput.value && this.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            });
        }
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

    // Password Policy Confirmation Modal
    const passwordPolicyForm = document.getElementById('passwordPolicyForm');
    const updatePasswordPolicyBtn = document.getElementById('updatePasswordPolicyBtn');
    const passwordPolicyConfirmModal = document.getElementById('passwordPolicyConfirmModal');
    const confirmPasswordPolicyBtn = document.getElementById('confirmPasswordPolicyBtn');

    // Guard flag so we don't intercept the programmatic submit after confirmation
    let passwordPolicyConfirmed = false;

    if (passwordPolicyForm && updatePasswordPolicyBtn && passwordPolicyConfirmModal && confirmPasswordPolicyBtn) {
        // Intercept form submission once, to show confirmation modal
        passwordPolicyForm.addEventListener('submit', function(e) {
            if (!passwordPolicyConfirmed) {
                // First submit → just show confirmation modal and stop actual submit
                e.preventDefault();

                if (typeof bootstrap !== 'undefined') {
                    const modalInstance = new bootstrap.Modal(passwordPolicyConfirmModal, {
                        backdrop: false,
                        keyboard: true
                    });
                    modalInstance.show();
                    
                    // Remove any backdrop elements that might have been created
                    setTimeout(function() {
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        backdrops.forEach(function(backdrop) {
                            if (backdrop.parentNode) {
                                backdrop.remove();
                            }
                        });
                        // Also remove backdrop class from body
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 10);
                } else {
                    // Fallback if Bootstrap is not available
                    passwordPolicyConfirmModal.style.display = 'block';
                    passwordPolicyConfirmModal.classList.add('show');
                }
            } else {
                // Allow the confirmed submit to go through, then reset the flag
                passwordPolicyConfirmed = false;
            }
        });

        // Handle confirmation button click
        confirmPasswordPolicyBtn.addEventListener('click', function() {
            // Hide modal
            if (typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(passwordPolicyConfirmModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            } else {
                passwordPolicyConfirmModal.style.display = 'none';
                passwordPolicyConfirmModal.classList.remove('show');
            }

            // Mark as confirmed and submit the form (will bypass the intercept branch)
            passwordPolicyConfirmed = true;
            passwordPolicyForm.submit();
        });
    }
});
</script>

