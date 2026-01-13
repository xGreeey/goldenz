<?php
$page_title = 'User Management - Super Admin - Golden Z-5 HR System';
$page = 'users';

// Ensure only Super Admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../landing/index.php');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Set JSON header
    header('Content-Type: application/json');
    
    // Enable error reporting for debugging (remove in production)
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    $current_user_id = $_SESSION['user_id'] ?? null;
    
    switch ($action) {
        case 'update_role':
            $new_role = $_POST['role'] ?? '';
            $result = update_user_role($user_id, $new_role, $current_user_id);
            echo json_encode($result);
            exit;
            
        case 'update_status':
            $new_status = $_POST['status'] ?? '';
            $result = update_user_status($user_id, $new_status, $current_user_id);
            echo json_encode($result);
            exit;
            
        case 'create_user':
            $user_data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'name' => trim($_POST['name'] ?? ''),
                'role' => $_POST['role'] ?? 'hr_admin',
                'status' => $_POST['status'] ?? 'active',
                'department' => trim($_POST['department'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'employee_id' => !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null
            ];
            $result = create_user($user_data, $current_user_id);
            echo json_encode($result);
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

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
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="position: relative; z-index: 1057;">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createUserForm">
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
                        
                        <!-- Optional Fields -->
                        <div class="col-12 mt-3">
                            <h6 class="text-muted mb-3 border-bottom pb-2">Additional Information (Optional)</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_department" class="form-label">Department</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="create_department" 
                                   name="department" 
                                   maxlength="100"
                                   placeholder="e.g., Human Resources">
                            <small class="text-muted">Max 100 characters</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="create_phone" class="form-label">Phone</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="create_phone" 
                                   name="phone" 
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

/* Fix modal z-index and backdrop issues - Must be above header (1100) and sidebar (1000) */
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
}

/* Ensure modal backdrop is below modal but above header/sidebar */
.modal-backdrop {
    z-index: 1101 !important;
    pointer-events: auto !important;
}

.modal-backdrop.show {
    z-index: 1101 !important;
    pointer-events: auto !important;
}

/* Critical: Make sure backdrop doesn't block clicks on modal content */
.modal-backdrop + .modal,
.modal-backdrop ~ .modal {
    pointer-events: none !important;
}

.modal-backdrop + .modal .modal-dialog,
.modal-backdrop ~ .modal .modal-dialog {
    pointer-events: auto !important;
}

/* Ensure modal dialog and content can receive clicks */
#createUserModal {
    pointer-events: none !important;
}

#createUserModal .modal-dialog {
    pointer-events: auto !important;
}

