<?php


include 'connect.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Fetch student counts by course
$sql_course_counts = "SELECT COURSE, COUNT(*) as count FROM user GROUP BY COURSE";
$result_course_counts = $conn->query($sql_course_counts);
$course_counts = [];
if ($result_course_counts->num_rows > 0) {
    while ($row = $result_course_counts->fetch_assoc()) {
        $course_counts[$row['COURSE']] = $row['count'];
    }
}

// Prepare data for the pie chart
$chart_labels = json_encode(array_keys($course_counts));
$chart_data = json_encode(array_values($course_counts));


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
    header("Location: admin.php");
    exit;
}

// Handle announcement deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_announcement'])) {
    $announcement_id = $_POST['delete_announcement'];
    $sql = "DELETE FROM announcement WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $announcement_id);
    if (!$stmt->execute()) {
        echo "Error deleting announcement: " . $stmt->error;
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle announcement update (display edit form)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['edit_announcement'])) {
    $announcement_id = $_GET['edit_announcement'];
    $sql = "SELECT * FROM announcement WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $announcement_to_edit = $result->fetch_assoc();
    $stmt->close();

    if (!$announcement_to_edit) {
        echo "Announcement not found.";
        exit;
    }
}

// Handle announcement update (submit changes)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_announcement_id']) && isset($_POST['updated_announcement_message'])) {
    $announcement_id = $_POST['update_announcement_id'];
    $updated_message = mysqli_real_escape_string($conn, $_POST['updated_announcement_message']);

    $sql = "UPDATE announcement SET message = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $updated_message, $announcement_id);
    if (!$stmt->execute()) {
        echo "Error updating announcement: " . $stmt->error;
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Fetch announcements
$sql_announcements = "SELECT * FROM announcement ORDER BY timestamp DESC"; // Get most recent first
$result_announcements = $conn->query($sql_announcements);
$announcements = [];
if ($result_announcements->num_rows > 0) {
    while ($row = $result_announcements->fetch_assoc()) {
        $announcements[] = $row;
    }
}
// Fetch current sit-in records (where time_out is NULL)
$sql_sitins = "SELECT sr.id, u.IDNO, u.FIRSTNAME, u.LASTNAME, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN
               FROM sitin_records sr
               JOIN user u ON sr.IDNO = u.IDNO
               WHERE sr.TIME_OUT IS NULL";
$result_sitins = $conn->query($sql_sitins);

$sitin_records = [];
if ($result_sitins->num_rows > 0) {
    while ($row = $result_sitins->fetch_assoc()) {
        $sitin_records[] = $row;
    }
}

// Handle student search (Modal Search) & Sit-in Form
$student_found = null;
$search_error = null;
$show_search_modal = false;
$show_result_modal = false;
$show_sitin_form = false;
$sitin_error = null;
$sitin_success = null;

// Handle adding sit-in record
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sitin_user_id']) && isset($_POST['purpose']) && isset($_POST['laboratory'])) {
    $user_id = $_POST['add_sitin_user_id'];
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $laboratory = mysqli_real_escape_string($conn, $_POST['laboratory']);

    // Check if the user is already sitting-in
    $sql_check_sitin = "SELECT * FROM sitin_records WHERE IDNO = ? AND TIME_OUT IS NULL";
    $stmt_check_sitin = $conn->prepare($sql_check_sitin);
    $stmt_check_sitin->bind_param("i", $user_id);
    $stmt_check_sitin->execute();
    $result_check_sitin = $stmt_check_sitin->get_result();
    
    if ($result_check_sitin->num_rows > 0) {
        $sitin_error = "The user is already sitting in. Please Time Out the user first.";
    } else {
            $sql_add_sitin = "INSERT INTO sitin_records (IDNO, PURPOSE, LABORATORY, TIME_IN) VALUES (?, ?, ?, NOW())";
            $stmt_add_sitin = $conn->prepare($sql_add_sitin);
            $stmt_add_sitin->bind_param("iss", $user_id, $purpose, $laboratory);

            if ($stmt_add_sitin->execute()) {
                $sitin_success = "Sitin record added successfully";
                $show_sitin_form = false;
                $show_result_modal = true;
                $student_found = null;
                
            } else {
                $sitin_error = "Error adding sitin record. Please try again";
            }
             $stmt_add_sitin->close();
    }

    $stmt_check_sitin->close();
}

