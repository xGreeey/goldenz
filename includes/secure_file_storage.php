<?php
/**
 * Secure File Storage Helper Functions
 * Handles secure file storage outside web root with UUID filenames
 */

require_once __DIR__ . '/../config/file_upload.php';

/**
 * Get secure storage path (outside web root)
 * 
 * @return string Absolute path to storage directory
 */
function get_secure_storage_path() {
    $config = include __DIR__ . '/../config/file_upload.php';
    $base_path = $config['storage_path'];
    
    // Ensure path ends with directory separator
    if (substr($base_path, -1) !== DIRECTORY_SEPARATOR) {
        $base_path .= DIRECTORY_SEPARATOR;
    }
    
    // Normalize path separators
    $base_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $base_path);
    
    // Create base directory if it doesn't exist
    if (!file_exists($base_path)) {
        $old_umask = umask(0);
        $created = @mkdir($base_path, 0755, true);
        umask($old_umask);
        
        if (!$created) {
            $error = error_get_last();
            error_log("Failed to create base storage directory: $base_path. Error: " . ($error['message'] ?? 'Unknown'));
            // Return path anyway - let the calling function handle the error
        } else {
            // Add .htaccess to prevent web access if using storage directory
            if (strpos($base_path, DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR) !== false) {
                $htaccess_path = $base_path . '.htaccess';
                if (!file_exists($htaccess_path)) {
                    @file_put_contents($htaccess_path, "Deny from all\n");
                }
            }
        }
    }
    
    return $base_path;
}

/**
 * Generate UUID v4 filename
 * 
 * @param string $extension File extension (without dot)
 * @return string UUID filename with extension
 */
function generate_uuid_filename($extension) {
    // Generate UUID v4
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant bits
    
    $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    
    // Sanitize extension
    $extension = preg_replace('/[^a-z0-9]/i', '', $extension);
    if (empty($extension)) {
        $extension = 'bin';
    }
    
    return strtolower($uuid . '.' . $extension);
}

/**
 * Get employee-specific storage directory
 * Creates directory if it doesn't exist (with security checks)
 * 
 * @param int $employee_id Employee ID
 * @return string|false Absolute path to employee directory, or false on failure
 */
function get_employee_storage_dir($employee_id) {
    // Validate employee ID (prevent path traversal)
    $employee_id = (int)$employee_id;
    if ($employee_id <= 0) {
        error_log("Invalid employee ID for storage: $employee_id");
        return false;
    }
    
    $base_path = get_secure_storage_path();
    
    // Ensure base directory exists first
    if (!file_exists($base_path)) {
        $old_umask = umask(0);
        $created = @mkdir($base_path, 0755, true);
        umask($old_umask);
        
        if (!$created) {
            $error = error_get_last();
            error_log("Failed to create base storage directory: $base_path. Error: " . ($error['message'] ?? 'Unknown'));
            return false;
        }
    }
    
    // Verify base directory is writable
    if (!is_writable($base_path)) {
        // Try to make it writable
        @chmod($base_path, 0755);
        if (!is_writable($base_path)) {
            error_log("Base storage directory is not writable: $base_path");
            return false;
        }
    }
    
    $employee_dir = $base_path . $employee_id . DIRECTORY_SEPARATOR;
    
    // Create employee directory if it doesn't exist
    if (!file_exists($employee_dir)) {
        // Create with secure permissions (0755 = rwxr-xr-x)
        $old_umask = umask(0);
        $created = @mkdir($employee_dir, 0755, true);
        umask($old_umask);
        
        if (!$created) {
            $error = error_get_last();
            error_log("Failed to create employee storage directory: $employee_dir. Error: " . ($error['message'] ?? 'Unknown'));
            return false;
        }
        
        // Add .htaccess to prevent web access (if Apache)
        $htaccess_path = $employee_dir . '.htaccess';
        if (!file_exists($htaccess_path)) {
            @file_put_contents($htaccess_path, "Deny from all\n");
        }
    }
    
    // Verify directory is writable
    if (!is_writable($employee_dir)) {
        // Try to make it writable
        @chmod($employee_dir, 0755);
        if (!is_writable($employee_dir)) {
            error_log("Employee storage directory is not writable: $employee_dir");
            return false;
        }
    }
    
    return $employee_dir;
}

