<?php
/**
 * Application Bootstrap
 * Initializes the application
 */

// Load autoloader
require_once __DIR__ . '/autoload.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    // Set session configuration defaults
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '0'); // Set to 1 if using HTTPS
    ini_set('session.use_strict_mode', '1');
    
    // Set session save path if storage directory exists
    $sessionPath = __DIR__ . '/../storage/sessions';
    if (is_dir($sessionPath) || mkdir($sessionPath, 0755, true)) {
        session_save_path($sessionPath);
    }
    
    session_start();
}

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Load application configuration
\App\Core\Config::load();

