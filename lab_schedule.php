<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Define lab rooms and time slots
$lab_rooms = ['524', '526', '528', '530', '542', '544'];
$days = ['Monday/Wednesday', 'Tuesday/Thursday', 'Friday', 'Saturday'];
$time_slots = [
    '7:30AM-9:00AM',
    '9:00AM-10:30AM',
    '10:30AM-12:00PM',
    '12:00PM-1:00PM',
    '1:00PM-3:00PM',
    '3:00PM-4:30PM',
    '4:30PM-6:00PM',
    '6:00PM-7:30PM',
    '7:30PM-9:00PM'
];

// Get selected lab room
$selected_lab = isset($_GET['lab']) && in_array($_GET['lab'], $lab_rooms) ? $_GET['lab'] : $lab_rooms[0];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_schedule'])) {
    $lab = $_POST['lab_room'];
    foreach ($days as $day) {
        foreach ($time_slots as $slot) {
            $key = $day . '_' . $slot;
            $status = isset($_POST['status'][$key]) ? $_POST['status'][$key] : 'Available';
            // Check if entry exists
            $stmt = $conn->prepare("SELECT id FROM lab_schedule WHERE lab_room=? AND day_of_week=? AND time_slot=?");
            $stmt->bind_param('sss', $lab, $day, $slot);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                // Update
                $update = $conn->prepare("UPDATE lab_schedule SET status=? WHERE id=?");
                $update->bind_param('si', $status, $row['id']);
                $update->execute();
                $update->close();
            } else {
                // Insert
                $insert = $conn->prepare("INSERT INTO lab_schedule (lab_room, day_of_week, time_slot, status) VALUES (?, ?, ?, ?)");
                $insert->bind_param('ssss', $lab, $day, $slot, $status);
                $insert->execute();
                $insert->close();
            }
            $stmt->close();
        }
    }
    header('Location: lab_schedule.php?lab=' . $lab);
    exit;
}

// Fetch current schedule for selected lab
$schedule = [];
$stmt = $conn->prepare("SELECT day_of_week, time_slot, status FROM lab_schedule WHERE lab_room=?");
$stmt->bind_param('s', $selected_lab);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $schedule[$row['day_of_week']][$row['time_slot']] = $row['status'];
}
$stmt->close();

function status_cell($day, $slot, $schedule) {
    $key = $day . '_' . $slot;
    $current = isset($schedule[$day][$slot]) ? $schedule[$day][$slot] : 'Available';
    $is_available = $current === 'Available';
    $color = $is_available ? '#d4f8e8' : '#ffd6d6';
    $dot = $is_available ? 'green' : 'red';
    $label = $is_available ? 'Available' : 'Occupied';
    
    return "<div style='background:$color;padding:8px;border-radius:6px;display:flex;align-items:center;gap:8px;'>"
        . "<select name='status[$key]' onchange='this.style.backgroundColor=this.value===\"Available\"?\"#d4f8e8\":\"#ffd6d6\"' style='border:none;background:transparent;width:100%;'>"
        . "<option value='Available' " . ($is_available ? 'selected' : '') . ">Available</option>"
        . "<option value='Occupied' " . (!$is_available ? 'selected' : '') . ">Occupied</option>"
        . "</select>"
        . "</div>";
}

//get the profile picture from database
$username = $_SESSION['user']['USERNAME']; // Assuming you store username in session
$sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param("s", $username);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$user = $result_profile->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <title>Lab Schedule Management</title>
    <style>
        .room-btn { margin: 0 5px 10px 0; padding: 8px 18px; border: none; border-radius: 6px; background: #e3eefd; color: #1a237e; font-weight: bold; cursor: pointer; }
        .room-btn.selected { background: #2a5ca7; color: #fff; }
        .schedule-table th, .schedule-table td { text-align: center; }
        .schedule-table th { background: #175fae; color: #fff; }
        .main-content { margin: 20px; }
        .room-selector { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .schedule-container { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status-available { background: #d4f8e8; padding: 8px; border-radius: 6px; display: flex; align-items: center; gap: 8px; }
        .status-occupied { background: #ffd6d6; padding: 8px; border-radius: 6px; display: flex; align-items: center; gap: 8px; }
        .status-dot { font-size: 1.2em; }
        .status-dot.available { color: #28a745; }
        .status-dot.occupied { color: #dc3545; }
        .save-btn { background: #175fae; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; transition: background 0.3s; }
        .save-btn:hover { background: #2a5ca7; }
        .schedule-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .schedule-table th { padding: 15px; font-weight: 600; }
        .schedule-table td { padding: 10px; }
        .schedule-table tr:hover td { background: #f8f9fa; }
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
        <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="lab_schedule.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedule</span></a>
        <a href="lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>
    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">☰</button>
            <h1 style="margin-left: 10px; color: #ffff;">Lab Schedule Management</h1>
        </div>
        <div class="main-content">
            <div class="room-selector">
                <h3 style="margin-bottom: 15px;"><i class="fa-solid fa-door-open"></i> Select Laboratory Room</h3>
                <form method="get" style="margin-bottom:20px;">
                    <?php foreach ($lab_rooms as $room): ?>
                        <button type="submit" name="lab" value="<?php echo $room; ?>" class="room-btn<?php if ($selected_lab == $room) echo ' selected'; ?>">
                            <i class="fa-solid fa-door-closed"></i><?php echo $room; ?>
                        </button>
                    <?php endforeach; ?>
                </form>
            </div>
            <div class="schedule-container">
                <h3 style="margin-bottom: 20px;"><i class="fa-solid fa-calendar-days"></i> Schedule for Room <?php echo $selected_lab; ?></h3>
                <form method="post">
                    <input type="hidden" name="lab_room" value="<?php echo $selected_lab; ?>">
                    <div class="table-responsive">
                        <table class="w3-table w3-bordered schedule-table">
                            <thead>
                                <tr>
                                    <th>Time Slot</th>
                                    <?php foreach ($days as $day): ?>
                                        <th><?php echo $day; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($time_slots as $slot): ?>
                                    <tr>
                                        <td><strong><?php echo $slot; ?></strong></td>
                                        <?php foreach ($days as $day): ?>
                                            <td><?php echo status_cell($day, $slot, $schedule); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" name="save_schedule" class="save-btn">
                            <i class="fa-solid fa-floppy-disk"></i> Save Schedule
                        </button>
                    </div>
                </form>
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