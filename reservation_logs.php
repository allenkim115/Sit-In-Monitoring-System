<?php
require_once 'connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Profile picture logic
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

// Date filter
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Build query for total count
$count_sql = "SELECT COUNT(*) as total FROM reservations r LEFT JOIN user u ON r.idno = u.IDNO";
$count_params = [];
$count_types = '';

if ($date_filter) {
    $count_sql .= " WHERE DATE(r.reservation_date) = ?";
    $count_params[] = $date_filter;
    $count_types .= 's';
}

if ($status_filter && in_array($status_filter, ['pending','approved','rejected'])) {
    $count_sql .= ($date_filter ? " AND" : " WHERE") . " r.status = ?";
    $count_params[] = $status_filter;
    $count_types .= 's';
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Build main query with pagination
$sql = "SELECT r.id, r.idno, CONCAT(u.FIRSTNAME, ' ', u.LASTNAME) as full_name, r.room_number, r.pc_number, r.reservation_date, r.time_slot, r.purpose, r.status, r.rejection_reason 
        FROM reservations r 
        LEFT JOIN user u ON r.idno = u.IDNO";

$params = [];
$types = '';

if ($date_filter) {
    $sql .= " WHERE DATE(r.reservation_date) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

if ($status_filter && in_array($status_filter, ['pending','approved','rejected'])) {
    $sql .= ($date_filter ? " AND" : " WHERE") . " r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$sql .= " ORDER BY r.reservation_date DESC, r.time_slot DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Logs</title>
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <style>
        body { background: #f4f6fb; font-family: 'Segoe UI', Arial, sans-serif; margin: 0; }
        .container { max-width: 1100px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 32px 36px 24px 36px; }
        .title-bar { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
        .title-bar i { color: #5a3ec8; font-size: 1.6rem; }
        .title-bar h2 { margin: 0; font-size: 1.5rem; color: #2d2d2d; letter-spacing: 1px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        th, td { padding: 14px 12px; text-align: left; }
        th { background: #f0f2fa; color: #5a3ec8; font-weight: 700; border-radius: 8px 8px 0 0; }
        tr { background: #f9fafd; border-radius: 8px; box-shadow: 0 1px 4px rgba(90,62,200,0.04); }
        tr:nth-child(even) { background: #f3f5fa; }
        td { color: #333; font-size: 1rem; border-bottom: 1px solid #eaeaea; }
        .filter-bar { display: flex; align-items: center; gap: 18px; margin-bottom: 18px; background: #f7f9fb; border-radius: 12px; padding: 18px 18px; }
        .filter-bar input[type="date"] { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 1rem; }
        .status-btns { display: flex; gap: 10px; }
        .status-btn { border: none; border-radius: 6px; padding: 8px 18px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: background 0.18s, color 0.18s; }
        .status-btn.all { background: #6c7a89; color: #fff; }
        .status-btn.pending { background: #ffc107; color: #fff; }
        .status-btn.approved { background: #2ecc40; color: #fff; }
        .status-btn.rejected { background: #e74c3c; color: #fff; }
        .status-btn.active, .status-btn:hover { filter: brightness(0.92); box-shadow: 0 2px 8px rgba(44,62,80,0.08); }
        .status-pill { display: inline-block; padding: 4px 14px; border-radius: 12px; font-size: 0.95rem; font-weight: 600; }
        .status-pill.pending { background: #ffe082; color: #b26a00; }
        .status-pill.approved { background: #b9f6ca; color: #00695c; }
        .status-pill.rejected { background: #ff8a80; color: #b71c1c; }
        .w3-button.w3-xlarge { padding: 8px 16px; }
        .w3-button.w3-xlarge:hover { background-color: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
        <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-arrow-left"></i></button>
        <div class="profile w3-center w3-margin w3-padding">
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('profile').style.display='block'" class="w3-bar-item w3-button"><i class="fa-regular fa-user w3-padding"></i><span>Profile</span></a>
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
            <h1 style="margin-left: 10px; color: #ffff;">Reservation Logs</h1>
        </div>
        <div class="container">
            <a href="reservation_management.php" class="w3-button w3-light-grey w3-round-large" style="margin-bottom: 18px; display: inline-flex; align-items: center; gap: 6px;"><i class="fa fa-arrow-left"></i> Back to Reservation</a>
            <div class="title-bar">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <h2>Reservation Logs</h2>
            </div>
            <form method="get" class="filter-bar">
                <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" onchange="this.form.submit()">
                <div class="status-btns">
                    <button type="submit" name="status" value="" class="status-btn all<?php if($status_filter=='') echo ' active'; ?>">All</button>
                    <button type="submit" name="status" value="pending" class="status-btn pending<?php if($status_filter=='pending') echo ' active'; ?>">Pending</button>
                    <button type="submit" name="status" value="approved" class="status-btn approved<?php if($status_filter=='approved') echo ' active'; ?>">Approved</button>
                    <button type="submit" name="status" value="rejected" class="status-btn rejected<?php if($status_filter=='rejected') echo ' active'; ?>">Rejected</button>
                </div>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Room</th>
                        <th>PC</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Rejection Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="9" style="text-align:center; color:#aaa;">No logs found for this date/status.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['idno']); ?></td>
                                <td><?php echo htmlspecialchars($log['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($log['room_number']); ?></td>
                                <td><?php echo htmlspecialchars($log['pc_number']); ?></td>
                                <td><?php echo date('m/d/Y', strtotime($log['reservation_date'])); ?></td>
                                <td><?php echo htmlspecialchars($log['time_slot']); ?></td>
                                <td><?php echo htmlspecialchars($log['purpose']); ?></td>
                                <td>
                                    <?php
                                        $status = ucfirst($log['status']);
                                        $color = $status === 'Pending' ? '#ffb300' : ($status === 'Approved' ? '#43a047' : ($status === 'Rejected' ? '#e53935' : '#757575'));
                                    ?>
                                    <span style="color:<?php echo $color; ?>; font-weight:600;"> <?php echo $status; ?> </span>
                                </td>
                                <td><?php echo $log['status'] === 'rejected' ? htmlspecialchars($log['rejection_reason']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Add pagination controls -->
            <div class="pagination" style="margin-top: 20px; text-align: center;">
                <?php if ($total_pages > 1): ?>
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo $date_filter ? '&date=' . urlencode($date_filter) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" class="w3-button w3-light-grey">&laquo; First</a>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $date_filter ? '&date=' . urlencode($date_filter) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" class="w3-button w3-light-grey">&lsaquo; Previous</a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo $date_filter ? '&date=' . urlencode($date_filter) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="w3-button <?php echo $i === $page ? 'w3-blue' : 'w3-light-grey'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $date_filter ? '&date=' . urlencode($date_filter) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" class="w3-button w3-light-grey">Next &rsaquo;</a>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $date_filter ? '&date=' . urlencode($date_filter) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" class="w3-button w3-light-grey">Last &raquo;</a>
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
</body>
</html> 