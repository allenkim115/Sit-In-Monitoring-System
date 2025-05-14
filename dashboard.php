<?php
include 'connect.php';
include_once 'notification_functions.php';

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Retrieve user data from the session
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

$sql_announcements = "SELECT * FROM announcement ORDER BY timestamp DESC"; // Get most recent first
$result_announcements = $conn->query($sql_announcements);
$announcements = [];
if($result_announcements->num_rows > 0){
    while ($row = $result_announcements->fetch_assoc()){
        $announcements[] = $row;
    }
}

//get the session count from database
$sql_session = "SELECT SESSION_COUNT FROM user WHERE IDNO = ?";
$stmt_session = $conn->prepare($sql_session);
$stmt_session->bind_param("s", $user['IDNO']);
$stmt_session->execute();
$result_session = $stmt_session->get_result()->fetch_assoc();

// Get top 5 users by points
$sql_leaderboard = "SELECT IDNO, FIRSTNAME, LASTNAME, POINTS FROM user ORDER BY POINTS DESC, FIRSTNAME ASC LIMIT 5";
$result_leaderboard = $conn->query($sql_leaderboard);
$leaderboard = [];
if($result_leaderboard->num_rows > 0){
    while ($row = $result_leaderboard->fetch_assoc()){
        $leaderboard[] = $row;
    }
}

// Get current user's rank
$sql_user_rank = "SELECT COUNT(*) as rank FROM user WHERE POINTS > (SELECT POINTS FROM user WHERE IDNO = ?)";
$stmt_user_rank = $conn->prepare($sql_user_rank);
$stmt_user_rank->bind_param("s", $user['IDNO']);
$stmt_user_rank->execute();
$result_user_rank = $stmt_user_rank->get_result()->fetch_assoc();
$user_rank = $result_user_rank['rank'] + 1;

// Get current user's points
$sql_user_points = "SELECT POINTS FROM user WHERE IDNO = ?";
$stmt_user_points = $conn->prepare($sql_user_points);
$stmt_user_points->bind_param("s", $user['IDNO']);
$stmt_user_points->execute();
$result_user_points = $stmt_user_points->get_result()->fetch_assoc();
$user_points = $result_user_points['POINTS'];

// Get user's notifications
$sql_notifications = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param("s", $user['IDNO']);
$stmt_notifications->execute();
$result_notifications = $stmt_notifications->get_result();
$notifications = [];
if($result_notifications->num_rows > 0){
    while ($row = $result_notifications->fetch_assoc()){
        $notifications[] = $row;
    }
}

