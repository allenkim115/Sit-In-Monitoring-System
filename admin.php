<?php
define('INCLUDED_IN_MAIN_FILE', true); // Define a constant to check if the file is included
include 'connect.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Fetch statistics
$sql_total_students = "SELECT COUNT(*) as total_registered FROM user";
$result_total_students = $conn->query($sql_total_students);
$total_registered = $result_total_students->fetch_assoc()['total_registered'];

$sql_current_sitins = "SELECT COUNT(*) as current_sitins FROM sitin_records WHERE TIME_OUT IS NULL";
$result_current_sitins = $conn->query($sql_current_sitins);
$current_sitins = $result_current_sitins->fetch_assoc()['current_sitins'];

$sql_total_sitins = "SELECT COUNT(*) as total_sitins FROM sitin_records";
$result_total_sitins = $conn->query($sql_total_sitins);
$total_sitins = $result_total_sitins->fetch_assoc()['total_sitins'];

// Fetch sit-in purposes count
$sql_purpose_counts = "SELECT PURPOSE, COUNT(*) as count FROM sitin_records GROUP BY PURPOSE";
$result_purpose_counts = $conn->query($sql_purpose_counts);
$purpose_counts = [];
if ($result_purpose_counts->num_rows > 0) {
    while ($row = $result_purpose_counts->fetch_assoc()) {
        $purpose_counts[$row['PURPOSE']] = $row['count'];
    }
}

// Prepare data for the pie chart
$chart_labels = json_encode(array_keys($purpose_counts));
$chart_data = json_encode(array_values($purpose_counts));


//get the profile picture from database
$username = $_SESSION['user']['USERNAME']; // Assuming you store username in session
$sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param("s", $username);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$user = $result_profile->fetch_assoc();


// Handle announcement deletion - place this BEFORE the posting handler
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

// Handle announcement update (submit changes) - place this SECOND
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_announcement_id']) && isset($_POST['updated_announcement_message'])) {
    $announcement_id = $_POST['update_announcement_id'];
    $updated_title = isset($_POST['updated_announcement_title']) ? mysqli_real_escape_string($conn, $_POST['updated_announcement_title']) : '';
    $updated_message = mysqli_real_escape_string($conn, $_POST['updated_announcement_message']);

    $sql = "UPDATE announcement SET title = ?, message = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $updated_title, $updated_message, $announcement_id);
    if (!$stmt->execute()) {
        echo "Error updating announcement: " . $stmt->error;
    }
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Handle announcement posting - place this LAST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement_message'])) {
    // Make sure we're not handling delete or update requests
    if (!isset($_POST['delete_announcement']) && !isset($_POST['update_announcement_id'])) {
        $message = mysqli_real_escape_string($conn, $_POST['announcement_message']);
        $title = isset($_POST['announcement_title']) ? mysqli_real_escape_string($conn, $_POST['announcement_title']) : '';
        
        if (!empty($message)) {
            $sql = "INSERT INTO announcement (title, message) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $title, $message);
            if (!$stmt->execute()) {
                echo "Error: " . $stmt->error; // Consider better error handling
            }
            $stmt->close();
        }
        header("Location: admin.php");
        exit;
    }
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Home</title>
    <style>
        .announcement-actions {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }
        .statistics-card {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .stat-item {
            background-color: #E4EFE7;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            flex-grow: 1;
            margin: 0 5px;
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
        <a href="admin.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>
    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">☰</button>
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
                            <input type="text" name="updated_announcement_title" class="w3-input w3-border w3-margin-bottom" placeholder="Announcement Title" value="<?php echo htmlspecialchars($announcement_to_edit['TITLE'] ?? ''); ?>" required>
                            <textarea name="updated_announcement_message" class="w3-input w3-border" placeholder="Edit your announcement here..." rows="4"><?php echo htmlspecialchars($announcement_to_edit['MESSAGE']); ?></textarea>
                            <button type="submit" class="w3-button w3-purple w3-margin-top">Update Announcement</button>
                            <a href="admin.php" class="w3-button w3-red w3-margin-top">Cancel</a>
                        </form>
                    <?php else : ?>
                        <form method="POST" class="w3-margin-top">
                            <input type="text" name="announcement_title" class="w3-input w3-border w3-margin-bottom" placeholder="Announcement Title" required>
                            <textarea name="announcement_message" class="w3-input w3-border" placeholder="Type your announcement here..." rows="4" required></textarea>
                            <button type="submit" class="w3-button w3-purple w3-margin-top">Post Announcement</button>
                        </form>
                    <?php endif; ?>
                    <div id="announcements-list" class="w3-margin-top">
                        <?php if (count($announcements) > 0) : ?>
                            <?php foreach ($announcements as $announcement) : ?>
                                <div class="w3-panel w3-light-gray w3-leftbar w3-border-purple">
                                    <h4 class="w3-text-purple"><?php echo htmlspecialchars($announcement['TITLE'] ?? 'Announcement'); ?></h4>
                                    <p><?php echo htmlspecialchars($announcement['MESSAGE']); ?></p>
                                    <small>Posted on: <?php echo date("Y-m-d H:i:s", strtotime($announcement['TIMESTAMP'])); ?></small>
                                    <div class="announcement-actions">
                                        <form method="GET" action="admin.php" style="display: inline-block; margin-right: 5px;">
                                            <input type="hidden" name="edit_announcement" value="<?php echo $announcement['ID']; ?>">
                                            <button type="submit" class="w3-button w3-blue w3-small">Update</button>
                                        </form>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="delete_announcement" value="<?php echo $announcement['ID']; ?>">
                                            <button type="submit" class="w3-button w3-red w3-small">Delete</button>
                                        </form>
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
        <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 100%; height: 700px;">
            <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-purple">
                <h3><i class="fa-solid fa-chart-simple w3-padding"></i>Statistics</h3>
            </div>
            
            <!-- New Statistics Cards -->
            <div class="statistics-card" style="margin-top: 50px;">
                <div class="stat-item">
                    <h4>Registered Students</h4>
                    <p class="w3-large w3-text-purple"><?php echo $total_registered; ?></p>
                </div>
                <div class="stat-item">
                    <h4>Current Sit-ins</h4>
                    <p class="w3-large w3-text-purple"><?php echo $current_sitins; ?></p>
                </div>
                <div class="stat-item">
                    <h4>Total Sit-ins</h4>
                    <p class="w3-large w3-text-purple"><?php echo $total_sitins; ?></p>
                </div>
            </div>

            <div class="w3-container" style="width: 100%; height: 400px;">
                <canvas id="purposeChart"></canvas>
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

                      //pie chart for purposes
                var ctx = document.getElementById('purposeChart').getContext('2d');
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
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(153, 102, 255, 0.8)',
                                'rgba(255, 159, 64, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Sit-in Purposes Distribution'
                            }
                        }
                    }
                });
            </script>
</body>
</html>
