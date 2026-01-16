<?php
$page_title = 'User Management - Super Admin - Golden Z-5 HR System';
$page = 'users';

// Ensure only Super Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../landing/index.php');
    exit;
}

// NOTE: POST/AJAX requests are handled by super-admin/index.php before this file is included
// This file should only handle GET requests for displaying the page

// Handle GET request for user details
if (isset($_GET['action']) && $_GET['action'] === 'get_details' && isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        echo '<div class="alert alert-danger">User not found</div>';
        exit;
    }
    
    $role_config = config('roles.roles', []);
    $user_role_config = $role_config[$user['role']] ?? [];
    ?>
    <div class="user-details">
        <div class="row mb-4">
            <div class="col-md-3 text-center">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                         alt="<?php echo htmlspecialchars($user['name']); ?>" 
                         class="rounded-circle mb-3" 
                         style="width: 100px; height: 100px; object-fit: cover;">
                <?php else: ?>
                    <div class="avatar-placeholder rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width: 100px; height: 100px; background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%); color: white; font-weight: 600; font-size: 2.5rem;">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <h5 class="mb-0"><?php echo htmlspecialchars($user['name']); ?></h5>
                <small class="text-muted"><?php echo htmlspecialchars($user['username']); ?></small>
            </div>
            <div class="col-md-9">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Email</label>
                        <div class="fw-semibold"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Phone</label>
                        <div class="fw-semibold"><?php echo htmlspecialchars($user['phone'] ?? '—'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Role</label>
                        <div>
                            <span class="badge badge-primary-modern">
                                <?php echo htmlspecialchars($user_role_config['name'] ?? ucfirst($user['role'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Status</label>
                        <div>
                            <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'suspended' ? 'danger' : 'secondary'); ?>-modern">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Department</label>
                        <div class="fw-semibold"><?php echo htmlspecialchars($user['department'] ?? '—'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Last Login</label>
                        <div class="fw-semibold">
                            <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                        </div>
                    </div>
                    <?php if ($user['employee_id']): ?>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Linked Employee</label>
                            <div class="fw-semibold">
                                #<?php echo htmlspecialchars($user['employee_no'] ?? $user['employee_id']); ?>
                                - <?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['surname'] ?? ''))); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Account Created</label>
                        <div class="fw-semibold"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                    <?php if ($user['created_by_name']): ?>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Created By</label>
                            <div class="fw-semibold"><?php echo htmlspecialchars($user['created_by_name']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($user_role_config['description'])): ?>
            <div class="mt-4">
                <h6>Role Description</h6>
                <p class="text-muted"><?php echo htmlspecialchars($user_role_config['description']); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    exit;
}

