<?php
define('INCLUDED_IN_MAIN_FILE', true);
include 'connect.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle delete user action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user_id'])) {
    $delete_user_id = $_POST['delete_user_id'];
    
    // First delete related records from sitin_records table
    $sql_delete_sitins = "DELETE FROM sitin_records WHERE IDNO = ?";
    $stmt_delete_sitins = $conn->prepare($sql_delete_sitins);
    $stmt_delete_sitins->bind_param("s", $delete_user_id);
    $stmt_delete_sitins->execute();
    
    // Then delete the user
    $sql_delete_user = "DELETE FROM user WHERE IDNO = ?";
    $stmt_delete_user = $conn->prepare($sql_delete_user);
    $stmt_delete_user->bind_param("s", $delete_user_id);
    
    if ($stmt_delete_user->execute()) {
        $_SESSION['delete_success'] = "User successfully deleted";
    } else {
        $_SESSION['delete_error'] = "Error deleting user";
    }
    
    $stmt_delete_sitins->close();
    $stmt_delete_user->close();
    header("Location: list.php");
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

// Handle session reset action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_session_id'])) {
    $reset_session_id = $_POST['reset_session_id'];
    $default_session_count = 30; // Define the default session count

    $sql_reset_session = "UPDATE user SET SESSION_COUNT = ? WHERE IDNO = ?";
    $stmt_reset_session = $conn->prepare($sql_reset_session);
    $stmt_reset_session->bind_param("is", $default_session_count, $reset_session_id);

    if ($stmt_reset_session->execute()) {
        $_SESSION['reset_success'] = "Session count reset successfully for IDNO: " . $reset_session_id;
    } else {
        $_SESSION['reset_error'] = "Error resetting session count for IDNO: " . $reset_session_id;
    }

    $stmt_reset_session->close();
    header("Location: list.php"); // Redirect to refresh the page
    exit;
}

// Handle reset all sessions action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_all_sessions'])) {
    $default_session_count = 30; // Define the default session count

    $sql_reset_all = "UPDATE user SET SESSION_COUNT = ?";
    $stmt_reset_all = $conn->prepare($sql_reset_all);
    $stmt_reset_all->bind_param("i", $default_session_count);

    if ($stmt_reset_all->execute()) {
        $_SESSION['reset_success'] = "Session count reset successfully for all students";
    } else {
        $_SESSION['reset_error'] = "Error resetting session count for all students";
    }

    $stmt_reset_all->close();
    header("Location: list.php"); // Redirect to refresh the page
    exit;
}

