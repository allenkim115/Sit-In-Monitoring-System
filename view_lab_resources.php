<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Retrieve user data from the session
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

// Fetch resources
$res_list = [];
$res = $conn->query("SELECT * FROM lab_resources ORDER BY created_at DESC");
while ($row = $res->fetch_assoc()) $res_list[] = $row;

$categories = ['All Categories', 'Programming', 'Database', 'Networking', 'Other'];
$types = ['All Types', 'File', 'Link'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <title>Lab Resources</title>
    <style>
        body { background: #f4f6fa; }
        img{  border: 2px solid rgba(100,25,117,1); border-radius: 50%; }
        .main-wrapper { max-width: 98vw; margin: 30px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 0; }
        .filters-bar { display: flex; gap: 16px; align-items: center; padding: 18px 32px 0 32px; background: #fff; }
        .search-input { flex: 2; padding: 10px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1em; }
        .filter-select { flex: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1em; }
        .resources-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; padding: 32px; }
        .resource-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 0 0 18px 0; display: flex; flex-direction: column; min-height: 260px; border: 1px solid #e3e8f0; }
        .resource-card-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 18px 0 18px; }
        .category-badge { background: #205493; color: #fff; border-radius: 20px; padding: 4px 16px; font-size: 0.98em; font-weight: 500; display: flex; align-items: center; gap: 6px; }
        .date-badge { color: #205493; background: #e3eefd; border-radius: 20px; padding: 4px 14px; font-size: 0.98em; display: flex; align-items: center; gap: 6px; }
        .resource-title { font-weight: 600; font-size: 1.15rem; margin: 18px 18px 0 18px; }
        .resource-desc { color: #444; font-size: 1em; margin: 8px 18px 0 18px; min-height: 40px; }
        .resource-actions { margin: 24px 18px 0 18px; display: flex; flex-direction: column; gap: 10px; }
        .resource-actions a { display: flex; align-items: center; justify-content: center; gap: 8px; border: none; border-radius: 8px; padding: 10px 0; font-size: 1.08em; font-weight: 500; text-decoration: none; transition: background 0.2s; }
        .visit-link { background: #205493; color: #fff; }
        .visit-link:hover { background: #183b6b; }
        .download-file { background: #205493; color: #fff; border: none; }
        .download-file:hover { background: #183b6b; }
        @media (max-width: 700px) {
            .main-wrapper { padding: 0; }
            .filters-bar, .resources-grid { padding: 12px; }
            .resources-grid { grid-template-columns: 1fr; }
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
    <a href="dashboard.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
    <a href="#" onclick="document.getElementById('profile').style.display='block'" class="w3-bar-item w3-button"><i class="fa-regular fa-user w3-padding"></i><span>Profile</span></a>
    <a href="profile.php" class="w3-bar-item w3-button"><i class="fa-solid fa-edit w3-padding"></i><span>Edit Profile</span></a>
    <a href="history.php" class="w3-bar-item w3-button"><i class="fa-solid fa-clock-rotate-left w3-padding"></i><span>History</span></a>
    <a href="view_lab_schedules.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedules</span></a>
    <a href="view_lab_resources.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
    <a href="#" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
    <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
</div>
<div id="profile" class="w3-modal" style="z-index: 1000;">
    <div class="w3-modal-content w3-animate-zoom w3-round-xlarge" style="width: 30%;">
        <header class="w3-container"> 
            <span onclick="document.getElementById('profile').style.display='none'" 
                  class="w3-button w3-display-topright">&times;</span>
            <h2 style="text-transform:uppercase;">Profile</h2>
        </header>
        <div class="display_photo w3-container w3-center">
            <img src="<?php echo htmlspecialchars($user['PROFILE_PIC']); ?>" alt="profile_pic" style="width: 120px; height:120px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <hr style="margin: 1rem 10%; border-width: 2px;">
        <div class="w3-container" style="margin: 0 10%;">
            <p><i class="fa-solid fa-id-card"></i> <strong>IDNO:</strong> <?php echo htmlspecialchars($user['IDNO']); ?></p>
            <p><i class="fa-solid fa-user"></i> <strong>Name:</strong> <?php echo htmlspecialchars($user['FIRSTNAME'] . ' ' . $user['MIDDLENAME'] . ' ' . $user['LASTNAME']); ?></p>
            <p><i class="fa-solid fa-book"></i> <strong>Course:</strong> <?php echo htmlspecialchars($user['COURSE']); ?></p>
            <p><i class="fa-solid fa-graduation-cap"></i> <strong>Level:</strong> <?php echo htmlspecialchars($user['YEAR_LEVEL']); ?></p>
        </div>
        <footer class="w3-container w3-padding" style="margin: 0 30%;">
            <button class="w3-btn w3-purple w3-round-xlarge" onclick="window.location.href='profile.php'">Edit Profile</button>
        </footer>
    </div>
</div>
<div style="margin-left:20%; z-index: 1; position: relative;">
    <div class="title_page w3-container" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">&#9776;</button>
        <h1 style="margin-left: 10px; color: #ffff;">Lab Resources</h1>
    </div>
        <div class="filters-bar">
            <input class="search-input" type="text" id="searchInput" placeholder="Search resources..." onkeyup="filterResources()">
            <select class="filter-select" id="categoryFilter" onchange="filterResources()">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>
            <select class="filter-select" id="typeFilter" onchange="filterResources()">
                <?php foreach ($types as $type): ?>
                    <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="resources-grid" id="resourcesGrid">
            <?php foreach ($res_list as $res): ?>
                <div class="resource-card" data-title="<?php echo strtolower($res['title']); ?>" data-category="<?php echo $res['category']; ?>" data-type="<?php echo ($res['file_path'] ? 'File' : ($res['resource_link'] ? 'Link' : '')); ?>">
                    <div class="resource-card-header">
                        <span class="category-badge"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($res['category']); ?></span>
                        <span class="date-badge"><i class="fa-solid fa-calendar"></i> <?php echo date('F d, Y', strtotime($res['created_at'])); ?></span>
                    </div>
                    <div class="resource-title"><?php echo htmlspecialchars($res['title']); ?></div>
                    <div class="resource-desc"><?php echo htmlspecialchars($res['description']); ?></div>
                    <div class="resource-actions">
                        <?php if ($res['resource_link']): ?>
                            <a class="visit-link" href="<?php echo htmlspecialchars($res['resource_link']); ?>" target="_blank"><i class="fa-solid fa-up-right-from-square"></i> Visit Link</a>
                        <?php endif; ?>
                        <?php if ($res['file_path']): ?>
                            <a class="download-file" href="<?php echo htmlspecialchars($res['file_path']); ?>" target="_blank"><i class="fa-solid fa-download"></i> Download File</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
    </div>
</div>
<script>
    function w3_open() {
        document.getElementById("mySidebar").style.display = "block";
    }
    function w3_close() {
        document.getElementById("mySidebar").style.display = "none";
    }
    function filterResources() {
        var search = document.getElementById('searchInput').value.toLowerCase();
        var cat = document.getElementById('categoryFilter').value;
        var type = document.getElementById('typeFilter').value;
        var cards = document.querySelectorAll('.resource-card');
        cards.forEach(function(card) {
            var title = card.getAttribute('data-title');
            var category = card.getAttribute('data-category');
            var rtype = card.getAttribute('data-type');
            var show = true;
            if (search && !title.includes(search)) show = false;
            if (cat !== 'All Categories' && category !== cat) show = false;
            if (type !== 'All Types' && rtype !== type) show = false;
            card.style.display = show ? '' : 'none';
        });
    }
</script>
</body>
</html> 