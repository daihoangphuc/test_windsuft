<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    if (!isset($_GET['taskId'])) {
        throw new Exception('Task ID is required');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    $taskId = (int)$_GET['taskId'];

    // Get task details
    $query = "SELECT 
                nv.*,
                GROUP_CONCAT(pcnv.NguoiDungId) as AssigneeIds
            FROM nhiemvu nv 
            LEFT JOIN phancongnhiemvu pcnv ON nv.Id = pcnv.NhiemVuId
            WHERE nv.Id = ?
            GROUP BY nv.Id";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();

    if (!$task) {
        throw new Exception('Task not found');
    }

    // Convert assignee IDs string to array
    $task['AssigneeIds'] = $task['AssigneeIds'] ? explode(',', $task['AssigneeIds']) : [];

    echo json_encode(['success' => true, 'data' => $task]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