// Fetch all users from the database with filtering and search
$sql_users = "SELECT IDNO, FIRSTNAME, MIDDLENAME, LASTNAME, COURSE, YEAR_LEVEL, PROFILE_PIC, SESSION_COUNT FROM user WHERE 1=1"; // Base query - **ADDED SESSION_COUNT**

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
        padding: 6px; /* Reduced padding from 8px to 6px */
        text-align: left;
        font-size: 0.9em; /* Added smaller font size */
    }
    .user-table td.action-buttons {
        width: 160px; /* Increased from 150px to accommodate larger buttons */
        white-space: nowrap;
    }
    .user-table td.action-buttons .w3-bar {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 2px; /* Added small gap between buttons */
    }
    .user-table td.action-buttons form {
        margin: 0;
    }
    .user-table .w3-button {
        padding: 5px 10px; /* Increased from 3px 6px */
        position: relative;
    }
    .user-table .w3-button i {
        font-size: 15px; /* Increased from 13px */
    }
    .user-table th {
        background-color: #f0fff0;
    }

    /* Adjusted CSS for Profile Pictures */
    .user-table img {
        width: 70px; /* Reduced from 90px */
        height: 70px; /* Reduced from 90px */
        border-radius: 50%;
        object-fit: cover;
    }

    /* Add some space to the profile picture column*/
    .user-table td:first-child {
        width: 40px; /* Reduced from 50px */
    }
    
    /* Make the table more compact overall */
    .user-table {
        line-height: 1.2;
    }
    /* Tooltip styling */
    .w3-button {
        position: relative;
    }
    .tooltip {
        visibility: hidden;
        width: auto;
        background-color: #555;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px 10px;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        font-size: 12px;
        pointer-events: none;
    }

    .tooltip::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #555 transparent transparent transparent;
    }

    .w3-button:hover .tooltip {
        visibility: visible;
    }

    /* Add hover effect for buttons */
    .w3-button.w3-blue:hover {
        background-color: #0b7dda !important;
    }
    .w3-button.w3-green:hover {
        background-color: #4CAF50 !important;
    }
    .w3-button.w3-red:hover {
        background-color: #f44336 !important;
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
        <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="lab_schedule.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedule</span></a>
        <a href="lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>
    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">&#9776;</button>
            <h1 style="margin-left: 10px; color: #ffff;">Students</h1>
        </div>

        <div class="w3-container" style="margin: 5% 10px;">
            <h2 class="w3-margin-bottom">Student List</h2>
             <?php if (isset($_SESSION['reset_success'])): ?>
                <div id="resetSuccess" class="w3-panel w3-green w3-display-container">
                    <p><?php echo htmlspecialchars($_SESSION['reset_success']); ?></p>
                </div>
                <script>
                    setTimeout(function() {
                        var resetSuccess = document.getElementById('resetSuccess');
                        if (resetSuccess) {
                            resetSuccess.style.display = 'none';
                        }
                    }, 2000);
                </script>
                <?php unset($_SESSION['reset_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['reset_error'])): ?>
                <div id="resetError" class="w3-panel w3-red w3-display-container">
                    <p><?php echo htmlspecialchars($_SESSION['reset_error']); ?></p>
                </div>
                <script>
                    setTimeout(function() {
                        var resetError = document.getElementById('resetError');
                        if (resetError) {
                            resetError.style.display = 'none';
                        }
                    }, 2000);
                </script>
                <?php unset($_SESSION['reset_error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['delete_success'])): ?>
                <div id="deleteSuccess" class="w3-panel w3-green w3-display-container">
                    <p><?php echo htmlspecialchars($_SESSION['delete_success']); ?></p>
                </div>
                <script>
                    setTimeout(function() {
                        var deleteSuccess = document.getElementById('deleteSuccess');
                        if (deleteSuccess) {
                            deleteSuccess.style.display = 'none';
                        }
                    }, 2000);
                </script>
                <?php unset($_SESSION['delete_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['delete_error'])): ?>
                <div id="deleteError" class="w3-panel w3-red w3-display-container">
                    <p><?php echo htmlspecialchars($_SESSION['delete_error']); ?></p>
                </div>
                <script>
                    setTimeout(function() {
                        var deleteError = document.getElementById('deleteError');
                        if (deleteError) {
                            deleteError.style.display = 'none';
                        }
                    }, 2000);
                </script>
                <?php unset($_SESSION['delete_error']); ?>
            <?php endif; ?>
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
            <div class="w3-margin-bottom">
                <form method="POST" onsubmit="return confirm('Are you sure you want to reset the session count for ALL students?');">
                    <button type="submit" name="reset_all_sessions" class="w3-button w3-red w3-round-large">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Reset All Sessions
                    </button>
                </form>
            </div>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Profile Picture</th>
                        <th>ID Number</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Sessions</th>
                        <th>Actions</th> 
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
                                <td><?php echo htmlspecialchars($user['SESSION_COUNT']); ?></td>
                                <td class="action-buttons">
                                    <div class="w3-bar">
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to reset the session count for this user?');">
                                            <input type="hidden" name="reset_session_id" value="<?php echo htmlspecialchars($user['IDNO']); ?>">
                                            <button type="submit" class="w3-button w3-blue w3-round-large w3-small" style="margin-right: 5px;" title="Reset Session">
                                                <i class="fa-solid fa-arrow-rotate-left"></i>
                                                <div class="tooltip">Reset Session</div>
                                            </button>
                                        </form>
                                        <button class="w3-button w3-green w3-round-large w3-small" style="margin-right: 5px;" onclick="window.location.href='edit_user.php?id=<?php echo htmlspecialchars($user['IDNO']); ?>'" title="Edit User">
                                            <i class="fa-solid fa-edit"></i>
                                            <div class="tooltip">Edit User</div>
                                        </button>
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                            <input type="hidden" name="delete_user_id" value="<?php echo htmlspecialchars($user['IDNO']); ?>">
                                            <button type="submit" class="w3-button w3-red w3-round-large w3-small" title="Delete User">
                                                <i class="fa-solid fa-trash"></i>
                                                <div class="tooltip">Delete User</div>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8">No users found.</td>
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

        // Add hover effect for tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.action-buttons .w3-button');
            
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    const tooltip = this.querySelector('.tooltip');
                    if (tooltip) {
                        tooltip.style.visibility = 'visible';
                    }
                });
                
                button.addEventListener('mouseleave', function() {
                    const tooltip = this.querySelector('.tooltip');
                    if (tooltip) {
                        tooltip.style.visibility = 'hidden';
                    }
                });
            });
        });
    </script>
</body>
</html>
