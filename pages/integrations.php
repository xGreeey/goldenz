<?php
// Super Admin Integrations – UI scaffold (no persistence yet)
$page_title = 'Integrations - Super Admin - Golden Z-5 HR System';
$page = 'integrations';

// Enforce Super Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../landing/index.php');
    exit;
}
?>

<div class="container-fluid super-admin-integrations">
    <div class="page-header-modern mb-4">
        <div class="page-title-modern">
            <h1 class="page-title-main">Integrations</h1>
            <p class="page-subtitle">Configure, enable/disable, and monitor third-party and internal integrations.</p>
        </div>
        <div class="page-actions-modern">
            <button class="btn btn-outline-modern btn-sm" disabled>
                <i class="fas fa-plus me-2"></i>Add Integration (wire later)
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Left: Navigation -->
        <div class="col-md-3 mb-4">
            <div class="list-group settings-nav" id="integrationTabs" role="tablist">
                <button class="list-group-item list-group-item-action active"
                        id="providers-tab"
                        data-bs-toggle="list"
                        data-bs-target="#providers"
                        type="button" role="tab">
                    <i class="fas fa-plug me-2"></i>Providers & Status
                </button>
                <button class="list-group-item list-group-item-action"
                        id="api-keys-tab"
                        data-bs-toggle="list"
                        data-bs-target="#api-keys"
                        type="button" role="tab">
                    <i class="fas fa-key me-2"></i>API Keys
                </button>
                <button class="list-group-item list-group-item-action"
                        id="webhooks-tab"
                        data-bs-toggle="list"
                        data-bs-target="#webhooks"
                        type="button" role="tab">
                    <i class="fas fa-link me-2"></i>Webhooks
                </button>
                <button class="list-group-item list-group-item-action"
                        id="monitoring-tab"
                        data-bs-toggle="list"
                        data-bs-target="#monitoring"
                        type="button" role="tab">
                    <i class="fas fa-heartbeat me-2"></i>Monitoring & Errors
                </button>
                <button class="list-group-item list-group-item-action"
                        id="permissions-tab"
                        data-bs-toggle="list"
                        data-bs-target="#permissions"
                        type="button" role="tab">
                    <i class="fas fa-user-shield me-2"></i>Role Access
                </button>
                <button class="list-group-item list-group-item-action"
                        id="security-tab"
                        data-bs-toggle="list"
                        data-bs-target="#security"
                        type="button" role="tab">
                    <i class="fas fa-lock me-2"></i>Security & Audit
                </button>
            </div>
        </div>

        <!-- Right: Content -->
        <div class="col-md-9">
            <div class="tab-content" id="integrationTabContent">
                <!-- Providers & Status -->
                <div class="tab-pane fade show active" id="providers" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title-modern">Providers & Status</h5>
                                    <small class="card-subtitle">Enable/disable and view health at a glance.</small>
                                </div>
                                <span class="badge badge-primary-modern">UI scaffold</span>
                            </div>
                            <div class="row g-3">
                                <?php
                                $providers = [
                                    ['name' => 'Email (SMTP)', 'status' => 'Enabled', 'health' => 'OK'],
                                    ['name' => 'SMS Gateway', 'status' => 'Disabled', 'health' => 'N/A'],
                                    ['name' => 'Payment Gateway', 'status' => 'Enabled', 'health' => 'OK'],
                                    ['name' => 'Analytics', 'status' => 'Enabled', 'health' => 'Warning'],
                                    ['name' => 'SSO / OAuth Provider', 'status' => 'Enabled', 'health' => 'OK'],
                                    ['name' => 'Internal API Bridge', 'status' => 'Enabled', 'health' => 'OK'],
                                ];
                                foreach ($providers as $prov):
                                ?>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center border rounded p-3 h-100">
                                        <div>
                                            <strong><?php echo htmlspecialchars($prov['name']); ?></strong><br>
                                            <small class="text-muted">Status: <?php echo htmlspecialchars($prov['status']); ?> | Health: <?php echo htmlspecialchars($prov['health']); ?></small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" <?php echo $prov['status'] === 'Enabled' ? 'checked' : ''; ?> disabled>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="text-muted small mt-3 mb-0">Wire these toggles to your integration config store and health checks.</p>
                        </div>
                    </div>
                </div>

                <!-- API Keys -->
                <div class="tab-pane fade" id="api-keys" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">API Key Management</h5>
                                <small class="card-subtitle">Generate, revoke, and scope keys. (UI only now.)</small>
                            </div>
                            <form class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">New Key Label</label>
                                    <input type="text" class="form-control" placeholder="e.g., Reporting Service" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Role Scope</label>
                                    <select class="form-select" disabled>
                                        <option>Read-only</option>
                                        <option>Read/Write</option>
                                        <option>Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-12 d-flex gap-2">
                                    <button type="button" class="btn btn-primary-modern" disabled>
                                        <i class="fas fa-key me-2"></i>Generate Key (hook later)
                                    </button>
                                    <button type="button" class="btn btn-outline-modern" disabled>
                                        <i class="fas fa-ban me-2"></i>Revoke Selected
                                    </button>
                                </div>
                            </form>

                            <div class="table-responsive mt-4">
                                <table class="table table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Label</th>
                                            <th>Scope</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Analytics Pipeline</td>
                                            <td>Read/Write</td>
                                            <td><span class="badge bg-success">Active</span></td>
                                            <td>Jan 10, 2026</td>
                                            <td><button class="btn btn-outline-danger btn-sm" disabled>Revoke</button></td>
                                        </tr>
                                        <tr>
                                            <td>Legacy Reporting</td>
                                            <td>Read-only</td>
                                            <td><span class="badge bg-secondary">Disabled</span></td>
                                            <td>Dec 02, 2025</td>
                                            <td><button class="btn btn-outline-success btn-sm" disabled>Enable</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted small mb-0 mt-2">Add persistence and encryption for real key storage.</p>
                        </div>
                    </div>
                </div>

                <!-- Webhooks -->
                <div class="tab-pane fade" id="webhooks" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Webhooks</h5>
                                <small class="card-subtitle">Configure endpoints and secrets; monitor deliveries.</small>
                            </div>
                            <form class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Endpoint URL</label>
                                    <input type="url" class="form-control" placeholder="https://example.com/webhook" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Secret</label>
                                    <input type="text" class="form-control" placeholder="auto-generated" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Events</label>
                                    <select class="form-select" disabled>
                                        <option>All events</option>
                                        <option>Alerts</option>
                                        <option>Users</option>
                                        <option>Posts</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Delivery Retry</label>
                                    <select class="form-select" disabled>
                                        <option>3 attempts</option>
                                        <option>5 attempts</option>
                                        <option>Unlimited</option>
                                    </select>
                                </div>
                                <div class="col-md-12 d-flex gap-2">
                                    <button type="button" class="btn btn-primary-modern" disabled>
                                        <i class="fas fa-save me-2"></i>Save Webhook
                                    </button>
                                    <button type="button" class="btn btn-outline-modern" disabled>
                                        <i class="fas fa-paper-plane me-2"></i>Send Test
                                    </button>
                                </div>
                            </form>
                            <p class="text-muted small mt-3">Implement signing, replay protection, and delivery logs when wiring this up.</p>
                        </div>
                    </div>
                </div>

                <!-- Monitoring & Errors -->
                <div class="tab-pane fade" id="monitoring" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Integration Monitoring</h5>
                                <small class="card-subtitle">Status, latency, recent errors. (Placeholder data.)</small>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Uptime (Email)</strong>
                                            <span class="badge bg-success">99.9%</span>
                                        </div>
                                        <small class="text-muted">Last 24h</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Latency (SMS)</strong>
                                            <span class="badge bg-warning text-dark">350ms</span>
                                        </div>
                                        <small class="text-muted">Last 1h</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Errors (Payments)</strong>
                                            <span class="badge bg-danger">5</span>
                                        </div>
                                        <small class="text-muted">Last 24h</small>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Time</th>
                                            <th>Integration</th>
                                            <th>Error</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Jan 13, 2026 14:05</td>
                                            <td>Payment Gateway</td>
                                            <td>Timeout contacting provider</td>
                                            <td><button class="btn btn-outline-modern btn-sm" disabled>View</button></td>
                                        </tr>
                                        <tr>
                                            <td>Jan 13, 2026 10:22</td>
                                            <td>SMS Gateway</td>
                                            <td>Invalid credentials</td>
                                            <td><button class="btn btn-outline-modern btn-sm" disabled>Resolve</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted small mb-0 mt-2">Hook to real health checks and logs to make this live.</p>
                        </div>
                    </div>
                </div>

                <!-- Role Access -->
                <div class="tab-pane fade" id="permissions" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Role-Based Integration Access</h5>
                                <small class="card-subtitle">Control which roles can manage which integrations.</small>
                            </div>
                            <p class="text-muted small">This is a visual matrix. Connect it to your RBAC config to enforce.</p>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Role</th>
                                            <th>Email</th>
                                            <th>SMS</th>
                                            <th>Payments</th>
                                            <th>Analytics</th>
                                            <th>API</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $roles = ['super_admin', 'hr_admin', 'admin', 'hr', 'accounting', 'operation', 'developer'];
                                        foreach ($roles as $role):
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars(ucfirst(str_replace('_',' ', $role))); ?></strong></td>
                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" <?php echo $role === 'super_admin' ? 'checked' : ''; ?> disabled>
                                                    </div>
                                                </td>
                                            <?php endfor; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security & Audit -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="card card-modern mb-4">
                        <div class="card-body-modern">
                            <div class="card-header-modern mb-3">
                                <h5 class="card-title-modern">Security & Audit</h5>
                                <small class="card-subtitle">Plan audits, logging, and secrets handling for integrations.</small>
                            </div>
                            <form class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Secrets Storage</label>
                                    <select class="form-select" disabled>
                                        <option selected>Env / Vault</option>
                                        <option>Database (encrypted)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Audit Scope</label>
                                    <select class="form-select" disabled>
                                        <option selected>All integration changes</option>
                                        <option>Only key changes</option>
                                        <option>Errors only</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Alert on Failures</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked disabled>
                                        <label class="form-check-label">Notify super admin on failures</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Rotation Policy</label>
                                    <select class="form-select" disabled>
                                        <option>30 days</option>
                                        <option selected>90 days</option>
                                        <option>180 days</option>
                                    </select>
                                </div>
                            </form>
                            <p class="text-muted small mb-0 mt-2">When wired, log all integration changes to audit trail.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.super-admin-integrations {
    padding: 2rem 2.5rem;
    max-width: 100%;
    background: #f8fafc;
    min-height: 100vh;
}
.settings-nav .list-group-item {
    border: none;
    border-radius: 0;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.settings-nav .list-group-item i {
    width: 1.25rem;
    text-align: center;
    color: #64748b;
}
.settings-nav .list-group-item.active {
    background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%);
    color: #fff;
    border-radius: 0.5rem;
}
.settings-nav .list-group-item.active i {
    color: #e0f2fe;
}
</style>
