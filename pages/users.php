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
                <?php 
                $avatar_url = !empty($user['avatar']) ? get_avatar_url($user['avatar']) : null;
                if ($avatar_url): ?>
                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" 
                         alt="<?php echo htmlspecialchars($user['name']); ?>" 
                         class="mb-3 avatar-md">
                <?php else: ?>
                    <div class="avatar-placeholder avatar-placeholder-lg mx-auto mb-3">
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
                                                <?php 
                                                $avatar_url = !empty($user['avatar']) ? get_avatar_url($user['avatar']) : null;
                                                if ($avatar_url): ?>
                                                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" 
                                                         alt="<?php echo htmlspecialchars($user['name']); ?>" 
                                                         class="avatar-sm"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="avatar-placeholder avatar-placeholder-sm" style="display: none;">
                                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="avatar-placeholder avatar-placeholder-sm">
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
                                        <select class="form-select form-select-sm user-role-select min-w-150" 
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-user-name="<?php echo htmlspecialchars($user['name']); ?>">
                                            <?php foreach ($role_config as $role_key => $role_data): ?>
                                                <option value="<?php echo $role_key; ?>" 
                                                        <?php echo $user['role'] === $role_key ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($role_data['name'] ?? ucfirst($role_key)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm user-status-select min-w-120" 
                                                data-user-id="<?php echo $user['id']; ?>">
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
                                                <i class="fa-solid fa-trash icon-sm" aria-hidden="true"></i>
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

<!-- Futuristic Role Change Confirmation Modal -->
<div class="modal fade" id="roleChangeConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content futuristic-modal">
            <div class="futuristic-modal-header">
                <div class="futuristic-icon-wrapper">
                    <i class="fas fa-user-shield futuristic-icon"></i>
                    <div class="futuristic-pulse"></div>
                </div>
                <h5 class="futuristic-modal-title">Confirm Role Change</h5>
            </div>
            <div class="futuristic-modal-body">
                <p class="futuristic-message" id="roleChangeConfirmMessage"></p>
                <div class="futuristic-info-box">
                    <i class="fas fa-info-circle"></i>
                    <span>This action will update the user's permissions and access levels.</span>
                </div>
            </div>
            <div class="futuristic-modal-footer">
                <button type="button" class="btn futuristic-btn-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn futuristic-btn-confirm" id="confirmRoleChangeBtn">
                    <i class="fas fa-check me-2"></i>Confirm Change
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Futuristic Status Change Confirmation Modal -->
<div class="modal fade" id="statusChangeConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content futuristic-modal">
            <div class="futuristic-modal-header">
                <div class="futuristic-icon-wrapper">
                    <i class="fas fa-toggle-on futuristic-icon" id="statusChangeIcon"></i>
                    <div class="futuristic-pulse"></div>
                </div>
                <h5 class="futuristic-modal-title">Confirm Status Change</h5>
            </div>
            <div class="futuristic-modal-body">
                <p class="futuristic-message" id="statusChangeConfirmMessage"></p>
                <div class="futuristic-info-box" id="statusChangeInfoBox">
                    <i class="fas fa-info-circle"></i>
                    <span id="statusChangeInfoText">This action will change the user's access status.</span>
                </div>
            </div>
            <div class="futuristic-modal-footer">
                <button type="button" class="btn futuristic-btn-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn futuristic-btn-confirm" id="confirmStatusChangeBtn">
                    <i class="fas fa-check me-2"></i>Confirm Change
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
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
                    
                    <div class="row g-2 compact-form">
                        <div class="col-md-6">
                            <label for="create_username" class="form-label small">Username <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="create_username" 
                                   name="username" 
                                   required
                                   maxlength="50"
                                   autocomplete="username"
                                   placeholder="Enter username">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_email" class="form-label small">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control form-control-sm" 
                                   id="create_email" 
                                   name="email" 
                                   required
                                   maxlength="100"
                                   autocomplete="email"
                                   placeholder="user@example.com">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_first_name" class="form-label small">First Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="create_first_name" 
                                   name="first_name" 
                                   required
                                   maxlength="100"
                                   placeholder="Enter first name">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_last_name" class="form-label small">Last Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="create_last_name" 
                                   name="last_name" 
                                   required
                                   maxlength="100"
                                   placeholder="Enter last name">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_role" class="form-label small">Role <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="create_role" name="role" required>
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
                            <label for="create_status" class="form-label small">Status</label>
                            <select class="form-select form-select-sm" id="create_status" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_department" class="form-label small">Department <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="create_department" 
                                   name="department" 
                                   required
                                   maxlength="100"
                                   placeholder="e.g., Human Resources">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_phone" class="form-label small">Phone <span class="text-danger">*</span></label>
                            <input type="tel" 
                                   class="form-control form-control-sm" 
                                   id="create_phone" 
                                   name="phone" 
                                   required
                                   maxlength="20"
                                   placeholder="0917-123-4567">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_employee_id" class="form-label small">Employee ID</label>
                            <input type="number" 
                                   class="form-control form-control-sm" 
                                   id="create_employee_id" 
                                   name="employee_id" 
                                   placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-modern btn-sm" id="createUserSubmitBtn">
                        <i class="fas fa-user-plus me-2"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Role Change Confirmation Modal -->

<style>
/* Page Header - Rectangle container with rounded corners */
.super-admin-dashboard .page-header-modern {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 14px !important;
    padding: 1.5rem 2rem !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 4px 12px rgba(0, 0, 0, 0.04) !important;
}

.super-admin-dashboard .page-header-modern .page-title-modern {
    padding-left: 1rem;
}

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

/* User Avatar Sizing - Match Header Profile Avatar */
.user-avatar {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-avatar img,
.user-avatar .avatar-sm,
.user-avatar .avatar-md {
    width: 40px !important;
    height: 40px !important;
    min-width: 40px !important;
    min-height: 40px !important;
    max-width: 40px !important;
    max-height: 40px !important;
    border-radius: 8px !important;
    object-fit: cover !important;
    display: block !important;
}

.user-avatar .avatar-placeholder,
.user-avatar .avatar-placeholder-sm,
.user-avatar .avatar-placeholder-md,
.user-avatar .avatar-placeholder-lg {
    width: 40px !important;
    height: 40px !important;
    min-width: 40px !important;
    min-height: 40px !important;
    max-width: 40px !important;
    max-height: 40px !important;
    border-radius: 8px !important;
    font-size: 0.875rem !important;
}

/* User Details Section Avatar */
.user-details .avatar-md,
.user-details .avatar-placeholder-lg {
    width: 40px !important;
    height: 40px !important;
    min-width: 40px !important;
    min-height: 40px !important;
    max-width: 40px !important;
    max-height: 40px !important;
    border-radius: 8px !important;
    object-fit: cover !important;
}

.user-details .avatar-placeholder-lg {
    font-size: 0.875rem !important;
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
    max-width: 600px;
    width: 90%;
    margin: 0.5rem auto !important;
    margin-top: 0.5rem !important;
    align-self: flex-start !important;
}

#createUserModal .modal-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%);
    color: white;
    border-bottom: none;
    padding: 0.75rem 1rem;
    flex-shrink: 0;
}

#createUserModal .modal-header .modal-title {
    font-size: 1rem;
    font-weight: 600;
}

