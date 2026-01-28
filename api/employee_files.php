<?php
/**
 * Employee Files API - Secure File Management
 * Handles upload, download, delete, and list operations with security controls
 */

// Suppress error display for API (errors will be logged, not displayed)
// This prevents HTML error output from breaking JSON responses
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Bootstrap application
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/secure_file_storage.php';
require_once __DIR__ . '/../config/file_upload.php';

// Clear any output that might have been generated
ob_clean();

// Enforce JSON responses
header('Content-Type: application/json; charset=UTF-8');

// Security: Check if user is authenticated
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$current_user_id = (int)($_SESSION['user_id'] ?? 0);
$current_user_role = $_SESSION['user_role'] ?? '';

if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User ID not found in session']);
    exit;
}

$pdo = get_db_connection();
$config = include __DIR__ . '/../config/file_upload.php';

// Check if employee_files table exists
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'employee_files'");
    $table_exists = $table_check->rowCount() > 0;
    if (!$table_exists) {
        http_response_code(503);
        echo json_encode([
            'success' => false, 
            'error' => 'File system not initialized. Please run database migration first.',
            'migration_file' => 'sql/migrations/001_create_employee_files_tables.sql'
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("Error checking employee_files table: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper: Check if user can upload files
function can_upload_files($user_role, $config) {
    return in_array($user_role, $config['upload_allowed_roles']);
}

// Helper: Check if user can view files
function can_view_files($user_role, $config) {
    return in_array($user_role, $config['view_allowed_roles']);
}

// Helper: Check if user can access employee's files
function can_access_employee_files($user_id, $user_role, $employee_id, $pdo) {
    // Super admin can access all
    if ($user_role === 'super_admin') {
        return true;
    }
    
    // Check if employee exists
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    if (!$stmt->fetch()) {
        return false;
    }
    
    // HR roles can access all employees
    if (in_array($user_role, ['hr_admin', 'hr', 'admin'])) {
        return true;
    }
    
    // Other roles: check if they're assigned to this employee or have specific access
    // For now, allow accounting and operation roles to view
    if (in_array($user_role, ['accounting', 'operation'])) {
        return true;
    }
    
    return false;
}

// Helper: Log file operation to audit log
function log_file_operation($action, $user_id, $employee_id, $file_id, $success, $pdo, $error_message = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO file_audit_logs 
            (action, user_id, employee_id, file_id, ip_address, user_agent, success, error_message, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $action,
            $user_id,
            $employee_id,
            $file_id,
            $ip_address,
            $user_agent,
            $success ? 1 : 0,
            $error_message
        ]);
    } catch (Exception $e) {
        error_log("Failed to log file operation: " . $e->getMessage());
    }
}

// Route: POST /api/employee_files.php?action=upload&employee_id={id}
if ($method === 'POST' && $action === 'upload') {
    try {
        // Check upload permission
        if (!can_upload_files($current_user_role, $config)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Insufficient permissions to upload files']);
            exit;
        }
        
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        if ($employee_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid employee ID']);
            exit;
        }
        
        // Check access to employee
        if (!can_access_employee_files($current_user_id, $current_user_role, $employee_id, $pdo)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied to this employee']);
            exit;
        }
        
        // Check file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
            exit;
        }
        
        $file = $_FILES['file'];
        $category = $_POST['category'] ?? 'Other';
        
        // Validate category
        if (!in_array($category, $config['categories'])) {
            $category = 'Other';
        }
        
        // Save file securely
        $save_result = save_uploaded_file_securely($file, $employee_id);
        
        if (!$save_result['success']) {
            // Log the error with more details
            $error_msg = $save_result['error'] ?? 'Unknown error';
            error_log("File upload failed for employee $employee_id: $error_msg");
            log_file_operation('upload', $current_user_id, $employee_id, null, false, $pdo, $error_msg);
            
            $http_code = 413; // Payload Too Large
            if (strpos($save_result['error'], 'size') !== false) {
                $http_code = 413;
            } elseif (strpos($save_result['error'], 'type') !== false || strpos($save_result['error'], 'not allowed') !== false) {
                $http_code = 415; // Unsupported Media Type
            } else {
                $http_code = 400;
            }
            
            http_response_code($http_code);
            echo json_encode(['success' => false, 'error' => $save_result['error']]);
            exit;
        }
        
        // Get file info
        $original_filename = $file['name'];
        
        // Get MIME type from save result or detect it
        if (isset($save_result['mime_type']) && !empty($save_result['mime_type'])) {
            $mime_type = $save_result['mime_type'];
        } else {
            $file_path = get_stored_file_path($save_result['file_path']);
            if ($file_path && file_exists($file_path)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file_path);
                finfo_close($finfo);
            } else {
                $mime_type = $file['type'] ?? 'application/octet-stream';
            }
        }
        
        $size_bytes = $file['size'];
        
        // Determine storage driver
        $storage_driver = 'local'; // For now, always use local (outside web root)
        
        // Insert into database
        try {
            $stmt = $pdo->prepare("
                INSERT INTO employee_files 
                (employee_id, uploaded_by, original_filename, stored_filename, file_path, category, mime_type, size_bytes, storage_driver, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $employee_id,
                $current_user_id,
                $original_filename,
                $save_result['stored_filename'],
                $save_result['file_path'],
                $category,
                $mime_type,
                $size_bytes,
                $storage_driver,
            ]);
            
            $file_id = $pdo->lastInsertId();
            
            if (!$file_id) {
                throw new Exception('Failed to get file ID after insert');
            }
            
            // Log successful upload
            log_file_operation('upload', $current_user_id, $employee_id, $file_id, true, $pdo, null);
            
            echo json_encode([
                'success' => true,
                'file_id' => $file_id,
                'message' => 'File uploaded successfully',
            ]);
        } catch (PDOException $e) {
            error_log("Database error during file insert: " . $e->getMessage());
            // Delete the uploaded file if database insert failed
            if (isset($save_result['file_path'])) {
                delete_stored_file($save_result['file_path']);
            }
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to save file record to database: ' . $e->getMessage()
            ]);
        }
        
    } catch (Exception $e) {
        error_log("File upload error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Internal server error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Route: GET /api/employee_files.php?action=download&file_id={id}
if ($method === 'GET' && $action === 'download') {
    try {
        $file_id = (int)($_GET['file_id'] ?? 0);
        if ($file_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid file ID']);
            exit;
        }
        
        // Get file record
        $stmt = $pdo->prepare("
            SELECT ef.*, e.id as employee_id
            FROM employee_files ef
            INNER JOIN employees e ON ef.employee_id = e.id
            WHERE ef.id = ? AND ef.deleted_at IS NULL
        ");
        $stmt->execute([$file_id]);
        $file_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file_record) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'File not found']);
            exit;
        }
        
        // Check view permission
        if (!can_view_files($current_user_role, $config)) {
            log_file_operation('download', $current_user_id, $file_record['employee_id'], $file_id, false, $pdo, 'Insufficient permissions');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
            exit;
        }
        
        // Check access to employee
        if (!can_access_employee_files($current_user_id, $current_user_role, $file_record['employee_id'], $pdo)) {
            log_file_operation('download', $current_user_id, $file_record['employee_id'], $file_id, false, $pdo, 'Access denied to employee');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        // Get file path
        $file_path = get_stored_file_path($file_record['file_path']);
        
        if (!$file_path || !file_exists($file_path)) {
            log_file_operation('download', $current_user_id, $file_record['employee_id'], $file_id, false, $pdo, 'File not found on disk');
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'File not found on disk']);
            exit;
        }
        
        // Log download
        log_file_operation('download', $current_user_id, $file_record['employee_id'], $file_id, true, $pdo, null);
        
        // Stream file to browser
        header('Content-Type: ' . $file_record['mime_type']);
        header('Content-Disposition: attachment; filename="' . addslashes($file_record['original_filename']) . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file
        readfile($file_path);
        exit;
        
    } catch (Exception $e) {
        error_log("File download error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Internal server error']);
    }
    exit;
}

