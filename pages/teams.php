<?php
// Super Admin Teams Management – UI scaffold (no persistence yet)
$page_title = 'Teams - Super Admin - Golden Z-5 HR System';
$page = 'teams';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../landing/index.php');
    exit;
}
?>


    <div class="page-header-modern mb-3">
        <div class="page-title-modern">
            <h1 class="page-title-main">Teams Overview</h1>
            <p class="page-subtitle">Create, edit, assign users, and control module access.</p>
        </div>
        <div class="page-actions-modern">
            <button class="btn btn-primary-modern btn-sm" disabled>
                <i class="fas fa-plus me-2"></i>Create Team
            </button>
        </div>
    </div>

    <!-- Team Statistics -->
    <div class="d-flex justify-content-center">
        <div class="team-stats-container">
            <div class="row g-3 mb-3">
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card-modern h-100">
                        <div class="card-body-modern">
                            <div class="stat-header">
                                <span class="stat-label">Total Teams</span>
                                <i class="fas fa-users stat-icon"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">2</h3>
                                <span class="badge badge-primary-modern">2 Active</span>
                            </div>
                            <small class="stat-footer">All teams in system</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card-modern h-100">
                        <div class="card-body-modern">
                            <div class="stat-header">
                                <span class="stat-label">Total Members</span>
                                <i class="fas fa-user-friends stat-icon"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">20</h3>
                                <span class="badge badge-success-modern">18 Active</span>
                            </div>
                            <small class="stat-footer">Across all teams</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card-modern h-100">
                        <div class="card-body-modern">
                            <div class="stat-header">
                                <span class="stat-label">Team Leads</span>
                                <i class="fas fa-user-tie stat-icon"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">2</h3>
                                <span class="badge badge-primary-modern">2 Assigned</span>
                            </div>
                            <small class="stat-footer">Active team leaders</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card-modern h-100">
                        <div class="card-body-modern">
                            <div class="stat-header">
                                <span class="stat-label">Avg Team Size</span>
                                <i class="fas fa-chart-line stat-icon"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">10</h3>
                                <span class="badge badge-warning-modern">±2 members</span>
                            </div>
                            <small class="stat-footer">Average per team</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Team Editor -->
        <div class="col-xl-5">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-3">
                        <h5 class="card-title-modern">Team Details</h5>
                        <small class="card-subtitle">UI-only scaffold; wire to DB + audit.</small>
                    </div>
                    <form class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Team Name</label>
                            <input type="text" class="form-control" placeholder="e.g., Operations North" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows=3 placeholder="Team scope and responsibilities" disabled></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Team Lead</label>
                            <select class="form-select" disabled>
                                <option>Select user</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" disabled>
                                <option selected>Active</option>
                                <option>Inactive</option>
                                <option>Archived</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="button" class="btn btn-primary-modern" disabled>
                                <i class="fas fa-save me-2"></i>Save
                            </button>
                            <button type="button" class="btn btn-outline-modern" disabled>
                                <i class="fas fa-sync me-2"></i>Reset
                            </button>
                            <button type="button" class="btn btn-outline-danger" disabled>
                                <i class="fas fa-trash me-2"></i>Delete
                            </button>
                        </div>
                        <small class="text-muted">On save/delete, add audit logging and RBAC checks.</small>
                    </form>
                </div>
            </div>
        </div>

        <!-- Teams List + Activity -->
        <div class="col-xl-7">
            <div class="card card-modern mb-4">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title-modern mb-0">Teams Overview</h5>
                            <small class="card-subtitle">Select a team to edit or review.</small>
                        </div>
                        <div class="input-group" style="max-width: 320px;">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search teams..." disabled>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Team</th>
                                    <th>Lead</th>
                                    <th>Members</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Operations North</td>
                                    <td>Maria L. Santos</td>
                                    <td>12</td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>
                                        <button class="btn btn-outline-modern btn-sm" disabled>Edit</button>
                                        <button class="btn btn-outline-danger btn-sm" disabled>Deactivate</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Admin & Compliance</td>
                                    <td>HR Admin</td>
                                    <td>8</td>
                                    <td><span class="badge bg-secondary">Inactive</span></td>
                                    <td>
                                        <button class="btn btn-outline-modern btn-sm" disabled>Edit</button>
                                        <button class="btn btn-outline-success btn-sm" disabled>Activate</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Wire this list to a teams table; include audit trail for edits/deletes.</small>
                </div>
            </div>

            <div class="card card-modern">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-3">
                        <h5 class="card-title-modern">Recent Team Activity</h5>
                        <small class="card-subtitle">Placeholder for audit entries.</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Team</th>
                                    <th>Action</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Jan 13, 2026 14:10</td>
                                    <td>Operations North</td>
                                    <td>Updated membership</td>
                                    <td>super_admin</td>
                                </tr>
                                <tr>
                                    <td>Jan 12, 2026 09:45</td>
                                    <td>Admin & Compliance</td>
                                    <td>Status changed to Inactive</td>
                                    <td>super_admin</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">When wired, pull from audit logs filtered by team actions.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <!-- Team Roles & Permissions -->
        <div class="col-xl-6">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-3">
                        <h5 class="card-title-modern">Team Roles & Permissions</h5>
                        <small class="card-subtitle">Matrix (UI only). Map to RBAC when ready.</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Role</th>
                                    <th>Create</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                    <th>Assign Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $team_roles = ['Team Owner', 'Team Manager', 'Member', 'Viewer'];
                                foreach ($team_roles as $role):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($role); ?></strong></td>
                                    <?php for ($i=0; $i<4; $i++): ?>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" <?php echo in_array($role, ['Team Owner','Team Manager']) ? 'checked' : ''; ?> disabled>
                                        </div>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Hook to a team_roles table and enforce with middleware.</small>
                </div>
            </div>
        </div>

        <!-- Module Access per Team -->
        <div class="col-xl-6">
            <div class="card card-modern h-100">
                <div class="card-body-modern">
                    <div class="card-header-modern mb-3">
                        <h5 class="card-title-modern">Module Access (by Team)</h5>
                        <small class="card-subtitle">Control which modules teams can use.</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Team</th>
                                    <th>Employees</th>
                                    <th>Posts</th>
                                    <th>Time</th>
                                    <th>Alerts</th>
                                    <th>Integrations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $teams = ['Operations North', 'Admin & Compliance', 'Logistics'];
                                foreach ($teams as $t):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($t); ?></strong></td>
                                    <?php for ($i=0; $i<5; $i++): ?>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" checked disabled>
                                        </div>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Persist to a team_module_access table; audit changes.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.super-admin-teams {
    padding: 1rem 1.5rem;
    max-width: 100%;
    background: #f8fafc;
    min-height: 100vh;
    border-radius: 14px;
}

