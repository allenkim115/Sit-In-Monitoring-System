<?php
define('INCLUDED_IN_MAIN_FILE', true);
include '../includes/connect.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Get the user ID from the URL
$user_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$user_id) {
    header('Location: list.php');
    exit;
}

// Fetch user data
$sql = "SELECT * FROM user WHERE IDNO = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: list.php');
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
    $stmt->bind_param("ssssisi", $lastname, $firstname, $midname, $course, $year_lvl, $username, $idno);
    $stmt->execute();

    // Check if password reset is requested
    if (!empty($password) && $password === $confirm_password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE user SET PASSWORD = ? WHERE IDNO = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $idno);
        $stmt->execute();
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        $unique_name = uniqid() . "_" . basename($_FILES["profile_pic"]["name"]);
        $allowed_type = ['jpg', 'jpeg', 'png', 'gif'];
        $target_file = $target_dir . $unique_name;
        move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file);

        if (!in_array(strtolower(pathinfo($target_file, PATHINFO_EXTENSION)), $allowed_type)) {
            $_SESSION['update_error'] = "Invalid file type. Please upload a valid image file.(jpg, jpeg, png, gif)";
            header("Location: edit_user.php?id=" . $idno);
            exit();
        }

        // Update profile picture filename (not path) in the database
        $sql = "UPDATE user SET PROFILE_PIC = ? WHERE IDNO = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $unique_name, $idno);
        $stmt->execute();
    }

    // Redirect back to list with success message
    $_SESSION['update_success'] = "User updated successfully!";
    header("Location: list.php");
    exit();
}

//get the admin's profile picture from database
$username = $_SESSION['user']['USERNAME'];
$sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param("s", $username);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$admin = $result_profile->fetch_assoc();

