<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    
    $task_id = (int)$_POST['task_id'];
    $status = (int)$_POST['status'];
    
    $query = "UPDATE nhiemvu SET TrangThai = ? WHERE Id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $status, $task_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $db->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
