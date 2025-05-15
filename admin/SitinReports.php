<?php
define('INCLUDED_IN_MAIN_FILE', true);
include '../includes/connect.php';
session_start();

// Include required libraries
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use setasign\Fpdi\Tcpdf\Fpdi;

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Get the profile picture from database
$username = $_SESSION['user']['USERNAME'];
$sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param("s", $username);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$user = $result_profile->fetch_assoc();

// Initialize filters
$laboratory_filter = isset($_GET['laboratory']) ? $_GET['laboratory'] : '';
$purpose_filter = isset($_GET['purpose']) ? $_GET['purpose'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Pagination parameters
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Base SQL query for total count
$sql_count = "SELECT COUNT(*) as total FROM sitin_records sr
              JOIN user u ON sr.IDNO = u.IDNO
              WHERE 1=1";

// Only include completed sit-ins
$sql_count .= " AND sr.TIME_OUT IS NOT NULL ";

// Exclude reserved sit-ins that have not yet started
$sql_count .= " AND NOT EXISTS (
    SELECT 1 FROM reservations r
    WHERE r.idno = sr.IDNO
      AND r.room_number = sr.LABORATORY
      AND DATE(r.reservation_date) = DATE(sr.TIME_IN)
      AND r.status = 'approved'
      AND CONCAT(r.reservation_date, ' ', SUBSTRING_INDEX(r.time_slot, '-', 1)) > NOW()
)";

// Apply filters to count query
if (!empty($laboratory_filter)) {
    $sql_count .= " AND sr.LABORATORY = ?";
}
if (!empty($purpose_filter)) {
    $sql_count .= " AND sr.PURPOSE = ?";
}
if (!empty($date_from)) {
    $sql_count .= " AND DATE(sr.TIME_IN) >= ?";
}
if (!empty($date_to)) {
    $sql_count .= " AND DATE(sr.TIME_IN) <= ?";
}

// Prepare and execute count query
$stmt_count = $conn->prepare($sql_count);

// Bind parameters if filters are set
$types = '';
$params = [];

if (!empty($laboratory_filter)) {
    $types .= 's';
    $params[] = $laboratory_filter;
}
if (!empty($purpose_filter)) {
    $types .= 's';
    $params[] = $purpose_filter;
}
if (!empty($date_from)) {
    $types .= 's';
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $types .= 's';
    $params[] = $date_to;
}

if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$stmt_count->close();

// Base SQL query for records
$sql_sitins = "SELECT sr.ID, u.IDNO, CONCAT(u.FIRSTNAME, ' ', u.LASTNAME) AS Name, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN, sr.TIME_OUT
               FROM sitin_records sr
               JOIN user u ON sr.IDNO = u.IDNO
               WHERE 1=1";

// Only include completed sit-ins
$sql_sitins .= " AND sr.TIME_OUT IS NOT NULL ";

// Exclude reserved sit-ins that have not yet started
$sql_sitins .= " AND NOT EXISTS (
    SELECT 1 FROM reservations r
    WHERE r.idno = sr.IDNO
      AND r.room_number = sr.LABORATORY
      AND DATE(r.reservation_date) = DATE(sr.TIME_IN)
      AND r.status = 'approved'
      AND CONCAT(r.reservation_date, ' ', SUBSTRING_INDEX(r.time_slot, '-', 1)) > NOW()
)";

// Apply filters
if (!empty($laboratory_filter)) {
    $sql_sitins .= " AND sr.LABORATORY = ?";
}
if (!empty($purpose_filter)) {
    $sql_sitins .= " AND sr.PURPOSE = ?";
}
if (!empty($date_from)) {
    $sql_sitins .= " AND DATE(sr.TIME_IN) >= ?";
}
if (!empty($date_to)) {
    $sql_sitins .= " AND DATE(sr.TIME_IN) <= ?";
}

// Add pagination to the query only if not exporting
if (!isset($_GET['export'])) {
    $sql_sitins .= " ORDER BY sr.TIME_IN DESC LIMIT ? OFFSET ?";
    $types .= 'ii';
    $params[] = $records_per_page;
    $params[] = $offset;
} else {
    $sql_sitins .= " ORDER BY sr.TIME_IN DESC";
}

// For debugging
error_log("SQL Query: " . $sql_sitins);
error_log("Laboratory Filter: " . $laboratory_filter);
error_log("Purpose Filter: " . $purpose_filter);
error_log("Date From: " . $date_from);
error_log("Date To: " . $date_to);

// Prepare and execute the query
$stmt_sitins = $conn->prepare($sql_sitins);

// Bind parameters if filters are set
if (!empty($params)) {
    $stmt_sitins->bind_param($types, ...$params);
}

$stmt_sitins->execute();
$result_sitins = $stmt_sitins->get_result();

