<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Task.php';

$auth = new Auth();
$auth->requireAdmin();

$task = new Task();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'TenNhiemVu' => $_POST['tenNhiemVu'],
                'MoTa' => $_POST['moTa'],
                'NgayBatDau' => $_POST['ngayBatDau'],
                'NgayKetThuc' => $_POST['ngayKetThuc'],
                'TrangThai' => 0 // Mặc định là chưa bắt đầu
            ];
            $result = $task->create($data);
            $_SESSION[($result['success'] ? 'flash_message' : 'flash_error')] = $result['message'];
            break;

        case 'delete':
            if (isset($_POST['id'])) {
                $result = $task->delete($_POST['id']);
                $_SESSION[($result['success'] ? 'flash_message' : 'flash_error')] = $result['message'];
            }
            break;
            
        case 'update':
            if (isset($_POST['id'])) {
                $data = [
                    'TenNhiemVu' => $_POST['tenNhiemVu'],
                    'MoTa' => $_POST['moTa'],
                    'NgayBatDau' => $_POST['ngayBatDau'],
                    'NgayKetThuc' => $_POST['ngayKetThuc'],
                    'TrangThai' => $_POST['trangThai']
                ];
                $result = $task->update($_POST['id'], $data);
                $_SESSION[($result['success'] ? 'flash_message' : 'flash_error')] = $result['message'];
            }
            break;

        case 'updateStatus':
            if (isset($_POST['taskId']) && isset($_POST['trangThai'])) {
                $data = [
                    'TrangThai' => $_POST['trangThai']
                ];
                $result = $task->update($_POST['taskId'], $data);
                $_SESSION[($result['success'] ? 'flash_message' : 'flash_error')] = $result['message'];
            }
            break;

        case 'assign':
            if (isset($_POST['taskId']) && isset($_POST['nguoiDungId'])) {
                // Xóa phân công cũ nếu có
                $sql = "DELETE FROM phancongnhiemvu WHERE NhiemVuId = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $_POST['taskId']);
                $stmt->execute();

                // Thêm phân công mới
                $sql = "INSERT INTO phancongnhiemvu (NguoiDungId, NhiemVuId, NguoiPhanCong) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $nguoiPhanCong = $_SESSION['user_id'];
                $stmt->bind_param("iis", $_POST['nguoiDungId'], $_POST['taskId'], $nguoiPhanCong);
                
                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = "Phân công nhiệm vụ thành công!";
                } else {
                    $_SESSION['flash_error'] = "Lỗi khi phân công nhiệm vụ: " . $stmt->error;
                }
            }
            break;
    }
}

header('Location: index.php');
exit;
