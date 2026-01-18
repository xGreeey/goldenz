<?php
/**
 * Path helpers to build consistent asset and public URLs
 * Works regardless of entry folder (developer, hr-admin, etc.)
 */

if (!function_exists('root_prefix')) {
    function root_prefix(): string
    {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        // Strip role subfolders so assets resolve from project root
        $rootDir = preg_replace('#/(developer|hr-admin|super-admin|employee|accounting|operation)$#', '', $scriptDir);
        return $rootDir === '' ? '' : $rootDir;
    }
}

if (!function_exists('base_url')) {
    function base_url(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return $scheme . '://' . $host . root_prefix();
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path = ''): string
    {
        return base_url() . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('public_url')) {
    function public_url(string $path = ''): string
    {
        return base_url() . '/public/' . ltrim($path, '/');
    }
}

if (!function_exists('get_avatar_url')) {
    /**
     * Get correct avatar URL based on current context
     * Handles path resolution for different entry points (super-admin, hr-admin, etc.)
     * 
     * @param string|null $avatar_path Path stored in database (e.g., 'uploads/users/filename.jpg')
     * @return string|null Correct relative URL for the avatar or null if file doesn't exist
     */
    function get_avatar_url($avatar_path) {
        if (empty($avatar_path)) {
            return null;
        }
        
        // Check if file exists
        $base_dir = dirname(__DIR__);
        $full_path = $base_dir . '/' . $avatar_path;
        if (!file_exists($full_path)) {
            return null;
        }
        
        // Determine correct relative path based on entry point
        $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
        $script_dir = dirname($script_name);
        
        // If accessed from subdirectory (super-admin, hr-admin, etc.), need ../ to reach root
        if (preg_match('#/(super-admin|hr-admin|developer|employee|accounting|operation)(/|$)#', $script_dir)) {
            return '../' . $avatar_path;
        }
        
        // Otherwise, use path as-is (from root)
        return $avatar_path;
    }
}

