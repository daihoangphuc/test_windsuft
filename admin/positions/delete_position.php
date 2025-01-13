<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../utils/functions.php';

$auth = new Auth();
$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['position_id'])) {
    $positionId = intval($_POST['position_id']);
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Kiểm tra xem chức vụ có được sử dụng trong bảng nguoidung không
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM nguoidung WHERE ChucVuId = ?");
        $checkStmt->bind_param("i", $positionId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            http_response_code(400);
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Xóa chức vụ', 'Thất bại', "Không thể xóa chức vụ ID $positionId vì đang có $count người dùng đang sử dụng");
            echo json_encode([
                'success' => false,
                'message' => 'Không thể xóa chức vụ này vì đang có ' . $count . ' người dùng đang giữ chức vụ này.'
            ]);
            exit;
        }
        
        // Nếu không có ràng buộc khóa ngoại, tiến hành xóa
        $deleteStmt = $conn->prepare("DELETE FROM chucvu WHERE Id = ?");
        $deleteStmt->bind_param("i", $positionId);
        
        if ($deleteStmt->execute()) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Xóa chức vụ', 'Thành công', "Đã xóa chức vụ ID $positionId");
            echo json_encode([
                'success' => true,
                'message' => 'Xóa chức vụ thành công!'
            ]);
        } else {
            throw new Exception("Lỗi khi xóa chức vụ");
        }
    } catch (Exception $e) {
        http_response_code(500);
        log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Xóa chức vụ', 'Thất bại', "Lỗi khi xóa chức vụ ID $positionId: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Yêu cầu không hợp lệ'
    ]);
}
