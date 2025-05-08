<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Retrieve user data from the session
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

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
    $current = isset($schedule[$day][$slot]) ? $schedule[$day][$slot] : 'Available';
    $is_available = $current === 'Available';
    $color = $is_available ? '#d4f8e8' : '#ffd6d6';
    $dot = $is_available ? '#28a745' : '#dc3545';
    $label = $is_available ? 'Available' : 'Occupied';
    
    return "<div style='background:$color;padding:8px;border-radius:6px;display:flex;align-items:center;gap:8px;'>"
        . "<span class='status-dot' style='color:$dot;'><i class='fa-solid fa-circle'></i></span>"
        . "<span>$label</span>"
        . "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <title>Lab Schedules</title>
    <style>
        body { background: #f4f6fa; }
        img{  border: 2px solid rgba(100,25,117,1); border-radius: 50%; }
        .main-wrapper { max-width: 98vw; margin: 30px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 0; }
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
        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px;">
    </div>
    <a href="dashboard.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
    <a href="#" onclick="document.getElementById('profile').style.display='block'" class="w3-bar-item w3-button"><i class="fa-regular fa-user w3-padding"></i><span>Profile</span></a>
    <a href="profile.php" class="w3-bar-item w3-button"><i class="fa-solid fa-edit w3-padding"></i><span>Edit Profile</span></a>
    <a href="history.php" class="w3-bar-item w3-button"><i class="fa-solid fa-clock-rotate-left w3-padding"></i><span>History</span></a>
    <a href="view_lab_schedules.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedules</span></a>
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
        </div>
        <footer class="w3-container w3-padding" style="margin: 0 30%;">
            <button class="w3-btn w3-purple w3-round-xlarge" onclick="window.location.href='profile.php'">Edit Profile</button>
        </footer>
    </div>
</div>
<div style="margin-left:20%; z-index: 1; position: relative;">
    <div class="title_page w3-container" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">&#9776;</button>
        <h1 style="margin-left: 10px; color: #ffff;">Lab Schedules</h1>
    </div>
    <div class="main-content">
        <div class="room-selector">
            <h3 style="margin-bottom: 15px;"><i class="fa-solid fa-door-open"></i> Select Laboratory Room</h3>
            <form method="get" style="margin-bottom:20px;">
                <?php foreach ($lab_rooms as $room): ?>
                    <button type="submit" name="lab" value="<?php echo $room; ?>" class="room-btn<?php if ($selected_lab == $room) echo ' selected'; ?>">
                        <?php echo $room; ?>
                    </button>
                <?php endforeach; ?>
            </form>
        </div>
        <div class="schedule-container">
            <h3 style="margin-bottom: 20px;"><i class="fa-solid fa-calendar-days"></i> Schedule for Room <?php echo $selected_lab; ?></h3>
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