// Route: DELETE /api/employee_files.php?action=delete&file_id={id}
if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
    try {
        $file_id = (int)($_GET['file_id'] ?? $_POST['file_id'] ?? 0);
        if ($file_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid file ID']);
            exit;
        }
        
        // Get file record
        $stmt = $pdo->prepare("
            SELECT ef.*, e.id as employee_id
            FROM employee_files ef
            INNER JOIN employees e ON ef.employee_id = e.id
            WHERE ef.id = ? AND ef.deleted_at IS NULL
        ");
        $stmt->execute([$file_id]);
        $file_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file_record) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'File not found']);
            exit;
        }
        
        // Check delete permission (only upload-allowed roles can delete)
        if (!can_upload_files($current_user_role, $config)) {
            log_file_operation('delete', $current_user_id, $file_record['employee_id'], $file_id, false, $pdo, 'Insufficient permissions');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
            exit;
        }
        
        // Check access to employee
        if (!can_access_employee_files($current_user_id, $current_user_role, $file_record['employee_id'], $pdo)) {
            log_file_operation('delete', $current_user_id, $file_record['employee_id'], $file_id, false, $pdo, 'Access denied to employee');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        // Soft delete in database
        $stmt = $pdo->prepare("UPDATE employee_files SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$file_id]);
        
        // Delete physical file
        $file_path = get_stored_file_path($file_record['file_path']);
        if ($file_path && file_exists($file_path)) {
            @unlink($file_path);
        }
        
        // Log deletion
        log_file_operation('delete', $current_user_id, $file_record['employee_id'], $file_id, true, $pdo, null);
        
        echo json_encode([
            'success' => true,
            'message' => 'File deleted successfully',
        ]);
        
    } catch (Exception $e) {
        error_log("File delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Internal server error']);
    }
    exit;
}

// Route: GET /api/employee_files.php?action=list&employee_id={id}&page={page}&limit={limit}
if ($method === 'GET' && $action === 'list') {
    try {
        $employee_id = (int)($_GET['employee_id'] ?? 0);
        if ($employee_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid employee ID']);
            exit;
        }
        
        // Check view permission
        if (!can_view_files($current_user_role, $config)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
            exit;
        }
        
        // Check access to employee
        if (!can_access_employee_files($current_user_id, $current_user_role, $employee_id, $pdo)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        
        // Get files
        $stmt = $pdo->prepare("
            SELECT ef.*, u.name as uploaded_by_name
            FROM employee_files ef
            LEFT JOIN users u ON ef.uploaded_by = u.id
            WHERE ef.employee_id = ? AND ef.deleted_at IS NULL
            ORDER BY ef.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$employee_id, $limit, $offset]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $count_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM employee_files 
            WHERE employee_id = ? AND deleted_at IS NULL
        ");
        $count_stmt->execute([$employee_id]);
        $total = $count_stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'files' => $files,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit),
            ],
        ]);
        
    } catch (Exception $e) {
        error_log("File list error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Internal server error']);
    }
    exit;
}

// Invalid route
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Invalid action']);
