<?php
include 'connect.php';
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

// Handle announcement posting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement_message'])) {
    $message = mysqli_real_escape_string($conn, $_POST['announcement_message']);
    if (!empty($message)) {
        $sql = "INSERT INTO announcement (message) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $message);
        if (!$stmt->execute()) {
            echo "Error: " . $stmt->error; // Consider better error handling
        }
        $stmt->close();
    }
}

// Fetch announcements
$sql_announcements = "SELECT * FROM announcement ORDER BY timestamp DESC"; // Get most recent first
$result_announcements = $conn->query($sql_announcements);
$announcements = [];
if($result_announcements->num_rows > 0){
    while ($row = $result_announcements->fetch_assoc()){
        $announcements[] = $row;
    }
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
    <title>Home</title>
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
  <a href="admin.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
  <a href="profile.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>View Sit-in Records</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-clipboard-list w3-padding"></i><span>Sit-in Reports</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
  <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
</div>
<div style="margin-left:20%; z-index: 1; position: relative;">
  <div class="title_page w3-container" style="display: flex; align-items: center;">
    <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">&#9776;</button>
    <h1 style="margin-left: 10px; color: #ffff;">Dashboard</h1>
  </div>
  <div class="w3-row-padding" style="margin: 5% 10px;">
    <div class="w3-col m6">
    <!---Announcement---->
        <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-margin-bottom w3-animate-top" style="width: 100%;">
            <div class="w3-purple w3-container w3-round-xlarge" style="display: flex; align-items: center;">
            <i class="fa-solid fa-bullhorn"></i>
            <h3 style="margin-left: 10px; color: #ffff;">Announcement</h3>
            </div>
            <form method="POST" class="w3-margin-top">
                <textarea name="announcement_message" class="w3-input w3-border" placeholder="Type your announcement here..." rows="4"></textarea>
                <button type="submit" class="w3-button w3-purple w3-margin-top">Post Announcement</button>
            </form>
            <div id="announcements-list" class="w3-margin-top">
                <?php if (count($announcements) > 0): ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="w3-panel w3-light-gray w3-leftbar w3-border-purple">
                            <p><?php echo htmlspecialchars($announcement['MESSAGE']); ?></p>
                            <small>Posted on: <?php echo date("Y-m-d H:i:s", strtotime($announcement['TIMESTAMP'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 18px; color: #333; font-family: Arial, sans-serif; margin-top: 20px;">No announcement for today.</p>
                <?php endif; ?>
            </div>
        </div>
            </div>
    <div class="w3-col m6"> 
    <!---Statistics---->
        <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 100%; height: 450px;">
        <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-purple">
            <h3><i class="fa-solid fa-chart-simple w3-padding"></i>Statistics</h3>
        </div>
    <br>
    </div>
</div>
</body>
</html>
