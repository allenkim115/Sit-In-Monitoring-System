<?php
include '../includes/connect.php';
header('Content-Type: application/json');

$room = $_GET['room'] ?? '';
$date = $_GET['date'] ?? '';
$time_slot = $_GET['time_slot'] ?? '';

if (!$room) {
    echo json_encode([]);
    exit;
}

// Get all PCs and their statuses for the room
$sql = "SELECT pc_number, status FROM pc_status WHERE room_number = ? ORDER BY CAST(SUBSTRING(pc_number, 3) AS UNSIGNED)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $room);
$stmt->execute();
$result = $stmt->get_result();
$pcs = [];
$debug_info = ['Initial PC Status from pc_status table' => []];

while ($row = $result->fetch_assoc()) {
    $debug_info['Initial PC Status from pc_status table'][] = [
        'pc' => $row['pc_number'],
        'original_status' => $row['status']
    ];
    // If PC is marked as 'used' in pc_status, keep it as 'in_use'
    $pcs[$row['pc_number']] = $row['status'] === 'used' ? 'in_use' : $row['status'];
    $debug_info['Initial PC Status from pc_status table'][] = [
        'pc' => $row['pc_number'],
        'converted_status' => $pcs[$row['pc_number']]
    ];
}

// Mark as reserved or used if already reserved for this date/time
if ($date && $time_slot) {
    $debug_info['Reservation Check'] = [
        'room' => $room,
        'date' => $date,
        'time_slot' => $time_slot
    ];
    
    // Remove 'Room ' prefix if it exists for the query
    $room_number = str_replace('Room ', '', $room);
    
    $sql_reserved = "SELECT pc_number, status FROM reservations WHERE room_number = ? AND reservation_date = ? AND time_slot = ? AND status IN ('pending','approved')";
    $stmt2 = $conn->prepare($sql_reserved);
    $stmt2->bind_param('sss', $room_number, $date, $time_slot);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    // Log the SQL query and parameters
    $debug_info['SQL Query'] = [
        'query' => $sql_reserved,
        'parameters' => [
            'room_number' => $room_number,
            'date' => $date,
            'time_slot' => $time_slot
        ],
        'num_rows' => $result2->num_rows
    ];
    
    $debug_info['Reserved PCs'] = [];
    
    while ($row = $result2->fetch_assoc()) {
        $debug_info['Reserved PCs'][] = [
            'pc' => $row['pc_number'],
            'reservation_status' => $row['status'],
            'current_status' => isset($pcs[$row['pc_number']]) ? $pcs[$row['pc_number']] : 'not set'
        ];
        
        // Only update status if PC is not already marked as 'in_use'
        if (!isset($pcs[$row['pc_number']]) || $pcs[$row['pc_number']] !== 'in_use') {
            if ($row['status'] === 'approved') {
                $pcs[$row['pc_number']] = 'in_use';
                $debug_info['Reserved PCs'][] = [
                    'pc' => $row['pc_number'],
                    'action' => 'Updated to in_use (approved reservation)'
                ];
            } else if ($row['status'] === 'pending') {
                $pcs[$row['pc_number']] = 'reserved';
                $debug_info['Reserved PCs'][] = [
                    'pc' => $row['pc_number'],
                    'action' => 'Updated to reserved (pending reservation)'
                ];
            }
        } else {
            $debug_info['Reserved PCs'][] = [
                'pc' => $row['pc_number'],
                'action' => 'Already in_use, status not changed'
            ];
        }
    }
}

// Output as array of objects
$output = [];
$debug_info['Final PC Statuses'] = [];
foreach ($pcs as $pc => $status) {
    $debug_info['Final PC Statuses'][] = [
        'pc' => $pc,
        'status' => $status
    ];
    $output[] = [
        'pc_number' => $pc,
        'status' => $status
    ];
}

// Add debug info to the response
$response = [
    'pcs' => $output,
    'debug' => $debug_info
];

echo json_encode($response); 