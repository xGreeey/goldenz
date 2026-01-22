<?php
/**
 * Authentication Middleware
 * Protects routes that require authentication
 */

namespace App\Middleware;

use App\Core\Auth;

class AuthMiddleware
{
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
    }
}

