<?php
include 'connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['recordId']) || !isset($data['rating']) || !isset($data['comment'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$recordId = (int)$data['recordId'];
$rating = (int)$data['rating'];
$comment = trim($data['comment']);
$studentId = $_SESSION['user']['IDNO'];

// Validate rating
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit;
}

// Check if feedback already exists
$check_sql = "SELECT ID FROM feedback WHERE SITIN_RECORD_ID = ? AND STUDENT_ID = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $recordId, $studentId);
$check_stmt->execute();
$existing_feedback = $check_stmt->get_result()->fetch_assoc();

if ($existing_feedback) {
    // Update existing feedback
    $sql = "UPDATE feedback SET RATING = ?, COMMENT = ? WHERE SITIN_RECORD_ID = ? AND STUDENT_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isis", $rating, $comment, $recordId, $studentId);
} else {
    // Insert new feedback
    $sql = "INSERT INTO feedback (SITIN_RECORD_ID, STUDENT_ID, RATING, COMMENT) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isis", $recordId, $studentId, $rating, $comment);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error submitting feedback']);
}

$stmt->close();
$conn->close(); 