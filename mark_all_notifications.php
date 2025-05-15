<?php
include 'connect.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    // Update all admin notifications as read
    $sql = "UPDATE notifications SET is_read = 1 WHERE recipient_type = 'admin' AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    // Redirect back to admin page
    header("Location: admin.php");
} else {
    $user_id = $_SESSION['user']['IDNO'];
    
    // Update all user notifications as read
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND recipient_type = 'user' AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    
    // Redirect back to the dashboard
    header("Location: dashboard.php");
}
exit;
?> 