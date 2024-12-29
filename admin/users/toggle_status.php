<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/User.php';

$auth = new Auth();
$auth->requireAdmin();

// Kiểm tra method và user_id
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$user_id) {
    http_response_code(400);
    die('Invalid User ID');
}

try {
    $user = new User();
    $current_user = $user->getById($user_id);
    
    if (!$current_user) {
        throw new Exception('User not found');
    }
    
    if ($user->toggleStatus($user_id)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'new_status' => $current_user['TrangThai'] == 1 ? 0 : 1
        ]);
    } else {
        throw new Exception('Failed to update status');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
