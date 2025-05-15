<?php
// search_modal.php

// Ensure this file is only included, not directly accessed
if (!defined('INCLUDED_IN_MAIN_FILE')) {
    exit('Direct access not permitted.');
}

// Initialize variables
$student_found = null;
$search_error = null;
$show_search_modal = false;
$show_result_modal = false;
$show_sitin_form = false;
$sitin_error = null;
$sitin_success = null;
$close_modal_on_success = false; // Initialize the variable

// Handle adding sit-in record (if applicable)
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
        $sitin_error = "Student is already sitting in. Time out the student first.";
        $show_sitin_form = true;  // Keep showing the form
        $show_result_modal = true; // Keep the modal open
        
        // Fetch the student info again to display it
        $sql_student_info = "SELECT * FROM user WHERE IDNO = ?";
        $stmt_student_info = $conn->prepare($sql_student_info);
        $stmt_student_info->bind_param("i", $user_id);
        $stmt_student_info->execute();
        $result_student_info = $stmt_student_info->get_result();
        if ($result_student_info->num_rows > 0) {
            $student_found = $result_student_info->fetch_assoc();
        }
        $stmt_student_info->close();

        // Add a flag to auto-close the error message
        $auto_close_error = true;
    } else {
        $sql_add_sitin = "INSERT INTO sitin_records (IDNO, PURPOSE, LABORATORY, TIME_IN) VALUES (?, ?, ?, NOW())";
        $stmt_add_sitin = $conn->prepare($sql_add_sitin);
        $stmt_add_sitin->bind_param("iss", $user_id, $purpose, $laboratory);
    
        if ($stmt_add_sitin->execute()) {
            $sitin_success = "Sitin record added successfully";
            $show_sitin_form = false;
            $show_result_modal = true;
            $student_found = null;
            $close_modal_on_success = true;
            
            echo "<script>
                     alert('Student added successfully');
                     window.location.href = 'currentSitin.php';
                   </script>";
            exit;
        } else {
            $sitin_error = "Error adding sitin record. Please try again";
        }
        $stmt_add_sitin->close();  
    }

    $stmt_check_sitin->close();
}

// Handle student search
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

<!-- Search Modal -->
<div id="searchModal" class="w3-modal" style="z-index: 1000; display: none;">
    <div class="w3-modal-content w3-animate-zoom w3-round-xlarge" style="width: 30%;">
        <header class="w3-container" style="position:relative;">
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

<!-- Result Modal -->
<div id="resultModal" class="w3-modal" style="z-index: 1001; display: <?php echo ($show_result_modal) ? 'block' : 'none'; ?>;">
    <div class="w3-modal-content w3-animate-zoom w3-round-xlarge" style="width: 30%;">
        <header class="w3-container" style="position:relative;">
            <span onclick="document.getElementById('resultModal').style.display='none'" class="w3-button w3-display-topright">&times;</span>
            <h2 style="text-transform:uppercase;">Search Result</h2>
        </header>
        <?php if ($sitin_error) : ?>
                    <p class="w3-text-red w3-center"><?php echo htmlspecialchars($sitin_error); ?></p>
        <?php endif; ?>
        <?php if ($student_found) : ?>
            <div class="w3-container" style="margin: 0 10%;">
                <p><i class="fa-solid fa-id-card"></i> <strong>IDNO:</strong> <?php echo htmlspecialchars($student_found['IDNO']); ?></p>
                <p><i class="fa-solid fa-user"></i> <strong>Name:</strong> <?php echo htmlspecialchars($student_found['FIRSTNAME'] . ' ' . $student_found['MIDDLENAME'] . ' ' . $student_found['LASTNAME']); ?></p>
                <p><i class="fa-solid fa-book"></i> <strong>Course:</strong> <?php echo htmlspecialchars($student_found['COURSE']); ?></p>
                <p><i class="fa-solid fa-graduation-cap"></i> <strong>Level:</strong> <?php echo htmlspecialchars($student_found['YEAR_LEVEL']); ?></p>
                <p><i class="fa-solid fa-clock"></i> <strong>Remaining Session:</strong> <?php echo htmlspecialchars($student_found['SESSION_COUNT']); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($show_sitin_form && !$sitin_error) : ?>
            <?php if ($sitin_success) : ?>
                <p class="w3-text-green w3-center"><?php echo htmlspecialchars($sitin_success); ?></p>
            <?php endif; ?>
            <div class="w3-container" style="margin: 0 10%;">
                <form method="POST">
                    <input type="hidden" name="add_sitin_user_id" value="<?php echo $student_found['IDNO']; ?>">
                    <label for="purpose">Purpose:</label><br>
                    <select id="purpose" name="purpose" class="w3-input w3-border" required>
                        <option value="" disabled selected hidden>Select Purpose</option>
                        <option value="C Programming">C Programming</option>
                        <option value="Java Programming">Java Programming</option>
                        <option value="C#">C#</option>
                        <option value="PHP">PHP</option>
                        <option value="ASP.Net">ASP.Net</option>
                        <option value="Database">Database</option>
                        <option value="Digital Logic & Design">Digital Logic & Design</option>
                        <option value="Embedded System & IOT">Embedded System % IOT</option>
                        <option value="Python Programming">Python Programming</option>
                        <option value="Systems Integration & Architecture">Systems Integration & Architecture</option>
                        <option value="Computer Application">Computer Application</option>
                        <option value="Web Design & Development">Web Design & Development</option>
                        <option value="Project Management">Project Management</option>
                        <option value="Other">Other</option>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($show_result_modal): ?>
            document.getElementById('resultModal').style.display = 'block';

            <?php if (isset($close_modal_on_success) && $close_modal_on_success): ?>
                // Close modal after successful submission
                setTimeout(function() {
                    document.getElementById('resultModal').style.display = 'none';
                    // Also reset the form and clear any previous search results
                    var searchForm = document.querySelector('#searchModal form');
                    if (searchForm) searchForm.reset();
                    // Reload the page to reset all PHP variables
                    window.location.reload();
                }, 2000); // Close after 2 seconds
            <?php endif; ?>
            
            <?php if (isset($auto_close_error) && $auto_close_error): ?>
                // Close result modal and show search modal after error
                setTimeout(function() {
                    document.getElementById('resultModal').style.display = 'none';
                    document.getElementById('searchModal').style.display = 'block';
                    // Reset the error message
                    <?php $sitin_error = null; ?>
                }, 3000); // Show error for 2 seconds
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($show_search_modal && !$show_result_modal): ?>
            document.getElementById('searchModal').style.display = 'block';
        <?php endif; ?>
    });
</script>