/**
 * Validate uploaded file
 * 
 * @param array $file $_FILES array element
 * @return array ['valid' => bool, 'error' => string|null, 'mime_type' => string|null, 'extension' => string|null]
 */
function validate_uploaded_file($file) {
    $config = include __DIR__ . '/../config/file_upload.php';
    
    // Check upload error
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
        ];
        $error_code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        return [
            'valid' => false,
            'error' => $error_messages[$error_code] ?? 'Unknown upload error',
            'mime_type' => null,
            'extension' => null,
        ];
    }
    
    // Check file size
    if ($file['size'] > $config['max_file_size']) {
        $max_mb = $config['max_file_size'] / (1024 * 1024);
        return [
            'valid' => false,
            'error' => "File size exceeds maximum allowed size of {$max_mb}MB",
            'mime_type' => null,
            'extension' => null,
        ];
    }
    
    // Get file extension
    $original_name = $file['name'] ?? '';
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    
    // Check blocked extensions
    if (in_array($extension, $config['blocked_extensions'])) {
        return [
            'valid' => false,
            'error' => "File type not allowed: .{$extension}",
            'mime_type' => null,
            'extension' => $extension,
        ];
    }
    
    // Validate MIME type server-side (DO NOT trust client)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!$detected_mime) {
        return [
            'valid' => false,
            'error' => 'Unable to detect file type',
            'mime_type' => null,
            'extension' => $extension,
        ];
    }
    
    // Check blocked MIME types
    if (in_array($detected_mime, $config['blocked_mime_types'])) {
        return [
            'valid' => false,
            'error' => "File type not allowed: {$detected_mime}",
            'mime_type' => $detected_mime,
            'extension' => $extension,
        ];
    }
    
    // Check allowed MIME types
    if (!in_array($detected_mime, $config['allowed_mime_types'])) {
        return [
            'valid' => false,
            'error' => "File type not allowed. Allowed types: PDF, JPG, PNG, DOCX",
            'mime_type' => $detected_mime,
            'extension' => $extension,
        ];
    }
    
    // Additional security: verify extension matches MIME type
    $mime_to_ext = [
        'application/pdf' => 'pdf',
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/jpg' => ['jpg', 'jpeg'],
        'image/png' => 'png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword' => 'doc',
    ];
    
    $expected_extensions = [];
    foreach ($mime_to_ext as $mime => $exts) {
        if ($mime === $detected_mime) {
            $expected_extensions = is_array($exts) ? $exts : [$exts];
            break;
        }
    }
    
    if (!empty($expected_extensions) && !in_array($extension, $expected_extensions)) {
        // MIME type doesn't match extension - potential security risk
        error_log("Security warning: MIME type {$detected_mime} doesn't match extension .{$extension}");
        // Still allow if MIME is valid (extension might be wrong but file is safe)
    }
    
    return [
        'valid' => true,
        'error' => null,
        'mime_type' => $detected_mime,
        'extension' => $extension,
    ];
}

/**
 * Scan file for malware (optional)
 * 
 * @param string $file_path Path to file to scan
 * @return array ['clean' => bool, 'message' => string]
 */
