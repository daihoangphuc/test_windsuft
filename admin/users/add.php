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
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $default_avatar = '../uploads/users/tvupng.png';
    
    // Chuẩn bị response
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        $db->begin_transaction();
        
        // Validate required fields
        if (empty($username) || empty($password) || empty($email) || empty($fullname)) {
            throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc!");
        }

        // Validate password strength
        if (strlen($password) < 8) {
            throw new Exception("Mật khẩu phải có ít nhất 8 ký tự!");
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email không hợp lệ!");
        }

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
        
        // Kiểm tra mã sinh viên đã tồn tại
        if (!empty($student_id)) {
            $stmt = $db->prepare("SELECT Id FROM nguoidung WHERE MaSinhVien = ?");
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Mã sinh viên đã tồn tại!");
            }
        }

        // Thêm người dùng mới
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO nguoidung (TenDangNhap, MatKhauHash, HoTen, Email, MaSinhVien, VaiTroId, LopHocId, anhdaidien, TrangThai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssssssis", $username, $password_hash, $fullname, $email, $student_id, $role, $class_id, $default_avatar);
        
        if (!$stmt->execute()) {
            throw new Exception("Không thể thêm người dùng!");
        }
        
        $user_id = $db->insert_id;
        
        // Log hoạt động
        $ip = $_SERVER['REMOTE_ADDR'];
        $admin_username = $_SESSION['username'];
        $action = "Thêm người dùng mới";
        $result = "Thành công";
        $details = "Thêm người dùng: $username";
        
        $stmt = $db->prepare("INSERT INTO log (IP, NguoiDung, HanhDong, KetQua, ChiTiet) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $ip, $admin_username, $action, $result, $details);
        $stmt->execute();
        
        $db->commit();
        $response['success'] = true;
        $response['message'] = "Thêm người dùng thành công!";
        
    } catch (Exception $e) {
        $db->rollback();
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Nếu không phải POST request, chuyển hướng về trang index
header('Location: index.php');
exit();
