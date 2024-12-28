<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/classes/Position.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position = new Position();
    $id = $_POST['id'] ?? 0;
    
    $response = ['success' => false];
    
    if ($position->delete($id)) {
        $response['success'] = true;
    } else {
        $response['message'] = 'Không thể xóa chức vụ này vì đang có người dùng sử dụng';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

http_response_code(405);
exit;
