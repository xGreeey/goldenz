<?php
/**
 * Authentication Manager
 * Handles user authentication and authorization
 */

namespace App\Core;

class Auth
{
    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public static function check()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get authenticated user
     * 
     * @return array|null
     */
    public static function user()
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'name' => $_SESSION['name'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
            'employee_id' => $_SESSION['employee_id'] ?? null,
            'department' => $_SESSION['department'] ?? null,
        ];
    }

    /**
     * Get user role
     * 
     * @return string|null
     */
    public static function role()
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Check if user has specific role
     * 
     * @param string|array $roles
     * @return bool
     */
    public static function hasRole($roles)
    {
        $userRole = self::role();
        
        if (!$userRole) {
            return false;
        }

        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }

        return $userRole === $roles;
    }

    /**
     * Check if user has permission
     * 
     * @param string $permission
     * @return bool
     */
    public static function can($permission)
    {
        $role = self::role();
        
        if (!$role) {
            return false;
        }

        // Super admin has all permissions
        if ($role === 'super_admin') {
            return true;
        }

        $roleConfig = Config::get("roles.roles.{$role}", []);
        $permissions = $roleConfig['permissions'] ?? [];

        // Check for wildcard
        if (in_array('*', $permissions)) {
            return true;
        }

        // Check exact permission
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Check wildcard permissions (e.g., 'employees.*' matches 'employees.view')
        foreach ($permissions as $perm) {
            if (strpos($perm, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($perm, '/'));
                if (preg_match("/^{$pattern}$/", $permission)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Login user
     * 
     * @param array $user
     * @return void
     */
    public static function login($user)
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['employee_id'] = $user['employee_id'] ?? null;
        $_SESSION['department'] = $user['department'] ?? null;
    }

    /**
     * Logout user
     * 
     * @return void
     */
    public static function logout()
    {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
    }

    /**
     * Require authentication
     * 
     * @return void
     */
    public static function requireAuth()
    {
        if (!self::check()) {
            header('Location: ' . url('landing/'));
            exit;
        }
    }

    /**
     * Require role
     * 
     * @param string|array $roles
     * @return void
     */
    public static function requireRole($roles)
    {
        self::requireAuth();
        
        if (!self::hasRole($roles)) {
            header('Location: ' . url('landing/'));
            exit;
        }
    }

    /**
     * Require permission
     * 
     * @param string $permission
     * @return void
     */
    public static function requirePermission($permission)
    {
        self::requireAuth();
        
        if (!self::can($permission)) {
            http_response_code(403);
            die('Access Denied: You do not have permission to access this resource.');
        }
    }
}

