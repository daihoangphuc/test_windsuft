<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../utils/functions.php';

$auth = new Auth();
$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenChucVu = trim($_POST['tenChucVu']);
    
    if (!empty($tenChucVu)) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Kiểm tra trùng tên chức vụ
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM chucvu WHERE TenChucVu = ?");
        $checkStmt->bind_param("s", $tenChucVu);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thêm chức vụ', 'Thất bại', "Tên chức vụ '$tenChucVu' đã tồn tại");
            header('Location: index.php?error=duplicate');
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO chucvu (TenChucVu) VALUES (?)");
        $stmt->bind_param("s", $tenChucVu);
        
        if ($stmt->execute()) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thêm chức vụ', 'Thành công', "Đã thêm chức vụ: $tenChucVu");
            header('Location: index.php?success=add');
        } else {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thêm chức vụ', 'Thất bại', "Lỗi khi thêm chức vụ: $tenChucVu");
            header('Location: index.php?error=add');
        }
    } else {
        log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thêm chức vụ', 'Thất bại', "Tên chức vụ không được để trống");
        header('Location: index.php?error=empty');
    }
} else {
    header('Location: index.php');
}
exit();
