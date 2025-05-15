<?php
define('INCLUDED_IN_MAIN_FILE', true); // Define a constant to check if the file is included
include '../includes/connect.php';
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

// Initialize search term
$search_term = "";

// Base SQL query for direct sit-ins
$sql_direct_sitins = "SELECT sr.ID, u.IDNO, CONCAT(u.FIRSTNAME, ' ', u.LASTNAME) AS Name, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN, sr.TIME_OUT
               FROM sitin_records sr
               JOIN user u ON sr.IDNO = u.IDNO
               WHERE NOT EXISTS (
                   SELECT 1 FROM reservations r 
                   WHERE r.idno = sr.IDNO 
                   AND r.room_number = sr.LABORATORY 
                   AND DATE(r.reservation_date) = DATE(sr.TIME_IN)
                   AND r.status = 'approved'
               )";

// Base SQL query for reserved sit-ins
$sql_reserved_sitins = "SELECT sr.ID, u.IDNO, CONCAT(u.FIRSTNAME, ' ', u.LASTNAME) AS Name, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN, sr.TIME_OUT,
                        r.reservation_date, r.time_slot
                        FROM sitin_records sr
                        JOIN user u ON sr.IDNO = u.IDNO
                        JOIN reservations r ON r.idno = sr.IDNO 
                            AND r.room_number = sr.LABORATORY 
                            AND DATE(r.reservation_date) = DATE(sr.TIME_IN)
                            AND r.status = 'approved'";

// Check if search term is submitted
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    // Search by IDNO or name while maintaining today's date filter
    $search_condition = " AND (u.IDNO LIKE '%$search_term%' OR u.FIRSTNAME LIKE '%$search_term%' OR u.LASTNAME LIKE '%$search_term%')";
    $date_condition = " AND (DATE(sr.TIME_IN) = CURDATE() OR (sr.TIME_OUT IS NOT NULL AND DATE(sr.TIME_OUT) = CURDATE()))";
    
    $sql_direct_sitins .= $date_condition . $search_condition;
    $sql_reserved_sitins .= " WHERE " . substr($date_condition, 5) . $search_condition;
} else {
    // Default: show today's records
    $date_condition = " AND (DATE(sr.TIME_IN) = CURDATE() OR (sr.TIME_OUT IS NOT NULL AND DATE(sr.TIME_OUT) = CURDATE()))";
    $sql_direct_sitins .= $date_condition;
    $sql_reserved_sitins .= " WHERE " . substr($date_condition, 5);
}

// Add order by clause
$sql_direct_sitins .= " ORDER BY sr.TIME_IN DESC";
$sql_reserved_sitins .= " ORDER BY sr.TIME_IN DESC";

// Execute the queries
$result_direct_sitins = $conn->query($sql_direct_sitins);
$result_reserved_sitins = $conn->query($sql_reserved_sitins);

$direct_sitin_records = [];
$reserved_sitin_records = [];

if ($result_direct_sitins->num_rows > 0) {
    while ($row = $result_direct_sitins->fetch_assoc()) {
        $direct_sitin_records[] = $row;
    }
}

if ($result_reserved_sitins->num_rows > 0) {
    while ($row = $result_reserved_sitins->fetch_assoc()) {
        $reserved_sitin_records[] = $row;
    }
}

// Get purpose statistics for today only
$sql_purpose_stats = "SELECT PURPOSE, COUNT(*) as count FROM sitin_records 
                      WHERE DATE(TIME_IN) = CURDATE() OR (TIME_OUT IS NOT NULL AND DATE(TIME_OUT) = CURDATE())
                      GROUP BY PURPOSE ORDER BY count DESC";
$result_purpose_stats = $conn->query($sql_purpose_stats);
$purpose_labels = [];
$purpose_data = [];
$purpose_stats = [];
if ($result_purpose_stats->num_rows > 0) {
    while ($row = $result_purpose_stats->fetch_assoc()) {
        $purpose_stats[$row['PURPOSE']] = $row['count'];
    }
}
// Ensure all purposes are present, even if zero
$all_purposes = [
    "C Programming",
    "Java Programming",
    "C#",
    "PHP",
    "ASP.Net",
    "Database",
    "Digital Logic & Design",
    "Embedded System % IOT",
    "Python Programming",
    "Systems Integration & Architecture",
    "Computer Application",
    "Web Design & Development",
    "Project Management",
    "Other"
];
foreach ($all_purposes as $purpose) {
    if (!isset($purpose_stats[$purpose])) {
        $purpose_stats[$purpose] = 0;
    }
}
// Keep the order
$purpose_stats = array_replace(array_flip($all_purposes), $purpose_stats);
$purpose_labels = array_keys($purpose_stats);
$purpose_data = array_values($purpose_stats);

