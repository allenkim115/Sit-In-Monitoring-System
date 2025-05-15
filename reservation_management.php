<?php
require_once 'connect.php';

// Fetch rooms from database
$query = "SELECT DISTINCT room_number FROM pc_status ORDER BY room_number";
$result = mysqli_query($conn, $query);
$rooms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = $row['room_number'];
}

// Get selected room or default to first room
$selected_room = isset($_GET['room']) ? $_GET['room'] : $rooms[0];

// Fetch PCs for selected room
$query = "SELECT pc_number, status FROM pc_status WHERE room_number = ? ORDER BY CAST(SUBSTRING(pc_number, 3) AS UNSIGNED)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $selected_room);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pcs = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pcs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Management</title>
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="side_nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: #f7f9fb;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
        }
        .container {
            max-width: 1100px;
            margin: 32px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            padding: 32px;
        }
        .header {
            background: #4f3cc9;
            color: #fff;
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
            letter-spacing: 1px;
        }
        .nav {
            display: flex;
            gap: 16px;
        }
        .nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .nav a:hover {
            background: #3a2a9c;
        }
        .room-select {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .room-select label {
            font-weight: 600;
        }
        .room-select select {
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .action-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            background: #f0f0f0;
            color: #333;
            transition: background 0.2s, color 0.2s;
        }
        .action-btn.available { background: #e6f9ed; color: #1a7f37; }
        .action-btn.used { background: #ffeaea; color: #c0392b; }
        .action-btn.maintenance { background: #fff6e0; color: #e67e22; }
        .action-btn:hover { background: #d1d8e0; }
        .pc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 18px;
        }
        .pc-card {
            background: linear-gradient(135deg, #f8fafd 60%, #e9e4f0 100%);
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(79,60,201,0.08), 0 1.5px 4px rgba(0,0,0,0.04);
            padding: 28px 0 18px 0;
            text-align: center;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border 0.2s, box-shadow 0.2s, transform 0.15s;
            position: relative;
            min-height: 120px;
        }
        .pc-card.selected {
            border: 2.5px solid #2a1a5e;
            box-shadow: 0 0 0 3px #2a1a5e33, 0 4px 16px rgba(42,26,94,0.15);
            transform: scale(1.03);
        }
        .pc-card:hover {
            box-shadow: 0 8px 24px rgba(79,60,201,0.13), 0 2px 8px rgba(0,0,0,0.06);
            transform: translateY(-2px) scale(1.04);
        }
        .pc-card .fa-desktop {
            font-size: 2.8rem;
            margin-bottom: 10px;
            color: #bcb8d7;
        }
        /* Available status styles */
        .pc-card.available {
            background: linear-gradient(135deg, #e6f9ed 60%, #d1f2d8 100%);
            border: 2px solid #1a7f37;
        }
        .pc-card.available:hover {
            box-shadow: 0 8px 24px rgba(26,127,55,0.13), 0 2px 8px rgba(26,127,55,0.06);
        }
        .pc-card.available .fa-desktop {
            color: #1a7f37;
        }
        /* Used status styles */
        .pc-card.used {
            background: linear-gradient(135deg, #ffd6d6 60%, #ffb3b3 100%);
            border: 2px solid #8B0000;
        }
        .pc-card.used:hover {
            box-shadow: 0 8px 24px rgba(139,0,0,0.13), 0 2px 8px rgba(139,0,0,0.06);
        }
        .pc-card.used .fa-desktop {
            color: #8B0000;
        }
        /* Maintenance status styles */
        .pc-card.maintenance {
            background: linear-gradient(135deg, #fff6e0 60%, #ffe9c2 100%);
            border: 2px solid #e67e22;
        }
        .pc-card.maintenance:hover {
            box-shadow: 0 8px 24px rgba(230,126,34,0.13), 0 2px 8px rgba(230,126,34,0.06);
        }
        .pc-card.maintenance .fa-desktop {
            color: #e67e22;
        }
        .pc-card .pc-name {
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: 0.5px;
        }
        .status-dot {
            position: absolute;
            top: 12px;
            right: 18px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2.5px solid #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.10);
        }
        .pc-card.available .status-dot { background: #1a7f37; }
        .pc-card.used .status-dot { background: #8B0000; }
        .pc-card.maintenance .status-dot { background: #e67e22; }
        .legend {
            display: flex;
            gap: 24px;
            align-items: center;
            font-size: 1rem;
        }
        .legend span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .legend .dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-block;
        }
        .legend .available { background: #1a7f37; }
        .legend .used { background: #8B0000; }
        .legend .maintenance { background: #e67e22; }
        /* Add styles for all Font Awesome icons */
        .fa-solid, .fa-regular {
            color: #000000;
        }
        /* Override icon color for active sidebar items */
        .w3-bar-item.active .fa-solid,
        .w3-bar-item.active .fa-regular {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
        <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-arrow-left"></i></button>
        <div class="profile w3-center w3-margin w3-padding">
            <img src="images/default_pic.png" alt="profile_pic" style="width: 90px; height:90px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal') ? document.getElementById('searchModal').style.display='block' : null" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-users w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-column w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="lab_schedule.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedule</span></a>
        <a href="lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="reservation_management.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-from-bracket w3-padding"></i><span>Log Out</span></a>
    </div>
    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">☰</button>
            <h1 style="margin-left: 10px; color: #ffff;">Reservation Management</h1>
        </div>
        <div class="container">
            <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                <a href="reservation_requests.php" class="w3-button w3-purple w3-round-large" style="font-weight: 500;"><i class="fa fa-calendar-check"></i> Reservation Requests</a>
                <a href="reservation_logs.php" class="w3-button w3-purple w3-round-large" style="font-weight: 500;"><i class="fa fa-book"></i> Reservation Logs</a>
            </div>
            <div class="room-select">
                <label for="room">Select Room:</label>
                <select id="room" onchange="changeRoom(this.value)">
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= htmlspecialchars($room) ?>" <?= $room === $selected_room ? 'selected' : '' ?>>
                            <?= htmlspecialchars($room) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="actions-bar">
                <div style="display: flex; gap: 12px;">
                    <button class="action-btn" id="selectAll"><i class="fa-solid fa-list-check"></i> Select All</button>
                    <button class="action-btn available" id="setAvailable"><i class="fa fa-circle"></i> Set Available</button>
                    <button class="action-btn used" id="setUsed"><i class="fa fa-circle"></i> Set Used</button>
                    <button class="action-btn maintenance" id="setMaintenance"><i class="fa fa-wrench"></i> Set Maintenance</button>
                </div>
                <div class="legend">
                    <span><span class="dot available"></span> Available</span>
                    <span><span class="dot used"></span> Used</span>
                    <span><span class="dot maintenance"></span> Maintenance</span>
                </div>
            </div>
            <div class="pc-grid" id="pcGrid">
                <?php foreach ($pcs as $pc): ?>
                    <div class="pc-card <?= $pc['status'] ?>" data-id="<?= htmlspecialchars($pc['pc_number']) ?>">
                        <span class="status-dot"></span>
                        <i class="fa-solid fa-desktop"></i>
                        <div class="pc-name"><?= htmlspecialchars($pc['pc_number']) ?></div>
                    </div>
                <?php endforeach; ?>
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

        function changeRoom(room) {
            window.location.href = `?room=${encodeURIComponent(room)}`;
        }

        // JS for selection and bulk actions
        const pcCards = document.querySelectorAll('.pc-card');
        let selected = new Set();
        let allSelected = false;

        pcCards.forEach(card => {
            card.addEventListener('click', () => {
                card.classList.toggle('selected');
                const id = card.getAttribute('data-id');
                if (card.classList.contains('selected')) {
                    selected.add(id);
                } else {
                    selected.delete(id);
                }
                allSelected = selected.size === pcCards.length;
            });
        });

        document.getElementById('selectAll').onclick = () => {
            allSelected = !allSelected;
            pcCards.forEach(card => {
                if (allSelected) {
                    card.classList.add('selected');
                    selected.add(card.getAttribute('data-id'));
                } else {
                    card.classList.remove('selected');
                    selected.delete(card.getAttribute('data-id'));
                }
            });
        };

        async function setStatus(status) {
            if (selected.size === 0) {
                alert('Please select at least one PC');
                return;
            }

            const room = document.getElementById('room').value;
            const pcIds = Array.from(selected);

            try {
                const response = await fetch('update_pc_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        pc_ids: pcIds,
                        status: status,
                        room: room
                    })
                });

                const data = await response.json();
                if (data.success) {
                    pcCards.forEach(card => {
                        if (card.classList.contains('selected')) {
                            card.classList.remove('available', 'used', 'maintenance');
                            card.classList.add(status);
                        }
                    });
                    selected.clear();
                    allSelected = false;
                } else {
                    alert('Error updating status: ' + data.message);
                }
            } catch (error) {
                alert('Error updating status: ' + error.message);
            }
        }

        document.getElementById('setAvailable').onclick = () => setStatus('available');
        document.getElementById('setUsed').onclick = () => setStatus('used');
        document.getElementById('setMaintenance').onclick = () => setStatus('maintenance');
    </script>
</body>
</html> 