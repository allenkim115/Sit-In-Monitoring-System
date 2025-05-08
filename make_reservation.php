<?php
include 'connect.php';
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

// Fetch available rooms
$rooms = [];
$sql_rooms = "SELECT DISTINCT room_number FROM pc_status ORDER BY room_number";
$result_rooms = $conn->query($sql_rooms);
while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row['room_number'];
}
$selected_room = isset($_POST['room_number']) ? $_POST['room_number'] : (count($rooms) > 0 ? $rooms[0] : '');

// Fetch available PCs for the selected room
$pcs = [];
if ($selected_room) {
    $sql_pcs = "SELECT pc_number FROM pc_status WHERE room_number = ? AND status = 'available' ORDER BY pc_number";
    $stmt_pcs = $conn->prepare($sql_pcs);
    $stmt_pcs->bind_param("s", $selected_room);
    $stmt_pcs->execute();
    $result_pcs = $stmt_pcs->get_result();
    while ($row = $result_pcs->fetch_assoc()) {
        $pcs[] = $row['pc_number'];
    }
}

// Time slots
$time_slots = [
    '7:30AM-9:00AM',
    '9:00AM-10:30AM',
    '10:30AM-12:00PM',
    '12:00PM-1:00PM',
    '1:00PM-3:00PM',
    '3:00PM-4:30PM',
    '4:30PM-6:00PM',
    '6:00PM-7:30PM',
    '7:30PM-9:00PM'
];

