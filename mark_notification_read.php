<?php
include 'connect.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['notification_id'])) {
    $notification_id = intval($_GET['notification_id']);
    $user_id = $_SESSION['user']['IDNO'];
    
    // Update the notification as read
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $notification_id, $user_id);
    $stmt->execute();
    
    // Get the redirect URL from the notification
    $sql = "SELECT type FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $notification_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $type = $row['type'];
        if ($type === 'reservation_approved' || $type === 'reservation_rejected') {
            header("Location: make_reservation.php?filter=" . ($type === 'reservation_approved' ? 'Approved' : 'Rejected'));
        } else {
            header("Location: dashboard.php");
        }
    } else {
        header("Location: dashboard.php");
    }
} else {
    header("Location: dashboard.php");
}
exit;
?> 