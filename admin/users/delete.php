<?php
require_once '../../config/database.php';
session_start();

if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['flash_error'] = "ID người dùng không hợp lệ";
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$userId = $_POST['id'];

// Kiểm tra khóa ngoại trong các bảng liên quan
$relatedTables = [
    ['table' => 'danhsachdangky', 'column' => 'NguoiDungId', 'description' => 'đăng ký hoạt động'],
    ['table' => 'danhsachthamgia', 'column' => 'NguoiDungId', 'description' => 'tham gia hoạt động'],
    ['table' => 'hoatdong', 'column' => 'NguoiTaoId', 'description' => 'hoạt động (với tư cách người tạo)'],
    ['table' => 'phancongnhiemvu', 'column' => 'NguoiDungId', 'description' => 'nhiệm vụ được phân công']
];

$dependencies = [];

foreach ($relatedTables as $table) {
    try {
        $sql = "SELECT COUNT(*) as count FROM {$table['table']} WHERE {$table['column']} = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            $dependencies[] = "- Có $count {$table['description']}";
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Lỗi khi kiểm tra ràng buộc: " . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}

if (!empty($dependencies)) {
    $_SESSION['flash_error'] = "Không thể xóa người dùng này vì còn các ràng buộc sau:<br>" . implode("<br>", $dependencies);
    header('Location: index.php');
    exit;
}

// Nếu không có ràng buộc, tiến hành xóa
try {
    $stmt = $db->prepare("DELETE FROM nguoidung WHERE Id = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Đã xóa người dùng thành công";
    } else {
        $_SESSION['flash_error'] = "Không thể xóa người dùng: " . $db->error;
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = "Lỗi khi xóa người dùng: " . $e->getMessage();
}

header('Location: index.php');
exit;
