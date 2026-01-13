<?php
/**
 * Database Functions for Golden Z-5 HR System
 * Secure Database Operations
 * 
 * NOTE: This file maintains backward compatibility.
 * New code should use App\Core\Database instead.
 */

// Load bootstrap if available
if (file_exists(__DIR__ . '/../bootstrap/autoload.php')) {
    require_once __DIR__ . '/../bootstrap/autoload.php';
}

// Database configuration (fallback if config not loaded)
$db_config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'database' => $_ENV['DB_DATABASE'] ?? 'goldenz_hr',
    'charset' => 'utf8mb4'
];

// Create database connection
if (!function_exists('get_db_connection')) {
    function get_db_connection() {
        // Try to use new Database class if available
        if (class_exists('App\Core\Database')) {
            return \App\Core\Database::getInstance()->getConnection();
        }
        
        // Fallback to old method
        global $db_config;
        
        try {
            $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}";
            $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection failed: ' . $e->getMessage());
        }
    }
}

// Error logging function for database operations
if (!function_exists('log_db_error')) {
    function log_db_error($context, $message, $data = []) {
        $log_dir = __DIR__ . '/../storage/logs';
        if (!is_dir($log_dir)) {
            $log_dir = __DIR__ . '/../logs'; // Fallback
        }
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/error.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Unknown';
        $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 'Unknown';
        
        $log_entry = "[{$timestamp}] [{$context}] User: {$user} (ID: {$user_id}) | IP: {$ip}\n";
        $log_entry .= "Error: {$message}\n";
        
        if (!empty($data)) {
            $log_entry .= "Data: " . print_r($data, true) . "\n";
        }
        
        $log_entry .= "File: " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "\n";
        $log_entry .= str_repeat("-", 80) . "\n";
        
        @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// Execute prepared statement
if (!function_exists('execute_query')) {
    function execute_query($sql, $params = []) {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $error_msg = $e->getMessage();
            $error_code = $e->getCode();
            
            // Log to error log file
            log_db_error('execute_query', "PDO Error [{$error_code}]: {$error_msg}", [
                'sql' => $sql,
                'params' => $params,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Also log to PHP error log
            error_log('Database Error: ' . $error_msg . ' | SQL: ' . $sql . ' | Params: ' . print_r($params, true));
            
            // Throw the exception instead of dying so it can be caught and handled by calling code
            throw new Exception('Database error: ' . $error_msg);
        }
    }
}

// Get all employees
if (!function_exists('get_employees')) {
    function get_employees() {
        // Check if created_by_name column exists, if not just select all
        try {
            $check_sql = "SHOW COLUMNS FROM employees LIKE 'created_by_name'";
            $check_stmt = execute_query($check_sql);
            $has_created_by = $check_stmt->rowCount() > 0;
            
            if ($has_created_by) {
                $sql = "SELECT e.*, u.name as creator_name 
                        FROM employees e 
                        LEFT JOIN users u ON e.created_by = u.id 
                        ORDER BY e.created_at DESC";
            } else {
                $sql = "SELECT * FROM employees ORDER BY created_at DESC";
            }
        } catch (Exception $e) {
            $sql = "SELECT * FROM employees ORDER BY created_at DESC";
        }
        
        $stmt = execute_query($sql);
        $employees = $stmt->fetchAll();
        
        // Add created_by_name if it doesn't exist in result
        foreach ($employees as &$emp) {
            if (!isset($emp['created_by_name']) && isset($emp['creator_name'])) {
                $emp['created_by_name'] = $emp['creator_name'];
            }
        }
        
        return $employees;
    }
}

// Get employee by ID
if (!function_exists('get_employee')) {
    function get_employee($id) {
        $sql = "SELECT * FROM employees WHERE id = ?";
        $stmt = execute_query($sql, [$id]);
        return $stmt->fetch();
    }
}

// Ensure employee columns exist (safe, idempotent)
if (!function_exists('ensure_employee_columns')) {
    function ensure_employee_columns($columns) {
        if (empty($columns) || !is_array($columns)) return;
        try {
            $missing = [];
            foreach ($columns as $name => $definition) {
                $col = (string)$name;
                $def = (string)$definition;
                if ($col === '' || $def === '') continue;

                $check_sql = "SHOW COLUMNS FROM employees LIKE '$col'";
                $check_stmt = execute_query($check_sql);
                if ($check_stmt && $check_stmt->rowCount() == 0) {
                    $missing[] = "ADD COLUMN `$col` $def";
                }
            }
            if (!empty($missing)) {
                execute_query("ALTER TABLE employees " . implode(", ", $missing));
            }
        } catch (Exception $e) {
            // ignore (table may not exist yet or limited permissions)
        }
    }
}

// Update employee
if (!function_exists('update_employee')) {
    function update_employee($id, $data) {
        // Optional extended profile fields
        ensure_employee_columns([
            'gender' => 'VARCHAR(10) NULL',
            'civil_status' => 'VARCHAR(20) NULL',
            'age' => 'INT NULL',
            'birthplace' => 'VARCHAR(150) NULL',
            'citizenship' => 'VARCHAR(80) NULL',
            'provincial_address' => 'VARCHAR(255) NULL',
            'special_skills' => 'TEXT NULL',
            'spouse_name' => 'VARCHAR(150) NULL',
            'spouse_age' => 'INT NULL',
            'spouse_occupation' => 'VARCHAR(150) NULL',
            'father_name' => 'VARCHAR(150) NULL',
            'father_age' => 'INT NULL',
            'father_occupation' => 'VARCHAR(150) NULL',
            'mother_name' => 'VARCHAR(150) NULL',
            'mother_age' => 'INT NULL',
            'mother_occupation' => 'VARCHAR(150) NULL',
            'children_names' => 'TEXT NULL',
            // Education
            'college_course' => 'VARCHAR(150) NULL',
            'college_school_name' => 'VARCHAR(200) NULL',
            'college_school_address' => 'VARCHAR(255) NULL',
            'college_years' => 'VARCHAR(15) NULL',
            'vocational_course' => 'VARCHAR(150) NULL',
            'vocational_school_name' => 'VARCHAR(200) NULL',
            'vocational_school_address' => 'VARCHAR(255) NULL',
            'vocational_years' => 'VARCHAR(15) NULL',
            'highschool_school_name' => 'VARCHAR(200) NULL',
            'highschool_school_address' => 'VARCHAR(255) NULL',
            'highschool_years' => 'VARCHAR(15) NULL',
            'elementary_school_name' => 'VARCHAR(200) NULL',
            'elementary_school_address' => 'VARCHAR(255) NULL',
            'elementary_years' => 'VARCHAR(15) NULL',
            // Trainings / Government Exams (stored as JSON)
            'trainings_json' => 'TEXT NULL',
            'gov_exam_taken' => 'TINYINT(1) NULL',
            'gov_exam_json' => 'TEXT NULL',
            // Employment History (stored as JSON)
            'employment_history_json' => 'TEXT NULL',
        ]);

        $sql = "UPDATE employees SET 
                employee_no = ?, employee_type = ?, surname = ?, first_name = ?, middle_name = ?, 
                post = ?, license_no = ?, license_exp_date = ?, rlm_exp = ?, date_hired = ?, 
                cp_number = ?, sss_no = ?, pagibig_no = ?, tin_number = ?, philhealth_no = ?, 
                birth_date = ?, gender = ?, civil_status = ?, age = ?, birthplace = ?, citizenship = ?, provincial_address = ?, special_skills = ?,
                spouse_name = ?, spouse_age = ?, spouse_occupation = ?, father_name = ?, father_age = ?, father_occupation = ?, mother_name = ?, mother_age = ?, mother_occupation = ?, children_names = ?,
                college_course = ?, college_school_name = ?, college_school_address = ?, college_years = ?,
                vocational_course = ?, vocational_school_name = ?, vocational_school_address = ?, vocational_years = ?,
                highschool_school_name = ?, highschool_school_address = ?, highschool_years = ?,
                elementary_school_name = ?, elementary_school_address = ?, elementary_years = ?,
                trainings_json = ?, gov_exam_taken = ?, gov_exam_json = ?, employment_history_json = ?,
                height = ?, weight = ?, address = ?, contact_person = ?, 
                relationship = ?, contact_person_address = ?, contact_person_number = ?, 
                blood_type = ?, religion = ?, status = ?, updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            $data['employee_no'] ?? null,
            $data['employee_type'] ?? 'SG',
            $data['surname'] ?? '',
            $data['first_name'] ?? '',
            $data['middle_name'] ?? null,
            $data['post'] ?? '',
            $data['license_no'] ?? null,
            !empty($data['license_exp_date']) ? $data['license_exp_date'] : null,
            $data['rlm_exp'] ?? null,
            $data['date_hired'] ?? null,
            $data['cp_number'] ?? null,
            $data['sss_no'] ?? null,
            $data['pagibig_no'] ?? null,
            $data['tin_number'] ?? null,
            $data['philhealth_no'] ?? null,
            !empty($data['birth_date']) ? $data['birth_date'] : null,
            $data['gender'] ?? null,
            $data['civil_status'] ?? null,
            $data['age'] ?? null,
            $data['birthplace'] ?? null,
            $data['citizenship'] ?? null,
            $data['provincial_address'] ?? null,
            $data['special_skills'] ?? null,
            $data['spouse_name'] ?? null,
            $data['spouse_age'] ?? null,
            $data['spouse_occupation'] ?? null,
            $data['father_name'] ?? null,
            $data['father_age'] ?? null,
            $data['father_occupation'] ?? null,
            $data['mother_name'] ?? null,
            $data['mother_age'] ?? null,
            $data['mother_occupation'] ?? null,
            $data['children_names'] ?? null,
            $data['college_course'] ?? null,
            $data['college_school_name'] ?? null,
            $data['college_school_address'] ?? null,
            $data['college_years'] ?? null,
            $data['vocational_course'] ?? null,
            $data['vocational_school_name'] ?? null,
            $data['vocational_school_address'] ?? null,
            $data['vocational_years'] ?? null,
            $data['highschool_school_name'] ?? null,
            $data['highschool_school_address'] ?? null,
            $data['highschool_years'] ?? null,
            $data['elementary_school_name'] ?? null,
            $data['elementary_school_address'] ?? null,
            $data['elementary_years'] ?? null,
            $data['trainings_json'] ?? null,
            $data['gov_exam_taken'] ?? null,
            $data['gov_exam_json'] ?? null,
            $data['employment_history_json'] ?? null,
            $data['height'] ?? null,
            $data['weight'] ?? null,
            $data['address'] ?? null,
            $data['contact_person'] ?? null,
            $data['relationship'] ?? null,
            $data['contact_person_address'] ?? null,
            $data['contact_person_number'] ?? null,
            $data['blood_type'] ?? null,
            $data['religion'] ?? null,
            $data['status'] ?? 'Active',
            $id
        ];
        
        $stmt = execute_query($sql, $params);
        return $stmt->rowCount() > 0;
    }
}

// Add new employee
if (!function_exists('add_employee')) {
    function add_employee($data) {
        log_db_error('add_employee', 'Starting employee creation', ['input_data' => $data]);
        
        // Check if created_by column exists, if not add it
        try {
            $check_sql = "SHOW COLUMNS FROM employees LIKE 'created_by'";
            $check_stmt = execute_query($check_sql);
            if ($check_stmt->rowCount() == 0) {
                log_db_error('add_employee', 'Adding created_by columns to employees table');
                // Add created_by column
                $alter_sql = "ALTER TABLE employees ADD COLUMN created_by INT NULL, ADD COLUMN created_by_name VARCHAR(100) NULL, ADD INDEX idx_created_by (created_by)";
                execute_query($alter_sql);
            }
        } catch (Exception $e) {
            // Column might already exist or table doesn't exist yet
            log_db_error('add_employee', 'Column check failed (may be expected)', ['error' => $e->getMessage()]);
        }

        // Optional extended profile fields
        ensure_employee_columns([
            'gender' => 'VARCHAR(10) NULL',
            'civil_status' => 'VARCHAR(20) NULL',
            'age' => 'INT NULL',
            'birthplace' => 'VARCHAR(150) NULL',
            'citizenship' => 'VARCHAR(80) NULL',
            'provincial_address' => 'VARCHAR(255) NULL',
            'special_skills' => 'TEXT NULL',
            'spouse_name' => 'VARCHAR(150) NULL',
            'spouse_age' => 'INT NULL',
            'spouse_occupation' => 'VARCHAR(150) NULL',
            'father_name' => 'VARCHAR(150) NULL',
            'father_age' => 'INT NULL',
            'father_occupation' => 'VARCHAR(150) NULL',
            'mother_name' => 'VARCHAR(150) NULL',
            'mother_age' => 'INT NULL',
            'mother_occupation' => 'VARCHAR(150) NULL',
            'children_names' => 'TEXT NULL',
            // Education
            'college_course' => 'VARCHAR(150) NULL',
            'college_school_name' => 'VARCHAR(200) NULL',
            'college_school_address' => 'VARCHAR(255) NULL',
            'college_years' => 'VARCHAR(15) NULL',
            'vocational_course' => 'VARCHAR(150) NULL',
            'vocational_school_name' => 'VARCHAR(200) NULL',
            'vocational_school_address' => 'VARCHAR(255) NULL',
            'vocational_years' => 'VARCHAR(15) NULL',
            'highschool_school_name' => 'VARCHAR(200) NULL',
            'highschool_school_address' => 'VARCHAR(255) NULL',
            'highschool_years' => 'VARCHAR(15) NULL',
            'elementary_school_name' => 'VARCHAR(200) NULL',
            'elementary_school_address' => 'VARCHAR(255) NULL',
            'elementary_years' => 'VARCHAR(15) NULL',
            // Trainings / Government Exams (stored as JSON)
            'trainings_json' => 'TEXT NULL',
            'gov_exam_taken' => 'TINYINT(1) NULL',
            'gov_exam_json' => 'TEXT NULL',
            // Employment History (stored as JSON)
            'employment_history_json' => 'TEXT NULL',
        ]);
        
        // Keep column list + placeholders in sync (prevents 1136 column/value mismatch)
        $columns = [
            'employee_no', 'employee_type', 'surname', 'first_name', 'middle_name', 'post',
            'license_no', 'license_exp_date', 'rlm_exp', 'date_hired',
            'cp_number', 'sss_no', 'pagibig_no', 'tin_number', 'philhealth_no',
            'birth_date',
            'gender', 'civil_status', 'age', 'birthplace', 'citizenship', 'provincial_address', 'special_skills',
            'spouse_name', 'spouse_age', 'spouse_occupation',
            'father_name', 'father_age', 'father_occupation',
            'mother_name', 'mother_age', 'mother_occupation',
            'children_names',
            'college_course', 'college_school_name', 'college_school_address', 'college_years',
            'vocational_course', 'vocational_school_name', 'vocational_school_address', 'vocational_years',
            'highschool_school_name', 'highschool_school_address', 'highschool_years',
            'elementary_school_name', 'elementary_school_address', 'elementary_years',
            'trainings_json', 'gov_exam_taken', 'gov_exam_json', 'employment_history_json',
            'height', 'weight', 'address', 'contact_person', 'relationship', 'contact_person_address', 'contact_person_number',
            'blood_type', 'religion', 'status',
            'created_by', 'created_by_name'
        ];
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO employees (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")";
        
        $params = [
            $data['employee_no'],
            $data['employee_type'] ?? 'SG',
            $data['surname'],
            $data['first_name'],
            $data['middle_name'] ?? null,
            $data['post'],
            $data['license_no'] ?? null,
            $data['license_exp_date'] ?? null,
            $data['rlm_exp'] ?? null,
            $data['date_hired'],
            $data['cp_number'] ?? null,
            $data['sss_no'] ?? null,
            $data['pagibig_no'] ?? null,
            $data['tin_number'] ?? null,
            $data['philhealth_no'] ?? null,
            $data['birth_date'] ?? null,
            $data['gender'] ?? null,
            $data['civil_status'] ?? null,
            $data['age'] ?? null,
            $data['birthplace'] ?? null,
            $data['citizenship'] ?? null,
            $data['provincial_address'] ?? null,
            $data['special_skills'] ?? null,
            $data['spouse_name'] ?? null,
            $data['spouse_age'] ?? null,
            $data['spouse_occupation'] ?? null,
            $data['father_name'] ?? null,
            $data['father_age'] ?? null,
            $data['father_occupation'] ?? null,
            $data['mother_name'] ?? null,
            $data['mother_age'] ?? null,
            $data['mother_occupation'] ?? null,
            $data['children_names'] ?? null,
            $data['college_course'] ?? null,
            $data['college_school_name'] ?? null,
            $data['college_school_address'] ?? null,
            $data['college_years'] ?? null,
            $data['vocational_course'] ?? null,
            $data['vocational_school_name'] ?? null,
            $data['vocational_school_address'] ?? null,
            $data['vocational_years'] ?? null,
            $data['highschool_school_name'] ?? null,
            $data['highschool_school_address'] ?? null,
            $data['highschool_years'] ?? null,
            $data['elementary_school_name'] ?? null,
            $data['elementary_school_address'] ?? null,
            $data['elementary_years'] ?? null,
            $data['trainings_json'] ?? null,
            $data['gov_exam_taken'] ?? null,
            $data['gov_exam_json'] ?? null,
            $data['employment_history_json'] ?? null,
            $data['height'] ?? null,
            $data['weight'] ?? null,
            $data['address'] ?? null,
            $data['contact_person'] ?? null,
            $data['relationship'] ?? null,
            $data['contact_person_address'] ?? null,
            $data['contact_person_number'] ?? null,
            $data['blood_type'] ?? null,
            $data['religion'] ?? null,
            $data['status'] ?? 'Active',
            $data['created_by'] ?? null,
            $data['created_by_name'] ?? null
        ];
        
        log_db_error('add_employee', 'Prepared SQL and parameters', [
            'sql' => $sql,
            'params_count' => count($params),
            'params' => $params
        ]);
        
        try {
            $stmt = execute_query($sql, $params);
            
            // Check if statement executed successfully
            if ($stmt !== false) {
                $pdo = get_db_connection();
                $last_insert_id = $pdo->lastInsertId();
                log_db_error('add_employee', 'Employee created successfully', [
                    'last_insert_id' => $last_insert_id,
                    'employee_no' => $data['employee_no']
                ]);
                return true;
            } else {
                log_db_error('add_employee', 'Statement returned false', [
                    'sql' => $sql,
                    'params' => $params
                ]);
                return false;
            }
        } catch (Exception $e) {
            log_db_error('add_employee', 'Exception caught during employee creation', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'sql' => $sql,
                'params' => $params
            ]);
            
            error_log('Error adding employee: ' . $e->getMessage());
            return false;
        }
    }
}

// Delete employee
if (!function_exists('delete_employee')) {
    function delete_employee($id) {
        $sql = "DELETE FROM employees WHERE id = ?";
        return execute_query($sql, [$id]);
    }
}

// Get dashboard statistics
if (!function_exists('get_dashboard_stats')) {
    function get_dashboard_stats() {
        $stats = [];
        
        // Total employees - ensure integer conversion
        $sql = "SELECT COUNT(*) as total FROM employees";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['total_employees'] = (int)($result['total'] ?? 0);
        
        // Active employees - ensure integer conversion
        $sql = "SELECT COUNT(*) as active FROM employees WHERE status = 'Active'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['active_employees'] = (int)($result['active'] ?? 0);
        
        // Security Personnel (SG + LG combined) - ensure integer conversion
        $sql = "SELECT COUNT(*) as security FROM employees WHERE employee_type IN ('SG', 'LG') AND status = 'Active'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['security_personnel'] = (int)($result['security'] ?? 0);
        
        // Investigation Team (placeholder - can be customized based on post assignments)
        $sql = "SELECT COUNT(*) as investigation FROM employees WHERE post LIKE '%INVESTIGATION%' AND status = 'Active'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['investigation_team'] = (int)($result['investigation'] ?? 0);
        
        // Security Guards (SG) - for reference
        $sql = "SELECT COUNT(*) as security FROM employees WHERE employee_type = 'SG' AND status = 'Active'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['security_guards'] = (int)($result['security'] ?? 0);
        
        // Licensed Guards (LG) - for reference
        $sql = "SELECT COUNT(*) as licensed FROM employees WHERE employee_type = 'LG' AND status = 'Active'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['licensed_guards'] = (int)($result['licensed'] ?? 0);
        
        // Expired licenses - only count valid dates, exclude NULL and '0000-00-00'
        $sql = "SELECT COUNT(*) as expired 
                FROM employees 
                WHERE license_no IS NOT NULL 
                AND license_no != '' 
                AND license_exp_date IS NOT NULL 
                AND license_exp_date != '' 
                AND license_exp_date != '0000-00-00'
                AND license_exp_date < CURDATE()";
        $stmt = execute_query($sql);
        $stats['expired_licenses'] = (int)$stmt->fetch()['expired'];
        
        // Expiring licenses (next 30 days) - exclude already expired licenses
        $sql = "SELECT COUNT(*) as expiring 
                FROM employees 
                WHERE license_no IS NOT NULL 
                AND license_no != '' 
                AND license_exp_date IS NOT NULL 
                AND license_exp_date != '' 
                AND license_exp_date != '0000-00-00'
                AND license_exp_date >= CURDATE() 
                AND license_exp_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
        $stmt = execute_query($sql);
        $stats['expiring_licenses'] = (int)$stmt->fetch()['expiring'];
        
        return $stats;
    }
}

// Get employee statistics for employees page
if (!function_exists('get_employee_statistics')) {
    function get_employee_statistics() {
        $stats = [];
        
        try {
            // Total employees
            $sql = "SELECT COUNT(*) as total FROM employees";
            $stmt = execute_query($sql);
            $result = $stmt->fetch();
            $stats['total_employees'] = $result ? (int)$result['total'] : 0;
            
            // Active employees
            $sql = "SELECT COUNT(*) as active FROM employees WHERE LOWER(status) = 'active'";
            $stmt = execute_query($sql);
            $result = $stmt->fetch();
            $stats['active_employees'] = $result ? (int)$result['active'] : 0;
            
            // Inactive employees (includes Inactive, Terminated, Suspended)
            $sql = "SELECT COUNT(*) as inactive FROM employees WHERE LOWER(status) IN ('inactive', 'terminated', 'suspended')";
            $stmt = execute_query($sql);
            $result = $stmt->fetch();
            $stats['inactive_employees'] = $result ? (int)$result['inactive'] : 0;
            
            // Onboarding employees - check if employment_status column exists, otherwise use date_hired (hired in last 6 months)
            // First try with employment_status if it exists
            try {
                $sql = "SHOW COLUMNS FROM employees LIKE 'employment_status'";
                $stmt = execute_query($sql);
                $has_employment_status = $stmt->rowCount() > 0;
                
                if ($has_employment_status) {
                    $sql = "SELECT COUNT(*) as onboarding FROM employees WHERE LOWER(employment_status) = 'provisional' AND LOWER(status) = 'active'";
                } else {
                    // Use date_hired - employees hired in the last 6 months are considered onboarding
                    $sql = "SELECT COUNT(*) as onboarding FROM employees WHERE date_hired >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND LOWER(status) = 'active'";
                }
                $stmt = execute_query($sql);
                $result = $stmt->fetch();
                $stats['onboarding_employees'] = $result ? (int)$result['onboarding'] : 0;
            } catch (Exception $e) {
                // Fallback: use date_hired approach
                $sql = "SELECT COUNT(*) as onboarding FROM employees WHERE date_hired >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND LOWER(status) = 'active'";
                $stmt = execute_query($sql);
                $result = $stmt->fetch();
                $stats['onboarding_employees'] = $result ? (int)$result['onboarding'] : 0;
            }
            
        } catch (Exception $e) {
            // Fallback to zero if there's an error
            $stats['total_employees'] = 0;
            $stats['active_employees'] = 0;
            $stats['inactive_employees'] = 0;
            $stats['onboarding_employees'] = 0;
        }
        
        return $stats;
    }
}

// Create database tables if they don't exist
if (!function_exists('create_tables')) {
    function create_tables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_no INT NOT NULL,
            employee_type ENUM('SG', 'LG', 'SO') NOT NULL COMMENT 'SG = Security Guard, LG = Lady Guard, SO = Security Officer',
            surname VARCHAR(50) NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50),
            post VARCHAR(100) NOT NULL COMMENT 'Assignment/Post',
            license_no VARCHAR(50) UNIQUE,
            license_exp_date DATE,
            rlm_exp VARCHAR(50) COMMENT 'RLM = Renewal of License/Membership',
            date_hired DATE NOT NULL,
            cp_number VARCHAR(20) COMMENT 'Contact Phone Number',
            sss_no VARCHAR(20) COMMENT 'Social Security System Number',
            pagibig_no VARCHAR(20) COMMENT 'PAG-IBIG Fund Number',
            tin_number VARCHAR(20) COMMENT 'Tax Identification Number',
            philhealth_no VARCHAR(20) COMMENT 'PhilHealth Number',
            birth_date DATE,
            height VARCHAR(10),
            weight VARCHAR(10),
            address TEXT,
            contact_person VARCHAR(100),
            relationship VARCHAR(50),
            contact_person_address TEXT,
            contact_person_number VARCHAR(20),
            blood_type VARCHAR(5),
            religion VARCHAR(50),
            status ENUM('Active', 'Inactive', 'Terminated', 'Suspended') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_employee_no (employee_no),
            INDEX idx_employee_type (employee_type),
            INDEX idx_post (post),
            INDEX idx_license_no (license_no),
            INDEX idx_license_exp (license_exp_date),
            INDEX idx_status (status)
        )";
        
        execute_query($sql);
        
        // Insert sample data (Philippine format)
        $sample_employees = [
            [1, 'SG', 'ABAD', 'JOHN MARK', 'DANIEL', 'BENAVIDES', 'R4B-202309000367', '2028-09-14', '2025-10-05', '2023-09-28', '0926-6917781', '04-4417766-7', '1213-0723-0701', '623-432-731-000', '09-202633701-3', '1999-10-05', null, null, null, null, null, null, null, null, null, 'Active'],
            [2, 'LG', 'ABADILLA', 'NORA', 'CABALQUINTO', 'SAPPORO', 'NCR-202411000339', '2029-11-07', '2025-12-27', '2024-11-13', '0967-9952106', '03-9677548-9', '1210-1313-3667', '905-112-708-000', '02-200206334-6', '1970-12-27', null, null, null, null, null, null, null, null, null, 'Active'],
            [3, 'LG', 'ABANILLA', 'VILMA', 'ABEDAÑO', 'MCMC', 'R05-202412001808', '2029-12-20', '2025-04-05', '2022-03-30', '0928-5781417', '33-0816833-7', '1211-3233-7121', '236-835-638-000', '19-090526559-1', '1974-06-10', null, null, null, null, null, null, null, null, null, 'Active'],
            [4, 'SG', 'ABDULMAN', 'ALMAN', 'JAINUDDIN', 'MCMC', 'BAR-202504000186', '2030-04-07', 'NO SEMINAR', '2025-06-26', '0905-1844366', '10-1537326-8', '1213-4444-8273', '676-724-973-000', '14-050287358-6', '2002-12-04', '5\'7', '62 KG', 'BLOCK 27, ADDITION HILLS, MANDALUYONG CITY', 'ALNISAR SAID', 'COUSIN', 'BLOCK 27, ADDITION HILLS, MANDALUYONG CITY', '0912-9440814', null, 'MUSLIM', 'Active']
        ];
        
        $sql = "INSERT IGNORE INTO employees (employee_no, employee_type, surname, first_name, middle_name, post, license_no, license_exp_date, rlm_exp, date_hired, cp_number, sss_no, pagibig_no, tin_number, philhealth_no, birth_date, height, weight, address, contact_person, relationship, contact_person_address, contact_person_number, blood_type, religion, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        foreach ($sample_employees as $employee) {
            execute_query($sql, $employee);
        }
        
        // Create dtr_entries table
        $sql = "CREATE TABLE IF NOT EXISTS dtr_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            entry_date DATE NOT NULL,
            time_in TIME,
            time_out TIME,
            entry_type ENUM('time-in', 'time-out', 'break', 'overtime') NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            
            INDEX idx_employee_id (employee_id),
            INDEX idx_entry_date (entry_date),
            INDEX idx_entry_type (entry_type),
            UNIQUE KEY unique_employee_date (employee_id, entry_date)
        )";
        
        execute_query($sql);
    }
}

