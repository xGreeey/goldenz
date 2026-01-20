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
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? 'Suomynona027',
    'database' => $_ENV['DB_DATABASE'] ?? 'goldenz_hr',
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
];

// Create database connection
if (!function_exists('get_db_connection')) {
    function get_db_connection() {
        // Use cached connection if available
        static $db_connection = null;

        if ($db_connection !== null) {
            // Check if connection is still alive
            try {
                $db_connection->query('SELECT 1');
                return $db_connection;
            } catch (PDOException $e) {
                // Connection is dead, reset it
                $db_connection = null;
            }
        }

        // Try to use new Database class if available
        if (class_exists('App\Core\Database')) {
            try {
                $db_connection = \App\Core\Database::getInstance()->getConnection();
                return $db_connection;
            } catch (Exception $e) {
                // Fall through to fallback method
            }
        }

        // Fallback to old method
        global $db_config;

        // Normalize config structure - handle both flat and nested config formats
        if (!isset($db_config) || empty($db_config) || !isset($db_config['host'])) {
            // Try to load config if not available
            $config_file = __DIR__ . '/../config/database.php';
            if (file_exists($config_file)) {
                $loaded_config = include $config_file;
                
                // Handle nested config structure (from config/database.php)
                if (isset($loaded_config['connections']['mysql'])) {
                    $mysql_config = $loaded_config['connections']['mysql'];
                    $db_config = [
                        'host' => $mysql_config['host'] ?? $_ENV['DB_HOST'] ?? 'localhost',
                        'port' => $mysql_config['port'] ?? $_ENV['DB_PORT'] ?? '3306',
                        'username' => $mysql_config['username'] ?? $_ENV['DB_USERNAME'] ?? 'root',
                        'password' => $mysql_config['password'] ?? $_ENV['DB_PASSWORD'] ?? '',
                        'database' => $mysql_config['database'] ?? $_ENV['DB_DATABASE'] ?? 'goldenz_hr',
                        'charset' => $mysql_config['charset'] ?? $_ENV['DB_CHARSET'] ?? 'utf8mb4'
                    ];
                } else {
                    // Handle flat config structure
                    $db_config = $loaded_config;
                }
            } else {
                error_log('Database configuration not found');
                throw new Exception('Database configuration not found');
            }
        }

        // Ensure port is set
        if (!isset($db_config['port'])) {
            $db_config['port'] = $_ENV['DB_PORT'] ?? '3306';
        }

        // Reload environment variables to ensure we have the latest values (important for Docker)
        // Environment variables take precedence over config file values
        $db_config['host'] = $_ENV['DB_HOST'] ?? $db_config['host'] ?? 'localhost';
        $db_config['port'] = $_ENV['DB_PORT'] ?? $db_config['port'] ?? '3306';
        $db_config['username'] = $_ENV['DB_USERNAME'] ?? $db_config['username'] ?? 'root';
        $db_config['password'] = $_ENV['DB_PASSWORD'] ?? $db_config['password'] ?? '';
        $db_config['database'] = $_ENV['DB_DATABASE'] ?? $db_config['database'] ?? 'goldenz_hr';
        $db_config['charset'] = $_ENV['DB_CHARSET'] ?? $db_config['charset'] ?? 'utf8mb4';

        try {
            // Get connection parameters
            $host = $db_config['host'];
            $port = $db_config['port'];
            $database = $db_config['database'];
            $charset = $db_config['charset'];
            
            // For Docker: if host is 'localhost', we need to use the Docker service name instead
            // Only convert to 127.0.0.1 if we're sure we're not in Docker (but this won't work in Docker anyway)
            // Better approach: log a helpful error if localhost is used in what appears to be Docker
            
            // Build DSN with port for Docker compatibility
            // Always use TCP/IP connection with explicit port
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
            
            $db_connection = new PDO($dsn, $db_config['username'], $db_config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false, // Don't use persistent connections to avoid memory issues
            ]);
            return $db_connection;
        } catch (PDOException $e) {
            // Enhanced error logging with connection details (without password)
            $error_details = [
                'host' => $db_config['host'],
                'port' => $db_config['port'],
                'database' => $db_config['database'],
                'username' => $db_config['username'],
                'error' => $e->getMessage(),
                'env_db_host' => $_ENV['DB_HOST'] ?? 'not_set'
            ];
            
            $error_msg = 'Database connection failed: ' . $e->getMessage();
            $error_msg .= "\nAttempted connection to: {$db_config['host']}:{$db_config['port']}";
            
            // Provide helpful Docker-specific guidance
            if ($db_config['host'] === 'localhost' || $db_config['host'] === '127.0.0.1') {
                $error_msg .= "\n\n⚠️  DOCKER DETECTED: You are using 'localhost' or '127.0.0.1' as DB_HOST.";
                $error_msg .= "\nIn Docker, you must use your MySQL service name (e.g., 'mysql', 'db', 'database').";
                $error_msg .= "\nSet DB_HOST environment variable to your Docker MySQL service name.";
                $error_msg .= "\nCheck your docker-compose.yml to find the MySQL service name.";
            }
            
            error_log('Database connection failed: ' . json_encode($error_details));
            throw new Exception($error_msg);
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

// Get all employees - fetches all records from database
if (!function_exists('get_employees')) {
    function get_employees() {
        try {
            $pdo = get_db_connection();

            // Check if created_by_name column exists, if not just select all
            try {
                $check_sql = "SHOW COLUMNS FROM employees LIKE 'created_by_name'";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute();
                $has_created_by = $check_stmt->rowCount() > 0;
            } catch (Exception $e) {
                $has_created_by = false;
            }

            // Always fetch ALL employees from database - no filtering
            if ($has_created_by) {
                $sql = "SELECT e.*, u.name as creator_name
                        FROM employees e
                        LEFT JOIN users u ON e.created_by = u.id
                        ORDER BY e.created_at DESC";
            } else {
                // Get all columns and all records from employees table
                $sql = "SELECT * FROM employees ORDER BY created_at DESC";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            // Fetch all records as associative array
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add created_by_name if it doesn't exist in result
            foreach ($employees as &$emp) {
                if (!isset($emp['created_by_name']) && isset($emp['creator_name'])) {
                    $emp['created_by_name'] = $emp['creator_name'];
                }
            }

            return $employees;
        } catch (Exception $e) {
            // Log error but return empty array instead of failing
            if (function_exists('log_db_error')) {
                log_db_error('get_employees', 'Error fetching employees', [
                    'error' => $e->getMessage()
                ]);
            }
            error_log('Error in get_employees: ' . $e->getMessage());
            return [];
        }
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
        // Optional extended profile fields - including Page 2 fields
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
            // Page 2 Fields - General Information
            'vacancy_source' => 'TEXT NULL',
            'referral_name' => 'VARCHAR(150) NULL',
            'knows_agency_person' => 'ENUM(\'Yes\', \'No\') NULL',
            'agency_person_name' => 'VARCHAR(200) NULL',
            'physical_defect' => 'ENUM(\'Yes\', \'No\') NULL',
            'physical_defect_specify' => 'TEXT NULL',
            'drives' => 'ENUM(\'Yes\', \'No\') NULL',
            'drivers_license_no' => 'VARCHAR(50) NULL',
            'drivers_license_exp' => 'VARCHAR(50) NULL',
            'drinks_alcohol' => 'ENUM(\'Yes\', \'No\') NULL',
            'alcohol_frequency' => 'VARCHAR(100) NULL',
            'prohibited_drugs' => 'ENUM(\'Yes\', \'No\') NULL',
            'security_guard_experience' => 'VARCHAR(100) NULL',
            'convicted' => 'ENUM(\'Yes\', \'No\') NULL',
            'conviction_details' => 'TEXT NULL',
            'filed_case' => 'ENUM(\'Yes\', \'No\') NULL',
            'case_specify' => 'TEXT NULL',
            'action_after_termination' => 'TEXT NULL',
            // Page 2 Fields - Specimen Signature and Initial
            'signature_1' => 'VARCHAR(200) NULL',
            'signature_2' => 'VARCHAR(200) NULL',
            'signature_3' => 'VARCHAR(200) NULL',
            'initial_1' => 'VARCHAR(100) NULL',
            'initial_2' => 'VARCHAR(100) NULL',
            'initial_3' => 'VARCHAR(100) NULL',
            // Page 2 Fields - Fingerprints
            'fingerprint_right_thumb' => 'VARCHAR(255) NULL',
            'fingerprint_right_index' => 'VARCHAR(255) NULL',
            'fingerprint_right_middle' => 'VARCHAR(255) NULL',
            'fingerprint_right_ring' => 'VARCHAR(255) NULL',
            'fingerprint_right_little' => 'VARCHAR(255) NULL',
            'fingerprint_left_thumb' => 'VARCHAR(255) NULL',
            'fingerprint_left_index' => 'VARCHAR(255) NULL',
            'fingerprint_left_middle' => 'VARCHAR(255) NULL',
            'fingerprint_left_ring' => 'VARCHAR(255) NULL',
            'fingerprint_left_little' => 'VARCHAR(255) NULL',
            // Page 2 Fields - Basic Requirements
            'requirements_signature' => 'VARCHAR(200) NULL',
            'req_2x2' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_birth_cert' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_barangay' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_police' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_nbi' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_di' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_diploma' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_neuro_drug' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_sec_license' => 'ENUM(\'YO\', \'NO\') NULL',
            'sec_lic_no' => 'VARCHAR(50) NULL',
            'req_sec_lic_no' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_sss' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_pagibig' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_philhealth' => 'ENUM(\'YO\', \'NO\') NULL',
            'req_tin' => 'ENUM(\'YO\', \'NO\') NULL',
            // Page 2 Fields - Sworn Statement
            'sworn_day' => 'VARCHAR(10) NULL',
            'sworn_month' => 'VARCHAR(50) NULL',
            'sworn_year' => 'VARCHAR(10) NULL',
            'tax_cert_no' => 'VARCHAR(100) NULL',
            'tax_cert_issued_at' => 'VARCHAR(200) NULL',
            'sworn_signature' => 'VARCHAR(200) NULL',
            'affiant_community' => 'VARCHAR(200) NULL',
            // Page 2 Fields - Form Footer
            'doc_no' => 'VARCHAR(50) NULL',
            'page_no' => 'VARCHAR(10) NULL',
            'book_no' => 'VARCHAR(50) NULL',
            'series_of' => 'VARCHAR(50) NULL',
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
    /**
     * Create a new employee record (INSERT only)
     *
     * IMPORTANT: This function performs an INSERT operation only.
     * It does NOT include the 'id' field (auto_increment primary key).
     * The 'id' field is automatically generated by MySQL (e.g., 14901, 14902, 14903).
     *
     * NOTE: 'id' is the primary key field (auto-increment).
     *       'employee_no' is a separate field (employee number like 24434).
     *
     * This function is ONLY used by Page 1 (add_employee.php).
     * Page 2 (add_employee_page2.php) uses UPDATE statements only with WHERE id = ?
     *
     * @param array $data Employee data (must NOT include 'id' field - it's auto-generated)
     * @return int|false Returns the auto-generated 'id' value on success, false on failure
     */
    function add_employee($data) {
        log_db_error('add_employee', 'Starting employee creation (INSERT only - id field auto-generated by MySQL)', ['input_data' => $data]);

        // Safety check: Ensure 'id' is not in the data array
        // The 'id' field is auto-increment and must never be manually set
        if (isset($data['id'])) {
            log_db_error('add_employee', 'WARNING: id field detected in data - removing it (auto_increment only)', []);
            unset($data['id']);
        }

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
        // IMPORTANT: 'id' field is NOT included - it's auto_increment primary key, generated by MySQL
        // NOTE: 'employee_no' is included (it's the employee number, different from 'id')
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
            // Use the same PDO connection for INSERT and lastInsertId()
            $pdo = get_db_connection();
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);

            // Check if statement executed successfully
            if ($result && $stmt !== false) {
                // Get the auto-generated 'id' value (primary key, auto-increment)
                // This 'id' value (e.g., 14901, 14902, 14903) is generated by MySQL
                // and must be used for all subsequent UPDATEs: WHERE id = ?
                $last_insert_id = $pdo->lastInsertId();

                log_db_error('add_employee', 'Employee created successfully with auto-generated id (primary key)', [
                    'auto_generated_id' => $last_insert_id,  // This is the 'id' field value
                    'employee_no' => $data['employee_no'],   // This is the 'employee_no' field (different from 'id')
                    'first_name' => $data['first_name'] ?? 'N/A',
                    'surname' => $data['surname'] ?? 'N/A'
                ]);

                // Return the auto-generated 'id' value instead of just true
                // This 'id' value will be used in Page 2 for: UPDATE employees SET ... WHERE id = ?
                return $last_insert_id > 0 ? $last_insert_id : false;
            } else {
                $error_info = $stmt->errorInfo();
                log_db_error('add_employee', 'Statement returned false', [
                    'sql' => $sql,
                    'params' => $params,
                    'error_info' => $error_info
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

// Fix employee auto-increment counter
// This resets the auto-increment to be one more than the maximum existing ID
if (!function_exists('fix_employee_auto_increment')) {
    function fix_employee_auto_increment() {
        try {
            $pdo = get_db_connection();

            // Get the maximum ID from the employees table
            $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) as max_id FROM employees");
            $result = $stmt->fetch();
            $max_id = (int)($result['max_id'] ?? 0);

            // Set the auto-increment to max_id + 1
            $new_auto_increment = $max_id + 1;
            $pdo->exec("ALTER TABLE employees AUTO_INCREMENT = {$new_auto_increment}");

            // Verify the fix
            $stmt = $pdo->query("SELECT AUTO_INCREMENT
                                 FROM information_schema.TABLES
                                 WHERE TABLE_SCHEMA = DATABASE()
                                 AND TABLE_NAME = 'employees'");
            $verify = $stmt->fetch();
            $actual_auto_increment = (int)($verify['AUTO_INCREMENT'] ?? 0);

            return [
                'success' => true,
                'max_id' => $max_id,
                'new_auto_increment' => $new_auto_increment,
                'actual_auto_increment' => $actual_auto_increment,
                'message' => "Auto-increment reset successfully. Next employee ID will be: {$new_auto_increment}"
            ];
        } catch (Exception $e) {
            log_db_error('fix_employee_auto_increment', 'Error fixing auto-increment', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Error fixing auto-increment: ' . $e->getMessage()
            ];
        }
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

        // Expired licenses - MySQL 8 strict mode can throw on DATE('') so validate in PHP instead
        // Fetch license dates and validate/count in PHP to avoid MySQL strict mode issues
        try {
            $sql = "SELECT license_exp_date
                    FROM employees
                    WHERE license_no IS NOT NULL
                    AND license_no != ''
                    AND license_exp_date IS NOT NULL
                    AND license_exp_date != ''
                    AND license_exp_date != '0000-00-00'";
            $stmt = execute_query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $expired_count = 0;
            $today = date('Y-m-d');
            
            foreach ($rows as $row) {
                $date_str = trim($row['license_exp_date'] ?? '');
                
                // Validate date format
                if (empty($date_str) || strlen($date_str) != 10 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
                    continue;
                }
                
                // Parse and validate date
                $date_parts = explode('-', $date_str);
                if (count($date_parts) != 3) {
                    continue;
                }
                
                $year = (int)$date_parts[0];
                $month = (int)$date_parts[1];
                $day = (int)$date_parts[2];
                
                if (!checkdate($month, $day, $year)) {
                    continue;
                }
                
                // Compare dates
                if ($date_str < $today) {
                    $expired_count++;
                }
            }
            
            $stats['expired_licenses'] = $expired_count;
        } catch (Exception $e) {
            error_log("Error counting expired licenses: " . $e->getMessage());
            $stats['expired_licenses'] = 0;
        }

        // Expiring licenses (next 30 days) - validate in PHP
        try {
            $sql = "SELECT license_exp_date
                    FROM employees
                    WHERE license_no IS NOT NULL
                    AND license_no != ''
                    AND license_exp_date IS NOT NULL
                    AND license_exp_date != ''
                    AND license_exp_date != '0000-00-00'";
            $stmt = execute_query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $expiring_count = 0;
            $today = date('Y-m-d');
            $future_date = date('Y-m-d', strtotime('+30 days'));
            
            foreach ($rows as $row) {
                $date_str = trim($row['license_exp_date'] ?? '');
                
                // Validate date format
                if (empty($date_str) || strlen($date_str) != 10 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
                    continue;
                }
                
                // Parse and validate date
                $date_parts = explode('-', $date_str);
                if (count($date_parts) != 3) {
                    continue;
                }
                
                $year = (int)$date_parts[0];
                $month = (int)$date_parts[1];
                $day = (int)$date_parts[2];
                
                if (!checkdate($month, $day, $year)) {
                    continue;
                }
                
                // Check if expiring within 30 days
                if ($date_str >= $today && $date_str <= $future_date) {
                    $expiring_count++;
                }
            }
            
            $stats['expiring_licenses'] = $expiring_count;
        } catch (Exception $e) {
            error_log("Error counting expiring licenses: " . $e->getMessage());
            $stats['expiring_licenses'] = 0;
        }

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

        // Sample data insertion removed - users should manage their own employee data
        // If you need to insert sample data for testing, use the goldenz_hr.sql file or run it manually

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
    function get_employee_alerts($status = 'active', $priority = null, $user_id = null) {
        // Build SELECT clause - include notification status columns only if user_id provided
        if ($user_id) {
            $sql = "SELECT ea.*, e.employee_no, e.surname, e.first_name, e.middle_name, e.post,
                            u1.name as created_by_name, u2.name as acknowledged_by_name,
                            ns.is_read, ns.is_dismissed, ns.read_at, ns.dismissed_at
                     FROM employee_alerts ea
                     JOIN employees e ON ea.employee_id = e.id
                     LEFT JOIN users u1 ON ea.created_by = u1.id
                     LEFT JOIN users u2 ON ea.acknowledged_by = u2.id
                     LEFT JOIN notification_status ns ON ea.id = ns.notification_id
                          AND ns.user_id = ? AND ns.notification_type = 'alert'
                     WHERE ea.status = ?";

            $params = [$user_id, $status];
        } else {
            $sql = "SELECT ea.*, e.employee_no, e.surname, e.first_name, e.middle_name, e.post,
                            u1.name as created_by_name, u2.name as acknowledged_by_name
                     FROM employee_alerts ea
                     JOIN employees e ON ea.employee_id = e.id
                     LEFT JOIN users u1 ON ea.created_by = u1.id
                     LEFT JOIN users u2 ON ea.acknowledged_by = u2.id
                     WHERE ea.status = ?";

            $params = [$status];
        }

        if ($priority) {
            $sql .= " AND ea.priority = ?";
            $params[] = $priority;
        }

        // Filter out dismissed notifications if user_id provided
        if ($user_id) {
            $sql .= " AND (ns.is_dismissed IS NULL OR ns.is_dismissed = 0)";
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
                 AND license_no != ''
                 AND license_exp_date IS NOT NULL
                 AND license_exp_date != ''
                 AND license_exp_date != '0000-00-00'
                 AND CHAR_LENGTH(TRIM(license_exp_date)) = 10
                 AND TRIM(license_exp_date) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
                 AND CASE 
                    WHEN CHAR_LENGTH(TRIM(COALESCE(license_exp_date, ''))) != 10 THEN NULL
                    WHEN TRIM(license_exp_date) NOT REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN NULL
                    ELSE STR_TO_DATE(TRIM(license_exp_date), '%Y-%m-%d')
                 END IS NOT NULL
                 AND CASE 
                    WHEN CHAR_LENGTH(TRIM(COALESCE(license_exp_date, ''))) != 10 THEN NULL
                    WHEN TRIM(license_exp_date) NOT REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN NULL
                    ELSE STR_TO_DATE(TRIM(license_exp_date), '%Y-%m-%d')
                 END BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
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
                $sql .= " AND al.action = ?";
                $params[] = $filters['action'];
            }

            if (!empty($filters['table_name'])) {
                $sql .= " AND al.table_name = ?";
                $params[] = $filters['table_name'];
            }

            if (!empty($filters['user_id'])) {
                $sql .= " AND al.user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['user_search'])) {
                $sql .= " AND (u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                $search_term = '%' . $filters['user_search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
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
                $sql .= " AND al.action = ?";
                $params[] = $filters['action'];
            }

            if (!empty($filters['table_name'])) {
                $sql .= " AND al.table_name = ?";
                $params[] = $filters['table_name'];
            }

            if (!empty($filters['user_id'])) {
                $sql .= " AND al.user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['user_search'])) {
                $sql .= " AND EXISTS (SELECT 1 FROM users u WHERE u.id = al.user_id AND (u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?))";
                $search_term = '%' . $filters['user_search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
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
// SYSTEM LOGS FUNCTIONS (Developer Dashboard)
// ========================================

// Log system event
if (!function_exists('log_system_event')) {
    function log_system_event($level, $message, $context = null, $metadata = null) {
        try {
            // Check if system_logs table exists
            $pdo = get_db_connection();
            $checkTable = $pdo->query("SHOW TABLES LIKE 'system_logs'");
            if ($checkTable->rowCount() === 0) {
                // Table doesn't exist, skip logging
                return false;
            }

            $user_id = $_SESSION['user_id'] ?? null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            if (is_array($metadata)) {
                $metadata = json_encode($metadata);
            }

            $sql = "INSERT INTO system_logs (level, message, context, user_id, ip_address, user_agent, metadata, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $params = [
                $level,
                $message,
                $context,
                $user_id,
                $ip_address,
                $user_agent,
                $metadata
            ];

            return execute_query($sql, $params);
        } catch (Exception $e) {
            // Silently fail - don't break the application if logging fails
            error_log("Error logging system event: " . $e->getMessage());
            return false;
        }
    }
}

// Log security event (enhanced version that also logs to database)
if (!function_exists('log_security_event_db')) {
    // Use global variable to prevent recursion between log_security_event and log_security_event_db
    $GLOBALS['_security_logging_in_progress'] = false;

    function log_security_event_db($type, $details, $metadata = null) {
        // Prevent infinite recursion - check global flag
        if (isset($GLOBALS['_security_logging_in_progress']) && $GLOBALS['_security_logging_in_progress']) {
            // If we're already logging, just write to file directly to prevent recursion
            $log_entry = date('Y-m-d H:i:s') . " - " . $type . " - " . $details . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            $log_file = __DIR__ . '/../storage/logs/security.log';
            if (!is_dir(dirname($log_file))) {
                $log_file = __DIR__ . '/../logs/security.log';
            }
            if (file_exists(dirname($log_file)) || mkdir(dirname($log_file), 0755, true)) {
                @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
            }
            return false;
        }

        $GLOBALS['_security_logging_in_progress'] = true;

        try {
            // Check if security_logs table exists (cache the result to avoid repeated queries)
            static $table_exists = null;
            static $table_checked = false;

            if (!$table_checked) {
                try {
                    $pdo = get_db_connection();
                    if ($pdo) {
                        // Use a more efficient query with error handling
                        $checkTable = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'security_logs'");
                        if ($checkTable) {
                            $result = $checkTable->fetch(PDO::FETCH_ASSOC);
                            $table_exists = ($result && isset($result['cnt']) && $result['cnt'] > 0);
                        } else {
                            $table_exists = false;
                        }
                        $table_checked = true;
                    } else {
                        $table_exists = false;
                        $table_checked = true;
                    }
                } catch (Exception $e) {
                    // If we can't check, assume table doesn't exist
                    $table_exists = false;
                    $table_checked = true;
                } catch (Error $e) {
                    // Handle fatal errors (like memory exhaustion)
                    $table_exists = false;
                    $table_checked = true;
                }
            }

            if (!$table_exists) {
                // Table doesn't exist, fallback to file logging directly (don't call log_security_event to avoid recursion)
                $GLOBALS['_security_logging_in_progress'] = false;
                // Write directly to file to avoid recursion
                $log_entry = date('Y-m-d H:i:s') . " - " . $type . " - " . $details . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
                $log_file = __DIR__ . '/../storage/logs/security.log';
                if (!is_dir(dirname($log_file))) {
                    $log_file = __DIR__ . '/../logs/security.log';
                }
                if (file_exists(dirname($log_file)) || mkdir(dirname($log_file), 0755, true)) {
                    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
                }
                return false;
            }

            $user_id = $_SESSION['user_id'] ?? null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            if (is_array($metadata)) {
                $metadata = json_encode($metadata);
            }

            $sql = "INSERT INTO security_logs (type, details, user_id, ip_address, user_agent, metadata, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $params = [
                $type,
                $details,
                $user_id,
                $ip_address,
                $user_agent,
                $metadata
            ];

            // Use direct PDO instead of execute_query to avoid potential recursion
            try {
                $pdo = get_db_connection();
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($params);
            } catch (PDOException $e) {
                // If database insert fails, just log to file
                $result = false;
            }

            // Also log to file as backup (but only if not already logging to avoid recursion)
            // Write directly to file instead of calling log_security_event
            $log_entry = date('Y-m-d H:i:s') . " - " . $type . " - " . $details . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            $log_file = __DIR__ . '/../storage/logs/security.log';
            if (!is_dir(dirname($log_file))) {
                $log_file = __DIR__ . '/../logs/security.log';
            }
            if (file_exists(dirname($log_file)) || mkdir(dirname($log_file), 0755, true)) {
                @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
            }

            $logging_in_progress = false;
            return $result;
        } catch (Exception $e) {
            $logging_in_progress = false;
            // Fallback to file logging directly (don't call log_security_event to avoid recursion)
            $log_entry = date('Y-m-d H:i:s') . " - " . $type . " - " . $details . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            $log_file = __DIR__ . '/../storage/logs/security.log';
            if (!is_dir(dirname($log_file))) {
                $log_file = __DIR__ . '/../logs/security.log';
            }
            if (file_exists(dirname($log_file)) || mkdir(dirname($log_file), 0755, true)) {
                @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
            }
            // Only log to error_log if it's not a memory issue
            if (strpos($e->getMessage(), 'memory') === false) {
                error_log("Error logging security event: " . $e->getMessage());
            }
            return false;
        }
    }
}

// Get system logs count
if (!function_exists('get_system_logs_count')) {
    function get_system_logs_count($filters = []) {
        try {
            $pdo = get_db_connection();
            $checkTable = $pdo->query("SHOW TABLES LIKE 'system_logs'");
            if ($checkTable->rowCount() === 0) {
                return 0;
            }

            $sql = "SELECT COUNT(*) as total FROM system_logs sl WHERE 1=1";
            $params = [];

            if (!empty($filters['level'])) {
                $sql .= " AND sl.level = ?";
                $params[] = $filters['level'];
            }

            if (!empty($filters['context'])) {
                $sql .= " AND sl.context = ?";
                $params[] = $filters['context'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (sl.message LIKE ? OR sl.context LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(sl.created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(sl.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            $stmt = execute_query($sql, $params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting system logs count: " . $e->getMessage());
            return 0;
        }
    }
}

// Get system logs
if (!function_exists('get_system_logs')) {
    function get_system_logs($filters = [], $limit = 50, $offset = 0) {
        try {
            $pdo = get_db_connection();
            $checkTable = $pdo->query("SHOW TABLES LIKE 'system_logs'");
            if ($checkTable->rowCount() === 0) {
                return [];
            }

            $sql = "SELECT sl.*, u.username, u.name as user_name
                    FROM system_logs sl
                    LEFT JOIN users u ON sl.user_id = u.id
                    WHERE 1=1";

            $params = [];

            if (!empty($filters['level'])) {
                $sql .= " AND sl.level = ?";
                $params[] = $filters['level'];
            }

            if (!empty($filters['context'])) {
                $sql .= " AND sl.context = ?";
                $params[] = $filters['context'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (sl.message LIKE ? OR sl.context LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(sl.created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(sl.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " ORDER BY sl.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = execute_query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting system logs: " . $e->getMessage());
            return [];
        }
    }
}

// Get security logs
if (!function_exists('get_security_logs')) {
    function get_security_logs($filters = [], $limit = 50, $offset = 0) {
        try {
            $pdo = get_db_connection();
            $checkTable = $pdo->query("SHOW TABLES LIKE 'security_logs'");
            if ($checkTable->rowCount() === 0) {
                return [];
            }

            $sql = "SELECT sl.*, u.username, u.name as user_name
                    FROM security_logs sl
                    LEFT JOIN users u ON sl.user_id = u.id
                    WHERE 1=1";

            $params = [];

            if (!empty($filters['type'])) {
                $sql .= " AND sl.type = ?";
                $params[] = $filters['type'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (sl.details LIKE ? OR sl.type LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(sl.created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(sl.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " ORDER BY sl.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = execute_query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting security logs: " . $e->getMessage());
            return [];
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

// ========================================
// SUPER ADMIN DASHBOARD STATISTICS
// ========================================

// Get comprehensive system-wide statistics for Super Admin dashboard
if (!function_exists('get_super_admin_stats')) {
    function get_super_admin_stats($filters = []) {
        $stats = [];

        try {
            $pdo = get_db_connection();

            // Date filters
            $date_from = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $date_to = $filters['date_to'] ?? date('Y-m-d');
            $role_filter = $filters['role'] ?? null;
            $status_filter = $filters['status'] ?? null;

            // Build WHERE clause for date filtering
            $date_where = "WHERE DATE(created_at) BETWEEN ? AND ?";
            $date_params = [$date_from, $date_to];

            // USER STATISTICS
            $user_where = "WHERE 1=1";
            $user_params = [];

            if ($role_filter) {
                $user_where .= " AND role = ?";
                $user_params[] = $role_filter;
            }

            if ($status_filter) {
                $user_where .= " AND status = ?";
                $user_params[] = $status_filter;
            }

            // Total users
            $sql = "SELECT COUNT(*) as total FROM users $user_where";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($user_params);
            $stats['total_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Users by role
            $sql = "SELECT role, COUNT(*) as count FROM users $user_where GROUP BY role";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($user_params);
            $stats['users_by_role'] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['users_by_role'][$row['role']] = (int)$row['count'];
            }

            // Active users
            $sql = "SELECT COUNT(*) as active FROM users $user_where AND status = 'active'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($user_params);
            $stats['active_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['active'];

            // Users logged in today
            $sql = "SELECT COUNT(*) as today FROM users $user_where AND DATE(last_login) = CURDATE()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($user_params);
            $stats['users_logged_in_today'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['today'];

            // EMPLOYEE STATISTICS
            $emp_where = "WHERE 1=1";
            $emp_params = [];

            if ($status_filter) {
                $emp_where .= " AND LOWER(status) = LOWER(?)";
                $emp_params[] = $status_filter;
            }

            // Total employees
            $sql = "SELECT COUNT(*) as total FROM employees $emp_where";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($emp_params);
            $stats['total_employees'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Active employees
            $sql = "SELECT COUNT(*) as active FROM employees $emp_where AND LOWER(status) = 'active'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($emp_params);
            $stats['active_employees'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['active'];

            // Employees by status (optimized - only counts, no full records)
            $sql = "SELECT status, COUNT(*) as count
                    FROM employees
                    GROUP BY status
                    ORDER BY count DESC
                    LIMIT 10";
            $stmt = $pdo->query($sql);
            $stats['employees_by_status'] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['employees_by_status'][$row['status']] = (int)$row['count'];
            }

            // Employees by type
            $sql = "SELECT employee_type, COUNT(*) as count FROM employees WHERE status = 'Active' GROUP BY employee_type";
            $stmt = $pdo->query($sql);
            $stats['employees_by_type'] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['employees_by_type'][$row['employee_type']] = (int)$row['count'];
            }

            // New employees in date range
            $sql = "SELECT COUNT(*) as new FROM employees WHERE DATE(created_at) BETWEEN ? AND ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$date_from, $date_to]);
            $stats['new_employees'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['new'];

            // AUDIT LOG STATISTICS
            $audit_where = $date_where;
            $audit_params = $date_params;

            // Total audit logs
            $sql = "SELECT COUNT(*) as total FROM audit_logs $audit_where";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($audit_params);
            $stats['total_audit_logs'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Audit logs by action
            $sql = "SELECT action, COUNT(*) as count FROM audit_logs $audit_where GROUP BY action ORDER BY count DESC LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($audit_params);
            $stats['audit_logs_by_action'] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['audit_logs_by_action'][$row['action']] = (int)$row['count'];
            }

            // Audit logs by table
            $sql = "SELECT table_name, COUNT(*) as count FROM audit_logs $audit_where GROUP BY table_name ORDER BY count DESC LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($audit_params);
            $stats['audit_logs_by_table'] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['audit_logs_by_table'][$row['table_name']] = (int)$row['count'];
            }

            // Recent audit activity (last 7 days)
            $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                    FROM audit_logs
                    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";
            $stmt = $pdo->query($sql);
            $stats['audit_activity_trend'] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['audit_activity_trend'][$row['date']] = (int)$row['count'];
            }

            // POST STATISTICS
            $sql = "SELECT COUNT(*) as total FROM posts";
            $stmt = $pdo->query($sql);
            $stats['total_posts'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // ALERT STATISTICS
            $sql = "SELECT COUNT(*) as total FROM employee_alerts WHERE status = 'active'";
            $stmt = $pdo->query($sql);
            $stats['active_alerts'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // LICENSE STATISTICS - parse safely (MySQL 8 strict mode can throw on DATE(''))
            // Validate in PHP to avoid MySQL strict mode issues
            try {
                $sql = "SELECT license_exp_date
                        FROM employees
                        WHERE license_no IS NOT NULL
                        AND license_no != ''
                        AND license_exp_date IS NOT NULL
                        AND license_exp_date != ''
                        AND license_exp_date != '0000-00-00'";
                $stmt = $pdo->query($sql);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $expired_count = 0;
                $expiring_count = 0;
                $today = date('Y-m-d');
                $future_date = date('Y-m-d', strtotime('+30 days'));
                
                foreach ($rows as $row) {
                    $date_str = trim($row['license_exp_date'] ?? '');
                    
                    // Validate date format
                    if (empty($date_str) || strlen($date_str) != 10 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
                        continue;
                    }
                    
                    // Parse and validate date
                    $date_parts = explode('-', $date_str);
                    if (count($date_parts) != 3) {
                        continue;
                    }
                    
                    $year = (int)$date_parts[0];
                    $month = (int)$date_parts[1];
                    $day = (int)$date_parts[2];
                    
                    if (!checkdate($month, $day, $year)) {
                        continue;
                    }
                    
                    // Count expired
                    if ($date_str < $today) {
                        $expired_count++;
                    }
                    // Count expiring within 30 days
                    elseif ($date_str >= $today && $date_str <= $future_date) {
                        $expiring_count++;
                    }
                }
                
                $stats['expired_licenses'] = $expired_count;
                $stats['expiring_licenses'] = $expiring_count;
            } catch (Exception $e) {
                error_log("Error counting license statistics: " . $e->getMessage());
                $stats['expired_licenses'] = 0;
                $stats['expiring_licenses'] = 0;
            }

            // SYSTEM ACTIVITY (from audit logs)
            $sql = "SELECT COUNT(DISTINCT user_id) as unique_users
                    FROM audit_logs
                    WHERE DATE(created_at) BETWEEN ? AND ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$date_from, $date_to]);
            $stats['active_users_period'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_users'];

        } catch (Exception $e) {
            error_log("Error in get_super_admin_stats: " . $e->getMessage());
            // Return empty stats on error
            return [
                'total_users' => 0,
                'active_users' => 0,
                'total_employees' => 0,
                'active_employees' => 0,
                'total_audit_logs' => 0,
                'users_by_role' => [],
                'employees_by_status' => [],
                'audit_logs_by_action' => [],
                'audit_activity_trend' => []
            ];
        }

        return $stats;
    }
}

// Get recent audit logs for dashboard
if (!function_exists('get_recent_audit_logs')) {
    function get_recent_audit_logs($limit = 10) {
        try {
            $sql = "SELECT al.*, u.name as user_name, u.username, u.role
                    FROM audit_logs al
                    LEFT JOIN users u ON al.user_id = u.id
                    ORDER BY al.created_at DESC
                    LIMIT ?";
            $stmt = execute_query($sql, [$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in get_recent_audit_logs: " . $e->getMessage());
            return [];
        }
    }
}

// Get security log statistics (from file)
if (!function_exists('get_security_log_stats')) {
    function get_security_log_stats($days = 7) {
        $stats = [
            'total_events' => 0,
            'login_attempts' => 0,
            'failed_logins' => 0,
            'account_locked' => 0,
            'recent_events' => []
        ];

        $log_file = __DIR__ . '/../storage/logs/security.log';
        if (!file_exists($log_file)) {
            return $stats;
        }

        try {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $cutoff_date = date('Y-m-d', strtotime("-$days days"));

            foreach ($lines as $line) {
                if (preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                    $log_date = $matches[1];
                    if ($log_date >= $cutoff_date) {
                        $stats['total_events']++;

                        if (stripos($line, 'Login Attempt') !== false || stripos($line, 'Login Success') !== false) {
                            $stats['login_attempts']++;
                        }
                        if (stripos($line, 'Login Failed') !== false) {
                            $stats['failed_logins']++;
                        }
                        if (stripos($line, 'Account Locked') !== false) {
                            $stats['account_locked']++;
                        }

                        // Keep last 20 events
                        if (count($stats['recent_events']) < 20) {
                            $stats['recent_events'][] = $line;
                        }
                    }
                }
            }

            // Reverse to show most recent first
            $stats['recent_events'] = array_reverse($stats['recent_events']);

        } catch (Exception $e) {
            error_log("Error reading security log: " . $e->getMessage());
        }

        return $stats;
    }
}

// ========================================
// USER MANAGEMENT FUNCTIONS
// ========================================

// Get all users with filters and pagination
if (!function_exists('get_all_users')) {
    function get_all_users($filters = [], $limit = 50, $offset = 0) {
        try {
            $pdo = get_db_connection();

            $where = "WHERE 1=1";
            $params = [];

            // Role filter
            if (!empty($filters['role'])) {
                $where .= " AND u.role = ?";
                $params[] = $filters['role'];
            }

            // Status filter
            if (!empty($filters['status'])) {
                $where .= " AND u.status = ?";
                $params[] = $filters['status'];
            }

            // Search filter (case-insensitive)
            if (!empty($filters['search'])) {
                $where .= " AND (LOWER(u.name) LIKE LOWER(?) OR LOWER(u.username) LIKE LOWER(?) OR LOWER(u.email) LIKE LOWER(?))";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }

            // Get total count - need to use same table alias structure
            $count_sql = "SELECT COUNT(*) FROM users u WHERE 1=1";
            $count_params = [];

            // Role filter for count
            if (!empty($filters['role'])) {
                $count_sql .= " AND u.role = ?";
                $count_params[] = $filters['role'];
            }

            // Status filter for count
            if (!empty($filters['status'])) {
                $count_sql .= " AND u.status = ?";
                $count_params[] = $filters['status'];
            }

            // Search filter for count (case-insensitive)
            if (!empty($filters['search'])) {
                $count_sql .= " AND (LOWER(u.name) LIKE LOWER(?) OR LOWER(u.username) LIKE LOWER(?) OR LOWER(u.email) LIKE LOWER(?))";
                $search_term = '%' . $filters['search'] . '%';
                $count_params[] = $search_term;
                $count_params[] = $search_term;
                $count_params[] = $search_term;
            }

            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute($count_params);
            $total = (int)$count_stmt->fetchColumn();

            // Get users
            $sql = "SELECT u.*,
                           creator.name as created_by_name,
                           e.first_name, e.surname, e.employee_no
                    FROM users u
                    LEFT JOIN users creator ON u.created_by = creator.id
                    LEFT JOIN employees e ON u.employee_id = e.id
                    $where
                    ORDER BY u.created_at DESC
                    LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'users' => $users,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error in get_all_users: " . $e->getMessage());
            return ['users' => [], 'total' => 0];
        }
    }
}

// Update user role
if (!function_exists('update_user_role')) {
    function update_user_role($user_id, $new_role, $updated_by = null) {
        try {
            $pdo = get_db_connection();

            // Validate role
            $valid_roles = ['super_admin', 'hr_admin', 'hr', 'admin', 'accounting', 'operation', 'logistics', 'employee', 'developer'];
            if (!in_array($new_role, $valid_roles)) {
                return ['success' => false, 'message' => 'Invalid role specified'];
            }

            // Get current user data for audit
            $current_user = $pdo->prepare("SELECT role, name, username FROM users WHERE id = ?");
            $current_user->execute([$user_id]);
            $user_data = $current_user->fetch(PDO::FETCH_ASSOC);

            if (!$user_data) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Update role
            $sql = "UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$new_role, $user_id]);

            if ($result) {
                // Log audit event
                if (function_exists('log_audit_event')) {
                    log_audit_event(
                        'USER_ROLE_UPDATED',
                        'users',
                        $user_id,
                        json_encode(['role' => $user_data['role']]),
                        json_encode(['role' => $new_role]),
                        $updated_by
                    );
                }

                return ['success' => true, 'message' => 'User role updated successfully'];
            }

            return ['success' => false, 'message' => 'Failed to update user role'];
        } catch (Exception $e) {
            error_log("Error in update_user_role: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Update user status
if (!function_exists('update_user_status')) {
    function update_user_status($user_id, $new_status, $updated_by = null) {
        try {
            $pdo = get_db_connection();

            // Validate status
            $valid_statuses = ['active', 'inactive', 'suspended'];
            if (!in_array($new_status, $valid_statuses)) {
                return ['success' => false, 'message' => 'Invalid status specified'];
            }

            // Get current user data for audit
            $current_user = $pdo->prepare("SELECT status, name, username FROM users WHERE id = ?");
            $current_user->execute([$user_id]);
            $user_data = $current_user->fetch(PDO::FETCH_ASSOC);

            if (!$user_data) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Prevent disabling own account
            if ($user_id == ($updated_by ?? $_SESSION['user_id'] ?? null) && $new_status !== 'active') {
                return ['success' => false, 'message' => 'You cannot disable your own account'];
            }

            // Update status
            $sql = "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$new_status, $user_id]);

            if ($result) {
                // If suspending, also lock the account
                if ($new_status === 'suspended') {
                    $lock_sql = "UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 1 YEAR) WHERE id = ?";
                    $lock_stmt = $pdo->prepare($lock_sql);
                    $lock_stmt->execute([$user_id]);
                } elseif ($new_status === 'active') {
                    // If activating, unlock the account
                    $unlock_sql = "UPDATE users SET locked_until = NULL, failed_login_attempts = 0 WHERE id = ?";
                    $unlock_stmt = $pdo->prepare($unlock_sql);
                    $unlock_stmt->execute([$user_id]);
                }

                // Log audit event
                if (function_exists('log_audit_event')) {
                    log_audit_event(
                        'USER_STATUS_UPDATED',
                        'users',
                        $user_id,
                        json_encode(['status' => $user_data['status']]),
                        json_encode(['status' => $new_status]),
                        $updated_by
                    );
                }

                return ['success' => true, 'message' => 'User status updated successfully'];
            }

            return ['success' => false, 'message' => 'Failed to update user status'];
        } catch (Exception $e) {
            error_log("Error in update_user_status: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Delete user (Super Admin only)
if (!function_exists('delete_user')) {
    function delete_user($user_id, $deleted_by = null) {
        try {
            $pdo = get_db_connection();

            $deleted_by = $deleted_by ?? ($_SESSION['user_id'] ?? null);

            if (!$user_id) {
                return ['success' => false, 'message' => 'Invalid user specified'];
            }

            // Prevent deleting own account
            if ($deleted_by && (int)$user_id === (int)$deleted_by) {
                return ['success' => false, 'message' => 'You cannot delete your own account'];
            }

            // Load target user
            $stmt = $pdo->prepare("SELECT id, role, name, username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Prevent deleting super_admin accounts (and especially last one)
            if (($target['role'] ?? '') === 'super_admin') {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
                $superAdminCount = (int)$countStmt->fetchColumn();
                if ($superAdminCount <= 1) {
                    return ['success' => false, 'message' => 'You cannot delete the last Super Admin account'];
                }
                return ['success' => false, 'message' => 'Super Admin accounts cannot be deleted'];
            }

            $pdo->beginTransaction();

            // Avoid FK issues for created_by references (if constraints exist)
            $nullCreatedBy = $pdo->prepare("UPDATE users SET created_by = NULL WHERE created_by = ?");
            $nullCreatedBy->execute([$user_id]);

            // Delete user
            $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$user_id]);

            if ($del->rowCount() < 1) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Failed to delete user'];
            }

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    'USER_DELETED',
                    'users',
                    $user_id,
                    json_encode(['role' => $target['role'], 'name' => $target['name'], 'username' => $target['username']]),
                    null,
                    $deleted_by
                );
            }

            $pdo->commit();
            return ['success' => true, 'message' => 'User deleted successfully'];
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error in delete_user: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Get user by ID
if (!function_exists('get_user_by_id')) {
    function get_user_by_id($user_id) {
        try {
            $pdo = get_db_connection();
            // Check if first_name and last_name columns exist
            $check_cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'first_name'");
            $has_first_name = $check_cols->rowCount() > 0;

            $check_cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_name'");
            $has_last_name = $check_cols->rowCount() > 0;

            // Build SELECT based on available columns
            $select_fields = "u.*";
            if ($has_first_name && $has_last_name) {
                // Columns exist, they'll be in u.*
            } else {
                // Fallback: use name field if first_name/last_name don't exist
            }

            $sql = "SELECT u.*,
                           creator.name as created_by_name,
                           e.first_name as employee_first_name,
                           e.surname as employee_surname,
                           e.employee_no
                    FROM users u
                    LEFT JOIN users creator ON u.created_by = creator.id
                    LEFT JOIN employees e ON u.employee_id = e.id
                    WHERE u.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // If first_name/last_name don't exist in users table, try to derive from name field
            if ($user && !$has_first_name && !empty($user['name'])) {
                $name_parts = preg_split('/\s+/', trim($user['name']), 2);
                $user['first_name'] = $name_parts[0] ?? '';
                $user['last_name'] = $name_parts[1] ?? '';
            }

            return $user;
        } catch (Exception $e) {
            error_log("Error in get_user_by_id: " . $e->getMessage());
            return null;
        }
    }
}

// Password policy helpers
if (!function_exists('get_password_policy')) {
    /**
     * Get global password policy.
     * Returns array with keys:
     * - min_length (int)
     * - require_special (bool)
     * - expiry_days (int)
     */
    function get_password_policy() {
        $defaults = [
            'min_length'      => 8,
            'require_special' => true,
            'expiry_days'     => 90,
        ];

        try {
            $pdo = get_db_connection();

            // Create table if it does not exist (simple key/value store)
            $pdo->exec("CREATE TABLE IF NOT EXISTS security_settings (
                `key` VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` VARCHAR(255) NULL,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $stmt = $pdo->query("SELECT `key`, `value` FROM security_settings");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];

            $policy = $defaults;

            if (isset($rows['password_min_length']) && is_numeric($rows['password_min_length'])) {
                $policy['min_length'] = max(4, (int)$rows['password_min_length']);
            }

            if (isset($rows['password_require_special'])) {
                $policy['require_special'] = $rows['password_require_special'] === '1';
            }

            if (isset($rows['password_expiry_days']) && is_numeric($rows['password_expiry_days'])) {
                $policy['expiry_days'] = max(0, (int)$rows['password_expiry_days']);
            }

            return $policy;
        } catch (Exception $e) {
            error_log("Error in get_password_policy: " . $e->getMessage());
            return $defaults;
        }
    }
}

if (!function_exists('update_password_policy')) {
    /**
     * Update global password policy.
     */
    function update_password_policy($min_length, $require_special, $expiry_days) {
        try {
            $pdo = get_db_connection();

            // Ensure table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS security_settings (
                `key` VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` VARCHAR(255) NULL,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $settings = [
                'password_min_length'      => (string)max(4, (int)$min_length),
                'password_require_special' => $require_special ? '1' : '0',
                'password_expiry_days'     => (string)max(0, (int)$expiry_days),
            ];

            $stmt = $pdo->prepare("INSERT INTO security_settings (`key`, `value`) VALUES (:key, :value)
                                   ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

            foreach ($settings as $key => $value) {
                $stmt->execute([':key' => $key, ':value' => $value]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in update_password_policy: " . $e->getMessage());
            return false;
        }
    }
}

// Backup settings helpers
if (!function_exists('get_backup_settings')) {
    /**
     * Get backup settings.
     * Returns array with keys:
     * - frequency (string: 'manual', 'daily', 'weekly')
     * - retention_days (int)
     * - backup_location (string)
     */
    function get_backup_settings() {
        $defaults = [
            'frequency' => 'daily',
            'retention_days' => 90,
            'backup_location' => 'storage/backups',
        ];

        try {
            $pdo = get_db_connection();

            // Create table if it does not exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS security_settings (
                `key` VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` VARCHAR(255) NULL,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $stmt = $pdo->query("SELECT `key`, `value` FROM security_settings WHERE `key` LIKE 'backup_%'");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];

            $settings = $defaults;

            if (isset($rows['backup_frequency'])) {
                $valid_frequencies = ['manual', 'daily', 'weekly'];
                if (in_array($rows['backup_frequency'], $valid_frequencies)) {
                    $settings['frequency'] = $rows['backup_frequency'];
                }
            }

            if (isset($rows['backup_retention_days']) && is_numeric($rows['backup_retention_days'])) {
                $settings['retention_days'] = max(1, (int)$rows['backup_retention_days']);
            }

            if (isset($rows['backup_location']) && !empty($rows['backup_location'])) {
                $settings['backup_location'] = $rows['backup_location'];
            }

            return $settings;
        } catch (Exception $e) {
            error_log("Error in get_backup_settings: " . $e->getMessage());
            return $defaults;
        }
    }
}

if (!function_exists('update_backup_settings')) {
    /**
     * Update backup settings.
     */
    function update_backup_settings($frequency, $retention_days, $backup_location) {
        try {
            $pdo = get_db_connection();

            // Ensure table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS security_settings (
                `key` VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` VARCHAR(255) NULL,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Validate frequency
            $valid_frequencies = ['manual', 'daily', 'weekly'];
            if (!in_array($frequency, $valid_frequencies)) {
                $frequency = 'daily';
            }

            // Validate retention days
            $retention_days = max(1, (int)$retention_days);

            // Sanitize backup location
            $backup_location = trim($backup_location);
            if (empty($backup_location)) {
                $backup_location = 'storage/backups';
            }

            $settings = [
                'backup_frequency' => $frequency,
                'backup_retention_days' => (string)$retention_days,
                'backup_location' => $backup_location,
            ];

            $stmt = $pdo->prepare("INSERT INTO security_settings (`key`, `value`) VALUES (:key, :value)
                                   ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

            foreach ($settings as $key => $value) {
                $stmt->execute([':key' => $key, ':value' => $value]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in update_backup_settings: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('create_database_backup')) {
    /**
     * Create a database backup.
     * Returns array with success status and file path or error message.
     */
    function create_database_backup() {
        try {
            $pdo = get_db_connection();
            $settings = get_backup_settings();

            // Get database config
            $db_config = [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'database' => $_ENV['DB_DATABASE'] ?? 'goldenz_hr',
            ];

            // Create backup directory if it doesn't exist
            $backup_dir = __DIR__ . '/../' . $settings['backup_location'];
            if (!file_exists($backup_dir)) {
                if (!mkdir($backup_dir, 0755, true)) {
                    return ['success' => false, 'message' => 'Failed to create backup directory'];
                }
            }

            // Generate backup filename
            $timestamp = date('Y-m-d_His');
            $filename = "backup_{$db_config['database']}_{$timestamp}.sql";
            $filepath = $backup_dir . '/' . $filename;

            // Use mysqldump if available (preferred method)
            $mysqldump_path = '';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - try common XAMPP paths
                $possible_paths = [
                    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                    'C:\\wamp\\bin\\mysql\\mysql' . (version_compare(PHP_VERSION, '7.0', '>=') ? '5.7' : '5.6') . '\\bin\\mysqldump.exe',
                    'mysqldump.exe', // If in PATH
                ];
                foreach ($possible_paths as $path) {
                    if (file_exists($path)) {
                        $mysqldump_path = $path;
                        break;
                    }
                }
            } else {
                // Linux/Unix
                $mysqldump_path = 'mysqldump'; // Assume in PATH
            }

            if (!empty($mysqldump_path) && (file_exists($mysqldump_path) || $mysqldump_path === 'mysqldump')) {
                // Use mysqldump
                $command = sprintf(
                    '"%s" --host=%s --user=%s --password=%s %s > "%s" 2>&1',
                    $mysqldump_path,
                    escapeshellarg($db_config['host']),
                    escapeshellarg($db_config['username']),
                    escapeshellarg($db_config['password']),
                    escapeshellarg($db_config['database']),
                    escapeshellarg($filepath)
                );

                exec($command, $output, $return_var);

                if ($return_var !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
                    // Fallback to PHP-based backup
                    return create_database_backup_php($pdo, $filepath, $db_config['database']);
                }
            } else {
                // Use PHP-based backup
                return create_database_backup_php($pdo, $filepath, $db_config['database']);
            }

            // Record backup in database
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS backup_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    filepath VARCHAR(500) NOT NULL,
                    file_size BIGINT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                $stmt = $pdo->prepare("INSERT INTO backup_history (filename, filepath, file_size) VALUES (?, ?, ?)");
                $stmt->execute([$filename, $settings['backup_location'] . '/' . $filename, filesize($filepath)]);
            } catch (Exception $e) {
                error_log("Error recording backup in database: " . $e->getMessage());
            }

            // Clean up old backups based on retention policy
            cleanup_old_backups();

            return [
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $filename,
                'filepath' => $settings['backup_location'] . '/' . $filename,
                'size' => filesize($filepath)
            ];
        } catch (Exception $e) {
            error_log("Error in create_database_backup: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create backup: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('create_database_backup_php')) {
    /**
     * Create database backup using PHP (fallback method).
     */
    function create_database_backup_php($pdo, $filepath, $database) {
        try {
            $output = "-- Golden Z-5 HR System Database Backup\n";
            $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- Database: {$database}\n\n";
            $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $output .= "SET time_zone = \"+00:00\";\n\n";

            // Get all tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $output .= "\n-- Table structure for table `{$table}`\n";
                $output .= "DROP TABLE IF EXISTS `{$table}`;\n";

                // Get table structure
                $create_table = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
                $output .= $create_table['Create Table'] . ";\n\n";

                // Get table data
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                if (count($rows) > 0) {
                    $output .= "-- Dumping data for table `{$table}`\n";
                    $output .= "LOCK TABLES `{$table}` WRITE;\n";

                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = $pdo->quote($value);
                            }
                        }
                        $output .= "INSERT INTO `{$table}` VALUES (" . implode(',', $values) . ");\n";
                    }

                    $output .= "UNLOCK TABLES;\n\n";
                }
            }

            if (file_put_contents($filepath, $output) === false) {
                return ['success' => false, 'message' => 'Failed to write backup file'];
            }

            return [
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => basename($filepath),
                'filepath' => str_replace(__DIR__ . '/../', '', $filepath),
                'size' => filesize($filepath)
            ];
        } catch (Exception $e) {
            error_log("Error in create_database_backup_php: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create backup: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('cleanup_old_backups')) {
    /**
     * Clean up old backups based on retention policy.
     */
    function cleanup_old_backups() {
        try {
            $settings = get_backup_settings();
            $retention_days = (int)$settings['retention_days'];

            if ($retention_days <= 0) {
                return; // Keep forever
            }

            $backup_dir = __DIR__ . '/../' . $settings['backup_location'];
            if (!is_dir($backup_dir)) {
                return;
            }

            $cutoff_time = time() - ($retention_days * 24 * 60 * 60);
            $deleted_count = 0;

            $files = glob($backup_dir . '/backup_*.sql');
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff_time) {
                    if (@unlink($file)) {
                        $deleted_count++;
                    }
                }
            }

            // Also clean up database records
            try {
                $pdo = get_db_connection();
                $pdo->exec("CREATE TABLE IF NOT EXISTS backup_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    filepath VARCHAR(500) NOT NULL,
                    file_size BIGINT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                $stmt = $pdo->prepare("DELETE FROM backup_history WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$retention_days]);
            } catch (Exception $e) {
                error_log("Error cleaning up backup history: " . $e->getMessage());
            }

            return $deleted_count;
        } catch (Exception $e) {
            error_log("Error in cleanup_old_backups: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('get_backup_list')) {
    /**
     * Get list of available backups.
     */
    function get_backup_list() {
        try {
            $settings = get_backup_settings();
            $backup_dir = __DIR__ . '/../' . $settings['backup_location'];

            if (!is_dir($backup_dir)) {
                return [];
            }

            $backups = [];
            $files = glob($backup_dir . '/backup_*.sql');

            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'filepath' => $settings['backup_location'] . '/' . basename($file),
                    'size' => filesize($file),
                    'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                    'timestamp' => filemtime($file)
                ];
            }

            // Sort by timestamp descending (newest first)
            usort($backups, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            return $backups;
        } catch (Exception $e) {
            error_log("Error in get_backup_list: " . $e->getMessage());
            return [];
        }
    }
}

// Create new user
// Generate secure password with mixed case, numbers, and symbols
if (!function_exists('generate_secure_password')) {
    function generate_secure_password($length = 12) {
        // Character sets
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        // Ensure at least one character from each set
        $password = '';
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Fill the rest with random characters from all sets
        $all_chars = $lowercase . $uppercase . $numbers . $symbols;
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $all_chars[random_int(0, strlen($all_chars) - 1)];
        }

        // Shuffle to avoid predictable pattern
        return str_shuffle($password);
    }
}

// Send new user credentials email
if (!function_exists('send_new_user_credentials_email')) {
    function send_new_user_credentials_email($email, $username, $password, $first_name = '', $last_name = '') {
        try {
            // Load PHPMailer
            $phpmailer_path = __DIR__ . '/../config/vendor/autoload.php';
            if (!file_exists($phpmailer_path)) {
                error_log('PHPMailer not found at: ' . $phpmailer_path);
                return false;
            }

            require_once $phpmailer_path;

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Get SMTP configuration from environment
            $smtp_host = $_ENV['SMTP_HOST'] ?? null;
            $smtp_username = $_ENV['SMTP_USERNAME'] ?? null;
            $smtp_password = $_ENV['SMTP_PASSWORD'] ?? null;
            $smtp_port = $_ENV['SMTP_PORT'] ?? '587';
            $smtp_encryption_raw = $_ENV['SMTP_ENCRYPTION'] ?? 'tls';
            $mail_from_address = $_ENV['MAIL_FROM_ADDRESS'] ?? null;
            $mail_from_name = $_ENV['MAIL_FROM_NAME'] ?? 'Golden Z-5 HR System';

            if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password) || empty($mail_from_address)) {
                error_log('SMTP configuration incomplete. Cannot send new user credentials email.');
                return false;
            }

            // Map encryption string to PHPMailer constant
            $smtp_encryption = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            if (strtolower($smtp_encryption_raw) === 'ssl') {
                $smtp_encryption = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }

            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
            $mail->SMTPSecure = $smtp_encryption;
            $mail->Port = (int)$smtp_port;
            $mail->CharSet = 'UTF-8';

            // Email content
            $mail->setFrom($mail_from_address, $mail_from_name);
            $mail->addAddress($email);
            $mail->Subject = 'Your Golden Z-5 HR System Account Credentials';

            // Build user name
            $user_name = trim(($first_name ?? '') . ' ' . ($last_name ?? ''));
            if (empty($user_name)) {
                $user_name = $username;
            }

            // Login URL
            $login_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
                        '://' . $_SERVER['HTTP_HOST'] .
                        dirname(dirname($_SERVER['PHP_SELF'])) .
                        '/landing/index.php';

            // HTML body
            $mail->isHTML(true);
            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e293b 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                        .credentials { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #1e3a8a; }
                        .credential-item { margin: 10px 0; }
                        .label { font-weight: bold; color: #1e3a8a; }
                        .value { font-family: monospace; font-size: 14px; background: #f1f3f5; padding: 8px; border-radius: 4px; }
                        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0; }
                        .button { display: inline-block; background: #1e3a8a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                        .footer { text-align: center; color: #6c757d; font-size: 12px; margin-top: 30px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Welcome to Golden Z-5 HR System</h2>
                        </div>
                        <div class='content'>
                            <p>Hello " . htmlspecialchars($user_name) . ",</p>
                            <p>Your account has been created successfully. Please find your login credentials below:</p>

                            <div class='credentials'>
                                <div class='credential-item'>
                                    <span class='label'>Username:</span><br>
                                    <span class='value'>" . htmlspecialchars($username) . "</span>
                                </div>
                                <div class='credential-item'>
                                    <span class='label'>Temporary Password:</span><br>
                                    <span class='value'>" . htmlspecialchars($password) . "</span>
                                </div>
                            </div>

                            <div class='warning'>
                                <strong>⚠️ Important Security Notice:</strong><br>
                                For your security, you <strong>must change your password</strong> immediately after your first login.
                                You will be prompted to change your password when you log in for the first time.
                            </div>

                            <p>
                                <a href='" . htmlspecialchars($login_url) . "' class='button'>Login to Your Account</a>
                            </p>

                            <p>If you have any questions or need assistance, please contact your system administrator.</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message. Please do not reply to this email.</p>
                            <p>&copy; " . date('Y') . " Golden Z-5 HR System. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            // Plain text alternative
            $mail->AltBody = "Hello " . $user_name . ",\n\n" .
                           "Your account has been created successfully.\n\n" .
                           "Login Credentials:\n" .
                           "Username: " . $username . "\n" .
                           "Temporary Password: " . $password . "\n\n" .
                           "IMPORTANT: You must change your password immediately after your first login.\n\n" .
                           "Login URL: " . $login_url . "\n\n" .
                           "If you have any questions, please contact your system administrator.\n\n" .
                           "This is an automated message. Please do not reply to this email.";

            // Send email
            if (!$mail->send()) {
                error_log('Failed to send new user credentials email: ' . $mail->ErrorInfo);
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log('Error sending new user credentials email: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('create_user')) {
    function create_user($user_data, $created_by = null) {
        try {
            $pdo = get_db_connection();

            // Check if first_name and last_name columns exist
            $check_first_name = $pdo->query("SHOW COLUMNS FROM users LIKE 'first_name'");
            $check_last_name = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_name'");
            $has_first_name = $check_first_name->rowCount() > 0;
            $has_last_name = $check_last_name->rowCount() > 0;

            // Validate required fields (password is now auto-generated, so not required)
            if ($has_first_name && $has_last_name) {
                // Use first_name and last_name if columns exist
                $required_fields = ['username', 'email', 'first_name', 'last_name', 'role'];
            } else {
                // Fallback to name field for backward compatibility
                $required_fields = ['username', 'email', 'name', 'role'];
            }

            foreach ($required_fields as $field) {
                if (empty($user_data[$field])) {
                    return ['success' => false, 'message' => "Field '{$field}' is required"];
                }
            }

            // Generate password if not provided
            if (empty($user_data['password'])) {
                $user_data['password'] = generate_secure_password();
            }

            // Validate role
            $valid_roles = ['super_admin', 'hr_admin', 'hr', 'admin', 'accounting', 'operation', 'logistics', 'employee', 'developer'];
            if (!in_array($user_data['role'], $valid_roles)) {
                return ['success' => false, 'message' => 'Invalid role specified'];
            }

            // Validate status
            $valid_statuses = ['active', 'inactive', 'suspended'];
            $status = $user_data['status'] ?? 'active';
            if (!in_array($status, $valid_statuses)) {
                $status = 'active';
            }

            // Check if username already exists
            $check_username = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check_username->execute([$user_data['username']]);
            if ($check_username->fetch()) {
                return ['success' => false, 'message' => 'Username already exists'];
            }

            // Check if email already exists
            $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->execute([$user_data['email']]);
            if ($check_email->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Validate employee_id if provided
            $employee_id = null;
            if (!empty($user_data['employee_id'])) {
                $check_employee = $pdo->prepare("SELECT id FROM employees WHERE id = ?");
                $check_employee->execute([$user_data['employee_id']]);
                if (!$check_employee->fetch()) {
                    return ['success' => false, 'message' => 'Invalid Employee ID - employee does not exist'];
                }
                $employee_id = (int)$user_data['employee_id'];
            }

            // Hash password
            $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);

            // Prepare first_name and last_name values
            $first_name = $has_first_name ? trim($user_data['first_name'] ?? '') : null;
            $last_name = $has_last_name ? trim($user_data['last_name'] ?? '') : null;

            // For backward compatibility, also set name field (concatenate first_name + last_name)
            $full_name = null;
            if ($has_first_name && $has_last_name) {
                $full_name = trim(($first_name ?? '') . ' ' . ($last_name ?? ''));
            } elseif (isset($user_data['name'])) {
                $full_name = trim($user_data['name']);
            }

            // Store plain password for email (before hashing)
            $plain_password = $user_data['password'];

            // Prepare insert statement (let MySQL handle timestamps automatically)
            // Set password_changed_at to NULL to force password change on first login
            if ($has_first_name && $has_last_name) {
                // Use first_name and last_name columns
                $sql = "INSERT INTO users (
                            username,
                            email,
                            password_hash,
                            name,
                            first_name,
                            last_name,
                            role,
                            status,
                            employee_id,
                            department,
                            phone,
                            password_changed_at,
                            created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)";
            } else {
                // Fallback to name field only
                $sql = "INSERT INTO users (
                            username,
                            email,
                            password_hash,
                            name,
                            role,
                            status,
                            employee_id,
                            department,
                            phone,
                            password_changed_at,
                            created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)";
            }

            // Convert empty strings to null for optional fields
            $department = (!empty($user_data['department']) && trim($user_data['department']) !== '') ? trim($user_data['department']) : null;
            $phone = (!empty($user_data['phone']) && trim($user_data['phone']) !== '') ? trim($user_data['phone']) : null;

            // Prepare parameters based on which columns exist
            if ($has_first_name && $has_last_name) {
                $params = [
                    $user_data['username'],
                    $user_data['email'],
                    $password_hash,
                    $full_name, // name field for backward compatibility
                    $first_name,
                    $last_name,
                    $user_data['role'],
                    $status,
                    $employee_id,
                    $department,
                    $phone,
                    $created_by
                ];
            } else {
                $params = [
                    $user_data['username'],
                    $user_data['email'],
                    $password_hash,
                    $full_name,
                    $user_data['role'],
                    $status,
                    $employee_id,
                    $department,
                    $phone,
                    $created_by
                ];
            }

            $stmt = $pdo->prepare($sql);

            // Execute with error handling
            try {
                $result = $stmt->execute($params);

                // Check for execution errors
                if (!$result) {
                    $error_info = $stmt->errorInfo();
                    error_log("PDO Execute Error in create_user: " . print_r($error_info, true));
                    return ['success' => false, 'message' => 'Database error: Failed to execute query'];
                }
            } catch (PDOException $e) {
                error_log("PDO Exception in create_user: " . $e->getMessage());
                error_log("PDO Error Code: " . $e->getCode());
                error_log("SQL: " . $sql);
                error_log("Params: " . print_r($params, true));
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }

            // Check if insert was successful
            // If execute() returned true and no exception was thrown, the insert succeeded
            if (!$result) {
                $error_info = $stmt->errorInfo();
                error_log("create_user: Execute returned false");
                error_log("PDO Error Info: " . print_r($error_info, true));
                return ['success' => false, 'message' => 'Failed to create user - execution returned false. Check logs for details.'];
            }

            // Get the inserted user ID - try multiple methods
            $new_user_id = $pdo->lastInsertId();

            // If lastInsertId() returns 0 or false, query the database to get the ID
            // This can happen in some MySQL configurations or if there are connection issues
            if (!$new_user_id || $new_user_id == 0) {
                // Query for the newly created user by username/email (both are unique)
                $verify_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $verify_stmt->execute([$user_data['username']]);
                $verified_user = $verify_stmt->fetch(PDO::FETCH_ASSOC);

                if ($verified_user && isset($verified_user['id'])) {
                    $new_user_id = (int)$verified_user['id'];
                } else {
                    // Try by email as fallback
                    $verify_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                    $verify_stmt->execute([$user_data['email']]);
                    $verified_user = $verify_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($verified_user && isset($verified_user['id'])) {
                        $new_user_id = (int)$verified_user['id'];
                    }
                }
            }

            // If we still don't have an ID, but execute() returned true,
            // the insert likely succeeded but we can't get the ID
            // In this case, we'll still return success but log a warning
            if (!$new_user_id || $new_user_id == 0) {
                error_log("create_user: Warning - Insert executed successfully but could not retrieve user ID");
                error_log("Username: " . $user_data['username']);
                error_log("Email: " . $user_data['email']);
                // Still return success since execute() returned true
                $new_user_id = null;
            }

            // Send credentials email to new user
            if ($new_user_id) {
                $email_sent = send_new_user_credentials_email(
                    $user_data['email'],
                    $user_data['username'],
                    $plain_password,
                    $first_name ?? '',
                    $last_name ?? ''
                );

                if (!$email_sent) {
                    error_log("Warning: Failed to send credentials email to {$user_data['email']} for user {$user_data['username']}");
                }
            }

            // Log audit event if we have a user ID
            if ($new_user_id && function_exists('log_audit_event')) {
                log_audit_event(
                    'USER_CREATED',
                    'users',
                    $new_user_id,
                    null,
                    json_encode([
                        'username' => $user_data['username'],
                        'email' => $user_data['email'],
                        'name' => $full_name ?? ($user_data['name'] ?? ''),
                        'first_name' => $first_name ?? '',
                        'last_name' => $last_name ?? '',
                        'role' => $user_data['role'],
                        'status' => $status
                    ]),
                    $created_by
                );
            }

            // Log security event
            if (function_exists('log_security_event')) {
                $display_name = $full_name ?? ($user_data['name'] ?? ($first_name . ' ' . $last_name));
                log_security_event('User Created', "New user created: {$user_data['username']} ({$display_name}) - Role: {$user_data['role']} - Created by: " . ($created_by ?? 'System'));
            }

            // Build success message
            $message = 'User created successfully';
            if (isset($email_sent) && $email_sent) {
                $message .= '. Credentials have been sent to ' . htmlspecialchars($user_data['email']);
            } elseif (isset($email_sent) && !$email_sent) {
                $message .= '. Warning: Failed to send credentials email. Please contact the user manually.';
            }

            return [
                'success' => true,
                'message' => $message,
                'user_id' => $new_user_id
            ];
        } catch (PDOException $e) {
            error_log("PDO Exception in create_user: " . $e->getMessage());
            error_log("PDO Error Code: " . $e->getCode());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Exception in create_user: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

// ========================================
// SUPPORT TICKETS SYSTEM
// ========================================

// Create support tickets table
if (!function_exists('create_support_tickets_table')) {
    function create_support_tickets_table() {
        try {
            $pdo = get_db_connection();

            // Create support_tickets table
            $sql = "CREATE TABLE IF NOT EXISTS support_tickets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_no VARCHAR(20) NOT NULL UNIQUE,
                user_id INT NULL,
                user_name VARCHAR(100) NOT NULL,
                user_email VARCHAR(100) NULL,
                user_role VARCHAR(50) NULL,
                category ENUM('system_issue', 'access_request', 'data_issue', 'feature_request', 'general_inquiry', 'bug_report') DEFAULT 'general_inquiry',
                priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                subject VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                status ENUM('open', 'in_progress', 'pending_user', 'resolved', 'closed') DEFAULT 'open',
                assigned_to INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                resolved_at TIMESTAMP NULL,
                INDEX idx_status (status),
                INDEX idx_priority (priority),
                INDEX idx_user_id (user_id),
                INDEX idx_ticket_no (ticket_no)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);

            // Create ticket_replies table
            $sql = "CREATE TABLE IF NOT EXISTS ticket_replies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT NOT NULL,
                user_id INT NULL,
                user_name VARCHAR(100) NOT NULL,
                user_role VARCHAR(50) NULL,
                message TEXT NOT NULL,
                is_internal TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
                INDEX idx_ticket_id (ticket_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);

            return true;
        } catch (Exception $e) {
            error_log("Error creating support tickets tables: " . $e->getMessage());
            return false;
        }
    }
}

// Generate unique ticket number
if (!function_exists('generate_ticket_number')) {
    function generate_ticket_number() {
        $prefix = 'TKT';
        $date = date('ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        return $prefix . $date . $random;
    }
}

// Get all support tickets with filters
if (!function_exists('get_support_tickets')) {
    function get_support_tickets($filters = [], $limit = 50, $offset = 0) {
        try {
            $pdo = get_db_connection();

            $where = "WHERE 1=1";
            $params = [];

            if (!empty($filters['status'])) {
                $where .= " AND t.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['priority'])) {
                $where .= " AND t.priority = ?";
                $params[] = $filters['priority'];
            }

            if (!empty($filters['category'])) {
                $where .= " AND t.category = ?";
                $params[] = $filters['category'];
            }

            if (!empty($filters['search'])) {
                $where .= " AND (t.ticket_no LIKE ? OR t.subject LIKE ? OR t.user_name LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }

            // Get total count
            $count_sql = "SELECT COUNT(*) FROM support_tickets t $where";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $total = (int)$count_stmt->fetchColumn();

            // Get tickets
            $sql = "SELECT t.*,
                           a.name as assigned_to_name,
                           (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id) as reply_count
                    FROM support_tickets t
                    LEFT JOIN users a ON t.assigned_to = a.id
                    $where
                    ORDER BY
                        CASE t.priority
                            WHEN 'urgent' THEN 1
                            WHEN 'high' THEN 2
                            WHEN 'medium' THEN 3
                            WHEN 'low' THEN 4
                        END,
                        t.created_at DESC
                    LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'tickets' => $tickets,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error in get_support_tickets: " . $e->getMessage());
            return ['tickets' => [], 'total' => 0];
        }
    }
}

// Get single ticket with replies
if (!function_exists('get_ticket_by_id')) {
    function get_ticket_by_id($ticket_id) {
        try {
            $pdo = get_db_connection();

            // Get ticket
            $sql = "SELECT t.*, a.name as assigned_to_name
                    FROM support_tickets t
                    LEFT JOIN users a ON t.assigned_to = a.id
                    WHERE t.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                return null;
            }

            // Get replies
            $sql = "SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ticket_id]);
            $ticket['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $ticket;
        } catch (Exception $e) {
            error_log("Error in get_ticket_by_id: " . $e->getMessage());
            return null;
        }
    }
}

// Create new support ticket
if (!function_exists('create_support_ticket')) {
    function create_support_ticket($data) {
        try {
            $pdo = get_db_connection();

            $ticket_no = generate_ticket_number();

            $sql = "INSERT INTO support_tickets
                    (ticket_no, user_id, user_name, user_email, user_role, category, priority, subject, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $ticket_no,
                $data['user_id'] ?? null,
                $data['user_name'],
                $data['user_email'] ?? null,
                $data['user_role'] ?? null,
                $data['category'] ?? 'general_inquiry',
                $data['priority'] ?? 'medium',
                $data['subject'],
                $data['description']
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'ticket_id' => $pdo->lastInsertId(),
                    'ticket_no' => $ticket_no,
                    'message' => "Ticket $ticket_no created successfully"
                ];
            }

            return ['success' => false, 'message' => 'Failed to create ticket'];
        } catch (Exception $e) {
            error_log("Error in create_support_ticket: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Add reply to ticket
if (!function_exists('add_ticket_reply')) {
    function add_ticket_reply($ticket_id, $data) {
        try {
            $pdo = get_db_connection();

            $sql = "INSERT INTO ticket_replies
                    (ticket_id, user_id, user_name, user_role, message, is_internal)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $ticket_id,
                $data['user_id'] ?? null,
                $data['user_name'],
                $data['user_role'] ?? null,
                $data['message'],
                $data['is_internal'] ?? 0
            ]);

            if ($result) {
                // Update ticket's updated_at timestamp
                $update_sql = "UPDATE support_tickets SET updated_at = NOW() WHERE id = ?";
                $pdo->prepare($update_sql)->execute([$ticket_id]);

                return ['success' => true, 'message' => 'Reply added successfully'];
            }

            return ['success' => false, 'message' => 'Failed to add reply'];
        } catch (Exception $e) {
            error_log("Error in add_ticket_reply: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Update ticket status
if (!function_exists('update_ticket_status')) {
    function update_ticket_status($ticket_id, $status, $updated_by = null) {
        try {
            $pdo = get_db_connection();

            $valid_statuses = ['open', 'in_progress', 'pending_user', 'resolved', 'closed'];
            if (!in_array($status, $valid_statuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }

            $resolved_at = in_array($status, ['resolved', 'closed']) ? 'NOW()' : 'NULL';

            $sql = "UPDATE support_tickets
                    SET status = ?, resolved_at = $resolved_at, updated_at = NOW()
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$status, $ticket_id]);

            if ($result) {
                // Log the status change as a system reply
                if ($updated_by) {
                    add_ticket_reply($ticket_id, [
                        'user_id' => $updated_by['id'] ?? null,
                        'user_name' => $updated_by['name'] ?? 'System',
                        'user_role' => $updated_by['role'] ?? 'system',
                        'message' => "Status changed to: " . ucfirst(str_replace('_', ' ', $status)),
                        'is_internal' => 1
                    ]);
                }

                return ['success' => true, 'message' => 'Ticket status updated'];
            }

            return ['success' => false, 'message' => 'Failed to update status'];
        } catch (Exception $e) {
            error_log("Error in update_ticket_status: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Assign ticket to user
if (!function_exists('assign_ticket')) {
    function assign_ticket($ticket_id, $assigned_to, $updated_by = null) {
        try {
            $pdo = get_db_connection();

            $sql = "UPDATE support_tickets SET assigned_to = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$assigned_to, $ticket_id]);

            if ($result) {
                return ['success' => true, 'message' => 'Ticket assigned successfully'];
            }

            return ['success' => false, 'message' => 'Failed to assign ticket'];
        } catch (Exception $e) {
            error_log("Error in assign_ticket: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Get ticket statistics
if (!function_exists('get_ticket_stats')) {
    function get_ticket_stats() {
        try {
            $pdo = get_db_connection();

            $stats = [];

            // By status
            $sql = "SELECT status, COUNT(*) as count FROM support_tickets GROUP BY status";
            $stmt = $pdo->query($sql);
            $stats['by_status'] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['by_status'][$row['status']] = (int)$row['count'];
            }

            // By priority
            $sql = "SELECT priority, COUNT(*) as count FROM support_tickets WHERE status NOT IN ('resolved', 'closed') GROUP BY priority";
            $stmt = $pdo->query($sql);
            $stats['by_priority'] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['by_priority'][$row['priority']] = (int)$row['count'];
            }

            // Total open
            $sql = "SELECT COUNT(*) FROM support_tickets WHERE status NOT IN ('resolved', 'closed')";
            $stats['open_tickets'] = (int)$pdo->query($sql)->fetchColumn();

            // Total today
            $sql = "SELECT COUNT(*) FROM support_tickets WHERE DATE(created_at) = CURDATE()";
            $stats['today'] = (int)$pdo->query($sql)->fetchColumn();

            // Urgent/High priority needing attention
            $sql = "SELECT COUNT(*) FROM support_tickets WHERE status NOT IN ('resolved', 'closed') AND priority IN ('urgent', 'high')";
            $stats['urgent_high'] = (int)$pdo->query($sql)->fetchColumn();

            return $stats;
        } catch (Exception $e) {
            error_log("Error in get_ticket_stats: " . $e->getMessage());
            return [
                'by_status' => [],
                'by_priority' => [],
                'open_tickets' => 0,
                'today' => 0,
                'urgent_high' => 0
            ];
        }
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

// Create support tickets tables
if (function_exists('create_support_tickets_table')) {
    create_support_tickets_table();
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

/**
 * Get expiring and expired licenses with notification status
 * @param int $user_id Current user ID for notification status
 * @param int $days_threshold Days ahead to check for expiring licenses (default 60)
 * @return array Array of license notifications
 */
if (!function_exists('get_license_notifications')) {
    function get_license_notifications($user_id = null, $days_threshold = 60) {
        $today = date('Y-m-d');
        $threshold_date = date('Y-m-d', strtotime("+{$days_threshold} days"));

        $sql = "SELECT
                    e.id as employee_id,
                    e.employee_no,
                    e.surname,
                    e.first_name,
                    e.middle_name,
                    e.post,
                    e.license_no,
                    e.license_exp_date,
                    DATEDIFF(e.license_exp_date, ?) as days_until_expiry,
                    CASE
                        WHEN e.license_exp_date < ? THEN 'expired'
                        WHEN DATEDIFF(e.license_exp_date, ?) <= 7 THEN 'urgent'
                        WHEN DATEDIFF(e.license_exp_date, ?) <= 15 THEN 'high'
                        WHEN DATEDIFF(e.license_exp_date, ?) <= 30 THEN 'medium'
                        ELSE 'low'
                    END as priority,
                    ns.is_read,
                    ns.is_dismissed,
                    ns.read_at,
                    ns.dismissed_at
                FROM employees e";

        if ($user_id) {
            $sql .= " LEFT JOIN notification_status ns ON
                      CONCAT('license_', e.id) = ns.notification_id
                      AND ns.user_id = ?
                      AND ns.notification_type = 'license'";
        }

        $sql .= " WHERE e.status = 'Active'
                  AND e.license_exp_date IS NOT NULL
                  AND e.license_exp_date <= ?";

        if ($user_id) {
            $sql .= " AND (ns.is_dismissed IS NULL OR ns.is_dismissed = 0)";
        }

        $sql .= " ORDER BY e.license_exp_date ASC";

        $params = [$today, $today, $today, $today, $today];
        if ($user_id) {
            $params[] = $user_id;
        }
        $params[] = $threshold_date;

        $stmt = execute_query($sql, $params);
        return $stmt->fetchAll();
    }
}

/**
 * Get expiring and expired clearances (RLM) with notification status
 * @param int $user_id Current user ID for notification status
 * @param int $days_threshold Days ahead to check for expiring clearances (default 60)
 * @return array Array of clearance notifications
 */
if (!function_exists('get_clearance_notifications')) {
    function get_clearance_notifications($user_id = null, $days_threshold = 60) {
        $today = date('Y-m-d');
        $threshold_date = date('Y-m-d', strtotime("+{$days_threshold} days"));

        $sql = "SELECT
                    e.id as employee_id,
                    e.employee_no,
                    e.surname,
                    e.first_name,
                    e.middle_name,
                    e.post,
                    e.rlm_exp,
                    STR_TO_DATE(e.rlm_exp, '%m/%d/%Y') as rlm_exp_date,
                    DATEDIFF(STR_TO_DATE(e.rlm_exp, '%m/%d/%Y'), ?) as days_until_expiry,
                    CASE
                        WHEN STR_TO_DATE(e.rlm_exp, '%m/%d/%Y') < ? THEN 'expired'
                        WHEN DATEDIFF(STR_TO_DATE(e.rlm_exp, '%m/%d/%Y'), ?) <= 14 THEN 'urgent'
                        WHEN DATEDIFF(STR_TO_DATE(e.rlm_exp, '%m/%d/%Y'), ?) <= 30 THEN 'high'
                        WHEN DATEDIFF(STR_TO_DATE(e.rlm_exp, '%m/%d/%Y'), ?) <= 45 THEN 'medium'
                        ELSE 'low'
                    END as priority,
                    ns.is_read,
                    ns.is_dismissed,
                    ns.read_at,
                    ns.dismissed_at
                FROM employees e";

        if ($user_id) {
            $sql .= " LEFT JOIN notification_status ns ON
                      CONCAT('clearance_', e.id) = ns.notification_id
                      AND ns.user_id = ?
                      AND ns.notification_type = 'clearance'";
        }

        $sql .= " WHERE e.status = 'Active'
                  AND e.rlm_exp IS NOT NULL
                  AND e.rlm_exp != ''
                  AND STR_TO_DATE(e.rlm_exp, '%m/%d/%Y') IS NOT NULL
                  AND STR_TO_DATE(e.rlm_exp, '%m/%d/%Y') <= ?";

        if ($user_id) {
            $sql .= " AND (ns.is_dismissed IS NULL OR ns.is_dismissed = 0)";
        }

        $sql .= " ORDER BY STR_TO_DATE(e.rlm_exp, '%m/%d/%Y') ASC";

        $params = [$today, $today, $today, $today, $today];
        if ($user_id) {
            $params[] = $user_id;
        }
        $params[] = $threshold_date;

        $stmt = execute_query($sql, $params);
        return $stmt->fetchAll();
    }
}

/**
 * Get total count of unread notifications for a user
 * @param int $user_id User ID
 * @return int Total count of unread notifications
 */
if (!function_exists('get_unread_notification_count')) {
    function get_unread_notification_count($user_id) {
        $count = 0;

        // Count unread alerts
        $sql = "SELECT COUNT(*) as count
                FROM employee_alerts ea
                LEFT JOIN notification_status ns ON ea.id = ns.notification_id
                    AND ns.user_id = ?
                    AND ns.notification_type = 'alert'
                WHERE ea.status = 'active'
                AND (ns.is_read IS NULL OR ns.is_read = 0)
                AND (ns.is_dismissed IS NULL OR ns.is_dismissed = 0)";

        $stmt = execute_query($sql, [$user_id]);
        $result = $stmt->fetch();
        $count += (int)($result['count'] ?? 0);

        // Count expiring licenses
        $licenses = get_license_notifications($user_id, 60);
        $count += count($licenses);

        // Count expiring clearances
        $clearances = get_clearance_notifications($user_id, 60);
        $count += count($clearances);

        return $count;
    }
}

// ============================================================================
// LEAVE MANAGEMENT FUNCTIONS
// ============================================================================

/**
 * Get leave requests with optional filters
 */
if (!function_exists('get_leave_requests')) {
    function get_leave_requests($status = null, $employee_id = null, $leave_type = null) {
        $sql = "SELECT lr.*, 
                       e.first_name, e.surname, e.post,
                       CONCAT(e.surname, ', ', e.first_name) as employee_name,
                       u.name as processed_by_name
                FROM leave_requests lr
                LEFT JOIN employees e ON lr.employee_id = e.id
                LEFT JOIN users u ON lr.processed_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $sql .= " AND lr.status = ?";
            $params[] = $status;
        }
        
        if ($employee_id) {
            $sql .= " AND lr.employee_id = ?";
            $params[] = $employee_id;
        }
        
        if ($leave_type) {
            $sql .= " AND lr.leave_type = ?";
            $params[] = $leave_type;
        }
        
        $sql .= " ORDER BY lr.request_date DESC";
        
        $stmt = execute_query($sql, $params);
        return $stmt->fetchAll();
    }
}

/**
 * Get leave balance for an employee
 */
if (!function_exists('get_leave_balance')) {
    function get_leave_balance($employee_id, $year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $sql = "SELECT * FROM leave_balances WHERE employee_id = ? AND year = ?";
        $stmt = execute_query($sql, [$employee_id, $year]);
        return $stmt->fetch();
    }
}

/**
 * Get all leave balances for active employees
 */
if (!function_exists('get_all_leave_balances')) {
    function get_all_leave_balances($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $sql = "SELECT lb.*, 
                       e.first_name, e.surname, e.post,
                       CONCAT(e.surname, ', ', e.first_name) as employee_name,
                       (lb.sick_leave_total - lb.sick_leave_used) as sick_available,
                       (lb.vacation_leave_total - lb.vacation_leave_used) as vacation_available,
                       (lb.emergency_leave_total - lb.emergency_leave_used) as emergency_available
                FROM leave_balances lb
                LEFT JOIN employees e ON lb.employee_id = e.id
                WHERE e.status = 'Active' AND lb.year = ?
                ORDER BY e.surname, e.first_name";
        
        $stmt = execute_query($sql, [$year]);
        return $stmt->fetchAll();
    }
}

// ============================================================================
// ATTENDANCE MANAGEMENT FUNCTIONS
// ============================================================================

/**
 * Get attendance records with filters
 */
if (!function_exists('get_attendance_records')) {
    function get_attendance_records($date_from = null, $date_to = null, $employee_id = null, $status = null) {
        $sql = "SELECT ar.*, 
                       e.first_name, e.surname, e.post,
                       CONCAT(e.surname, ', ', e.first_name) as employee_name,
                       u.name as adjusted_by_name
                FROM attendance_records ar
                LEFT JOIN employees e ON ar.employee_id = e.id
                LEFT JOIN users u ON ar.adjusted_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($date_from) {
            $sql .= " AND ar.date >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $sql .= " AND ar.date <= ?";
            $params[] = $date_to;
        }
        
        if ($employee_id) {
            $sql .= " AND ar.employee_id = ?";
            $params[] = $employee_id;
        }
        
        if ($status) {
            $sql .= " AND ar.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY ar.date DESC, e.surname, e.first_name";
        
        $stmt = execute_query($sql, $params);
        return $stmt->fetchAll();
    }
}

// ============================================================================
// VIOLATION MANAGEMENT FUNCTIONS
// ============================================================================

/**
 * Get violation types
 */
if (!function_exists('get_violation_types')) {
    function get_violation_types($category = null) {
        $sql = "SELECT * FROM violation_types WHERE is_active = 1";
        $params = [];
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY category DESC, name ASC";
        
        $stmt = execute_query($sql, $params);
        $types = $stmt->fetchAll();
        
        // Decode JSON sanctions
        foreach ($types as &$type) {
            if (isset($type['sanctions']) && is_string($type['sanctions'])) {
                $type['sanctions'] = json_decode($type['sanctions'], true) ?: [];
            }
        }
        
        return $types;
    }
}

/**
 * Get employee violations with filters
 */
if (!function_exists('get_employee_violations')) {
    function get_employee_violations($employee_id = null, $severity = null, $violation_type_id = null) {
        $sql = "SELECT ev.*, 
                       e.first_name, e.surname, e.post,
                       CONCAT(e.surname, ', ', e.first_name) as employee_name,
                       vt.name as violation_type_name,
                       vt.category as violation_category
                FROM employee_violations ev
                LEFT JOIN employees e ON ev.employee_id = e.id
                LEFT JOIN violation_types vt ON ev.violation_type_id = vt.id
                WHERE 1=1";
        
        $params = [];
        
        if ($employee_id) {
            $sql .= " AND ev.employee_id = ?";
            $params[] = $employee_id;
        }
        
        if ($severity) {
            $sql .= " AND ev.severity = ?";
            $params[] = $severity;
        }
        
        if ($violation_type_id) {
            $sql .= " AND ev.violation_type_id = ?";
            $params[] = $violation_type_id;
        }
        
        $sql .= " ORDER BY ev.violation_date DESC";
        
        $stmt = execute_query($sql, $params);
        return $stmt->fetchAll();
    }
}

// ============================================================================
// DOCUMENT MANAGEMENT FUNCTIONS
// ============================================================================

/**
 * Get employee documents with filters
 */
if (!function_exists('get_employee_documents')) {
    function get_employee_documents($employee_id = null, $document_type = null) {
        $sql = "SELECT ed.*, 
                       e.first_name, e.surname, e.post,
                       CONCAT(e.surname, ', ', e.first_name) as employee_name,
                       u.name as uploaded_by_name
                FROM employee_documents ed
                LEFT JOIN employees e ON ed.employee_id = e.id
                LEFT JOIN users u ON ed.uploaded_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($employee_id) {
            $sql .= " AND ed.employee_id = ?";
            $params[] = $employee_id;
        }
        
        if ($document_type) {
            $sql .= " AND ed.document_type = ?";
            $params[] = $document_type;
        }
        
        $sql .= " ORDER BY ed.upload_date DESC";
        
        $stmt = execute_query($sql, $params);
        return $stmt->fetchAll();
    }
}

?>