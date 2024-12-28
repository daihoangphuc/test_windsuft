<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $activity = new Activity();
    
    if ($activity->delete($_GET['id'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa hoạt động']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ hoặc thiếu ID']);
}
