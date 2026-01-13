<?php
/**
 * Global Helper Functions
 */

if (!function_exists('config')) {
    /**
     * Get configuration value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config($key, $default = null)
    {
        return \App\Core\Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('base_path')) {
    /**
     * Get base path
     * 
     * @param string $path
     * @return string
     */
    function base_path($path = '')
    {
        return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('app_path')) {
    /**
     * Get app path
     * 
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        return APP_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('config_path')) {
    /**
     * Get config path
     * 
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return CONFIG_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get storage path
     * 
     * @param string $path
     * @return string
     */
    function storage_path($path = '')
    {
        return STORAGE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('public_path')) {
    /**
     * Get public path
     * 
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        return PUBLIC_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('asset')) {
    /**
     * Generate asset URL
     * 
     * @param string $path
     * @return string
     */
    function asset($path)
    {
        $baseUrl = config('app.url', 'http://localhost');
        return rtrim($baseUrl, '/') . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Generate URL
     * 
     * @param string $path
     * @return string
     */
    function url($path = '')
    {
        $baseUrl = config('app.url', 'http://localhost');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

