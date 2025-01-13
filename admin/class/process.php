<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/classes/ClassRoom.php';
require_once __DIR__ . '/../../utils/functions.php';

session_start();
$classroom = new ClassRoom($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tenLop = $_POST['tenLop'] ?? '';
    $khoaTruongId = $_POST['khoaTruongId'] ?? '';

    if ($action === 'create' && !empty($tenLop) && !empty($khoaTruongId)) {
        $result = $classroom->create($tenLop, $khoaTruongId);
        if ($result['success']) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thêm lớp học', 'Thành công', "Đã thêm lớp học mới: $tenLop");
            $_SESSION['success'] = $result['message'];
        } else {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thêm lớp học', 'Thất bại', "Lỗi khi thêm lớp học: $tenLop - " . $result['message']);
            $_SESSION['error'] = $result['message'];
        }
        header('Location: index.php');
        exit;
    } elseif ($action === 'update' && !empty($tenLop) && !empty($khoaTruongId)) {
        $id = $_POST['id'] ?? '';
        $result = $classroom->update($id, $tenLop, $khoaTruongId);
        if ($result['success']) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Cập nhật lớp học', 'Thành công', "Đã cập nhật lớp học ID $id: $tenLop");
            $_SESSION['success'] = $result['message'];
        } else {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Cập nhật lớp học', 'Thất bại', "Lỗi khi cập nhật lớp học ID $id: $tenLop - " . $result['message']);
            $_SESSION['error'] = $result['message'];
        }
        header('Location: index.php');
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'delete') {
        $id = $_GET['id'] ?? '';
        // Lấy thông tin lớp học trước khi xóa để ghi log
        $classInfo = $classroom->getById($id);
        $result = $classroom->delete($id);
        
        if ($result['success']) {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Xóa lớp học', 'Thành công', 
                        "Đã xóa lớp học ID $id: " . ($classInfo ? $classInfo['TenLop'] : 'Không tìm thấy tên lớp'));
            $_SESSION['success'] = $result['message'];
        } else {
            log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Xóa lớp học', 'Thất bại', 
                        "Lỗi khi xóa lớp học ID $id: " . ($classInfo ? $classInfo['TenLop'] : 'Không tìm thấy tên lớp') . " - " . $result['message']);
            $_SESSION['error'] = $result['message'];
        }
        header('Location: index.php');
        exit;
    }
}

$_SESSION['error'] = "Có lỗi xảy ra!";
log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['username'], 'Thao tác lớp học', 'Thất bại', "Có lỗi không xác định xảy ra");
header('Location: index.php');
exit;
?>
