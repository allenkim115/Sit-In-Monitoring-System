<?php
include 'connect.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
    } else {
        header('Location: login.php');
    }
    exit;
}

if (isset($_GET['notification_id'])) {
    $notification_id = intval($_GET['notification_id']);
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        // For admin notifications
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND recipient_type = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $notification_id);
        $success = $stmt->execute();
        
        if ($is_ajax) {
            echo json_encode(['success' => $success]);
            exit;
        }
        
        // Get the redirect URL from the notification
        $sql = "SELECT type FROM notifications WHERE id = ? AND recipient_type = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $type = $row['type'];
            if ($type === 'reservation_request') {
                header("Location: reservation_management.php?filter=Pending");
            } else {
                header("Location: admin.php");
            }
        } else {
            header("Location: admin.php");
        }
    } else {
        // For user notifications
        $user_id = $_SESSION['user']['IDNO'];
        
        // Update the notification as read
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND recipient_type = 'user'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $notification_id, $user_id);
        $success = $stmt->execute();
        
        if ($is_ajax) {
            echo json_encode(['success' => $success]);
            exit;
        }
        
        // Get the redirect URL from the notification
        $sql = "SELECT type FROM notifications WHERE id = ? AND user_id = ? AND recipient_type = 'user'";
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
    }
} else {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'No notification ID provided']);
    } else {
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            header("Location: admin.php");
        } else {
            header("Location: dashboard.php");
        }
    }
}
exit;
?> 