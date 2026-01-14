<?php
/**
 * Configuration Management Class
 * Loads and manages application configuration
 */

namespace App\Core;

class Config
{
    private static $config = [];
    private static $loaded = false;

    /**
     * Load configuration from files
     */
    public static function load()
    {
        if (self::$loaded) {
            return;
        }

        // Load environment variables
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Skip comments and empty lines
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }
                
                // Parse KEY=VALUE format
                if (strpos($line, '=') !== false) {
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        $name = trim($parts[0]);
                        $value = trim($parts[1]);
                        
                        // Remove quotes if present
                        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                            $value = substr($value, 1, -1);
                        }
                        
                        if (!empty($name)) {
                            $_ENV[$name] = $value;
                        }
                    }
                }
            }
        }

        // Load config files
        $configDir = __DIR__ . '/../../config';
        if (is_dir($configDir)) {
            $files = glob($configDir . '/*.php');
            foreach ($files as $file) {
                $key = basename($file, '.php');
                self::$config[$key] = require $file;
            }
        }

        self::$loaded = true;
    }

    /**
     * Get configuration value
     * 
     * @param string $key Dot notation key (e.g., 'database.host')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        self::load();

        // Check environment variable first
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // Check config array
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value
     * 
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        self::load();
        return isset($_ENV[$key]) || self::get($key) !== null;
    }
}

