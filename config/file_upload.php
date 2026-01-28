<?php
/**
 * Secure File Upload Configuration
 * Configuration for employee document uploads with security controls
 */

return [
    // Storage path (OUTSIDE web root for security)
    // Default: Uses application storage directory (storage/employee_docs/)
    // Can be overridden via EMPLOYEE_FILES_STORAGE_PATH env variable
    // For production, set to absolute path outside web root:
    //   Linux: /var/hrdash_storage/employee_docs/
    //   Windows: C:\HRDASH_STORAGE\employee_docs\
    'storage_path' => $_ENV['EMPLOYEE_FILES_STORAGE_PATH'] ?? (
        defined('BASE_PATH') 
            ? BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'employee_docs' . DIRECTORY_SEPARATOR
            : (PHP_OS_FAMILY === 'Windows' 
                ? 'C:\\HRDASH_STORAGE\\employee_docs\\'
                : '/var/hrdash_storage/employee_docs/')
    ),
    
    // Maximum file size (in bytes)
    // Default: 20MB (configurable via env)
    'max_file_size' => (int)($_ENV['MAX_UPLOAD_SIZE_MB'] ?? 20) * 1024 * 1024,
    
    // Allowed MIME types (server-side validation)
    'allowed_mime_types' => [
        // PDF documents
        'application/pdf',
        // Images
        'image/jpeg',
        'image/jpg',
        'image/png',
        // Microsoft Word
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        'application/msword', // .doc (legacy)
    ],
    
    // Allowed file extensions (for client-side hint only, server validates MIME)
    'allowed_extensions' => [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'docx',
        'doc', // Legacy Word format
    ],
    
    // Blocked dangerous file types (even if extension is allowed)
    'blocked_mime_types' => [
        'application/x-php',
        'application/x-httpd-php',
        'text/x-php',
        'application/x-executable',
        'application/x-msdownload', // .exe
        'application/x-sh', // .sh
        'application/x-bat', // .bat
        'text/html',
        'application/javascript',
        'text/javascript',
    ],
    
    // Blocked extensions (additional safety check)
    'blocked_extensions' => [
        'php',
        'php3',
        'php4',
        'php5',
        'phtml',
        'exe',
        'bat',
        'sh',
        'js',
        'html',
        'htm',
        'asp',
        'aspx',
        'jsp',
    ],
    
    // Document categories
    'categories' => [
        'Personal Records',
        'Contracts',
        'Government IDs',
        'Certifications',
        'Other',
    ],
    
    // Roles allowed to upload files
    'upload_allowed_roles' => [
        'super_admin',
        'hr_admin',
        'hr',
        'admin',
    ],
    
    // Roles allowed to view/download files
    'view_allowed_roles' => [
        'super_admin',
        'hr_admin',
        'hr',
        'admin',
        'accounting',
        'operation',
    ],
    
    // Enable malware scanning
    'enable_malware_scan' => $_ENV['ENABLE_MALWARE_SCAN'] ?? true,
    
    // Malware scan method: 'windows_defender', 'online', or 'none'
    'malware_scan_method' => $_ENV['MALWARE_SCAN_METHOD'] ?? (
        PHP_OS_FAMILY === 'Windows' ? 'windows_defender' : 'online'
    ),
    
    // Online scanner API (if using online scanning)
    'online_scanner_api' => $_ENV['ONLINE_SCANNER_API'] ?? 'https://www.virustotal.com/vtapi/v2/file/scan',
    'online_scanner_api_key' => $_ENV['ONLINE_SCANNER_API_KEY'] ?? null,
];
