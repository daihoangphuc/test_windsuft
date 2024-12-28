<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    
    $task_id = (int)$_POST['task_id'];
    $user_id = (int)$_POST['user_id'];
    $nguoi_phan_cong = $_SESSION['user_id'] ?? 'admin';
    
    // Xóa phân công cũ nếu có
    $delete_query = "DELETE FROM phancongnhiemvu WHERE NhiemVuId = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bind_param("i", $task_id);
    $delete_stmt->execute();
    
    // Thêm phân công mới
    $insert_query = "INSERT INTO phancongnhiemvu (NhiemVuId, NguoiDungId, NguoiPhanCong) VALUES (?, ?, ?)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bind_param("iis", $task_id, $user_id, $nguoi_phan_cong);
    
    if ($insert_stmt->execute()) {
        $_SESSION['flash_message'] = "Phân công nhiệm vụ thành công!";
    } else {
        $_SESSION['flash_message'] = "Có lỗi xảy ra: " . $db->error;
    }
}

header('Location: index.php');