// Get filters from request
$filters = [
    'role' => trim($_GET['role'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
];

$page_num = max(1, (int)($_GET['p'] ?? 1));
$per_page = 10;
$offset = ($page_num - 1) * $per_page;

// Get users
$result = get_all_users($filters, $per_page, $offset);
$users = $result['users'];
$total_users = $result['total'];
$total_pages = ceil($total_users / $per_page);

// Get role configuration
$role_config = config('roles.roles', []);
?>

<div class="container-fluid dashboard-modern super-admin-dashboard">
    <!-- Page Header -->
    <div class="page-header-modern mb-5">
        <div class="page-title-modern">
            <h1 class="page-title-main">User Management</h1>
            <p class="page-subtitle">Manage system users, roles, and access permissions</p>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern">
            <form method="GET" action="" id="filterForm" class="row g-3">
                <input type="hidden" name="page" value="users" id="pageNameInput">
                <div class="col-md-4">
                    <label class="form-label">Search Users</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" 
                               name="search" 
                               id="userSearchInput"
                               class="form-control" 
                               placeholder="Search by name, username, or email..."
                               value="<?php echo htmlspecialchars($filters['search']); ?>"
                               autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter by Role</label>
                    <select name="role" class="form-select auto-filter">
                        <option value="">All Roles</option>
                        <?php foreach ($role_config as $role_key => $role_data): ?>
                            <option value="<?php echo $role_key; ?>" <?php echo $filters['role'] === $role_key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role_data['name'] ?? ucfirst($role_key)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter by Status</label>
                    <select name="status" class="form-select auto-filter">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $filters['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-modern w-100" onclick="clearFilters()">
                        <i class="fas fa-times me-2"></i>Clear
                    </button>
                </div>
            </form>
            <?php if (!empty($filters['search']) || !empty($filters['role']) || !empty($filters['status'])): ?>
                <div class="mt-3">
                    <a href="?page=users" class="btn btn-outline-modern btn-sm">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card card-modern">
        <div class="card-body-modern">
            <div class="card-header-modern mb-4">
                <div>
                    <h5 class="card-title-modern">System Users</h5>
                    <small class="card-subtitle">Total: <?php echo number_format($total_users); ?> user<?php echo $total_users !== 1 ? 's' : ''; ?></small>
                </div>
                <button type="button" class="btn btn-primary-modern" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-user-plus me-2"></i>Create User
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Employee Link</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No users found</h5>
                                        <p class="text-muted mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr data-user-id="<?php echo $user['id']; ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?php if (!empty($user['avatar'])): ?>
                                                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                                                         alt="<?php echo htmlspecialchars($user['name']); ?>" 
                                                         class="rounded-circle" 
                                                         style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px; background: linear-gradient(135deg, #1fb2d5 0%, #0ea5e9 100%); color: white; font-weight: 600;">
                                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['username']); ?></small>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm user-role-select" 
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                style="min-width: 150px;">
                                            <?php foreach ($role_config as $role_key => $role_data): ?>
                                                <option value="<?php echo $role_key; ?>" 
                                                        <?php echo $user['role'] === $role_key ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($role_data['name'] ?? ucfirst($role_key)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm user-status-select" 
                                                data-user-id="<?php echo $user['id']; ?>"
                                                style="min-width: 120px;">
                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                        </select>
                                    </td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <div>
                                                <div><?php echo date('M d, Y', strtotime($user['last_login'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($user['last_login'])); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['employee_id']): ?>
                                            <span class="badge badge-primary-modern">
                                                #<?php echo htmlspecialchars($user['employee_no'] ?? $user['employee_id']); ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['surname'] ?? ''))); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <button class="btn btn-sm btn-outline-modern view-user-btn" 
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    title="View Details">
                                                <i class="fa-solid fa-eye" aria-hidden="true" style="font-size: 0.95rem;"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-modern delete-user-btn"
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                    title="Delete User">
                                                <i class="fa-solid fa-trash" aria-hidden="true" style="font-size: 0.95rem;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Users pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page_num > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=users&<?php echo http_build_query(array_merge($filters, ['p' => $page_num - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page_num - 2);
                        $end_page = min($total_pages, $page_num + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i === $page_num ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=users&<?php echo http_build_query(array_merge($filters, ['p' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page_num < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=users&<?php echo http_build_query(array_merge($filters, ['p' => $page_num + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details & Permissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" style="margin-top: 1rem;">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createUserModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Create New User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createUserForm" novalidate>
                <div class="modal-body">
                    <div id="createUserAlert"></div>
                    
                    <div class="row g-2">
                        <!-- Required Fields -->
                        <div class="col-12">
                            <h6 class="text-muted mb-2 border-bottom pb-1" style="font-size: 0.9rem;">Required Information</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="create_username" 
                                   name="username" 
                                   required
                                   maxlength="50"
                                   autocomplete="username"
                                   placeholder="Enter username">
                            <small class="text-muted">Must be unique (max 50 characters)</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control" 
                                   id="create_email" 
                                   name="email" 
                                   required
                                   maxlength="100"
                                   autocomplete="email"
                                   placeholder="user@example.com">
                            <small class="text-muted">Must be unique (max 100 characters)</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control" 
                                   id="create_password" 
                                   name="password" 
                                   required
                                   minlength="8"
                                   autocomplete="new-password"
                                   placeholder="Minimum 8 characters">
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="create_name" 
                                   name="name" 
                                   required
                                   maxlength="100"
                                   placeholder="Enter full name">
                            <small class="text-muted">Max 100 characters</small>
                        </div>
                        
                        <!-- Role & Status -->
                        <div class="col-12 mt-2">
                            <h6 class="text-muted mb-2 border-bottom pb-1" style="font-size: 0.9rem;">Role & Status</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="create_role" name="role" required>
                                <option value="hr_admin" selected>HR Administrator</option>
                                <option value="super_admin">Super Administrator</option>
                                <option value="hr">HR Staff</option>
                                <option value="admin">Administrator</option>
                                <option value="accounting">Accounting</option>
                                <option value="operation">Operation</option>
                                <option value="logistics">Logistics</option>
                                <option value="employee">Employee</option>
                                <option value="developer">Developer</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_status" class="form-label">Status</label>
                            <select class="form-select" id="create_status" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            <small class="text-muted">User can login if Active</small>
                        </div>
                        
                        <!-- Additional Fields -->
                        <div class="col-12 mt-2">
                            <h6 class="text-muted mb-2 border-bottom pb-1" style="font-size: 0.9rem;">Additional Information</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_department" class="form-label">Department <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="create_department" 
                                   name="department" 
                                   required
                                   maxlength="100"
                                   placeholder="e.g., Human Resources">
                            <small class="text-muted">Max 100 characters</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="create_phone" 
                                   name="phone" 
                                   required
                                   maxlength="20"
                                   placeholder="0917-123-4567">
                            <small class="text-muted">Max 20 characters</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_employee_id" class="form-label">Employee ID</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="create_employee_id" 
                                   name="employee_id" 
                                   placeholder="Employee ID">
                            <small class="text-muted">Link to existing employee record (optional)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-modern" id="createUserSubmitBtn">
                        <i class="fas fa-user-plus me-2"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Role Change Confirmation Modal -->
<div class="modal fade" id="roleChangeModal" tabindex="-1" aria-labelledby="roleChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" style="margin-top: 1rem;">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="roleChangeModalLabel">
                    <i class="fas fa-user-cog me-2"></i>Confirm Role Change
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-0" id="roleChangeMessage">Are you sure you want to change the user's role?</p>
                    </div>
                </div>
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> This action will immediately update the user's role and permissions.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary-modern" id="confirmRoleChangeBtn">
                    <i class="fas fa-check me-2"></i>Confirm Change
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.user-role-select,
.user-status-select {
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.user-role-select:focus,
.user-status-select:focus {
    border-color: #1fb2d5;
    box-shadow: 0 0 0 3px rgba(31, 178, 213, 0.1);
}

.user-role-select.changed,
.user-status-select.changed {
    border-color: #f59e0b;
    background-color: #fef3c7;
}

.avatar-placeholder {
    font-size: 1rem;
}

.user-actions {
    display: inline-flex;
    gap: 0.5rem;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.user-actions .btn {
    width: 36px;
    height: 36px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.user-actions .btn i {
    margin: 0 !important;
    display: inline-block;
}

.page-link {
    color: #1fb2d5;
    border-color: #e2e8f0;
}

.page-link:hover {
    color: #0ea5e9;
    background-color: #f8fafc;
    border-color: #cbd5e1;
}

.page-item.active .page-link {
    background-color: #1fb2d5;
    border-color: #1fb2d5;
    color: white;
}

/* Fix modal z-index - Must be above header (1100) and sidebar (1000) */
/* Create User Modal - Positioned at Top, Smaller Size */
#createUserModal.modal {
    align-items: flex-start !important;
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
}

#createUserModal .modal-dialog {
    max-width: 700px;
    width: 90%;
    margin: 1rem auto !important;
    margin-top: 1rem !important;
    align-self: flex-start !important;
}

#createUserModal .modal-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%);
    color: white;
    border-bottom: none;
}

#createUserModal .modal-header .btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}

#createUserModal .modal-content {
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    border-radius: 12px;
    overflow: hidden;
    max-height: calc(100vh - 2rem);
    display: flex;
    flex-direction: column;
}

#createUserModal .modal-header {
    padding: 1rem 1.25rem;
    flex-shrink: 0;
}

#createUserModal .modal-body {
    overflow-y: auto;
    flex: 1 1 auto;
    padding: 1.25rem;
}

#createUserModal .modal-footer {
    padding: 1rem 1.25rem;
    flex-shrink: 0;
    border-top: 1px solid #e2e8f0;
}

@media (max-width: 768px) {
    #createUserModal .modal-dialog {
        margin: 1rem;
        max-width: calc(100% - 2rem);
        width: calc(100% - 2rem);
    }
    
    #createUserModal .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

/* Role Change Confirmation Modal - Positioned at Top, Smaller Size */
#roleChangeModal.modal {
    align-items: flex-start !important;
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
}

#roleChangeModal .modal-dialog {
    max-width: 500px;
    width: 90%;
    margin: 1rem auto !important;
    margin-top: 1rem !important;
    align-self: flex-start !important;
}

#roleChangeModal .modal-header {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 50%, #ea580c 100%);
    color: #1e293b;
    border-bottom: none;
    padding: 1rem 1.25rem;
    flex-shrink: 0;
}

#roleChangeModal .modal-content {
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    border-radius: 12px;
    overflow: hidden;
    max-height: calc(100vh - 2rem);
    display: flex;
    flex-direction: column;
}

#roleChangeModal .modal-body {
    padding: 1.25rem;
    overflow-y: auto;
    flex: 1 1 auto;
}

#roleChangeModal .modal-footer {
    padding: 1rem 1.25rem;
    flex-shrink: 0;
    border-top: 1px solid #e2e8f0;
}

@media (max-width: 768px) {
    #roleChangeModal .modal-dialog {
        margin: 1rem;
        max-width: calc(100% - 2rem);
        width: calc(100% - 2rem);
    }
}
/* Card styling to match HR admin dashboard */
.card-modern,
.card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    background: #ffffff;
    overflow: hidden;
    transition: all 0.3s ease;
    outline: none !important;
}

