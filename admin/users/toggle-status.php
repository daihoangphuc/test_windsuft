<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT);

if (!$user_id || !in_array($status, [0, 1])) {
    $_SESSION['flash_error'] = "Thông tin không hợp lệ!";
    header('Location: index.php');
    exit();
}

$db = Database::getInstance()->getConnection();

try {
    $db->begin_transaction();
    
    // Cập nhật trạng thái
    $stmt = $db->prepare("UPDATE nguoidung SET TrangThai = ? WHERE Id = ?");
    $stmt->bind_param("ii", $status, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Không thể cập nhật trạng thái người dùng!");
    }
    
    // Log hoạt động
    $ip = $_SERVER['REMOTE_ADDR'];
    $admin_username = $_SESSION['username'];
    $action = $status == 1 ? "Mở khóa tài khoản" : "Khóa tài khoản";
    $result = "Thành công";
    $details = "$action cho người dùng ID: $user_id";
    
    $stmt = $db->prepare("INSERT INTO log (IP, NguoiDung, HanhDong, KetQua, ChiTiet) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $ip, $admin_username, $action, $result, $details);
    $stmt->execute();
    
    $db->commit();
    $_SESSION['flash_message'] = $status == 1 ? "Đã mở khóa tài khoản!" : "Đã khóa tài khoản!";
    
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['flash_error'] = $e->getMessage();
}

header('Location: index.php');
exit();
