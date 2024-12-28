<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $activity = new Activity();
    $db = Database::getInstance()->getConnection();
    
    // Lấy thông tin hoạt động trước khi xóa
    $activity_data = $activity->get($id);
    
    if (!$activity_data) {
        throw new Exception("Không tìm thấy hoạt động");
    }
    
    // Xóa hoạt động
    if ($activity->delete($id)) {
        // Log hoạt động
        $ip = $_SERVER['REMOTE_ADDR'];
        $admin_username = $_SESSION['username'];
        $action = "Xóa hoạt động";
        $result = "Thành công";
        $details = "Xóa hoạt động: " . $activity_data['TenHoatDong'];
        
        $stmt = $db->prepare("INSERT INTO log (IP, NguoiDung, HanhDong, KetQua, ChiTiet) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $ip, $admin_username, $action, $result, $details);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Không thể xóa hoạt động");
    }
} catch (Exception $e) {
    // Log lỗi
    $ip = $_SERVER['REMOTE_ADDR'];
    $admin_username = $_SESSION['username'];
    $action = "Xóa hoạt động";
    $result = "Thất bại";
    $details = "Lỗi: " . $e->getMessage();
    
    $stmt = $db->prepare("INSERT INTO log (IP, NguoiDung, HanhDong, KetQua, ChiTiet) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $ip, $admin_username, $action, $result, $details);
    $stmt->execute();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
