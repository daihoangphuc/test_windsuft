<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new Auth();
$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $role = filter_input(INPUT_POST, 'role', FILTER_VALIDATE_INT);
    
    try {
        $db->begin_transaction();
        
        // Kiểm tra username đã tồn tại
        $stmt = $db->prepare("SELECT Id FROM nguoidung WHERE TenDangNhap = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Tên đăng nhập đã tồn tại!");
        }
        
        // Kiểm tra email đã tồn tại
        $stmt = $db->prepare("SELECT Id FROM nguoidung WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email đã được sử dụng!");
        }
        
        // Thêm người dùng mới
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO nguoidung (TenDangNhap, MatKhauHash, HoTen, Email, MaSinhVien) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password_hash, $fullname, $email, $student_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Không thể thêm người dùng!");
        }
        
        $user_id = $db->insert_id;
        
        // Gán vai trò cho người dùng
        $stmt = $db->prepare("INSERT INTO vaitronguoidung (NguoiDungId, VaiTroId) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $role);
        
        if (!$stmt->execute()) {
            throw new Exception("Không thể gán vai trò cho người dùng!");
        }
        
        // Log hoạt động
        $ip = $_SERVER['REMOTE_ADDR'];
        $admin_username = $_SESSION['username'];
        $action = "Thêm người dùng mới";
        $result = "Thành công";
        $details = "Thêm người dùng: $username";
        
        $stmt = $db->prepare("INSERT INTO nhatkyhoatdong (IP, NguoiDung, HanhDong, KetQua, ChiTiet) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $ip, $admin_username, $action, $result, $details);
        $stmt->execute();
        
        $db->commit();
        $_SESSION['flash_message'] = "Thêm người dùng thành công!";
        header('Location: index.php');
        exit();
        
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['flash_error'] = $e->getMessage();
        header('Location: index.php');
        exit();
    }
}

header('Location: index.php');
exit();
