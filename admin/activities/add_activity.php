<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity = new Activity();
    
    $data = [
        'TenHoatDong' => $_POST['TenHoatDong'] ?? '',
        'MoTa' => $_POST['MoTa'] ?? '',
        'NgayBatDau' => $_POST['NgayBatDau'] ?? '',
        'NgayKetThuc' => $_POST['NgayKetThuc'] ?? '',
        'DiaDiem' => $_POST['DiaDiem'] ?? '',
        'ToaDo' => $_POST['ToaDo'] ?? '',
        'SoLuong' => $_POST['SoLuong'] ?? 0,
        'TrangThai' => $_POST['TrangThai'] ?? 1,
        'NguoiTaoId' => $_SESSION['user_id'] ?? null
    ];

    if ($activity->add($data)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể thêm hoạt động']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
}
