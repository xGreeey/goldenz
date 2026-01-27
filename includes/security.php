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

/**
 * Check if user's password has expired
 * 
 * @param int|null $user_id User ID (defaults to current session user)
 * @return array Returns ['expired' => bool, 'days_until_expiry' => int|null, 'expiry_date' => string|null]
 */
if (!function_exists('check_password_expiry')) {
    function check_password_expiry($user_id = null) {
        if (!$user_id) {
            $user_id = $_SESSION['user_id'] ?? null;
        }
        
        if (!$user_id) {
            return ['expired' => false, 'days_until_expiry' => null, 'expiry_date' => null];
        }
        
        try {
            if (!function_exists('get_user_by_id') || !function_exists('get_password_policy')) {
                require_once __DIR__ . '/database.php';
            }

            $policy = get_password_policy();
            $expiry_days = isset($policy['expiry_days']) ? (int)$policy['expiry_days'] : 90;

            $user = get_user_by_id($user_id);
            
            if (!$user) {
                return ['expired' => false, 'days_until_expiry' => null, 'expiry_date' => null];
            }

            // If expiry_days is 0, password never expires
            if ($expiry_days === 0) {
                return ['expired' => false, 'days_until_expiry' => null, 'expiry_date' => null];
            }

            // If password_changed_at is NULL, consider it expired for security
            if (empty($user['password_changed_at'])) {
                return ['expired' => true, 'days_until_expiry' => 0, 'expiry_date' => null];
            }
            
            $password_changed = strtotime($user['password_changed_at']);
            $expiry_timestamp = $password_changed + ($expiry_days * 24 * 60 * 60);
            $current_timestamp = time();
            
            if ($expiry_timestamp < $current_timestamp) {
                return [
                    'expired' => true,
                    'days_until_expiry' => 0,
                    'expiry_date' => date('Y-m-d H:i:s', $expiry_timestamp)
                ];
            } else {
                $days_until = ceil(($expiry_timestamp - $current_timestamp) / (24 * 60 * 60));
                return [
                    'expired' => false,
                    'days_until_expiry' => $days_until,
                    'expiry_date' => date('Y-m-d H:i:s', $expiry_timestamp)
                ];
            }
        } catch (Exception $e) {
            error_log("Error checking password expiry: " . $e->getMessage());
            return ['expired' => false, 'days_until_expiry' => null, 'expiry_date' => null];
        }
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
    // Initialize global flag if not set
    if (!isset($GLOBALS['_security_logging_in_progress'])) {
        $GLOBALS['_security_logging_in_progress'] = false;
    }
    
    function log_security_event($event, $details = '') {
        // Prevent infinite recursion - check global flag (shared with log_security_event_db)
        if (isset($GLOBALS['_security_logging_in_progress']) && $GLOBALS['_security_logging_in_progress']) {
            // If already logging, just write to file directly
            $log_entry = date('Y-m-d H:i:s') . " - " . $event . " - " . $details . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            $log_file = __DIR__ . '/../storage/logs/security.log';
            if (!is_dir(dirname($log_file))) {
                $log_file = __DIR__ . '/../logs/security.log';
            }
            if (file_exists(dirname($log_file)) || mkdir(dirname($log_file), 0755, true)) {
                @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
            }
            return;
        }
        
        $GLOBALS['_security_logging_in_progress'] = true;
        
        try {
            // Try database logging first (for developer dashboard)
            // Note: log_security_event_db will check the global flag and handle recursion
            if (function_exists('log_security_event_db')) {
                log_security_event_db($event, $details);
            }
            
            // Also log system event (but check for recursion)
            if (function_exists('log_system_event')) {
                try {
                    log_system_event('security', $event . ': ' . $details, 'security');
                } catch (Exception $e) {
                    // Silently fail to avoid recursion
                }
            }
            
            if (class_exists('App\Core\Security')) {
                try {
                    \App\Core\Security::logSecurityEvent($event, $details);
                } catch (Exception $e) {
                    // Silently fail to avoid recursion
                }
            }
            
            // Always log to file as backup
            $log_entry = date('Y-m-d H:i:s') . " - " . $event . " - " . $details . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            $log_file = __DIR__ . '/../storage/logs/security.log';
            if (!is_dir(dirname($log_file))) {
                $log_file = __DIR__ . '/../logs/security.log'; // Fallback to old location
            }
            if (file_exists(dirname($log_file)) || mkdir(dirname($log_file), 0755, true)) {
                @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
            }
        } catch (Exception $e) {
            // Silently fail to avoid recursion
        } finally {
            $GLOBALS['_security_logging_in_progress'] = false;
        }
    }
}

// Redirect with message
if (!function_exists('redirect_with_message')) {
    function redirect_with_message($url, $message, $type = 'info') {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
        
        // Avoid "headers already sent" warnings when pages are rendered inside a layout
        // that may have already started output (e.g. includes/header.php).
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        }
        
        // Fallback to a client-side redirect if output already started.
        $safeUrl = json_encode((string)$url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        echo "<script>window.location.href = {$safeUrl};</script>";
        echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url=" . htmlspecialchars((string)$url, ENT_QUOTES, 'UTF-8') . "\"></noscript>";
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

/**
 * TWO-FACTOR AUTHENTICATION (TOTP) HELPERS
 * Simple Google Authenticator–compatible implementation
 */

if (!function_exists('generate_two_factor_secret')) {
    /**
     * Generate a random Base32 secret for TOTP (Google Authenticator compatible)
     *
     * @param int $length Number of Base32 characters (typical: 16)
     * @return string
     */
    function generate_two_factor_secret($length = 16) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, $max)];
        }
        return $secret;
    }
}

