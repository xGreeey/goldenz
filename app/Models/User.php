<?php
/**
 * User Model
 * Handles user data operations
 */

namespace App\Models;

use App\Core\Database;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find user by username
     * 
     * @param string $username
     * @return array|null
     */
    public function findByUsername($username)
    {
        $sql = "SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1";
        return $this->db->fetch($sql, [$username]);
    }

    /**
     * Find user by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Verify password
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Update last login
     * 
     * @param int $userId
     * @param string $ipAddress
     * @return bool
     */
    public function updateLastLogin($userId, $ipAddress = null)
    {
        $sql = "UPDATE users SET last_login = NOW(), last_login_ip = ? WHERE id = ?";
        $this->db->query($sql, [$ipAddress, $userId]);
        return true;
    }

    /**
     * Log failed login attempt
     * 
     * @param int $userId
     * @return bool
     */
    public function logFailedLoginAttempt($userId)
    {
        // Get current failed attempts
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }
        
        $failedAttempts = ($user['failed_login_attempts'] ?? 0) + 1;
        $lockedUntil = null;
        
        // Lock account after 5 failed attempts for 30 minutes
        if ($failedAttempts >= 5) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        }
        
        $sql = "UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?";
        $this->db->query($sql, [$failedAttempts, $lockedUntil, $userId]);
        return true;
    }

    /**
     * Reset failed login attempts
     * 
     * @param int $userId
     * @return bool
     */
    public function resetFailedLoginAttempts($userId)
    {
        $sql = "UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?";
        $this->db->query($sql, [$userId]);
        return true;
    }

    /**
     * Get all users
     * 
     * @param array $filters
     * @return array
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];

        if (!empty($filters['role'])) {
            $sql .= " AND role = ?";
            $params[] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }
}

