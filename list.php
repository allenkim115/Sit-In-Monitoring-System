<?php
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

// Fetch all users from the database with filtering and search
$sql_users = "SELECT IDNO, FIRSTNAME, MIDDLENAME, LASTNAME, COURSE, YEAR_LEVEL, PROFILE_PIC FROM user WHERE 1=1"; // Base query

// Filtering variables initialization
$filter_course = "";
$filter_year = "";
$search_term = ""; // Initialize search term

// Check if filters are submitted
if (isset($_GET['filter_course']) && !empty($_GET['filter_course'])) {
    $filter_course = mysqli_real_escape_string($conn, $_GET['filter_course']);
    $sql_users .= " AND COURSE = '$filter_course'";
}

if (isset($_GET['filter_year']) && !empty($_GET['filter_year'])) {
    $filter_year = mysqli_real_escape_string($conn, $_GET['filter_year']);
    $sql_users .= " AND YEAR_LEVEL = '$filter_year'";
}

// Check if search term is submitted
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    // Search by IDNO or name (FIRSTNAME, MIDDLENAME, LASTNAME)
    $sql_users .= " AND (IDNO LIKE '%$search_term%' OR FIRSTNAME LIKE '%$search_term%' OR MIDDLENAME LIKE '%$search_term%' OR LASTNAME LIKE '%$search_term%')";
}

$result_users = $conn->query($sql_users);

$users = [];
if ($result_users->num_rows > 0) {
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
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
    <title>Students</title>
    <style>
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-table th,
        .user-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .user-table th {
            background-color: #f0fff0;
        }

        /* Adjusted CSS for Profile Pictures */
        .user-table img {
            width: 90px;
            /* Reduced width */
            height: 90px;
            /* Reduced height */
            border-radius: 50%;
            object-fit: cover;
        }

        /* Add some space to the profile picture column*/
        .user-table td:first-child {
            width: 50px;
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
        <a href="list.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
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
                    <?php if ($search_error && !$show_result_modal) : ?>
                        <p class="w3-text-red"><?php echo htmlspecialchars($search_error); ?></p>
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
            <h1 style="margin-left: 10px; color: #ffff;">Students</h1>
        </div>

        <div class="w3-container" style="margin: 5% 10px;">
            <h2 class="w3-margin-bottom">Student List</h2>
            <!-- Search Bar -->
            <div class="w3-row w3-margin-bottom">
                <div class="w3-col m6">
                    <form method="GET" class="w3-bar">
                        <input type="text" name="search" class="w3-input w3-border w3-round" style="width: auto; display: inline-block;" placeholder="IDNO/Name" value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="w3-button w3-purple w3-round-large w3-small">Search</button>
                        <a href="list.php" class="w3-button w3-gray w3-round-large w3-small">Clear</a>
                    </form>
                </div>
                <!--Filter-->
                <div class="w3-col m6 w3-right-align">
                    <form method="GET" class="w3-bar">
                        <select name="filter_course" class="w3-select w3-border w3-round" style="width: auto; display: inline-block; margin-right: 10px;">
                            <option value="" <?php echo empty($filter_course) ? 'selected' : ''; ?>>All Courses</option>
                            <option value="BSIT" <?php echo ($filter_course == 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                            <option value="BSCS" <?php echo ($filter_course == 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
                            <option value="BSCpE" <?php echo ($filter_course == 'BSCpE') ? 'selected' : ''; ?>>BSCpE</option>
                        </select>
                        <select name="filter_year" class="w3-select w3-border w3-round" style="width: auto; display: inline-block; margin-right: 10px;">
                            <option value="" <?php echo empty($filter_year) ? 'selected' : ''; ?>>All Year Levels</option>
                            <option value="1" <?php echo ($filter_year == '1') ? 'selected' : ''; ?>>1</option>
                            <option value="2" <?php echo ($filter_year == '2') ? 'selected' : ''; ?>>2</option>
                            <option value="3" <?php echo ($filter_year == '3') ? 'selected' : ''; ?>>3</option>
                            <option value="4" <?php echo ($filter_year == '4') ? 'selected' : ''; ?>>4</option>
                        </select>
                        <button type="submit" class="w3-button w3-purple w3-round-large w3-small">Apply Filters</button>
                        <a href="list.php" class="w3-button w3-gray w3-round-large w3-small">Clear Filters</a>
                    </form>
                </div>
            </div>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Profile Picture</th>
                        <th>ID Number</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0) : ?>
                        <?php foreach ($users as $user) : ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($user['PROFILE_PIC'] ? $user['PROFILE_PIC'] : 'images/default_pic.png'); ?>" alt="User Profile">
                                </td>
                                <td><?php echo htmlspecialchars($user['IDNO']); ?></td>
                                <td><?php echo htmlspecialchars($user['FIRSTNAME'] . ' ' . $user['MIDDLENAME'] . ' ' . $user['LASTNAME']); ?></td>
                                <td><?php echo htmlspecialchars($user['COURSE']); ?></td>
                                <td><?php echo htmlspecialchars($user['YEAR_LEVEL']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($show_result_modal) : ?>
                document.getElementById('searchModal').style.display = 'none';
                document.getElementById('resultModal').style.display = 'block';
            <?php endif; ?>
        });

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

        function w3_open() {
            document.getElementById("mySidebar").style.display = "block";
        }

        function w3_close() {
            document.getElementById("mySidebar").style.display = "none";
        }
    </script>
</body>
</html>
