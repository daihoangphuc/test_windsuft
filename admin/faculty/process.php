<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/classes/Faculty.php';
require_once __DIR__ . '/../../utils/functions.php';

session_start();
$faculty = new Faculty($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tenKhoaTruong = $_POST['tenKhoaTruong'] ?? '';

    if ($action === 'create' && !empty($tenKhoaTruong)) {
        // Kiểm tra trùng tên khoa trưởng
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM khoatruong WHERE TenKhoaTruong = ?");
        $stmt->bind_param("s", $tenKhoaTruong);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thêm khoa trưởng', 'Thất bại', "Tên khoa trưởng '$tenKhoaTruong' đã tồn tại");
            header('Location: index.php?error=duplicate');
            exit;
        }

        if ($faculty->create($tenKhoaTruong)) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thêm khoa trưởng', 'Thành công', "Đã thêm khoa trưởng: $tenKhoaTruong");
            header('Location: index.php?success=create');
            exit;
        } else {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thêm khoa trưởng', 'Thất bại', "Lỗi khi thêm khoa trưởng: $tenKhoaTruong");
        }
    } elseif ($action === 'update' && !empty($tenKhoaTruong)) {
        $id = $_POST['id'] ?? '';
        
        // Kiểm tra trùng tên khoa trưởng (không tính chính nó)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM khoatruong WHERE TenKhoaTruong = ? AND Id != ?");
        $stmt->bind_param("si", $tenKhoaTruong, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Cập nhật khoa trưởng', 'Thất bại', "Tên khoa trưởng '$tenKhoaTruong' đã tồn tại");
            header('Location: index.php?error=duplicate');
            exit;
        }

        if ($faculty->update($id, $tenKhoaTruong)) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Cập nhật khoa trưởng', 'Thành công', "Đã cập nhật khoa trưởng ID $id: $tenKhoaTruong");
            header('Location: index.php?success=update');
            exit;
        } else {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Cập nhật khoa trưởng', 'Thất bại', "Lỗi khi cập nhật khoa trưởng ID $id: $tenKhoaTruong");
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'delete') {
        $id = $_GET['id'] ?? '';
        
        // Kiểm tra xem có người dùng nào đang sử dụng khoa trưởng này không
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM nguoidung WHERE KhoaTruongId = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Xóa khoa trưởng', 'Thất bại', "Không thể xóa khoa trưởng ID $id vì đang có $count người dùng đang sử dụng");
            header('Location: index.php?error=in_use');
            exit;
        }

        if ($faculty->delete($id)) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Xóa khoa trưởng', 'Thành công', "Đã xóa khoa trưởng ID $id");
            header('Location: index.php?success=delete');
            exit;
        } else {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Xóa khoa trưởng', 'Thất bại', "Lỗi khi xóa khoa trưởng ID $id");
            header('Location: index.php?error=delete');
            exit;
        }
    }
}

log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thao tác khoa trưởng', 'Thất bại', 'Lỗi không xác định');
header('Location: index.php?error=general');
exit;
?>
