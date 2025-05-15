<?php
include '../includes/connect.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);
    
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        // Delete admin notification
        $sql = "DELETE FROM notifications WHERE id = ? AND recipient_type = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $notification_id);
        $redirect = '../admin/admin.php';
    } else {
        // Delete user notification
        $user_id = $_SESSION['user']['IDNO'];
        $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ? AND recipient_type = 'user'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $notification_id, $user_id);
        $redirect = '../student/dashboard.php';
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Notification deleted successfully";
        error_log("Success message set in session: " . $_SESSION['success']);
    } else {
        $_SESSION['error'] = "Error deleting notification: " . $stmt->error;
        error_log("Error message set in session: " . $_SESSION['error']);
    }
    
    // Ensure session is written before redirect
    session_write_close();
    header("Location: $redirect");
    exit;
}

// Default fallback redirect
header('Location: ../login.php');
exit;
?> 