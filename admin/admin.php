<?php
define('INCLUDED_IN_MAIN_FILE', true); // Define a constant to check if the file is included
include '../includes/connect.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

include_once '../notifications/notification_functions.php';

// Get admin's notifications
$sql_notifications = "SELECT * FROM notifications 
                     WHERE recipient_type = 'admin' 
                     AND (type = 'reservation_request' 
                          OR type = 'reservation_approved' 
                          OR type = 'reservation_rejected' 
                          OR type = ''
                          OR type IS NULL)
                     ORDER BY is_read ASC, created_at DESC LIMIT 10";

// Debug logging
error_log("Admin notifications query: " . $sql_notifications);

$result_notifications = $conn->query($sql_notifications);
$notifications = [];
if($result_notifications && $result_notifications->num_rows > 0){
    while ($row = $result_notifications->fetch_assoc()){
        // Debug logging for each notification
        error_log("Notification found - ID: " . $row['id'] . ", Type: " . $row['type'] . ", Message: " . $row['message']);
        $notifications[] = $row;
    }
} else {
    error_log("No notifications found or query error: " . $conn->error);
}

// Get unread notification count
$sql_unread = "SELECT COUNT(*) as unread FROM notifications 
               WHERE recipient_type = 'admin' 
               AND (type = 'reservation_request' 
                    OR type = 'reservation_approved' 
                    OR type = 'reservation_rejected' 
                    OR type = ''
                    OR type IS NULL)
               AND is_read = 0";
$result_unread = $conn->query($sql_unread);
$unread_count = $result_unread->fetch_assoc()['unread'];

// Debug: Check all notifications in database
if (isset($_GET['debug_notif'])) {
    echo '<div style="background: #f5f5f5; padding: 10px; margin: 10px; border: 1px solid #ddd;">';
    echo '<h3>Debug Information:</h3>';
    
    // Check all notifications
    $sql_debug = "SELECT * FROM notifications ORDER BY created_at DESC";
    $result_debug = $conn->query($sql_debug);
    echo '<h4>All Notifications in Database:</h4>';
    echo '<pre>';
    if ($result_debug && $result_debug->num_rows > 0) {
        while ($row = $result_debug->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "No notifications found in database.";
    }
    echo '</pre>';
    
    // Check admin notifications specifically
    echo '<h4>Admin Notifications Query:</h4>';
    echo '<pre>';
    echo "SQL: " . $sql_notifications . "\n";
    echo "Result rows: " . ($result_notifications ? $result_notifications->num_rows : 0) . "\n";
    if ($result_notifications && $result_notifications->num_rows > 0) {
        print_r($notifications);
    } else {
        echo "No admin notifications found.";
    }
    echo '</pre>';

    // Check notification types distribution
    echo '<h4>Notification Types Distribution:</h4>';
    $sql_types = "SELECT type, COUNT(*) as count FROM notifications GROUP BY type";
    $result_types = $conn->query($sql_types);
    echo '<pre>';
    if ($result_types && $result_types->num_rows > 0) {
        while ($row = $result_types->fetch_assoc()) {
            echo "Type: " . ($row['type'] ?: 'empty') . " - Count: " . $row['count'] . "\n";
        }
    } else {
        echo "No notification types found.";
    }
    echo '</pre>';
    echo '</div>';
}

// Fetch statistics
$sql_total_students = "SELECT COUNT(*) as total_registered FROM user";
$result_total_students = $conn->query($sql_total_students);
$total_registered = $result_total_students->fetch_assoc()['total_registered'];

$sql_current_sitins = "SELECT COUNT(*) as current_sitins FROM sitin_records WHERE TIME_OUT IS NULL";
$result_current_sitins = $conn->query($sql_current_sitins);
$current_sitins = $result_current_sitins->fetch_assoc()['current_sitins'];

$sql_total_sitins = "SELECT COUNT(*) as total_sitins FROM sitin_records";
$result_total_sitins = $conn->query($sql_total_sitins);
$total_sitins = $result_total_sitins->fetch_assoc()['total_sitins'];

// Fetch sit-in purposes count
$sql_purpose_counts = "SELECT PURPOSE, COUNT(*) as count FROM sitin_records GROUP BY PURPOSE";
$result_purpose_counts = $conn->query($sql_purpose_counts);
$purpose_counts = [];
if ($result_purpose_counts->num_rows > 0) {
    while ($row = $result_purpose_counts->fetch_assoc()) {
        $purpose_counts[$row['PURPOSE']] = $row['count'];
    }
}
// Ensure all purposes are present, even if zero
$all_purposes = [
    "C Programming",
    "Java Programming",
    "C#",
    "PHP",
    "ASP.Net",
    "Database",
    "Digital Logic & Design",
    "Embedded System % IOT",
    "Python Programming",
    "Systems Integration & Architecture",
    "Computer Application",
    "Web Design & Development",
    "Project Management",
    "Other"
];
foreach ($all_purposes as $purpose) {
    if (!isset($purpose_counts[$purpose])) {
        $purpose_counts[$purpose] = 0;
    }
}
// Keep the order
$purpose_counts = array_replace(array_flip($all_purposes), $purpose_counts);
$chart_labels = json_encode(array_keys($purpose_counts));
$chart_data = json_encode(array_values($purpose_counts));


//get the profile picture from database
$username = $_SESSION['user']['USERNAME']; // Assuming you store username in session
$sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param("s", $username);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$user = $result_profile->fetch_assoc();


// Handle announcement deletion - place this BEFORE the posting handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_announcement'])) {
    $announcement_id = $_POST['delete_announcement'];
    $sql = "DELETE FROM announcement WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $announcement_id);
    if (!$stmt->execute()) {
        echo "Error deleting announcement: " . $stmt->error;
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle announcement update (display edit form)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['edit_announcement'])) {
    $announcement_id = $_GET['edit_announcement'];
    $sql = "SELECT * FROM announcement WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $announcement_to_edit = $result->fetch_assoc();
    $stmt->close();

    if (!$announcement_to_edit) {
        echo "Announcement not found.";
        exit;
    }
}

