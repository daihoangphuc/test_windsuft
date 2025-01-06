<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/config.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("
            SELECT * FROM nguoidung
            WHERE TenDangNhap = ?
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Kiểm tra trạng thái tài khoản
            if ($user['TrangThai'] == 0) {
                $_SESSION['flash_message'] = 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên!';
                $_SESSION['flash_type'] = 'error';
                return false;
            }
            
            if (password_verify($password, $user['MatKhauHash'])) {
                // Cập nhật lần truy cập cuối
                $updateStmt = $this->db->prepare("UPDATE nguoidung SET lantruycapcuoi = NOW() WHERE Id = ?");
                $updateStmt->bind_param("i", $user['Id']);
                $updateStmt->execute();
    
                $_SESSION['user_id'] = $user['Id'];
                $_SESSION['username'] = $user['TenDangNhap'];
                $_SESSION['role_id'] = $user['VaiTroId'];
                $_SESSION['position_id'] = $user['ChucVuId'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['avatar'] = $user['anhdaidien'];
                $_SESSION['name'] = $user['HoTen'];
                return true;
            }
        }
        return false;
    }

    public function loginWithGoogle($email) {
        $stmt = $this->db->prepare("SELECT * FROM nguoidung WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            // Kiểm tra trạng thái tài khoản
            if ($user['TrangThai'] == 0) {
                $_SESSION['flash_message'] = 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên!';
                $_SESSION['flash_type'] = 'error';
                return false;
            }
            
            // Cập nhật lần truy cập cuối
            $updateStmt = $this->db->prepare("UPDATE nguoidung SET lantruycapcuoi = NOW() WHERE Id = ?");
            $updateStmt->bind_param("i", $user['Id']);
            $updateStmt->execute();
            
            $_SESSION['user_id'] = $user['Id'];
            $_SESSION['username'] = $user['TenDangNhap'];
            $_SESSION['role_id'] = $user['VaiTroId'];
            $_SESSION['position_id'] = $user['ChucVuId'];
            $_SESSION['email'] = $user['Email'];
            $_SESSION['avatar'] = $user['anhdaidien'];
            $_SESSION['name'] = $user['HoTen'];
            return true;
        }
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
    }

    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT n.*, c.TenChucVu 
            FROM nguoidung n
            LEFT JOIN chucvu c ON n.ChucVuId = c.Id
            WHERE n.Id = ?
        ");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $_SESSION['flash_message'] = 'Vui lòng đăng nhập để tiếp tục!';
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            redirect('/login.php');
        }
    }

    public function requireAdmin() {
        $this->requireLogin();
        if ($_SESSION['role_id'] !== 1) {
            $_SESSION['flash_message'] = 'Bạn không có quyền truy cập trang này!';
            redirect('/');
        }
    }

    public function logout() {
        session_destroy();
        redirect('/login.php');
    }
}
