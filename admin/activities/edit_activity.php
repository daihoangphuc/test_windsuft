<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $activity = new Activity();
    
    $data = [
        'TenHoatDong' => $_POST['TenHoatDong'] ?? '',
        'MoTa' => $_POST['MoTa'] ?? '',
        'NgayBatDau' => $_POST['NgayBatDau'] ?? '',
        'NgayKetThuc' => $_POST['NgayKetThuc'] ?? '',
        'DiaDiem' => $_POST['DiaDiem'] ?? '',
        'ToaDo' => $_POST['ToaDo'] ?? '',
        'SoLuong' => $_POST['SoLuong'] ?? 0,
        'TrangThai' => $_POST['TrangThai'] ?? 1
    ];

    if ($activity->update($_GET['id'], $data)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật hoạt động']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ hoặc thiếu ID']);
}
