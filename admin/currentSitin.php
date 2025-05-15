<?php
define('INCLUDED_IN_MAIN_FILE', true);
include '../includes/connect.php';
include_once '../notifications/notification_functions.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

//get the profile picture from database
$username = $_SESSION['user']['USERNAME']; // Assuming you store username in session
$sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param("s", $username);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$user = $result_profile->fetch_assoc();

// Initialize search term
$search_term = "";

// Initialize $stmt_deduct_session to null before the try block
$stmt_deduct_session = null;

// Handle Time Out action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['timeout_id'])) {
    $timeout_id = $_POST['timeout_id'];
    $sitin_record_id = $_POST['sitin_record_id'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Update the sit-in record to set the time out
        $sql_timeout = "UPDATE sitin_records SET TIME_OUT = NOW() WHERE ID = ?";
        $stmt_timeout = $conn->prepare($sql_timeout);
        $stmt_timeout->bind_param("i", $sitin_record_id);
        $stmt_timeout->execute();

        // Deduct the user's session
        $sql_deduct_session = "UPDATE user SET SESSION_COUNT = SESSION_COUNT - 1 WHERE IDNO = ?";
        $stmt_deduct_session = $conn->prepare($sql_deduct_session);
        $stmt_deduct_session->bind_param("s", $timeout_id);
        $stmt_deduct_session->execute();

        // If reward button was pressed, add points
        if (isset($_POST['timeout_reward'])) {
            $reward_points = 1;
            $sql_reward = "UPDATE user SET POINTS = IFNULL(POINTS, 0) + ? WHERE IDNO = ?";
            $stmt_reward = $conn->prepare($sql_reward);
            $stmt_reward->bind_param("is", $reward_points, $timeout_id);
            $stmt_reward->execute();

            // Fetch the updated total points
            $sql_fetch_points = "SELECT POINTS FROM user WHERE IDNO = ?";
            $stmt_fetch_points = $conn->prepare($sql_fetch_points);
            $stmt_fetch_points->bind_param("s", $timeout_id);
            $stmt_fetch_points->execute();
            $result_fetch_points = $stmt_fetch_points->get_result();
            $user_points = $result_fetch_points->fetch_assoc()['POINTS'];

            // Create notification for reward
            $message = "You have earned 1 point for your sit-in session! (Total points: $user_points)";
            createNotification($timeout_id, 'reward_received', $message);
        }

        // Commit the transaction
        $conn->commit();

        // Set success message in session
        if (isset($_POST['timeout_reward'])) {
            $_SESSION['timeout_success'] = "Student logged out and earned 1 point! (Total points: $user_points)";
        } else {
            $_SESSION['timeout_success'] = "Time out successful!";
        }

        // Redirect to currentSitin.php
        header("Location: currentSitin.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    } 
}

// Base SQL query for direct sit-ins
$sql_direct_sitins = "SELECT sr.ID, u.IDNO, u.FIRSTNAME, u.LASTNAME, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN, u.SESSION_COUNT
               FROM sitin_records sr
               JOIN user u ON sr.IDNO = u.IDNO
               WHERE sr.TIME_OUT IS NULL 
               AND NOT EXISTS (
                   SELECT 1 FROM reservations r 
                   WHERE r.idno = sr.IDNO 
                   AND r.room_number = sr.LABORATORY 
                   AND DATE(r.reservation_date) = DATE(sr.TIME_IN)
                   AND r.status = 'approved'
               )";

// Base SQL query for reserved sit-ins
$sql_reserved_sitins = "SELECT sr.ID, u.IDNO, u.FIRSTNAME, u.LASTNAME, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN, u.SESSION_COUNT,
                        r.reservation_date, r.time_slot
                        FROM sitin_records sr
                        JOIN user u ON sr.IDNO = u.IDNO
                        JOIN reservations r ON r.idno = sr.IDNO 
                            AND r.room_number = sr.LABORATORY 
                            AND DATE(r.reservation_date) = DATE(sr.TIME_IN)
                            AND r.status = 'approved'
                        WHERE sr.TIME_OUT IS NULL";

// Auto timeout expired reservations
$sql_auto_timeout = "UPDATE sitin_records sr
                    JOIN reservations r ON r.idno = sr.IDNO 
                        AND r.room_number = sr.LABORATORY 
                        AND DATE(r.reservation_date) = DATE(sr.TIME_IN)
                        AND r.status = 'approved'
                    JOIN user u ON sr.IDNO = u.IDNO
                    SET sr.TIME_OUT = NOW(),
                        u.SESSION_COUNT = u.SESSION_COUNT - 1
                    WHERE sr.TIME_OUT IS NULL
                    AND (
                        DATE(r.reservation_date) < CURDATE()
                        OR (
                            DATE(r.reservation_date) = CURDATE()
                            AND TIME(SUBSTRING_INDEX(r.time_slot, '-', -1)) < CURTIME()
                        )
                    )";

$conn->query($sql_auto_timeout);

// Check if search term is submitted
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    // Search by IDNO or name while maintaining current sit-in filter
    $search_condition = " AND (u.IDNO LIKE '%$search_term%' OR u.FIRSTNAME LIKE '%$search_term%' OR u.LASTNAME LIKE '%$search_term%')";
    $sql_direct_sitins .= $search_condition;
    $sql_reserved_sitins .= $search_condition;
}