#createUserModal .modal-header .btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}

#createUserModal .modal-content {
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    border-radius: 12px;
    overflow: hidden;
    max-height: calc(100vh - 1rem);
    display: flex;
    flex-direction: column;
}

#createUserModal .modal-body {
    overflow-y: auto;
    flex: 1 1 auto;
    padding: 0.75rem 1rem;
}

#createUserModal .modal-footer {
    padding: 0.5rem 1rem;
    flex-shrink: 0;
    border-top: 1px solid #e2e8f0;
}

/* Compact form styling */
#createUserModal .compact-form .form-label {
    margin-bottom: 0.25rem;
    font-size: 0.8rem;
    font-weight: 500;
}

#createUserModal .compact-form .form-control-sm,
#createUserModal .compact-form .form-select-sm {
    padding: 0.35rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.4;
}

#createUserModal .compact-form .row {
    margin-bottom: 0;
}

#createUserModal .compact-form .col-md-6 {
    margin-bottom: 0.5rem;
}

#createUserModal .compact-form small {
    display: none;
}

#createUserModal .modal-footer .btn {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
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

/* Futuristic Role Change Confirmation Modal Styles */
.futuristic-modal {
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.95) 100%);
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(99, 102, 241, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    overflow: hidden;
    position: relative;
}

.futuristic-modal::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, 
        rgba(99, 102, 241, 0.1) 0%, 
        rgba(168, 85, 247, 0.1) 50%, 
        rgba(236, 72, 153, 0.1) 100%);
    opacity: 0.6;
    z-index: 0;
    animation: gradientShift 8s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 0.8; }
}

.futuristic-modal > * {
    position: relative;
    z-index: 1;
}

