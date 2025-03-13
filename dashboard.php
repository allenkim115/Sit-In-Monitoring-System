<?php
include 'connect.php';

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
    <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-clock-rotate-left w3-padding"></i><span>History</span></a>
    <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
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
    </div>
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
                                    <p><strong><?php echo htmlspecialchars($announcement['MESSAGE']); ?></strong></p>
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