// Add order by clause
$sql_direct_sitins .= " ORDER BY sr.TIME_IN DESC";
$sql_reserved_sitins .= " ORDER BY sr.TIME_IN DESC";

// Execute the queries
$result_direct_sitins = $conn->query($sql_direct_sitins);
$result_reserved_sitins = $conn->query($sql_reserved_sitins);

$direct_sitin_records = [];
$reserved_sitin_records = [];

if ($result_direct_sitins->num_rows > 0) {
    while ($row = $result_direct_sitins->fetch_assoc()) {
        $direct_sitin_records[] = $row;
    }
}

if ($result_reserved_sitins->num_rows > 0) {
    while ($row = $result_reserved_sitins->fetch_assoc()) {
        $reserved_sitin_records[] = $row;
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
    <title>Current Sit-ins</title>
    <style>
        .sitin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .sitin-table th,
        .sitin-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .sitin-table th {
            background-color: #f0fff0;
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
        <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="lab_schedule.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedule</span></a>
        <a href="lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="reservation_management.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="../logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>
    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">&#9776;</button>
            <h1 style="margin-left: 10px; color: #ffff;">Current Sit-ins</h1>
        </div>
        <div class="w3-container" style="margin: 5% 10px;">
            <!-- Search Bar -->
            <div class="w3-row w3-margin-bottom">
                <div class="w3-col m6">
                    <form method="GET" class="w3-bar">
                        <input type="text" name="search" class="w3-input w3-border w3-round" style="width: auto; display: inline-block;" placeholder="IDNO/Name" value="<?php echo htmlspecialchars($search_term); ?>">
                        <input type="hidden" name="active_tab" id="active_tab_input" value="<?php echo isset($_GET['active_tab']) ? htmlspecialchars($_GET['active_tab']) : 'Direct'; ?>">
                        <button type="submit" class="w3-button w3-purple w3-round-large w3-small">Search</button>
                        <a href="currentSitin.php" class="w3-button w3-gray w3-round-large w3-small">Clear</a>
                    </form>
                </div>
            <div class="w3-container" style="margin: 0 10px; display: flex; justify-content: flex-end;">
                <a href="SitinRecords.php" class="w3-button w3-purple w3-round-large w3-margin-bottom">View Sit-in Records</a>
            </div>

            <!-- Tab Navigation -->
            <div class="w3-bar w3-light-grey w3-round-large">
                <button class="w3-bar-item w3-button <?php echo (!isset($_GET['active_tab']) || $_GET['active_tab'] === 'Direct') ? 'w3-purple' : ''; ?>" onclick="openTab('Direct')">Direct Sit-ins</button>
                <button class="w3-bar-item w3-button <?php echo (isset($_GET['active_tab']) && $_GET['active_tab'] === 'Reserved') ? 'w3-purple' : ''; ?>" onclick="openTab('Reserved')">Reserved Sit-ins</button>
            </div>

            <!-- Direct Sit-ins Tab -->
            <div id="Direct" class="w3-container tab" style="display:<?php echo (!isset($_GET['active_tab']) || $_GET['active_tab'] === 'Direct') ? 'block' : 'none'; ?>">
                <h2 class="w3-margin-bottom">Direct Sit-ins</h2>            
                <table class="sitin-table">
                    <thead>
                        <tr>
                            <th>IDNO</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Purpose</th>
                            <th>Laboratory</th>
                            <th>Sessions</th>
                            <th>Time In</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (isset($_SESSION['timeout_success'])) : ?>                    
                        <div id="timeoutSuccess" class="w3-panel w3-green w3-display-container" style="margin-bottom: 20px;">
                            <span class="w3-xlarge" style="margin-right: 8px;">&#x2714;</span>
                            <?php echo htmlspecialchars($_SESSION['timeout_success']); unset($_SESSION['timeout_success']); ?>
                        </div>
                        <script>
                            setTimeout(function() {
                                var timeoutSuccess = document.getElementById('timeoutSuccess');
                                if (timeoutSuccess) {
                                    timeoutSuccess.style.display = 'none';
                                }
                            }, 2000);
                        </script>
                    <?php endif; ?>
                    <?php if (count($direct_sitin_records) > 0) : ?>
                        <?php foreach ($direct_sitin_records as $record) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['IDNO']); ?></td>
                                <td><?php echo htmlspecialchars($record['FIRSTNAME']); ?></td>
                                <td><?php echo htmlspecialchars($record['LASTNAME']); ?></td>
                                <td><?php echo htmlspecialchars($record['PURPOSE']); ?></td>
                                <td><?php echo htmlspecialchars($record['LABORATORY']); ?></td>
                                <td><?php echo htmlspecialchars($record['SESSION_COUNT']); ?></td>
                                <td><?php echo date("Y-m-d g:i a", strtotime($record['TIME_IN'])); ?></td>
                                <td style="text-align: center;">
                                    <!-- Regular Time Out -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to time out this user?');">
                                        <input type="hidden" name="timeout_id" value="<?php echo htmlspecialchars($record['IDNO']); ?>">
                                        <input type="hidden" name="sitin_record_id" value="<?php echo htmlspecialchars($record['ID']); ?>">
                                        <button type="submit" name="timeout_regular" class="w3-button w3-red w3-round-large w3-small">Time Out</button>
                                    </form>
                                    <!-- Time Out with Reward -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Time out and reward this user?');">
                                        <input type="hidden" name="timeout_id" value="<?php echo htmlspecialchars($record['IDNO']); ?>">
                                        <input type="hidden" name="sitin_record_id" value="<?php echo htmlspecialchars($record['ID']); ?>">
                                        <button type="submit" name="timeout_reward" class="w3-button w3-green w3-round-large w3-small">Time Out + Reward</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8">No direct sit-ins found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Reserved Sit-ins Tab -->
            <div id="Reserved" class="w3-container tab" style="display:<?php echo (isset($_GET['active_tab']) && $_GET['active_tab'] === 'Reserved') ? 'block' : 'none'; ?>">
                <h2 class="w3-margin-bottom">Reserved Sit-ins</h2>            
                <table class="sitin-table">
                    <thead>
                        <tr>
                            <th>IDNO</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Purpose</th>
                            <th>Laboratory</th>
                            <th>Sessions</th>
                            <th>Time In</th>
                            <th>Reserved Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($reserved_sitin_records) > 0) : ?>
                        <?php foreach ($reserved_sitin_records as $record) : 
                            // Get server's current date and time
                            $server_date = date('Y-m-d');
                            $server_time = date('H:i:s');
                            
                            // Parse the time slot
                            $time_slot = explode('-', $record['time_slot']);
                            $start_time = date('H:i:s', strtotime($time_slot[0]));
                            $end_time = date('H:i:s', strtotime($time_slot[1]));
                            
                            // Check if current server date matches reservation date
                            $is_correct_date = ($server_date === date('Y-m-d', strtotime($record['reservation_date'])));
                            
                            // Check if current server time is within the reservation time slot
                            $is_within_time_slot = ($is_correct_date && $server_time >= $start_time && $server_time <= $end_time);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['IDNO']); ?></td>
                                <td><?php echo htmlspecialchars($record['FIRSTNAME']); ?></td>
                                <td><?php echo htmlspecialchars($record['LASTNAME']); ?></td>
                                <td><?php echo htmlspecialchars($record['PURPOSE']); ?></td>
                                <td><?php echo htmlspecialchars($record['LABORATORY']); ?></td>
                                <td><?php echo htmlspecialchars($record['SESSION_COUNT']); ?></td>
                                <td><?php echo date("Y-m-d g:i a", strtotime($record['TIME_IN'])); ?></td>
                                <td><?php echo date("Y-m-d", strtotime($record['reservation_date'])) . " " . $record['time_slot']; ?></td>
                                <td style="text-align: center;">
                                    <?php if ($is_within_time_slot) : ?>
                                        <!-- Regular Time Out -->
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to time out this user?');">
                                            <input type="hidden" name="timeout_id" value="<?php echo htmlspecialchars($record['IDNO']); ?>">
                                            <input type="hidden" name="sitin_record_id" value="<?php echo htmlspecialchars($record['ID']); ?>">
                                            <button type="submit" name="timeout_regular" class="w3-button w3-red w3-round-large w3-small">Time Out</button>
                                        </form>
                                        <!-- Time Out with Reward -->
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Time out and reward this user?');">
                                            <input type="hidden" name="timeout_id" value="<?php echo htmlspecialchars($record['IDNO']); ?>">
                                            <input type="hidden" name="sitin_record_id" value="<?php echo htmlspecialchars($record['ID']); ?>">
                                            <button type="submit" name="timeout_reward" class="w3-button w3-green w3-round-large w3-small">Time Out + Reward</button>
                                        </form>
                                    <?php else : ?>
                                        <span class="w3-text-gray">
                                            <?php 
                                            if (!$is_correct_date) {
                                                echo "Not the reserved date";
                                            } else {
                                                echo "Not within reserved time slot";
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="9">No reserved sit-ins found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
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

        function openTab(tabName) {
            var i;
            var x = document.getElementsByClassName("tab");
            for (i = 0; i < x.length; i++) {
                x[i].style.display = "none";
            }
            document.getElementById(tabName).style.display = "block";
            
            // Update tab button styles
            var buttons = document.getElementsByClassName("w3-bar-item");
            for (i = 0; i < buttons.length; i++) {
                buttons[i].className = buttons[i].className.replace(" w3-purple", "");
            }
            event.currentTarget.className += " w3-purple";

            // Update hidden input value
            document.getElementById('active_tab_input').value = tabName;
        }
    </script>
</body>

</html>
