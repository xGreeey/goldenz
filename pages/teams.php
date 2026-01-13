<?php
// Super Admin Teams Management – UI scaffold (no persistence yet)
$page_title = 'Teams - Super Admin - Golden Z-5 HR System';
$page = 'teams';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../landing/index.php');
    exit;
}
?>

<div class="container-fluid super-admin-teams">
    <div class="page-header-modern mb-4">
        <div class="page-title-modern">
            <h1 class="page-title-main">Teams</h1>
            <p class="page-subtitle">Create, edit, assign users, and control module access.</p>
        </div>
        <div class="page-actions-modern">
            <button class="btn btn-primary-modern btn-sm" disabled>
                <i class="fas fa-plus me-2"></i>Create Team
            </button>
        </div>
    </div>

    <div class="row g-4">
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

    <div class="row g-4 mt-1">
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
    padding: 2rem 2.5rem;
    max-width: 100%;
    background: #f8fafc;
    min-height: 100vh;
}
</style>