.card-modern:hover,
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0 !important;
    outline: none !important;
}

.card-modern:focus,
.card:focus,
.card-modern:focus-visible,
.card:focus-visible {
    outline: none !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0 !important;
}

/* Make Create User button more visible */
.btn-primary-modern {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%) !important;
    color: #ffffff !important;
    border: none !important;
    padding: 0.625rem 1.25rem !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 2px 8px rgba(30, 58, 138, 0.3) !important;
    cursor: pointer !important;
}

.btn-primary-modern:hover {
    background: linear-gradient(135deg, #1e40af 0%, #1e293b 50%, #1e3a8a 100%) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 16px rgba(30, 58, 138, 0.4) !important;
}

.btn-primary-modern:focus,
.btn-primary-modern:focus-visible {
    outline: none !important;
    box-shadow: 0 2px 8px rgba(30, 58, 138, 0.3) !important;
}

.card-body-modern,
.card-body {
    padding: 1.5rem;
}

.card-header-modern {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.card-title-modern {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem 0;
}

/* Dark theme support for User Management page */
html[data-theme="dark"] .super-admin-dashboard {
    background: var(--interface-bg) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-title-main {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-subtitle {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .card-modern {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-header-modern {
    background: #1a1d23 !important;
    border-bottom-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-title-modern {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .card-subtitle {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .card-body-modern {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .form-label {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .form-control,
html[data-theme="dark"] .form-select {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .form-control::placeholder {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .form-control:focus,
html[data-theme="dark"] .form-select:focus {
    background-color: #0f1114 !important;
    border-color: var(--primary-color) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .input-group-text {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .table {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table thead {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table thead.table-light {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table thead th {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .table tbody {
    background: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .table tbody tr {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .table tbody tr:hover {
    background-color: var(--interface-hover) !important;
}

html[data-theme="dark"] .table td {
    background-color: transparent !important;
    color: var(--interface-text) !important;
    border-color: var(--interface-border) !important;
}

html[data-theme="dark"] .fw-semibold {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .text-muted {
    color: var(--interface-text-muted) !important;
}

html[data-theme="dark"] .user-role-select,
html[data-theme="dark"] .user-status-select {
    background-color: #0f1114 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .user-role-select:focus,
html[data-theme="dark"] .user-status-select:focus {
    background-color: #0f1114 !important;
    border-color: var(--primary-color) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .user-role-select.changed,
html[data-theme="dark"] .user-status-select.changed {
    background-color: rgba(245, 158, 11, 0.1) !important;
    border-color: #f59e0b !important;
}

html[data-theme="dark"] .user-actions .btn-outline-modern {
    background: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .user-actions .btn-outline-modern:hover {
    background: var(--interface-hover) !important;
    border-color: var(--primary-color) !important;
    color: var(--primary-color) !important;
}

html[data-theme="dark"] .user-actions .btn-outline-modern i {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .user-actions .btn-outline-modern:hover i {
    color: var(--primary-color) !important;
}

html[data-theme="dark"] .page-link {
    background-color: #1a1d23 !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .page-link:hover {
    background-color: var(--interface-hover) !important;
    border-color: var(--interface-border) !important;
    color: var(--primary-color) !important;
}

html[data-theme="dark"] .page-item.active .page-link {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: white !important;
}

html[data-theme="dark"] .modal-content {
    background-color: #1a1d23 !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .modal-header {
    border-bottom-color: var(--interface-border) !important;
}

html[data-theme="dark"] .modal-title {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .modal-footer {
    border-top-color: var(--interface-border) !important;
}

html[data-theme="dark"] .modal-body {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .user-details h5 {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .user-details .fw-semibold {
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .alert {
    background-color: rgba(30, 41, 59, 0.8) !important;
    border-color: var(--interface-border) !important;
    color: var(--interface-text) !important;
}

html[data-theme="dark"] .alert-info {
    background-color: rgba(31, 178, 213, 0.1) !important;
    border-color: var(--primary-color) !important;
    color: var(--interface-text) !important;
}

</style>

<script>
// Auto-filter functionality - filter automatically as user types or changes dropdowns
function initializeAutoFilter() {
    let searchTimeout;
    const searchInput = document.getElementById('userSearchInput');
    const filterForm = document.getElementById('filterForm');
    
    if (filterForm) {
        // Get the page name input (we'll preserve it)
        const pageNameInput = filterForm.querySelector('#pageNameInput');
        
        // Function to submit form with page reset
        function submitForm() {
            // Remove any existing page number from URL params
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete('page'); // Remove page number if it exists
            
            // Build new URL with page=users and filters, but no page number (defaults to 1)
            const newParams = new URLSearchParams();
            newParams.set('page', 'users');
            
            // Add filters
            const searchValue = filterForm.querySelector('[name="search"]')?.value || '';
            const roleValue = filterForm.querySelector('[name="role"]')?.value || '';
            const statusValue = filterForm.querySelector('[name="status"]')?.value || '';
            
            if (searchValue) newParams.set('search', searchValue);
            if (roleValue) newParams.set('role', roleValue);
            if (statusValue) newParams.set('status', statusValue);
            
            // Navigate to new URL (this resets to page 1)
            window.location.href = '?' + newParams.toString();
        }
        
        // Auto-submit on select changes
        const autoFilterElements = filterForm.querySelectorAll('.auto-filter');
        autoFilterElements.forEach(function(element) {
            element.addEventListener('change', function() {
                submitForm();
            });
        });
        
        // Auto-submit on search input with debounce
        if (searchInput) {
            // Remove any existing listeners by cloning the input
            const newInput = searchInput.cloneNode(true);
            searchInput.parentNode.replaceChild(newInput, searchInput);
            
            // Add event listeners to the new input
            newInput.addEventListener('input', function() {
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                // Set a new timeout to submit after user stops typing (500ms delay)
                searchTimeout = setTimeout(function() {
                    submitForm();
                }, 500);
            });
            
            // Also trigger on Enter key for immediate search
            newInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(searchTimeout);
                    e.preventDefault();
                    submitForm();
                }
            });
        }
    }
}

// Clear all filters function
function clearFilters() {
    window.location.href = '?page=users';
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAutoFilter);
} else {
    initializeAutoFilter();
}

// Re-initialize when page content is loaded via AJAX
document.addEventListener('pageContentLoaded', function(e) {
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'users') {
        setTimeout(initializeAutoFilter, 100);
    }
});

// Also listen for the old event name (backwards compatibility)
document.addEventListener('pageLoaded', function(e) {
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'users') {
        setTimeout(initializeAutoFilter, 100);
    }
});

// Remove the old initialization code and replace with this:

// === GLOBAL STATE ===
window.usersPageState = window.usersPageState || {
    initialized: false,
    pendingRoleChange: null
};

// === MAIN INITIALIZATION FUNCTION ===
function initializeUsersPage() {
    console.log('🎯 Initializing users page');
    
    // Check if we're on the users page
    const urlParams = new URLSearchParams(window.location.search);
    const isUsersPage = urlParams.get('page') === 'users';
    
    if (!isUsersPage) {
        console.log('Not on users page, skipping');
        return;
    }
    
    // Check if Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.log('Bootstrap not ready, retrying in 100ms');
        setTimeout(initializeUsersPage, 100);
        return;
    }
    
    // Check if required elements exist
    const createUserModal = document.getElementById('createUserModal');
    const createUserForm = document.getElementById('createUserForm');
    
    if (!createUserModal || !createUserForm) {
        console.log('Required elements not found, retrying...', {
            modal: !!createUserModal,
            form: !!createUserForm
        });
        setTimeout(initializeUsersPage, 100);
        return;
    }
    
    // Skip if already initialized
    if (window.usersPageState.initialized) {
        console.log('✅ Already initialized');
        return;
    }
    
    console.log('🚀 Starting initialization...');
    window.usersPageState.initialized = true;
    
    // Initialize Bootstrap Modal - destroy old instance first
    let modalInstance = bootstrap.Modal.getInstance(createUserModal);
    if (modalInstance) {
        modalInstance.dispose();
    }
  
    // Initialize Create User modal with standard Bootstrap behavior
    modalInstance = new bootstrap.Modal(createUserModal, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    console.log('✅ Create User modal initialized');
    
    // Store modal instance for later use
    createUserModal._modalInstance = modalInstance;
    
    // Attach form submit handler ONLY ONCE
    if (!createUserForm.hasAttribute('data-submit-handler')) {
        createUserForm.setAttribute('data-submit-handler', 'attached');
        
        createUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🚀 Form submitted');
            
            const alertDiv = document.getElementById('createUserAlert');
            const submitBtn = document.getElementById('createUserSubmitBtn');
            
            // Clear previous alerts
            if (alertDiv) alertDiv.innerHTML = '';
            
            // Validate required fields
            const requiredInputs = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                if (alertDiv) {
                    alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Please fill all required fields</div>';
                }
                return;
            }
            
            // Check password length
            const password = document.getElementById('create_password').value;
            if (password && password.length < 8) {
                if (alertDiv) {
                    alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Password must be at least 8 characters long</div>';
                }
                return;
            }
            
            // Disable button
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
            }
            
            // Prepare data
            const formData = new FormData(this);
            formData.append('action', 'create_user');
            
            // Use current pathname + page=users
            const submitURL = window.location.pathname + '?page=users&_t=' + Date.now();
            
            console.log('📤 Submitting to:', submitURL);
            console.log('📦 Form data:', Object.fromEntries(formData));
            
            // Submit
            fetch(submitURL, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('📥 Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                
                // Try to parse JSON directly; if it fails, log the raw text for debugging
                return response.json().catch(() => {
                    return response.text().then(text => {
                        console.error('Unexpected response (not valid JSON):', text.substring(0, 300));
                        throw new Error('Server returned an unexpected response while creating user');
                    });
                });
            })
            .then(data => {
                console.log('✅ Response:', data);
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create User';
                }
                
                if (data && data.success) {
                    if (alertDiv) {
                        alertDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + 
                            (data.message || 'User created successfully') + '</div>';
                    }
                    
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(createUserModal);
                        if (modal) modal.hide();
                        window.location.href = window.location.pathname + '?page=users';
                    }, 1500);
                } else {
                    if (alertDiv) {
                        alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + 
                            (data.message || 'Failed to create user') + '</div>';
                    }
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create User';
                }
                
                if (alertDiv) {
                    alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + 
                        error.message + '</div>';
                }
            });
        });
        
        console.log('✅ Form handler attached');
    }
    
    // Modal event handlers
    if (!createUserModal.hasAttribute('data-modal-events')) {
        createUserModal.setAttribute('data-modal-events', 'attached');
        
        // Handle modal show event
        createUserModal.addEventListener('show.bs.modal', function() {
            // Focus on first input when modal opens
            const usernameInput = document.getElementById('create_username');
            if (usernameInput) {
                setTimeout(() => usernameInput.focus(), 300);
            }
        });
        
        // Handle backdrop clicks to close modal
        createUserModal.addEventListener('click', function(e) {
            if (e.target === createUserModal) {
                const modalInstance = bootstrap.Modal.getInstance(createUserModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        });
        
        // Reset form when modal is hidden
        createUserModal.addEventListener('hidden.bs.modal', function() {
            if (createUserForm) {
                createUserForm.reset();
                createUserForm.querySelectorAll('.is-invalid').forEach(f => f.classList.remove('is-invalid'));
            }
            const alertDiv = document.getElementById('createUserAlert');
            if (alertDiv) alertDiv.innerHTML = '';
        });
        
        console.log('✅ Modal events attached');
    }
    
    // === ROLE CHANGE FUNCTIONALITY ===
    // Initialize role change modal
    const roleChangeModal = document.getElementById('roleChangeModal');
    if (roleChangeModal && !roleChangeModal.hasAttribute('data-initialized')) {
        roleChangeModal.setAttribute('data-initialized', 'true');
        
        // Initialize Bootstrap modal for role change
        let roleModalInstance = bootstrap.Modal.getInstance(roleChangeModal);
        if (roleModalInstance) {
            roleModalInstance.dispose();
        }
        roleModalInstance = new bootstrap.Modal(roleChangeModal, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        // Handle confirmation button
        const confirmRoleChangeBtn = document.getElementById('confirmRoleChangeBtn');
        if (confirmRoleChangeBtn && !confirmRoleChangeBtn.hasAttribute('data-handler-attached')) {
            confirmRoleChangeBtn.setAttribute('data-handler-attached', 'true');
            confirmRoleChangeBtn.addEventListener('click', function() {
                if (window.usersPageState.pendingRoleChange) {
                    const { userId, newRole, selectElement } = window.usersPageState.pendingRoleChange;
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(roleChangeModal);
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Proceed with role update
                    updateUserRole(userId, newRole, selectElement);
                    
                    // Clear pending change
                    window.usersPageState.pendingRoleChange = null;
                }
            });
        }
        
        // Handle modal cancel - revert selection
        roleChangeModal.addEventListener('hidden.bs.modal', function() {
            if (window.usersPageState.pendingRoleChange) {
                // Revert selection if modal was closed without confirming
                window.usersPageState.pendingRoleChange.selectElement.value = 
                    window.usersPageState.pendingRoleChange.originalValue;
                window.usersPageState.pendingRoleChange.selectElement.classList.remove('changed');
                window.usersPageState.pendingRoleChange = null;
            }
        });
        
        // Modal event handlers - no positioning needed, uses standard Bootstrap behavior
        roleChangeModal.addEventListener('show.bs.modal', function() {
            // Modal will appear at top automatically via CSS
        });
        
        roleChangeModal.addEventListener('shown.bs.modal', function() {
            // Modal is now visible at top
        });
        
        console.log('✅ Role change modal initialized');
    }
    
    // Attach role change handlers to all role selects (use event delegation for dynamic content)
    document.querySelectorAll('.user-role-select').forEach(select => {
        if (!select.hasAttribute('data-role-handler-attached')) {
            select.setAttribute('data-role-handler-attached', 'true');
            
            select.addEventListener('change', function() {
                const userId = this.dataset.userId;
                const username = this.dataset.username || 'User';
                const userName = this.dataset.userName || username;
                const newRole = this.value;
                const newRoleText = this.options[this.selectedIndex].text;
                const originalValue = this.getAttribute('data-original-value') || this.value;
                
                if (!this.hasAttribute('data-original-value')) {
                    this.setAttribute('data-original-value', originalValue);
                }
                
                this.classList.add('changed');
                
                // Store pending change data
                window.usersPageState.pendingRoleChange = {
                    userId: userId,
                    username: username,
                    userName: userName,
                    newRole: newRole,
                    newRoleText: newRoleText,
                    selectElement: this,
                    originalValue: originalValue
                };
                
                // Show confirmation modal
                showRoleChangeModal(userName, newRoleText);
            });
        }
    });
    
    // Attach status change handlers
    document.querySelectorAll('.user-status-select').forEach(select => {
        if (!select.hasAttribute('data-status-handler-attached')) {
            select.setAttribute('data-status-handler-attached', 'true');
            
            select.addEventListener('change', function() {
                const userId = this.dataset.userId;
                const newStatus = this.value;
                const statusText = this.options[this.selectedIndex].text;
                
                if (!this.hasAttribute('data-original-value')) {
                    this.setAttribute('data-original-value', this.value);
                }
                
                this.classList.add('changed');
                
                // Show confirmation
                if (confirm(`Change user status to "${statusText}"?`)) {
                    updateUserStatus(userId, newStatus, this);
                } else {
                    // Revert selection
                    this.value = this.getAttribute('data-original-value');
                    this.classList.remove('changed');
                }
            });
        }
    });
    
    // Attach view user details handlers
    document.querySelectorAll('.view-user-btn').forEach(btn => {
        if (!btn.hasAttribute('data-view-handler-attached')) {
            btn.setAttribute('data-view-handler-attached', 'true');
            
            btn.addEventListener('click', function() {
                const userId = this.dataset.userId;
                viewUserDetails(userId);
            });
        }
    });

    // Attach delete user handlers
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        if (!btn.hasAttribute('data-delete-handler-attached')) {
            btn.setAttribute('data-delete-handler-attached', 'true');

            btn.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const userName = this.dataset.userName || 'this user';
                deleteUser(userId, userName, this);
            });
        }
    });
    
    console.log('✅ Role/Status/View handlers attached');
    console.log('✅ Users page fully initialized');
}

// === ROLE CHANGE HELPER FUNCTIONS ===
function showRoleChangeModal(userName, newRoleText) {
    const modalElement = document.getElementById('roleChangeModal');
    if (!modalElement) {
        console.error('Role change modal not found');
        return;
    }
    
    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    const messageEl = document.getElementById('roleChangeMessage');
    if (messageEl && window.usersPageState.pendingRoleChange) {
        messageEl.innerHTML = `Are you sure you want to change <strong>${userName}</strong>'s role to <strong>"${newRoleText}"</strong>?`;
    }
    
    modal.show();
}

/**
 * Position modal - Fixed viewport-centered positioning
 * Modal stays centered in viewport regardless of scroll position
 * This function enforces fixed positioning to override Bootstrap defaults
 */
function positionModalRelativeToScroll(modalElement) {
    // Only position if modal is shown
    if (!modalElement.classList.contains('show') && !modalElement.classList.contains('showing')) {
        return;
    }
    
    const modalDialog = modalElement.querySelector('.modal-dialog');
    if (!modalDialog) return;
    
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;
    const minMargin = 20;
    const availableHeight = viewportHeight - (minMargin * 2);
    
    // Enforce fixed positioning on modal container
    modalElement.style.setProperty('position', 'fixed', 'important');
    modalElement.style.setProperty('top', '0', 'important');
    modalElement.style.setProperty('left', '0', 'important');
    modalElement.style.setProperty('right', '0', 'important');
    modalElement.style.setProperty('bottom', '0', 'important');
    modalElement.style.setProperty('width', '100vw', 'important');
    modalElement.style.setProperty('height', '100vh', 'important');
    modalElement.style.setProperty('margin', '0', 'important');
    modalElement.style.setProperty('padding', '0', 'important');
    modalElement.style.setProperty('overflow', 'hidden', 'important');
    modalElement.style.setProperty('display', 'block', 'important');
    
    // Enforce fixed positioning with viewport centering on dialog
    // Fixed positioning is relative to viewport, not document
    modalDialog.style.setProperty('position', 'fixed', 'important');
    modalDialog.style.setProperty('top', '50%', 'important');
    modalDialog.style.setProperty('left', '50%', 'important');
    modalDialog.style.setProperty('transform', 'translate(-50%, -50%)', 'important');
    modalDialog.style.setProperty('margin', '0', 'important');
    modalDialog.style.setProperty('max-height', `${availableHeight}px`, 'important');
    modalDialog.style.setProperty('overflow', 'visible', 'important');
    modalDialog.style.setProperty('width', 'auto', 'important');
    
    // Ensure modal content height adapts to viewport
    const modalContent = modalElement.querySelector('.modal-content');
    if (modalContent) {
        const headerFooterHeight = 120; // Approximate height of header + footer
        const maxContentHeight = availableHeight - headerFooterHeight;
        modalContent.style.setProperty('max-height', `${Math.max(150, maxContentHeight)}px`, 'important');
        modalContent.style.setProperty('overflow-y', 'auto', 'important');
    }
    
    // Force reflow to ensure styles are applied
    void modalDialog.offsetHeight;
    void modalElement.offsetHeight;
}

function updateUserRole(userId, newRole, selectElement) {
    console.log('🔄 Updating user role:', { userId, newRole });
    
    const formData = new FormData();
    formData.append('action', 'update_role');
    formData.append('user_id', userId);
    formData.append('role', newRole);
    
    const submitURL = window.location.pathname + '?page=users';
    
    fetch(submitURL, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        return response.json().catch(() => {
            return response.text().then(text => {
                console.error('Unexpected response (not valid JSON) for role update:', text.substring(0, 300));
                throw new Error('Server returned an unexpected response while updating role');
            });
        });
    })
    .then(data => {
        console.log('✅ Role update response:', data);
        
        if (data.success) {
            selectElement.classList.remove('changed');
            selectElement.setAttribute('data-original-value', newRole);
            showNotification('Role updated successfully', 'success');
        } else {
            // Revert on error
            selectElement.value = selectElement.getAttribute('data-original-value');
            selectElement.classList.remove('changed');
            showNotification(data.message || 'Failed to update role', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Role update error:', error);
        selectElement.value = selectElement.getAttribute('data-original-value');
        selectElement.classList.remove('changed');
        showNotification('An error occurred while updating role', 'error');
    });
}

function updateUserStatus(userId, newStatus, selectElement) {
    console.log('🔄 Updating user status:', { userId, newStatus });
    
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('user_id', userId);
    formData.append('status', newStatus);
    
    const submitURL = window.location.pathname + '?page=users';
    
    fetch(submitURL, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        return response.json().catch(() => {
            return response.text().then(text => {
                console.error('Unexpected response (not valid JSON) for status update:', text.substring(0, 300));
                throw new Error('Server returned an unexpected response while updating status');
            });
        });
    })
    .then(data => {
        console.log('✅ Status update response:', data);
        
        if (data.success) {
            selectElement.classList.remove('changed');
            selectElement.setAttribute('data-original-value', newStatus);
            showNotification('Status updated successfully', 'success');
        } else {
            // Revert on error
            selectElement.value = selectElement.getAttribute('data-original-value');
            selectElement.classList.remove('changed');
            showNotification(data.message || 'Failed to update status', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Status update error:', error);
        selectElement.value = selectElement.getAttribute('data-original-value');
        selectElement.classList.remove('changed');
        showNotification('An error occurred while updating status', 'error');
    });
}

function viewUserDetails(userId) {
    console.log('👁️ Viewing user details:', userId);
    
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    const content = document.getElementById('userDetailsContent');
    
    if (!content) {
        console.error('User details content element not found');
        return;
    }
    
    content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.show();
    
    // Fetch user details
    const url = window.location.pathname + '?page=users&action=get_details&user_id=' + userId;
    fetch(url)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            console.error('❌ Error loading user details:', error);
            content.innerHTML = '<div class="alert alert-danger">Error loading user details</div>';
        });
}

function deleteUser(userId, userName, buttonEl) {
    if (!userId) return;

    const ok = confirm(`Delete ${userName}? This action cannot be undone.`);
    if (!ok) return;

    const formData = new FormData();
    formData.append('action', 'delete_user');
    formData.append('user_id', userId);

    const submitURL = window.location.pathname + '?page=users';

    // Optimistic UI: disable button while deleting
    if (buttonEl) {
        buttonEl.disabled = true;
    }

    fetch(submitURL, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('HTTP ' + response.status);

        return response.json().catch(() => {
            return response.text().then(text => {
                console.error('Unexpected response (not valid JSON) while deleting user:', text.substring(0, 300));
                throw new Error('Server returned an unexpected response while deleting user');
            });
        });
    })
    .then(data => {
        if (data && data.success) {
            // Remove the row
            const row = buttonEl?.closest('tr');
            if (row) row.remove();
            showNotification(data.message || 'User deleted', 'success');
        } else {
            if (buttonEl) buttonEl.disabled = false;
            showNotification(data.message || 'Failed to delete user', 'error');
        }
    })
    .catch(err => {
        console.error('❌ Delete user error:', err);
        if (buttonEl) buttonEl.disabled = false;
        showNotification('An error occurred while deleting the user', 'error');
    });
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// === INITIALIZATION TRIGGERS ===
// Initialize state object if it doesn't exist
if (!window.usersPageState) {
    window.usersPageState = { 
        initialized: false,
        pendingRoleChange: null
    };
}

function tryInit() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('page') !== 'users') {
        console.log('ℹ️ Not on users page, skipping initialization');
        return;
    }
    
    if (typeof bootstrap === 'undefined') {
        console.log('⏳ Bootstrap not ready, retrying...');
        setTimeout(tryInit, 100);
        return;
    }
    
    const modal = document.getElementById('createUserModal');
    if (!modal) {
        console.log('⏳ Modal element not found, retrying...');
        setTimeout(tryInit, 100);
        return;
    }
    
    console.log('🎯 Initializing Users page...');
    initializeUsersPage();
}

// Multiple initialization points
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tryInit);
} else {
    tryInit();
}

// CRITICAL: Listen for AJAX page loads (pageContentLoaded event)
document.addEventListener('pageContentLoaded', function(e) {
    console.log('📄 pageContentLoaded event received:', e.detail);
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'users') {
        console.log('🔄 Resetting initialization flag for users page');
        // Reset all initialization flags so handlers reattach
        window.usersPageState.initialized = false;
        
        // Remove data attributes from elements so they can be re-initialized
        document.querySelectorAll('[data-role-handler-attached]').forEach(el => {
            el.removeAttribute('data-role-handler-attached');
        });
        document.querySelectorAll('[data-status-handler-attached]').forEach(el => {
            el.removeAttribute('data-status-handler-attached');
        });
        document.querySelectorAll('[data-view-handler-attached]').forEach(el => {
            el.removeAttribute('data-view-handler-attached');
        });
        
        // Reset role change modal
        const roleChangeModal = document.getElementById('roleChangeModal');
        if (roleChangeModal) {
            roleChangeModal.removeAttribute('data-initialized');
        }
        
        setTimeout(tryInit, 100);
    }
});

// Also listen for the old event name (backwards compatibility)
document.addEventListener('pageLoaded', function(e) {
    console.log('📄 pageLoaded event received:', e.detail);
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'users') {
        console.log('🔄 Resetting initialization flag for users page');
        // Reset all initialization flags so handlers reattach
        window.usersPageState.initialized = false;
        
        // Remove data attributes from elements so they can be re-initialized
        document.querySelectorAll('[data-role-handler-attached]').forEach(el => {
            el.removeAttribute('data-role-handler-attached');
        });
        document.querySelectorAll('[data-status-handler-attached]').forEach(el => {
            el.removeAttribute('data-status-handler-attached');
        });
        document.querySelectorAll('[data-view-handler-attached]').forEach(el => {
            el.removeAttribute('data-view-handler-attached');
        });
        
        // Reset role change modal
        const roleChangeModal = document.getElementById('roleChangeModal');
        if (roleChangeModal) {
            roleChangeModal.removeAttribute('data-initialized');
        }
        
        setTimeout(tryInit, 100);
    }
});

// Fallback for full page loads
window.addEventListener('load', function() {
    setTimeout(tryInit, 200);
});

console.log('📜 Users page script loaded');
</script>
