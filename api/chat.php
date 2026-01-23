<?php
/**
 * Chat API - Private Messaging System
 * Handles all chat-related operations with proper security and validation
 */

// Bootstrap application
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/paths.php';

// Enforce JSON responses
header('Content-Type: application/json');

// Security: Check if user is authenticated
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User ID not found in session']);
    exit;
}

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper function to check if soft-delete columns exist
function hasSoftDeleteColumns($pdo) {
    static $checked = null;
    static $result = false;
    
    if ($checked === null) {
        try {
            // Check for both columns to be sure
            $stmt = $pdo->query("SHOW COLUMNS FROM chat_messages WHERE Field IN ('deleted_by_sender', 'deleted_by_receiver')");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $result = count($columns) >= 2; // Both columns must exist
            $checked = true;
            
            // Log for debugging
            if (!$result) {
                error_log('Chat API: Soft delete columns not found. Found: ' . implode(', ', $columns));
            }
        } catch (Exception $e) {
            error_log('Chat API: Error checking soft delete columns: ' . $e->getMessage());
            $result = false;
            $checked = true;
        }
    }
    
    return $result;
}

try {
    $pdo = get_db_connection();
    $hasSoftDelete = hasSoftDeleteColumns($pdo);
    
    switch ($action) {
        case 'get_users':
            // Get list of users for contact panel (excluding current user)
            // Sorted by most recent conversation first
            $search = $_GET['search'] ?? '';
            
            // Build query with or without soft-delete columns
            // Only exclude messages that CURRENT USER has deleted, not the other user
            if ($hasSoftDelete) {
                $sql = "SELECT u.id, u.username, u.name, u.role, u.avatar, u.last_login, u.status,
                               (SELECT MAX(created_at) 
                                FROM chat_messages 
                                WHERE ((sender_id = u.id AND receiver_id = ? AND deleted_by_receiver = 0) 
                                    OR (sender_id = ? AND receiver_id = u.id AND deleted_by_sender = 0))
                               ) as last_message_time
                        FROM users u
                        WHERE u.id != ? AND u.status = 'active'";
            } else {
                $sql = "SELECT u.id, u.username, u.name, u.role, u.avatar, u.last_login, u.status,
                               (SELECT MAX(created_at) 
                                FROM chat_messages 
                                WHERE (sender_id = u.id AND receiver_id = ?) 
                                   OR (sender_id = ? AND receiver_id = u.id)
                               ) as last_message_time
                        FROM users u
                        WHERE u.id != ? AND u.status = 'active'";
            }
            
            $params = [$current_user_id, $current_user_id, $current_user_id];
            
            if (!empty($search)) {
                $sql .= " AND (u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Order by: users with recent messages first, then alphabetically
            $sql .= " ORDER BY 
                      CASE WHEN last_message_time IS NOT NULL THEN 0 ELSE 1 END,
                      last_message_time DESC,
                      u.name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get unread counts and last message preview for each user
            foreach ($users as &$user) {
                // Get unread count (exclude deleted messages if columns exist)
                if ($hasSoftDelete) {
                    $unreadStmt = $pdo->prepare(
                        "SELECT COUNT(*) as unread_count 
                         FROM chat_messages 
                         WHERE sender_id = ? AND receiver_id = ? AND is_read = 0 
                           AND deleted_by_receiver = 0"
                    );
                } else {
                    $unreadStmt = $pdo->prepare(
                        "SELECT COUNT(*) as unread_count 
                         FROM chat_messages 
                         WHERE sender_id = ? AND receiver_id = ? AND is_read = 0"
                    );
                }
                $unreadStmt->execute([$user['id'], $current_user_id]);
                $unreadData = $unreadStmt->fetch(PDO::FETCH_ASSOC);
                $user['unread_count'] = (int)($unreadData['unread_count'] ?? 0);
                
                // Get last message preview (exclude deleted messages if columns exist)
                // Only exclude messages that CURRENT USER has deleted, not the other user
                if ($hasSoftDelete) {
                    $lastMsgStmt = $pdo->prepare(
                        "SELECT message, created_at, sender_id
                         FROM chat_messages 
                         WHERE ((sender_id = ? AND receiver_id = ? AND deleted_by_receiver = 0) 
                            OR (sender_id = ? AND receiver_id = ? AND deleted_by_sender = 0))
                         ORDER BY created_at DESC 
                         LIMIT 1"
                    );
                } else {
                    $lastMsgStmt = $pdo->prepare(
                        "SELECT message, created_at, sender_id
                         FROM chat_messages 
                         WHERE (sender_id = ? AND receiver_id = ?) 
                            OR (sender_id = ? AND receiver_id = ?)
                         ORDER BY created_at DESC 
                         LIMIT 1"
                    );
                }
                $lastMsgStmt->execute([$user['id'], $current_user_id, $current_user_id, $user['id']]);
                $lastMsg = $lastMsgStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($lastMsg) {
                    $user['last_message'] = mb_substr($lastMsg['message'], 0, 50);
                    $user['last_message_time'] = $lastMsg['created_at'];
                    $user['last_message_from_me'] = ($lastMsg['sender_id'] == $current_user_id);
                } else {
                    $user['last_message'] = null;
                    $user['last_message_time'] = null;
                    $user['last_message_from_me'] = false;
                }
                
                // Format avatar path using helper function
                $user['avatar_url'] = get_avatar_url($user['avatar'] ?? null);
            }
            
            echo json_encode(['success' => true, 'users' => $users]);
            break;
            
        case 'get_messages':
            // Get messages between current user and selected user
            $other_user_id = (int)($_GET['user_id'] ?? 0);
            
            if ($other_user_id <= 0) {
                throw new Exception('Invalid user ID');
            }
            
            // Validate that other user exists and is not the current user
            if ($other_user_id === $current_user_id) {
                throw new Exception('Cannot chat with yourself');
            }
            
            $limit = (int)($_GET['limit'] ?? 50);
            $before_id = (int)($_GET['before_id'] ?? 0);
            
            // Build query for messages between these two users
            // Exclude messages that current user has deleted from their view (if columns exist)
            if ($hasSoftDelete) {
                $sql = "SELECT m.*, 
                               sender.name as sender_name, sender.avatar as sender_avatar,
                               receiver.name as receiver_name, receiver.avatar as receiver_avatar
                        FROM chat_messages m
                        INNER JOIN users sender ON m.sender_id = sender.id
                        INNER JOIN users receiver ON m.receiver_id = receiver.id
                        WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
                               OR (m.sender_id = ? AND m.receiver_id = ?))
                          AND (
                              (m.sender_id = ? AND m.deleted_by_sender = 0)
                              OR (m.receiver_id = ? AND m.deleted_by_receiver = 0)
                          )";
                $params = [
                    $current_user_id, $other_user_id, 
                    $other_user_id, $current_user_id,
                    $current_user_id, $current_user_id
                ];
            } else {
                $sql = "SELECT m.*, 
                               sender.name as sender_name, sender.avatar as sender_avatar,
                               receiver.name as receiver_name, receiver.avatar as receiver_avatar
                        FROM chat_messages m
                        INNER JOIN users sender ON m.sender_id = sender.id
                        INNER JOIN users receiver ON m.receiver_id = receiver.id
                        WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
                               OR (m.sender_id = ? AND m.receiver_id = ?))";
                $params = [
                    $current_user_id, $other_user_id, 
                    $other_user_id, $current_user_id
                ];
            }
            
            if ($before_id > 0) {
                $sql .= " AND m.id < ?";
                $params[] = $before_id;
            }
            
            $sql .= " ORDER BY m.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Reverse to show oldest first
            $messages = array_reverse($messages);
            
            // Mark messages as read (messages sent TO current user FROM other user)
            // Only mark messages that haven't been deleted by receiver (if columns exist)
            if ($hasSoftDelete) {
                $markReadStmt = $pdo->prepare(
                    "UPDATE chat_messages 
                     SET is_read = 1, read_at = NOW() 
                     WHERE receiver_id = ? AND sender_id = ? AND is_read = 0 
                       AND deleted_by_receiver = 0"
                );
            } else {
                $markReadStmt = $pdo->prepare(
                    "UPDATE chat_messages 
                     SET is_read = 1, read_at = NOW() 
                     WHERE receiver_id = ? AND sender_id = ? AND is_read = 0"
                );
            }
            $markReadStmt->execute([$current_user_id, $other_user_id]);
            
            // Format messages
            foreach ($messages as &$msg) {
                $msg['is_mine'] = ($msg['sender_id'] == $current_user_id);
                $msg['sender_avatar_url'] = get_avatar_url($msg['sender_avatar'] ?? null);
                
                // Add attachment URL if exists
                if (!empty($msg['attachment_path'])) {
                    $msg['attachment_url'] = '/' . $msg['attachment_path'];
                } else {
                    $msg['attachment_url'] = null;
                }
            }
            
            echo json_encode([
                'success' => true, 
                'messages' => $messages,
                'current_user_id' => $current_user_id
            ]);
            break;
            
        case 'send_message':
            // Send a new message
            $receiver_id = (int)($_POST['receiver_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            
            // Validation
            if ($receiver_id <= 0) {
                throw new Exception('Invalid receiver ID');
            }
            
            if (empty($message)) {
                throw new Exception('Message cannot be empty');
            }
            
            if ($receiver_id === $current_user_id) {
                throw new Exception('Cannot send message to yourself');
            }
            
            if (mb_strlen($message) > 5000) {
                throw new Exception('Message is too long (max 5000 characters)');
            }
            
            // Verify receiver exists and is active
            $checkStmt = $pdo->prepare("SELECT id, status FROM users WHERE id = ?");
            $checkStmt->execute([$receiver_id]);
            $receiver = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$receiver) {
                throw new Exception('Receiver not found');
            }
            
            if ($receiver['status'] !== 'active') {
                throw new Exception('Receiver account is not active');
            }
            
            // Sanitize message
            $message = sanitize_input($message);
            
            // XSS and SQL injection checks
            if (!check_xss($message)) {
                throw new Exception('Invalid message content detected');
            }
            
            // Insert message
            $insertStmt = $pdo->prepare(
                "INSERT INTO chat_messages (sender_id, receiver_id, message, created_at) 
                 VALUES (?, ?, ?, NOW())"
            );
            $insertStmt->execute([$current_user_id, $receiver_id, $message]);
            $message_id = $pdo->lastInsertId();
            
            // Get the inserted message with user details
            $getStmt = $pdo->prepare(
                "SELECT m.*, 
                        sender.name as sender_name, sender.avatar as sender_avatar
                 FROM chat_messages m
                 INNER JOIN users sender ON m.sender_id = sender.id
                 WHERE m.id = ?"
            );
            $getStmt->execute([$message_id]);
            $newMessage = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($newMessage) {
                $newMessage['is_mine'] = true;
                $newMessage['sender_avatar_url'] = get_avatar_url($newMessage['sender_avatar'] ?? null);
            }
            
            // Log security event
            if (function_exists('log_security_event')) {
                log_security_event(
                    'INFO Chat Message Sent',
                    "User ID: {$current_user_id} sent message to User ID: {$receiver_id}"
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $newMessage,
                'message_id' => $message_id
            ]);
            break;
            
        case 'get_unread_count':
            // Get total unread message count for current user (exclude deleted messages if columns exist)
            if ($hasSoftDelete) {
                $stmt = $pdo->prepare(
                    "SELECT sender_id, COUNT(*) as count 
                     FROM chat_messages 
                     WHERE receiver_id = ? AND is_read = 0 AND deleted_by_receiver = 0
                     GROUP BY sender_id"
                );
            } else {
                $stmt = $pdo->prepare(
                    "SELECT sender_id, COUNT(*) as count 
                     FROM chat_messages 
                     WHERE receiver_id = ? AND is_read = 0
                     GROUP BY sender_id"
                );
            }
            $stmt->execute([$current_user_id]);
            $unreadBySender = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalUnread = 0;
            $unreadMap = [];
            foreach ($unreadBySender as $row) {
                $count = (int)$row['count'];
                $totalUnread += $count;
                $unreadMap[$row['sender_id']] = $count;
            }
            
            echo json_encode([
                'success' => true,
                'total_unread' => $totalUnread,
                'unread_by_user' => $unreadMap
            ]);
            break;
            
        case 'mark_as_read':
            // Mark specific messages as read (exclude deleted messages if columns exist)
            $sender_id = (int)($_POST['sender_id'] ?? 0);
            
            if ($sender_id <= 0) {
                throw new Exception('Invalid sender ID');
            }
            
            if ($hasSoftDelete) {
                $stmt = $pdo->prepare(
                    "UPDATE chat_messages 
                     SET is_read = 1, read_at = NOW() 
                     WHERE receiver_id = ? AND sender_id = ? AND is_read = 0 
                       AND deleted_by_receiver = 0"
                );
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE chat_messages 
                     SET is_read = 1, read_at = NOW() 
                     WHERE receiver_id = ? AND sender_id = ? AND is_read = 0"
                );
            }
            $stmt->execute([$current_user_id, $sender_id]);
            $affected = $stmt->rowCount();
            
            echo json_encode([
                'success' => true,
                'marked_count' => $affected
            ]);
            break;
            
        case 'set_typing_status':
            // Set typing indicator (optional feature)
            $recipient_id = (int)($_POST['recipient_id'] ?? 0);
            $is_typing = (int)($_POST['is_typing'] ?? 0);
            
            if ($recipient_id <= 0) {
                throw new Exception('Invalid recipient ID');
            }
            
            // Insert or update typing status
            $stmt = $pdo->prepare(
                "INSERT INTO chat_typing_status (user_id, recipient_id, is_typing, updated_at) 
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE is_typing = ?, updated_at = NOW()"
            );
            $stmt->execute([$current_user_id, $recipient_id, $is_typing, $is_typing]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get_typing_status':
            // Get typing status from other user
            $other_user_id = (int)($_GET['user_id'] ?? 0);
            
            if ($other_user_id <= 0) {
                throw new Exception('Invalid user ID');
            }
            
            // Get typing status (only recent entries within last 10 seconds)
            $stmt = $pdo->prepare(
                "SELECT is_typing, updated_at 
                 FROM chat_typing_status 
                 WHERE user_id = ? AND recipient_id = ? 
                 AND updated_at >= DATE_SUB(NOW(), INTERVAL 10 SECOND)"
            );
            $stmt->execute([$other_user_id, $current_user_id]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'is_typing' => $status ? (bool)$status['is_typing'] : false
            ]);
            break;
            
        case 'clear_history':
            // Clear chat history with a specific user (only for current user's view)
            // Implements "Delete for me" - other user still sees all messages
            $other_user_id = (int)($_POST['user_id'] ?? 0);
            
            if ($other_user_id <= 0) {
                throw new Exception('Invalid user ID');
            }
            
            if ($other_user_id === $current_user_id) {
                throw new Exception('Cannot clear history with yourself');
            }
            
            // Check if soft delete columns exist
            $checkStmt = $pdo->query("SHOW COLUMNS FROM chat_messages WHERE Field IN ('deleted_by_sender', 'deleted_by_receiver')");
            $columns = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($columns) < 2) {
                // Soft delete columns don't exist - return error with migration info
                echo json_encode([
                    'success' => false,
                    'error' => 'Soft delete columns not found. Please run migration: php src/migrations/run_add_soft_delete_columns.php',
                    'feature_available' => false
                ]);
                break;
            }
            
            // Soft delete: Mark messages as deleted for current user only
            // If current user is sender, mark deleted_by_sender = 1
            // If current user is receiver, mark deleted_by_receiver = 1
            $stmt = $pdo->prepare(
                "UPDATE chat_messages 
                 SET deleted_by_sender = CASE 
                        WHEN sender_id = ? THEN 1 
                        ELSE deleted_by_sender 
                     END,
                     deleted_by_receiver = CASE 
                        WHEN receiver_id = ? THEN 1 
                        ELSE deleted_by_receiver 
                     END,
                     updated_at = NOW()
                 WHERE (sender_id = ? AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = ?)"
            );
            $stmt->execute([
                $current_user_id, 
                $current_user_id,
                $current_user_id, 
                $other_user_id, 
                $other_user_id, 
                $current_user_id
            ]);
            $affectedCount = $stmt->rowCount();
            
            // Log security event
            if (function_exists('log_security_event')) {
                log_security_event(
                    'INFO Chat History Cleared',
                    "User ID: {$current_user_id} cleared their view of chat history with User ID: {$other_user_id}. Affected {$affectedCount} messages."
                );
            }
            
            echo json_encode([
                'success' => true,
                'cleared_count' => $affectedCount,
                'feature_available' => true
            ]);
            break;
            
        case 'upload_photo':
            // Handle photo upload for chat messages
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error');
            }
            
            $file = $_FILES['photo'];
            $receiver_id = (int)($_POST['receiver_id'] ?? 0);
            $caption = trim($_POST['caption'] ?? '');
            
            // Validation
            if ($receiver_id <= 0) {
                throw new Exception('Invalid receiver ID');
            }
            
            if ($receiver_id === $current_user_id) {
                throw new Exception('Cannot send photo to yourself');
            }
            
            // Verify receiver exists
            $checkStmt = $pdo->prepare("SELECT id, status FROM users WHERE id = ?");
            $checkStmt->execute([$receiver_id]);
            $receiver = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$receiver || $receiver['status'] !== 'active') {
                throw new Exception('Invalid receiver');
            }
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPG, PNG, WEBP, and GIF images are allowed.');
            }
            
            if ($file['size'] > $maxSize) {
                throw new Exception('File is too large. Maximum size is 5MB.');
            }
            
            // Create upload directory if not exists
            $uploadDir = __DIR__ . '/../uploads/chat_attachments';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate secure filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'chat_' . $current_user_id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $filePath = $uploadDir . '/' . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception('Failed to save uploaded file');
            }
            
            // Sanitize caption
            if (!empty($caption)) {
                $caption = sanitize_input($caption);
                if (!check_xss($caption)) {
                    unlink($filePath);
                    throw new Exception('Invalid caption content detected');
                }
            }
            
            // Create message with attachment
            $message = !empty($caption) ? $caption : '[Photo]';
            $attachmentType = 'image';
            $attachmentPath = 'uploads/chat_attachments/' . $filename;
            
            $insertStmt = $pdo->prepare(
                "INSERT INTO chat_messages 
                 (sender_id, receiver_id, message, attachment_type, attachment_path, attachment_size, attachment_name, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $insertStmt->execute([
                $current_user_id, 
                $receiver_id, 
                $message, 
                $attachmentType,
                $attachmentPath,
                $file['size'],
                $file['name']
            ]);
            $message_id = $pdo->lastInsertId();
            
            // Get the inserted message with user details
            $getStmt = $pdo->prepare(
                "SELECT m.*, 
                        sender.name as sender_name, sender.avatar as sender_avatar
                 FROM chat_messages m
                 INNER JOIN users sender ON m.sender_id = sender.id
                 WHERE m.id = ?"
            );
            $getStmt->execute([$message_id]);
            $newMessage = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($newMessage) {
                $newMessage['is_mine'] = true;
                $newMessage['sender_avatar_url'] = get_avatar_url($newMessage['sender_avatar'] ?? null);
                $newMessage['attachment_url'] = '/' . $attachmentPath;
            }
            
            // Log security event
            if (function_exists('log_security_event')) {
                log_security_event(
                    'INFO Chat Photo Upload',
                    "User ID: {$current_user_id} uploaded photo to User ID: {$receiver_id}"
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $newMessage,
                'message_id' => $message_id
            ]);
            break;
            
        case 'check_soft_delete':
            // Diagnostic endpoint to check if soft delete columns exist
            try {
                $checkStmt = $pdo->query("SHOW COLUMNS FROM chat_messages WHERE Field IN ('deleted_by_sender', 'deleted_by_receiver')");
                $columns = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $columnNames = array_column($columns, 'Field');
                $hasBoth = count($columns) >= 2;
                
                echo json_encode([
                    'success' => true,
                    'has_soft_delete' => $hasBoth,
                    'columns_found' => $columnNames,
                    'column_count' => count($columns),
                    'expected' => ['deleted_by_sender', 'deleted_by_receiver'],
                    'message' => $hasBoth 
                        ? 'Soft delete columns are present' 
                        : 'Soft delete columns are missing. Run migration: http://goldenz.local/migrations/run_chat_soft_delete_web.php'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'has_soft_delete' => false
                ]);
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    $message = $e->getMessage();
    $lowerMessage = strtolower($message);

    // Provide a clearer hint when soft-delete columns are missing
    if (strpos($lowerMessage, "unknown column 'deleted_by_sender'") !== false
        || strpos($lowerMessage, "unknown column 'deleted_by_receiver'") !== false) {
        $message = 'Chat soft-delete columns are missing. Run the migration: php src/migrations/run_chat_soft_delete_migration.php';
    } elseif (strpos($lowerMessage, "unknown column") !== false) {
        // Generic unknown column error - might be attachment columns
        $message = 'Database schema may be outdated. Please run all chat migrations.';
    }

    error_log('Chat API Error: ' . $e->getMessage() . ' | Action: ' . $action);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
}
