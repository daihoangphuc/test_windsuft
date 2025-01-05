<?php
require_once 'config.php';
require_once '../config/auth.php';

// Khởi tạo Auth class
$auth = new Auth();

// Xử lý các request liên quan đến authentication
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($method) {
    case 'POST':
        switch($action) {
            case 'login':
                // Nhận dữ liệu từ request
                $data = json_decode(file_get_contents('php://input'), true);
                $username = $data['username'] ?? '';
                $password = $data['password'] ?? '';

                if(empty($username) || empty($password)) {
                    sendResponse(false, 'Tên đăng nhập và mật khẩu không được để trống');
                }

                // Sử dụng hàm login từ Auth class
                if($auth->login($username, $password)) {
                    sendResponse(true, 'Đăng nhập thành công', [
                        'user_id' => $_SESSION['user_id'],
                        'username' => $_SESSION['username'],
                        'role_id' => $_SESSION['role_id'],
                        'position_id' => $_SESSION['position_id'],
                        'email' => $_SESSION['email'],
                        'avatar' => $_SESSION['avatar'],
                        'name' => $_SESSION['name']
                    ]);
                }
                
                sendResponse(false, 'Tên đăng nhập hoặc mật khẩu không chính xác');
                break;

            case 'logout':
                $auth->logout();
                sendResponse(true, 'Đăng xuất thành công');
                break;

            case 'check-auth':
                if($auth->isLoggedIn()) {
                    $user = $auth->getCurrentUser();
                    sendResponse(true, 'Người dùng đã đăng nhập', [
                        'user_id' => $_SESSION['user_id'],
                        'username' => $_SESSION['username'],
                        'role_id' => $_SESSION['role_id'],
                        'position_id' => $_SESSION['position_id'],
                        'email' => $_SESSION['email'],
                        'avatar' => $_SESSION['avatar'],
                        'name' => $_SESSION['name'],
                        'position_name' => $user['TenChucVu']
                    ]);
                }
                sendResponse(false, 'Chưa đăng nhập');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    case 'GET':
        switch($action) {
            case 'current-user':
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }
                $user = $auth->getCurrentUser();
                if($user) {
                    sendResponse(true, 'Thông tin người dùng', $user);
                }
                sendResponse(false, 'Không tìm thấy thông tin người dùng');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
