<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireAdmin();

// Nhận dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);
$documentId = $data['documentId'] ?? null;
$permission = $data['permission'] ?? null;

if (!$documentId || !$permission) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

// Khởi tạo kết nối
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Kiểm tra xem đã có quyền cho member chưa
    $stmt = $conn->prepare("SELECT Id FROM phanquyentailieu WHERE TaiLieuId = ? AND VaiTroId = 2");
    $stmt->bind_param("i", $documentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Cập nhật quyền
        $stmt = $conn->prepare("UPDATE phanquyentailieu SET Quyen = ? WHERE TaiLieuId = ? AND VaiTroId = 2");
        $stmt->bind_param("ii", $permission, $documentId);
    } else {
        // Thêm quyền mới
        $stmt = $conn->prepare("INSERT INTO phanquyentailieu (TaiLieuId, VaiTroId, Quyen) VALUES (?, 2, ?)");
        $stmt->bind_param("ii", $documentId, $permission);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu phân quyền']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
