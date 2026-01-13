<?php
$page_title = 'Permissions Management - Golden Z-5 HR System';
$page = 'permissions';

// Get database connection (already included in header, but ensure it's available)
$pdo = null;
$db_error = null;
if (!function_exists('get_db_connection')) {
    require_once __DIR__ . '/../includes/database.php';
}
try {
    $pdo = get_db_connection();
} catch (Exception $e) {
    $db_error = $e->getMessage();
    // Don't die - let the page render with an error message
}

// Define role-based modules (grouping employees by departments/offices)
$role_modules = [
    'hr_admin' => [
        'name' => 'HR/Admin Module',
        'icon' => 'fa-building',
        'color' => '#0ea5e9',
        'description' => 'HR administrators',
        'roles' => ['hr_admin']
    ],
    'developer' => [
        'name' => 'Developer Module',
        'icon' => 'fa-code',
        'color' => '#8b5cf6',
        'description' => 'System developers and IT staff',
        'roles' => ['developer']
    ]
];

// Pages catalog for per-page role access
$page_catalog = [
    ['key' => 'dashboard', 'name' => 'Dashboard', 'category' => 'Core'],
    ['key' => 'employees', 'name' => 'Employees', 'category' => 'Core'],
    ['key' => 'add_employee', 'name' => 'Add Employee', 'category' => 'Core'],
    ['key' => 'edit_employee', 'name' => 'Edit Employee', 'category' => 'Core'],
    ['key' => 'dtr', 'name' => 'Attendance (DTR)', 'category' => 'Operations'],
    ['key' => 'timeoff', 'name' => 'Time Off', 'category' => 'Operations'],
    ['key' => 'checklist', 'name' => 'Checklist', 'category' => 'Operations'],
    ['key' => 'hiring', 'name' => 'Hiring', 'category' => 'Hiring'],
    ['key' => 'onboarding', 'name' => 'Onboarding', 'category' => 'Hiring'],
    ['key' => 'handbook', 'name' => 'Handbook', 'category' => 'Hiring'],
    ['key' => 'posts', 'name' => 'Posts & Locations', 'category' => 'Posts'],
    ['key' => 'add_post', 'name' => 'Add Post', 'category' => 'Posts'],
    ['key' => 'edit_post', 'name' => 'Edit Post', 'category' => 'Posts'],
    ['key' => 'post_assignments', 'name' => 'Post Assignments', 'category' => 'Posts'],
    ['key' => 'alerts', 'name' => 'Alerts', 'category' => 'Alerts'],
    ['key' => 'settings', 'name' => 'Settings', 'category' => 'Admin'],
    ['key' => 'permissions', 'name' => 'Permissions', 'category' => 'Admin'],
];

// Flatten roles list for page access selector
$all_roles = [];
    foreach ($role_modules as $module) {
        foreach ($module['roles'] as $r) {
            if (!in_array($r, $all_roles, true)) {
                $all_roles[] = $r;
            }
        }
    }

// Available modules for JavaScript (matching role_modules structure)
$available_modules = $role_modules;

// Get current tab
$current_tab = $_GET['tab'] ?? 'role_permissions';
$selected_module = $_GET['module'] ?? '';

