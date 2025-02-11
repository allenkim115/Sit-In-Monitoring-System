<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
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
    <title>Dashboard</title>
</head>
<body>
<div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
  <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-x"></i></button>
  <div class="profile w3-center">
    <img src="images/default_pic.png" alt="profile_pic" style="width: 90px; height:90px;">
  </div>
  <a href="profile.php" class="w3-bar-item w3-button"><i class="fa-regular fa-user w3-padding"></i><span>Profile</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-volume-high w3-padding"></i><span>View Announcement</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-hourglass-start w3-padding"></i><span>View Remaining Session</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-book-open w3-padding"></i><span>Sit-in Rules</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-brands fa-readme w3-padding"></i><span>Lab Rules & Regulation</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-clock-rotate-left w3-padding"></i><span>History</span></a>
  <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
  <a href="login.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
</div>

<div class="w3-main" style="margin-left:20%">
<div class="w3-teal">
  <button class="w3-button w3-teal w3-xlarge w3-hide-large" onclick="w3_open()">&#9776;</button>
  <div class="title_page w3-container">
    <h1><a href="dashboard.php"></a>Dashboard</h1>
  </div>
</div>

<div class="w3-container">
  <h3>Welcome to CCS Sit-in Monitoring System</h3>
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