// Handle announcement update (submit changes) - place this SECOND
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_announcement_id']) && isset($_POST['updated_announcement_message'])) {
    $announcement_id = $_POST['update_announcement_id'];
    $updated_title = isset($_POST['updated_announcement_title']) ? mysqli_real_escape_string($conn, $_POST['updated_announcement_title']) : '';
    $updated_message = mysqli_real_escape_string($conn, $_POST['updated_announcement_message']);

    $sql = "UPDATE announcement SET title = ?, message = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $updated_title, $updated_message, $announcement_id);
    if (!$stmt->execute()) {
        echo "Error updating announcement: " . $stmt->error;
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle announcement posting - place this LAST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement_message'])) {
    // Make sure we're not handling delete or update requests
    if (!isset($_POST['delete_announcement']) && !isset($_POST['update_announcement_id'])) {
        $message = mysqli_real_escape_string($conn, $_POST['announcement_message']);
        $title = isset($_POST['announcement_title']) ? mysqli_real_escape_string($conn, $_POST['announcement_title']) : '';
        
        if (!empty($message)) {
            $sql = "INSERT INTO announcement (title, message) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $title, $message);
            if (!$stmt->execute()) {
                echo "Error: " . $stmt->error; // Consider better error handling
            }
            $stmt->close();
        }
        header("Location: admin.php");
        exit;
    }
}


// Fetch announcements
$sql_announcements = "SELECT * FROM announcement ORDER BY timestamp DESC"; // Get most recent first
$result_announcements = $conn->query($sql_announcements);
$announcements = [];
if ($result_announcements->num_rows > 0) {
    while ($row = $result_announcements->fetch_assoc()) {
        $announcements[] = $row;
    }
}
// Fetch current sit-in records (where time_out is NULL)
$sql_sitins = "SELECT sr.id, u.IDNO, u.FIRSTNAME, u.LASTNAME, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN
               FROM sitin_records sr
               JOIN user u ON sr.IDNO = u.IDNO
               WHERE sr.TIME_OUT IS NULL";
$result_sitins = $conn->query($sql_sitins);

$sitin_records = [];
if ($result_sitins->num_rows > 0) {
    while ($row = $result_sitins->fetch_assoc()) {
        $sitin_records[] = $row;
    }
}

