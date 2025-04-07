<?php
include 'connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if record_id is provided
if (!isset($_GET['record_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Record ID is required']);
    exit;
}

$record_id = (int)$_GET['record_id'];
$student_id = $_SESSION['user']['IDNO'];

// Check if feedback exists
$sql = "SELECT ID FROM feedback WHERE SITIN_RECORD_ID = ? AND STUDENT_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $record_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode([
    'success' => true,
    'has_feedback' => $result->num_rows > 0
]);

$stmt->close();
$conn->close(); 