/* Card styling to match HR admin dashboard */
.super-admin-teams .card-modern {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    background: #ffffff;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.super-admin-teams .card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
}

.super-admin-teams .card-body-modern {
    padding: 1rem;
}

.super-admin-teams .card-header-modern {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.super-admin-teams .card-title-modern {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

.super-admin-teams .card-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

/* Compact form spacing */
.super-admin-teams .row.g-3 > * {
    padding-right: calc(var(--bs-gutter-x) * 0.5);
    padding-left: calc(var(--bs-gutter-x) * 0.5);
    margin-top: calc(var(--bs-gutter-y) * 0.5);
}

.super-admin-teams form .row.g-3 {
    margin-bottom: 0.75rem;
}

.super-admin-teams .form-label {
    margin-bottom: 0.375rem;
    font-size: 0.875rem;
}

.super-admin-teams textarea.form-control {
    min-height: 80px;
}

.super-admin-teams .page-header-modern {
    padding: 1rem 1.25rem;
    margin-bottom: 1rem;
}

.super-admin-teams .page-title-main {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.super-admin-teams .page-subtitle {
    font-size: 0.875rem;
    margin-bottom: 0;
}

.super-admin-teams .mb-3 {
    margin-bottom: 0.75rem !important;
}

.super-admin-teams .mb-4 {
    margin-bottom: 1rem !important;
}

.super-admin-teams .card-modern.mb-4 {
    margin-bottom: 1rem !important;
}

/* Dark theme support for Super Admin Teams */
html[data-theme="dark"] .super-admin-teams {
    background: var(--interface-bg) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .page-header-modern {
    background-color: #1a1d23 !important;
    border: 1px solid var(--interface-border) !important;
    border-radius: 14px; /* Rounded rectangle */
    padding: 1.5rem 2rem; /* Adjusted padding */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04); /* Added shadow */
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .page-title-main {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .page-subtitle {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .super-admin-teams .card-modern {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .card-body-modern {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .card-header-modern {
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .super-admin-teams .card-title-modern {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .card-subtitle {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .super-admin-teams .form-label {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .form-control,
html[data-theme="dark"] .super-admin-teams .form-select {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .input-group-text {
    background-color: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .table,
html[data-theme="dark"] .super-admin-teams .table.table-sm,
html[data-theme="dark"] .super-admin-teams .table.align-middle {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .table thead {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .table thead.table-light {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .table thead th {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .super-admin-teams .table tbody {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .table tbody tr {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .table tbody tr:hover {
    background-color: var(--interface-hover) !important;
}

html[data-theme="dark"] .super-admin-teams .table td,
html[data-theme="dark"] .super-admin-teams .table th {
    background-color: transparent !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .table td strong {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .super-admin-teams .text-muted {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .super-admin-teams .badge {
    color: #fff !important;
}
</style>
