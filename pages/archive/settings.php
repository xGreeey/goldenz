<?php
$page_title = 'Settings - Golden Z-5 HR System';
$page = 'settings';

// Sample settings data
$system_info = [
    'version' => '1.2.3',
    'last_updated' => '2025-01-15',
    'database_status' => 'Connected',
    'server_status' => 'Online',
    'backup_status' => 'Last backup: 2025-01-14 23:00',
    'security_level' => 'High'
];
?>

<div class="settings-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>Settings</h1>
        </div>
        <div class="page-actions">
            <button class="btn btn-outline-secondary">
                <i class="fas fa-envelope"></i>
            </button>
            <button class="btn btn-outline-secondary">
                <i class="fas fa-bell"></i>
            </button>
            <button class="btn btn-outline-primary" id="exportBtn">
                <i class="fas fa-download me-2"></i>Export Config
            </button>
            <button class="btn btn-primary" id="saveBtn">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </div>

    <!-- Settings Tabs -->
    <div class="settings-tabs">
        <button class="settings-tab active" data-tab="general">General</button>
        <button class="settings-tab" data-tab="security">Security</button>
        <button class="settings-tab" data-tab="notifications">Notifications</button>
        <button class="settings-tab" data-tab="system">System</button>
    </div>

    <!-- General Settings -->
    <div class="settings-content active" id="general-tab">
        <div class="settings-section">
            <h3>Company Information</h3>
            <div class="settings-grid">
                <div class="form-group">
                    <label for="companyName">Company Name</label>
                    <input type="text" class="form-control" id="companyName" value="Golden Z-5 Security Agency">
                </div>
                <div class="form-group">
                    <label for="companyAddress">Address</label>
                    <textarea class="form-control" id="companyAddress" rows="3">123 Security Street, Downtown, City 12345</textarea>
                </div>
                <div class="form-group">
                    <label for="companyPhone">Phone</label>
                    <input type="tel" class="form-control" id="companyPhone" value="+1 (555) 123-4567">
                </div>
                <div class="form-group">
                    <label for="companyEmail">Email</label>
                    <input type="email" class="form-control" id="companyEmail" value="info@goldenz5.com">
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>System Preferences</h3>
            <div class="settings-grid">
                <div class="form-group">
                    <label for="timezone">Timezone</label>
                    <select class="form-select" id="timezone">
                        <option value="UTC-8">Pacific Time (UTC-8)</option>
                        <option value="UTC-5" selected>Eastern Time (UTC-5)</option>
                        <option value="UTC+0">UTC</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dateFormat">Date Format</label>
                    <select class="form-select" id="dateFormat">
                        <option value="MM/DD/YYYY" selected>MM/DD/YYYY</option>
                        <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                        <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="language">Language</label>
                    <select class="form-select" id="language">
                        <option value="en" selected>English</option>
                        <option value="es">Spanish</option>
                        <option value="fr">French</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="settings-content" id="security-tab">
        <div class="settings-section">
            <h3>Password Policy</h3>
            <div class="settings-grid">
                <div class="form-group">
                    <label for="minPasswordLength">Minimum Password Length</label>
                    <input type="number" class="form-control" id="minPasswordLength" value="8" min="6" max="20">
                </div>
                <div class="form-group">
                    <label for="passwordExpiry">Password Expiry (days)</label>
                    <input type="number" class="form-control" id="passwordExpiry" value="90" min="30" max="365">
                </div>
                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="requireSpecialChars" checked>
                        Require special characters
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="requireNumbers" checked>
                        Require numbers
                    </label>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>Session Management</h3>
            <div class="settings-grid">
                <div class="form-group">
                    <label for="sessionTimeout">Session Timeout (minutes)</label>
                    <input type="number" class="form-control" id="sessionTimeout" value="30" min="5" max="480">
                </div>
                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="autoLogout" checked>
                        Auto-logout on inactivity
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Settings -->
    <div class="settings-content" id="notifications-tab">
        <div class="settings-section">
            <h3>Email Notifications</h3>
            <div class="settings-grid">
                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="newEmployeeEmail" checked>
                        New employee registration
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="timeOffEmail" checked>
                        Time off requests
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="alertEmail" checked>
                        System alerts
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="reminderEmail" checked>
                        Reminders and deadlines
                    </label>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>System Alerts</h3>
            <div class="settings-grid">
                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="maintenanceAlert" checked>
                        Maintenance notifications
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="securityAlert" checked>
                        Security alerts
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- System Settings -->
    <div class="settings-content" id="system-tab">
        <div class="settings-section">
            <h3>System Information</h3>
            <div class="system-info-grid">
                <div class="info-card">
                    <div class="info-label">Version</div>
                    <div class="info-value"><?php echo $system_info['version']; ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Last Updated</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($system_info['last_updated'])); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Database Status</div>
                    <div class="info-value status-connected"><?php echo $system_info['database_status']; ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Server Status</div>
                    <div class="info-value status-online"><?php echo $system_info['server_status']; ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Last Backup</div>
                    <div class="info-value"><?php echo $system_info['backup_status']; ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Security Level</div>
                    <div class="info-value status-high"><?php echo $system_info['security_level']; ?></div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>Maintenance</h3>
            <div class="maintenance-actions">
                <button class="btn btn-outline-primary" onclick="backupDatabase()">
                    <i class="fas fa-database me-2"></i>Backup Database
                </button>
                <button class="btn btn-outline-warning" onclick="clearCache()">
                    <i class="fas fa-broom me-2"></i>Clear Cache
                </button>
                <button class="btn btn-outline-info" onclick="checkUpdates()">
                    <i class="fas fa-sync me-2"></i>Check Updates
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.settings-container {
    padding: var(--spacing-xl);
    background-color: var(--interface-bg);
    min-height: 100vh;
    max-width: 1400px;
    margin: 0 auto;
}