$success_message = $error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reservation'])) {
    $room_number = $_POST['room_number'];
    // Remove 'Room ' prefix if it exists
    $room_number = str_replace('Room ', '', $room_number);
    $reservation_date = $_POST['reservation_date'];
    $time_slot = $_POST['time_slot'];
    $pc_number = $_POST['pc_number'];
    $purpose = $_POST['purpose'];
    $idno = $user['IDNO'];

    // Check if student already has a reservation for this time slot
    $sql_check_time = "SELECT room_number, pc_number, status FROM reservations WHERE idno = ? AND reservation_date = ? AND time_slot = ? AND status IN ('pending', 'approved')";
    $stmt_check_time = $conn->prepare($sql_check_time);
    $stmt_check_time->bind_param("iss", $idno, $reservation_date, $time_slot);
    $stmt_check_time->execute();
    $result_check_time = $stmt_check_time->get_result();
    
    if ($result_check_time->num_rows > 0) {
        $existing_reservation = $result_check_time->fetch_assoc();
        $status = ucfirst($existing_reservation['status']);
        $error_message = "You already have a {$status} reservation for this time slot in Room {$existing_reservation['room_number']} (PC {$existing_reservation['pc_number']}). Please choose a different time.";
        goto end_validation;
    }

    // Get the day of week from the reservation date
    $day_of_week = date('l', strtotime($reservation_date));
    error_log("Original day of week: " . $day_of_week);
    
    // Convert to the format used in lab_schedule (Monday/Wednesday, Tuesday/Thursday, Friday, Saturday)
    if ($day_of_week == 'Monday' || $day_of_week == 'Wednesday') {
        $day_of_week = 'Monday/Wednesday';
    } else if ($day_of_week == 'Tuesday' || $day_of_week == 'Thursday') {
        $day_of_week = 'Tuesday/Thursday';
    } else if ($day_of_week == 'Friday') {
        $day_of_week = 'Friday';
    } else if ($day_of_week == 'Saturday') {
        $day_of_week = 'Saturday';
    } else {
        $error_message = "Reservations are not allowed on Sundays.";
        goto end_validation;
    }
    error_log("Converted day of week: " . $day_of_week);

    // Debug logging
    error_log("Reservation attempt - Room: $room_number, Date: $reservation_date, Day: $day_of_week, Time: $time_slot");

    // First, let's check what schedules exist for this room
    $sql_check_all = "SELECT day_of_week, time_slot, status FROM lab_schedule WHERE lab_room = ?";
    $stmt_check_all = $conn->prepare($sql_check_all);
    $stmt_check_all->bind_param("s", $room_number);
    $stmt_check_all->execute();
    $result_all = $stmt_check_all->get_result();
    error_log("All schedules for room $room_number:");
    $found_schedules = false;
    while ($row = $result_all->fetch_assoc()) {
        $found_schedules = true;
        error_log("Day: {$row['day_of_week']}, Time: {$row['time_slot']}, Status: {$row['status']}");
    }
    if (!$found_schedules) {
        error_log("WARNING: No schedules found for room $room_number at all!");
    }

    // Now check the specific schedule
    $sql_check_schedule = "SELECT status FROM lab_schedule WHERE lab_room = ? AND day_of_week = ? AND time_slot = ?";
    error_log("Schedule check SQL: " . $sql_check_schedule);
    error_log("Schedule check parameters - Room: '$room_number', Day: '$day_of_week', Time: '$time_slot'");
    
    $stmt_check_schedule = $conn->prepare($sql_check_schedule);
    $stmt_check_schedule->bind_param("sss", $room_number, $day_of_week, $time_slot);
    $stmt_check_schedule->execute();
    $result_schedule = $stmt_check_schedule->get_result();
    
    // Debug logging
    error_log("Schedule check result rows: " . $result_schedule->num_rows);
    
    if ($result_schedule->num_rows > 0) {
        $schedule_row = $result_schedule->fetch_assoc();
        $schedule_status = $schedule_row['status'];
        error_log("Schedule status from DB: '" . $schedule_status . "'");
        error_log("Schedule status type: " . gettype($schedule_status));
        error_log("Schedule status length: " . strlen($schedule_status));
        
        if ($schedule_status === 'Occupied') {
            $error_message = "This room is occupied during the selected time slot according to the lab schedule.";
            error_log("Reservation rejected - Room is occupied in schedule");
            goto end_validation;
        } else if ($schedule_status === 'Available') {
            error_log("Room is available for reservation (status: '$schedule_status')");
        } else {
            $error_message = "Invalid schedule status found. Please contact the administrator.";
            error_log("Invalid schedule status: '$schedule_status'");
            goto end_validation;
        }
    } else {
        $error_message = "No schedule found for this room on the selected day and time.";
        error_log("No schedule entry found for this room/day/time - rejecting reservation");
        error_log("Query parameters that returned no results:");
        error_log("Room: '$room_number'");
        error_log("Day: '$day_of_week'");
        error_log("Time: '$time_slot'");
        goto end_validation;
    }

    // Continue with the existing double-booking check
    $sql_check = "SELECT 1 FROM reservations WHERE room_number = ? AND reservation_date = ? AND time_slot = ? AND pc_number = ? AND status IN ('pending','approved')";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ssss", $room_number, $reservation_date, $time_slot, $pc_number);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $error_message = "This PC is already reserved for the selected date and time.";
        error_log("Reservation rejected - PC already reserved");
        goto end_validation;
    }

    // Insert reservation
    $sql_insert = "INSERT INTO reservations (idno, room_number, pc_number, reservation_date, time_slot, purpose, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("isssss", $idno, $room_number, $pc_number, $reservation_date, $time_slot, $purpose);
    if ($stmt_insert->execute()) {
        $success_message = "Reservation request submitted successfully! Please wait for approval.";
        error_log("Reservation accepted and inserted");
    } else {
        $error_message = "Error submitting reservation: " . $conn->error;
        error_log("Database error: " . $conn->error);
    }

    end_validation:
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
    <title>Reservation</title>
    <style>
        .form-container { max-width: 600px; margin: 20px auto; padding: 20px; }
        .required { color: red; }
        
        /* Enhanced Legend Styling */
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 24px;
            background: #f5f0ff;
            border-radius: 12px;
            padding: 16px 20px;
        }
        .legend span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            color: #424242;
            padding: 4px 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .legend span:hover {
            background: rgba(255, 255, 255, 0.7);
        }
        .legend-box {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s;
        }
        .legend span:hover .legend-box {
            transform: scale(1.15);
        }
        .legend-available { border: 2px solid #43a047; background: #e8f5e9; }
        .legend-inuse { border: 2px solid #e53935; background: #ffebee; }
        .legend-maintenance { border: 2px solid #fb8c00; background: #fff3e0; }
        .legend-reserved { border: 2px solid #5c6bc0; background: #e8eaf6; }
        
        /* Enhanced PC Grid */
        .pc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 18px;
            margin: 0 auto;
            background: #f8fafc;
            border-radius: 16px;
            padding: 24px 18px;
            max-width: 100%;
            width: 100%;
            max-height: 55vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #9575cd #f0f0f0;
        }
        .pc-grid::-webkit-scrollbar {
            width: 8px;
        }
        .pc-grid::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 8px;
        }
        .pc-grid::-webkit-scrollbar-thumb {
            background-color: #9575cd;
            border-radius: 8px;
        }
        
        /* Enhanced PC buttons */
        .pc-btn {
            width: 100%;
            height: 90px;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            background: #fff;
            color: #212121;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            padding: 10px 2px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        .pc-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #e0e0e0;
            transition: background 0.25s;
        }
        .pc-btn.available {
            border-color: #43a047;
            background: linear-gradient(to bottom, #e8f5e9, #ffffff);
        }
        .pc-btn.available::before {
            background: #43a047;
        }
        .pc-btn.available:hover {
            border-color: #2e7d32;
            background: linear-gradient(to bottom, #dbefdc, #f1f8f1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.15);
        }
        .pc-btn.in_use {
            border-color: #e53935;
            background: linear-gradient(to bottom, #ffebee, #ffffff);
            color: #c62828;
            cursor: not-allowed;
            opacity: 0.85;
        }
        .pc-btn.in_use::before {
            background: #e53935;
        }
        .pc-btn.maintenance {
            border-color: #fb8c00;
            background: linear-gradient(to bottom, #fff3e0, #ffffff);
            color: #ef6c00;
            cursor: not-allowed;
            opacity: 0.85;
        }
        .pc-btn.maintenance::before {
            background: #fb8c00;
        }
        .pc-btn.reserved {
            border-color: #5c6bc0;
            background: linear-gradient(to bottom, #e8eaf6, #ffffff);
            color: #3949ab;
            cursor: not-allowed;
            opacity: 0.85;
        }
        .pc-btn.reserved::before {
            background: #5c6bc0;
        }
        .pc-btn.selected {
            border: 2.5px solid #512da8;
            background: linear-gradient(to bottom, #ede7f6, #f5f0ff);
            color: #512da8;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(81, 45, 168, 0.2);
            font-weight: 700;
        }
        .pc-btn.selected::before {
            background: #512da8;
            height: 6px;
        }
        .pc-btn .pc-label {
            font-size: 1.1em;
            font-weight: 700;
            margin-bottom: 8px;
            word-break: break-word;
            text-align: center;
        }
        .pc-btn .pc-status {
            font-size: 0.78em;
            font-weight: 500;
            color: #757575;
            text-align: center;
            word-break: break-word;
            line-height: 1.2;
            margin-top: 2px;
        }
        .pc-btn.selected .pc-status {
            color: #673ab7;
            font-weight: 600;
        }

        /* Enhanced PC loading state */
        #pcGrid.loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px;
            min-height: 200px;
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e0e0e0;
            border-top: 4px solid #512da8;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Enhanced Modal styles */
        #pcModal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(30, 20, 50, 0.25);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        #pcModal.active { 
            display: flex; 
            animation: fadeIn 0.25s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        #pcModal .modal-content {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(80, 40, 120, 0.15), 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 32px;
            width: 92%;
            max-width: 900px;
            border: none;
            position: relative;
            margin: 4vh auto;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0.7; }
            to { transform: translateY(0); opacity: 1; }
        }
        #pcModal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 0 15px 0;
            border-bottom: 1px solid #f0e7ff;
        }
        #pcModal .modal-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #512da8;
            margin: 0;
            letter-spacing: 0.5px;
        }
        #pcModal .close-btn {
            font-size: 1.8rem;
            cursor: pointer;
            color: #9e9e9e;
            border: none;
            background: #f5f0ff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin-left: 16px;
            padding: 0;
            line-height: 1;
        }
        #pcModal .close-btn:hover { 
            color: #512da8; 
            background: #ede7f6;
            transform: scale(1.05);
        }
        
        /* Modal footer with action buttons */
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #f0e7ff;
        }
        .modal-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .btn-cancel {
            background: #f5f5f5;
            color: #616161;
        }
        .btn-cancel:hover {
            background: #eeeeee;
            color: #424242;
        }
        .btn-confirm {
            background: #673ab7;
            color: white;
        }
        .btn-confirm:hover {
            background: #5e35b1;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(94, 53, 177, 0.2);
        }
        .btn-confirm:disabled {
            background: #d1c4e9;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Form enhancements */
        .w3-input {
            border-radius: 8px !important;
            transition: border 0.3s, box-shadow 0.3s;
        }
        .w3-input:focus {
            border-color: #673ab7 !important;
            box-shadow: 0 0 0 2px rgba(103, 58, 183, 0.1);
            outline: none;
        }
        #pc_number_display {
            background-color: #f5f0ff;
            color: #512da8;
            font-weight: 500;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            #pcModal .modal-content {
                padding: 20px;
                width: 95%;
                border-radius: 16px;
            }
            .legend {
                gap: 12px;
                padding: 12px;
            }
            .legend span {
                font-size: 0.85rem;
            }
            .pc-btn {
                height: 80px;
            }
        }
    </style>
