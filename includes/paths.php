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
        
        // Normalize the avatar path (ensure forward slashes)
        $avatar_path = str_replace('\\', '/', $avatar_path);
        
        // Get the project root directory
        $base_dir = dirname(__DIR__);
        // Normalize base directory path for cross-platform compatibility
        $base_dir = str_replace('\\', '/', $base_dir);
        
        // Build full path and check if file exists
        $full_path = $base_dir . '/' . $avatar_path;
        
        // Also try with DIRECTORY_SEPARATOR for Windows compatibility
        $full_path_native = str_replace('/', DIRECTORY_SEPARATOR, $full_path);
        
        if (!file_exists($full_path) && !file_exists($full_path_native)) {
            // Log for debugging
            error_log("Avatar file not found: $full_path (also tried: $full_path_native)");
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

if (!function_exists('get_employee_photo_url')) {
    /**
     * Get correct employee photo URL based on current context
     * Handles path resolution for different entry points (super-admin, hr-admin, etc.)
     * 
     * @param string|null $photo_path Path stored in database (e.g., 'uploads/employees/filename.jpg')
     * @param int|null $employee_id Employee ID to check for file-based photos
     * @return string|null Correct relative URL for the photo or null if file doesn't exist
     */
    function get_employee_photo_url($photo_path = null, $employee_id = null) {
        $base_dir = dirname(__DIR__);
        $base_dir = str_replace('\\', '/', $base_dir);
        
        // First, try the photo_path from database
        if (!empty($photo_path)) {
            $photo_path = str_replace('\\', '/', $photo_path);
            $full_path = $base_dir . '/' . $photo_path;
            $full_path_native = str_replace('/', DIRECTORY_SEPARATOR, $full_path);
            
            if (file_exists($full_path) || file_exists($full_path_native)) {
                // Determine correct relative path based on entry point
                $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
                $script_dir = dirname($script_name);
                
                // If accessed from subdirectory (super-admin, hr-admin, etc.), need ../ to reach root
                if (preg_match('#/(super-admin|hr-admin|developer|employee|accounting|operation)(/|$)#', $script_dir)) {
                    return '../' . $photo_path;
                }
                
                return $photo_path;
            }
        }
        
        // If no photo_path or file doesn't exist, try file-based approach with employee_id
        if ($employee_id) {
            $possible_paths = [
                'uploads/employees/' . $employee_id . '.jpg',
                'uploads/employees/' . $employee_id . '.png',
                'assets/images/employees/' . $employee_id . '.jpg',
                'assets/images/employees/' . $employee_id . '.png'
            ];
            
            foreach ($possible_paths as $path) {
                $full_path = $base_dir . '/' . $path;
                $full_path_native = str_replace('/', DIRECTORY_SEPARATOR, $full_path);
                
                if (file_exists($full_path) || file_exists($full_path_native)) {
                    // Determine correct relative path based on entry point
                    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
                    $script_dir = dirname($script_name);
                    
                    // If accessed from subdirectory (super-admin, hr-admin, etc.), need ../ to reach root
                    if (preg_match('#/(super-admin|hr-admin|developer|employee|accounting|operation)(/|$)#', $script_dir)) {
                        return '../' . $path;
                    }
                    
                    return $path;
                }
            }
        }
        
        return null;
    }
}