// Default to Administration module if none selected
if (!$selected_module) {
    $selected_module = array_key_first($role_modules);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Error handler for AJAX
    try {
        switch ($_POST['action']) {
        case 'set_default_access':
            $module = $_POST['module'] ?? '';
            $default_access = $_POST['default_access'] ?? 'none';
            
            // Update or insert default access
            $check = $pdo->prepare("SELECT id FROM module_default_access WHERE module = ?");
            $check->execute([$module]);
            
            if ($check->fetch()) {
                $stmt = $pdo->prepare("UPDATE module_default_access SET access_level = ?, updated_at = NOW() WHERE module = ?");
                $stmt->execute([$default_access, $module]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO module_default_access (module, access_level, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$module, $default_access]);
            }
            echo json_encode(['success' => true, 'message' => 'Default access updated']);
            exit;
            
        case 'get_groups_list':
            // Get all groups (for future implementation)
            $groups = [];
            echo json_encode(['success' => true, 'groups' => $groups]);
            exit;
            
        case 'check_user_access':
            $user_email = $_POST['user_email'] ?? '';
            $module = $_POST['module'] ?? '';
            
            if (empty($user_email)) {
                echo json_encode(['success' => false, 'message' => 'User email is required']);
                exit;
            }
            
            // Get user by email
            $user_stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE email = ? LIMIT 1");
            $user_stmt->execute([$user_email]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            // Get default access for module
            $default_stmt = $pdo->prepare("SELECT access_level FROM module_default_access WHERE module = ?");
            $default_stmt->execute([$module]);
            $default_access = $default_stmt->fetchColumn() ?: 'none';
            
            // Get role permissions
            $role_perms = ['can_view' => 0, 'can_edit' => 0, 'can_delete' => 0];
            if ($user['role']) {
                $role_stmt = $pdo->prepare("SELECT can_view, can_edit, can_delete FROM role_module_permissions WHERE role = ? AND module = ?");
                $role_stmt->execute([$user['role'], $module]);
                $role_perm = $role_stmt->fetch(PDO::FETCH_ASSOC);
                if ($role_perm) {
                    $role_perms = $role_perm;
                } else {
                    // Apply default access
                    $role_perms['can_view'] = ($default_access !== 'none') ? 1 : 0;
                    $role_perms['can_edit'] = ($default_access === 'query' || $default_access === 'manage') ? 1 : 0;
                    $role_perms['can_delete'] = ($default_access === 'manage') ? 1 : 0;
                }
            }
            
            // Get individual permissions
            $indiv_perms = ['can_view' => 0, 'can_edit' => 0, 'can_delete' => 0];
            $indiv_stmt = $pdo->prepare("SELECT can_view, can_edit, can_delete FROM employee_module_permissions WHERE user_id = ? AND module = ?");
            $indiv_stmt->execute([$user['id'], $module]);
            $indiv_perm = $indiv_stmt->fetch(PDO::FETCH_ASSOC);
            if ($indiv_perm) {
                $indiv_perms = $indiv_perm;
            }
            
            // Calculate effective permissions (individual overrides role)
            $effective_perms = [
                'can_view' => $indiv_perms['can_view'] ?: $role_perms['can_view'],
                'can_edit' => $indiv_perms['can_edit'] ?: $role_perms['can_edit'],
                'can_delete' => $indiv_perms['can_delete'] ?: $role_perms['can_delete']
            ];
            
            echo json_encode([
                'success' => true,
                'user' => $user,
                'module' => $module,
                'role_permissions' => $role_perms,
                'individual_permissions' => $indiv_perms,
                'effective_permissions' => $effective_perms
            ]);
            exit;
            
        case 'update_module_permission':
            $user_id = $_POST['user_id'] ?? 0;
            $module = $_POST['module'] ?? '';
            $permission_type = $_POST['permission_type'] ?? ''; // 'read', 'write', 'share', 'delete', 'manage'
            $value = $_POST['value'] ?? 0; // 0 or 1
            
            // Map permission types to database columns
            $permission_map = [
                'read' => 'can_view',
                'write' => 'can_edit',
                'share' => 'can_edit', // Share uses edit permission
                'delete' => 'can_delete',
                'manage' => 'can_delete' // Manage uses delete permission
            ];
            
            $column = $permission_map[$permission_type] ?? 'can_view';
            
            // Check if individual permission exists
            $check = $pdo->prepare("SELECT id FROM employee_module_permissions WHERE user_id = ? AND module = ?");
            $check->execute([$user_id, $module]);
            
            if ($check->fetch()) {
                // Update existing
                $sql = "UPDATE employee_module_permissions SET {$column} = ?, updated_at = NOW() WHERE user_id = ? AND module = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$value, $user_id, $module]);
            } else {
                // Create new with defaults
                $can_view = ($permission_type === 'read' && $value) ? 1 : 0;
                $can_edit = (in_array($permission_type, ['write', 'share']) && $value) ? 1 : 0;
                $can_delete = (in_array($permission_type, ['delete', 'manage']) && $value) ? 1 : 0;
                
                $stmt = $pdo->prepare("INSERT INTO employee_module_permissions (user_id, module, can_view, can_edit, can_delete, access_via, created_at) VALUES (?, ?, ?, ?, ?, 'Individual', NOW())");
                $stmt->execute([$user_id, $module, $can_view, $can_edit, $can_delete]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Permission updated']);
            exit;
            
        case 'add_user_access':
            $user_id = $_POST['user_id'] ?? 0;
            $module = $_POST['module'] ?? '';
            $access_level = $_POST['access_level'] ?? 'view';
            
            // Map Mode access levels: View, Query (edit), Manage
            $can_view = in_array($access_level, ['view', 'query', 'manage']) ? 1 : 0;
            $can_edit = in_array($access_level, ['query', 'manage']) ? 1 : 0;
            $can_delete = $access_level === 'manage' ? 1 : 0;
            
            $check = $pdo->prepare("SELECT id FROM employee_module_permissions WHERE user_id = ? AND module = ?");
            $check->execute([$user_id, $module]);
            
            if ($check->fetch()) {
                $stmt = $pdo->prepare("UPDATE employee_module_permissions SET can_view = ?, can_edit = ?, can_delete = ?, access_via = 'Individual', updated_at = NOW() WHERE user_id = ? AND module = ?");
                $stmt->execute([$can_view, $can_edit, $can_delete, $user_id, $module]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO employee_module_permissions (user_id, module, can_view, can_edit, can_delete, access_via, created_at) VALUES (?, ?, ?, ?, ?, 'Individual', NOW())");
                $stmt->execute([$user_id, $module, $can_view, $can_edit, $can_delete]);
            }
            echo json_encode(['success' => true, 'message' => 'User access added']);
            exit;
            
        case 'remove_user_access':
            $user_id = $_POST['user_id'] ?? 0;
            $module = $_POST['module'] ?? '';
            
            $stmt = $pdo->prepare("DELETE FROM employee_module_permissions WHERE user_id = ? AND module = ? AND access_via = 'Individual'");
            $stmt->execute([$user_id, $module]);
            echo json_encode(['success' => true, 'message' => 'User access removed']);
            exit;
            
        case 'update_user_access':
            $user_id = $_POST['user_id'] ?? 0;
            $module = $_POST['module'] ?? '';
            $access_level = $_POST['access_level'] ?? 'view';
            
            // Map Mode access levels: View, Query (edit), Manage
            $can_view = in_array($access_level, ['view', 'query', 'manage']) ? 1 : 0;
            $can_edit = in_array($access_level, ['query', 'manage']) ? 1 : 0;
            $can_delete = $access_level === 'manage' ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE employee_module_permissions SET can_view = ?, can_edit = ?, can_delete = ?, updated_at = NOW() WHERE user_id = ? AND module = ?");
            $stmt->execute([$can_view, $can_edit, $can_delete, $user_id, $module]);
            echo json_encode(['success' => true, 'message' => 'Access updated']);
            exit;

        case 'get_role_pages':
            $role = $_POST['role'] ?? '';
            if (!$role) {
                echo json_encode(['success' => false, 'message' => 'Role is required']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT module FROM role_module_permissions WHERE role = ? AND module LIKE 'page:%'");
            $stmt->execute([$role]);
            $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $pages = array_map(function($m) { return str_replace('page:', '', $m); }, $modules);
            echo json_encode(['success' => true, 'pages' => $pages]);
            exit;

        case 'update_role_pages':
            $role = $_POST['role'] ?? '';
            $pages = $_POST['pages'] ?? [];
            if (!$role) {
                echo json_encode(['success' => false, 'message' => 'Role is required']);
                exit;
            }
            if (!is_array($pages)) {
                $pages = [];
            }
            // Remove existing page permissions for this role
            $del = $pdo->prepare("DELETE FROM role_module_permissions WHERE role = ? AND module LIKE 'page:%'");
            $del->execute([$role]);
            // Insert new selections
            if (!empty($pages)) {
                $ins = $pdo->prepare("INSERT INTO role_module_permissions (role, module, can_view, can_edit, can_delete, created_at, updated_at) VALUES (?, ?, 1, 0, 0, NOW(), NOW())");
                foreach ($pages as $p) {
                    $ins->execute([$role, 'page:' . $p]);
                }
            }
            echo json_encode(['success' => true, 'message' => 'Page access updated']);
            exit;
            
        case 'get_module_employees':
            $module = $_POST['module'] ?? '';
            $search = $_POST['search'] ?? '';
            $filter = $_POST['filter'] ?? 'all';
            $page = $_POST['page'] ?? 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;
            
            // Get role module configuration
            $role_modules = [
                'hr_admin' => ['hr_admin'],
                'developer' => ['developer']
            ];
            
            $module_roles = $role_modules[$module] ?? [];
            if (empty($module_roles)) {
                echo json_encode(['success' => false, 'message' => 'Invalid module']);
                exit;
            }
            
            // Build query with role filter
            $placeholders = str_repeat('?,', count($module_roles) - 1) . '?';
            $where = "WHERE u.status = 'active' AND u.role IN ($placeholders)";
            $params = $module_roles;
            
            // Apply search filter
            if ($search) {
                $where .= " AND (u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                $search_param = "%{$search}%";
                $params = array_merge($params, [$search_param, $search_param, $search_param]);
            }
            
            // Get total count
            $count_sql = "SELECT COUNT(*) FROM users u $where";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetchColumn();
            
            // Get users with activity tracking
            $sql = "SELECT u.id, u.name, u.username, u.email, u.role, u.department, u.last_login,
                           u.employee_id, e.employee_no, e.employee_type, e.post,
                           CASE 
                               WHEN u.last_login IS NULL THEN 0
                               WHEN TIMESTAMPDIFF(MINUTE, u.last_login, NOW()) < 5 THEN 1
                               ELSE 0
                           END as is_active,
                           CASE 
                               WHEN u.last_login IS NULL THEN 0
                               WHEN TIMESTAMPDIFF(MINUTE, u.last_login, NOW()) < 5 THEN TIMESTAMPDIFF(MINUTE, u.last_login, NOW())
                               ELSE 0
                           END as active_minutes
                    FROM users u
                    LEFT JOIN employees e ON u.employee_id = e.id
                    $where
                    ORDER BY is_active DESC, u.name ASC
                    LIMIT ? OFFSET ?";
            
            $params[] = $per_page;
            $params[] = $offset;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format user data with permissions summary
            foreach ($users as &$user) {
                // Get permission summary across all modules
                $perm_stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT module) as total_modules,
                           SUM(can_view) as can_view_count,
                           SUM(can_edit) as can_edit_count,
                           SUM(can_delete) as can_delete_count
                    FROM employee_module_permissions
                    WHERE user_id = ?
                ");
                $perm_stmt->execute([$user['id']]);
                $perms = $perm_stmt->fetch(PDO::FETCH_ASSOC);
                
                $user['permissions'] = [
                    'total_modules' => $perms['total_modules'] ?? 0,
                    'can_view' => $perms['can_view_count'] ?? 0,
                    'can_edit' => $perms['can_edit_count'] ?? 0,
                    'can_delete' => $perms['can_delete_count'] ?? 0
                ];
                
                // Format active time
                if ($user['is_active']) {
                    $hours = floor($user['active_minutes'] / 60);
                    $minutes = $user['active_minutes'] % 60;
                    $user['active_time'] = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                } else {
                    $user['active_time'] = 'Offline';
                }
            }
            
            echo json_encode([
                'success' => true,
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page
            ]);
            exit;
            
        case 'get_users_list':
            $module = $_POST['module'] ?? '';
            $search = $_POST['search'] ?? '';
            $filter = $_POST['filter'] ?? 'all';
            $page = $_POST['page'] ?? 1;
            $per_page = $_POST['per_page'] ?? 20;
            $offset = ($page - 1) * $per_page;
            
            // Get all users (not filtered by module role for user permissions tab)
            $where = "WHERE u.status = 'active'";
            $params = [];
            
            // Apply search filter
            if ($search) {
                $where .= " AND (u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                $search_param = "%{$search}%";
                $params = array_merge($params, [$search_param, $search_param, $search_param]);
            }
            
            // Get total count
            $count_sql = "SELECT COUNT(*) FROM users u $where";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetchColumn();
            
            // Get users with permissions
            $sql = "SELECT u.id, u.name, u.username, u.email, u.role,
                           COALESCE(emp.can_view, 0) as can_view,
                           COALESCE(emp.can_edit, 0) as can_edit,
                           COALESCE(emp.can_delete, 0) as can_delete,
                           COALESCE(emp.access_via, 'Default') as access_via
                    FROM users u
                    LEFT JOIN employee_module_permissions emp ON u.id = emp.user_id AND emp.module = ?
                    $where
                    ORDER BY u.name ASC
                    LIMIT ? OFFSET ?";
            
            $params = array_merge([$module], $params, [$per_page, $offset]);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format user data with access levels
            foreach ($users as &$user) {
                // Determine access level based on permissions
                if ($user['can_delete']) {
                    $user['access_level'] = 'Manage';
                } elseif ($user['can_edit']) {
                    $user['access_level'] = 'Query';
                } elseif ($user['can_view']) {
                    $user['access_level'] = 'View';
                } else {
                    $user['access_level'] = 'None';
                }
            }
            
            echo json_encode([
                'success' => true,
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page
            ]);
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Get default access for selected module
$default_access = 'none';
if ($selected_module) {
    $stmt = $pdo->prepare("SELECT access_level FROM module_default_access WHERE module = ?");
    $stmt->execute([$selected_module]);
    $default_access = $stmt->fetchColumn() ?: 'none';
}

// Get pagination
$current_page = $_GET['page'] ?? 1;
$per_page = 14;
$search = $_GET['search'] ?? '';

// Debug: Check if variables are set
if (!isset($role_modules) || empty($role_modules)) {
    echo '<div class="alert alert-danger">Error: Role modules not defined</div>';
    $role_modules = [];
}
if (!isset($selected_module) || empty($selected_module)) {
    $selected_module = array_key_first($role_modules) ?: '';
}

// Ensure $pdo is available
if (!isset($pdo) || !$pdo) {
    try {
        $pdo = get_db_connection();
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $pdo = null;
    }
}
?>
<style>
/* Hide the main header with black background - placed early for priority */
body .main-content .header,
body .main-content header.header,
#mainContent .header,
#mainContent header.header,
header.header {
    display: none !important;
    visibility: hidden !important;
    height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: hidden !important;
    opacity: 0 !important;
}

/* Hide page-header if it exists */
.page-header {
    display: none !important;
}
</style>

<!-- DEBUG: Permissions page content starting -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">

            <?php if ($db_error): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Database Error:</strong> <?php echo htmlspecialchars($db_error); ?>
                    <br><small>The page will still load, but some features may not work.</small>
                </div>
            <?php endif; ?>
            
            <?php if (empty($role_modules)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No role modules configured. Please check the system configuration.
                </div>
            <?php endif; ?>

            <!-- Role Module Selection -->
            <div class="row mb-4">
                <?php if (empty($role_modules)): ?>
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No role modules are configured. Please check the system configuration.
                        </div>
                    </div>
                <?php else: ?>
                <?php foreach ($role_modules as $module_key => $module): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 module-card <?php echo $selected_module === $module_key ? 'border-primary shadow-sm' : ''; ?>" 
                             style="cursor: pointer; transition: all 0.3s;"
                             data-module="<?php echo $module_key; ?>">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="<?php echo $module['icon']; ?> fa-3x" style="color: <?php echo $module['color']; ?>;"></i>
                                </div>
                                <h5 class="card-title mb-2"><?php echo $module['name']; ?></h5>
                                <p class="text-muted small mb-3"><?php echo $module['description']; ?></p>
                                <div class="d-flex justify-content-center align-items-center">
                                    <?php 
                                    // Count users in this module
                                    $user_count = 0;
                                    try {
                                        if (isset($pdo) && $pdo) {
                                            $role_placeholders = str_repeat('?,', count($module['roles']) - 1) . '?';
                                            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role IN ($role_placeholders) AND status = 'active'");
                                            if ($count_stmt) {
                                                $count_stmt->execute($module['roles']);
                                                $user_count = (int)$count_stmt->fetchColumn();
                                            }
                                        }
                                    } catch (Exception $e) {
                                        $user_count = 0;
                                        // Silently fail - don't break the page
                                    }
                                    ?>
                                    <span class="badge bg-primary rounded-pill">
                                        <i class="fas fa-users me-1"></i><?php echo $user_count; ?> Members
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($selected_module) && isset($role_modules[$selected_module])): ?>
                <?php 
                $module_info = $role_modules[$selected_module];
                $module_name = $module_info['name'];
                $module_color = $module_info['color'];
                ?>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $current_tab === 'role_permissions' ? 'active' : ''; ?>" 
                                id="role-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#role-permissions" 
                                type="button" 
                                role="tab">
                            <i class="fas fa-user-tag me-2"></i>Role Permissions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $current_tab === 'user_permissions' ? 'active' : ''; ?>" 
                                id="user-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#user-permissions" 
                                type="button" 
                                role="tab">
                            <i class="fas fa-users me-2"></i>User Permissions
                        </button>
                    </li>
                </ul>

                <!-- Employees List -->
                <div class="card">
                    <div class="card-header" style="background-color: <?php echo $module_color; ?>20; border-left: 4px solid <?php echo $module_color; ?>;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="<?php echo $module_info['icon']; ?> me-2" style="color: <?php echo $module_color; ?>;"></i>
                                    <?php echo $module_name; ?> - Employee List
                                </h5>
                                <small class="text-muted">View and manage permissions for employees in this module</small>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-primary" id="refreshEmployeesBtn">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search and Filters -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="employeeSearchInput" 
                                           placeholder="Search employees...">
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item filter-option active" href="#" data-filter="all">All Employees</a></li>
                                        <li><a class="dropdown-item filter-option" href="#" data-filter="active">Active Now</a></li>
                                        <li><a class="dropdown-item filter-option" href="#" data-filter="offline">Offline</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Employees Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>EMPLOYEE</th>
                                        <th>ROLE / DEPARTMENT</th>
                                        <th>PERMISSIONS</th>
                                        <th>STATUS</th>
                                        <th>ACTIVE TIME</th>
                                        <th class="text-end">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="employeesTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="text-muted mt-2">Loading employees...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <span class="text-muted" id="paginationInfo">Loading...</span>
                            </div>
                            <nav>
                                <ul class="pagination mb-0" id="paginationNav">
                                    <!-- Will be populated by JavaScript -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Page Access by Role -->
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">Page Access by Role</h5>
                                <p class="text-muted small mb-0">Toggle which pages each role can open. Unchecked pages stay hidden/blocked.</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <select class="form-select form-select-sm" id="pageRoleSelect" style="min-width: 200px;">
                                    <?php foreach ($all_roles as $r): ?>
                                        <option value="<?php echo htmlspecialchars($r); ?>"><?php echo htmlspecialchars(strtoupper($r)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary btn-sm" id="savePageAccessBtn">
                                    <i class="fas fa-save me-1"></i>Save
                                </button>
                            </div>
                        </div>

                        <div id="pageAccessList" class="row g-2">
                            <!-- populated by JS -->
                        </div>
                    </div>
                </div>

                <!-- Tab Content (Hidden for now, can be shown later) -->
                <div class="tab-content" style="display: none;">
                    <!-- Role Permissions Tab -->
                    <div class="tab-pane fade <?php echo $current_tab === 'role_permissions' ? 'show active' : ''; ?>" 
                         id="role-permissions" 
                         role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Role-Based Access Control</h5>
                                <p class="text-muted">Assign permissions to roles. All users with a specific role will inherit these permissions.</p>
                                
                                <!-- Role Selection -->
                                <div class="row mb-4">
                                    <?php
                                    $role_categories = [
                                        'HR / Admin' => ['hr_admin'],
                                        'Developer' => ['developer']
                                    ];
                                    
                                    $roles_query = "SELECT DISTINCT role FROM users WHERE role IS NOT NULL ORDER BY role";
                                    $roles_result = $pdo->query($roles_query);
                                    $all_roles = $roles_result->fetchAll(PDO::FETCH_COLUMN);
                                    
                                    foreach ($role_categories as $category => $roles):
                                        $available_roles = array_intersect($roles, $all_roles);
                                        if (empty($available_roles)) continue;
                                    ?>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold text-uppercase small text-muted"><?php echo $category; ?></label>
                                            <div class="list-group">
                                                <?php foreach ($available_roles as $role): ?>
                                                    <a href="?page=permissions&module=<?php echo urlencode($selected_module); ?>&tab=role_permissions&role=<?php echo urlencode($role); ?>" 
                                                       class="list-group-item list-group-item-action <?php echo ($_GET['role'] ?? '') === $role ? 'active' : ''; ?>">
                                                        <i class="fas fa-user me-2"></i>
                                                        <?php echo ucfirst(str_replace('_', ' ', $role)); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if (isset($_GET['role'])): ?>
                                    <?php
                                    $selected_role = $_GET['role'];
                                    $stmt = $pdo->prepare("SELECT module, can_view, can_edit, can_delete FROM role_module_permissions WHERE role = ? AND module = ?");
                                    $stmt->execute([$selected_role, $selected_module]);
                                    $role_permission = $stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="mb-3">Permissions for: <strong><?php echo ucfirst(str_replace('_', ' ', $selected_role)); ?></strong></h6>
                                            
                                            <?php if ($role_permission): ?>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="roleCanView" <?php echo $role_permission['can_view'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="roleCanView">Can View</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="roleCanEdit" <?php echo $role_permission['can_edit'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="roleCanEdit">Can Edit</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="roleCanDelete" <?php echo $role_permission['can_delete'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="roleCanDelete">Can Delete</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <button type="button" class="btn btn-primary mt-3" id="saveRolePermissionsBtn">
                                                    <i class="fas fa-save me-1"></i>Save Role Permissions
                                                </button>
                                            <?php else: ?>
                                                <p class="text-muted">No permissions set for this role. Click below to assign permissions.</p>
                                                <button type="button" class="btn btn-success" id="assignRolePermissionsBtn">
                                                    <i class="fas fa-plus me-1"></i>Assign Permissions to Role
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Select a role from above to manage its permissions for this module.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- User Permissions Tab -->
                    <div class="tab-pane fade <?php echo $current_tab === 'user_permissions' ? 'show active' : ''; ?>" 
                         id="user-permissions" 
                         role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Connection Access</h5>
                            </div>
                            <div class="card-body">
                                <!-- Default Access Section -->
                                <div class="row align-items-center mb-4 pb-4 border-bottom">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold mb-2">Default org-wide access:</label>
                                        <select class="form-select" id="defaultAccessSelect">
                                            <option value="none" <?php echo $default_access === 'none' ? 'selected' : ''; ?>>None</option>
                                            <option value="view" <?php echo $default_access === 'view' ? 'selected' : ''; ?>>View</option>
                                            <option value="query" <?php echo $default_access === 'query' ? 'selected' : ''; ?>>Query</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <p class="text-muted mb-0 mt-3" id="defaultAccessDescription">
                                            <?php 
                                            $descriptions = [
                                                'none' => "Workspace members have no default access to this module.",
                                                'view' => "Workspace members can view Reports created using this module by default. This includes the ability to use parameters and run Reports.",
                                                'query' => "Workspace members can write & modify queries against this module by default. Only users with this permission can create and edit Reports using this module."
                                            ];
                                            echo $descriptions[$default_access] ?? $descriptions['none'];
                                            ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Search and Actions -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="fas fa-search text-muted"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="userSearchInput" 
                                                   placeholder="Search..." 
                                                   value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="btn-group me-2">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" id="viewFilterBtn">
                                                <i class="fas fa-filter me-1"></i>View All Members
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item filter-option active" href="#" data-filter="all">All Members</a></li>
                                                <li><a class="dropdown-item filter-option" href="#" data-filter="individuals">Individual Users</a></li>
                                                <li><a class="dropdown-item filter-option" href="#" data-filter="groups">Groups</a></li>
                                            </ul>
                                        </div>
                                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#checkAccessModal">
                                            <i class="fas fa-search me-1"></i>Check Access
                                        </button>
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                            <i class="fas fa-user-plus me-1"></i>Add Members
                                        </button>
                                    </div>
                                </div>

                                <!-- Additional Access Table -->
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>USER</th>
                                                <th>EMAIL</th>
                                                <th>ACCESS</th>
                                                <th>ACCESS VIA</th>
                                                <th class="text-end"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="usersTableBody">
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <p class="text-muted mt-2">Loading users...</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div>
                                        <span class="text-muted" id="paginationInfo">Loading...</span>
                                    </div>
                                    <nav>
                                        <ul class="pagination mb-0" id="paginationNav">
                                            <!-- Will be populated by JavaScript -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please select a module above to manage permissions.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add members to connection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <label class="form-label fw-bold mb-2">Add groups and individuals</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               id="addUserSearch" 
                               placeholder="Search...">
                    </div>
                </div>
                
                <!-- Selected Members List -->
                <div id="selectedMembersList" class="mb-4" style="display: none;">
                    <h6 class="mb-3">Selected Members</h6>
                    <div id="selectedMembersContainer">
                        <!-- Selected members will appear here -->
                    </div>
                </div>
                
                <!-- Available Users List -->
                <div class="list-group" id="addUserList" style="max-height: 400px; overflow-y: auto;">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="addSelectedMembersBtn" disabled>
                    <i class="fas fa-user-plus me-1"></i>Add members
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Check Access Modal -->
<div class="modal fade" id="checkAccessModal" tabindex="-1" aria-labelledby="checkAccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkAccessModalLabel">Check Access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Check effective permissions for any user when this module is shared with them.</p>
                
                <div class="input-group mb-4">
                    <input type="text" 
                           class="form-control" 
                           id="checkAccessEmail" 
                           placeholder="Enter user email (e.g., user@example.com)"
                           value="">
                    <button class="btn btn-primary" type="button" id="checkAccessBtn">
                        <i class="fas fa-search me-1"></i>Check user access
                    </button>
                </div>
                
                <div id="checkAccessResults" style="display: none;">
                    <div class="mb-3">
                        <h6 class="mb-1">User: <span id="checkAccessUserName"></span></h6>
                        <small class="text-muted">Module: <span id="checkAccessModuleName"></span></small>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Permissions type</th>
                                    <th class="text-center">
                                        <i class="fas fa-eye"></i><br>Read
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-pencil-alt"></i><br>Write
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-share-alt"></i><br>Share
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-trash"></i><br>Delete
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-cog"></i><br>Manage
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold">Module permissions</td>
                                    <td class="text-center" id="roleRead">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="roleWrite">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="roleShare">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="roleDelete">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="roleManage">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Individual permissions</td>
                                    <td class="text-center" id="indivRead">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="indivWrite">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="indivShare">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="indivDelete">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="indivManage">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                </tr>
                                <tr class="table-primary">
                                    <td class="fw-bold">Effective permissions</td>
                                    <td class="text-center" id="effectiveRead">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="effectiveWrite">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="effectiveShare">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="effectiveDelete">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                    <td class="text-center" id="effectiveManage">
                                        <i class="fas fa-check text-success"></i>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="editPermissionsBtn">
                            <i class="fas fa-edit me-1"></i>Edit Permissions
                        </button>
                    </div>
                </div>
                
                <div id="checkAccessError" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Access Modal -->
<div class="modal fade" id="editAccessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editUserId">
                <input type="hidden" id="editModule" value="<?php echo htmlspecialchars($selected_module); ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Access Level</label>
                    <div class="list-group">
                        <label class="list-group-item d-flex align-items-start border-0 p-3">
                            <input class="form-check-input me-3 mt-1" type="radio" name="editAccessLevel" value="view">
                            <div>
                                <div class="fw-bold">View</div>
                                <small class="text-muted">View data from this module</small>
                            </div>
                        </label>
                        <label class="list-group-item d-flex align-items-start border-0 p-3">
                            <input class="form-check-input me-3 mt-1" type="radio" name="editAccessLevel" value="query">
                            <div>
                                <div class="fw-bold">Query</div>
                                <small class="text-muted">View data and query this module</small>
                            </div>
                        </label>
                        <label class="list-group-item d-flex align-items-start border-0 p-3">
                            <input class="form-check-input me-3 mt-1" type="radio" name="editAccessLevel" value="manage">
                            <div>
                                <div class="fw-bold">Manage</div>
                                <small class="text-muted">Query this module and edit configuration</small>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAccessBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>


/* User Avatar Styles */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    margin-right: 12px;
}

.user-info {
    display: flex;
    align-items: center;
}

.user-name {
    font-weight: 500;
    color: #171717;
}

.access-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
}

.access-badge.view {
    background-color: #dbeafe;
    color: #1e40af;
}

.access-badge.query {
    background-color: #fef3c7;
    color: #92400e;
}

.access-badge.edit {
    background-color: #fef3c7;
    color: #92400e;
}

.access-badge.manage {
    background-color: #d1fae5;
    color: #065f46;
}

.access-badge.none {
    background-color: #f3f4f6;
    color: #6b7280;
}

.access-via-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
    font-size: 0.8125rem;
    background-color: #f3f4f6;
    color: #4b5563;
}

.nav-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: #6b7280;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    color: #22c55e;
    border-bottom-color: #22c55e;
    background-color: transparent;
}

.nav-tabs .nav-link:hover {
    border-bottom-color: #d1d5db;
    color: #374151;
}

/* Module Card Styles */
.module-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.module-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-color: #e5e7eb;
}

.module-card.border-primary {
    border-color: #0ea5e9 !important;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
}

/* Active Status Indicator */
.badge.bg-success i.fa-circle {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectedModule = '<?php echo htmlspecialchars($selected_module); ?>';
    const currentTab = '<?php echo htmlspecialchars($current_tab); ?>';
    let currentPage = 1;
    let currentSearch = '';
    let currentFilter = 'all';
    
    // Role modules configuration
    const roleModules = <?php echo json_encode($role_modules); ?>;
    const pageCatalog = <?php echo json_encode($page_catalog); ?>;
    const allRoles = <?php echo json_encode($all_roles); ?>;
    
    // Module card selection
    document.querySelectorAll('.module-card').forEach(card => {
        card.addEventListener('click', function() {
            const module = this.dataset.module;
            window.location.href = `?page=permissions&module=${module}`;
        });
    });
    
    // Load employees for selected module
    function loadEmployees() {
        if (!selectedModule) return;
        
        const formData = new FormData();
        formData.append('action', 'get_module_employees');
        formData.append('module', selectedModule);
        formData.append('search', currentSearch);
        formData.append('filter', currentFilter);
        formData.append('page', currentPage);
        
        fetch('?page=permissions&module=' + selectedModule, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderEmployeesTable(data.users);
                renderEmployeesPagination(data.total, data.page, data.per_page);
                updateEmployeesPaginationInfo(data.total, data.page, data.per_page);
            }
        })
        .catch(error => {
            console.error('Error loading employees:', error);
        });
    }
    
    // Render employees table
    function renderEmployeesTable(employees) {
        const tbody = document.getElementById('employeesTableBody');
        if (!tbody) return;
        
        if (employees.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No employees found</td></tr>';
            return;
        }
        
        tbody.innerHTML = employees.map(emp => {
            const initials = emp.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const avatarColor = getAvatarColor(emp.id);
            const isActive = emp.is_active == 1;
            const statusBadge = isActive 
                ? '<span class="badge bg-success"><i class="fas fa-circle me-1"></i>Active</span>'
                : '<span class="badge bg-secondary"><i class="fas fa-circle me-1"></i>Offline</span>';
            
            // Permission summary
            const perms = emp.permissions || {};
            const permSummary = perms.total_modules > 0 
                ? `${perms.total_modules} modules (${perms.can_view} view, ${perms.can_edit} edit, ${perms.can_delete} manage)`
                : 'No permissions assigned';
            
            return `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3" style="background-color: ${avatarColor};">
                                ${initials}
                            </div>
                            <div>
                                <div class="fw-bold">${escapeHtml(emp.name)}</div>
                                <small class="text-muted">${escapeHtml(emp.email)}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>
                            <span class="badge bg-info">${escapeHtml(emp.role || 'N/A')}</span>
                            ${emp.department ? `<br><small class="text-muted">${escapeHtml(emp.department)}</small>` : ''}
                            ${emp.post ? `<br><small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>${escapeHtml(emp.post)}</small>` : ''}
                        </div>
                    </td>
                    <td>
                        <small class="text-muted">${permSummary}</small>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <small class="${isActive ? 'text-success fw-bold' : 'text-muted'}">
                            ${isActive ? '<i class="fas fa-clock me-1"></i>' : ''}
                            ${escapeHtml(emp.active_time || 'Offline')}
                        </small>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary edit-employee-permissions-btn" 
                                data-user-id="${emp.id}" 
                                data-user-name="${escapeHtml(emp.name)}"
                                title="Edit Permissions">
                            <i class="fas fa-edit me-1"></i>Edit
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
        
        // Attach edit button handlers
        document.querySelectorAll('.edit-employee-permissions-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const userName = this.dataset.userName;
                // Open permissions edit modal or navigate to user permissions
                window.location.href = `?page=permissions&module=${selectedModule}&user_id=${userId}&action=edit`;
            });
        });
    }
    
    // Search employees
    document.getElementById('employeeSearchInput')?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = this.value;
            currentPage = 1;
            loadEmployees();
        }, 300);
    });
    
    // Filter employees
    document.querySelectorAll('.filter-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            currentFilter = this.dataset.filter;
            
            document.querySelectorAll('.filter-option').forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            
            currentPage = 1;
            loadEmployees();
        });
    });
    
    // Refresh button
    document.getElementById('refreshEmployeesBtn')?.addEventListener('click', function() {
        loadEmployees();
    });
    
    // Auto-refresh every 30 seconds for real-time updates
    if (selectedModule) {
        loadEmployees();
        setInterval(() => {
            loadEmployees();
        }, 30000); // Refresh every 30 seconds
    }

    // ---------- Page Access by Role ----------
    const pageRoleSelect = document.getElementById('pageRoleSelect');
    const pageAccessList = document.getElementById('pageAccessList');
    if (pageRoleSelect && pageAccessList) {
        // Initialize options if empty
        if (pageRoleSelect.options.length === 0) {
            allRoles.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r;
                opt.textContent = r.toUpperCase();
                pageRoleSelect.appendChild(opt);
            });
        }

        function renderPageChecklist(selectedPages = []) {
            if (!pageAccessList) return;
            if (!pageCatalog || pageCatalog.length === 0) {
                pageAccessList.innerHTML = '<div class="text-muted small">No pages found.</div>';
                return;
            }
            const categories = {};
            pageCatalog.forEach(p => {
                if (!categories[p.category]) categories[p.category] = [];
                categories[p.category].push(p);
            });
            let html = '';
            Object.keys(categories).forEach(cat => {
                html += `<div class="col-12 mt-2"><div class="fw-semibold text-muted small">${cat}</div></div>`;
                categories[cat].forEach(p => {
                    const checked = selectedPages.includes(p.key) ? 'checked' : '';
                    html += `
                        <div class="col-md-4 col-sm-6">
                            <div class="form-check">
                                <input class="form-check-input page-check" type="checkbox" value="${p.key}" id="page-${p.key}" ${checked}>
                                <label class="form-check-label" for="page-${p.key}">${p.name}</label>
                            </div>
                        </div>
                    `;
                });
            });
            pageAccessList.innerHTML = html;
        }

        function loadRolePages(role) {
            const fd = new FormData();
            fd.append('action', 'get_role_pages');
            fd.append('role', role);
            fetch('?page=permissions', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderPageChecklist(data.pages || []);
                    }
                })
                .catch(err => console.error(err));
        }

        pageRoleSelect.addEventListener('change', function() {
            loadRolePages(this.value);
        });

        document.getElementById('savePageAccessBtn')?.addEventListener('click', function() {
            const role = pageRoleSelect.value;
            const selected = Array.from(document.querySelectorAll('.page-check:checked')).map(cb => cb.value);
            const fd = new FormData();
            fd.append('action', 'update_role_pages');
            fd.append('role', role);
            selected.forEach(p => fd.append('pages[]', p));

            fetch('?page=permissions', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const toast = document.createElement('div');
                        toast.className = 'alert alert-success py-2 px-3 mt-2';
                        toast.textContent = 'Page access saved';
                        pageAccessList.prepend(toast);
                        setTimeout(() => toast.remove(), 1500);
                    }
                })
                .catch(err => console.error(err));
        });

        // initial load
        loadRolePages(pageRoleSelect.value);
    }
    
    // Helper functions
    let searchTimeout;
    
    function renderEmployeesPagination(total, page, perPage) {
        const totalPages = Math.ceil(total / perPage);
        const nav = document.getElementById('paginationNav');
        if (!nav) return;
        
        if (totalPages <= 1) {
            nav.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Previous button
        html += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${page - 1}">Prev</a>
                 </li>`;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
                html += `<li class="page-item ${i === page ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                         </li>`;
            } else if (i === page - 2 || i === page + 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        // Next button
        html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${page + 1}">Next</a>
                 </li>`;
        
        nav.innerHTML = html;
        
        // Attach click handlers
        nav.querySelectorAll('a.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const newPage = parseInt(this.dataset.page);
                if (newPage && newPage !== page) {
                    currentPage = newPage;
                    loadEmployees();
                }
            });
        });
    }
    
    function updateEmployeesPaginationInfo(total, page, perPage) {
        const info = document.getElementById('paginationInfo');
        if (!info) return;
        
        const start = (page - 1) * perPage + 1;
        const end = Math.min(page * perPage, total);
        info.textContent = `Showing ${start} to ${end} of ${total} employees`;
    }
    
    function getAvatarColor(id) {
        const colors = [
            '#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6',
            '#06b6d4', '#10b981', '#6366f1', '#f97316', '#ec4899'
        ];
        return colors[id % colors.length];
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Default access change (matching Mode's structure)
    document.getElementById('defaultAccessSelect')?.addEventListener('change', function() {
        const access = this.value;
        const descriptions = {
            'none': "Workspace members have no default access to this module.",
            'view': "Workspace members can view Reports created using this module by default. This includes the ability to use parameters and run Reports.",
            'query': "Workspace members can write & modify queries against this module by default. Only users with this permission can create and edit Reports using this module."
        };
        
        document.getElementById('defaultAccessDescription').textContent = descriptions[access] || descriptions['none'];
        
        // Save default access
        const formData = new FormData();
        formData.append('action', 'set_default_access');
        formData.append('module', selectedModule);
        formData.append('default_access', access);
        
        fetch('?page=permissions&module=' + selectedModule, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadUsers(); // Reload users table
            }
        });
    });
    
    // Search functionality
    let searchTimeout;
    document.getElementById('userSearchInput')?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = this.value;
            currentPage = 1;
            loadUsers();
        }, 500);
    });
    
    // Load users function
    function loadUsers() {
        if (!selectedModule) return;
        
        const formData = new FormData();
        formData.append('action', 'get_users_list');
        formData.append('module', selectedModule);
        formData.append('search', currentSearch);
        formData.append('filter', currentFilter);
        formData.append('page', currentPage);
        
        fetch('?page=permissions&module=' + selectedModule, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderUsersTable(data.users);
                renderPagination(data.total, data.page, data.per_page);
                updatePaginationInfo(data.total, data.page, data.per_page);
            }
        });
    }
    
    // Render users table
    function renderUsersTable(users) {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) return;
        
        if (users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">No users found</td></tr>';
            return;
        }
        
        tbody.innerHTML = users.map(user => {
            const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const avatarColor = getAvatarColor(user.id);
            
            return `
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar" style="background-color: ${avatarColor}">
                                ${initials}
                            </div>
                            <div>
                                <div class="user-name">${escapeHtml(user.name)}</div>
                                <small class="text-muted">${escapeHtml(user.username)}</small>
                            </div>
                        </div>
                    </td>
                    <td>${escapeHtml(user.email)}</td>
                                                <td>
                                                    <span class="access-badge ${user.access_level.toLowerCase()}">${user.access_level}</span>
                                                </td>
                                                <td>
                                                    <span class="access-via-badge">${user.access_via}</span>
                                                </td>
                                                <td class="text-end">
                                                    ${user.access_via === 'Individual' ? `
                                                        <button class="btn btn-sm btn-link text-primary edit-access-btn p-0" 
                                                                data-user-id="${user.id}" 
                                                                data-access="${user.access_level.toLowerCase()}">
                                                            <i class="fas fa-chevron-right"></i>
                                                        </button>
                                                    ` : user.access_via === 'Admin' ? `
                                                        <span class="text-muted small">Cannot modify</span>
                                                    ` : `
                                                        <button class="btn btn-sm btn-link text-primary edit-access-btn p-0" 
                                                                data-user-id="${user.id}" 
                                                                data-access="${user.access_level.toLowerCase()}">
                                                            <i class="fas fa-chevron-right"></i>
                                                        </button>
                                                    `}
                                                </td>
                </tr>
            `;
        }).join('');
        
        // Attach event listeners (matching Mode's design)
        document.querySelectorAll('.edit-access-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.dataset.userId;
                let access = this.dataset.access;
                
                // Map access levels: 'edit' -> 'query' for Mode compatibility
                if (access === 'edit') {
                    access = 'query';
                }
                
                document.getElementById('editUserId').value = userId;
                
                // Set radio button (matching Mode's radio button interface)
                const radio = document.querySelector(`input[name="editAccessLevel"][value="${access}"]`);
                if (radio) {
                    radio.checked = true;
                } else {
                    // Default to view if not found
                    const defaultRadio = document.querySelector('input[name="editAccessLevel"][value="view"]');
                    if (defaultRadio) defaultRadio.checked = true;
                }
                
                new bootstrap.Modal(document.getElementById('editAccessModal')).show();
            });
        });
        
        document.querySelectorAll('.remove-access-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Remove access for this user?')) return;
                const userId = this.dataset.userId;
                
                const formData = new FormData();
                formData.append('action', 'remove_user_access');
                formData.append('user_id', userId);
                formData.append('module', selectedModule);
                
                fetch('?page=permissions&module=' + selectedModule, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadUsers();
                    }
                });
            });
        });
    }
    
    // Render pagination
    function renderPagination(total, page, perPage) {
        const totalPages = Math.ceil(total / perPage);
        const nav = document.getElementById('paginationNav');
        if (!nav) return;
        
        if (totalPages <= 1) {
            nav.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Previous button
        html += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${page - 1}">Prev</a>
                 </li>`;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
                html += `<li class="page-item ${i === page ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                         </li>`;
            } else if (i === page - 2 || i === page + 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        // Next button
        html += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${page + 1}">Next</a>
                 </li>`;
        
        nav.innerHTML = html;
        
        // Attach click handlers
        nav.querySelectorAll('a.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const newPage = parseInt(this.dataset.page);
                if (newPage && newPage !== page) {
                    currentPage = newPage;
                    loadUsers();
                }
            });
        });
    }
    
    // Update pagination info
    function updatePaginationInfo(total, page, perPage) {
        const start = (page - 1) * perPage + 1;
        const end = Math.min(page * perPage, total);
        document.getElementById('paginationInfo').textContent = `Showing ${start}-${end} of ${total}`;
    }
    
    // Save access
    document.getElementById('saveAccessBtn')?.addEventListener('click', function() {
        const userId = document.getElementById('editUserId').value;
        const accessLevel = document.getElementById('editAccessLevel').value;
        
        const formData = new FormData();
        formData.append('action', 'update_user_access');
        formData.append('user_id', userId);
        formData.append('module', selectedModule);
        formData.append('access_level', accessLevel);
        
        fetch('?page=permissions&module=' + selectedModule, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editAccessModal')).hide();
                loadUsers();
            }
        });
    });
    
    // Helper functions
    function getAvatarColor(id) {
        const colors = ['#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#10b981', '#6366f1', '#f97316', '#ec4899'];
        return colors[id % colors.length];
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Role permissions functionality
    document.getElementById('assignRolePermissionsBtn')?.addEventListener('click', function() {
        const role = '<?php echo htmlspecialchars($_GET['role'] ?? ''); ?>';
        if (!role) return;
        
        const formData = new FormData();
        formData.append('action', 'assign_module_to_role');
        formData.append('role', role);
        formData.append('module', selectedModule);
        
        fetch('?page=permissions&module=' + selectedModule, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to assign permissions');
            }
        });
    });
    
    document.getElementById('saveRolePermissionsBtn')?.addEventListener('click', function() {
        const role = '<?php echo htmlspecialchars($_GET['role'] ?? ''); ?>';
        if (!role) return;
        
        const formData = new FormData();
        formData.append('action', 'update_role_permissions');
        formData.append('role', role);
        formData.append('module', selectedModule);
        
        if (document.getElementById('roleCanView').checked) formData.append('can_view', '1');
        if (document.getElementById('roleCanEdit').checked) formData.append('can_edit', '1');
        if (document.getElementById('roleCanDelete').checked) formData.append('can_delete', '1');
        
        fetch('?page=permissions&module=' + selectedModule, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Role permissions saved successfully');
                location.reload();
            } else {
                alert(data.message || 'Failed to save permissions');
            }
        });
    });
    
    // Add Members Modal - Selected members tracking
    let selectedMembers = new Map(); // Map<userId, {user, accessLevel}>
    
    const addUserModal = document.getElementById('addUserModal');
    if (addUserModal) {
        addUserModal.addEventListener('show.bs.modal', function() {
            selectedMembers.clear();
            updateSelectedMembersDisplay();
            loadUsersForAddModal();
        });
        
        addUserModal.addEventListener('hidden.bs.modal', function() {
            selectedMembers.clear();
            document.getElementById('addUserSearch').value = '';
        });
    }
    
    function loadUsersForAddModal() {
        const search = document.getElementById('addUserSearch').value;
        
        const formData = new FormData();
        formData.append('action', 'get_users_list');
        formData.append('module', selectedModule);
        formData.append('search', search);
        formData.append('page', 1);
        formData.append('per_page', 100);
        
        fetch('?page=permissions&module=' + selectedModule, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderAddUserList(data.users);
            }
        });
    }
    
    function renderAddUserList(users) {
        const list = document.getElementById('addUserList');
        if (!list) return;
        
        // Filter out users who already have individual access and already selected
        const usersWithoutAccess = users.filter(u => {
            return u.access_via !== 'Individual' && 
                   u.access_level === 'None' && 
                   !selectedMembers.has(u.id.toString());
        });
        
        if (usersWithoutAccess.length === 0) {
            list.innerHTML = '<div class="text-center text-muted py-4">No available users to add</div>';
            return;
        }
        
        list.innerHTML = usersWithoutAccess.map(user => {
            const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const avatarColor = getAvatarColor(user.id);
            
            return `
                <div class="list-group-item list-group-item-action d-flex align-items-center py-3" data-user-id="${user.id}">
                    <div class="user-avatar me-3" style="background-color: ${avatarColor}; width: 40px; height: 40px;">
                        ${initials}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${escapeHtml(user.name)}</div>
                        <small class="text-muted">${escapeHtml(user.email)}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary add-to-selection-btn" data-user-id="${user.id}">
                        <i class="fas fa-plus me-1"></i>Add
                    </button>
                </div>
            `;
        }).join('');
        
        // Attach event listeners
        document.querySelectorAll('.add-to-selection-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const row = this.closest('.list-group-item');
                const userData = {
                    id: userId,
                    name: row.querySelector('.fw-bold').textContent,
                    email: row.querySelector('.text-muted').textContent,
                    initials: row.querySelector('.user-avatar').textContent.trim()
                };
                
                // Add to selection with default access level "view" (matching Mode)
                selectedMembers.set(userId, {
                    user: userData,
                    accessLevel: 'view'
                });
                
                // Remove from available list
                row.remove();
                
                // Update selected members display
                updateSelectedMembersDisplay();
            });
        });
    }
    
    function updateSelectedMembersDisplay() {
        const container = document.getElementById('selectedMembersContainer');
        const listSection = document.getElementById('selectedMembersList');
        const addBtn = document.getElementById('addSelectedMembersBtn');
        
        if (!container || !listSection || !addBtn) return;
        
        if (selectedMembers.size === 0) {
            listSection.style.display = 'none';
            addBtn.disabled = true;
            return;
        }
        
        listSection.style.display = 'block';
        addBtn.disabled = false;
        
        container.innerHTML = Array.from(selectedMembers.entries()).map(([userId, data]) => {
            const user = data.user;
            const accessLevel = data.accessLevel;
            const avatarColor = getAvatarColor(parseInt(userId));
            
            return `
                <div class="d-flex align-items-center mb-3 p-3 border rounded" data-user-id="${userId}">
                    <div class="user-avatar me-3" style="background-color: ${avatarColor}; width: 40px; height: 40px;">
                        ${user.initials}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${escapeHtml(user.name)}</div>
                        <small class="text-muted">${escapeHtml(user.email)}</small>
                    </div>
                    <div class="dropdown me-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                type="button" 
                                data-bs-toggle="dropdown" 
                                data-user-id="${userId}">
                            ${accessLevel.charAt(0).toUpperCase() + accessLevel.slice(1)}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 280px;">
                            <li>
                                <a class="dropdown-item access-option ${accessLevel === 'view' ? 'active' : ''}" 
                                   href="#" 
                                   data-user-id="${userId}" 
                                   data-access="view">
                                    <div class="d-flex align-items-start">
                                        <div class="form-check me-3 mt-1">
                                            <input class="form-check-input" type="radio" name="access_${userId}" ${accessLevel === 'view' ? 'checked' : ''}>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold mb-1">View</div>
                                            <small class="text-muted d-block">View data from this module</small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item access-option ${accessLevel === 'query' ? 'active' : ''}" 
                                   href="#" 
                                   data-user-id="${userId}" 
                                   data-access="query">
                                    <div class="d-flex align-items-start">
                                        <div class="form-check me-3 mt-1">
                                            <input class="form-check-input" type="radio" name="access_${userId}" ${accessLevel === 'query' ? 'checked' : ''}>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold mb-1">Query</div>
                                            <small class="text-muted d-block">View data and query this module</small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item access-option ${accessLevel === 'manage' ? 'active' : ''}" 
                                   href="#" 
                                   data-user-id="${userId}" 
                                   data-access="manage">
                                    <div class="d-flex align-items-start">
                                        <div class="form-check me-3 mt-1">
                                            <input class="form-check-input" type="radio" name="access_${userId}" ${accessLevel === 'manage' ? 'checked' : ''}>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold mb-1">Manage</div>
                                            <small class="text-muted d-block">Query this module and edit configuration</small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <button class="btn btn-sm btn-outline-danger remove-selected-btn" data-user-id="${userId}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }).join('');
        
        // Attach event listeners for access level changes
        container.querySelectorAll('.access-option').forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                const userId = this.dataset.userId;
                const access = this.dataset.access;
                
                if (selectedMembers.has(userId)) {
                    selectedMembers.get(userId).accessLevel = access;
                    updateSelectedMembersDisplay();
                }
            });
        });
        
        // Attach event listeners for remove buttons
        container.querySelectorAll('.remove-selected-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.dataset.userId;
                selectedMembers.delete(userId);
                updateSelectedMembersDisplay();
                loadUsersForAddModal(); // Reload available users
            });
        });
    }
    
    // Add selected members button
    document.getElementById('addSelectedMembersBtn')?.addEventListener('click', function() {
        if (selectedMembers.size === 0) return;
        
        // Add all selected members
        const promises = Array.from(selectedMembers.entries()).map(([userId, data]) => {
            const formData = new FormData();
            formData.append('action', 'add_user_access');
            formData.append('user_id', userId);
            formData.append('module', selectedModule);
            formData.append('access_level', data.accessLevel);
            
            return fetch('?page=permissions&module=' + selectedModule, {
                method: 'POST',
                body: formData
            }).then(response => response.json());
        });
        
        Promise.all(promises).then(results => {
            const allSuccess = results.every(r => r.success);
            if (allSuccess) {
                bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                loadUsers(); // Reload main table
                selectedMembers.clear();
            } else {
                alert('Some members could not be added. Please try again.');
            }
        });
    });
    
    // Search in add user modal
    document.getElementById('addUserSearch')?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadUsersForAddModal();
        }, 300);
    });
    
    // Check Access functionality
    let currentCheckAccessUser = null;
    let currentCheckAccessModule = null;
    
    // Check Access button click
    document.getElementById('checkAccessBtn')?.addEventListener('click', function() {
        const email = document.getElementById('checkAccessEmail').value.trim();
        if (!email) {
            alert('Please enter a user email address');
            return;
        }
        
        if (!selectedModule) {
            alert('Please select a module first');
            return;
        }
        
        checkUserAccess(email, selectedModule);
    });
    
    // Check Access on Enter key
    document.getElementById('checkAccessEmail')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('checkAccessBtn').click();
        }
    });
    
    // Available modules (from PHP)
    const availableModules = <?php echo json_encode($available_modules); ?>;
    
    // Check Access modal open - set current module
    const checkAccessModal = document.getElementById('checkAccessModal');
    if (checkAccessModal) {
        checkAccessModal.addEventListener('show.bs.modal', function() {
            if (selectedModule) {
                const moduleName = availableModules[selectedModule]?.name || selectedModule;
                // Update placeholder with module name
                const emailInput = document.getElementById('checkAccessEmail');
                if (emailInput) {
                    emailInput.placeholder = `Enter user email for ${moduleName}`;
                }
            } else {
                alert('Please select a module first');
                bootstrap.Modal.getInstance(checkAccessModal).hide();
            }
        });
        
        checkAccessModal.addEventListener('hidden.bs.modal', function() {
            // Reset form
            document.getElementById('checkAccessEmail').value = '';
            document.getElementById('checkAccessResults').style.display = 'none';
            document.getElementById('checkAccessError').style.display = 'none';
            currentCheckAccessUser = null;
            currentCheckAccessModule = null;
        });
    }
    
    function checkUserAccess(email, module) {
        const formData = new FormData();
        formData.append('action', 'check_user_access');
        formData.append('user_email', email);
        formData.append('module', module);
        
        const btn = document.getElementById('checkAccessBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Checking...';
        
        fetch('?page=permissions&module=' + module, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (data.success) {
                currentCheckAccessUser = data.user;
                currentCheckAccessModule = module;
                displayAccessResults(data);
                document.getElementById('checkAccessError').style.display = 'none';
                document.getElementById('checkAccessResults').style.display = 'block';
            } else {
                document.getElementById('checkAccessError').textContent = data.message || 'Failed to check access';
                document.getElementById('checkAccessError').style.display = 'block';
                document.getElementById('checkAccessResults').style.display = 'none';
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            document.getElementById('checkAccessError').textContent = 'Error checking access: ' + error.message;
            document.getElementById('checkAccessError').style.display = 'block';
            document.getElementById('checkAccessResults').style.display = 'none';
        });
    }
    
    function displayAccessResults(data) {
        // Set user info
        document.getElementById('checkAccessUserName').textContent = data.user.name;
        document.getElementById('checkAccessModuleName').textContent = availableModules[data.module]?.name || data.module;
        
        // Helper function to render permission icon
        function renderPermission(hasPermission) {
            return hasPermission 
                ? '<i class="fas fa-check text-success"></i>' 
                : '<i class="fas fa-times text-danger"></i>';
        }
        
        // Role/Module permissions
        const rolePerms = data.role_permissions;
        document.getElementById('roleRead').innerHTML = renderPermission(rolePerms.can_view);
        document.getElementById('roleWrite').innerHTML = renderPermission(rolePerms.can_edit);
        document.getElementById('roleShare').innerHTML = renderPermission(rolePerms.can_edit);
        document.getElementById('roleDelete').innerHTML = renderPermission(rolePerms.can_delete);
        document.getElementById('roleManage').innerHTML = renderPermission(rolePerms.can_delete);
        
        // Individual permissions
        const indivPerms = data.individual_permissions;
        document.getElementById('indivRead').innerHTML = renderPermission(indivPerms.can_view);
        document.getElementById('indivWrite').innerHTML = renderPermission(indivPerms.can_edit);
        document.getElementById('indivShare').innerHTML = renderPermission(indivPerms.can_edit);
        document.getElementById('indivDelete').innerHTML = renderPermission(indivPerms.can_delete);
        document.getElementById('indivManage').innerHTML = renderPermission(indivPerms.can_delete);
        
        // Effective permissions
        const effectivePerms = data.effective_permissions;
        document.getElementById('effectiveRead').innerHTML = renderPermission(effectivePerms.can_view);
        document.getElementById('effectiveWrite').innerHTML = renderPermission(effectivePerms.can_edit);
        document.getElementById('effectiveShare').innerHTML = renderPermission(effectivePerms.can_edit);
        document.getElementById('effectiveDelete').innerHTML = renderPermission(effectivePerms.can_delete);
        document.getElementById('effectiveManage').innerHTML = renderPermission(effectivePerms.can_delete);
    }
    
    // Edit Permissions button - opens edit modal
    document.getElementById('editPermissionsBtn')?.addEventListener('click', function() {
        if (!currentCheckAccessUser || !currentCheckAccessModule) return;
        
        // Close check access modal
        bootstrap.Modal.getInstance(document.getElementById('checkAccessModal')).hide();
        
        // Open edit access modal with user info
        document.getElementById('editUserId').value = currentCheckAccessUser.id;
        
        // Determine current access level
        const effectivePerms = document.querySelectorAll('#checkAccessResults [id^="effective"]');
        let currentAccess = 'view';
        if (effectivePerms[4].querySelector('.fa-check')) { // Manage
            currentAccess = 'manage';
        } else if (effectivePerms[1].querySelector('.fa-check')) { // Write
            currentAccess = 'query';
        }
        
        // Set radio button
        const radio = document.querySelector(`input[name="editAccessLevel"][value="${currentAccess}"]`);
        if (radio) {
            radio.checked = true;
        }
        
        // Show edit modal
        new bootstrap.Modal(document.getElementById('editAccessModal')).show();
    });
    
    // Load users on page load if module is selected
    if (selectedModule && currentTab === 'user_permissions') {
        loadUsers();
    }
    
    // Hide the main header with black background (fallback)
    const header = document.querySelector('.main-content .header, #mainContent .header, header.header');
    if (header) {
        header.style.display = 'none';
        header.style.visibility = 'hidden';
        header.style.height = '0';
        header.style.padding = '0';
        header.style.margin = '0';
        header.style.overflow = 'hidden';
        header.style.opacity = '0';
    }
});
</script>
