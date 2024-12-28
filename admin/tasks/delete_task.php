<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['taskId'])) {
        throw new Exception('Task ID is required');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    $taskId = (int)$data['taskId'];

    // Start transaction
    $conn->begin_transaction();

    // Delete task assignments first (foreign key constraint)
    $deleteAssignments = "DELETE FROM phancongnhiemvu WHERE NhiemVuId = ?";
    $stmt = $conn->prepare($deleteAssignments);
    $stmt->bind_param("i", $taskId);
    $stmt->execute();

    // Delete the task
    $deleteTask = "DELETE FROM nhiemvu WHERE Id = ?";
    $stmt = $conn->prepare($deleteTask);
    $stmt->bind_param("i", $taskId);
    $stmt->execute();

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Xóa nhiệm vụ',
        'Thành công',
        "Đã xóa nhiệm vụ ID: $taskId"
    );

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Xóa nhiệm vụ thành công']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
