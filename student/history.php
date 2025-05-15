<?php
include '../includes/connect.php';

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Retrieve user data from the session
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

//get the session count from database
$sql_session = "SELECT SESSION_COUNT FROM user WHERE IDNO = ?";
$stmt_session = $conn->prepare($sql_session);
$stmt_session->bind_param("s", $user['IDNO']);
$stmt_session->execute();
$result_session = $stmt_session->get_result()->fetch_assoc();

// Number of records per page
$records_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;

// Get current page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = " WHERE s.IDNO = " . $user['IDNO'] . " AND s.TIME_OUT IS NOT NULL"; // Base condition to show only current user's completed records
if (!empty($search)) {
    $search_condition .= " AND (s.PURPOSE LIKE '%$search%' OR s.LABORATORY LIKE '%$search%')";
}

// Get total number of records
$total_records_query = "SELECT COUNT(*) as count FROM sitin_records s" . $search_condition;
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $records_per_page);

// Get records for current page
$sql = "SELECT s.*, 
        CONCAT(u.FIRSTNAME, ' ', COALESCE(u.MIDDLENAME, ''), ' ', u.LASTNAME) as NAME,
        f.ID as FEEDBACK_ID,
        f.RATING as FEEDBACK_RATING 
        FROM sitin_records s 
        LEFT JOIN user u ON s.IDNO = u.IDNO
        LEFT JOIN feedback f ON s.ID = f.SITIN_RECORD_ID AND f.STUDENT_ID = s.IDNO" . 
        $search_condition . 
        " ORDER BY s.TIME_IN DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/w3.css">
    <link rel="stylesheet" href="../css/side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <title>History Information</title>
    <style>
        .feedback-btn {
            background-color: #4CAF50;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .feedback-btn:hover {
            background-color: #45a049;
        }
        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .rating input {
            display: none;
        }

        .rating label {
            font-size: 30px;
            color: #ddd;
            cursor: pointer;
            padding: 5px;
        }

        .rating label:hover,
        .rating label:hover ~ label,
        .rating input:checked ~ label {
            color: #ffd700;
        }

        .rating label:hover,
        .rating label:hover ~ label {
            color: #ffd700;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
        <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <div class="profile w3-center w3-margin w3-padding">
            <?php
            $profile_pic = isset($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : '../images/default_pic.png';
            ?>
            <img src="<?php echo (strpos($user['PROFILE_PIC'], 'uploads/') === 0 ? '../' : '') . htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <a href="dashboard.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i>Home</a>
        <a href="#" onclick="document.getElementById('profile').style.display='block'" class="w3-bar-item w3-button"><i class="fa-regular fa-user w3-padding"></i>Profile</a>
        <a href="profile.php" class="w3-bar-item w3-button"><i class="fa-solid fa-edit w3-padding"></i>Edit Profile</a>
        <a href="history.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-clock-rotate-left w3-padding"></i>History</a>
        <a href="view_lab_schedules.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedules</span></a>
        <a href="view_lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="make_reservation.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="../logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i>Log Out</a>
    </div>

    <!-- Profile Modal -->
    <div id="profile" class="w3-modal" style="z-index: 1000;">
        <div class="w3-modal-content w3-animate-zoom w3-round-xlarge" style="width: 30%;">
            <header class="w3-container"> 
                <span onclick="document.getElementById('profile').style.display='none'" 
                      class="w3-button w3-display-topright">&times;</span>
                <h2 style="text-transform:uppercase;">Profile</h2>
            </header>
            <div class="display_photo w3-container w3-center">
                <img src="<?php echo (strpos($user['PROFILE_PIC'], 'uploads/') === 0 ? '../' : '') . htmlspecialchars($user['PROFILE_PIC']); ?>" alt="profile_pic" style="width: 120px; height:120px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
            </div>
            <hr style="margin: 1rem 10%; border-width: 2px;">
            <div class="w3-container" style="margin: 0 10%;">
                <p><i class="fa-solid fa-id-card"></i> <strong>IDNO:</strong> <?php echo htmlspecialchars($user['IDNO']); ?></p>
                <p><i class="fa-solid fa-user"></i> <strong>Name:</strong> <?php echo htmlspecialchars($user['FIRSTNAME'] . ' ' . $user['MIDDLENAME'] . ' ' . $user['LASTNAME']); ?></p>
                <p><i class="fa-solid fa-book"></i> <strong>Course:</strong> <?php echo htmlspecialchars($user['COURSE']); ?></p>
                <p><i class="fa-solid fa-graduation-cap"></i> <strong>Level:</strong> <?php echo htmlspecialchars($user['YEAR_LEVEL']); ?></p>
                <p><i class="fa-solid fa-stopwatch"></i> <strong>Session:</strong> <?php echo htmlspecialchars($result_session['SESSION_COUNT']); ?></p>
            </div>
            <footer class="w3-container w3-padding" style="margin: 0 30%;">
                <button class="w3-btn w3-purple w3-round-xlarge" onclick="window.location.href='profile.php'">Edit Profile</button>
            </footer>
        </div>
    </div>

    <!-- Main content -->
    <div style="margin-left:20%">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" onclick="w3_open()" style="color: #ffff;">&#9776;</button>
            <h1 style="margin-left: 10px; color: #ffff;">History Information</h1>
        </div>

        <!-- Controls -->
        <div class="w3-container w3-margin">
            <div class="w3-row">
                <div class="w3-col m6">
                    <select class="w3-select w3-border" style="width: 100px;" onchange="changeEntries(this.value)">
                        <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $records_per_page == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $records_per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                    <span>entries per page</span>
                </div>
                <div class="w3-col m6 w3-right-align">
                    <input type="text" class="w3-input w3-border" placeholder="Search..." 
                           style="width: 200px; display: inline-block;" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           onkeyup="if(event.keyCode === 13) search(this.value)">
                </div>
            </div>

            <!-- Table -->
            <div class="w3-responsive">
                <table class="w3-table-all w3-margin-top">
                    <thead>
                        <tr class="w3-purple">
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Sit Purpose</th>
                            <th>Laboratory</th>
                            <th>Login</th>
                            <th>Logout</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['IDNO']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NAME']); ?></td>
                                    <td><?php echo htmlspecialchars($row['PURPOSE']); ?></td>
                                    <td><?php echo htmlspecialchars($row['LABORATORY']); ?></td>
                                    <td><?php echo htmlspecialchars(date('h:i:sa', strtotime($row['TIME_IN']))); ?></td>
                                    <td><?php echo $row['TIME_OUT'] ? htmlspecialchars(date('h:i:sa', strtotime($row['TIME_OUT']))) : ''; ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['TIME_IN']))); ?></td>
                                    <td>
                                        <?php if ($row['FEEDBACK_ID']): ?>
                                            <button class="w3-button w3-disabled w3-light-grey" disabled>
                                                <i class="fa-solid fa-check"></i> Feedback Submitted
                                            </button>
                                        <?php else: ?>
                                            <button class="feedback-btn" onclick="provideFeedback('<?php echo $row['ID']; ?>')">
                                                <i class="fa-solid fa-comment"></i> Provide Feedback
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="w3-center">No records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Feedback Modal -->
            <div id="feedbackModal" class="w3-modal">
                <div class="w3-modal-content w3-card-4 w3-animate-zoom" style="max-width:600px">
                    <header class="w3-container w3-purple">
                        <span onclick="document.getElementById('feedbackModal').style.display='none'" 
                            class="w3-button w3-display-topright">&times;</span>
                        <h2>Provide Feedback</h2>
                    </header>

                    <form class="w3-container" id="feedbackForm">
                        <input type="hidden" id="recordId" name="recordId">
                        
                        <div class="w3-section">
                            <label>Rating</label>
                            <div class="w3-row w3-padding-small">
                                <div class="rating">
                                    <?php for($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" />
                                        <label for="star<?php echo $i; ?>">☆</label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>

                        <div class="w3-section">
                            <label>Comment</label>
                            <textarea class="w3-input w3-border" id="comment" name="comment" rows="4" required></textarea>
                        </div>

                        <div class="w3-section">
                            <button type="submit" class="w3-button w3-purple">Submit Feedback</button>
                            <button type="button" onclick="document.getElementById('feedbackModal').style.display='none'" 
                                    class="w3-button w3-red w3-right">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Pagination -->
            <div class="w3-center w3-margin-top">
                <div class="w3-bar">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&entries=<?php echo $records_per_page; ?>&search=<?php echo urlencode($search); ?>" 
                           class="w3-button">&laquo;</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?>&entries=<?php echo $records_per_page; ?>&search=<?php echo urlencode($search); ?>" 
                           class="w3-button <?php echo $i == $page ? 'w3-purple' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $total_pages; ?>&entries=<?php echo $records_per_page; ?>&search=<?php echo urlencode($search); ?>" 
                           class="w3-button">&raquo;</a>
                    <?php endif; ?>
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

        function changeEntries(value) {
            window.location.href = '?entries=' + value + '&search=<?php echo urlencode($search); ?>';
        }

        function search(value) {
            window.location.href = '?entries=<?php echo $records_per_page; ?>&search=' + encodeURIComponent(value);
        }

        function provideFeedback(recordId) {
            // Check if feedback already exists (additional security)
            fetch('check_feedback.php?record_id=' + recordId)
                .then(response => response.json())
                .then(data => {
                    if (data.has_feedback) {
                        alert('You have already submitted feedback for this sit-in session.');
                        return;
                    }
                    // Reset form
                    document.getElementById('feedbackForm').reset();
                    document.getElementById('recordId').value = recordId;
                    document.getElementById('feedbackModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error checking feedback status');
                });
        }

        // Add feedback form submission handler
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const recordId = document.getElementById('recordId').value;
            const rating = document.querySelector('input[name="rating"]:checked');
            const comment = document.getElementById('comment').value;

            if (!rating) {
                alert('Please select a rating');
                return;
            }

            const data = {
                recordId: recordId,
                rating: parseInt(rating.value),
                comment: comment
            };

            fetch('submit_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Find the button for this record and update it
                    const feedbackButton = document.querySelector(`button[onclick="provideFeedback('${recordId}')"]`);
                    if (feedbackButton) {
                        const newButton = document.createElement('button');
                        newButton.className = 'w3-button w3-disabled w3-light-grey';
                        newButton.disabled = true;
                        newButton.innerHTML = '<i class="fa-solid fa-check"></i> Feedback Submitted';
                        feedbackButton.parentNode.replaceChild(newButton, feedbackButton);
                    }
                    
                    // Close the modal and show success message
                    document.getElementById('feedbackModal').style.display = 'none';
                    alert('Thank you for your feedback!');
                } else {
                    alert(data.message || 'Error submitting feedback');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting feedback');
            });
        });
    </script>
</body>
</html> 