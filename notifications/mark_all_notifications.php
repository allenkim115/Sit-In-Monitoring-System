<?php
include '../includes/connect.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login.php');
    exit;
}

try {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        // Update all admin notifications as read
        $sql = "UPDATE notifications SET is_read = 1 WHERE recipient_type = 'admin' AND is_read = 0";
        if (!$conn->query($sql)) {
            throw new Exception("Error updating admin notifications: " . $conn->error);
        }
        
        // Redirect back to admin page
        header("Location: ../admin/admin.php");
    } else {
        $user_id = $_SESSION['user']['IDNO'];
        
        // Update all user notifications as read
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND recipient_type = 'user' AND is_read = 0";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        $stmt->bind_param("s", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Error updating user notifications: " . $stmt->error);
        }
        
        // Redirect back to the dashboard
        header("Location: ../student/dashboard.php");
    }
} catch (Exception $e) {
    error_log("Error in mark_all_notifications.php: " . $e->getMessage());
    $_SESSION['error'] = "Failed to mark notifications as read. Please try again.";
    
    // Redirect back to appropriate page with error
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        header("Location: ../admin/admin.php");
    } else {
        header("Location: ../student/dashboard.php");
    }
}
exit;
?> 