.futuristic-modal-header {
    padding: 2rem 2rem 1rem;
    text-align: center;
    border-bottom: 1px solid rgba(99, 102, 241, 0.2);
    background: linear-gradient(180deg, rgba(99, 102, 241, 0.1) 0%, transparent 100%);
}

.futuristic-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 1rem;
}

.futuristic-icon {
    font-size: 3rem;
    color: #6366f1;
    text-shadow: 0 0 20px rgba(99, 102, 241, 0.6),
                 0 0 40px rgba(99, 102, 241, 0.4);
    animation: iconPulse 2s ease-in-out infinite;
    position: relative;
    z-index: 2;
}

@keyframes iconPulse {
    0%, 100% { 
        transform: scale(1);
        filter: brightness(1);
    }
    50% { 
        transform: scale(1.1);
        filter: brightness(1.3);
    }
}

.futuristic-pulse {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80px;
    height: 80px;
    border: 2px solid rgba(99, 102, 241, 0.5);
    border-radius: 50%;
    animation: pulseRing 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulseRing {
    0% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.5);
        opacity: 0;
    }
}

.futuristic-modal-title {
    color: #ffffff;
    font-weight: 600;
    font-size: 1.5rem;
    margin: 0;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    letter-spacing: 0.5px;
}

.futuristic-modal-body {
    padding: 2rem;
    color: #e2e8f0;
}

.futuristic-message {
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    color: #cbd5e1;
    text-align: center;
}

.futuristic-info-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(99, 102, 241, 0.1);
    border: 1px solid rgba(99, 102, 241, 0.3);
    border-radius: 12px;
    color: #a5b4fc;
    font-size: 0.9rem;
    backdrop-filter: blur(10px);
}

.futuristic-info-box i {
    font-size: 1.2rem;
    color: #818cf8;
    flex-shrink: 0;
}

.futuristic-modal-footer {
    padding: 1.5rem 2rem 2rem;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    border-top: 1px solid rgba(99, 102, 241, 0.2);
    background: linear-gradient(180deg, transparent 0%, rgba(99, 102, 241, 0.05) 100%);
}

.futuristic-btn-cancel,
.futuristic-btn-confirm {
    padding: 0.75rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.futuristic-btn-cancel {
    background: linear-gradient(135deg, rgba(71, 85, 105, 0.8) 0%, rgba(51, 65, 85, 0.8) 100%);
    color: #cbd5e1;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.futuristic-btn-cancel:hover {
    background: linear-gradient(135deg, rgba(71, 85, 105, 1) 0%, rgba(51, 65, 85, 1) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    color: #ffffff;
}

.futuristic-btn-confirm {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4),
                0 0 20px rgba(99, 102, 241, 0.2);
    background-size: 200% 200%;
    animation: gradientMove 3s ease infinite;
}

@keyframes gradientMove {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.futuristic-btn-confirm::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.futuristic-btn-confirm:hover::before {
    left: 100%;
}

.futuristic-btn-confirm:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 25px rgba(99, 102, 241, 0.6),
                0 0 30px rgba(139, 92, 246, 0.4);
}

.futuristic-btn-confirm:active {
    transform: translateY(0) scale(0.98);
}

/* Ensure modal is clickable and properly positioned */
#roleChangeConfirmModal.show,
#statusChangeConfirmModal.show {
    display: block !important;
    z-index: 1060 !important;
    padding-right: 0 !important;
}

#roleChangeConfirmModal .modal-dialog,
#statusChangeConfirmModal .modal-dialog {
    transform: translate(0, 0) !important;
    max-width: 500px;
    margin: 1.75rem auto !important;
    position: relative;
    pointer-events: auto;
}

#roleChangeConfirmModal .futuristic-btn-cancel,
#roleChangeConfirmModal .futuristic-btn-confirm,
#statusChangeConfirmModal .futuristic-btn-cancel,
#statusChangeConfirmModal .futuristic-btn-confirm {
    pointer-events: auto !important;
    cursor: pointer !important;
    z-index: 10;
    position: relative;
}

#roleChangeConfirmModal .modal-content,
#statusChangeConfirmModal .modal-content {
    pointer-events: auto !important;
}

#statusChangeConfirmModal {
    z-index: 1060 !important;
}

#statusChangeConfirmModal .modal-dialog {
    z-index: 1061 !important;
    position: relative;
    margin: 1.75rem auto;
    pointer-events: auto;
}

#statusChangeConfirmModal .modal-backdrop {
    z-index: 1059 !important;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
}