if (!function_exists('base32_decode_secret')) {
    /**
     * Decode a Base32-encoded TOTP secret
     *
     * @param string $secret
     * @return string|false Binary string or false on failure
     */
    function base32_decode_secret($secret) {
        if ($secret === '' || $secret === null) {
            return false;
        }

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
        $binaryString = '';
        $buffer = 0;
        $bitsLeft = 0;

        $len = strlen($secret);
        for ($i = 0; $i < $len; $i++) {
            $val = strpos($alphabet, $secret[$i]);
            if ($val === false) {
                return false;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binaryString .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $binaryString;
    }
}

if (!function_exists('verify_totp_code')) {
    /**
     * Verify a 6‑digit TOTP code for a given secret
     *
     * @param string $secret Base32-encoded secret
     * @param string $code   6-digit user input
     * @param int $discrepancy Number of 30s windows to allow before/after (default ±1)
     * @param int|null $timestamp Unix timestamp (defaults to time())
     * @return bool
     */
    function verify_totp_code($secret, $code, $discrepancy = 1, $timestamp = null) {
        $timestamp = $timestamp ?? time();
        $code = trim($code);

        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $binarySecret = base32_decode_secret($secret);
        if ($binarySecret === false) {
            return false;
        }

        $timeSlice = (int)floor($timestamp / 30);

        // Check current, previous, and next time window
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $counter = pack('N*', 0) . pack('N*', $timeSlice + $i);
            $hash = hash_hmac('sha1', $counter, $binarySecret, true);
            $offset = ord(substr($hash, -1)) & 0x0F;
            $truncatedHash = substr($hash, $offset, 4);
            $value = unpack('N', $truncatedHash)[1] & 0x7FFFFFFF;
            $generatedCode = str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);

            if (hash_equals($generatedCode, $code)) {
                return true;
            }
        }

        return false;
    }
}

// CSRF Token Helpers
if (!function_exists('generate_csrf_token')) {
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    function generate_csrf_token() {
        if (class_exists('App\Core\Security')) {
            return \App\Core\Security::generateCsrfToken();
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Verify CSRF token
     * 
     * @param string $token
     * @return bool
     */
    function verify_csrf_token($token) {
        if (class_exists('App\Core\Security')) {
            return \App\Core\Security::verifyCsrfToken($token);
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

