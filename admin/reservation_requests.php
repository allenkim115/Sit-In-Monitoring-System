<?php
define('INCLUDED_IN_MAIN_FILE', true); // Define a constant to check if the file is included
require_once '../includes/connect.php';
require_once '../notifications/notification_functions.php';

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug logging
    error_log("POST request received: " . print_r($_POST, true));
    
    if (isset($_POST['action']) && isset($_POST['reservation_id'])) {
        $reservation_id = $_POST['reservation_id'];
        $action = $_POST['action'];
        
        error_log("Processing action: " . $action . " for reservation: " . $reservation_id);
        
        if ($action === 'approve' || $action === 'reject') {
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get the PC and room information from the reservation first
                $sql_get_info = "SELECT room_number, pc_number, idno, purpose, reservation_date, time_slot FROM reservations WHERE id = ?";
                $stmt_info = $conn->prepare($sql_get_info);
                $stmt_info->bind_param("i", $reservation_id);
                $stmt_info->execute();
                $result_info = $stmt_info->get_result();
                $res_info = $result_info->fetch_assoc();
                
                if (!$res_info) {
                    throw new Exception("Reservation not found");
                }

                error_log("Reservation info: " . print_r($res_info, true));

                // Update reservation status and rejection reason if provided
                if ($action === 'reject') {
                    if (!isset($_POST['rejection_reason']) || empty($_POST['rejection_reason'])) {
                        throw new Exception("Rejection reason is required");
                    }
                    $rejection_reason = $_POST['rejection_reason'];
                    $sql = "UPDATE reservations SET status = ?, rejection_reason = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssi", $status, $rejection_reason, $reservation_id);
                    
                    error_log("Executing rejection update with reason: " . $rejection_reason);
                } else {
                    $sql = "UPDATE reservations SET status = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $status, $reservation_id);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update reservation status: " . $stmt->error);
                }
                
                error_log("Status update successful");
                
                // If approved, update PC status to 'used'
                if ($action === 'approve') {
                    // Update PC status to 'used'
                    $sql_update_pc = "UPDATE pc_status SET status = 'used' WHERE room_number = ? AND pc_number = ?";
                    $stmt_pc = $conn->prepare($sql_update_pc);
                    $stmt_pc->bind_param("ss", $res_info['room_number'], $res_info['pc_number']);
                    $stmt_pc->execute();

                    // Create sit-in record
                    $sql_sitin = "INSERT INTO sitin_records (IDNO, PURPOSE, LABORATORY, TIME_IN) VALUES (?, ?, ?, ?)";
                    $stmt_sitin = $conn->prepare($sql_sitin);
                    $laboratory = $res_info['room_number'];
                    $time_in = $res_info['reservation_date'] . " " . explode("-", $res_info['time_slot'])[0];
                    $stmt_sitin->bind_param("isss", $res_info['idno'], $res_info['purpose'], $laboratory, $time_in);
                    $stmt_sitin->execute();
                }
                
                // Create notification (short and correct PC label)
                $pc_label = (stripos($res_info['pc_number'], 'PC') === 0) ? $res_info['pc_number'] : 'PC' . $res_info['pc_number'];
                $message = "Reservation for Room {$res_info['room_number']}, {$pc_label} on {$res_info['reservation_date']} ({$res_info['time_slot']}) " . 
                    ($status === 'approved' ? 'approved.' : 'rejected.' . 
                    ($status === 'rejected' && isset($_POST['rejection_reason']) ? " Reason: {$_POST['rejection_reason']}" : ''));
                
                // Set notification type based on status
                $notification_type = $status === 'approved' ? 'reservation_approved' : 'reservation_rejected';
                
                // Debug logging
                error_log("Creating notification - Type: " . $notification_type . ", Message: " . $message);
                
                // Create notification for user
                createNotification($res_info['idno'], $notification_type, $message, 'user');
                
                // Create notification for admin
                $admin_message = "Reservation for Room {$res_info['room_number']}, {$pc_label} on {$res_info['reservation_date']} ({$res_info['time_slot']}) has been " . 
                    $status . ($status === 'rejected' && isset($_POST['rejection_reason']) ? ". Reason: {$_POST['rejection_reason']}" : '.');
                createNotification(NULL, $notification_type, $admin_message, 'admin');
                
                // Debug logging
                error_log("Created notifications for both user and admin");
                
                // Commit transaction
                $conn->commit();
                
                // Set success message and redirect
                $_SESSION['success'] = "Reservation has been " . $status;
                
                // Redirect to current sit-in page if approved, otherwise stay on reservation requests
                if ($action === 'approve') {
                    header("Location: currentSitin.php");
                } else {
                    header("Location: reservation_requests.php");
                }
                exit();
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $_SESSION['error'] = "Error updating reservation status: " . $e->getMessage();
                header("Location: reservation_requests.php");
                exit();
            }
        }
    }
}

// Profile picture logic
$username = $_SESSION['user']['USERNAME'];
$sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param("s", $username);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$user = $result_profile->fetch_assoc();

$profile_pic = !empty($user['PROFILE_PIC']) ? '../uploads/' . $user['PROFILE_PIC'] : '../images/default_pic.png';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total count of pending reservations
$count_query = "SELECT COUNT(*) as total FROM reservations r JOIN user u ON r.idno = u.IDNO WHERE r.status = 'pending'";
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch reservation requests with pagination
$query = "SELECT r.id, r.idno as student_id, CONCAT(u.FIRSTNAME, ' ', u.MIDDLENAME, ' ', u.LASTNAME) as name, r.room_number as room, r.pc_number as seat, CONCAT(r.reservation_date, ' ', SUBSTRING_INDEX(r.time_slot, '-', 1)) as datetime, r.purpose, r.status 
          FROM reservations r 
          JOIN user u ON r.idno = u.IDNO 
          WHERE r.status = 'pending' 
          ORDER BY r.reservation_date ASC, r.time_slot ASC
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reservations[] = $row;
}

