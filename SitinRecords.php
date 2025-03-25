<?php
define('INCLUDED_IN_MAIN_FILE', true); // Define a constant to check if the file is included
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

// Initialize search term
$search_term = "";

// Base SQL query
$sql_sitins = "SELECT sr.ID, u.IDNO, CONCAT(u.FIRSTNAME, ' ', u.LASTNAME) AS Name, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN, sr.TIME_OUT
               FROM sitin_records sr
               JOIN user u ON sr.IDNO = u.IDNO";

// Check if search term is submitted
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    // Search by IDNO or name (FIRSTNAME, MIDDLENAME, LASTNAME)
    $sql_sitins .= " WHERE (u.IDNO LIKE '%$search_term%' OR u.FIRSTNAME LIKE '%$search_term%' OR u.LASTNAME LIKE '%$search_term%')";
} else {
    // Default: show today's records
    $sql_sitins .= " WHERE DATE(sr.TIME_IN) = CURDATE() OR (sr.TIME_OUT IS NOT NULL AND DATE(sr.TIME_OUT) = CURDATE())";
}

// Add order by clause
$sql_sitins .= " ORDER BY sr.TIME_IN DESC";

// Prepare and execute the query
$stmt_sitins = $conn->prepare($sql_sitins);
$stmt_sitins->execute();
$result_sitins = $stmt_sitins->get_result();

$sitin_records = [];
if ($result_sitins->num_rows > 0) {
    while ($row = $result_sitins->fetch_assoc()) {
        $sitin_records[] = $row;
    }
}
$stmt_sitins->close();

// Get purpose statistics
$sql_purpose_stats = "SELECT PURPOSE, COUNT(*) as count FROM sitin_records GROUP BY PURPOSE ORDER BY count DESC";
$result_purpose_stats = $conn->query($sql_purpose_stats);
$purpose_labels = [];
$purpose_data = [];

if ($result_purpose_stats->num_rows > 0) {
    while ($row = $result_purpose_stats->fetch_assoc()) {
        $purpose_labels[] = $row['PURPOSE'];
        $purpose_data[] = $row['count'];
    }
}

// Get laboratory statistics
$sql_lab_stats = "SELECT LABORATORY, COUNT(*) as count FROM sitin_records GROUP BY LABORATORY ORDER BY count DESC";
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
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="side_nav.css">
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
            $profile_pic = isset($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : 'images/default_pic.png';
            ?>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px;">
        </div>
        <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
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
            <h2 class="w3-margin-bottom">Current Sit-in Records</h2>
            <table class="sitin-table">
                <thead id="sitinTable">
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
                <tbody id="sitinTableBody">
                    <?php if (count($sitin_records) > 0) : ?>
                        <?php foreach ($sitin_records as $record) : ?>
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
                            <td colspan="8">No sit-in records found for today.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function w3_open() {
            document.getElementById("mySidebar").style.display = "block";
        }

        function w3_close() {
            document.getElementById("mySidebar").style.display = "none";
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
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
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
