<?php
/**
 * Role-Based Access Control Middleware
 * Protects routes based on user roles
 */

namespace App\Middleware;

use App\Core\Auth;

class RoleMiddleware
{
    private $allowedRoles;

    public function __construct($roles)
    {
        $this->allowedRoles = is_array($roles) ? $roles : [$roles];
    }

    /**
     * Handle the request
     * 
     * @return void
     */
    public function handle()
    {
        if (!Auth::check()) {
            header('Location: ' . url('landing/'));
            exit;
        }

        if (!Auth::hasRole($this->allowedRoles)) {
            http_response_code(403);
            die('Access Denied: Insufficient permissions.');
        }
    }
}

