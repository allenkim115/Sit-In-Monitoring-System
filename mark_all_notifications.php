<?php
include 'connect.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['IDNO'];

// Update all notifications as read for this user
$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();

// Redirect back to the dashboard
header("Location: dashboard.php");
exit;
?> 