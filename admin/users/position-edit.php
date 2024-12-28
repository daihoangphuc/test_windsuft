<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/classes/Position.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position = new Position();
    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['position_name'] ?? '');
    
    if (empty($name)) {
        $_SESSION['error'] = 'Tên chức vụ không được để trống';
    } else {
        if ($position->update($id, $name)) {
            $_SESSION['success'] = 'Cập nhật chức vụ thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật chức vụ';
        }
    }
}

header('Location: positions.php');
exit;