</head>
<body style="background: #f4f6fa;">
<div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
    <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-arrow-left"></i></button>
    <div class="profile w3-center w3-margin w3-padding">
        <?php $profile_pic = isset($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : 'images/default_pic.png'; ?>
        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
    </div>
    <a href="dashboard.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
    <a href="#" onclick="document.getElementById('profile').style.display='block'" class="w3-bar-item w3-button"><i class="fa-regular fa-user w3-padding"></i><span>Profile</span></a>
    <a href="profile.php" class="w3-bar-item w3-button"><i class="fa-solid fa-edit w3-padding"></i><span>Edit Profile</span></a>
    <a href="history.php" class="w3-bar-item w3-button"><i class="fa-solid fa-clock-rotate-left w3-padding"></i><span>History</span></a>
    <a href="view_lab_schedules.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedules</span></a>
    <a href="view_lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
    <a href="make_reservation.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
    <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
</div>
<div style="margin-left:20%;">
    <div class="title_page w3-container" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">&#9776;</button>
        <h1 style="margin-left: 10px; color: #ffff;">Reservation</h1>
    </div>
    <div style="display: flex; gap: 32px; align-items: flex-start; flex-wrap: wrap; margin-top: 18px; margin-left: 18px; padding-left: 18px;">
        <div class="form-container w3-card-4 w3-round-xlarge" style="flex: 1 1 350px; min-width: 340px; padding: 24px; margin: 0; box-sizing: border-box;">
            <?php if ($success_message): ?>
                <div class="w3-panel w3-green w3-round" id="successMessage">
                    <p><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="w3-panel w3-red w3-round" id="errorMessage">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            <form method="POST" action="" class="w3-container" id="reservationForm">
                <div class="w3-section">
                    <label><b>Room <span class="required">*</span></b></label>
                    <select name="room_number" id="room_number" class="w3-input w3-border w3-round" required>
                        <option value="">Select Room</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo htmlspecialchars($room); ?>" <?php if ($room == $selected_room) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($room); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w3-section">
                    <label><b>Date <span class="required">*</span></b></label>
                    <input type="date" name="reservation_date" id="reservation_date" class="w3-input w3-border w3-round" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['reservation_date']) ? htmlspecialchars($_POST['reservation_date']) : ''; ?>">
                </div>
                <div class="w3-section">
                    <label><b>Time Slot <span class="required">*</span></b></label>
                    <select name="time_slot" id="time_slot" class="w3-input w3-border w3-round" required>
                        <option value="">Select Time</option>
                        <?php foreach ($time_slots as $slot): ?>
                            <option value="<?php echo $slot; ?>" <?php if (isset($_POST['time_slot']) && $_POST['time_slot'] == $slot) echo 'selected'; ?>><?php echo $slot; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w3-section">
                    <label><b>PC Number <span class="required">*</span></b></label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="pc_number_display" class="w3-input w3-border w3-round" style="max-width: 200px;" placeholder="Select a PC" readonly required>
                        <input type="hidden" name="pc_number" id="pc_number" required>
                        <button type="button" class="w3-button w3-purple w3-round" onclick="openPCModal()">Select PC</button>
                    </div>
                </div>
                <!-- Enhanced PC Selection Modal -->
                <div id="pcModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="modal-title">Select a Computer</div>
                            <button type="button" class="close-btn" onclick="closePCModal()">&times;</button>
                        </div>
                        <div class="legend">
                            <span><span class="legend-box legend-available"></span> Available</span>
                            <span><span class="legend-box legend-inuse"></span> In Use</span>
                            <span><span class="legend-box legend-maintenance"></span> Maintenance</span>
                            <span><span class="legend-box legend-reserved"></span> Reserved</span>
                        </div>
                        <div id="pcGrid" class="pc-grid"></div>
                        <div class="modal-footer">
                            <button type="button" class="modal-btn btn-cancel" onclick="closePCModal()">Cancel</button>
                            <button class="modal-btn btn-confirm" id="confirmPCBtn" disabled>Confirm Selection</button>
                        </div>
                    </div>
                </div>
                <div class="w3-section">
                    <label><b>Purpose <span class="required">*</span></b></label>
                    <select name="purpose" class="w3-input w3-border w3-round" required>
                        <option value="" disabled selected hidden>Select Purpose</option>
                        <option value="C Programming">C Programming</option>
                        <option value="C++ Programming">C++ Programming</option>
                        <option value="Python Programming">Python Programming</option>
                        <option value="PHP Programming">PHP Programming</option>
                        <option value="Java Programming">Java Programming</option>
                        <option value=".Net Programming">ASP.Net Programming</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="w3-section">
                    <button type="submit" name="submit_reservation" class="w3-button w3-purple w3-round w3-block">
                        Submit Reservation
                    </button>
                </div>
            </form>
        </div>
        <!-- Reservation Requests Card -->
        <div class="w3-card-4 w3-round-xlarge" style="flex: 1 1 350px; min-width: 340px; background: #fff; padding: 24px; margin: 0; margin-right: 30px; box-sizing: border-box;">
            <h3 style="margin-bottom: 16px; color: #512da8;"><i class="fa-solid fa-list-check" style="margin-right: 8px;"></i>Reservation Requests</h3>
            <?php
            // Filtering
            $filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';
            $idno = $user['IDNO'];
            $filter_sql = '';
            if ($filter === 'Pending') {
                $filter_sql = "AND status = 'pending'";
            } elseif ($filter === 'Approved') {
                $filter_sql = "AND status = 'approved'";
            } elseif ($filter === 'Rejected') {
                $filter_sql = "AND status = 'rejected'";
            } else {
                $filter_sql = "AND status IN ('pending','approved','rejected')";
            }
            $sql_requests = "SELECT id, reservation_date, time_slot, room_number, pc_number, purpose, status FROM reservations WHERE idno = ? $filter_sql ORDER BY reservation_date DESC, time_slot DESC";
            $stmt_requests = $conn->prepare($sql_requests);
            $stmt_requests->bind_param("i", $idno);
            $stmt_requests->execute();
            $result_requests = $stmt_requests->get_result();
            ?>
            <div style="margin-bottom: 12px;">
                <form method="get" style="display: flex; gap: 8px;">
                    <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($selected_room); ?>">
                    <button type="submit" name="filter" value="All" class="w3-button w3-round <?php if($filter=='All') echo 'w3-purple'; else echo 'w3-light-grey'; ?>">All</button>
                    <button type="submit" name="filter" value="Pending" class="w3-button w3-round <?php if($filter=='Pending') echo 'w3-purple'; else echo 'w3-light-grey'; ?>">Pending</button>
                    <button type="submit" name="filter" value="Approved" class="w3-button w3-round <?php if($filter=='Approved') echo 'w3-purple'; else echo 'w3-light-grey'; ?>">Approved</button>
                    <button type="submit" name="filter" value="Rejected" class="w3-button w3-round <?php if($filter=='Rejected') echo 'w3-purple'; else echo 'w3-light-grey'; ?>">Rejected</button>
                </form>
            </div>
            <div style="overflow-x:auto;">
            <table class="w3-table w3-bordered w3-small">
                <thead>
                    <tr style="background:#ede7f6; color:#512da8;">
                        <th>Date</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>PC</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result_requests->num_rows > 0): ?>
                    <?php while ($row = $result_requests->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['time_slot']); ?></td>
                            <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['pc_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                            <td>
                                <?php
                                    $status = ucfirst($row['status']);
                                    $color = $status === 'Pending' ? '#ffb300' : ($status === 'Approved' ? '#43a047' : ($status === 'Rejected' ? '#e53935' : '#757575'));
                                ?>
                                <span style="color:<?php echo $color; ?>; font-weight:600;"> <?php echo $status; ?> </span>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="cancel_request_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="cancel_request" class="w3-button w3-round w3-red w3-small" onclick="return confirm('Cancel this reservation request?');">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; color:#757575;">No reservation requests found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
<script>
    // Add this at the beginning of your script section
    // Auto-hide messages after 2 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');
        
        if (successMessage) {
            setTimeout(function() {
                successMessage.style.opacity = '0';
                successMessage.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    successMessage.style.display = 'none';
                }, 500);
            }, 000);
        }
        
        if (errorMessage) {
            setTimeout(function() {
                errorMessage.style.opacity = '0';
                errorMessage.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    errorMessage.style.display = 'none';
                }, 8000);
            }, 5000);
        }
    });

    function w3_open() {
        document.getElementById("mySidebar").style.display = "block";
    }
    function w3_close() {
        document.getElementById("mySidebar").style.display = "none";
    }

    function openPCModal() {
        updatePCGrid();
        document.getElementById('pcModal').classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
    
    function closePCModal() {
        document.getElementById('pcModal').classList.remove('active');
        document.body.style.overflow = 'auto'; // Restore scrolling
    }

    // AJAX to update PC grid with improved loading state
    function updatePCGrid() {
        const room = document.getElementById('room_number').value;
        const date = document.getElementById('reservation_date').value;
        const time_slot = document.getElementById('time_slot').value;
        const grid = document.getElementById('pcGrid');
        const confirmBtn = document.getElementById('confirmPCBtn');
        
        // Reset confirm button
        confirmBtn.disabled = true;
        
        // Show loading state
        grid.className = 'pc-grid loading';
        grid.innerHTML = '<div class="loading-spinner"></div>';
        
        if (!room) { 
            grid.innerHTML = '<div style="padding: 30px; text-align: center; color: #616161;">Please select a room first</div>'; 
            return; 
        }
        
        if (!date) { 
            grid.innerHTML = '<div style="padding: 30px; text-align: center; color: #616161;">Please select a date first</div>'; 
            return; 
        }
        
        if (!time_slot) { 
            grid.innerHTML = '<div style="padding: 30px; text-align: center; color: #616161;">Please select a time slot first</div>'; 
            return; 
        }
        
        fetch(`get_pc_grid.php?room=${encodeURIComponent(room)}&date=${encodeURIComponent(date)}&time_slot=${encodeURIComponent(time_slot)}`)
            .then(res => res.json())
            .then(data => {
                // Log debug information to console
                console.log('Debug Information:', data.debug);
                
                grid.className = 'pc-grid';
                grid.innerHTML = '';
                
                if (!data.pcs.length) { 
                    grid.innerHTML = '<div style="padding: 30px; text-align: center; color: #616161;">No computers found for this selection</div>'; 
                    return; 
                }
                
                data.pcs.forEach(pc => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    
                    // Map the status to the correct CSS class
                    let statusClass;
                    switch(pc.status) {
                        case 'in_use':
                            statusClass = 'in_use';
                            break;
                        case 'reserved':
                            statusClass = 'reserved';
                            break;
                        case 'maintenance':
                            statusClass = 'maintenance';
                            break;
                        default:
                            statusClass = 'available';
                    }
                    
                    btn.className = `pc-btn ${statusClass}`;
                    if (pc.status !== 'available') btn.disabled = true;
                    
                    const statusLabel = pc.status.charAt(0).toUpperCase() + pc.status.slice(1).replace('_',' ');
                    btn.innerHTML = `<span class='pc-label'>${pc.pc_number}</span><span class='pc-status'>${statusLabel}</span>`;
                    
                    btn.onclick = function() {
                        document.querySelectorAll('.pc-btn').forEach(b => b.classList.remove('selected'));
                        btn.classList.add('selected');
                        document.getElementById('pc_number').value = pc.pc_number;
                        document.getElementById('pc_number_display').value = pc.pc_number;
                        // Enable confirm button
                        confirmBtn.disabled = false;
                    };
                    grid.appendChild(btn);
                });
            })
            .catch(error => {
                grid.innerHTML = '<div style="padding: 30px; text-align: center; color: #e53935;">Error loading computers. Please try again.</div>';
                console.error('Error fetching PC data:', error);
            });
    }
    
    // Setup confirm button
    document.addEventListener('DOMContentLoaded', function() {
        const confirmBtn = document.getElementById('confirmPCBtn');
        confirmBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent any default action (like form submission)
            closePCModal();
        });
    });
    
    document.getElementById('room_number').addEventListener('change', function() {
        document.getElementById('pc_number').value = '';
        document.getElementById('pc_number_display').value = '';
    });
    document.getElementById('reservation_date').addEventListener('change', function() {
        document.getElementById('pc_number').value = '';
        document.getElementById('pc_number_display').value = '';
    });
    document.getElementById('time_slot').addEventListener('change', function() {
        document.getElementById('pc_number').value = '';
        document.getElementById('pc_number_display').value = '';
    });
    // Prevent form submit if no PC selected
    document.getElementById('reservationForm').addEventListener('submit', function(e) {
        if (!document.getElementById('pc_number').value) {
            alert('Please select a PC.');
            e.preventDefault();
        }
    });
