<?php
include '../includes/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['pc_ids']) || !isset($data['status']) || !isset($data['room'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    try {
        // Create placeholders for the IN clause
        $placeholders = str_repeat('?,', count($data['pc_ids']) - 1) . '?';
        
        // Prepare the statement
        $query = "UPDATE pc_status SET status = ? WHERE pc_number IN ($placeholders) AND room_number = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        // Create types string for bind_param
        $types = 's' . str_repeat('s', count($data['pc_ids'])) . 's';
        
        // Create parameters array for bind_param
        $params = array_merge([$data['status']], $data['pc_ids'], [$data['room']]);
        
        // Bind parameters
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 