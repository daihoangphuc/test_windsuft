<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    
    $task_name = $db->real_escape_string($_POST['task_name']);
    $description = $db->real_escape_string($_POST['description']);
    $start_date = $db->real_escape_string($_POST['start_date']);
    $end_date = $db->real_escape_string($_POST['end_date']);
    
    $query = "INSERT INTO nhiemvu (TenNhiemVu, MoTa, NgayBatDau, NgayKetThuc) 
              VALUES ('$task_name', '$description', '$start_date', '$end_date')";
    
    if ($db->query($query)) {
        $_SESSION['flash_message'] = "Thêm nhiệm vụ thành công!";
    } else {
        $_SESSION['flash_message'] = "Có lỗi xảy ra: " . $db->error;
    }
}

header('Location: index.php');
?>
