<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/auth.php';

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Test database connection
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    $query = "SELECT 
        Id,
        TenHoatDong as title,
        NgayBatDau as start,
        NgayKetThuc as end,
        DiaDiem as location,
        CASE 
            WHEN TrangThai = 0 THEN '#3B82F6' 
            WHEN TrangThai = 1 THEN '#10B981'
            ELSE '#6B7280'
        END as backgroundColor
    FROM hoatdong
    WHERE TrangThai IN (0, 1)
    ORDER BY NgayBatDau ASC";

    // Debug query
    error_log("Executing query: " . $query);

    $stmt = $db->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }

    $stmt->execute();
    if ($stmt->error) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $events = [];
    
    while ($row = $result->fetch_assoc()) {
        $event = [
            'id' => $row['Id'],
            'title' => $row['title'],
            'start' => $row['start'],
            'end' => $row['end'],
            'extendedProps' => [
                'location' => $row['location']
            ],
            'backgroundColor' => $row['backgroundColor'],
            'borderColor' => $row['backgroundColor']
        ];
        $events[] = $event;
    }

    // Debug output
    error_log("Number of events found: " . count($events));
    error_log("Events data: " . json_encode($events));

    echo json_encode($events);
} catch (Exception $e) {
    error_log("Error in get_calendar_events.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
