<?php
/**
 * Security Functions
 * Security utilities and helpers
 * 
 * NOTE: This maintains backward compatibility with includes/security.php
 */

namespace App\Core;

class Security
{
    /**
     * Sanitize input
     * 
     * @param mixed $input
     * @return mixed
     */
    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email
     * 
     * @param string $email
     * @return bool
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public static function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     * 
     * @param string $token
     * @return bool
     */
    public static function verifyCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Hash password
     * 
     * @param string $password
     * @return string
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate secure random string
     * 
     * @param int $length
     * @return string
     */
    public static function generateSecureString($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Log security event
     * 
     * @param string $event
     * @param string $details
     * @return void
     */
    public static function logSecurityEvent($event, $details = '')
    {
        $logEntry = date('Y-m-d H:i:s') . " - " . $event . " - " . $details . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
        $logFile = storage_path('logs/security.log');
        
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
