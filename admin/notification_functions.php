<?php
require_once '../includes/connect.php';

/**
 * Creates a notification for a user or admin
 * 
 * @param string|null $user_id The ID number of the user (null for admin notifications)
 * @param string $type The type of notification (e.g., 'reservation_approved', 'reservation_rejected')
 * @param string $message The notification message
 * @param string $recipient_type Either 'user' or 'admin'
 * @return bool True if notification was created successfully, false otherwise
 */
function createNotification($user_id, $type, $message, $recipient_type) {
    global $conn;
    
    try {
        $sql = "INSERT INTO notifications (user_id, type, message, recipient_type, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $user_id, $type, $message, $recipient_type);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets notifications for a specific user or admin
 * 
 * @param string|null $user_id The ID number of the user (null for admin notifications)
 * @param string $recipient_type Either 'user' or 'admin'
 * @param int $limit Optional limit on number of notifications to return
 * @return array Array of notifications
 */
function getNotifications($user_id, $recipient_type, $limit = 10) {
    global $conn;
    
    try {
        $sql = "SELECT * FROM notifications 
                WHERE (user_id = ? OR (user_id IS NULL AND recipient_type = 'admin'))
                AND recipient_type = ?
                ORDER BY created_at DESC 
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $user_id, $recipient_type, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        return $notifications;
    } catch (Exception $e) {
        error_log("Error getting notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Marks a notification as read
 * 
 * @param int $notification_id The ID of the notification to mark as read
 * @return bool True if notification was marked as read successfully, false otherwise
 */
function markNotificationAsRead($notification_id) {
    global $conn;
    
    try {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $notification_id);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets the count of unread notifications for a user or admin
 * 
 * @param string|null $user_id The ID number of the user (null for admin notifications)
 * @param string $recipient_type Either 'user' or 'admin'
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount($user_id, $recipient_type) {
    global $conn;
    
    try {
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE (user_id = ? OR (user_id IS NULL AND recipient_type = 'admin'))
                AND recipient_type = ?
                AND is_read = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $user_id, $recipient_type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'];
    } catch (Exception $e) {
        error_log("Error getting unread notification count: " . $e->getMessage());
        return 0;
    }
}
?> 