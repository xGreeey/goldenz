<?php
// Super Admin Settings – UI scaffold only (no persistence yet)
$page_title = 'Settings - Super Admin - Golden Z-5 HR System';
$page = 'settings';

// Enforce Super Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../landing/index.php');
    exit;
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
                                        <small class="text-muted">Password must be at least 8 characters long</small>
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

                    <!-- Password Policy Section -->
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Password Policy</h5>
                                <small class="card-subtitle">System-wide password requirements and settings.</small>
                            </div>

                            <form>
                                <h6 class="mb-3">Password Policy</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label">Minimum Length</label>
                                        <input type="number" class="form-control" min="8" value="8" disabled>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Require Special Characters</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" checked disabled>
                                            <label class="form-check-label">Enabled</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Password Expiry (days)</label>
                                        <input type="number" class="form-control" min="0" value="90" disabled>
                                        <small class="text-muted">0 = never expires</small>
                                    </div>
                                </div>

                                <h6 class="mb-3">Two-Factor Authentication (2FA)</h6>
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
    padding-right: 45px;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    padding: 0.375rem 0.5rem;
    color: #64748b;
    cursor: pointer;
    z-index: 10;
    text-decoration: none;
    transition: color 0.2s ease;
}

.password-toggle:hover {
    color: #1fb2d5;
    background: transparent;
}

.password-toggle:focus {
    outline: none;
    box-shadow: none;
}

.password-toggle i {
    font-size: 1rem;
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
            
            // Validate password length
            if (newPassword.length < 8) {
                if (alertDiv) {
                    alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Password must be at least 8 characters long</div>';
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
                    
                    // Reset form
                    changePasswordForm.reset();
                    
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
});
</script>