include 'search_modal.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Requests</title>
    <link rel="stylesheet" href="../css/w3.css">
    <link rel="stylesheet" href="../css/side_nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: #f4f6fb;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            padding: 32px 36px 24px 36px;
        }
        .title-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }
        .title-bar i {
            color: #5a3ec8;
            font-size: 1.6rem;
        }
        .title-bar h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #2d2d2d;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
        }
        th {
            background: #f0f2fa;
            color: #5a3ec8;
            font-weight: 700;
            border-radius: 8px 8px 0 0;
        }
        tr {
            background: #f9fafd;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(90,62,200,0.04);
        }
        tr:nth-child(even) {
            background: #f3f5fa;
        }
        td {
            color: #333;
            font-size: 1rem;
            border-bottom: 1px solid #eaeaea;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border: none;
            border-radius: 7px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
        }
        .btn-approve {
            background: #e6f9ed;
            color: #1a7f37;
        }
        .btn-approve:hover {
            background: #1a7f37;
            color: #fff;
        }
        .btn-reject {
            background: #ffeaea;
            color: #c0392b;
        }
        .btn-reject:hover {
            background: #c0392b;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
        <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-arrow-left"></i></button>
        <div class="profile w3-center w3-margin w3-padding">
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal') ? document.getElementById('searchModal').style.display='block' : null" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="lab_schedule.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedule</span></a>
        <a href="lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="reservation_management.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="../logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>
    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">☰</button>
            <h1 style="margin-left: 10px; color: #ffff;">Reservation Requests</h1>
        </div>
        <div class="container">
            <a href="reservation_management.php" class="w3-button w3-light-grey w3-round-large" style="margin-bottom: 18px; display: inline-flex; align-items: center; gap: 6px;"><i class="fa fa-arrow-left"></i> Back to Reservation</a>
            <div class="title-bar">
                <i class="fa-solid fa-calendar-check"></i>
                <h2>Reservation Requests</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Room</th>
                        <th>Seat</th>
                        <th>Date & Time</th>
                        <th>Purpose</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr><td colspan="7" style="text-align:center; color:#aaa;">No pending requests.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $res): ?>
                            <tr>
                                <td><?= htmlspecialchars($res['student_id']) ?></td>
                                <td><?= htmlspecialchars($res['name']) ?></td>
                                <td><?= htmlspecialchars($res['room']) ?></td>
                                <td><?= htmlspecialchars($res['seat']) ?></td>
                                <td><?= date('M d, Y, h:i A', strtotime($res['datetime'])) ?></td>
                                <td><?= htmlspecialchars($res['purpose']) ?></td>
                                <td class="actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-approve"><i class="fa fa-check"></i>Approve</button>
                                        <button type="button" onclick="showRejectModal(<?= $res['id'] ?>)" class="btn btn-reject"><i class="fa fa-times"></i>Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Add pagination controls -->
            <div class="pagination" style="margin-top: 20px; text-align: center;">
                <?php if ($total_pages > 1): ?>
                    <?php if ($page > 1): ?>
                        <a href="?page=1" class="w3-button w3-light-grey">&laquo; First</a>
                        <a href="?page=<?php echo $page - 1; ?>" class="w3-button w3-light-grey">&lsaquo; Previous</a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="w3-button <?php echo $i === $page ? 'w3-blue' : 'w3-light-grey'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="w3-button w3-light-grey">Next &rsaquo;</a>
                        <a href="?page=<?php echo $total_pages; ?>" class="w3-button w3-light-grey">Last &raquo;</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function w3_open() {
            document.getElementById("mySidebar").style.display = "block";
        }
        function w3_close() {
            document.getElementById("mySidebar").style.display = "none";
        }
    </script>
    <!-- Rejection Reason Modal -->
    <div id="rejectModal" class="w3-modal">
        <div class="w3-modal-content w3-card-4 w3-animate-zoom" style="max-width:500px">
            <div class="w3-container">
                <span onclick="document.getElementById('rejectModal').style.display='none'" class="w3-button w3-display-topright">&times;</span>
                <h3>Reject Reservation</h3>
                <form method="POST" action="reservation_requests.php">
                    <input type="hidden" name="reservation_id" id="reject_reservation_id">
                    <input type="hidden" name="action" value="reject">
                    <div class="w3-section">
                        <label><b>Reason for Rejection</b></label>
                        <textarea name="rejection_reason" class="w3-input w3-border w3-round" rows="4" required></textarea>
                    </div>
                    <div class="w3-section">
                        <button type="submit" class="w3-button w3-red w3-round">Reject Reservation</button>
                        <button type="button" onclick="document.getElementById('rejectModal').style.display='none'" class="w3-button w3-light-grey w3-round">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function showRejectModal(reservationId) {
        document.getElementById('reject_reservation_id').value = reservationId;
        document.getElementById('rejectModal').style.display='block';
    }

    // Add error message display
    <?php if (isset($_SESSION['error'])): ?>
        alert('<?php echo $_SESSION['error']; ?>');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    // Add success message display
    <?php if (isset($_SESSION['success'])): ?>
        alert('<?php echo $_SESSION['success']; ?>');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    // Debug logging
    console.log('Script loaded');
    </script>
</body>
</html> 