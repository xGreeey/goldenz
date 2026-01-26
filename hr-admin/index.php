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
    header('Location: /landing/');
    exit;
}

// Check if user has appropriate role for HR Admin portal
$user_role = $_SESSION['user_role'] ?? null;
$allowed_roles = ['hr_admin', 'hr', 'admin', 'accounting', 'operation', 'logistics'];
if (!in_array($user_role, $allowed_roles)) {
    // Invalid role, redirect to login
    session_destroy();
    header('Location: /landing/');
    exit;
}

// Handle POST requests (AJAX and form submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'] ?? '';
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $page = $_GET['page'] ?? 'dashboard';
    
    // For non-AJAX profile updates and employee exports, let the page handle it (skip JSON response)
    $pageHandledActions = [
        'profile' => ['update_profile'],
        'employees' => ['export_employees']
    ];
    
    $shouldSkipJson = !$isAjax && 
                      isset($pageHandledActions[$page]) && 
                      in_array($action, $pageHandledActions[$page]);
    
    if (!$shouldSkipJson) {
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
    
    // Handle schedule events fetch (AJAX)
    if ($action === 'get_schedule_events' && $isAjax) {
        require_once __DIR__ . '/../includes/database.php';
        $pdo = get_db_connection();
        
        // Function to get schedule events for a specific date
        function get_schedule_events_ajax($pdo, $date) {
            $events = [];
            
            try {
                $dateStr = date('Y-m-d', strtotime($date));
                
                // Get events from employee_alerts table
                $alertsStmt = $pdo->prepare("SELECT 
                        ea.id,
                        ea.title,
                        ea.description,
                        ea.priority,
                        ea.status,
                        ea.created_at,
                        ea.alert_date as event_date,
                        TIME(ea.created_at) as event_time,
                        e.first_name,
                        e.surname,
                        e.post,
                        'alert' as event_source
                    FROM employee_alerts ea
                    LEFT JOIN employees e ON ea.employee_id = e.id
                    WHERE (ea.alert_date = ? OR DATE(ea.created_at) = ?)
                        AND (ea.status = 'active' OR ea.status IS NULL)
                    ORDER BY 
                        FIELD(ea.priority, 'Urgent', 'High', 'Medium', 'Low'),
                        TIME(ea.created_at) ASC,
                        ea.created_at ASC");
                $alertsStmt->execute([$dateStr, $dateStr]);
                $alerts = $alertsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get events from events table
                $eventsStmt = $pdo->prepare("SELECT 
                        e.id,
                        e.title,
                        e.description,
                        CASE 
                            WHEN e.event_type = 'Holiday' THEN 'Urgent'
                            WHEN e.event_type = 'Examination' THEN 'High'
                            WHEN e.event_type = 'Academic' THEN 'Medium'
                            ELSE 'Low'
                        END as priority,
                        'active' as status,
                        CONCAT(e.start_date, ' ', COALESCE(e.start_time, '00:00:00')) as created_at,
                        e.start_date as event_date,
                        COALESCE(e.start_time, '00:00:00') as event_time,
                        NULL as first_name,
                        NULL as surname,
                        NULL as post,
                        'event' as event_source
                    FROM events e
                    WHERE e.start_date = ?
                    ORDER BY 
                        COALESCE(e.start_time, '00:00:00') ASC,
                        e.start_date ASC");
                $eventsStmt->execute([$dateStr]);
                $calendarEvents = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Merge and format events
                $allEvents = array_merge($alerts, $calendarEvents);
                
                // Sort by time
                usort($allEvents, function($a, $b) {
                    $timeA = $a['event_time'] ?? '00:00:00';
                    $timeB = $b['event_time'] ?? '00:00:00';
                    return strcmp($timeA, $timeB);
                });
                
                // Return all events (no limit)
                return $allEvents;
                
            } catch (Exception $e) {
                error_log("Error fetching schedule events: " . $e->getMessage());
                return [];
            }
        }
        
        $requestedDate = $_POST['date'] ?? date('Y-m-d');
        $events = get_schedule_events_ajax($pdo, $requestedDate);
        
        // Format events for JSON response
        $formattedEvents = [];
        foreach ($events as $event) {
            // Format time
            $eventTime = '12:00 AM';
            if (!empty($event['event_time']) && $event['event_time'] !== '00:00:00') {
                $timeParts = explode(':', $event['event_time']);
                $hour = (int)$timeParts[0];
                $minute = (int)$timeParts[1];
                $ampm = $hour >= 12 ? 'PM' : 'AM';
                $hour = $hour % 12;
                $hour = $hour ? $hour : 12;
                $eventTime = sprintf('%d:%02d %s', $hour, $minute, $ampm);
            } elseif (!empty($event['created_at'])) {
                $eventTime = date('g:i A', strtotime($event['created_at']));
            }
            
            // Determine priority class
            $priority = strtolower($event['priority'] ?? 'low');
            $priorityClass = 'event--low';
            $priorityIcon = 'fa-circle-info';
            switch($priority) {
                case 'urgent':
                    $priorityClass = 'event--urgent';
                    $priorityIcon = 'fa-exclamation-triangle';
                    break;
                case 'high':
                    $priorityClass = 'event--high';
                    $priorityIcon = 'fa-exclamation-circle';
                    break;
                case 'medium':
                    $priorityClass = 'event--medium';
                    $priorityIcon = 'fa-circle-exclamation';
                    break;
            }
            
            $employeeName = trim(($event['first_name'] ?? '') . ' ' . ($event['surname'] ?? ''));
            
            $formattedEvents[] = [
                'id' => $event['id'],
                'title' => $event['title'],
                'description' => $event['description'] ?? '',
                'time' => $eventTime,
                'priority' => $priority,
                'priorityClass' => $priorityClass,
                'priorityIcon' => $priorityIcon,
                'employeeName' => $employeeName,
                'post' => $event['post'] ?? '',
                'source' => $event['event_source'] ?? 'alert'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'events' => $formattedEvents,
            'count' => count($formattedEvents)
        ]);
        exit;
    }
    
    // Handle event creation
    if ($action === 'create_event' && $isAjax) {
        require_once __DIR__ . '/../includes/database.php';
        
        try {
            // Validate and sanitize input data
            $title = trim($_POST['title'] ?? '');
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            $start_date = trim($_POST['start_date'] ?? '');
            $start_time = !empty($_POST['start_time']) ? trim($_POST['start_time']) : null;
            $end_date = !empty($_POST['end_date']) ? trim($_POST['end_date']) : null;
            $end_time = !empty($_POST['end_time']) ? trim($_POST['end_time']) : null;
            $event_type = $_POST['event_type'] ?? 'Other';
            $holiday_type = $_POST['holiday_type'] ?? 'N/A';
            $category = !empty($_POST['category']) ? trim($_POST['category']) : null;
            $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
            
            // Validate required fields
            if (empty($title)) {
                throw new InvalidArgumentException('Event title is required.');
            }
            
            if (empty($start_date)) {
                throw new InvalidArgumentException('Start date is required.');
            }
            
            // Validate date format
            $date_format = 'Y-m-d';
            $start_date_obj = DateTime::createFromFormat($date_format, $start_date);
            if (!$start_date_obj || $start_date_obj->format($date_format) !== $start_date) {
                throw new InvalidArgumentException('Invalid start date format.');
            }
            
            // Validate end_date if provided
            if (!empty($end_date)) {
                $end_date_obj = DateTime::createFromFormat($date_format, $end_date);
                if (!$end_date_obj || $end_date_obj->format($date_format) !== $end_date) {
                    throw new InvalidArgumentException('Invalid end date format.');
                }
                
                // Ensure end_date is not before start_date
                if ($end_date_obj < $start_date_obj) {
                    throw new InvalidArgumentException('End date cannot be before start date.');
                }
            }
            
            // Validate event_type enum
            $valid_event_types = ['Holiday', 'Examination', 'Academic', 'Special Event', 'Other'];
            if (!in_array($event_type, $valid_event_types, true)) {
                $event_type = 'Other';
            }
            
            // Validate holiday_type enum
            $valid_holiday_types = ['Regular Holiday', 'Special Non-Working Holiday', 'Local Special Non-Working Holiday', 'N/A'];
            if (!in_array($holiday_type, $valid_holiday_types, true)) {
                $holiday_type = 'N/A';
            }
            
            // Prepare event data array
            $event_data = [
                'title' => $title,
                'description' => $description,
                'start_date' => $start_date,
                'start_time' => $start_time,
                'end_date' => $end_date,
                'end_time' => $end_time,
                'event_type' => $event_type,
                'holiday_type' => $holiday_type,
                'category' => $category,
                'notes' => $notes
            ];
            
            // Create event using database function
            $event_id = create_event($event_data);
            
            if ($event_id !== false && $event_id > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Event created successfully!',
                    'event_id' => (int)$event_id
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create event. Please try again.'
                ]);
            }
        } catch (Exception $e) {
            error_log("Error creating event: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Handle event counts for date range (for day button indicators)
    if ($action === 'get_event_counts' && $isAjax) {
        require_once __DIR__ . '/../includes/database.php';
        $pdo = get_db_connection();
        
        // Handle dates array - can come as 'dates[]' or 'dates'
        $dates = [];
        if (isset($_POST['dates']) && is_array($_POST['dates'])) {
            $dates = $_POST['dates'];
        } elseif (isset($_POST['dates'])) {
            $dates = [$_POST['dates']];
        }
        
        $counts = [];
        
        try {
            foreach ($dates as $dateStr) {
                if (empty($dateStr)) continue;
                $dateStr = date('Y-m-d', strtotime($dateStr));
                
                // Count alerts
                $alertsStmt = $pdo->prepare("SELECT COUNT(*) as count
                    FROM employee_alerts ea
                    WHERE (ea.alert_date = ? OR DATE(ea.created_at) = ?)
                        AND (ea.status = 'active' OR ea.status IS NULL)");
                $alertsStmt->execute([$dateStr, $dateStr]);
                $alertCount = (int)$alertsStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // Count events
                $eventsStmt = $pdo->prepare("SELECT COUNT(*) as count
                    FROM events e
                    WHERE e.start_date = ?");
                $eventsStmt->execute([$dateStr]);
                $eventCount = (int)$eventsStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                $totalCount = $alertCount + $eventCount;
                
                // Get priority breakdown for color coding
                $priorityStmt = $pdo->prepare("SELECT 
                    SUM(CASE WHEN ea.priority = 'urgent' THEN 1 ELSE 0 END) as urgent,
                    SUM(CASE WHEN ea.priority = 'high' THEN 1 ELSE 0 END) as high
                    FROM employee_alerts ea
                    WHERE (ea.alert_date = ? OR DATE(ea.created_at) = ?)
                        AND (ea.status = 'active' OR ea.status IS NULL)");
                $priorityStmt->execute([$dateStr, $dateStr]);
                $priorities = $priorityStmt->fetch(PDO::FETCH_ASSOC);
                $urgentCount = (int)($priorities['urgent'] ?? 0);
                $highCount = (int)($priorities['high'] ?? 0);
                
                // Determine color: red for urgent, gold for high, default for others
                $color = 'default';
                if ($urgentCount > 0) {
                    $color = 'red';
                } elseif ($highCount > 0) {
                    $color = 'gold';
                }
                
                $counts[$dateStr] = [
                    'count' => $totalCount,
                    'color' => $color,
                    'urgent' => $urgentCount,
                    'high' => $highCount
                ];
            }
        } catch (Exception $e) {
            error_log("Error fetching event counts: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'counts' => $counts
        ]);
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
            if (isset($_FILES['avatar']) && !empty($_FILES['avatar']['tmp_name']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
                error_log('Avatar upload attempt - File info: ' . json_encode($_FILES['avatar']));
                
                if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../uploads/users/';
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0755, true)) {
                            error_log('Failed to create upload directory: ' . $upload_dir);
                            $update_error = 'Failed to create upload directory.';
                        }
                    }
                    
                    if (empty($update_error)) {
                        $file = $_FILES['avatar'];
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        $max_size = 2 * 1024 * 1024; // 2MB
                        
                        // Also check by extension as a fallback
                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if ((in_array($file['type'], $allowed_types) || in_array($extension, $allowed_extensions)) && $file['size'] <= $max_size) {
                            $filename = 'user_' . $current_user_id . '_' . time() . '.' . $extension;
                            $target_path = $upload_dir . $filename;
                            
                            error_log('Attempting to move file to: ' . $target_path);
                            
                            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                                error_log('File moved successfully to: ' . $target_path);
                                
                                // Delete old avatar if exists
                                if (!empty($current_user['avatar'])) {
                                    $old_avatar_path = __DIR__ . '/../' . $current_user['avatar'];
                                    if (file_exists($old_avatar_path)) {
                                        @unlink($old_avatar_path);
                                        error_log('Deleted old avatar: ' . $old_avatar_path);
                                    }
                                }
                                
                                $avatar_path = 'uploads/users/' . $filename;
                                $user_updates[] = "avatar = ?";
                                $user_params[] = $avatar_path;
                                error_log('Avatar path set for database update: ' . $avatar_path);
                            } else {
                                error_log('Failed to move uploaded file from ' . $file['tmp_name'] . ' to ' . $target_path);
                                $update_error = 'Failed to move uploaded file. Please check file permissions.';
                            }
                        } else {
                            if (!in_array($file['type'], $allowed_types) && !in_array($extension, $allowed_extensions)) {
                                error_log('Invalid file type: ' . $file['type'] . ', extension: ' . $extension);
                                $update_error = 'Invalid file type. Please upload a JPG, PNG, or GIF image.';
                            } elseif ($file['size'] > $max_size) {
                                error_log('File too large: ' . $file['size'] . ' bytes');
                                $update_error = 'File size too large. Maximum size is 2MB.';
                            }
                        }
                    }
                } else {
                    $upload_errors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
                    ];
                    $error_code = $_FILES['avatar']['error'];
                    $update_error = $upload_errors[$error_code] ?? 'Unknown upload error (code: ' . $error_code . ')';
                    error_log('Avatar upload error: ' . $update_error);
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
                    
                    error_log('Profile update SQL: ' . $user_sql);
                    error_log('Profile update params: ' . json_encode($user_params));
                    
                    $user_stmt = $pdo->prepare($user_sql);
                    $result = $user_stmt->execute($user_params);
                    $rows_affected = $user_stmt->rowCount();
                    
                    error_log('Profile update result: ' . ($result ? 'success' : 'failed') . ', rows affected: ' . $rows_affected);
                    
                    if (!$result) {
                        $error_info = $user_stmt->errorInfo();
                        error_log('Profile update error info: ' . json_encode($error_info));
                        $update_error = 'Failed to update profile in database.';
                    }
                    
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
                    error_log('Profile update exception: ' . $e->getMessage());
                    $update_error = 'An error occurred while updating your profile.';
                }
            } else {
                if (empty($user_updates)) {
                    error_log('Profile update: No updates to apply');
                }
                if (!empty($update_error)) {
                    error_log('Profile update skipped due to error: ' . $update_error);
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
    
    // Handle employee export (must be before any output)
    if ($action === 'export_employees' && $page === 'employees') {
        require_once __DIR__ . '/../includes/database.php';
        
        // Helper functions for export
        if (!function_exists('get_employee_export_columns')) {
            function get_employee_export_columns() {
                try {
                    $pdo = get_db_connection();
                    $stmt = $pdo->query("SHOW COLUMNS FROM employees");
                    $columns = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (!empty($row['Field'])) {
                            $columns[] = $row['Field'];
                        }
                    }
                    return $columns;
                } catch (Exception $e) {
                    return [];
                }
            }
        }
        
        if (!function_exists('humanize_employee_column_label')) {
            function humanize_employee_column_label($column) {
                $overrides = [
                    'id' => 'ID',
                    'employee_no' => 'Employee No',
                    'employee_type' => 'Employee Type',
                    'first_name' => 'First Name',
                    'middle_name' => 'Middle Name',
                    'surname' => 'Surname',
                    'post' => 'Post / Assignment',
                    'license_no' => 'License No',
                    'license_exp_date' => 'License Expiration Date',
                    'rlm_exp' => 'RLM Expiration',
                    'date_hired' => 'Date Hired',
                    'cp_number' => 'Contact Number',
                    'sss_no' => 'SSS No',
                    'pagibig_no' => 'Pag-IBIG No',
                    'tin_number' => 'TIN Number',
                    'philhealth_no' => 'PhilHealth No',
                    'birth_date' => 'Birth Date',
                    'contact_person' => 'Emergency Contact Person',
                    'relationship' => 'Emergency Relationship',
                    'contact_person_address' => 'Emergency Contact Address',
                    'contact_person_number' => 'Emergency Contact Number'
                ];
                if (isset($overrides[$column])) {
                    return $overrides[$column];
                }
                $label = str_replace('_', ' ', $column);
                $label = ucwords($label);
                return $label;
            }
        }
        
        if (!function_exists('format_employee_export_value')) {
            function format_employee_export_value($value) {
                if ($value === null) {
                    return '';
                }
                if (is_bool($value)) {
                    return $value ? 'Yes' : 'No';
                }
                if (is_string($value)) {
                    $trimmed = trim($value);
                    if ($trimmed === '0000-00-00' || $trimmed === '0000-00-00 00:00:00') {
                        return '';
                    }
                    return $value;
                }
                return (string)$value;
            }
        }
        
        $export_all_employees = !empty($_POST['export_all_employees']);
        $export_all_columns = !empty($_POST['export_all_columns']);
        $file_format = isset($_POST['file_format']) && in_array($_POST['file_format'], ['csv', 'xlsx']) ? $_POST['file_format'] : 'csv';
        
        $selected_employee_ids = isset($_POST['employee_ids']) && is_array($_POST['employee_ids'])
            ? array_values(array_unique(array_filter(array_map('intval', $_POST['employee_ids']))))
            : [];
        $selected_columns = isset($_POST['columns']) && is_array($_POST['columns'])
            ? array_values(array_unique(array_filter(array_map('trim', $_POST['columns']))))
            : [];

        $available_columns = get_employee_export_columns();
        $available_column_lookup = array_flip($available_columns);

        if ($export_all_columns) {
            $selected_columns = $available_columns;
        } else {
            $selected_columns = array_values(array_filter($selected_columns, function ($column) use ($available_column_lookup) {
                return isset($available_column_lookup[$column]);
            }));
        }

        if (empty($selected_columns)) {
            $_SESSION['message'] = 'Please select at least one column to export.';
            $_SESSION['message_type'] = 'error';
            header('Location: ?page=employees');
            exit;
        }

        if (!$export_all_employees && empty($selected_employee_ids)) {
            $_SESSION['message'] = 'Please select at least one employee to export.';
            $_SESSION['message_type'] = 'error';
            header('Location: ?page=employees');
            exit;
        }

        try {
            $pdo = get_db_connection();
        } catch (Exception $e) {
            $_SESSION['message'] = 'Unable to export employees right now. Please try again.';
            $_SESSION['message_type'] = 'error';
            header('Location: ?page=employees');
            exit;
        }

        $quoted_columns = array_map(function ($column) {
            return "`" . str_replace('`', '', $column) . "`";
        }, $selected_columns);

        $sql = "SELECT " . implode(', ', $quoted_columns) . " FROM employees";
        $params = [];

        if (!$export_all_employees) {
            $placeholders = implode(',', array_fill(0, count($selected_employee_ids), '?'));
            $sql .= " WHERE id IN ($placeholders)";
            $params = $selected_employee_ids;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $header_labels = array_map('humanize_employee_column_label', $selected_columns);
        
        if ($file_format === 'xlsx' && class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Generate XLSX file
            $filename = 'employees_export_' . date('Y-m-d') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $col = 'A';
            foreach ($header_labels as $header) {
                $sheet->setCellValue($col . '1', $header);
                $sheet->getStyle($col . '1')->getFont()->setBold(true);
                $col++;
            }
            
            // Add data
            $row = 2;
            foreach ($data as $dataRow) {
                $col = 'A';
                foreach ($selected_columns as $column) {
                    $value = format_employee_export_value($dataRow[$column] ?? null);
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        } else {
            // Generate CSV file
            $filename = 'employees_export_' . date('Y-m-d') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');

            $output = fopen('php://output', 'w');
            fputcsv($output, $header_labels);

            foreach ($data as $dataRow) {
                $formatted_row = [];
                foreach ($selected_columns as $column) {
                    $formatted_row[] = format_employee_export_value($dataRow[$column] ?? null);
                }
                fputcsv($output, $formatted_row);
            }

            fclose($output);
        }
        exit;
    }
    
    // Handle feed page create_event action directly here to avoid HTML output
    if ($page === 'feed' && $action === 'create_event' && $isAjax) {
        // Include necessary files
        require_once __DIR__ . '/../includes/database.php';
        
        // Handle the create_event action
        try {
            // Validate and sanitize input data
            $title = trim($_POST['title'] ?? '');
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            $start_date = trim($_POST['start_date'] ?? '');
            $start_time = !empty($_POST['start_time']) ? trim($_POST['start_time']) : null;
            $end_date = !empty($_POST['end_date']) ? trim($_POST['end_date']) : null;
            $end_time = !empty($_POST['end_time']) ? trim($_POST['end_time']) : null;
            $event_type = $_POST['event_type'] ?? 'Other';
            $holiday_type = $_POST['holiday_type'] ?? 'N/A';
            $category = !empty($_POST['category']) ? trim($_POST['category']) : null;
            $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
            
            // Validate required fields
            if (empty($title)) {
                throw new InvalidArgumentException('Event title is required.');
            }
            
            if (empty($start_date)) {
                throw new InvalidArgumentException('Start date is required.');
            }
            
            // Validate date format
            $date_format = 'Y-m-d';
            $start_date_obj = DateTime::createFromFormat($date_format, $start_date);
            if (!$start_date_obj || $start_date_obj->format($date_format) !== $start_date) {
                throw new InvalidArgumentException('Invalid start date format.');
            }
            
            // Validate end_date if provided
            if (!empty($end_date)) {
                $end_date_obj = DateTime::createFromFormat($date_format, $end_date);
                if (!$end_date_obj || $end_date_obj->format($date_format) !== $end_date) {
                    throw new InvalidArgumentException('Invalid end date format.');
                }
                
                // Ensure end_date is not before start_date
                if ($end_date_obj < $start_date_obj) {
                    throw new InvalidArgumentException('End date cannot be before start date.');
                }
            }
            
            // Validate event_type enum
            $valid_event_types = ['Holiday', 'Examination', 'Academic', 'Special Event', 'Other'];
            if (!in_array($event_type, $valid_event_types, true)) {
                $event_type = 'Other';
            }
            
            // Validate holiday_type enum
            $valid_holiday_types = ['Regular Holiday', 'Special Non-Working Holiday', 'Local Special Non-Working Holiday', 'N/A'];
            if (!in_array($holiday_type, $valid_holiday_types, true)) {
                $holiday_type = 'N/A';
            }
            
            // Prepare event data array
            $event_data = [
                'title' => $title,
                'description' => $description,
                'start_date' => $start_date,
                'start_time' => $start_time,
                'end_date' => $end_date,
                'end_time' => $end_time,
                'event_type' => $event_type,
                'holiday_type' => $holiday_type,
                'category' => $category,
                'notes' => $notes
            ];
            
            // Create event using database function
            $event_id = create_event($event_data);
            
            if ($event_id !== false && $event_id > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Event created successfully!',
                    'event_id' => (int)$event_id
                ], JSON_THROW_ON_ERROR);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create event. Please try again.'
                ], JSON_THROW_ON_ERROR);
            }
        } catch (InvalidArgumentException $e) {
            error_log('Event validation error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            error_log('Error creating event: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            echo json_encode([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.'
            ], JSON_THROW_ON_ERROR);
        }
        exit;
    }
    
    // Other POST handlers can be added here
    // Skip JSON response and "Invalid action" for page-handled actions
    $allowPageToHandle = !$isAjax && 
                         isset($pageHandledActions[$page]) && 
                         in_array($action, $pageHandledActions[$page]);
    
    if (!$allowPageToHandle) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    // For page-handled actions, continue to include the page normally
}

// Redirect to dashboard if no page parameter is set
if (!isset($_GET['page'])) {
    header('Location: ?page=dashboard');
    exit;
}

// Include the header which handles routing
include '../includes/header.php';
?>