/* Fix for modal appearing below screen */
body.modal-open {
    overflow: hidden;
    padding-right: 0 !important;
}

/* Ensure backdrop doesn't block clicks */
.modal-backdrop.show {
    z-index: 1059 !important;
    pointer-events: auto;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .futuristic-modal-header {
        padding: 1.5rem 1.5rem 0.75rem;
    }
    
    .futuristic-icon {
        font-size: 2.5rem;
    }
    
    .futuristic-modal-title {
        font-size: 1.25rem;
    }
    
    .futuristic-modal-body {
        padding: 1.5rem;
    }
    
    .futuristic-modal-footer {
        padding: 1rem 1.5rem 1.5rem;
        flex-direction: column;
    }
    
    .futuristic-btn-cancel,
    .futuristic-btn-confirm {
        width: 100%;
    }
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
    initialized: false
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
    // Attach role change handlers to all role selects (use event delegation for dynamic content)
    document.querySelectorAll('.user-role-select').forEach(select => {
        if (!select.hasAttribute('data-role-handler-attached')) {
            select.setAttribute('data-role-handler-attached', 'true');
            
            select.addEventListener('change', function() {
                const userId = this.dataset.userId;
                const newRole = this.value;
                const newRoleText = this.options[this.selectedIndex].text;
                const originalValue = this.getAttribute('data-original-value') || this.value;
                
                if (!this.hasAttribute('data-original-value')) {
                    this.setAttribute('data-original-value', originalValue);
                }
                
                this.classList.add('changed');
                
                // Show futuristic confirmation modal
                showRoleChangeConfirm(userId, newRole, newRoleText, this);
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
                
                // Show futuristic confirmation modal
                showStatusChangeConfirm(userId, newStatus, statusText, this);
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

function showRoleChangeConfirm(userId, newRole, newRoleText, selectElement) {
    const modal = document.getElementById('roleChangeConfirmModal');
    const messageEl = document.getElementById('roleChangeConfirmMessage');
    const confirmBtn = document.getElementById('confirmRoleChangeBtn');
    
    if (!modal || !messageEl || !confirmBtn) {
        // Fallback to default confirm if modal not found
        if (confirm(`Change user role to "${newRoleText}"?`)) {
            updateUserRole(userId, newRole, selectElement);
        } else {
            selectElement.value = selectElement.getAttribute('data-original-value');
            selectElement.classList.remove('changed');
        }
        return;
    }
    
    // Ensure modal is in the body (not hidden in a container)
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    
    // Set message
    messageEl.textContent = `Are you sure you want to change the user's role to "${newRoleText}"?`;
    
    // Store original values for cleanup
    const originalConfirmBtn = confirmBtn;
    const originalCancelBtn = modal.querySelector('.futuristic-btn-cancel');
    
    // Remove previous event listeners by cloning buttons
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    // Cleanup function to restore page state
    function cleanupModal() {
        // Remove backdrop
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Remove modal-open class and restore body scroll
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Remove show class from modal
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('aria-modal', 'false');
    }
    
    // Handle confirmation
    newConfirmBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        cleanupModal();
        updateUserRole(userId, newRole, selectElement);
    });
    
    // Handle cancellation
    const cancelBtn = modal.querySelector('.futuristic-btn-cancel');
    if (cancelBtn) {
        const newCancelBtn = cancelBtn.cloneNode(true);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
        
        newCancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            cleanupModal();
            selectElement.value = selectElement.getAttribute('data-original-value');
            selectElement.classList.remove('changed');
        });
    }
    
    // Also handle modal hidden event to ensure cleanup
    modal.addEventListener('hidden.bs.modal', function() {
        cleanupModal();
    });
    
    // Show modal using Bootstrap
    const modalInstance = new bootstrap.Modal(modal, {
        backdrop: 'static',
        keyboard: false,
        focus: true
    });
    
    modalInstance.show();
    
    // Ensure modal is visible and properly positioned after Bootstrap shows it
    setTimeout(() => {
        modal.style.display = 'block';
        modal.style.zIndex = '1060';
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        modal.setAttribute('aria-modal', 'true');
        
        const modalDialog = modal.querySelector('.modal-dialog');
        if (modalDialog) {
            modalDialog.style.zIndex = '1061';
            modalDialog.style.pointerEvents = 'auto';
            modalDialog.style.margin = '1.75rem auto';
        }
        
        // Ensure backdrop exists and is properly positioned
        let backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
        backdrop.style.zIndex = '1059';
        backdrop.classList.add('show');
        
        // Add body class for modal-open
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }, 50);
}

function showStatusChangeConfirm(userId, newStatus, statusText, selectElement) {
    const modal = document.getElementById('statusChangeConfirmModal');
    const messageEl = document.getElementById('statusChangeConfirmMessage');
    const confirmBtn = document.getElementById('confirmStatusChangeBtn');
    const infoTextEl = document.getElementById('statusChangeInfoText');
    const iconEl = document.getElementById('statusChangeIcon');
    const infoBoxEl = document.getElementById('statusChangeInfoBox');
    
    if (!modal || !messageEl || !confirmBtn) {
        // Fallback to default confirm if modal not found
        if (confirm(`Change user status to "${statusText}"?`)) {
            updateUserStatus(userId, newStatus, selectElement);
        } else {
            selectElement.value = selectElement.getAttribute('data-original-value');
            selectElement.classList.remove('changed');
        }
        return;
    }
    
    // Ensure modal is in the body (not hidden in a container)
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    
    // Set message and icon based on status
    messageEl.textContent = `Are you sure you want to change the user's status to "${statusText}"?`;
    
    // Update icon and info text based on status
    let iconClass = 'fas fa-toggle-on';
    let infoMessage = 'This action will change the user\'s access status.';
    
    if (newStatus === 'active') {
        iconClass = 'fas fa-check-circle';
        infoMessage = 'The user will be able to login and access the system.';
        infoBoxEl.style.borderColor = 'rgba(34, 197, 94, 0.3)';
        infoBoxEl.style.background = 'rgba(34, 197, 94, 0.1)';
        iconEl.style.color = '#22c55e';
    } else if (newStatus === 'inactive') {
        iconClass = 'fas fa-pause-circle';
        infoMessage = 'The user will not be able to login. Their account will be inactive.';
        infoBoxEl.style.borderColor = 'rgba(148, 163, 184, 0.3)';
        infoBoxEl.style.background = 'rgba(148, 163, 184, 0.1)';
        iconEl.style.color = '#94a3b8';
    } else if (newStatus === 'suspended') {
        iconClass = 'fas fa-ban';
        infoMessage = 'The user will be suspended and cannot login. This is typically used for disciplinary actions.';
        infoBoxEl.style.borderColor = 'rgba(239, 68, 68, 0.3)';
        infoBoxEl.style.background = 'rgba(239, 68, 68, 0.1)';
        iconEl.style.color = '#ef4444';
    }
    
    iconEl.className = iconClass + ' futuristic-icon';
    infoTextEl.textContent = infoMessage;
    
    // Cleanup function to restore page state
    function cleanupModal() {
        // Remove backdrop
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Remove modal-open class and restore body scroll
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Remove show class from modal
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('aria-modal', 'false');
    }
    
    // Remove previous event listeners by cloning buttons
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    // Handle confirmation
    newConfirmBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        cleanupModal();
        updateUserStatus(userId, newStatus, selectElement);
    });
    
    // Handle cancellation
    const cancelBtn = modal.querySelector('.futuristic-btn-cancel');
    if (cancelBtn) {
        const newCancelBtn = cancelBtn.cloneNode(true);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
        
        newCancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            cleanupModal();
            selectElement.value = selectElement.getAttribute('data-original-value');
            selectElement.classList.remove('changed');
        });
    }
    
    // Also handle modal hidden event to ensure cleanup
    modal.addEventListener('hidden.bs.modal', function() {
        cleanupModal();
    });
    
    // Show modal using Bootstrap
    const modalInstance = new bootstrap.Modal(modal, {
        backdrop: 'static',
        keyboard: false,
        focus: true
    });
    
    modalInstance.show();
    
    // Ensure modal is visible and properly positioned after Bootstrap shows it
    setTimeout(() => {
        modal.style.display = 'block';
        modal.style.zIndex = '1060';
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        modal.setAttribute('aria-modal', 'true');
        
        const modalDialog = modal.querySelector('.modal-dialog');
        if (modalDialog) {
            modalDialog.style.zIndex = '1061';
            modalDialog.style.pointerEvents = 'auto';
            modalDialog.style.margin = '1.75rem auto';
        }
        
        // Ensure backdrop exists and is properly positioned
        let backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
        backdrop.style.zIndex = '1059';
        backdrop.classList.add('show');
        
        // Add body class for modal-open
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }, 50);
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
        
        setTimeout(tryInit, 100);
    }
});

// Fallback for full page loads
window.addEventListener('load', function() {
    setTimeout(tryInit, 200);
});

console.log('📜 Users page script loaded');
</script>
