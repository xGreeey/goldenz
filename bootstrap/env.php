<?php
/**
 * Environment Variable Loader
 * Loads .env file using vlucas/phpdotenv if available, otherwise uses simple parser
 */

if (!function_exists('load_env_file')) {
    function load_env_file($envPath) {
        if (!file_exists($envPath)) {
            return false;
        }

        // Try to use Dotenv if available (vlucas/phpdotenv)
        if (class_exists('Dotenv\Dotenv')) {
            try {
                $dotenv = Dotenv\Dotenv::createImmutable(dirname($envPath));
                $dotenv->load();
                return true;
            } catch (Exception $e) {
                error_log('Dotenv error: ' . $e->getMessage());
                // Fall through to simple parser
            }
        }

        // Fallback: Simple .env parser
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                // Only set if not already set (environment variables take precedence)
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        return true;
    }
}

// Load .env file from project root
$envFile = __DIR__ . '/../.env';
load_env_file($envFile);
