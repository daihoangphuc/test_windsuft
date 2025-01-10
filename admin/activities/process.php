<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireAdmin();

$activity = new Activity();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete':
            if (isset($_POST['id'])) {
                $result = $activity->delete($_POST['id']);
                $_SESSION[($result['success'] ? 'flash_message' : 'flash_error')] = $result['message'];
            }
            break;
            
        // Thêm các case khác cho create, update nếu cần
    }
}

header('Location: index.php');
exit;
