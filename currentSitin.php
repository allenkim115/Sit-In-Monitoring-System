<?php
define('INCLUDED_IN_MAIN_FILE', true);
include 'connect.php';
include_once 'notification_functions.php';
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

// Base SQL query for current sit-ins
$sql_sitins = "SELECT sr.ID, u.IDNO, u.FIRSTNAME, u.LASTNAME, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN, u.SESSION_COUNT
               FROM sitin_records sr
               JOIN user u ON sr.IDNO = u.IDNO
               WHERE sr.TIME_OUT IS NULL";

// Check if search term is submitted
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    // Search by IDNO or name while maintaining current sit-in filter
    $sql_sitins .= " AND (u.IDNO LIKE '%$search_term%' OR u.FIRSTNAME LIKE '%$search_term%' OR u.LASTNAME LIKE '%$search_term%')";
}

// Add order by clause
$sql_sitins .= " ORDER BY sr.TIME_IN DESC";

// Execute the query
$result_sitins = $conn->query($sql_sitins);

$sitin_records = [];
if ($result_sitins->num_rows > 0) {
    while ($row = $result_sitins->fetch_assoc()) {
        $sitin_records[] = $row;
    }
}
include 'search_modal.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="side_nav.css">
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
            $profile_pic = isset($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : 'images/default_pic.png';
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
        <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
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
                        <button type="submit" class="w3-button w3-purple w3-round-large w3-small">Search</button>
                        <a href="currentSitin.php" class="w3-button w3-gray w3-round-large w3-small">Clear</a>
                    </form>
                </div>
            <div class="w3-container" style="margin: 0 10px; display: flex; justify-content: flex-end;">
                <a href="SitinRecords.php" class="w3-button w3-purple w3-round-large w3-margin-bottom">View Sit-in Records</a>
            </div>
            <h2 class="w3-margin-bottom">Current Sit-in</h2>            
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
                <?php if (count($sitin_records) > 0) : ?>
                    <?php foreach ($sitin_records as $record) : ?>
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
                        <td colspan="8">No current sit-ins found.</td>
                    </tr>
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
