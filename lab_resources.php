<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}
include 'connect.php';

$categories = ['Programming', 'Database', 'Networking', 'Other'];
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $res = $conn->query("SELECT file_path FROM lab_resources WHERE id=$id");
    if ($row = $res->fetch_assoc()) {
        if ($row['file_path'] && file_exists($row['file_path'])) unlink($row['file_path']);
    }
    $conn->query("DELETE FROM lab_resources WHERE id=$id");
    header('Location: lab_resources.php');
    exit;
}

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_resource'])) {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $cat = $_POST['category'];
    $link = trim($_POST['resource_link']);
    $file_path = null;
    if (!empty($_FILES['resource_file']['name'])) {
        $ext = pathinfo($_FILES['resource_file']['name'], PATHINFO_EXTENSION);
        $basename = uniqid('res_') . '.' . $ext;
        $target = $upload_dir . $basename;
        if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $target)) {
            $file_path = $target;
        }
    }
    $stmt = $conn->prepare("INSERT INTO lab_resources (title, description, category, resource_link, file_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $title, $desc, $cat, $link, $file_path);
    $stmt->execute();
    $stmt->close();
    header('Location: lab_resources.php');
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_resource'])) {
    $id = intval($_POST['resource_id']);
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $cat = $_POST['category'];
    $link = trim($_POST['resource_link']);
    $file_path = null;

    // Get existing file path
    $res = $conn->query("SELECT file_path FROM lab_resources WHERE id=$id");
    $existing_file = $res->fetch_assoc()['file_path'];

    // Handle new file upload
    if (!empty($_FILES['resource_file']['name'])) {
        // Delete old file if exists
        if ($existing_file && file_exists($existing_file)) {
            unlink($existing_file);
        }
        $ext = pathinfo($_FILES['resource_file']['name'], PATHINFO_EXTENSION);
        $basename = uniqid('res_') . '.' . $ext;
        $target = $upload_dir . $basename;
        if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $target)) {
            $file_path = $target;
        }
    } else {
        $file_path = $existing_file;
    }

    $stmt = $conn->prepare("UPDATE lab_resources SET title=?, description=?, category=?, resource_link=?, file_path=? WHERE id=?");
    $stmt->bind_param('sssssi', $title, $desc, $cat, $link, $file_path, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: lab_resources.php');
    exit;
}

// Fetch resources
$res_list = [];
$res = $conn->query("SELECT * FROM lab_resources ORDER BY created_at DESC");
while ($row = $res->fetch_assoc()) $res_list[] = $row;