// Handle student search
$student_found = null;
$search_error = null;
$show_search_modal = false;
$show_result_modal = false; // Added variable for result modal visibility

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_idno'])) {
    $search_idno = mysqli_real_escape_string($conn, $_POST['search_idno']);
    $show_search_modal = true;
    if (!empty($search_idno)) {
        $sql_search = "SELECT * FROM user WHERE IDNO = ?";
        $stmt_search = $conn->prepare($sql_search);
        $stmt_search->bind_param("s", $search_idno);
        $stmt_search->execute();
        $result_search = $stmt_search->get_result();
        if ($result_search->num_rows > 0) {
            $student_found = $result_search->fetch_assoc();
            $show_result_modal = true;
            $show_sitin_form = true;
        } else {
            $search_error = "Student not found.";
            $show_result_modal = false;
            $show_sitin_form = false;
        }
        $stmt_search->close();
    } else {
        $search_error = "Please enter an ID number.";
        $show_result_modal = false;
        $show_sitin_form = false;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Home</title>
    <style>
        .announcement-actions {
            display: flex;
            gap: 5px;
            margin-top: 5px;
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
        <a href="admin.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-clipboard-list w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>
       <!-- Search Modal -->
       <div id="searchModal" class="w3-modal" style="z-index: 1000; display: <?php echo ($show_search_modal && !$show_result_modal) ? 'block' : 'none'; ?>;">
        <div class="w3-modal-content w3-animate-zoom w3-round-xlarge" style="width: 30%;">
            <header class="w3-container">
                <span onclick="document.getElementById('searchModal').style.display='none'" class="w3-button w3-display-topright">&times;</span>
                <h2 style="text-transform:uppercase;">Search Student</h2>
            </header>
            <div class="w3-container">
                <form method="POST">
                    <script>
                        setTimeout(function() {
                            var successMessage = document.getElementById('successMessage');
                            if (successMessage) {
                                successMessage.style.display = 'none';
                            }
                        }, 2000);
                    </script>
                    <?php if ($search_error && !$show_result_modal) : ?>
                        <p class="w3-text-red w3-bold" id="successMessage"><?php echo htmlspecialchars($search_error); ?></p>
                    <?php endif; ?>
                    <label for="search_idno">Enter Student IDNO:</label>
                    <input type="text" id="search_idno" name="search_idno" class="w3-input w3-border" required>
                    <button type="submit" class="w3-button w3-purple w3-margin w3-padding w3-round-large w3-right">Search</button>
                </form>
            </div>
        </div>
    </div>
 <!--Result Modal-->
 <div id="resultModal" class="w3-modal" style="z-index: 1001; display: <?php echo ($show_result_modal) ? 'block' : 'none'; ?>;">
        <div class="w3-modal-content w3-animate-zoom w3-round-xlarge" style="width: 30%;">
            <header class="w3-container">
                <span onclick="document.getElementById('resultModal').style.display='none'; document.getElementById('searchModal').style.display='block';" class="w3-button w3-display-topright">&times;</span>
            </header>
            <?php if ($student_found) : ?>
                <div class="w3-container w3-center w3-margin-top">
                    <img src="<?php echo htmlspecialchars($student_found['PROFILE_PIC'] ? $student_found['PROFILE_PIC'] : 'images/default_pic.png'); ?>" alt="User Profile" style="width: 100px; height: 100px; border-radius: 50%;">
                </div>
                <div class="w3-container" style="margin: 0 10%;">
                    <p><i class="fa-solid fa-id-card"></i> <strong>IDNO:</strong> <?php echo htmlspecialchars($student_found['IDNO']); ?></p>
                    <p><i class="fa-solid fa-user"></i> <strong>Name:</strong> <?php echo htmlspecialchars($student_found['FIRSTNAME'] . ' ' . $student_found['MIDDLENAME'] . ' ' . $student_found['LASTNAME']); ?></p>
                    <p><i class="fa-solid fa-book"></i> <strong>Course:</strong> <?php echo htmlspecialchars($student_found['COURSE']); ?></p>
                    <p><i class="fa-solid fa-graduation-cap"></i> <strong>Level:</strong> <?php echo htmlspecialchars($student_found['YEAR_LEVEL']); ?></p>
                    <p><i class="fa-solid fa-clock"></i> <strong>Remaining Session:</strong> <?php echo htmlspecialchars($student_found['SESSION_COUNT']); ?></p>
                </div>
            <?php endif; ?>
            <?php if ($show_sitin_form) : ?>
                    <?php if($sitin_error): ?>
                        <p class="w3-text-red w3-center"><?php echo htmlspecialchars($sitin_error); ?></p>
                    <?php endif; ?>
                    <?php if($sitin_success): ?>
                        <p class="w3-text-green w3-center"><?php echo htmlspecialchars($sitin_success); ?></p>
                    <?php endif; ?>
                    <div class="w3-container" style="margin: 0 10%;">
                        <form method="POST">
                            <input type="hidden" name="add_sitin_user_id" value="<?php echo $student_found['IDNO']; ?>">
                            <label for="purpose">Purpose:</label><br>
                            <select id="purpose" name="purpose" class="w3-input w3-border" required>
                                <option value="" disabled selected hidden>Select Purpose</option>
                                <option value="PHP Programming">PHP Programming</option>
                                <option value="C Programming">C Programming</option>
                                <option value="C++ Programming">C++ Programming</option>
                                <option value="Java Programming">Java Programming</option>
                                <option value=".Net Programming">.Net Programming</option>
                                <option value="Others">Others</option>
                            </select><br>
                            <label for="laboratory">Laboratory:</label><br>
                            <select id="laboratory" name="laboratory" class="w3-input w3-border" required>
                                <option value="" disabled selected hidden>Select Laboratory</option>
                                <option value="524">524</option>
                                <option value="526">526</option>
                                <option value="528">528</option>
                                <option value="530">530</option>
                                <option value="542">542</option>
                                <option value="544">544</option>
                            </select><br>
                            <button type="submit" class="w3-button w3-purple w3-margin w3-padding w3-round-large w3-right">Add Sit-in</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php if ($search_error && $show_result_modal) : ?>
                <p class="w3-text-red w3-center"><?php echo htmlspecialchars($search_error); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">&#9776;</button>
            <h1 style="margin-left: 10px; color: #ffff;">Admin Dashboard</h1>
        </div>
        <div class="w3-row-padding" style="margin: 5% 10px;">
            <div class="w3-col m6">
                <!---Announcement---->
                <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-margin-bottom w3-animate-top" style="width: 100%;">
                    <div class="w3-purple w3-container w3-round-xlarge" style="display: flex; align-items: center;">
                        <i class="fa-solid fa-bullhorn"></i>
                        <h3 style="margin-left: 10px; color: #ffff;">Announcement</h3>
                    </div>
                    <?php if (isset($announcement_to_edit)) : ?>
                        <form method="POST" class="w3-margin-top">
                            <input type="hidden" name="update_announcement_id" value="<?php echo htmlspecialchars($announcement_to_edit['ID']); ?>">
                            <textarea name="updated_announcement_message" class="w3-input w3-border" placeholder="Edit your announcement here..." rows="4"><?php echo htmlspecialchars($announcement_to_edit['MESSAGE']); ?></textarea>
                            <button type="submit" class="w3-button w3-purple w3-margin-top">Update Announcement</button>
                            <a href="admin.php" class="w3-button w3-red w3-margin-top">Cancel</a>
                        </form>
                    <?php else : ?>
                        <form method="POST" class="w3-margin-top">
                            <textarea name="announcement_message" class="w3-input w3-border" placeholder="Type your announcement here..." rows="4" required></textarea>
                            <button type="submit" class="w3-button w3-purple w3-margin-top">Post Announcement</button>
                        </form>
                    <?php endif; ?>
                    <div id="announcements-list" class="w3-margin-top">
                        <?php if (count($announcements) > 0) : ?>
                            <?php foreach ($announcements as $announcement) : ?>
                                <div class="w3-panel w3-light-gray w3-leftbar w3-border-purple">
                                    <p><?php echo htmlspecialchars($announcement['MESSAGE']); ?></p>
                                    <small>Posted on: <?php echo date("Y-m-d H:i:s", strtotime($announcement['TIMESTAMP'])); ?></small>
                                    <div class="announcement-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="delete_announcement" value="<?php echo $announcement['ID']; ?>">
                                            <button type="submit" class="w3-button w3-red w3-small">Delete</button>
                                        </form>
                                        <a href="admin.php?edit_announcement=<?php echo $announcement['ID']; ?>" class="w3-button w3-blue w3-small">Update</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p style="font-size: 18px; color: #333; font-family: Arial, sans-serif; margin-top: 20px;">No announcement for today.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="w3-col m6">
                <!---Statistics---->
                <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 100%; height: 500px;">
                    <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-purple">
                        <h3><i class="fa-solid fa-chart-simple w3-padding"></i>Statistics</h3>
                    </div>
                    <div class="w3-container" style="width: 100%;">
                        <canvas id="courseChart"></canvas>
                     </div>
                    <br>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                <?php if ($show_result_modal): ?>
                
                    document.getElementById('resultModal').style.display = 'block';

                    <?php if (isset($close_modal_on_success) && $close_modal_on_success): ?>
                        // Close modal after successful submission
                        setTimeout(function() {
                            document.getElementById('resultModal').style.display = 'none';
                        }, 1000); // Close after 1 second
                    <?php endif; ?>
                <?php endif; ?>
                });
                //close the modal
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if ($show_result_modal): ?>
                        document.getElementById('searchModal').style.display = 'none';
                        document.getElementById('resultModal').style.display = 'block';
                    <?php endif; ?>
                });

                
                function w3_open() {
                    document.getElementById("mySidebar").style.display = "block";
                }

                function w3_close() {
                    document.getElementById("mySidebar").style.display = "none";
                }

                //pie chart
                var ctx = document.getElementById('courseChart').getContext('2d');
                var myChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: <?php echo $chart_labels; ?>,
                        datasets: [{
                            data: <?php echo $chart_data; ?>,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true, // Ensure chart is responsive
                        maintainAspectRatio: true,
                        layout: {
                            padding: 10 // Adjust padding around the chart
                        }
                     }
                });

            </script>
</body>

</html>
