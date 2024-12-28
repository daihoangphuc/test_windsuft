<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get and sanitize input
    $taskId = (int)$_POST['taskId'];
    $taskName = sanitize_input($_POST['taskName']);
    $description = sanitize_input($_POST['description']);
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $status = (int)$_POST['status'];
    $assignees = isset($_POST['assignees']) ? $_POST['assignees'] : [];

    // Validate input
    if (empty($taskId) || empty($taskName) || empty($startDate) || empty($endDate)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
    }

    // Start transaction
    $conn->begin_transaction();

    // Update task
    $query = "UPDATE nhiemvu SET TenNhiemVu = ?, MoTa = ?, NgayBatDau = ?, NgayKetThuc = ?, TrangThai = ? WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssii", $taskName, $description, $startDate, $endDate, $status, $taskId);
    $stmt->execute();

    // Update assignments
    // First, remove all current assignments
    $deleteAssign = "DELETE FROM phancongnhiemvu WHERE NhiemVuId = ?";
    $stmt = $conn->prepare($deleteAssign);
    $stmt->bind_param("i", $taskId);
    $stmt->execute();

    // Then add new assignments
    if (!empty($assignees)) {
        $assignQuery = "INSERT INTO phancongnhiemvu (NhiemVuId, NguoiDungId, NguoiPhanCong) VALUES (?, ?, ?)";
        $assignStmt = $conn->prepare($assignQuery);
        
        foreach ($assignees as $userId) {
            $nguoiPhanCong = $_SESSION['user_id'];
            $assignStmt->bind_param("iis", $taskId, $userId, $nguoiPhanCong);
            $assignStmt->execute();
            
            // Send notification to newly assigned user
            $notificationTitle = "Cập nhật nhiệm vụ";
            $notificationMessage = "Bạn được phân công nhiệm vụ: " . $taskName;
            send_notification($userId, $notificationTitle, $notificationMessage);
        }
    }

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Cập nhật nhiệm vụ',
        'Thành công',
        "Đã cập nhật nhiệm vụ: $taskName"
    );

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Cập nhật nhiệm vụ thành công']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
