<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $activity = new Activity();
    $data = $activity->get($_GET['id']);
    
    if ($data) {
        // Định dạng lại ngày giờ để phù hợp với input datetime-local
        $data['NgayBatDau'] = date('Y-m-d\TH:i', strtotime($data['NgayBatDau']));
        $data['NgayKetThuc'] = date('Y-m-d\TH:i', strtotime($data['NgayKetThuc']));
        
        echo json_encode([
            'success' => true, 
            'activity' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Không tìm thấy hoạt động'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Phương thức không được hỗ trợ hoặc thiếu ID'
    ]);
}