$sitin_records = [];
if ($result_sitins->num_rows > 0) {
    while ($row = $result_sitins->fetch_assoc()) {
        $sitin_records[] = $row;
    }
}
$stmt_sitins->close();

// For debugging
error_log("Number of records found: " . count($sitin_records));

// Get unique laboratories and purposes for filter dropdowns
$sql_labs = "SELECT DISTINCT LABORATORY FROM sitin_records ORDER BY LABORATORY";
$sql_purposes = "SELECT DISTINCT PURPOSE FROM sitin_records ORDER BY PURPOSE";

$result_labs = $conn->query($sql_labs);
$result_purposes = $conn->query($sql_purposes);

$laboratories = [];
$purposes = [];

while ($row = $result_labs->fetch_assoc()) {
    $laboratories[] = $row['LABORATORY'];
}
while ($row = $result_purposes->fetch_assoc()) {
    $purposes[] = $row['PURPOSE'];
}

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Build the filename with filter information
    $filename = "sit_in_reports";
    if (!empty($laboratory_filter)) {
        $filename .= "_lab_" . $laboratory_filter;
    }
    if (!empty($purpose_filter)) {
        $filename .= "_" . str_replace(' ', '_', strtolower($purpose_filter));
    }
    if (!empty($date_from)) {
        $filename .= "_from_" . $date_from;
    }
    if (!empty($date_to)) {
        $filename .= "_to_" . $date_to;
    }
    $filename .= "_" . date('Y-m-d') . "." . $export_type;
    
    // Get filtered data using the same query as the display
    $sql_export = $sql_sitins;
    $stmt_export = $conn->prepare($sql_export);
    
    // Bind parameters if filters are set
    if (!empty($params)) {
        // For debugging - log the query and parameters
        error_log("Export SQL Query: " . $sql_export);
        error_log("Export Parameters: " . print_r($params, true));
        error_log("Export Types: " . $types);
        
        $stmt_export->bind_param($types, ...$params);
    }
    
    $stmt_export->execute();
    $result_export = $stmt_export->get_result();
    $export_records = [];
    while ($row = $result_export->fetch_assoc()) {
        $export_records[] = $row;
    }
    
    // For debugging - log the number of records
    error_log("Number of records to export: " . count($export_records));
    
    $stmt_export->close();
    
    if ($export_type == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add filter information as header rows
        fputcsv($output, array('Sit-in Reports - Generated on ' . date('Y-m-d')));
        if (!empty($laboratory_filter)) fputcsv($output, array('Laboratory:', $laboratory_filter));
        if (!empty($purpose_filter)) fputcsv($output, array('Purpose:', $purpose_filter));
        if (!empty($date_from)) fputcsv($output, array('Date From:', $date_from));
        if (!empty($date_to)) fputcsv($output, array('Date To:', $date_to));
        fputcsv($output, array('')); // Empty line for spacing
        
        fputcsv($output, array('ID', 'IDNO', 'Name', 'Purpose', 'Laboratory', 'Time In', 'Time Out'));
        
        foreach ($export_records as $record) {
            fputcsv($output, array(
                $record['ID'],
                $record['IDNO'],
                $record['Name'],
                $record['PURPOSE'],
                $record['LABORATORY'],
                $record['TIME_IN'],
                $record['TIME_OUT']
            ));
        }
        fclose($output);
        exit;
    } elseif ($export_type == 'xlsx') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add title and filter information
        $row = 1;
        $sheet->setCellValue('A' . $row, 'Sit-in Reports - Generated on ' . date('Y-m-d'));
        $row++;
        
        if (!empty($laboratory_filter)) {
            $sheet->setCellValue('A' . $row, 'Laboratory:');
            $sheet->setCellValue('B' . $row, $laboratory_filter);
            $row++;
        }
        if (!empty($purpose_filter)) {
            $sheet->setCellValue('A' . $row, 'Purpose:');
            $sheet->setCellValue('B' . $row, $purpose_filter);
            $row++;
        }
        if (!empty($date_from)) {
            $sheet->setCellValue('A' . $row, 'Date From:');
            $sheet->setCellValue('B' . $row, $date_from);
            $row++;
        }
        if (!empty($date_to)) {
            $sheet->setCellValue('A' . $row, 'Date To:');
            $sheet->setCellValue('B' . $row, $date_to);
            $row++;
        }
        
        $row++; // Empty row for spacing
        
        // Set headers
        $headers = array('ID', 'IDNO', 'Name', 'Purpose', 'Laboratory', 'Time In', 'Time Out');
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $row++;
        
        // Add data
        foreach ($export_records as $record) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $record['ID']);
            $sheet->setCellValue($col++ . $row, $record['IDNO']);
            $sheet->setCellValue($col++ . $row, $record['Name']);
            $sheet->setCellValue($col++ . $row, $record['PURPOSE']);
            $sheet->setCellValue($col++ . $row, $record['LABORATORY']);
            $sheet->setCellValue($col++ . $row, $record['TIME_IN']);
            $sheet->setCellValue($col++ . $row, $record['TIME_OUT']);
            $row++;
        }
        
        // Auto-size columns
        foreach(range('A','G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } elseif ($export_type == 'pdf') {
        // Create new PDF document
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Sit-in System');
        $pdf->SetTitle('Sit-in Reports');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins - adjusted for landscape
        $pdf->SetMargins(15, 15, 15);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font for title
        $pdf->SetFont('helvetica', 'B', 16);
        
        // Add logo
        $logoPath = 'images/ucheader.jpg'; // Replace with the actual path to your logo in JPEG format
        $pdf->Image($logoPath, 15, 10, 30); // Adjust position (x, y) and size (width)

        // Add title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'University of Cebu', 0, 1, 'C');
        $pdf->Cell(0, 10, 'College of Computer Studies', 0, 1, 'C');
        $pdf->Cell(0, 10, 'Sit-in Reports', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Generated on ' . date('Y-m-d'), 0, 1, 'C');

        $logoPath = 'images/OIP.jpg'; // Replace with the actual path to your logo in JPEG format
        $pdf->Image($logoPath, 260, 10, 30); // Adjust position (x, y) and size (width) to place it on the right side
        
        // Add filter information
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', '', 10);
        if (!empty($laboratory_filter)) {
            $pdf->Cell(30, 5, 'Laboratory:', 0, 0);
            $pdf->Cell(0, 5, $laboratory_filter, 0, 1);
        }
        if (!empty($purpose_filter)) {
            $pdf->Cell(30, 5, 'Purpose:', 0, 0);
            $pdf->Cell(0, 5, $purpose_filter, 0, 1);
        }
        if (!empty($date_from)) {
            $pdf->Cell(30, 5, 'Date From:', 0, 0);
            $pdf->Cell(0, 5, $date_from, 0, 1);
        }
        if (!empty($date_to)) {
            $pdf->Cell(30, 5, 'Date To:', 0, 0);
            $pdf->Cell(0, 5, $date_to, 0, 1);
        }
        $pdf->Ln(5);
        
        // Add table headers
        $headers = array('ID', 'IDNO', 'Name', 'Purpose', 'Laboratory', 'Time In', 'Time Out');
        $w = array(20, 30, 60, 50, 30, 45, 45);
        
        // Colors for header row
        $pdf->SetFillColor(240, 255, 240); // Light green background
        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', 'B', 10);
        
        // Header
        for($i = 0; $i < count($headers); $i++) {
            $pdf->Cell($w[$i], 7, $headers[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Reset font for data
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        
        // Data
        foreach($export_records as $record) {
            $pdf->Cell($w[0], 6, $record['ID'], 1, 0, 'C');
            $pdf->Cell($w[1], 6, $record['IDNO'], 1, 0, 'C');
            $pdf->Cell($w[2], 6, $record['Name'], 1, 0, 'L');
            $pdf->Cell($w[3], 6, $record['PURPOSE'], 1, 0, 'L');
            $pdf->Cell($w[4], 6, $record['LABORATORY'], 1, 0, 'C');
            $pdf->Cell($w[5], 6, date("Y-m-d H:i", strtotime($record['TIME_IN'])), 1, 0, 'C');
            $pdf->Cell($w[6], 6, $record['TIME_OUT'] ? date("Y-m-d H:i", strtotime($record['TIME_OUT'])) : 'Still Sitting-in', 1, 0, 'C');
            $pdf->Ln();
        }
        
        // Close and output PDF document
        $pdf->Output($filename, 'D');
        exit;
    }
}

include 'search_modal.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/w3.css">
    <link rel="stylesheet" href="../css/side_nav.css">
    <script src="https://kit.fontawesome.com/bf35ff1032.js" crossorigin="anonymous"></script>
    <title>Sit-in Reports</title>
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

        .filter-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-item {
            flex: 1;
            min-width: 200px;
        }

        .export-buttons {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" style="width:20%;" id="mySidebar">
        <button class="w3-bar-item w3-button w3-large w3-hide-large w3-center" onclick="w3_close()"><i class="fa-solid fa-arrow-left"></i></button>
        <div class="profile w3-center w3-margin w3-padding">
            <?php
            $profile_pic = isset($user['PROFILE_PIC']) ? $user['PROFILE_PIC'] : '../images/default_pic.png';
            ?>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile_pic" style="width: 90px; height:90px; border-radius: 50%; border: 2px solid rgba(100,25,117,1);">
        </div>
        <a href="admin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-house w3-padding"></i><span>Home</span></a>
        <a href="#" onclick="document.getElementById('searchModal').style.display='block'" class="w3-bar-item w3-button"><i class="fa-solid fa-magnifying-glass w3-padding"></i><span>Search</span></a>
        <a href="list.php" class="w3-bar-item w3-button"><i class="fa-solid fa-user w3-padding"></i><span>Students</span></a>
        <a href="currentSitin.php" class="w3-bar-item w3-button"><i class="fa-solid fa-computer w3-padding"></i><span>Sit-in</span></a>
        <a href="SitinReports.php" class="w3-bar-item w3-button active"><i class="fa-solid fa-chart-bar w3-padding"></i><span>Sit-in Reports</span></a>
        <a href="feedback_reports.php" class="w3-bar-item w3-button"><i class="fa-solid fa-comment-dots w3-padding"></i><span>Feedback Reports</span></a>
        <a href="lab_schedule.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar w3-padding"></i><span>Lab Schedule</span></a>
        <a href="lab_resources.php" class="w3-bar-item w3-button"><i class="fa-solid fa-book w3-padding"></i><span>Lab Resources</span></a>
        <a href="reservation_management.php" class="w3-bar-item w3-button"><i class="fa-solid fa-calendar-days w3-padding"></i><span>Reservation</span></a>
        <a href="../logout.php" class="w3-bar-item w3-button"><i class="fa-solid fa-right-to-bracket w3-padding"></i><span>Log Out</span></a>
    </div>

    <div style="margin-left:20%; z-index: 1; position: relative;">
        <div class="title_page w3-container" style="display: flex; align-items: center;">
            <button class="w3-button w3-xlarge w3-hide-large" id="openNav" onclick="w3_open()" style="color: #ffff;">☰</button>
            <h1 style="margin-left: 10px; color: #ffff;">Sit-in Reports</h1>
        </div>

        <div class="w3-container" style="margin: 5% 10px;">
            <!-- Export Buttons -->
            <div class="export-buttons">
                <?php
                // Build query string with current filters
                $export_params = $_GET;
                $filter_query = http_build_query(array_merge($export_params, ['export' => 'csv']));
                $excel_query = http_build_query(array_merge($export_params, ['export' => 'xlsx']));
                $pdf_query = http_build_query(array_merge($export_params, ['export' => 'pdf']));
                ?>
                <a href="?<?php echo $filter_query; ?>" class="w3-button w3-green w3-round-large">CSV</a>
                <a href="?<?php echo $excel_query; ?>" class="w3-button w3-blue w3-round-large">Excel</a>
                <a href="?<?php echo $pdf_query; ?>" class="w3-button w3-red w3-round-large">PDF</a>
            </div>

            <!-- Filters -->
            <form method="GET" class="filter-container">
                <div class="filter-item">
                    <label>Laboratory:</label>
                    <select name="laboratory" class="w3-select w3-border">
                        <option value="">All Laboratories</option>
                        <?php foreach ($laboratories as $lab): ?>
                            <option value="<?php echo htmlspecialchars($lab); ?>" <?php echo $laboratory_filter == $lab ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lab); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label>Purpose:</label>
                    <select name="purpose" class="w3-select w3-border">
                        <option value="">All Purposes</option>
                        <?php foreach ($purposes as $purpose): ?>
                            <option value="<?php echo htmlspecialchars($purpose); ?>" <?php echo $purpose_filter == $purpose ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($purpose); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label>Date From:</label>
                    <input type="date" name="date_from" class="w3-input w3-border" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="filter-item">
                    <label>Date To:</label>
                    <input type="date" name="date_to" class="w3-input w3-border" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="filter-item" style="align-self: flex-end;">
                    <button type="submit" class="w3-button w3-purple w3-round-large">Apply Filters</button>
                    <a href="SitinReports.php" class="w3-button w3-gray w3-round-large">Clear Filters</a>
                </div>
            </form>

            <!-- Records Table -->
            <table class="sitin-table">
                <thead>
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
                <tbody>
                    <?php if (count($sitin_records) > 0): ?>
                        <?php foreach ($sitin_records as $record): ?>
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
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No sit-in records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <?php if (!isset($_GET['export']) && $total_pages > 1): ?>
            <div class="w3-center w3-padding-16">
                <div class="w3-bar">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="w3-button w3-purple">&laquo;</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="w3-button w3-purple">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="w3-button">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = $i == $page ? 'w3-purple' : '';
                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '" class="w3-button ' . $active . '">' . $i . '</a>';
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="w3-button">...</span>';
                        }
                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '" class="w3-button w3-purple">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="w3-button w3-purple">&raquo;</a>
                    <?php endif; ?>
                </div>
                <p class="w3-center">Page <?php echo $page; ?> of <?php echo $total_pages; ?></p>
            </div>
            <?php endif; ?>
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