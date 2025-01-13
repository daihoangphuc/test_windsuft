<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../utils/functions.php';

$auth = new Auth();
$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $tenChucVu = trim($_POST['tenChucVu']);
    
    if (!empty($tenChucVu) && $id > 0) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Kiểm tra trùng tên chức vụ (không tính chính nó)
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM chucvu WHERE TenChucVu = ? AND Id != ?");
        $checkStmt->bind_param("si", $tenChucVu, $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Cập nhật chức vụ', 'Thất bại', "Tên chức vụ '$tenChucVu' đã tồn tại");
            header('Location: index.php?error=duplicate');
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE chucvu SET TenChucVu = ? WHERE Id = ?");
        $stmt->bind_param("si", $tenChucVu, $id);
        
        if ($stmt->execute()) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Cập nhật chức vụ', 'Thành công', "Đã cập nhật chức vụ ID $id: $tenChucVu");
            header('Location: index.php?success=edit');
        } else {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Cập nhật chức vụ', 'Thất bại', "Lỗi khi cập nhật chức vụ ID $id: $tenChucVu");
            header('Location: index.php?error=edit');
        }
    } else {
        log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Cập nhật chức vụ', 'Thất bại', "ID hoặc tên chức vụ không hợp lệ");
        header('Location: index.php?error=empty');
    }
} else {
    header('Location: index.php');
}
exit();