// DTR Functions
if (!function_exists('get_dtr_entries')) {
    function get_dtr_entries($employee_id = null, $date_from = null, $date_to = null) {
        try {
            $pdo = get_db_connection();
            $sql = "SELECT d.*, e.first_name, e.surname, e.post 
                    FROM dtr_entries d 
                    JOIN employees e ON d.employee_id = e.id";
            $params = [];
            $conditions = [];
            
            if ($employee_id) {
                $conditions[] = "d.employee_id = ?";
                $params[] = $employee_id;
            }
            
            if ($date_from) {
                $conditions[] = "d.entry_date >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $conditions[] = "d.entry_date <= ?";
                $params[] = $date_to;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY d.entry_date DESC, d.time_in ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting DTR entries: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('save_dtr_entry')) {
    function save_dtr_entry($data) {
        try {
            $pdo = get_db_connection();
            
            // Check if entry already exists for this employee and date
            $stmt = $pdo->prepare("SELECT id FROM dtr_entries WHERE employee_id = ? AND entry_date = ?");
            $stmt->execute([$data['employee_id'], $data['date']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing entry
                $sql = "UPDATE dtr_entries SET 
                        time_in = ?, time_out = ?, entry_type = ?, notes = ?, updated_at = NOW()
                        WHERE id = ?";
                $params = [
                    $data['time_in'],
                    $data['time_out'],
                    $data['type'],
                    $data['notes'],
                    $existing['id']
                ];
            } else {
                // Insert new entry
                $sql = "INSERT INTO dtr_entries (employee_id, entry_date, time_in, time_out, entry_type, notes, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $params = [
                    $data['employee_id'],
                    $data['date'],
                    $data['time_in'],
                    $data['time_out'],
                    $data['type'],
                    $data['notes']
                ];
            }
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error saving DTR entry: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_dtr_statistics')) {
    function get_dtr_statistics($date_from = null, $date_to = null) {
        try {
            $pdo = get_db_connection();
            
            $date_from = $date_from ?: date('Y-m-01'); // First day of current month
            $date_to = $date_to ?: date('Y-m-t'); // Last day of current month
            
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT employee_id) as total_employees,
                    COUNT(*) as total_entries,
                    SUM(CASE WHEN entry_type = 'time-in' THEN 1 ELSE 0 END) as time_in_count,
                    SUM(CASE WHEN entry_type = 'time-out' THEN 1 ELSE 0 END) as time_out_count,
                    SUM(CASE WHEN entry_type = 'break' THEN 1 ELSE 0 END) as break_count,
                    SUM(CASE WHEN entry_type = 'overtime' THEN 1 ELSE 0 END) as overtime_count
                FROM dtr_entries 
                WHERE entry_date BETWEEN ? AND ?
            ");
            $stmt->execute([$date_from, $date_to]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting DTR statistics: " . $e->getMessage());
            return [
                'total_employees' => 0,
                'total_entries' => 0,
                'time_in_count' => 0,
                'time_out_count' => 0,
                'break_count' => 0,
                'overtime_count' => 0
            ];
        }
    }
}

// Posts Management Functions
if (!function_exists('get_posts')) {
    function get_posts($filters = []) {
        try {
            $pdo = get_db_connection();
            $sql = "SELECT * FROM posts WHERE 1=1";
            $params = [];
            
            if (!empty($filters['department'])) {
                $sql .= " AND department = :department";
                $params['department'] = $filters['department'];
            }
            
            if (!empty($filters['employee_type'])) {
                $sql .= " AND employee_type = :employee_type";
                $params['employee_type'] = $filters['employee_type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND status = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (post_title LIKE :search OR location LIKE :search OR description LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in get_posts: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('get_post_by_id')) {
    function get_post_by_id($id) {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in get_post_by_id: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('create_post')) {
    function create_post($data) {
        try {
            $pdo = get_db_connection();
            $sql = "INSERT INTO posts (post_title, post_code, department, employee_type, location, description, requirements, responsibilities, required_count, priority, status, shift_type, work_hours, salary_range, benefits, reporting_to, expires_at) 
                    VALUES (:post_title, :post_code, :department, :employee_type, :location, :description, :requirements, :responsibilities, :required_count, :priority, :status, :shift_type, :work_hours, :salary_range, :benefits, :reporting_to, :expires_at)";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Database error in create_post: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('update_post')) {
    function update_post($id, $data) {
        try {
            $pdo = get_db_connection();
            $data['id'] = $id;
            $sql = "UPDATE posts SET 
                    post_title = :post_title,
                    post_code = :post_code,
                    department = :department,
                    employee_type = :employee_type,
                    location = :location,
                    description = :description,
                    requirements = :requirements,
                    responsibilities = :responsibilities,
                    required_count = :required_count,
                    priority = :priority,
                    status = :status,
                    shift_type = :shift_type,
                    work_hours = :work_hours,
                    salary_range = :salary_range,
                    benefits = :benefits,
                    reporting_to = :reporting_to,
                    expires_at = :expires_at,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Database error in update_post: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('delete_post')) {
    function delete_post($id) {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Database error in delete_post: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_post_statistics')) {
    function get_post_statistics() {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_posts,
                    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_posts,
                    SUM(required_count) as total_required,
                    SUM(filled_count) as total_filled,
                    SUM(required_count - filled_count) as total_vacant,
                    SUM(CASE WHEN priority = 'Urgent' THEN 1 ELSE 0 END) as urgent_posts,
                    SUM(CASE WHEN priority = 'High' THEN 1 ELSE 0 END) as high_priority_posts
                FROM posts
            ");
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in get_post_statistics: " . $e->getMessage());
            return [
                'total_posts' => 0,
                'active_posts' => 0,
                'total_required' => 0,
                'total_filled' => 0,
                'total_vacant' => 0,
                'urgent_posts' => 0,
                'high_priority_posts' => 0
            ];
        }
    }
}

if (!function_exists('get_posts_for_dropdown')) {
    function get_posts_for_dropdown() {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->query("
                SELECT id, post_title, post_code, location, required_count, filled_count, 
                       (required_count - filled_count) as available_count
                FROM posts 
                WHERE status = 'Active' 
                ORDER BY post_title
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in get_posts_for_dropdown: " . $e->getMessage());
            return [];
        }
    }
}

// ========================================
// EMPLOYEE ALERTS FUNCTIONS
// ========================================

// Get all employee alerts
if (!function_exists('get_employee_alerts')) {
    function get_employee_alerts($status = 'active', $priority = null) {
        $sql = "SELECT ea.*, e.employee_no, e.surname, e.first_name, e.middle_name, e.post, 
                        u1.name as created_by_name, u2.name as acknowledged_by_name
                 FROM employee_alerts ea
                 JOIN employees e ON ea.employee_id = e.id
                 LEFT JOIN users u1 ON ea.created_by = u1.id
                 LEFT JOIN users u2 ON ea.acknowledged_by = u2.id
                 WHERE ea.status = ?";
        
        $params = [$status];
        
        if ($priority) {
            $sql .= " AND ea.priority = ?";
            $params[] = $priority;
        }
        
        $sql .= " ORDER BY ea.priority DESC, ea.due_date ASC, ea.created_at DESC";
        
        $stmt = execute_query($sql, $params);
        return $stmt->fetchAll();
    }
}

// Get alerts by employee ID
if (!function_exists('get_employee_alerts_by_employee')) {
    function get_employee_alerts_by_employee($employee_id, $status = 'active') {
        $sql = "SELECT ea.*, e.employee_no, e.surname, e.first_name, e.middle_name, e.post,
                        u1.name as created_by_name, u2.name as acknowledged_by_name
                 FROM employee_alerts ea
                 JOIN employees e ON ea.employee_id = e.id
                 LEFT JOIN users u1 ON ea.created_by = u1.id
                 LEFT JOIN users u2 ON ea.acknowledged_by = u2.id
                 WHERE ea.employee_id = ? AND ea.status = ?
                 ORDER BY ea.priority DESC, ea.due_date ASC, ea.created_at DESC";
        
        $stmt = execute_query($sql, [$employee_id, $status]);
        return $stmt->fetchAll();
    }
}

// Get alert by ID
if (!function_exists('get_alert')) {
    function get_alert($id) {
        $sql = "SELECT ea.*, e.employee_no, e.surname, e.first_name, e.middle_name, e.post,
                        u1.name as created_by_name, u2.name as acknowledged_by_name
                 FROM employee_alerts ea
                 JOIN employees e ON ea.employee_id = e.id
                 LEFT JOIN users u1 ON ea.created_by = u1.id
                 LEFT JOIN users u2 ON ea.acknowledged_by = u2.id
                 WHERE ea.id = ?";
        
        $stmt = execute_query($sql, [$id]);
        return $stmt->fetch();
    }
}

// Create new alert
if (!function_exists('create_alert')) {
    function create_alert($data) {
        $sql = "INSERT INTO employee_alerts (employee_id, alert_type, title, description, alert_date, due_date, priority, status, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['employee_id'],
            $data['alert_type'],
            $data['title'],
            $data['description'] ?? null,
            $data['alert_date'],
            $data['due_date'] ?? null,
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'active',
            $data['created_by'] ?? null
        ];
        
        return execute_query($sql, $params);
    }
}

// Update alert
if (!function_exists('update_alert')) {
    function update_alert($id, $data) {
        $sql = "UPDATE employee_alerts SET 
                 alert_type = ?, title = ?, description = ?, alert_date = ?, due_date = ?, 
                 priority = ?, status = ? 
                 WHERE id = ?";
        
        $params = [
            $data['alert_type'],
            $data['title'],
            $data['description'] ?? null,
            $data['alert_date'],
            $data['due_date'] ?? null,
            $data['priority'],
            $data['status'],
            $id
        ];
        
        return execute_query($sql, $params);
    }
}

// Acknowledge alert
if (!function_exists('acknowledge_alert')) {
    function acknowledge_alert($id, $acknowledged_by) {
        $sql = "UPDATE employee_alerts SET 
                 status = 'acknowledged', acknowledged_by = ?, acknowledged_at = CURRENT_TIMESTAMP 
                 WHERE id = ?";
        
        return execute_query($sql, [$acknowledged_by, $id]);
    }
}

// Resolve alert
if (!function_exists('resolve_alert')) {
    function resolve_alert($id, $resolved_by) {
        $sql = "UPDATE employee_alerts SET 
                 status = 'resolved', resolved_at = CURRENT_TIMESTAMP 
                 WHERE id = ?";
        
        return execute_query($sql, [$id]);
    }
}

// Dismiss alert
if (!function_exists('dismiss_alert')) {
    function dismiss_alert($id) {
        $sql = "UPDATE employee_alerts SET status = 'dismissed' WHERE id = ?";
        return execute_query($sql, [$id]);
    }
}

// Delete alert
if (!function_exists('delete_alert')) {
    function delete_alert($id) {
        $sql = "DELETE FROM employee_alerts WHERE id = ?";
        return execute_query($sql, [$id]);
    }
}

// Get alert statistics
if (!function_exists('get_alert_statistics')) {
    function get_alert_statistics() {
        $stats = [];
        
        // Total active alerts
        $sql = "SELECT COUNT(*) as total FROM employee_alerts WHERE status = 'active'";
        $stmt = execute_query($sql);
        $stats['total_active'] = $stmt->fetch()['total'];
        
        // Urgent alerts
        $sql = "SELECT COUNT(*) as urgent FROM employee_alerts WHERE status = 'active' AND priority = 'urgent'";
        $stmt = execute_query($sql);
        $stats['urgent'] = $stmt->fetch()['urgent'];
        
        // High priority alerts
        $sql = "SELECT COUNT(*) as high FROM employee_alerts WHERE status = 'active' AND priority = 'high'";
        $stmt = execute_query($sql);
        $stats['high'] = $stmt->fetch()['high'];
        
        // Overdue alerts
        $sql = "SELECT COUNT(*) as overdue FROM employee_alerts WHERE status = 'active' AND due_date < CURDATE()";
        $stmt = execute_query($sql);
        $stats['overdue'] = $stmt->fetch()['overdue'];
        
        // Alerts by type
        $sql = "SELECT alert_type, COUNT(*) as count FROM employee_alerts WHERE status = 'active' GROUP BY alert_type";
        $stmt = execute_query($sql);
        $stats['by_type'] = $stmt->fetchAll();
        
        return $stats;
    }
}

// Auto-generate alerts for expiring licenses
if (!function_exists('generate_license_expiry_alerts')) {
    function generate_license_expiry_alerts() {
        $sql = "SELECT id, employee_no, surname, first_name, license_no, license_exp_date 
                 FROM employees 
                 WHERE license_no IS NOT NULL 
                 AND license_exp_date IS NOT NULL 
                 AND license_exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 AND id NOT IN (
                     SELECT employee_id FROM employee_alerts 
                     WHERE alert_type = 'license_expiry' 
                     AND status IN ('active', 'acknowledged')
                     AND due_date >= CURDATE()
                 )";
        
        $stmt = execute_query($sql);
        $employees = $stmt->fetchAll();
        
        $alerts_created = 0;
        foreach ($employees as $employee) {
            $days_until_expiry = (strtotime($employee['license_exp_date']) - time()) / (60 * 60 * 24);
            $priority = $days_until_expiry <= 7 ? 'urgent' : ($days_until_expiry <= 15 ? 'high' : 'medium');
            
            $alert_data = [
                'employee_id' => $employee['id'],
                'alert_type' => 'license_expiry',
                'title' => 'Security License Expiring Soon',
                'description' => "Security guard license ({$employee['license_no']}) will expire in " . round($days_until_expiry) . " days. Please renew before expiration.",
                'alert_date' => date('Y-m-d'),
                'due_date' => $employee['license_exp_date'],
                'priority' => $priority,
                'status' => 'active',
                'created_by' => 1 // System user
            ];
            
            create_alert($alert_data);
            $alerts_created++;
        }
        
        return $alerts_created;
    }
}

// ========================================
// AUDIT TRAIL FUNCTIONS
// ========================================

// Get audit logs with enhanced details
if (!function_exists('get_audit_logs')) {
    function get_audit_logs($filters = [], $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT al.*, u.name as user_name, u.username, u.role, u.email as user_email
                    FROM audit_logs al 
                    LEFT JOIN users u ON al.user_id = u.id 
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($filters['action'])) {
                $sql .= " AND al.action LIKE ?";
                $params[] = '%' . $filters['action'] . '%';
            }
            
            if (!empty($filters['table_name'])) {
                $sql .= " AND al.table_name = ?";
                $params[] = $filters['table_name'];
            }
            
            if (!empty($filters['user_id'])) {
                $sql .= " AND al.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(al.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(al.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = execute_query($sql, $params);
            $logs = $stmt->fetchAll();
            
            // Enhance logs with related record information
            foreach ($logs as &$log) {
                $log['related_record'] = get_audit_related_record($log['table_name'], $log['record_id']);
            }
            
            return $logs;
        } catch (Exception $e) {
            error_log("Database error in get_audit_logs: " . $e->getMessage());
            return [];
        }
    }
}

// Get related record information for audit logs
if (!function_exists('get_audit_related_record')) {
    function get_audit_related_record($table_name, $record_id) {
        if (!$table_name || !$record_id) {
            return null;
        }
        
        try {
            switch ($table_name) {
                case 'employees':
                    $sql = "SELECT id, employee_no, surname, first_name, middle_name, employee_type, post, status 
                            FROM employees WHERE id = ?";
                    $stmt = execute_query($sql, [$record_id]);
                    $record = $stmt->fetch();
                    if ($record) {
                        $record['display_name'] = trim($record['surname'] . ', ' . $record['first_name'] . ' ' . ($record['middle_name'] ?? ''));
                        $record['display_id'] = $record['employee_no'];
                    }
                    return $record;
                    
                case 'employee_alerts':
                    $sql = "SELECT ea.id, ea.title, ea.alert_type, ea.priority, ea.status, 
                                   e.employee_no, e.surname, e.first_name, e.middle_name
                            FROM employee_alerts ea
                            JOIN employees e ON ea.employee_id = e.id
                            WHERE ea.id = ?";
                    $stmt = execute_query($sql, [$record_id]);
                    $record = $stmt->fetch();
                    if ($record) {
                        $record['display_name'] = $record['title'];
                        $record['display_id'] = 'Alert #' . $record['id'];
                        $record['employee_name'] = trim($record['surname'] . ', ' . $record['first_name'] . ' ' . ($record['middle_name'] ?? ''));
                    }
                    return $record;
                    
                case 'posts':
                    $sql = "SELECT id, post_name, location, status FROM posts WHERE id = ?";
                    $stmt = execute_query($sql, [$record_id]);
                    $record = $stmt->fetch();
                    if ($record) {
                        $record['display_name'] = $record['post_name'];
                        $record['display_id'] = 'Post #' . $record['id'];
                    }
                    return $record;
                    
                case 'users':
                    $sql = "SELECT id, username, name, email, role, status FROM users WHERE id = ?";
                    $stmt = execute_query($sql, [$record_id]);
                    $record = $stmt->fetch();
                    if ($record) {
                        $record['display_name'] = $record['name'];
                        $record['display_id'] = $record['username'];
                    }
                    return $record;
                    
                default:
                    return null;
            }
        } catch (Exception $e) {
            error_log("Error getting related record: " . $e->getMessage());
            return null;
        }
    }
}

// Get audit log count
if (!function_exists('get_audit_logs_count')) {
    function get_audit_logs_count($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM audit_logs al WHERE 1=1";
            $params = [];
            
            if (!empty($filters['action'])) {
                $sql .= " AND al.action LIKE ?";
                $params[] = '%' . $filters['action'] . '%';
            }
            
            if (!empty($filters['table_name'])) {
                $sql .= " AND al.table_name = ?";
                $params[] = $filters['table_name'];
            }
            
            if (!empty($filters['user_id'])) {
                $sql .= " AND al.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(al.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(al.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            $stmt = execute_query($sql, $params);
            $result = $stmt->fetch();
            return $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            error_log("Database error in get_audit_logs_count: " . $e->getMessage());
            return 0;
        }
    }
}

// Log audit event
if (!function_exists('log_audit_event')) {
    function log_audit_event($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null, $user_id = null) {
        try {
            // Get user from session if not provided
            if ($user_id === null) {
                $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
            }
            
            $sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Convert arrays to JSON if needed
            if (is_array($old_values)) {
                $old_values = json_encode($old_values);
            }
            if (is_array($new_values)) {
                $new_values = json_encode($new_values);
            }
            
            $params = [
                $user_id,
                $action,
                $table_name,
                $record_id,
                $old_values,
                $new_values,
                $ip_address,
                $user_agent
            ];
            
            return execute_query($sql, $params);
        } catch (Exception $e) {
            error_log("Error logging audit event: " . $e->getMessage());
            // Also log to security log as fallback
            if (function_exists('log_security_event')) {
                log_security_event('Audit Log Error', $e->getMessage());
            }
            return false;
        }
    }
}

// ========================================
// TIME OFF FUNCTIONS
// ========================================

// Get time off requests
if (!function_exists('get_time_off_requests')) {
    function get_time_off_requests($filters = []) {
        $sql = "SELECT tor.*, e.employee_no, e.surname, e.first_name, e.middle_name, e.post,
                        u.name as approved_by_name
                 FROM time_off_requests tor
                 JOIN employees e ON tor.employee_id = e.id
                 LEFT JOIN users u ON tor.approved_by = u.id
                 WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND tor.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['employee_id'])) {
            $sql .= " AND tor.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (!empty($filters['request_type'])) {
            $sql .= " AND tor.request_type = ?";
            $params[] = $filters['request_type'];
        }
        
        $sql .= " ORDER BY tor.created_at DESC";
        
        $stmt = execute_query($sql, $params);
        return $stmt->fetchAll();
    }
}

// Get leave balances
if (!function_exists('get_leave_balances')) {
    function get_leave_balances($employee_id = null, $year = null) {
        $year = $year ?: date('Y');
        
        $sql = "SELECT lb.*, e.employee_no, e.surname, e.first_name, e.middle_name, e.post
                 FROM leave_balances lb
                 JOIN employees e ON lb.employee_id = e.id
                 WHERE lb.year = ?";
        
        $params = [$year];
        
        if ($employee_id) {
            $sql .= " AND lb.employee_id = ?";
            $params[] = $employee_id;
        }
        
        $sql .= " ORDER BY e.surname, e.first_name, lb.leave_type";
        
        $stmt = execute_query($sql, $params);
        return $stmt->fetchAll();
    }
}

// Task Management Functions
if (!function_exists('create_tasks_table')) {
    function create_tasks_table() {
        $sql = "CREATE TABLE IF NOT EXISTS hr_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_number VARCHAR(20) UNIQUE NOT NULL,
            task_title VARCHAR(255) NOT NULL,
            description TEXT,
            category ENUM('Employee Record', 'License', 'Leave Request', 'Clearance', 'Cash Bond', 'Other') DEFAULT 'Other',
            assigned_by INT,
            assigned_by_name VARCHAR(100),
            due_date DATE,
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            urgency_level ENUM('normal', 'important', 'critical') DEFAULT 'normal',
            location_page VARCHAR(255) COMMENT 'Where can it be found',
            notes TEXT COMMENT 'Notes created by the person who alerted',
            status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
            assigned_to INT COMMENT 'HR Admin user ID',
            completed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_priority (priority),
            INDEX idx_category (category),
            INDEX idx_due_date (due_date),
            INDEX idx_assigned_to (assigned_to)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            execute_query($sql);
            return true;
        } catch (Exception $e) {
            error_log('Error creating tasks table: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_all_tasks')) {
    function get_all_tasks($status = null, $priority = null, $category = null) {
        $sql = "SELECT t.*, u.name as assigned_to_name 
                FROM hr_tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        
        if ($priority) {
            $sql .= " AND t.priority = ?";
            $params[] = $priority;
        }
        
        if ($category) {
            $sql .= " AND t.category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY 
                    CASE t.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        WHEN 'low' THEN 4 
                    END,
                    t.due_date ASC,
                    t.created_at DESC";
        
        $stmt = execute_query($sql, $params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_task')) {
    function get_task($id) {
        $sql = "SELECT t.*, u.name as assigned_to_name 
                FROM hr_tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.id = ?";
        $stmt = execute_query($sql, [$id]);
        return $stmt->fetch();
    }
}

if (!function_exists('create_task')) {
    function create_task($data) {
        // Generate task number
        $task_number = 'TASK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Ensure unique task number
        $check_sql = "SELECT COUNT(*) as count FROM hr_tasks WHERE task_number = ?";
        $check_stmt = execute_query($check_sql, [$task_number]);
        $count = $check_stmt->fetch()['count'];
        
        if ($count > 0) {
            $task_number = 'TASK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        $sql = "INSERT INTO hr_tasks (task_number, task_title, description, category, assigned_by, assigned_by_name, 
                                     due_date, priority, urgency_level, location_page, notes, assigned_to, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $task_number,
            $data['task_title'] ?? '',
            $data['description'] ?? null,
            $data['category'] ?? 'Other',
            $data['assigned_by'] ?? null,
            $data['assigned_by_name'] ?? null,
            !empty($data['due_date']) ? $data['due_date'] : null,
            $data['priority'] ?? 'medium',
            $data['urgency_level'] ?? 'normal',
            $data['location_page'] ?? null,
            $data['notes'] ?? null,
            $data['assigned_to'] ?? null,
            $data['status'] ?? 'pending'
        ];
        
        $stmt = execute_query($sql, $params);
        return $stmt->rowCount() > 0;
    }
}

if (!function_exists('update_task')) {
    function update_task($id, $data) {
        $sql = "UPDATE hr_tasks SET 
                task_title = ?, description = ?, category = ?, due_date = ?, priority = ?, 
                urgency_level = ?, location_page = ?, notes = ?, status = ?, 
                assigned_to = ?, updated_at = CURRENT_TIMESTAMP";
        
        if (isset($data['status']) && $data['status'] === 'completed') {
            $sql .= ", completed_at = CURRENT_TIMESTAMP";
        } else {
            $sql .= ", completed_at = NULL";
        }
        
        $sql .= " WHERE id = ?";
        
        $params = [
            $data['task_title'] ?? '',
            $data['description'] ?? null,
            $data['category'] ?? 'Other',
            !empty($data['due_date']) ? $data['due_date'] : null,
            $data['priority'] ?? 'medium',
            $data['urgency_level'] ?? 'normal',
            $data['location_page'] ?? null,
            $data['notes'] ?? null,
            $data['status'] ?? 'pending',
            $data['assigned_to'] ?? null,
            $id
        ];
        
        $stmt = execute_query($sql, $params);
        return $stmt->rowCount() > 0;
    }
}

if (!function_exists('delete_task')) {
    function delete_task($id) {
        $sql = "DELETE FROM hr_tasks WHERE id = ?";
        return execute_query($sql, [$id]);
    }
}

if (!function_exists('get_pending_task_count')) {
    function get_pending_task_count() {
        $sql = "SELECT COUNT(*) as count FROM hr_tasks WHERE status IN ('pending', 'in_progress')";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    }
}

if (!function_exists('get_task_statistics')) {
    function get_task_statistics() {
        $stats = [];
        
        // Total tasks
        $sql = "SELECT COUNT(*) as total FROM hr_tasks";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['total'] = (int)($result['total'] ?? 0);
        
        // Pending tasks
        $sql = "SELECT COUNT(*) as count FROM hr_tasks WHERE status = 'pending'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['pending'] = (int)($result['count'] ?? 0);
        
        // In Progress tasks
        $sql = "SELECT COUNT(*) as count FROM hr_tasks WHERE status = 'in_progress'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['in_progress'] = (int)($result['count'] ?? 0);
        
        // Completed tasks
        $sql = "SELECT COUNT(*) as count FROM hr_tasks WHERE status = 'completed'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['completed'] = (int)($result['count'] ?? 0);
        
        // Cancelled tasks
        $sql = "SELECT COUNT(*) as count FROM hr_tasks WHERE status = 'cancelled'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['cancelled'] = (int)($result['count'] ?? 0);
        
        // Tasks needing action (pending + in_progress)
        $stats['needs_action'] = $stats['pending'] + $stats['in_progress'];
        
        // Overdue tasks (pending/in_progress with due_date < today)
        $sql = "SELECT COUNT(*) as count FROM hr_tasks 
                WHERE status IN ('pending', 'in_progress') 
                AND due_date IS NOT NULL 
                AND due_date != '' 
                AND due_date != '0000-00-00'
                AND due_date < CURDATE()";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['overdue'] = (int)($result['count'] ?? 0);
        
        // Urgent tasks (priority = urgent and status != completed)
        $sql = "SELECT COUNT(*) as count FROM hr_tasks 
                WHERE priority = 'urgent' 
                AND status != 'completed' 
                AND status != 'cancelled'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['urgent'] = (int)($result['count'] ?? 0);
        
        // High priority tasks (priority = high and status != completed)
        $sql = "SELECT COUNT(*) as count FROM hr_tasks 
                WHERE priority = 'high' 
                AND status != 'completed' 
                AND status != 'cancelled'";
        $stmt = execute_query($sql);
        $result = $stmt->fetch();
        $stats['high_priority'] = (int)($result['count'] ?? 0);
        
        return $stats;
    }
}

if (!function_exists('generate_employee_update_tasks')) {
    function generate_employee_update_tasks($assigned_to = null) {
        // Configuration thresholds for automatic HR notifications
        $thresholds = [
            'missing_fields_critical' => 5,      // If 5+ fields missing, escalate to urgent
            'missing_fields_high' => 3,          // If 3+ fields missing, high priority
            'license_expiring_critical' => 7,     // 7 days before expiration = critical
            'license_expiring_high' => 15,       // 15 days before expiration = high priority
            'license_expiring_medium' => 30,     // 30 days before expiration = medium priority
            'clearance_expiring_critical' => 14,  // 14 days before clearance expiration = critical
            'clearance_expiring_high' => 30,     // 30 days before clearance expiration = high
            'days_overdue_urgent' => 30,         // 30+ days overdue = urgent escalation
            'days_overdue_high' => 7              // 7+ days overdue = high priority
        ];
        
        // Get all active employees
        $sql = "SELECT * FROM employees WHERE status = 'Active'";
        $stmt = execute_query($sql);
        $employees = $stmt->fetchAll();
        
        $tasks_created = 0;
        $now = strtotime('today');
        
        foreach ($employees as $employee) {
            $employee_id = $employee['id'];
            $employee_name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['surname'] ?? ''));
            $employee_no = $employee['employee_no'] ?? 'N/A';
            $location_page = "?page=view_employee&id=" . $employee_id;
            
            // Check for missing required fields
            $missing_fields = [];
            $critical_fields = [];
            $important_fields = [];
            
            // Critical fields (affect operations)
            if (empty($employee['cp_number'])) {
                $missing_fields[] = 'Contact Phone Number';
                $critical_fields[] = 'Contact Phone Number';
            }
            if (empty($employee['license_no'])) {
                $missing_fields[] = 'License Number';
                $critical_fields[] = 'License Number';
            }
            if (empty($employee['license_exp_date'])) {
                $missing_fields[] = 'License Expiration Date';
                $critical_fields[] = 'License Expiration Date';
            }
            
            // Important fields (compliance)
            if (empty($employee['contact_person'])) {
                $missing_fields[] = 'Emergency Contact Person';
                $important_fields[] = 'Emergency Contact Person';
            }
            if (empty($employee['contact_person_number'])) {
                $missing_fields[] = 'Emergency Contact Number';
                $important_fields[] = 'Emergency Contact Number';
            }
            if (empty($employee['sss_no'])) {
                $missing_fields[] = 'SSS Number';
                $important_fields[] = 'SSS Number';
            }
            if (empty($employee['pagibig_no'])) {
                $missing_fields[] = 'PAG-IBIG Number';
                $important_fields[] = 'PAG-IBIG Number';
            }
            if (empty($employee['tin_number'])) {
                $missing_fields[] = 'TIN Number';
                $important_fields[] = 'TIN Number';
            }
            if (empty($employee['philhealth_no'])) {
                $missing_fields[] = 'PhilHealth Number';
                $important_fields[] = 'PhilHealth Number';
            }
            if (empty($employee['nbi_clearance_no'])) {
                $missing_fields[] = 'NBI Clearance';
                $important_fields[] = 'NBI Clearance';
            }
            if (empty($employee['police_clearance_no'])) {
                $missing_fields[] = 'Police Clearance';
                $important_fields[] = 'Police Clearance';
            }
            if (empty($employee['barangay_clearance_no'])) {
                $missing_fields[] = 'Barangay Clearance';
                $important_fields[] = 'Barangay Clearance';
            }
            
            // Create task for missing required fields with threshold-based priority
            if (!empty($missing_fields)) {
                $missing_count = count($missing_fields);
                $critical_count = count($critical_fields);
                
                // Determine priority based on thresholds
                if ($missing_count >= $thresholds['missing_fields_critical'] || $critical_count >= 2) {
                    $priority = 'urgent';
                    $urgency_level = 'critical';
                    $prefix = 'URGENT: ';
                } elseif ($missing_count >= $thresholds['missing_fields_high'] || $critical_count >= 1) {
                    $priority = 'high';
                    $urgency_level = 'important';
                    $prefix = 'ACTION REQUIRED: ';
                } else {
                    $priority = 'medium';
                    $urgency_level = 'normal';
                    $prefix = '';
                }
                
                // Create more descriptive task title
                $field_type = !empty($critical_fields) ? 'Critical Information' : 'Required Information';
                $task_title = $prefix . "Employee Record Update Needed - " . $employee_name . " (ID: " . $employee_no . ")";
                $description = "Employee record is incomplete and requires immediate attention. ";
                $description .= "Missing " . $missing_count . " required field(s): " . implode(', ', $missing_fields) . ". ";
                if (!empty($critical_fields)) {
                    $description .= "CRITICAL: Missing " . count($critical_fields) . " critical field(s) that affect operations: " . implode(', ', $critical_fields) . ". ";
                }
                $description .= "Please update the employee record to ensure compliance and operational readiness.";
                
                $notes = "Auto-generated task. Missing fields detected: " . implode(', ', $missing_fields) . ". ";
                $notes .= "This task was automatically created based on data completeness thresholds. ";
                if ($priority === 'urgent') {
                    $notes .= "URGENT: Critical information missing - immediate action required.";
                }
                
                // Check if task already exists for this employee's missing fields
                $check_sql = "SELECT id FROM hr_tasks 
                             WHERE task_title LIKE ? 
                             AND description LIKE ?
                             AND status IN ('pending', 'in_progress')
                             AND location_page = ?";
                $check_stmt = execute_query($check_sql, [
                    '%Update Employee Record: ' . $employee_name . '%',
                    '%Missing ' . $missing_count . ' required field%',
                    $location_page
                ]);
                
                if ($check_stmt->rowCount() == 0) {
                    $task_data = [
                        'task_title' => $task_title,
                        'description' => $description,
                        'category' => 'Employee Record',
                        'assigned_by' => 1, // System
                        'assigned_by_name' => 'System',
                        'due_date' => date('Y-m-d', strtotime('+7 days')),
                        'priority' => 'high',
                        'urgency_level' => 'important',
                        'location_page' => $location_page,
                        'notes' => $notes,
                        'assigned_to' => $assigned_to, // Assigned to HR-ADMIN
                        'status' => 'pending'
                    ];
                    
                    if (function_exists('create_task')) {
                        create_task($task_data);
                        $tasks_created++;
                    }
                }
            }
            
            // Check for expired licenses with threshold-based escalation
            if (!empty($employee['license_exp_date']) && $employee['license_exp_date'] !== '0000-00-00' && $employee['license_exp_date'] !== '') {
                $license_exp = strtotime($employee['license_exp_date']);
                if ($license_exp < $now) {
                    $days_expired = floor(($now - $license_exp) / (60 * 60 * 24));
                    
                    // Determine priority based on days expired threshold
                    if ($days_expired >= $thresholds['days_overdue_urgent']) {
                        $priority = 'urgent';
                        $urgency_level = 'critical';
                        $prefix = 'CRITICAL ESCALATION: ';
                    } elseif ($days_expired >= $thresholds['days_overdue_high']) {
                        $priority = 'urgent';
                        $urgency_level = 'critical';
                        $prefix = 'URGENT: ';
                    } else {
                        $priority = 'urgent';
                        $urgency_level = 'critical';
                        $prefix = 'URGENT: ';
                    }
                    
                    $check_sql = "SELECT id FROM hr_tasks 
                                 WHERE task_title LIKE ? 
                                 AND category = 'License'
                                 AND description LIKE ?
                                 AND status IN ('pending', 'in_progress')
                                 AND location_page = ?";
                    $check_stmt = execute_query($check_sql, [
                        '%' . $employee_name . '%',
                        '%License expired%',
                        $location_page
                    ]);
                    
                    if ($check_stmt->rowCount() == 0) {
                        $task_title = $prefix . "License Expired - " . $employee_name . " (ID: " . $employee_no . ")";
                        $description = "Employee license (" . ($employee['license_no'] ?? 'N/A') . ") expired " . $days_expired . " day(s) ago. ";
                        if ($days_expired >= $thresholds['days_overdue_urgent']) {
                            $description .= "CRITICAL: License has been expired for " . $days_expired . " days - immediate renewal required to maintain compliance and operational status.";
                        } else {
                            $description .= "Immediate action required to renew license and restore employee operational status.";
                        }
                        
                        $task_data = [
                            'task_title' => $task_title,
                            'description' => $description,
                            'category' => 'License',
                            'assigned_by' => 1,
                            'assigned_by_name' => 'System',
                            'due_date' => date('Y-m-d'),
                            'priority' => $priority,
                            'urgency_level' => $urgency_level,
                            'location_page' => $location_page,
                            'notes' => "License expired on " . date('M d, Y', $license_exp) . ". " . 
                                      ($days_expired >= $thresholds['days_overdue_urgent'] ? 
                                       "CRITICAL: Overdue by " . $days_expired . " days - immediate HR notification triggered." : 
                                       "Please renew immediately to avoid operational disruption."),
                            'assigned_to' => $assigned_to,
                            'status' => 'pending'
                        ];
                        
                        if (function_exists('create_task')) {
                            create_task($task_data);
                            $tasks_created++;
                        }
                    }
                }
            }
            
            // Check for expiring licenses with threshold-based priority
            if (!empty($employee['license_exp_date']) && $employee['license_exp_date'] !== '0000-00-00' && $employee['license_exp_date'] !== '') {
                $license_exp = strtotime($employee['license_exp_date']);
                $days_until_exp = floor(($license_exp - $now) / (60 * 60 * 24));
                
                if ($days_until_exp > 0 && $days_until_exp <= $thresholds['license_expiring_medium']) {
                    $check_sql = "SELECT id FROM hr_tasks 
                                 WHERE task_title LIKE ? 
                                 AND category = 'License'
                                 AND description LIKE ?
                                 AND status IN ('pending', 'in_progress')
                                 AND location_page = ?";
                    $check_stmt = execute_query($check_sql, [
                        '%' . $employee_name . '%',
                        '%License expiring%',
                        $location_page
                    ]);
                    
                    if ($check_stmt->rowCount() == 0) {
                        // Determine priority based on threshold
                        if ($days_until_exp <= $thresholds['license_expiring_critical']) {
                            $priority = 'urgent';
                            $urgency_level = 'critical';
                            $prefix = 'URGENT: ';
                        } elseif ($days_until_exp <= $thresholds['license_expiring_high']) {
                            $priority = 'high';
                            $urgency_level = 'important';
                            $prefix = 'ACTION REQUIRED: ';
                        } else {
                            $priority = 'medium';
                            $urgency_level = 'normal';
                            $prefix = '';
                        }
                        
                        $task_title = $prefix . "License Expiring - " . $employee_name . " (ID: " . $employee_no . ")";
                        $description = "Employee license (" . ($employee['license_no'] ?? 'N/A') . ") will expire in " . $days_until_exp . " day(s) on " . date('M d, Y', $license_exp) . ". ";
                        if ($days_until_exp <= $thresholds['license_expiring_critical']) {
                            $description .= "CRITICAL: License expires in " . $days_until_exp . " days - immediate renewal action required to prevent expiration.";
                        } else {
                            $description .= "Please initiate renewal process before expiration to maintain compliance.";
                        }
                        
                        $task_data = [
                            'task_title' => $task_title,
                            'description' => $description,
                            'category' => 'License',
                            'assigned_by' => 1,
                            'assigned_by_name' => 'System',
                            'due_date' => $employee['license_exp_date'],
                            'priority' => $priority,
                            'urgency_level' => $urgency_level,
                            'location_page' => $location_page,
                            'notes' => "License expires on " . date('M d, Y', $license_exp) . ". " . 
                                      ($days_until_exp <= $thresholds['license_expiring_critical'] ? 
                                       "CRITICAL: Automatic HR notification triggered due to threshold (" . $thresholds['license_expiring_critical'] . " days)." : 
                                       "Action needed before expiration."),
                            'assigned_to' => $assigned_to,
                            'status' => 'pending'
                        ];
                        
                        if (function_exists('create_task')) {
                            create_task($task_data);
                            $tasks_created++;
                        }
                    }
                }
            }
            
            // Check for expired and expiring clearances with threshold-based priority
            $clearances = [
                ['field' => 'nbi_clearance_exp', 'name' => 'NBI Clearance', 'number_field' => 'nbi_clearance_no'],
                ['field' => 'police_clearance_exp', 'name' => 'Police Clearance', 'number_field' => 'police_clearance_no'],
                ['field' => 'barangay_clearance_exp', 'name' => 'Barangay Clearance', 'number_field' => 'barangay_clearance_no']
            ];
            
            foreach ($clearances as $clearance) {
                if (!empty($employee[$clearance['field']]) && $employee[$clearance['field']] !== '0000-00-00' && $employee[$clearance['field']] !== '') {
                    $clearance_exp = strtotime($employee[$clearance['field']]);
                    $clearance_no = $employee[$clearance['number_field']] ?? 'N/A';
                    
                    // Check for expired clearances
                    if ($clearance_exp < $now) {
                        $days_expired = floor(($now - $clearance_exp) / (60 * 60 * 24));
                        
                        // Determine priority based on threshold
                        if ($days_expired >= $thresholds['days_overdue_urgent']) {
                            $priority = 'urgent';
                            $urgency_level = 'critical';
                            $prefix = 'CRITICAL: ';
                        } elseif ($days_expired >= $thresholds['days_overdue_high']) {
                            $priority = 'urgent';
                            $urgency_level = 'critical';
                            $prefix = 'URGENT: ';
                        } else {
                            $priority = 'high';
                            $urgency_level = 'important';
                            $prefix = '';
                        }
                        
                        $check_sql = "SELECT id FROM hr_tasks 
                                     WHERE task_title LIKE ? 
                                     AND category = 'Clearance'
                                     AND description LIKE ?
                                     AND status IN ('pending', 'in_progress')
                                     AND location_page = ?";
                        $check_stmt = execute_query($check_sql, [
                            '%' . $employee_name . '%',
                            '%' . $clearance['name'] . '%',
                            $location_page
                        ]);
                        
                        if ($check_stmt->rowCount() == 0) {
                            $task_title = $prefix . $clearance['name'] . " Expired - " . $employee_name . " (ID: " . $employee_no . ")";
                            $description = $clearance['name'] . " (" . $clearance_no . ") expired " . $days_expired . " day(s) ago. ";
                            if ($days_expired >= $thresholds['days_overdue_urgent']) {
                                $description .= "CRITICAL: Clearance has been expired for " . $days_expired . " days - immediate renewal required for compliance.";
                            } else {
                                $description .= "Please renew to maintain compliance and operational status.";
                            }
                            
                            $task_data = [
                                'task_title' => $task_title,
                                'description' => $description,
                                'category' => 'Clearance',
                                'assigned_by' => 1,
                                'assigned_by_name' => 'System',
                                'due_date' => date('Y-m-d', strtotime('+' . ($priority === 'urgent' ? 0 : 7) . ' days')),
                                'priority' => $priority,
                                'urgency_level' => $urgency_level,
                                'location_page' => $location_page,
                                'notes' => $clearance['name'] . " expired on " . date('M d, Y', $clearance_exp) . ". " . 
                                          ($days_expired >= $thresholds['days_overdue_urgent'] ? 
                                           "CRITICAL: Automatic HR notification triggered - overdue by " . $days_expired . " days." : 
                                           "Renewal required."),
                                'assigned_to' => $assigned_to,
                                'status' => 'pending'
                            ];
                            
                            if (function_exists('create_task')) {
                                create_task($task_data);
                                $tasks_created++;
                            }
                        }
                    } 
                    // Check for expiring clearances
                    elseif ($clearance_exp > $now) {
                        $days_until_exp = floor(($clearance_exp - $now) / (60 * 60 * 24));
                        
                        if ($days_until_exp <= $thresholds['clearance_expiring_high']) {
                            $check_sql = "SELECT id FROM hr_tasks 
                                         WHERE task_title LIKE ? 
                                         AND category = 'Clearance'
                                         AND description LIKE ?
                                         AND status IN ('pending', 'in_progress')
                                         AND location_page = ?";
                            $check_stmt = execute_query($check_sql, [
                                '%' . $employee_name . '%',
                                '%' . $clearance['name'] . ' expiring%',
                                $location_page
                            ]);
                            
                            if ($check_stmt->rowCount() == 0) {
                                // Determine priority based on threshold
                                if ($days_until_exp <= $thresholds['clearance_expiring_critical']) {
                                    $priority = 'urgent';
                                    $urgency_level = 'critical';
                                    $prefix = 'URGENT: ';
                                } else {
                                    $priority = 'high';
                                    $urgency_level = 'important';
                                    $prefix = '';
                                }
                                
                                $task_title = $prefix . $clearance['name'] . " Expiring - " . $employee_name . " (ID: " . $employee_no . ")";
                                $description = $clearance['name'] . " (" . $clearance_no . ") will expire in " . $days_until_exp . " day(s) on " . date('M d, Y', $clearance_exp) . ". ";
                                if ($days_until_exp <= $thresholds['clearance_expiring_critical']) {
                                    $description .= "CRITICAL: Clearance expires in " . $days_until_exp . " days - immediate renewal action required.";
                                } else {
                                    $description .= "Please initiate renewal process before expiration.";
                                }
                                
                                $task_data = [
                                    'task_title' => $task_title,
                                    'description' => $description,
                                    'category' => 'Clearance',
                                    'assigned_by' => 1,
                                    'assigned_by_name' => 'System',
                                    'due_date' => $employee[$clearance['field']],
                                    'priority' => $priority,
                                    'urgency_level' => $urgency_level,
                                    'location_page' => $location_page,
                                    'notes' => $clearance['name'] . " expires on " . date('M d, Y', $clearance_exp) . ". " . 
                                              ($days_until_exp <= $thresholds['clearance_expiring_critical'] ? 
                                               "CRITICAL: Automatic HR notification triggered due to threshold (" . $thresholds['clearance_expiring_critical'] . " days)." : 
                                               "Action needed before expiration."),
                                    'assigned_to' => $assigned_to,
                                    'status' => 'pending'
                                ];
                                
                                if (function_exists('create_task')) {
                                    create_task($task_data);
                                    $tasks_created++;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $tasks_created;
    }
}

// Initialize database
if (function_exists('create_tables')) {
    create_tables();
}

// Create tasks table if it doesn't exist
if (function_exists('create_tasks_table')) {
    create_tasks_table();
}
?>
