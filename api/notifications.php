<?php
/**
 * Notifications API
 * Handles notification actions: mark as read, dismiss, clear
 */

session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'mark_read':
            $notificationId = $_POST['notification_id'] ?? 0;
            $notificationType = $_POST['notification_type'] ?? 'alert'; // alert, license, clearance
            
            if (!$notificationId) {
                throw new Exception('Notification ID is required');
            }
            
            $sql = "INSERT INTO notification_status (user_id, notification_id, notification_type, is_read, read_at) 
                    VALUES (?, ?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW(), is_dismissed = 0";
            
            execute_query($sql, [$userId, $notificationId, $notificationType]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
            break;
            
        case 'dismiss':
            $notificationId = $_POST['notification_id'] ?? 0;
            $notificationType = $_POST['notification_type'] ?? 'alert';
            
            if (!$notificationId) {
                throw new Exception('Notification ID is required');
            }
            
            $sql = "INSERT INTO notification_status (user_id, notification_id, notification_type, is_dismissed, dismissed_at) 
                    VALUES (?, ?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE is_dismissed = 1, dismissed_at = NOW()";
            
            execute_query($sql, [$userId, $notificationId, $notificationType]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification dismissed'
            ]);
            break;
            
        case 'mark_all_read':
            // Mark all current notifications as read
            $sql = "INSERT INTO notification_status (user_id, notification_id, notification_type, is_read, read_at)
                    SELECT ?, ea.id, 'alert', 1, NOW()
                    FROM employee_alerts ea
                    WHERE ea.status = 'active'
                    ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()";
            
            execute_query($sql, [$userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
            break;
            
        case 'clear_all':
            // Dismiss all current notifications
            $sql = "INSERT INTO notification_status (user_id, notification_id, notification_type, is_dismissed, dismissed_at)
                    SELECT ?, ea.id, 'alert', 1, NOW()
                    FROM employee_alerts ea
                    WHERE ea.status = 'active'
                    ON DUPLICATE KEY UPDATE is_dismissed = 1, dismissed_at = NOW()";
            
            execute_query($sql, [$userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications cleared'
            ]);
            break;
            
        case 'get_unread_count':
            // Get count of unread notifications
            $sql = "SELECT COUNT(*) as count
                    FROM employee_alerts ea
                    LEFT JOIN notification_status ns ON ea.id = ns.notification_id 
                        AND ns.user_id = ? 
                        AND ns.notification_type = 'alert'
                    WHERE ea.status = 'active' 
                    AND (ns.is_read IS NULL OR ns.is_read = 0)
                    AND (ns.is_dismissed IS NULL OR ns.is_dismissed = 0)";
            
            $stmt = execute_query($sql, [$userId]);
            $result = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'count' => (int)$result['count']
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
