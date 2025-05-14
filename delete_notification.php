<?php
include 'connect.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);
    $user_id = $_SESSION['user']['IDNO'];
    
    // Delete the notification
    $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $notification_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Notification deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting notification";
    }
}

// Redirect back to the previous page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?> 