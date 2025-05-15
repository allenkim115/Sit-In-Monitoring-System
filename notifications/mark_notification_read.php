<?php
include '../includes/connect.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
    } else {
        header('Location: ../login.php');
    }
    exit;
}

try {
    if (isset($_GET['notification_id'])) {
        $notification_id = intval($_GET['notification_id']);
        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            // For admin notifications
            $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND recipient_type = 'admin'";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param("i", $notification_id);
            $success = $stmt->execute();
            
            if ($is_ajax) {
                echo json_encode(['success' => $success]);
                exit;
            }
            
            // Get the redirect URL from the notification
            $sql = "SELECT type FROM notifications WHERE id = ? AND recipient_type = 'admin'";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param("i", $notification_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $type = $row['type'];
                // If type is empty, determine it from the message
                if (empty($type)) {
                    $sql = "SELECT message FROM notifications WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $notification_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($msg_row = $result->fetch_assoc()) {
                        if (strpos($msg_row['message'], 'has been submitted and is pending approval') !== false) {
                            $type = 'reservation_request';
                        } elseif (strpos($msg_row['message'], 'approved') !== false) {
                            $type = 'reservation_approved';
                        } elseif (strpos($msg_row['message'], 'rejected') !== false) {
                            $type = 'reservation_rejected';
                        }
                    }
                }
                
                if ($type === 'reservation_request' || empty($type)) {
                    header("Location: ../admin/reservation_management.php?filter=Pending");
                } elseif ($type === 'reservation_approved') {
                    header("Location: ../admin/reservation_management.php?filter=Approved");
                } elseif ($type === 'reservation_rejected') {
                    header("Location: ../admin/reservation_management.php?filter=Rejected");
                } else {
                    header("Location: ../admin/admin.php");
                }
            } else {
                header("Location: ../admin/admin.php");
            }
        } else {
            // For user notifications
            $user_id = $_SESSION['user']['IDNO'];
            
            // Update the notification as read
            $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND recipient_type = 'user'";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param("is", $notification_id, $user_id);
            $success = $stmt->execute();
            
            if ($is_ajax) {
                echo json_encode(['success' => $success]);
                exit;
            }
            
            // Get the redirect URL from the notification
            $sql = "SELECT type FROM notifications WHERE id = ? AND user_id = ? AND recipient_type = 'user'";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param("is", $notification_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $type = $row['type'];
                if ($type === 'reservation_approved' || $type === 'reservation_rejected') {
                    header("Location: ../student/make_reservation.php?filter=" . ($type === 'reservation_approved' ? 'Approved' : 'Rejected'));
                } else {
                    header("Location: ../student/dashboard.php");
                }
            } else {
                header("Location: ../student/dashboard.php");
            }
        }
    } else {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'No notification ID provided']);
        } else {
            if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
                header("Location: ../admin/admin.php");
            } else {
                header("Location: ../student/dashboard.php");
            }
        }
    }
} catch (Exception $e) {
    error_log("Error in mark_notification_read.php: " . $e->getMessage());
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
    } else {
        $_SESSION['error'] = "Failed to mark notification as read. Please try again.";
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            header("Location: ../admin/admin.php");
        } else {
            header("Location: ../student/dashboard.php");
        }
    }
}
exit;
?> 