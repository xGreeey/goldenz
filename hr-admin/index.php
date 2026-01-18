<?php
/**
 * HR Admin Portal - Golden Z-5 HR Management System
 * Main entry point for HR administrators
 */

// Bootstrap application
require_once __DIR__ . '/../bootstrap/app.php';

// Include legacy functions for backward compatibility
require_once '../includes/security.php';
require_once '../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../landing/index.php');
    exit;
}

// Check if user has appropriate role for HR Admin portal
$user_role = $_SESSION['user_role'] ?? null;
$allowed_roles = ['hr_admin', 'hr', 'admin', 'accounting', 'operation', 'logistics'];
if (!in_array($user_role, $allowed_roles)) {
    // Invalid role, redirect to login
    session_destroy();
    header('Location: ../landing/index.php');
    exit;
}

// Handle POST requests (AJAX and form submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'] ?? '';
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $page = $_GET['page'] ?? 'dashboard';
    
    // For non-AJAX profile updates, let the profile page handle it (skip JSON response)
    if (!$isAjax && $page === 'profile' && $action === 'update_profile') {
        // Don't set JSON header or exit - let the profile page process the form
    } else {
        // Set JSON header for AJAX requests
        header('Content-Type: application/json');
    }
    
    // Handle password change (AJAX)
    if ($action === 'change_password' && $isAjax) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            echo json_encode(['success' => false, 'message' => 'All password fields are required']);
            exit;
        }
        
        if (strlen($new_password) < 8) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long']);
            exit;
        }
        
        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match']);
            exit;
        }
        
        if ($new_password === $current_password) {
            echo json_encode(['success' => false, 'message' => 'New password must be different from current password']);
            exit;
        }
        
        // Validate password requirements
        $passwordRequirements = [
            'length' => strlen($new_password) >= 8,
            'lowercase' => preg_match('/[a-z]/', $new_password),
            'uppercase' => preg_match('/[A-Z]/', $new_password),
            'number' => preg_match('/[0-9]/', $new_password),
            'symbol' => preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password)
        ];
        
        $missingRequirements = [];
        if (!$passwordRequirements['length']) $missingRequirements[] = 'Minimum 8 characters';
        if (!$passwordRequirements['lowercase']) $missingRequirements[] = 'Lowercase letter';
        if (!$passwordRequirements['uppercase']) $missingRequirements[] = 'Uppercase letter';
        if (!$passwordRequirements['number']) $missingRequirements[] = 'Number';
        if (!$passwordRequirements['symbol']) $missingRequirements[] = 'Symbol';
        
        if (count($missingRequirements) > 0) {
            echo json_encode(['success' => false, 'message' => 'Password must contain: ' . implode(', ', $missingRequirements)]);
            exit;
        }
        
        try {
            $pdo = get_db_connection();
            $user_id = $_SESSION['user_id'] ?? null;
            
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            if (!password_verify($current_password, $user['password_hash'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_changed_at = NOW(), updated_at = NOW() WHERE id = ?");
            $result = $update_stmt->execute([$new_password_hash, $user_id]);
            
            if ($result && $update_stmt->rowCount() > 0) {
                if (function_exists('log_security_event')) {
                    log_security_event('INFO Password Changed', "User ID: $user_id - Username: " . ($_SESSION['username'] ?? 'Unknown') . " - Password changed via settings");
                }
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update password']);
            }
        } catch (Exception $e) {
            error_log('Password change error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while changing password']);
        }
        exit;
    }
    
    // Handle profile update (non-AJAX - process before header output)
    if ($action === 'update_profile' && !$isAjax) {
        // Process profile update before header output, then redirect
        $current_user_id = $_SESSION['user_id'] ?? null;
        if ($current_user_id) {
            require_once __DIR__ . '/../includes/database.php';
            $pdo = get_db_connection();
            $current_user = get_user_by_id($current_user_id);
            
            $user_updates = [];
            $user_params = [];
            $employee_updates = [];
            $employee_params = [];
            $update_error = null;
            
            // Handle avatar upload
            if (isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
                if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../uploads/users/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file = $_FILES['avatar'];
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    
                    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $filename = 'user_' . $current_user_id . '_' . time() . '.' . $extension;
                        $target_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $target_path)) {
                            if (!empty($current_user['avatar'])) {
                                $old_avatar_path = __DIR__ . '/../' . $current_user['avatar'];
                                if (file_exists($old_avatar_path)) {
                                    @unlink($old_avatar_path);
                                }
                            }
                            
                            $avatar_path = 'uploads/users/' . $filename;
                            $user_updates[] = "avatar = ?";
                            $user_params[] = $avatar_path;
                        } else {
                            $update_error = 'Failed to move uploaded file. Please check file permissions.';
                        }
                    } else {
                        if (!in_array($file['type'], $allowed_types)) {
                            $update_error = 'Invalid file type. Please upload a JPG, PNG, or GIF image.';
                        } elseif ($file['size'] > $max_size) {
                            $update_error = 'File size too large. Maximum size is 2MB.';
                        }
                    }
                }
            }
            
            // Email update (optional but must be valid if provided)
            if (isset($_POST['email'])) {
                $email = trim($_POST['email']);
                if (!empty($email)) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $check_stmt->execute([$email, $current_user_id]);
                        if ($check_stmt->rowCount() === 0) {
                            $user_updates[] = "email = ?";
                            $user_params[] = $email;
                        } else {
                            $update_error = 'Email address is already in use by another account.';
                        }
                    } else {
                        $update_error = 'Invalid email address format.';
                    }
                }
                // If email is empty, we don't update it (leave existing value)
            }
            
            // First name and last name update (users table)
            // Check if columns exist before updating
            try {
                $check_first_name = $pdo->query("SHOW COLUMNS FROM users LIKE 'first_name'");
                $check_last_name = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_name'");
                $has_first_name = $check_first_name->rowCount() > 0;
                $has_last_name = $check_last_name->rowCount() > 0;
                
                if ($has_first_name && isset($_POST['first_name'])) {
                    $user_updates[] = "first_name = ?";
                    $user_params[] = trim($_POST['first_name']);
                }
                
                if ($has_last_name && isset($_POST['last_name'])) {
                    $user_updates[] = "last_name = ?";
                    $user_params[] = trim($_POST['last_name']);
                }
            } catch (Exception $e) {
                error_log('Error checking first_name/last_name columns: ' . $e->getMessage());
            }
            
            // Department update
            if (isset($_POST['department']) && !empty(trim($_POST['department']))) {
                $user_updates[] = "department = ?";
                $user_params[] = trim($_POST['department']);
            }
            
            // Contact number update
            if (isset($_POST['contact_number']) && !empty(trim($_POST['contact_number']))) {
                try {
                    $check_col = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
                    if ($check_col->rowCount() > 0) {
                        $user_updates[] = "phone = ?";
                        $user_params[] = trim($_POST['contact_number']);
                    } elseif (!empty($current_user['employee_id'])) {
                        $employee_updates[] = "cp_number = ?";
                        $employee_params[] = trim($_POST['contact_number']);
                    }
                } catch (Exception $e) {
                    if (!empty($current_user['employee_id'])) {
                        $employee_updates[] = "cp_number = ?";
                        $employee_params[] = trim($_POST['contact_number']);
                    }
                }
            }
            
            // Employee-specific fields (only position and date_hired, not name)
            if (!empty($current_user['employee_id'])) {
                if (isset($_POST['position']) && !empty(trim($_POST['position']))) {
                    $employee_updates[] = "post = ?";
                    $employee_params[] = trim($_POST['position']);
                }
                
                if (isset($_POST['date_hired']) && !empty(trim($_POST['date_hired']))) {
                    $employee_updates[] = "date_hired = ?";
                    $employee_params[] = trim($_POST['date_hired']);
                }
            }
            
            // Update users table
            if (!empty($user_updates) && empty($update_error)) {
                try {
                    $user_params[] = $current_user_id;
                    $user_sql = "UPDATE users SET " . implode(", ", $user_updates) . ", updated_at = NOW() WHERE id = ?";
                    $user_stmt = $pdo->prepare($user_sql);
                    $user_stmt->execute($user_params);
                    
                    // Update session
                    if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                        $_SESSION['email'] = trim($_POST['email']);
                    }
                    
                    // Update session name from first_name and last_name
                    $first_name = trim($_POST['first_name'] ?? '');
                    $last_name = trim($_POST['last_name'] ?? '');
                    if (!empty($first_name) || !empty($last_name)) {
                        $_SESSION['name'] = trim($first_name . ' ' . $last_name);
                    } elseif (!empty($first_name)) {
                        $_SESSION['name'] = $first_name;
                    } elseif (!empty($last_name)) {
                        $_SESSION['name'] = $last_name;
                    }
                } catch (Exception $e) {
                    error_log('Profile update error: ' . $e->getMessage());
                    $update_error = 'An error occurred while updating your profile.';
                }
            }
            
            // Update employees table
            if (!empty($current_user['employee_id']) && !empty($employee_updates) && empty($update_error)) {
                try {
                    $employee_params[] = $current_user['employee_id'];
                    $employee_sql = "UPDATE employees SET " . implode(", ", $employee_updates) . ", updated_at = NOW() WHERE id = ?";
                    $employee_stmt = $pdo->prepare($employee_sql);
                    $employee_stmt->execute($employee_params);
                } catch (Exception $e) {
                    error_log('Employee update error: ' . $e->getMessage());
                }
            }
            
            // Redirect after update (before header output)
            if (empty($update_error)) {
                header('Location: ?page=profile&updated=1');
                exit;
            } else {
                $_SESSION['profile_update_error'] = $update_error;
                header('Location: ?page=profile');
                exit;
            }
        }
    }
    
    // Handle profile update (AJAX only)
    if ($action === 'update_profile' && $isAjax) {
        // Handle AJAX profile update
        $current_user_id = $_SESSION['user_id'] ?? null;
        
        if (!$current_user_id) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            exit;
        }
        
        try {
            $pdo = get_db_connection();
            
            // Get current user data
            $current_user = get_user_by_id($current_user_id);
            if (!$current_user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            $updates = [];
            $params = [];
            $user_updates = [];
            $user_params = [];
            
            // Update user email if provided and valid
            if (isset($_POST['email']) && !empty(trim($_POST['email']))) {
                $email = trim($_POST['email']);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Check if email is already taken
                    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $check_stmt->execute([$email, $current_user_id]);
                    if ($check_stmt->rowCount() === 0) {
                        $user_updates[] = "email = ?";
                        $user_params[] = $email;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Email address is already in use']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid email address format']);
                    exit;
                }
            }
            
            // Update employee data if user is linked to an employee
            if (!empty($current_user['employee_id'])) {
                if (isset($_POST['first_name']) && !empty(trim($_POST['first_name']))) {
                    $updates[] = "first_name = ?";
                    $params[] = trim($_POST['first_name']);
                }
                
                if (isset($_POST['last_name']) && !empty(trim($_POST['last_name']))) {
                    $updates[] = "surname = ?";
                    $params[] = trim($_POST['last_name']);
                }
                
                if (isset($_POST['contact_number']) && !empty(trim($_POST['contact_number']))) {
                    $updates[] = "cp_number = ?";
                    $params[] = trim($_POST['contact_number']);
                }
                
                if (isset($_POST['position']) && !empty(trim($_POST['position']))) {
                    $updates[] = "post = ?";
                    $params[] = trim($_POST['position']);
                }
                
                if (isset($_POST['date_hired']) && !empty(trim($_POST['date_hired']))) {
                    $updates[] = "date_hired = ?";
                    $params[] = trim($_POST['date_hired']);
                }
                
                // Update employees table
                if (!empty($updates)) {
                    $params[] = $current_user['employee_id'];
                    $sql = "UPDATE employees SET " . implode(", ", $updates) . ", updated_at = NOW() WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                }
            }
            
            // Update users table
            if (!empty($user_updates)) {
                // Update department if provided
                if (isset($_POST['department']) && !empty(trim($_POST['department']))) {
                    $user_updates[] = "department = ?";
                    $user_params[] = trim($_POST['department']);
                }
                
                $user_params[] = $current_user_id;
                $user_sql = "UPDATE users SET " . implode(", ", $user_updates) . ", updated_at = NOW() WHERE id = ?";
                $user_stmt = $pdo->prepare($user_sql);
                $user_stmt->execute($user_params);
            } elseif (isset($_POST['department']) && !empty(trim($_POST['department']))) {
                // Only department update
                $user_sql = "UPDATE users SET department = ?, updated_at = NOW() WHERE id = ?";
                $user_stmt = $pdo->prepare($user_sql);
                $user_stmt->execute([trim($_POST['department']), $current_user_id]);
            }
            
            // Update session variables
            if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['email'] = trim($_POST['email']);
            }
            
            if (isset($_POST['first_name']) || isset($_POST['last_name'])) {
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                if (!empty($first_name) || !empty($last_name)) {
                    $_SESSION['name'] = trim($first_name . ' ' . $last_name);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } catch (Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while updating your profile']);
        }
        exit;
    }
    
    // Other POST handlers can be added here
    // Skip JSON response for non-AJAX profile updates
    if (!(!$isAjax && $page === 'profile' && $action === 'update_profile')) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    // For profile page non-AJAX, continue to include the page normally
}

// Redirect to dashboard if no page parameter is set
if (!isset($_GET['page'])) {
    header('Location: ?page=dashboard');
    exit;
}

// Include the header which handles routing
include '../includes/header.php';
?>
