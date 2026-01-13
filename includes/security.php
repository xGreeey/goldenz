<?php
/**
 * Security Functions for Golden Z-5 HR System
 * High-Security Features for Security Agency
 * 
 * NOTE: This file maintains backward compatibility.
 * New code should use App\Core\Security class instead.
 */

// Load bootstrap if available
if (file_exists(__DIR__ . '/../bootstrap/autoload.php')) {
    require_once __DIR__ . '/../bootstrap/autoload.php';
}

// Input sanitization
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        if (class_exists('App\Core\Security')) {
            return \App\Core\Security::sanitize($data);
        }
        if (is_array($data)) {
            return array_map('sanitize_input', $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// SQL injection protection
if (!function_exists('check_sql_injection')) {
    function check_sql_injection($input) {
        $dangerous_patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT)\b)/i',
            '/(\b(OR|AND)\s+\d+\s*=\s*\d+)/i',
            '/(\b(OR|AND)\s+\'.*?\'\s*=\s*\'.*?\')/i',
            '/(UNION\s+SELECT)/i',
            '/(DROP\s+TABLE)/i'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return false;
            }
        }
        return true;
    }
}

// XSS protection
if (!function_exists('check_xss')) {
    function check_xss($input) {
        $xss_patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/i'
        ];
        
        foreach ($xss_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return false;
            }
        }
        return true;
    }
}

// Validate input
if (!function_exists('validate_input')) {
    function validate_input($data, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($data, FILTER_VALIDATE_EMAIL) !== false;
            case 'int':
                return filter_var($data, FILTER_VALIDATE_INT) !== false;
            case 'float':
                return filter_var($data, FILTER_VALIDATE_FLOAT) !== false;
            case 'url':
                return filter_var($data, FILTER_VALIDATE_URL) !== false;
            default:
                return !empty($data);
        }
    }
}

// Password hashing
if (!function_exists('hash_password')) {
    function hash_password($password) {
        if (class_exists('App\Core\Security')) {
            return \App\Core\Security::hashPassword($password);
        }
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

// Verify password
if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        if (class_exists('App\Core\Security')) {
            return \App\Core\Security::verifyPassword($password, $hash);
        }
        return password_verify($password, $hash);
    }
}

// Generate secure random string
if (!function_exists('generate_secure_string')) {
    function generate_secure_string($length = 32) {
        if (class_exists('App\Core\Security')) {
            return \App\Core\Security::generateSecureString($length);
        }
        return bin2hex(random_bytes($length));
    }
}

// Log security events
if (!function_exists('log_security_event')) {
    function log_security_event($event, $details = '') {
        if (class_exists('App\Core\Security')) {
            \App\Core\Security::logSecurityEvent($event, $details);
            return;
        }
        
        // Fallback to old method
        $log_entry = date('Y-m-d H:i:s') . " - " . $event . " - " . $details . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
        $log_file = __DIR__ . '/../storage/logs/security.log';
        if (!is_dir(dirname($log_file))) {
            $log_file = __DIR__ . '/../logs/security.log'; // Fallback to old location
        }
        if (file_exists(dirname($log_file)) || mkdir(dirname($log_file), 0755, true)) {
            file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
    }
}

// Redirect with message
if (!function_exists('redirect_with_message')) {
    function redirect_with_message($url, $message, $type = 'info') {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
        header("Location: $url");
        exit;
    }
}

// Display message
if (!function_exists('display_message')) {
    function display_message() {
        if (isset($_SESSION['message'])) {
            $message = $_SESSION['message'];
            $type = $_SESSION['message_type'] ?? 'info';
            unset($_SESSION['message'], $_SESSION['message_type']);
            
            $alertClass = [
                'success' => 'alert-success',
                'error' => 'alert-danger',
                'warning' => 'alert-warning',
                'info' => 'alert-info'
            ][$type] ?? 'alert-info';
            
            echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($message);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        }
    }
}

