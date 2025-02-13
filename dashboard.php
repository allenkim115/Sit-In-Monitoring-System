<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Retrieve user data from the session
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
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
    </style>
</head>
<body>
<div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
  <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-x"></i></button>
  <div class="profile w3-center w3-margin w3-padding">
    <?php
    $profile_pic = isset($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : 'images/default_pic.png';
    ?>
    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px;">
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

<div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 50%; margin:auto; margin-top: 5%; background-color:#fcfbfc;">
    <div class="w3-center w3-margin w3-padding" style="margin-bottom:0;">
        <img src="<?php echo htmlspecialchars($user['PROFILE_PIC']); ?>" alt="profile_pic" style="width: 150px; height:150px;">
    </div>
    <div class="w3-center w3-padding">
        <h2 id="welcome-text">Welcome, <?php echo htmlspecialchars($user['FIRSTNAME']); ?></h2>
        <p id="typing-text" style="font-size: 18px; color: #333; font-family: Arial, sans-serif; margin-top: 10px;">to CSS Sit-In Monitoring System</p>
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
