<?php
include 'connect.php';

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = $_POST['IDNO'];
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $midname = $_POST['midname'];
    $course = $_POST['course'];
    $year_lvl = $_POST['year_lvl'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Update user information
    $sql = "UPDATE user SET LASTNAME = ?, FIRSTNAME = ?, MIDDLENAME = ?, COURSE = ?, YEAR_LEVEL = ?, USERNAME = ? WHERE IDNO = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssissi", $lastname, $firstname, $midname, $course, $year_lvl, $username, $idno);
    $stmt->execute();

    // Check if password reset is requested
    if (!empty($password) && $password === $confirm_password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE user SET PASSWORD = ? WHERE IDNO = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $idno);
        $stmt->execute();
    }

    // Update session data
    $sql = "SELECT * FROM user WHERE IDNO = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idno);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $_SESSION['user'] = $user;

    // Redirect back to profile with success message
    $_SESSION['update_success'] = "Profile updated successfully!";
    header("Location: profile.php");
    exit();
}

// Assuming user data is stored in the session
$user = $_SESSION['user'];
$update_success = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : '';
unset($_SESSION['update_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <title>Document</title>
    <style>
        .home:hover {
            color: rgba(233,236,107,1); /* White text */
        }
    </style>
</head>
<body>
<div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
  <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-x"></i></button>
  <div class="profile w3-center">
    <img src="images/default_pic.png" alt="profile_pic" style="width: 90px; height:90px;">
  </div>
  <a href="profile.php" class="w3-bar-item w3-button active"><i class="fa-regular fa-user w3-padding"></i><span>Profile</span></a>
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
    <h1><a class="home" href="dashboard.php" style="text-decoration:none;">Dashboard</a></h1>
    </div>
    </div>
    <div class="container w3-container w3-margin">
        <?php if ($update_success): ?>
            <div class="w3-panel w3-green w3-display-container" id="successMessage">
                <span onclick="this.parentElement.style.display='none'" class="w3-button w3-green w3-large w3-display-topright"></span>
                <p><?php echo $update_success; ?></p>
            </div>
        <?php endif; ?>
        <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 75%; margin:auto; background-color:#ffff;">
            <form action="profile.php" method="POST">
                <h2 class="w3-margin" style="text-transform: uppercase; font-weight: 600;">Edit Profile</h2>
                <input type="text" name="IDNO" placeholder="IDNO" class="w3-input w3-border w3-round" value="<?php echo htmlspecialchars($user['IDNO']); ?>" readonly><br>
                <input type="text" name="lastname" placeholder="Lastname" class="w3-input w3-border w3-round" value="<?php echo htmlspecialchars($user['LASTNAME']); ?>" required><br>
                <input type="text" name="firstname" placeholder="Firstname" class="w3-input w3-border w3-round" value="<?php echo htmlspecialchars($user['FIRSTNAME']); ?>" required><br>
                <input type="text" name="midname" placeholder="Middlename" class="w3-input w3-border w3-round" value="<?php echo htmlspecialchars($user['MIDDLENAME']); ?>"><br>
                <select class="w3-input w3-border w3-round w3-select" name="course" required>
                    <option value="" disabled>Course</option>
                    <option value="1" <?php if ($user['COURSE'] == 1) echo 'selected'; ?>>BSIT</option>
                    <option value="2" <?php if ($user['COURSE'] == 2) echo 'selected'; ?>>BSCS</option>
                    <option value="3" <?php if ($user['COURSE'] == 3) echo 'selected'; ?>>BSCpE</option>
                </select><br>
                <select class="w3-input w3-border w3-round w3-select" name="year_lvl" required>
                    <option value="" disabled>Year Level</option>
                    <option value="1" <?php if ($user['YEAR_LEVEL'] == 1) echo 'selected'; ?>>1</option>
                    <option value="2" <?php if ($user['YEAR_LEVEL'] == 2) echo 'selected'; ?>>2</option>
                    <option value="3" <?php if ($user['YEAR_LEVEL'] == 3) echo 'selected'; ?>>3</option>
                    <option value="4" <?php if ($user['YEAR_LEVEL'] == 4) echo 'selected'; ?>>4</option>
                </select><br>
                <input type="text" name="username" placeholder="Username" class="w3-input w3-border w3-round" value="<?php echo htmlspecialchars($user['USERNAME']); ?>" required><br>
                <input type="checkbox" id="resetPasswordCheckbox" onclick="togglePasswordFields()"> Reset Password<br>
                <div id="passwordFields" style="display: none;">
                    <input type="password" name="password" placeholder="Password" class="w3-input w3-border w3-round"><br>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" class="w3-input w3-border w3-round"><br>
                </div>
                <button type="submit" class="w3-input w3-blue w3-round-xlarge w3-center" name="Register">Save</button><br>
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
function togglePasswordFields() {
    var passwordFields = document.getElementById("passwordFields");
    if (document.getElementById("resetPasswordCheckbox").checked) {
        passwordFields.style.display = "block";
    } else {
        passwordFields.style.display = "none";
    }
}
window.onload = function() {
    var successMessage = document.getElementById("successMessage");
    if (successMessage) {
        setTimeout(function() {
            successMessage.style.display = 'none';
        }, 3000); // Hide after 3 seconds
    }
}
</script>  
</body>
</html>