$update_success = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : '';
$update_error = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : '';
unset($_SESSION['update_success']);
unset($_SESSION['update_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/w3.css">
    <link rel="stylesheet" href="../css/side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <title>Edit User</title>
    <style>
        .home:hover {
            color: rgba(233,236,107,1);
        }
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
        $profile_pic = isset($admin['PROFILE_PIC']) ? $admin['PROFILE_PIC'] : '../images/default_pic.png';
        ?>  
        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px;">
    </div>
    <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
    <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
    <a href="list.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
    <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
    <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
    <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
    <a href="view_lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
    <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
    <a href="../logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
</div>
<div class="w3-main" style="margin-left:20%">
    <div class="w3-teal">
        <button class="w3-button w3-teal w3-xlarge w3-hide-large" onclick="w3_open()">&#9776;</button>
        <div class="title_page w3-container">
            <h1><a href="list.php" class="w3-button"><i class="fa-solid fa-arrow-left"></i></a>Edit User</h1>
        </div>
    </div>
    <div class="container w3-container w3-margin">
        <?php if ($update_success): ?>
            <div class="w3-panel w3-green w3-display-container" id="successMessage">
                <span onclick="this.parentElement.style.display='none'" class="w3-button w3-green w3-large w3-display-topright"></span>
                <p><?php echo $update_success; ?></p>
            </div>
        <?php endif; ?>
        <?php if ($update_error): ?>
            <div class="w3-panel w3-red w3-display-container" id="errorMessage">
                <span onclick="this.parentElement.style.display='none'" class="w3-button w3-red w3-large w3-display-topright"></span>
                <p><?php echo $update_error; ?></p>
            </div>
        <?php endif; ?>
        <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 75%; margin:auto; background-color:#ffff;">
            <form action="edit_user.php?id=<?php echo htmlspecialchars($user_id); ?>" method="POST" enctype="multipart/form-data">
                <div style="text-align: center;" class="profile w3-padding w3-margin">
                    <img src="<?php echo htmlspecialchars($user['PROFILE_PIC']); ?>" alt="profile_pic" style="width: 120px; height:120px;"><br>
                </div>
                <label for="profile_pic">Profile Pic:</label>
                <input type="file" name="profile_pic" class="w3-input w3-border w3-round"><br>
                <label for="IDNO">IDNO:</label>
                <input type="text" name="IDNO" placeholder="IDNO" class="w3-input w3-border w3-round" value="<?php echo htmlspecialchars($user['IDNO']); ?>" readonly><br>
                <label for="lastname">Lastname:</label>
                <input type="text" name="lastname" placeholder="Lastname" class="w3-input w3-border w3-round" value="<?php echo htmlspecialchars($user['LASTNAME']); ?>" required><br>
                <label for="firstname">Firstname:</label>
                <input type="text" name="firstname" placeholder="Firstname" class="w3-input w3-border w3-round" value="<?php echo htmlspecialchars($user['FIRSTNAME']); ?>" required><br>
                <label for="midname">Middlename:</label>
                <input type="text" name="midname" placeholder="Middlename" class="w3-input w3-border w3-round" value="<?php echo htmlspecialchars($user['MIDDLENAME']); ?>"><br>
                <label for="course">Course:</label>
                <select class="w3-input w3-border w3-round w3-select" name="course" required>
                    <option value="" disabled>Course</option>
                    <option value="BSIT" <?php if ($user['COURSE'] == 'BSIT') echo 'selected'; ?>>BSIT</option>
                    <option value="BSCS" <?php if ($user['COURSE'] == 'BSCS') echo 'selected'; ?>>BSCS</option>
                    <option value="BSCpE" <?php if ($user['COURSE'] == 'BSCpE') echo 'selected'; ?>>BSCpE</option>
                    <option value="ACT" <?php if ($user['COURSE'] == 'ACT') echo 'selected'; ?>>ACT</option>
                    <option value="BSCE" <?php if ($user['COURSE'] == 'BSCE') echo 'selected'; ?>>BSCE</option>
                    <option value="BSCEE" <?php if ($user['COURSE'] == 'BSCEE') echo 'selected'; ?>>BSCEE</option>
                    <option value="BSA" <?php if ($user['COURSE'] == 'BSA') echo 'selected'; ?>>BSA</option>
                    <option value="BSBA" <?php if ($user['COURSE'] == 'BSBA') echo 'selected'; ?>>BSBA</option>
                    <option value="BSOA" <?php if ($user['COURSE'] == 'BSOA') echo 'selected'; ?>>BSOA</option>
                    <option value="BEEd" <?php if ($user['COURSE'] == 'BEEd') echo 'selected'; ?>>BEEd</option>
                    <option value="BSEd" <?php if ($user['COURSE'] == 'BSEd') echo 'selected'; ?>>BSEd</option>
                    <option value="AB PolSci" <?php if ($user['COURSE'] == 'AB PolSci') echo 'selected'; ?>>AB PolSci</option>
                    <option value="BSCrim" <?php if ($user['COURSE'] == 'BSCrim') echo 'selected'; ?>>BSCrim</option>
                    <option value="BSHRM" <?php if ($user['COURSE'] == 'BSHRM') echo 'selected'; ?>>BSHRM</option>
                </select><br>
                <label for="year_lvl">Year Level:</label>
                <select class="w3-input w3-border w3-round w3-select" name="year_lvl" required>
                    <option value="" disabled>Year Level</option>
                    <option value="1" <?php if ($user['YEAR_LEVEL'] == 1) echo 'selected'; ?>>1</option>
                    <option value="2" <?php if ($user['YEAR_LEVEL'] == 2) echo 'selected'; ?>>2</option>
                    <option value="3" <?php if ($user['YEAR_LEVEL'] == 3) echo 'selected'; ?>>3</option>
                    <option value="4" <?php if ($user['YEAR_LEVEL'] == 4) echo 'selected'; ?>>4</option>
                </select><br>
                <label for="username">Username:</label>
                <input type="text" name="username" placeholder="Username" class="w3-input w3-border w3-round" value="<?php echo htmlspecialchars($user['USERNAME']); ?>" required><br>
                <input type="checkbox" id="resetPasswordCheckbox" onclick="togglePasswordFields()" class="w3-padding"> Reset Password?<br>
                <div id="passwordFields" style="display: none;">
                    <label for="NewPass">New Password:</label>
                    <input type="password" name="password" placeholder="New Password" class="w3-input w3-border w3-round"><br>
                    <label for="ConfirmPass">Confirm New Password:</label>
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" class="w3-input w3-border w3-round"><br>
                </div>
                <button type="submit" class="w3-input w3-purple w3-round-xlarge w3-center w3-padding w3-margin" name="SaveEdit" style="width: 25%;">Save Changes</button><br>
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
    var errorMessage = document.getElementById("errorMessage");
    if (successMessage) {
        setTimeout(function() {
            successMessage.style.display = 'none';
        }, 3000);
    }
    if (errorMessage) {
        setTimeout(function() {
            errorMessage.style.display = 'none';
        }, 3000);
    }
}
</script>  
</body>
</html> 