// Get laboratory statistics for today only
$sql_lab_stats = "SELECT LABORATORY, COUNT(*) as count FROM sitin_records 
                  WHERE DATE(TIME_IN) = CURDATE() OR (TIME_OUT IS NOT NULL AND DATE(TIME_OUT) = CURDATE())
                  GROUP BY LABORATORY ORDER BY count DESC";
$result_lab_stats = $conn->query($sql_lab_stats);
$lab_labels = [];
$lab_data = [];

if ($result_lab_stats->num_rows > 0) {
    while ($row = $result_lab_stats->fetch_assoc()) {
        $lab_labels[] = $row['LABORATORY'];
        $lab_data[] = $row['count'];
    }
}

include 'search_modal.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/w3.css">
    <link rel="stylesheet" href="../css/side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Sit-in Records</title>
    <style>
        .sitin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .sitin-table th,
        .sitin-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .sitin-table th {
            background-color: #f0fff0;
        }
        
        .chart-container {
            position: relative;
            height: 450px;
            width: 100%;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
        <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-arrow-left"></i></button>
        <div class="profile w3-center w3-margin w3-padding">
            <?php
            $profile_pic = isset($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : '../images/default_pic.png';
            ?>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="lab_schedule.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedule</span></a>
        <a href="lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="reservation_management.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="../logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>
    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">☰</button>
            <h1 style="margin-left: 10px; color: #ffff;">Sit-in Records</h1>
        </div>
        <div class="w3-container" style="margin: 5% 10px;">
            <!-- Charts Section -->
            <div class="w3-row-padding w3-margin-bottom">
                <div class="w3-half">
                    <div class="w3-card w3-round-large w3-white w3-padding">
                        <div class="chart-container">
                            <canvas id="purposeChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="w3-half">
                    <div class="w3-card w3-round-large w3-white w3-padding">
                        <div class="chart-container">
                            <canvas id="labChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
              <!-- Search Bar -->
              <div class="w3-row w3-margin-bottom">
                <div class="w3-col m6">
                    <form method="GET" class="w3-bar">
                        <input type="text" name="search" class="w3-input w3-border w3-round" style="width: auto; display: inline-block;" placeholder="IDNO/Name" value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="w3-button w3-purple w3-round-large w3-small">Search</button>
                        <a href="SitinRecords.php" class="w3-button w3-gray w3-round-large w3-small">Clear</a>
                    </form>
                </div>
                <div class="w3-container" style="margin: 0 10px; display: flex; justify-content: flex-end;">
                <a href="currentSitin.php" class="w3-button w3-purple w3-round-large w3-margin-bottom">View Current Sit-in</a>
            </div>
            </div> 
            <!-- Tab Navigation -->
            <div class="w3-bar w3-light-grey w3-round-large">
                <button class="w3-bar-item w3-button <?php echo (!isset($_GET['active_tab']) || $_GET['active_tab'] === 'Direct') ? 'w3-purple' : ''; ?>" onclick="openTab('Direct')">Direct Sit-ins</button>
                <button class="w3-bar-item w3-button <?php echo (isset($_GET['active_tab']) && $_GET['active_tab'] === 'Reserved') ? 'w3-purple' : ''; ?>" onclick="openTab('Reserved')">Reserved Sit-ins</button>
            </div>

            <!-- Direct Sit-ins Tab -->
            <div id="Direct" class="w3-container tab" style="display:<?php echo (!isset($_GET['active_tab']) || $_GET['active_tab'] === 'Direct') ? 'block' : 'none'; ?>">
                <h2 class="w3-margin-bottom">Direct Sit-ins</h2>            
                <table class="sitin-table">
                    <thead>
                        <tr>
                            <th>Sit-in No.</th>
                            <th>IDNO</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Laboratory</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($direct_sitin_records) > 0) : ?>
                        <?php foreach ($direct_sitin_records as $record) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['ID']); ?></td>
                                <td><?php echo htmlspecialchars($record['IDNO']); ?></td>
                                <td><?php echo htmlspecialchars($record['Name']); ?></td>
                                <td><?php echo htmlspecialchars($record['PURPOSE']); ?></td>
                                <td><?php echo htmlspecialchars($record['LABORATORY']); ?></td>
                                <td><?php echo date("g:i a", strtotime($record['TIME_IN'])); ?></td>
                                <td><?php echo $record['TIME_OUT'] ? date("g:i a", strtotime($record['TIME_OUT'])) : 'Still Sitting-in'; ?></td>
                                <td><?php echo date("Y-m-d", strtotime($record['TIME_IN'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8">No direct sit-in records found for today.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Reserved Sit-ins Tab -->
            <div id="Reserved" class="w3-container tab" style="display:<?php echo (isset($_GET['active_tab']) && $_GET['active_tab'] === 'Reserved') ? 'block' : 'none'; ?>">
                <h2 class="w3-margin-bottom">Reserved Sit-ins</h2>            
                <table class="sitin-table">
                    <thead>
                        <tr>
                            <th>Sit-in No.</th>
                            <th>IDNO</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Laboratory</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Reserved Time</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($reserved_sitin_records) > 0) : ?>
                        <?php foreach ($reserved_sitin_records as $record) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['ID']); ?></td>
                                <td><?php echo htmlspecialchars($record['IDNO']); ?></td>
                                <td><?php echo htmlspecialchars($record['Name']); ?></td>
                                <td><?php echo htmlspecialchars($record['PURPOSE']); ?></td>
                                <td><?php echo htmlspecialchars($record['LABORATORY']); ?></td>
                                <td><?php echo date("g:i a", strtotime($record['TIME_IN'])); ?></td>
                                <td><?php echo $record['TIME_OUT'] ? date("g:i a", strtotime($record['TIME_OUT'])) : 'Still Sitting-in'; ?></td>
                                <td><?php echo $record['time_slot']; ?></td>
                                <td><?php echo date("Y-m-d", strtotime($record['TIME_IN'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="9">No reserved sit-in records found for today.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
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

        function openTab(tabName) {
            var i;
            var x = document.getElementsByClassName("tab");
            for (i = 0; i < x.length; i++) {
                x[i].style.display = "none";
            }
            document.getElementById(tabName).style.display = "block";
            
            // Update tab button styles
            var buttons = document.getElementsByClassName("w3-bar-item");
            for (i = 0; i < buttons.length; i++) {
                buttons[i].className = buttons[i].className.replace(" w3-purple", "");
            }
            event.currentTarget.className += " w3-purple";

            // Update hidden input value
            document.getElementById('active_tab_input').value = tabName;
        }
        
        // Purpose Chart
        var purposeCtx = document.getElementById('purposeChart').getContext('2d');
        var purposeChart = new Chart(purposeCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($purpose_labels); ?>,
                datasets: [{
                    label: 'Sit-in Purposes',
                    data: <?php echo json_encode($purpose_data); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',   // 1
                        'rgba(54, 162, 235, 0.8)',   // 2
                        'rgba(255, 206, 86, 0.8)',   // 3
                        'rgba(75, 192, 192, 0.8)',   // 4
                        'rgba(153, 102, 255, 0.8)',  // 5
                        'rgba(255, 159, 64, 0.8)',   // 6
                        'rgba(255, 205, 210, 0.8)',  // 7
                        'rgba(100, 181, 246, 0.8)',  // 8
                        'rgba(174, 213, 129, 0.8)',  // 9
                        'rgba(255, 245, 157, 0.8)',  // 10
                        'rgba(129, 212, 250, 0.8)',  // 11
                        'rgba(244, 143, 177, 0.8)',  // 12
                        'rgba(255, 224, 178, 0.8)',  // 13
                        'rgba(197, 202, 233, 0.8)'   // 14
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255, 205, 210, 1)',
                        'rgba(100, 181, 246, 1)',
                        'rgba(174, 213, 129, 1)',
                        'rgba(255, 245, 157, 1)',
                        'rgba(129, 212, 250, 1)',
                        'rgba(244, 143, 177, 1)',
                        'rgba(255, 224, 178, 1)',
                        'rgba(197, 202, 233, 1)'
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
        
        // Laboratory Chart
        var labCtx = document.getElementById('labChart').getContext('2d');
        var labChart = new Chart(labCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($lab_labels); ?>,
                datasets: [{
                    label: 'Laboratory Usage',
                    data: <?php echo json_encode($lab_data); ?>,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
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
                        text: 'Laboratory Usage Distribution'
                    }
                }
            }
        });
    </script>
</body>

</html>