// Fetch top 5 users by points for leaderboard
$sql_leaderboard = "SELECT u.IDNO, u.FIRSTNAME, u.LASTNAME, u.POINTS, 
                    (SELECT COUNT(*) FROM sitin_records sr WHERE sr.IDNO = u.IDNO) as sitin_count 
                    FROM user u 
                    ORDER BY u.POINTS DESC, sitin_count DESC, u.FIRSTNAME ASC LIMIT 5";
$result_leaderboard = $conn->query($sql_leaderboard);
$leaderboard = [];
if ($result_leaderboard->num_rows > 0) {
    while ($row = $result_leaderboard->fetch_assoc()) {
        $leaderboard[] = $row;
    }
}

include 'search_modal.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/w3.css">
    <link rel="stylesheet" href="../css/side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Home</title>
    <style>
        .announcement-actions {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }
        .statistics-card {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .stat-item {
            background-color: #E4EFE7;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            flex-grow: 1;
            margin: 0 5px;
        }
        .leaderboard-icon { font-size: 1.2em; margin-right: 5px; }
        
        /* Notification Styles */
        .notif-bell-btn {
            background: #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
            border: none;
            position: relative;
            transition: box-shadow 0.2s;
        }
        .notif-bell-btn:hover {
            box-shadow: 0 4px 16px rgba(123,31,162,0.16);
            background: #f3e6ff;
        }
        .notif-bell-btn i.fa-bell {
            color: #7b1fa2 !important;
            font-size: 1.7em;
        }
        .notif-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e53935;
            color: #fff;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.85em;
            font-weight: bold;
            z-index: 2;
        }
        .notif-dropdown {
            position: absolute;
            right: 0;
            top: 38px;
            width: 340px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18), 0 1.5px 6px rgba(123,31,162,0.08);
            z-index: 1001;
            padding: 0 0 8px 0;
            min-width: 260px;
            border: 1px solid #ece6f6;
            font-family: 'Segoe UI', Arial, sans-serif;
            animation: notifFadeIn 0.18s;
        }
        @keyframes notifFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notif-header {
            padding: 16px 20px 8px 20px;
            font-size: 1.13em;
            color: #222;
            background: linear-gradient(90deg, #f7f3fa 0%, #ece6f6 100%);
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .notif-header a {
            color: #a259c6;
            font-size: 0.97em;
            text-decoration: none;
            transition: color 0.18s;
        }
        .notif-header a:hover {
            color: #7b1fa2;
            text-decoration: underline;
        }
        .notif-list {
            max-height: 260px;
            overflow-y: auto;
        }
        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 20px 12px 20px;
            font-size: 1.01em;
            border-bottom: 1px solid #f0f0f0;
            background: #fff;
            transition: background 0.18s;
            cursor: pointer;
            border-left: 4px solid transparent;
            text-decoration: none;
            color: inherit;
        }
        .notif-item:last-child {
            border-bottom: none;
        }
        .notif-item:hover {
            background: #f7f3fa;
            text-decoration: none;
            color: inherit;
        }
        .notif-unread {
            background: linear-gradient(90deg, #f7f3fa 60%, #fff 100%);
            font-weight: 500;
            border-left: 4px solid #b47ddb;
        }
        .notif-icon {
            margin-top: 2px;
            font-size: 1.25em;
            flex-shrink: 0;
        }
        .notif-msg {
            flex: 1;
            color: #3d2956;
            line-height: 1.4;
        }
        .notif-date {
            font-size: 0.87em;
            color: #a59bb0;
            margin-left: 8px;
            white-space: nowrap;
            align-self: flex-end;
        }
        .notif-footer {
            padding: 10px 0 0 0;
            background: none;
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }
        .notif-footer a {
            color: #a259c6;
            text-align: center;
            display: block;
            font-weight: 500;
            padding: 8px 0 6px 0;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.18s, color 0.18s;
        }
        .notif-footer a:hover {
            color: #7b1fa2;
            text-decoration: underline;
        }
        /* Custom close button for All Notifications modal */
        #allNotifications .w3-button.w3-display-topright {
            display: none;
        }
        .allnotif-header {
            display: flex !important;
            align-items: center;
            justify-content: flex-start;
            position: relative;
            padding: 22px 32px 22px 32px !important;
            background: linear-gradient(90deg, #7b1fa2 0%, #b47ddb 100%);
            box-shadow: 0 2px 8px rgba(123,31,162,0.08);
        }
        .allnotif-title {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #fff;
            font-size: 2em;
            font-weight: bold;
            margin: 0;
            z-index: 2;
        }
        .allnotif-title i {
            font-size: 1.3em;
        }
        .allnotif-close {
            position: absolute;
            right: 32px;
            top: 50%;
            transform: translateY(-50%);
            background: #fff;
            color: #7b1fa2;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 1.7em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(123,31,162,0.08);
            cursor: pointer;
            z-index: 3;
        }
        .allnotif-close:hover {
            background: #b47ddb;
            color: #fff;
            box-shadow: 0 4px 16px rgba(123,31,162,0.16);
        }
    </style>
</head>

<body>
    <div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
        <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-arrow-left"></i></button>
        <div class="profile w3-center w3-margin w3-padding">
            <?php
            $profile_pic = isset($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : '../images/default_pic.png';
            ?>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <a href="admin.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="lab_schedule.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedule</span></a>
        <a href="lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="reservation_management.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="../logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>
    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">☰</button>
            <h1 style="margin-left: 10px; color: #ffff;">Admin Dashboard</h1>
            <div class="notification-bell-container" style="position: relative; margin-left: auto; margin-right: 30px;">
                <button id="notifBell" class="notif-bell-btn">
                    <i class="fa fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </button>
                <div id="notifDropdown" class="notif-dropdown" style="display: none;">
                    <div class="notif-header">
                        <span style="font-weight: bold;">Notifications</span>
                        <a href="../notifications/mark_all_notifications.php" style="float: right; color: #c0392b; font-size: 0.95em;">Mark all as read</a>
                    </div>
                    <hr style="margin: 6px 0;">
                    <div class="notif-list">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <?php
                                    $redirect_url = '';
                                    $type = $notification['type'];
                                    
                                    // If type is empty, determine it from the message
                                    if (empty($type)) {
                                        if (strpos($notification['message'], 'has been submitted and is pending approval') !== false) {
                                            $type = 'reservation_request';
                                        } elseif (strpos($notification['message'], 'approved') !== false) {
                                            $type = 'reservation_approved';
                                        } elseif (strpos($notification['message'], 'rejected') !== false) {
                                            $type = 'reservation_rejected';
                                        }
                                    }
                                    
                                    // Set redirect URL based on type
                                    if ($type === 'reservation_request' || empty($type)) {
                                        $redirect_url = 'reservation_management.php?filter=Pending';
                                    } elseif ($type === 'reservation_approved') {
                                        $redirect_url = 'reservation_management.php?filter=Approved';
                                    } elseif ($type === 'reservation_rejected') {
                                        $redirect_url = 'reservation_management.php?filter=Rejected';
                                    }
                                    
                                    // Debug logging
                                    error_log("Processing notification - ID: " . $notification['id'] . 
                                             ", Type: " . $type . 
                                             ", Message: " . $notification['message'] . 
                                             ", Redirect: " . $redirect_url);
                                ?>
                                <a href="javascript:void(0)" onclick="handleNotificationClick(<?php echo $notification['id']; ?>, '<?php echo $redirect_url; ?>')" class="notif-item<?php echo !$notification['is_read'] ? ' notif-unread' : ''; ?>">
                                    <?php
                                        $icon = '';
                                        switch($type) {
                                            case 'reservation_request':
                                                $icon = '<i class="fa fa-calendar-check" style="color: #2196F3;"></i>';
                                                break;
                                            case 'reservation_approved':
                                                $icon = '<i class="fa fa-check-circle" style="color: #4CAF50;"></i>';
                                                break;
                                            case 'reservation_rejected':
                                                $icon = '<i class="fa fa-times-circle" style="color: #f44336;"></i>';
                                                break;
                                            default:
                                                // Default icon for empty type
                                                if (strpos($notification['message'], 'has been submitted and is pending approval') !== false) {
                                                    $icon = '<i class="fa fa-calendar-check" style="color: #2196F3;"></i>';
                                                } elseif (strpos($notification['message'], 'approved') !== false) {
                                                    $icon = '<i class="fa fa-check-circle" style="color: #4CAF50;"></i>';
                                                } elseif (strpos($notification['message'], 'rejected') !== false) {
                                                    $icon = '<i class="fa fa-times-circle" style="color: #f44336;"></i>';
                                                } else {
                                                    $icon = '<i class="fa fa-bell" style="color: #9C27B0;"></i>';
                                                }
                                        }
                                    ?>
                                    <span class="notif-icon"><?php echo $icon; ?></span>
                                    <span class="notif-msg"><?php echo htmlspecialchars($notification['message']); ?></span>
                                    <div class="notif-date"><?php echo date('M d, H:i', strtotime($notification['created_at'])); ?></div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="notif-item">No notifications.</div>
                        <?php endif; ?>
                    </div>
                    <div class="notif-footer">
                        <a href="#" onclick="document.getElementById('allNotifications').style.display='block'" style="color: #c0392b; text-align: center; display: block;">View all notifications</a>
                    </div>
                </div>
            </div>
        </div>
        <?php if (isset($_SESSION['success'])): ?>
            <div id="flash-message-success" class="w3-panel w3-green w3-round-xlarge" style="margin: 10px 0;">
                <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div id="flash-message-error" class="w3-panel w3-red w3-round-xlarge" style="margin: 10px 0;">
                <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            </div>
        <?php endif; ?>
        <div class="w3-row-padding" style="margin: 5% 10px;">
            <div class="w3-col m6">
                <!---Leaderboard---->
                <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 100%; margin-bottom: 20px;">
                    <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-purple">
                        <h3><i class="fa-solid fa-trophy w3-padding"></i>Leaderboard</h3>
                    </div>
                    <table class="w3-table w3-bordered w3-striped w3-margin-top">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>ID No</th>
                                <th>Name</th>
                                <th>Points</th>
                                <th>Sit-ins</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($leaderboard) > 0): ?>
                                <?php foreach ($leaderboard as $index => $user): ?>
                                    <?php
                                        $row_class = '';
                                        $icon = '<i class="fa-solid fa-user leaderboard-icon"></i>';
                                        if ($index === 0) {
                                            $icon = '<i class="fa-solid fa-trophy leaderboard-icon" style="color:gold;"></i>';
                                        } elseif ($index === 1) {
                                            $icon = '<i class="fa-solid fa-medal leaderboard-icon" style="color:silver;"></i>';
                                        } elseif ($index === 2) {
                                            $icon = '<i class="fa-solid fa-medal leaderboard-icon" style="color:#cd7f32;"></i>';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($user['IDNO']); ?></td>
                                        <td><?php echo $icon . htmlspecialchars($user['FIRSTNAME'] . ' ' . $user['LASTNAME']); ?></td>
                                        <td><?php echo htmlspecialchars($user['POINTS']); ?></td>
                                        <td><?php echo htmlspecialchars($user['sitin_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No data available.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!---Announcement---->
                <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-margin-bottom w3-animate-top" style="width: 100%;">
                    <div class="w3-purple w3-container w3-round-xlarge" style="display: flex; align-items: center;">
                        <i class="fa-solid fa-bullhorn"></i>
                        <h3 style="margin-left: 10px; color: #ffff;">Announcement</h3>
                    </div>
                    <?php if (isset($announcement_to_edit)) : ?>
                        <form method="POST" class="w3-margin-top">
                            <input type="hidden" name="update_announcement_id" value="<?php echo htmlspecialchars($announcement_to_edit['ID']); ?>">
                            <input type="text" name="updated_announcement_title" class="w3-input w3-border w3-margin-bottom" placeholder="Announcement Title" value="<?php echo htmlspecialchars($announcement_to_edit['TITLE'] ?? ''); ?>" required>
                            <textarea name="updated_announcement_message" class="w3-input w3-border" placeholder="Edit your announcement here..." rows="4"><?php echo htmlspecialchars($announcement_to_edit['MESSAGE']); ?></textarea>
                            <button type="submit" class="w3-button w3-purple w3-margin-top">Update Announcement</button>
                            <a href="admin.php" class="w3-button w3-red w3-margin-top">Cancel</a>
                        </form>
                    <?php else : ?>
                        <form method="POST" class="w3-margin-top">
                            <input type="text" name="announcement_title" class="w3-input w3-border w3-margin-bottom" placeholder="Announcement Title" required>
                            <textarea name="announcement_message" class="w3-input w3-border" placeholder="Type your announcement here..." rows="4" required></textarea>
                            <button type="submit" class="w3-button w3-purple w3-margin-top">Post Announcement</button>
                        </form>
                    <?php endif; ?>
                    <div id="announcements-list" class="w3-margin-top">
                        <?php if (count($announcements) > 0) : ?>
                            <?php foreach ($announcements as $announcement) : ?>
                                <div class="w3-panel w3-light-gray w3-leftbar w3-border-purple">
                                    <h4 class="w3-text-purple"><?php echo htmlspecialchars($announcement['TITLE'] ?? 'Announcement'); ?></h4>
                                    <p><?php echo htmlspecialchars($announcement['MESSAGE']); ?></p>
                                    <small>Posted on: <?php echo date("Y-m-d H:i:s", strtotime($announcement['TIMESTAMP'])); ?></small>
                                    <div class="announcement-actions">
                                        <form method="GET" action="admin.php" style="display: inline-block; margin-right: 5px;">
                                            <input type="hidden" name="edit_announcement" value="<?php echo $announcement['ID']; ?>">
                                            <button type="submit" class="w3-button w3-blue w3-small">Update</button>
                                        </form>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="delete_announcement" value="<?php echo $announcement['ID']; ?>">
                                            <button type="submit" class="w3-button w3-red w3-small">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p style="font-size: 18px; color: #333; font-family: Arial, sans-serif; margin-top: 20px;">No announcement for today.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="w3-col m6">
                <!---Statistics---->
                <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 100%; height: 700px;">
                    <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-purple">
                        <h3><i class="fa-solid fa-chart-simple w3-padding"></i>Statistics</h3>
                    </div>
                    <!-- New Statistics Cards -->
                    <div class="statistics-card" style="margin-top: 50px;">
                        <div class="stat-item">
                            <h4>Registered Students</h4>
                            <p class="w3-large w3-text-purple"><?php echo $total_registered; ?></p>
                        </div>
                        <div class="stat-item">
                            <h4>Current Sit-ins</h4>
                            <p class="w3-large w3-text-purple"><?php echo $current_sitins; ?></p>
                        </div>
                        <div class="stat-item">
                            <h4>Total Sit-ins</h4>
                            <p class="w3-large w3-text-purple"><?php echo $total_sitins; ?></p>
                        </div>
                    </div>
                    <div class="w3-container" style="width: 100%; height: 400px;">
                        <canvas id="purposeChart"></canvas>
                    </div>
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

            //pie chart for purposes
            var ctx = document.getElementById('purposeChart').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?php echo $chart_labels; ?>,
                    datasets: [{
                        data: <?php echo $chart_data; ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',   // 1
                            'rgba(54, 162, 235, 0.8)',   // 2
                            'rgba(255, 206, 86, 0.8)',   // 3
                            'rgba(75, 192, 192, 0.8)',   // 4
                            'rgba(153, 102, 255, 0.8)',  // 5
                            'rgba(255, 159, 64, 0.8)',   // 6
                            'rgba(255, 205, 210, 0.8)',  // 7
                            'rgba(100, 181, 246, 0.8)',  // 8
                            'rgba(174, 213, 129, 0.8)',  // 9
                            'rgba(255, 245, 157, 0.8)',  // 10
                            'rgba(129, 212, 250, 0.8)',  // 11
                            'rgba(244, 143, 177, 0.8)',  // 12
                            'rgba(255, 224, 178, 0.8)',  // 13
                            'rgba(197, 202, 233, 0.8)'   // 14
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(255, 205, 210, 1)',
                            'rgba(100, 181, 246, 1)',
                            'rgba(174, 213, 129, 1)',
                            'rgba(255, 245, 157, 1)',
                            'rgba(129, 212, 250, 1)',
                            'rgba(244, 143, 177, 1)',
                            'rgba(255, 224, 178, 1)',
                            'rgba(197, 202, 233, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Sit-in Purposes Distribution'
                        }
                    }
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                var bell = document.getElementById('notifBell');
                var dropdown = document.getElementById('notifDropdown');
                bell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                });
                document.addEventListener('click', function() {
                    dropdown.style.display = 'none';
                });
            });

            function handleNotificationClick(notificationId, redirectUrl) {
                // Mark notification as read using AJAX
                fetch('../notifications/mark_notification_read.php?notification_id=' + notificationId, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove unread styling
                        const notification = document.querySelector(`[onclick*="${notificationId}"]`);
                        if (notification) {
                            notification.classList.remove('notif-unread');
                        }
                        
                        // Update unread count
                        const badge = document.querySelector('.notif-badge');
                        if (badge) {
                            const currentCount = parseInt(badge.textContent);
                            if (currentCount > 1) {
                                badge.textContent = currentCount - 1;
                            } else {
                                badge.style.display = 'none';
                            }
                        }

                        // Redirect if there's a URL
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                        }
                    } else {
                        console.error('Failed to mark notification as read:', data.message);
                        // If there's an error but we have a redirect URL, still try to redirect
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // If there's an error but we have a redirect URL, still try to redirect
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                });
            }

            window.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    var successMsg = document.getElementById('flash-message-success');
                    if (successMsg) {
                        successMsg.style.display = 'none';
                    }
                    var errorMsg = document.getElementById('flash-message-error');
                    if (errorMsg) {
                        errorMsg.style.display = 'none';
                    }
                }, 1500); // Changed to 1.5 seconds
            });
        </script>
    </div>

    <!-- All Notifications Modal -->
    <div id="allNotifications" class="w3-modal" style="z-index: 1000;">
        <div class="w3-modal-content w3-animate-zoom w3-round-xlarge" style="width: 50%;">
            <header class="w3-container w3-purple w3-round-xlarge allnotif-header">
                <div class="allnotif-title">
                    <i class="fa fa-bell"></i>
                    <span>All Notifications</span>
                </div>
                <button type="button" class="allnotif-close" onclick="document.getElementById('allNotifications').style.display='none'">&times;</button>
            </header>
            <div class="w3-container w3-padding">
                <hr class="divider">
                <div class="notif-list" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    // Get all notifications for the admin
                    $sql_all_notifications = "SELECT * FROM notifications 
                                           WHERE recipient_type = 'admin' 
                                           AND (type = 'reservation_request' OR type = 'reservation_approved' OR type = 'reservation_rejected' OR type = '')
                                           ORDER BY created_at DESC";
                    $result_all_notifications = $conn->query($sql_all_notifications);
                    $all_notifications = [];
                    if($result_all_notifications->num_rows > 0){
                        while ($row = $result_all_notifications->fetch_assoc()){
                            $all_notifications[] = $row;
                        }
                    }
                    ?>
                    <?php if (count($all_notifications) > 0): ?>
                        <?php foreach ($all_notifications as $notification): ?>
                            <div class="notif-item<?php echo !$notification['is_read'] ? ' notif-unread' : ''; ?>" style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: flex-start; gap: 12px; flex: 1;">
                                    <?php
                                        $icon = '';
                                        switch($notification['type']) {
                                            case 'reservation_request':
                                                $icon = '<i class="fa fa-calendar-check" style="color: #2196F3;"></i>';
                                                break;
                                            case 'reservation_approved':
                                                $icon = '<i class="fa fa-check-circle" style="color: #4CAF50;"></i>';
                                                break;
                                            case 'reservation_rejected':
                                                $icon = '<i class="fa fa-times-circle" style="color: #f44336;"></i>';
                                                break;
                                        }
                                    ?>
                                    <span class="notif-icon"><?php echo $icon; ?></span>
                                    <div style="flex: 1;">
                                        <span class="notif-msg"><?php echo htmlspecialchars($notification['message']); ?></span>
                                        <div class="notif-date"><?php echo date('M d, H:i', strtotime($notification['created_at'])); ?></div>
                                    </div>
                                </div>
                                <form method="POST" action="../notifications/delete_notification.php" style="margin-left: 10px;">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="w3-button w3-red w3-round" style="padding: 4px 8px;">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif-item">No notifications.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