/* Ensure modal is above everything when open */
body.modal-open {
    overflow: hidden;
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
</style>

<script>
// Re-enable right-click on this page
// Override the global contextmenu prevention from footer.php
(function() {
    // Add listener in capture phase to intercept and allow right-click
    document.addEventListener('contextmenu', function(e) {
        // Stop any preventDefault calls from other listeners
        e.stopImmediatePropagation();
        // Allow the default context menu
        return true;
    }, true);
    
    // Also add after page loads to ensure it works
    window.addEventListener('load', function() {
        document.addEventListener('contextmenu', function(e) {
            e.stopImmediatePropagation();
            return true;
        }, true);
    });
})();

document.addEventListener('DOMContentLoaded', function() {
    // Main page functionality
    // Handle role changes
    document.querySelectorAll('.user-role-select').forEach(select => {
        select.addEventListener('change', function() {
            const userId = this.dataset.userId;
            const newRole = this.value;
            const originalValue = this.getAttribute('data-original-value') || this.options[this.selectedIndex].text;
            
            if (!this.hasAttribute('data-original-value')) {
                this.setAttribute('data-original-value', originalValue);
            }
            
            this.classList.add('changed');
            
            // Show confirmation
            if (confirm(`Change user role to "${this.options[this.selectedIndex].text}"?`)) {
                updateUserRole(userId, newRole, this);
            } else {
                // Revert selection
                this.value = this.getAttribute('data-original-value');
                this.classList.remove('changed');
            }
        });
    });
    
    // Handle status changes
    document.querySelectorAll('.user-status-select').forEach(select => {
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
    });
    
    // View user details
    document.querySelectorAll('.view-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            viewUserDetails(userId);
        });
    });
    
    function updateUserRole(userId, newRole, selectElement) {
        const formData = new FormData();
        formData.append('action', 'update_role');
        formData.append('user_id', userId);
        formData.append('role', newRole);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
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
            console.error('Error:', error);
            selectElement.value = selectElement.getAttribute('data-original-value');
            selectElement.classList.remove('changed');
            showNotification('An error occurred', 'error');
        });
    }
    
    function updateUserStatus(userId, newStatus, selectElement) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('user_id', userId);
        formData.append('status', newStatus);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
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
            console.error('Error:', error);
            selectElement.value = selectElement.getAttribute('data-original-value');
            selectElement.classList.remove('changed');
            showNotification('An error occurred', 'error');
        });
    }
    
    function viewUserDetails(userId) {
        const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
        const content = document.getElementById('userDetailsContent');
        
        content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        modal.show();
        
        // Fetch user details (you can implement this via AJAX if needed)
        // For now, just show basic info
        fetch(`?page=users&action=get_details&user_id=${userId}`)
            .then(response => response.text())
            .then(html => {
                content.innerHTML = html;
            })
            .catch(error => {
                content.innerHTML = '<div class="alert alert-danger">Error loading user details</div>';
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
            notification.remove();
        }, 3000);
    }
    
    // Create User Modal Handlers
    const createUserModal = document.getElementById('createUserModal');
    const createUserForm = document.getElementById('createUserForm');
    
    // Fix modal backdrop issues
    if (createUserModal) {
        // Fix backdrop to not interfere with modal clicks
        createUserModal.addEventListener('show.bs.modal', function() {
            // Wait for Bootstrap to create the backdrop
            setTimeout(function() {
                // Remove any existing duplicate backdrops
                const existingBackdrops = document.querySelectorAll('.modal-backdrop');
                existingBackdrops.forEach((backdrop, index) => {
                    if (index > 0) {
                        backdrop.remove();
                    } else {
                        // Set backdrop z-index and ensure it doesn't block modal
                        backdrop.style.zIndex = '1101';
                        backdrop.style.pointerEvents = 'auto';
                    }
                });
                
                // Critical: Set modal to not receive pointer events, but dialog should
                createUserModal.style.zIndex = '1200';
                createUserModal.style.display = 'block';
                createUserModal.style.pointerEvents = 'none'; // Modal container doesn't receive clicks
                
                const modalDialog = createUserModal.querySelector('.modal-dialog');
                if (modalDialog) {
                    modalDialog.style.zIndex = '1201';
                    modalDialog.style.position = 'relative';
                    modalDialog.style.pointerEvents = 'auto'; // Dialog receives clicks
                }
                const modalContent = createUserModal.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.style.zIndex = '1202';
                    modalContent.style.position = 'relative';
                    modalContent.style.pointerEvents = 'auto'; // Content receives clicks
                }
                
                // Ensure all form elements are clickable
                const formElements = createUserModal.querySelectorAll('input, select, textarea, button, label, a');
                formElements.forEach(el => {
                    el.style.pointerEvents = 'auto';
                    el.style.position = 'relative';
                    el.style.zIndex = '1';
                });
            }, 10);
        });
        
        // Reset form when modal is closed
        createUserModal.addEventListener('hidden.bs.modal', function() {
            // Clean up any duplicate backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach((backdrop, index) => {
                if (index > 0) {
                    backdrop.remove();
                }
            });
            
            if (createUserForm) {
                createUserForm.reset();
            }
            const alertDiv = document.getElementById('createUserAlert');
            if (alertDiv) {
                alertDiv.innerHTML = '';
            }
            const submitBtn = document.getElementById('createUserSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create User';
            }
        });
        
        // Focus first input when modal opens
        createUserModal.addEventListener('shown.bs.modal', function() {
            // Ensure modal is on top (above header z-index 1100)
            createUserModal.style.zIndex = '1200';
            createUserModal.style.display = 'block';
            createUserModal.style.pointerEvents = 'none'; // Container doesn't receive clicks
            
            // Remove any duplicate backdrops and set correct z-index
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach((backdrop, index) => {
                if (index > 0) {
                    backdrop.remove();
                } else {
                    backdrop.style.zIndex = '1101';
                    backdrop.style.pointerEvents = 'auto';
                }
            });
            
            // Ensure modal dialog receives clicks
            const modalDialog = createUserModal.querySelector('.modal-dialog');
            if (modalDialog) {
                modalDialog.style.pointerEvents = 'auto';
                modalDialog.style.position = 'relative';
                modalDialog.style.zIndex = '1201';
            }
            
            // Ensure modal content is clickable
            const modalContent = createUserModal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.pointerEvents = 'auto';
                modalContent.style.position = 'relative';
                modalContent.style.zIndex = '1202';
            }
            
            const modalBody = createUserModal.querySelector('.modal-body');
            if (modalBody) {
                modalBody.style.pointerEvents = 'auto';
                modalBody.style.position = 'relative';
                modalBody.style.zIndex = '1';
            }
            
            // Make sure all inputs are clickable
            const inputs = createUserModal.querySelectorAll('input, select, textarea, button, label, a');
            inputs.forEach(input => {
                input.style.pointerEvents = 'auto';
                input.style.position = 'relative';
                input.style.zIndex = '1';
            });
            
            // Force reflow to ensure styles are applied
            createUserModal.offsetHeight;
            
            const usernameInput = document.getElementById('create_username');
            if (usernameInput) {
                setTimeout(() => {
                    usernameInput.focus();
                }, 150);
            }
        });
    }
    
    // Additional fix: Clean up on page load if modal was left open
    window.addEventListener('load', function() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) {
            backdrops.forEach((backdrop, index) => {
                if (index > 0) {
                    backdrop.remove();
                }
            });
        }
    });
    
    // Form submission handler
    if (createUserForm) {
        createUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const alertDiv = document.getElementById('createUserAlert');
            const submitBtn = document.getElementById('createUserSubmitBtn');
            
            // Clear previous alerts
            if (alertDiv) {
                alertDiv.innerHTML = '';
            }
            
            // Validate form
            if (!createUserForm.checkValidity()) {
                createUserForm.reportValidity();
                return;
            }
            
            // Validate password length
            const password = document.getElementById('create_password').value;
            if (password.length < 8) {
                if (alertDiv) {
                    alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Password must be at least 8 characters long</div>';
                }
                return;
            }
            
            // Disable submit button
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
            }
            
            // Prepare form data
            const formData = new FormData(createUserForm);
            formData.append('action', 'create_user');
            
            // Submit via AJAX
            const formAction = window.location.pathname + window.location.search;
            
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
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create User';
                }
                
                if (data && data.success) {
                    // Show success message
                    if (alertDiv) {
                        alertDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + (data.message || 'User created successfully') + '</div>';
                    }
                    
                    // Close modal and reload page after short delay
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(createUserModal);
                        if (modal) {
                            modal.hide();
                        }
                        // Reload page to show new user
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    const errorMsg = (data && data.message) ? data.message : 'Failed to create user. Please check all fields and try again.';
                    if (alertDiv) {
                        alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + errorMsg + '</div>';
                        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }
            })
            .catch(error => {
                console.error('Create User Error:', error);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create User';
                }
                if (alertDiv) {
                    alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>An error occurred while creating the user. Please try again.</div>';
                    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });
    }
});
</script>
