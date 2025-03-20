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

// Initialize $stmt_deduct_session to null before the try block
$stmt_deduct_session = null;

// Handle Time Out action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['timeout_id'])) {
    $timeout_id = $_POST['timeout_id'];
    $sitin_record_id = $_POST['sitin_record_id'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Update the sit-in record to set the time out
        $sql_timeout = "UPDATE sitin_records SET TIME_OUT = NOW() WHERE ID = ?";
        $stmt_timeout = $conn->prepare($sql_timeout);
        $stmt_timeout->bind_param("i", $sitin_record_id);
        $stmt_timeout->execute();

        // Deduct the user's session
        $sql_deduct_session = "UPDATE user SET SESSION_COUNT = SESSION_COUNT - 1 WHERE IDNO = ?";
        $stmt_deduct_session = $conn->prepare($sql_deduct_session);
        $stmt_deduct_session->bind_param("s", $timeout_id);
        $stmt_deduct_session->execute();

        // Commit the transaction
        $conn->commit();

        // Set success message in session
        $_SESSION['timeout_success'] = "Time out successful!";

        // Redirect to currentSitin.php
        header("Location: currentSitin.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    } 

    $stmt_timeout->close();
}

// Fetch current sit-in records (where time_out is NULL)
$sql_sitins = "SELECT sr.ID, u.IDNO, u.FIRSTNAME, u.LASTNAME, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN
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
                $search_error = null;
                
                $close_modal_on_success = true;
                
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

// Display success message if set
if (isset($_SESSION['timeout_success'])) {
    $timeout_success = $_SESSION['timeout_success'];
    unset($_SESSION['timeout_success']); // Clear the message after displaying it
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
    <title>Current Sit-ins</title>
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
            <h1 style="margin-left: 10px; color: #ffff;">Current Sit-ins</h1>
        </div>
        <div class="w3-container" style="margin: 5% 10px;">
            <div class="w3-container" style="margin: 0 10px; display: flex; justify-content: flex-end;">
                <a href="SitinRecords.php" class="w3-button w3-purple w3-round-large w3-margin-bottom">View Sit-in Records</a>
            </div>
            <h2 class="w3-margin-bottom">Current Sit-in</h2>            
            <table class="sitin-table">
                <thead>
                    <tr>
                        <th>IDNO</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Purpose</th>
                        <th>Laboratory</th>
                        <th>Time In</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <?php
                //Modified SQL Query to fetch records
                $sql_sitins = "SELECT sr.ID, u.IDNO, u.FIRSTNAME, u.LASTNAME, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN
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
                ?>
                <tbody>
                <?php if (isset($timeout_success)) : ?>                    
                    <div id="timeoutSuccess" class="w3-panel w3-green w3-display-container">
                        <p><?php echo htmlspecialchars($timeout_success); ?></p>
                    </div>
                    <script>
                        setTimeout(function() {
                            var timeoutSuccess = document.getElementById('timeoutSuccess');
                            if (timeoutSuccess) {
                                timeoutSuccess.style.display = 'none';
                            }
                        }, 2000);
                    </script>
                <?php endif; ?>
                <?php if (count($sitin_records) > 0) : ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7">No current sit-ins found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php foreach ($sitin_records as $record) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['IDNO']); ?></td>
                                <td><?php echo htmlspecialchars($record['FIRSTNAME']); ?></td>
                                <td><?php echo htmlspecialchars($record['LASTNAME']); ?></td>
                                <td><?php echo htmlspecialchars($record['PURPOSE']); ?></td>
                                <td><?php echo htmlspecialchars($record['LABORATORY']); ?></td>
                                <td><?php echo date("Y-m-d g:i a", strtotime($record['TIME_IN'])); ?></td>
                                <td style="text-align: center;">
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to time out this user?');">
                                        <input type="hidden" name="timeout_id" value="<?php echo htmlspecialchars($record['IDNO']); ?>">
                                        <input type="hidden" name="sitin_record_id" value="<?php echo htmlspecialchars($record['ID']); ?>">
                                        <button type="submit" class="w3-button w3-red w3-round-large w3-small">Time Out</button>
                                    </form>                                    
                                </td>
                            </tr>
                        <?php endforeach; ?>
            </table>
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

        function w3_open() {
            document.getElementById("mySidebar").style.display = "block";
        }

        function w3_close() {
            document.getElementById("mySidebar").style.display = "none";
        }
    </script>
</body>

</html>
