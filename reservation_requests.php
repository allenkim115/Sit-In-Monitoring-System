<?php
require_once 'connect.php';
require_once 'notification_functions.php';

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['reservation_id'])) {
        $reservation_id = $_POST['reservation_id'];
        $action = $_POST['action'];
        
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

                // Update reservation status
                $sql = "UPDATE reservations SET status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $status, $reservation_id);
                $stmt->execute();
                
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
                $message = "Reservation for Room {$res_info['room_number']}, {$pc_label} on {$res_info['reservation_date']} ({$res_info['time_slot']}) " . ($status === 'approved' ? 'approved.' : 'rejected.');
                $notification_type = $status === 'approved' ? 'reservation_approved' : 'reservation_rejected';
                createNotification($res_info['idno'], $notification_type, $message);
                
                // Commit transaction
                $conn->commit();
                $success_message = "Reservation " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!";
                
                // Redirect to current sit-in page if approved
                if ($action === 'approve') {
                    header("Location: currentSitin.php");
                    exit();
                }
                
                $_SESSION['success'] = "Reservation has been " . $status;
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error_message = "Error updating reservation status: " . $e->getMessage();
            }
        }
    }
}

// Profile picture logic (copied from admin.php)
$profile_pic = 'images/default_pic.png';
if (isset($_SESSION['user']['USERNAME'])) {
    $username = $_SESSION['user']['USERNAME'];
    $sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
    $stmt_profile = $conn->prepare($sql_profile);
    $stmt_profile->bind_param("s", $username);
    $stmt_profile->execute();
    $result_profile = $stmt_profile->get_result();
    $user = $result_profile->fetch_assoc();
    if (isset($user['PROFILE_PIC']) && $user['PROFILE_PIC']) {
        $profile_pic = $user['PROFILE_PIC'];
    }
}

// Fetch reservation requests (example query, adjust table/fields as needed)
$query = "SELECT r.id, r.idno as student_id, CONCAT(u.FIRSTNAME, ' ', u.MIDDLENAME, ' ', u.LASTNAME) as name, r.room_number as room, r.pc_number as seat, CONCAT(r.reservation_date, ' ', SUBSTRING_INDEX(r.time_slot, '-', 1)) as datetime, r.purpose, r.status 
          FROM reservations r 
          JOIN user u ON r.idno = u.IDNO 
          WHERE r.status = 'pending' 
          ORDER BY r.reservation_date ASC, r.time_slot ASC";
$result = mysqli_query($conn, $query);

$reservations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reservations[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Requests</title>
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="side_nav.css">
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
        <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
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
                                        <button type="submit" name="action" value="reject" class="btn btn-reject"><i class="fa fa-times"></i>Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
</body>
</html> 