.settings-tabs {
    display: flex;
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
    border-bottom: 1px solid var(--interface-border);
}

.settings-tab {
    background: none;
    border: none;
    padding: var(--spacing-md) 0;
    font-weight: 500;
    color: var(--muted-color);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.settings-tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.settings-content {
    display: none;
}

.settings-content.active {
    display: block;
}

.settings-section {
    background: white;
    border: 1px solid var(--interface-border);
    border-radius: 0;
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-lg);
}

.settings-section h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--interface-text);
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-sm);
    border-bottom: 1px solid var(--interface-border-light);
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-lg);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 500;
    color: var(--interface-text);
    margin-bottom: var(--spacing-sm);
}

.form-group .form-check-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    font-weight: 400;
}

.system-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
}

.info-card {
    background: var(--interface-border-light);
    padding: var(--spacing-lg);
    border-radius: 0;
    text-align: center;
}

.info-label {
    font-size: 0.875rem;
    color: var(--muted-color);
    margin-bottom: var(--spacing-sm);
    font-weight: 500;
}

.info-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--interface-text);
}

.status-connected,
.status-online {
    color: var(--success-color);
}

.status-high {
    color: var(--warning-color);
}

.maintenance-actions {
    display: flex;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .system-info-grid {
        grid-template-columns: 1fr;
    }
    
    .maintenance-actions {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabs = document.querySelectorAll('.settings-tab');
    const contents = document.querySelectorAll('.settings-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
    
    // Save functionality
    document.getElementById('saveBtn').addEventListener('click', function() {
        alert('Settings saved successfully!');
    });
    
    // Export functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        alert('Export configuration functionality will be implemented soon.');
    });
});

function backupDatabase() {
    if (confirm('Are you sure you want to backup the database?')) {
        alert('Database backup started...');
    }
}

function clearCache() {
    if (confirm('Are you sure you want to clear the cache?')) {
        alert('Cache cleared successfully!');
    }
}

function checkUpdates() {
    alert('Checking for updates...');
}
</script>