// Get resource to edit if specified
$resource_to_edit = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM lab_resources WHERE id=$edit_id");
    $resource_to_edit = $res->fetch_assoc();
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
    <title>Lab Resources Management</title>
    <style>
        body { background: #f4f6fa; }
        .resources-wrapper { max-width: 1200px; margin: 30px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.07); padding: 40px 30px; }
        .resources-title { color: #175fae; font-size: 2.2rem; font-weight: 600; margin-bottom: 30px; }
        .resources-flex { display: flex; gap: 30px; }
        .resource-form { flex: 1; background: #f9fafc; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .resource-list { flex: 2; max-height: 600px; overflow-y: auto; }
        .resource-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 18px 20px; margin-bottom: 18px; display: flex; align-items: flex-start; justify-content: space-between; }
        .resource-info { max-width: 80%; }
        .resource-title { font-weight: 600; font-size: 1.1rem; margin-bottom: 2px; }
        .resource-meta { color: #6c757d; font-size: 0.95em; margin-bottom: 6px; }
        .resource-actions { display: flex; gap: 10px; }
        .resource-actions button, .resource-actions a { border: none; background: none; cursor: pointer; font-size: 1.2em; text-decoration: none; }
        .resource-actions .edit { color: #175fae; }
        .resource-actions .delete { color: #dc3545; }
        .form-label { font-weight: 500; margin-bottom: 6px; display: block; }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 14px; font-size: 1em; }
        .form-textarea { resize: vertical; min-height: 60px; }
        .form-upload { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .form-upload label { background: #e3eefd; color: #175fae; border: 1px dashed #175fae; border-radius: 6px; padding: 8px 18px; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .form-upload input[type='file'] { display: none; }
        .add-btn { width: 100%; background: #175fae; color: #fff; border: none; border-radius: 6px; padding: 12px 0; font-size: 1.1em; font-weight: 600; margin-top: 10px; cursor: pointer; transition: background 0.2s; }
        .add-btn:hover { background: #2a5ca7; }
        .resource-file-link { display: inline-block; margin-top: 4px; color: #175fae; font-size: 0.97em; }
    </style>
</head>
<body>
    <div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
        <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-arrow-left"></i></button>
        <div class="profile w3-center w3-margin w3-padding">
            <?php
            $username = $_SESSION['user']['USERNAME'];
            $sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
            $stmt_profile = $conn->prepare($sql_profile);
            $stmt_profile->bind_param("s", $username);
            $stmt_profile->execute();
            $result_profile = $stmt_profile->get_result();
            $user = $result_profile->fetch_assoc();
            $profile_pic = isset($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : 'images/default_pic.png';
            ?>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="lab_schedule.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedule</span></a>
        <a href="lab_resources.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="reservation_management.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>
    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">☰</button>
            <h1 style="margin-left: 10px; color: #ffff;">Lab Resources Management</h1>
        </div>
        <div class="resources-wrapper">
            <div class="resources-flex">
                <form class="resource-form" method="post" enctype="multipart/form-data">
                    <?php if (isset($resource_to_edit)): ?>
                        <input type="hidden" name="update_resource" value="1">
                        <input type="hidden" name="resource_id" value="<?php echo $resource_to_edit['id']; ?>">
                        <h3 class="w3-text-purple">Edit Resource</h3>
                    <?php else: ?>
                        <h3 class="w3-text-purple">Add New Resource</h3>
                    <?php endif; ?>
                    <label class="form-label">Title</label>
                    <input class="form-input" type="text" name="title" required value="<?php echo isset($resource_to_edit) ? htmlspecialchars($resource_to_edit['title']) : ''; ?>">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" required><?php echo isset($resource_to_edit) ? htmlspecialchars($resource_to_edit['description']) : ''; ?></textarea>
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo (isset($resource_to_edit) && $resource_to_edit['category'] === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label">Resource Link (Optional)</label>
                    <input class="form-input" type="url" name="resource_link" placeholder="Enter URL (e.g., Google Drive)" value="<?php echo isset($resource_to_edit) ? htmlspecialchars($resource_to_edit['resource_link']) : ''; ?>">
                    <div class="form-upload">
                        <label for="file-upload"><i class="fa-solid fa-cloud-upload-alt"></i> Choose a file</label>
                        <input id="file-upload" type="file" name="resource_file">
                        <?php if (isset($resource_to_edit) && $resource_to_edit['file_path']): ?>
                            <div class="w3-small w3-text-grey">Current file: <?php echo basename($resource_to_edit['file_path']); ?></div>
                        <?php endif; ?>
                    </div>
                    <button class="add-btn" type="submit">
                        <?php if (isset($resource_to_edit)): ?>
                            <i class="fa fa-save"></i> Update Resource
                        <?php else: ?>
                            <i class="fa fa-plus"></i> Add Resource
                        <?php endif; ?>
                    </button>
                    <?php if (isset($resource_to_edit)): ?>
                        <a href="lab_resources.php" class="w3-button w3-red w3-margin-top" style="width: 100%;"><i class="fa fa-times"></i> Cancel</a>
                    <?php endif; ?>
                </form>
                <div class="resource-list">
                    <?php if (count($res_list) === 0): ?>
                        <div style="color:#888; text-align:center; margin-top:40px;">No resources found.</div>
                    <?php else: ?>
                        <?php foreach ($res_list as $res): ?>
                            <div class="resource-card">
                                <div class="resource-info">
                                    <div class="resource-title"><?php echo htmlspecialchars($res['title']); ?></div>
                                    <div class="resource-meta">Category: <?php echo htmlspecialchars($res['category']); ?> | Added: <?php echo date('M d, Y', strtotime($res['created_at'])); ?></div>
                                    <div><?php echo nl2br(htmlspecialchars($res['description'])); ?></div>
                                    <?php if ($res['resource_link']): ?>
                                        <div class="resource-file-link"><a href="<?php echo htmlspecialchars($res['resource_link']); ?>" target="_blank"><i class="fa-solid fa-link"></i> Resource Link</a></div>
                                    <?php endif; ?>
                                    <?php if ($res['file_path']): ?>
                                        <div class="resource-file-link"><a href="<?php echo htmlspecialchars($res['file_path']); ?>" target="_blank"><i class="fa-solid fa-file"></i> Download File</a></div>
                                    <?php endif; ?>
                                </div>
                                <div class="resource-actions">
                                    <a class="edit" href="?edit=<?php echo $res['id']; ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    <a class="delete" href="?delete=<?php echo $res['id']; ?>" title="Delete" onclick="return confirm('Delete this resource?');"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
    </script>
</body>
</html> 