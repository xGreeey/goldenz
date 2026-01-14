<?php
/**
 * Bootstrap Autoloader
 * Handles class autoloading and initial setup
 */

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('RESOURCES_PATH', BASE_PATH . '/resources');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Load environment variables FIRST (before any other configuration)
require_once __DIR__ . '/env.php';

// Set error reporting based on environment
$env = $_ENV['APP_ENV'] ?? 'production';
$debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

if ($debug || $env === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Load Composer autoloader if available
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}

// Simple PSR-4 autoloader
spl_autoload_register(function ($class) {
    // Remove namespace prefix
    $prefix = 'App\\';
    $base_dir = APP_PATH . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load helper functions
if (file_exists(APP_PATH . '/Helpers/functions.php')) {
    require APP_PATH . '/Helpers/functions.php';
}

// Load configuration
if (class_exists('App\Core\Config')) {
    \App\Core\Config::load();
}