</script>
<?php
// Handle cancel request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    $cancel_id = intval($_POST['cancel_request_id']);
    $sql_cancel = "UPDATE reservations SET status = 'cancelled' WHERE id = ? AND idno = ? AND status = 'pending'";
    $stmt_cancel = $conn->prepare($sql_cancel);
    $stmt_cancel->bind_param("ii", $cancel_id, $idno);
    $stmt_cancel->execute();
    // Optionally, add a message or redirect to refresh the list
    echo "<script>window.location.href=window.location.href;</script>";
    exit;
}
?>
    </script>
    <!-- Profile Modal -->
    <div id="profile" class="w3-modal" style="z-index: 1000;">
        <div class="w3-modal-content w3-animate-zoom w3-round-xlarge" style="width: 30%;">
            <header class="w3-container"> 
                <span onclick="document.getElementById('profile').style.display='none'" 
                      class="w3-button w3-display-topright">&times;</span>
                <h2 style="text-transform:uppercase;">Profile</h2>
            </header>
            <div class="display_photo w3-container w3-center">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 120px; height:120px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
            </div>
            <hr style="margin: 1rem 10%; border-width: 2px;">
            <div class="w3-container" style="margin: 0 10%;">
                <p><i class="fa-solid fa-id-card"></i> <strong>IDNO:</strong> <?php echo htmlspecialchars($_SESSION['user']['IDNO']); ?></p>
                <p><i class="fa-solid fa-user"></i> <strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['user']['FIRSTNAME'] . ' ' . $_SESSION['user']['MIDDLENAME'] . ' ' . $_SESSION['user']['LASTNAME']); ?></p>
                <p><i class="fa-solid fa-book"></i> <strong>Course:</strong> <?php echo htmlspecialchars($_SESSION['user']['COURSE']); ?></p>
                <p><i class="fa-solid fa-graduation-cap"></i> <strong>Level:</strong> <?php echo htmlspecialchars($_SESSION['user']['YEAR_LEVEL']); ?></p>
            </div>
            <footer class="w3-container w3-padding" style="margin: 0 30%;">
                <button class="w3-btn w3-purple w3-round-xlarge" onclick="window.location.href='profile.php'">Edit Profile</button>
            </footer>
        </div>
    </div>
</body>
</html>