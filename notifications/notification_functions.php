<?php
include '../includes/connect.php';

function createNotification($user_id, $type, $message, $recipient_type = 'user') {
    global $conn;
    
    // Debug logging for input parameters
    error_log("createNotification called with - User ID: '$user_id', Type: '$type', Recipient Type: '$recipient_type'");
    
    // Only determine type from message if type is empty
    if (empty($type)) {
        error_log("Type is empty, determining from message content");
        // If type is empty, determine it from the message content
        if (strpos($message, 'has been submitted and is pending approval') !== false) {
            $type = 'reservation_request';
        } elseif (strpos($message, 'approved') !== false) {
            $type = 'reservation_approved';
        } elseif (strpos($message, 'rejected') !== false) {
            $type = 'reservation_rejected';
        } else {
            $type = 'reservation_request'; // Default fallback
        }
        error_log("Determined type from message: '$type'");
    }
    
    // Debug logging for final values
    error_log("Creating notification with - Type: '$type', Recipient: '$recipient_type', Message: '$message'");
    
    // For admin notifications, we don't need a user_id
    if ($recipient_type === 'admin') {
        $sql = "INSERT INTO notifications (type, message, recipient_type) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $type, $message, $recipient_type);
    } else {
        $sql = "INSERT INTO notifications (user_id, type, message, recipient_type) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $user_id, $type, $message, $recipient_type);
    }
    
    if ($stmt->execute()) {
        error_log("Notification created successfully with ID: " . $conn->insert_id);
        return true;
    } else {
        error_log("Error creating notification: " . $stmt->error);
        return false;
    }
}

function markNotificationAsRead($notification_id) {
    global $conn;
    
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notification_id);
    
    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Error marking notification as read: " . $stmt->error);
        return false;
    }
}

function getUnreadNotificationCount($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}
?> 