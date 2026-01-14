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
    'role' => $_GET['role'] ?? '',
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
];

$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
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
                <input type="hidden" name="page" value="users">
                <div class="col-md-4">
                    <label class="form-label">Search Users</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by name, username, or email..."
                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter by Role</label>
                    <select name="role" class="form-select">
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
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $filters['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary-modern w-100">
                        <i class="fas fa-filter me-2"></i>Filter
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
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-modern view-user-btn" 
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
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
                                <a class="page-link" href="?page=users&<?php echo http_build_query(array_merge($filters, ['page' => $page_num - 1])); ?>">
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
                                <a class="page-link" href="?page=users&<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page_num < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=users&<?php echo http_build_query(array_merge($filters, ['page' => $page_num + 1])); ?>">
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
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true" data-bs-backdrop="false" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="position: relative; z-index: 1057;">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createUserForm" novalidate>
                <div class="modal-body">
                    <div id="createUserAlert"></div>
                    
                    <div class="row g-3">
                        <!-- Required Fields -->
                        <div class="col-12">
                            <h6 class="text-muted mb-3 border-bottom pb-2">Required Information</h6>
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
                        <div class="col-12 mt-3">
                            <h6 class="text-muted mb-3 border-bottom pb-2">Role & Status</h6>
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
                        <div class="col-12 mt-3">
                            <h6 class="text-muted mb-3 border-bottom pb-2">Additional Information</h6>
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
<div class="modal fade" id="roleChangeModal" tabindex="-1" aria-labelledby="roleChangeModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleChangeModalLabel">
                    <i class="fas fa-user-cog me-2"></i>Confirm Role Change
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 2.5rem;"></i>
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
#createUserModal {
    z-index: 1200 !important;
}

#createUserModal .modal-dialog {
    z-index: 1201 !important;
    position: relative;
}

#createUserModal .modal-content {
    z-index: 1202 !important;
    position: relative;
    /* Glassmorphism-style blurred background for nicer appearance */
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

/* Ensure modal dialog and content can receive clicks */
#createUserModal .modal-dialog {
    pointer-events: auto !important;
}

body.modal-open .modal-backdrop {
    z-index: 1101 !important;
}

body.modal-open #createUserModal {
    z-index: 1200 !important;
}

/* Fix any potential pointer-events issues */
#createUserModal .modal-content * {
    pointer-events: auto !important;
}

#createUserModal input,
#createUserModal select,
#createUserModal textarea,
#createUserModal button,
#createUserModal label {
    pointer-events: auto !important;
    position: relative;
    z-index: 1;
}

/* Ensure modal body is clickable */
#createUserModal .modal-body {
    pointer-events: auto !important;
    position: relative;
    z-index: 1;
}

/* Make sure form elements are interactive */
#createUserModal form {
    pointer-events: auto !important;
    position: relative;
    z-index: 1;
}

/* Role Change Confirmation Modal */
#roleChangeModal {
    z-index: 1200 !important;
}

#roleChangeModal .modal-dialog {
    z-index: 1201 !important;
    position: relative;
}

#roleChangeModal .modal-content {
    z-index: 1202 !important;
    position: relative;
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

body.modal-open #roleChangeModal {
    z-index: 1200 !important;
}

#roleChangeModal .modal-body {
    padding: 1.5rem;
}

#roleChangeModal .modal-footer {
    border-top: 1px solid #e2e8f0;
    padding: 1rem 1.5rem;
}

/* Hide backdrop for role change modal */
body:has(#roleChangeModal.show) .modal-backdrop,
#roleChangeModal.show ~ .modal-backdrop,
.modal-backdrop:has(+ #roleChangeModal.show) {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
}
</style>

<script>
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
    new bootstrap.Modal(createUserModal, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    console.log('✅ Modal initialized');
    
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
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text.substring(0, 200));
                        throw new Error('Server returned non-JSON response');
                    });
                }
                
                return response.json();
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
        
        createUserModal.addEventListener('hidden.bs.modal', function() {
            if (createUserForm) {
                createUserForm.reset();
                createUserForm.querySelectorAll('.is-invalid').forEach(f => f.classList.remove('is-invalid'));
            }
            const alertDiv = document.getElementById('createUserAlert');
            if (alertDiv) alertDiv.innerHTML = '';
        });
        
        createUserModal.addEventListener('shown.bs.modal', function() {
            const usernameInput = document.getElementById('create_username');
            if (usernameInput) setTimeout(() => usernameInput.focus(), 100);
        });
        
        console.log('✅ Modal events attached');
    }
    
    console.log('✅ Users page fully initialized');
}

// === INITIALIZATION TRIGGERS ===
// Initialize state object if it doesn't exist
if (!window.usersPageState) {
    window.usersPageState = { initialized: false };
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
        window.usersPageState.initialized = false;
        setTimeout(tryInit, 100);
    }
});

// Also listen for the old event name (backwards compatibility)
document.addEventListener('pageLoaded', function(e) {
    console.log('📄 pageLoaded event received:', e.detail);
    const page = e.detail?.page || new URLSearchParams(window.location.search).get('page');
    if (page === 'users') {
        console.log('🔄 Resetting initialization flag for users page');
        window.usersPageState.initialized = false;
        setTimeout(tryInit, 100);
    }
});

// Fallback for full page loads
window.addEventListener('load', function() {
    setTimeout(tryInit, 200);
});

console.log('📜 Users page script loaded');
</script>
