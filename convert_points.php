<?php
include 'connect.php';
include 'notification_functions.php';

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['points_to_convert'])) {
    $points_to_convert = (int)$_POST['points_to_convert'];
    $user_id = $_SESSION['user']['IDNO'];
    
    // Validate points to convert
    if ($points_to_convert < 3 || $points_to_convert % 3 !== 0) {
        $_SESSION['error'] = "Points must be in multiples of 3";
        header('Location: dashboard.php');
        exit;
    }
    
    // Get current user points
    $sql = "SELECT POINTS FROM user WHERE IDNO = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($points_to_convert > $user['POINTS']) {
        $_SESSION['error'] = "You don't have enough points";
        header('Location: dashboard.php');
        exit;
    }
    
    // Calculate sessions to add
    $sessions_to_add = $points_to_convert / 3;
    
    // Update user points and sessions
    $sql = "UPDATE user SET POINTS = POINTS - ?, SESSION_COUNT = SESSION_COUNT + ? WHERE IDNO = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $points_to_convert, $sessions_to_add, $user_id);
    
    if ($stmt->execute()) {
        // Create notification
        $message = "You have successfully converted {$points_to_convert} points into {$sessions_to_add} sessions.";
        createNotification($user_id, 'reward_received', $message);
        
        $_SESSION['success'] = "Points converted successfully";
    } else {
        $_SESSION['error'] = "Error converting points";
    }
    
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: dashboard.php');
    exit;
}
?> 