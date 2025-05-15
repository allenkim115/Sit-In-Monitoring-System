<?php
define('INCLUDED_IN_MAIN_FILE', true);
include '../includes/connect.php';
include '../includes/profanity_filter.php';

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Get feedback records
$sql = "SELECT f.*, s.LABORATORY, s.TIME_IN, s.TIME_OUT, s.PURPOSE,
        u.IDNO, CONCAT(u.FIRSTNAME, ' ', COALESCE(u.MIDDLENAME, ''), ' ', u.LASTNAME) as STUDENT_NAME
        FROM feedback f
        JOIN sitin_records s ON f.SITIN_RECORD_ID = s.ID
        JOIN user u ON s.IDNO = u.IDNO
        ORDER BY s.TIME_IN DESC";
$result = $conn->query($sql);

//get the profile picture from database
$username = $_SESSION['user']['USERNAME'];
$sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param("s", $username);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$user = $result_profile->fetch_assoc();

include 'search_modal.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Feedbacks</title>
    <link rel="stylesheet" href="../css/w3.css">
    <link rel="stylesheet" href="../css/side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <style>
        .star-rating {
            color: #ffd700;
        }
        .star-empty {
            color: #ccc;
        }
    </style>
</head>
<body>
    <div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
        <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <div class="profile w3-center w3-margin w3-padding">
            <?php
            $profile_pic = !empty($user['PROFILE_PIC']) ? '../uploads/' . $user['PROFILE_PIC'] : '../images/default_pic.png';
            ?>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button">
            <i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span>
        </a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="lab_schedule.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedule</span></a>
        <a href="lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="reservation_management.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="../logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>

    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" onclick="w3_open()" style="color: #ffff;">☰</button>
            <h1 style="margin-left: 10px; color: #ffff;">Sit-in Feedbacks</h1>
        </div>

        <div class="w3-row-padding" style="margin: 5% 10px;">
            <!-- Feedback Table -->
            <div class="w3-card w3-round w3-white">
                <div class="w3-container w3-padding">
                    <div class="w3-responsive">
                        <table class="w3-table-all">
                            <thead>
                                <tr class="w3-purple">
                                    <th>Sit-in No.</th>
                                    <th>IDNO</th>
                                    <th>Name</th>
                                    <th>Purpose</th>
                                    <th>Laboratory</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Date</th>
                                    <th>Rating</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): 
                                        $hasProfanity = ProfanityFilter::hasProfanity($row['COMMENT']);
                                        $rating = isset($row['RATING']) ? intval($row['RATING']) : 0;
                                    ?>
                                        <tr <?php echo $hasProfanity ? 'class="w3-pale-red"' : ''; ?>>
                                            <td><?php echo htmlspecialchars($row['SITIN_RECORD_ID']); ?></td>
                                            <td><?php echo htmlspecialchars($row['IDNO']); ?></td>
                                            <td><?php echo htmlspecialchars($row['STUDENT_NAME']); ?></td>
                                            <td><?php echo htmlspecialchars($row['PURPOSE']); ?></td>
                                            <td><?php echo htmlspecialchars($row['LABORATORY']); ?></td>
                                            <td><?php echo date('h:i a', strtotime($row['TIME_IN'])); ?></td>
                                            <td><?php echo $row['TIME_OUT'] ? date('h:i a', strtotime($row['TIME_OUT'])) : ''; ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['TIME_IN'])); ?></td>
                                            <td>
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fa-solid fa-star <?php echo $i <= $rating ? 'star-rating' : 'star-empty'; ?>"></i>
                                                <?php endfor; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['COMMENT']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="w3-center">No feedback records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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