$unread_count = getUnreadNotificationCount($user['IDNO']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <title>Dashboard</title>
    <style>
      img{  
        border: 2px solid rgba(100,25,117,1);
        border-radius: 50%;
        }
        ul li{
          list-style-type: none;
          padding: 5px;
        }
        /* Modal overlay */
        #pointsConversion.w3-modal {
            background: rgba(40, 0, 60, 0.25);
        }
        /* Modal content */
        #pointsConversion .w3-modal-content {
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border-radius: 24px;
            border: 1px solid #b47ddb;
            background: #fff;
            padding-bottom: 20px;
        }
        /* Gradient header */
        #pointsConversion .w3-container.w3-purple {
            background: linear-gradient(90deg, #7b1fa2 0%, #b47ddb 100%);
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            box-shadow: 0 2px 8px rgba(123,31,162,0.08);
        }
        /* Form elements */
        #pointsConversion input[type='number'] {
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        #pointsConversion button[type='submit'] {
            background: linear-gradient(90deg, #7b1fa2 0%, #b47ddb 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(123,31,162,0.08);
        }
        #pointsConversion button[type='submit']:hover {
            background: linear-gradient(90deg, #b47ddb 0%, #7b1fa2 100%);
            box-shadow: 0 4px 16px rgba(123,31,162,0.16);
        }
        #pointsConversion .w3-panel {
            border-radius: 12px;
            margin-bottom: 18px;
        }
        #pointsConversion .divider {
            border: none;
            border-top: 2px solid #b47ddb;
            margin: 18px 0 18px 0;
        }
        #pointsConversion .w3-button.w3-display-topright {
            border-top-right-radius: 24px;
            border-bottom-left-radius: 12px;
            background: transparent;
            padding: 8px 18px 8px 18px;
            transition: background 0.2s, color 0.2s;
        }
        #pointsConversion .w3-button.w3-display-topright:hover {
            background: rgba(255,255,255,0.15);
            color: #fff;
            cursor: pointer;
        }
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
        $profile_pic = isset($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : 'images/default_pic.png';
        ?>
        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px;">
    </div>
    <a href="dashboard.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
    <a href="#" onclick="document.getElementById('profile').style.display='block'" class="w3-bar-item w3-button"><i class="fa-regular fa-user w3-padding"></i><span>Profile</span></a>
    <a href="profile.php" class="w3-bar-item w3-button"><i class="fa-solid fa-edit w3-padding"></i><span>Edit Profile</span></a>
    <a href="history.php" class="w3-bar-item w3-button"><i class="fa-solid fa-clock-rotate-left w3-padding"></i><span>History</span></a>
    <a href="view_lab_schedules.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedules</span></a>
    <a href="view_lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
    <a href="make_reservation.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
    <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
</div>
<div id="profile" class="w3-modal" style="z-index: 1000;">
    <div class="w3-modal-content w3-animate-zoom w3-round-xlarge" style="width: 30%;">
        <header class="w3-container"> 
            <span onclick="document.getElementById('profile').style.display='none'" 
                  class="w3-button w3-display-topright">&times;</span>
            <h2 style="text-transform:uppercase;">Profile</h2>
        </header>
        <div class="display_photo w3-container w3-center">
            <img src="<?php echo htmlspecialchars($user['PROFILE_PIC']); ?>" alt="profile_pic" style="width: 120px; height:120px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <hr style="margin: 1rem 10%; border-width: 2px;">
        <div class="w3-container" style="margin: 0 10%;">
            <p><i class="fa-solid fa-id-card"></i> <strong>IDNO:</strong> <?php echo htmlspecialchars($user['IDNO']); ?></p>
            <p><i class="fa-solid fa-user"></i> <strong>Name:</strong> <?php echo htmlspecialchars($user['FIRSTNAME'] . ' ' . $user['MIDDLENAME'] . ' ' . $user['LASTNAME']); ?></p>
            <p><i class="fa-solid fa-book"></i> <strong>Course:</strong> <?php echo htmlspecialchars($user['COURSE']); ?></p>
            <p><i class="fa-solid fa-graduation-cap"></i> <strong>Level:</strong> <?php echo htmlspecialchars($user['YEAR_LEVEL']); ?></p>
            <p><i class="fa-solid fa-stopwatch"></i> <strong>Session:</strong><?php echo htmlspecialchars($result_session['SESSION_COUNT']); ?></p>
        </div>
        <footer class="w3-container w3-padding" style="margin: 0 30%;">
            <button class="w3-btn w3-purple w3-round-xlarge" onclick="window.location.href='profile.php'">Edit Profile</button>
        </footer>
    </div>
</div>
<div style="margin-left:20%; z-index: 1; position: relative;">
    <div class="title_page w3-container" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">&#9776;</button>
        <h1 style="margin-left: 10px; color: #ffff;">Dashboard</h1>
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
                    <a href="mark_all_notifications.php" style="float: right; color: #c0392b; font-size: 0.95em;">Mark all as read</a>
                </div>
                <hr style="margin: 6px 0;">
                <div class="notif-list">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                                $redirect_url = 'mark_notification_read.php?notification_id=' . $notification['id'];
                                $type = $notification['type'];
                                
                                if ($type === 'reservation_approved' || $type === 'reservation_rejected') {
                                    $redirect_url .= '&redirect=' . urlencode('make_reservation.php?filter=' . ($type === 'reservation_approved' ? 'Approved' : 'Rejected'));
                                }
                            ?>
                            <a href="<?php echo $redirect_url; ?>" class="notif-item<?php echo !$notification['is_read'] ? ' notif-unread' : ''; ?>">
                                <?php
                                    $icon = '';
                                    switch($notification['type']) {
                                        case 'reservation_approved':
                                            $icon = '<i class="fa fa-check-circle" style="color: #4CAF50;"></i>';
                                            break;
                                        case 'reservation_rejected':
                                            $icon = '<i class="fa fa-times-circle" style="color: #f44336;"></i>';
                                            break;
                                        case 'reward_received':
                                            $icon = '<i class="fa fa-gift" style="color: #9C27B0;"></i>';
                                            break;
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
        <div id="flash-message-success" class="w3-panel w3-green w3-round-xlarge" style="margin: 10px;">
            <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div id="flash-message-error" class="w3-panel w3-red w3-round-xlarge" style="margin: 10px;">
            <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        </div>
    <?php endif; ?>
    <script>
    window.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            var successMsg = document.getElementById('flash-message-success');
            if (successMsg) successMsg.style.display = 'none';
            var errorMsg = document.getElementById('flash-message-error');
            if (errorMsg) errorMsg.style.display = 'none';
        }, 2000);
    });
    </script>
    <div class="w3-row-padding" style="margin: 5% 10px;">
        <div class="w3-col m6">
            <!-----Welcome Message----->
            <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="margin-bottom: 30px; width: 100%;">
                <div class="w3-center w3-margin w3-padding" style="margin-bottom:0; display: flex; align-items: center; justify-content: center;">
                    <img src="<?php echo htmlspecialchars($user['PROFILE_PIC']); ?>" alt="profile_pic" style="width: 130px; height:130px; margin-right: 20px;">
                    <div>
                        <h2 id="welcome-text">Welcome, <?php echo htmlspecialchars($user['FIRSTNAME']); ?></h2>
                        <p id="typing-text" style="font-size: 18px; color: #333; font-family: Arial, sans-serif; margin-top: 10px;">to CSS Sit-In Monitoring System</p>
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const welcomeText = "Welcome, <?php echo htmlspecialchars($user['FIRSTNAME']); ?>";
                        const typingText = "to CSS Sit-In Monitoring System";
                        let index = 0;
                        const typingSpeed = 60; // Adjust typing speed here

                        function typeWelcome() {
                            if (index < welcomeText.length) {
                                document.getElementById("welcome-text").innerHTML += welcomeText.charAt(index);
                                index++;
                                setTimeout(typeWelcome, typingSpeed);
                            } else {
                                index = 0;
                                typeTypingText();
                            }
                        }

                        function typeTypingText() {
                            if (index < typingText.length) {
                                document.getElementById("typing-text").innerHTML += typingText.charAt(index);
                                index++;
                                setTimeout(typeTypingText, typingSpeed);
                            }
                        }

                        document.getElementById("welcome-text").innerHTML = "";
                        document.getElementById("typing-text").innerHTML = "";
                        typeWelcome();
                    });
                </script>
            </div>
            <!---Rules and Regulations---->
            <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 100%; height: 450px;">
                <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-purple">
                    <h3><i class="fa-brands fa-readme w3-padding"></i>Rules and Regulation</h3>
                </div>
                <br>
                <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container" style="height:350px; overflow-y: auto;">
                    <div class="w3-center">
                        <h4>University of Cebu</h4>
                        <h4>COLLEGE OF INFORMATION & COMPUTER STUDIES</h4>
                    </div>
                    <br>
                    <h4>LABORATORY RULES AND REGULATIONS</h4>
                    <p>To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                    <ul>
                        <li>1. Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans, and other personal pieces of equipment must be switched off.</li>
                        <li>2. Games are not allowed inside the lab. This includes computer-related games, card games, and other games that may disturb the operation of the lab.</li>
                        <li>3. Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</li>
                        <li>4. Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</li>
                        <li>5. Deleting computer files and changing the set-up of the computer is a major offense.</li>
                        <li>6. Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".</li>
                        <li>7. Observe proper decorum while inside the laboratory.</li>
                        <ul>
                            <li>a. Do not get inside the lab unless the instructor is present.</li>
                            <li>b. All bags, knapsacks, and the likes must be deposited at the counter.</li>
                            <li>c. Follow the seating arrangement of your instructor.</li>
                            <li>d. At the end of class, all software programs must be closed.</li>
                            <li>e. Return all chairs to their proper places after using.</li>
                        </ul>
                        <li>8. Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.</li>
                        <li>9. Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the members of the community, including public display of physical intimacy, are not tolerated.</li>
                        <li>10. Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.</li>
                        <li>11. For serious offense, the lab personnel may call the Civil Security Office (CSU) for assistance.</li>
                        <li>12. Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant, or instructor immediately.</li>
                    </ul>
                    <h4>DISCIPLINARY ACTION</h4>
                    <ul>
                        <li>First Offense - The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.</li>
                        <li>Second and Subsequent Offenses - A recommendation for a heavier sanction will be endorsed to the Guidance Center.</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="w3-col m6"> 
            <!---Announcement---->
            <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-margin-bottom w3-animate-top" style="width: 100%;">
                <div class="w3-purple w3-container w3-round-xlarge" style="display: flex; align-items: center;">
                    <i class="fa-solid fa-bullhorn"></i>
                    <h3 style="margin-left: 10px; color: #ffff;">Announcement</h3>
                </div>
                <div id="announcements-list" class="w3-margin-top" style="height:115px; overflow-y: auto;">
                    <?php if (count($announcements) > 0): ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="w3-panel w3-light-gray w3-leftbar w3-border-purple">
                                <h4><strong><?php echo htmlspecialchars($announcement['TITLE']); ?></strong></h4>
                                <p><?php echo htmlspecialchars($announcement['MESSAGE']); ?></p>
                                <small>Posted on: <?php echo date("Y-m-d H:i:s", strtotime($announcement['TIMESTAMP'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="font-size: 18px; color: #333; font-family: Arial, sans-serif; margin-top: 20px;">No announcement for today.</p>
                    <?php endif; ?>
                </div>
            </div>
            <!---Leaderboard---->
            <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 100%; height: 450px;">
                <div class="w3-purple w3-container w3-round-xlarge" style="display: flex; align-items: center;">
                    <i class="fa-solid fa-trophy"></i>
                    <h3 style="margin-left: 10px; color: #ffff;">Leaderboard</h3>
                </div>
                <div class="w3-margin-top">
                    <table class="w3-table w3-bordered w3-striped">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Name</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($leaderboard) > 0): ?>
                                <?php foreach ($leaderboard as $index => $leader): ?>
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
                                        <td><?php echo $icon . ' ' . htmlspecialchars($leader['FIRSTNAME'] . ' ' . $leader['LASTNAME']); ?></td>
                                        <td><?php echo htmlspecialchars($leader['POINTS']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No leaderboard data available.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="w3-panel w3-light-gray w3-leftbar w3-border-purple w3-margin-top" style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <p>Your Rank: #<?php echo $user_rank; ?></p>
                            <p>Your Points: <?php echo $user_points; ?></p>
                        </div>
                        <button onclick="document.getElementById('pointsConversion').style.display='block'" class="w3-button w3-purple w3-round-xlarge" style="margin-left: 20px; min-width: 150px;">
                            <i class="fa-solid fa-exchange-alt"></i> Convert Points
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Points Conversion Modal -->
<div id="pointsConversion" class="w3-modal" style="z-index: 1000;">
    <div class="w3-modal-content w3-animate-zoom w3-round-xlarge" style="width: 30%;">
        <header class="w3-container w3-purple w3-round-xlarge"> 
            <span onclick="document.getElementById('pointsConversion').style.display='none'" 
                  class="w3-button w3-display-topright" style="color: #fff; font-size: 2em;">&times;</span>
            <h2 style="color: #fff; display: flex; align-items: center; gap: 10px; margin-bottom: 0;">
                <i class="fa-solid fa-exchange-alt"></i> Points Conversion
            </h2>
        </header>
        <div class="w3-container w3-padding">
            <div class="w3-panel w3-light-gray w3-leftbar w3-border-purple" style="background: #f7f3fa;">
                <p><strong>Current Points:</strong> <?php echo $user_points; ?></p>
                <p><strong>Current Sessions:</strong> <?php echo $result_session['SESSION_COUNT']; ?></p>
                <p><strong>Conversion Rate:</strong> 3 points = 1 session</p>
            </div>
            <hr class="divider">
            <form action="convert_points.php" method="POST" class="w3-container">
                <div class="w3-row-padding">
                    <div class="w3-col m8 s12">
                        <input type="number" name="points_to_convert" class="w3-input w3-border" min="3" max="<?php echo $user_points; ?>" step="3" placeholder="Enter points to convert (multiple of 3)" required>
                    </div>
                    <div class="w3-col m4 s12" style="margin-top: 8px;">
                        <button type="submit" class="w3-button w3-purple w3-round-xlarge" style="width: 100%;">Convert</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
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
            <div class="w3-panel w3-light-gray w3-leftbar w3-border-purple" style="background: #f7f3fa;">
                <p><strong>Total Notifications:</strong> <?php echo count($notifications); ?></p>
                <p><strong>Unread Notifications:</strong> <?php echo $unread_count; ?></p>
            </div>
            <hr class="divider">
            <div class="notif-list" style="max-height: 400px; overflow-y: auto;">
                <?php
                // Get all notifications for the user
                $sql_all_notifications = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
                $stmt_all_notifications = $conn->prepare($sql_all_notifications);
                $stmt_all_notifications->bind_param("s", $user['IDNO']);
                $stmt_all_notifications->execute();
                $result_all_notifications = $stmt_all_notifications->get_result();
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
                                        case 'reservation_approved':
                                            $icon = '<i class="fa fa-check-circle" style="color: #4CAF50;"></i>';
                                            break;
                                        case 'reservation_rejected':
                                            $icon = '<i class="fa fa-times-circle" style="color: #f44336;"></i>';
                                            break;
                                        case 'reward_received':
                                            $icon = '<i class="fa fa-gift" style="color: #9C27B0;"></i>';
                                            break;
                                    }
                                ?>
                                <span class="notif-icon"><?php echo $icon; ?></span>
                                <div style="flex: 1;">
                                    <span class="notif-msg"><?php echo htmlspecialchars($notification['message']); ?></span>
                                    <div class="notif-date"><?php echo date('M d, H:i', strtotime($notification['created_at'])); ?></div>
                                </div>
                            </div>
                            <form method="POST" action="delete_notification.php" style="margin-left: 10px;">
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

<script>
    function w3_open() {
        document.getElementById("mySidebar").style.display = "block";
    }
    function w3_close() {
        document.getElementById("mySidebar").style.display = "none";
    }
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
</script>
</body>
</html>