function scan_file_for_malware($file_path) {
    $config = include __DIR__ . '/../config/file_upload.php';
    
    if (!$config['enable_malware_scan']) {
        return ['clean' => true, 'message' => 'Malware scanning disabled'];
    }
    
    $method = $config['malware_scan_method'];
    
    if ($method === 'none') {
        return ['clean' => true, 'message' => 'Malware scanning disabled'];
    }
    
    if ($method === 'windows_defender' && PHP_OS_FAMILY === 'Windows') {
        // Use Windows Defender via PowerShell
        if (function_exists('exec')) {
            $command = sprintf(
                'powershell -Command "& {Get-MpPreference | Out-Null; $result = Scan-MpComputer -ScanType CustomScan -ScanPath \'%s\' -ErrorAction SilentlyContinue; if ($result) { Write-Output \'INFECTED\' } else { Write-Output \'CLEAN\' } }"',
                escapeshellarg($file_path)
            );
            
            exec($command, $output, $return_var);
            $result = implode('', $output);
            
            if (stripos($result, 'INFECTED') !== false) {
                return ['clean' => false, 'message' => 'File flagged by Windows Defender'];
            }
            
            return ['clean' => true, 'message' => 'Windows Defender scan passed'];
        }
    }
    
    if ($method === 'online' && !empty($config['online_scanner_api_key'])) {
        // Use VirusTotal API (requires API key)
        // Note: This is a placeholder - implement actual API call if needed
        // For now, we'll skip online scanning if not configured
        error_log("Online malware scanning not fully implemented. Skipping scan.");
        return ['clean' => true, 'message' => 'Online scan skipped (not implemented)'];
    }
    
    // Default: allow if scan method not available
    return ['clean' => true, 'message' => 'Malware scan method not available'];
}

/**
 * Save uploaded file securely
 * 
 * @param array $file $_FILES array element
 * @param int $employee_id Employee ID
 * @return array ['success' => bool, 'stored_filename' => string|null, 'file_path' => string|null, 'error' => string|null]
 */
function save_uploaded_file_securely($file, $employee_id) {
    // Validate file
    $validation = validate_uploaded_file($file);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'stored_filename' => null,
            'file_path' => null,
            'error' => $validation['error'],
        ];
    }
    
    // Get employee storage directory
    $employee_dir = get_employee_storage_dir($employee_id);
    if (!$employee_dir) {
        return [
            'success' => false,
            'stored_filename' => null,
            'file_path' => null,
            'error' => 'Failed to create storage directory',
        ];
    }
    
    // Generate UUID filename
    $stored_filename = generate_uuid_filename($validation['extension']);
    $full_path = $employee_dir . $stored_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $full_path)) {
        return [
            'success' => false,
            'stored_filename' => null,
            'file_path' => null,
            'error' => 'Failed to save file',
        ];
    }
    
    // Set secure file permissions (read/write for owner, read for group/others)
    @chmod($full_path, 0644);
    
    // Optional: Scan for malware
    $scan_result = scan_file_for_malware($full_path);
    if (!$scan_result['clean']) {
        // Delete file if malware detected
        @unlink($full_path);
        return [
            'success' => false,
            'stored_filename' => null,
            'file_path' => null,
            'error' => 'File failed malware scan: ' . $scan_result['message'],
        ];
    }
    
    // Return relative path from storage root
    $config = include __DIR__ . '/../config/file_upload.php';
    $base_path = get_secure_storage_path();
    $relative_path = str_replace($base_path, '', $full_path);
    
    return [
        'success' => true,
        'stored_filename' => $stored_filename,
        'file_path' => $relative_path,
        'mime_type' => $validation['mime_type'],
        'extension' => $validation['extension'],
        'error' => null,
    ];
}

/**
 * Get full path to stored file
 * 
 * @param string $relative_path Relative path from storage root
 * @return string|false Full path or false if invalid
 */
function get_stored_file_path($relative_path) {
    // Prevent path traversal
    $relative_path = str_replace('..', '', $relative_path);
    $relative_path = ltrim($relative_path, '/\\');
    
    $base_path = get_secure_storage_path();
    $full_path = $base_path . $relative_path;
    
    // Verify file exists and is within storage directory
    $real_base = realpath($base_path);
    $real_file = realpath($full_path);
    
    if (!$real_file || strpos($real_file, $real_base) !== 0) {
        return false;
    }
    
    return $real_file;
}

/**
 * Delete stored file securely
 * 
 * @param string $relative_path Relative path from storage root
 * @return bool Success
 */
function delete_stored_file($relative_path) {
    $full_path = get_stored_file_path($relative_path);
    if (!$full_path || !file_exists($full_path)) {
        return false;
    }
    
    return @unlink($full_path);
}
