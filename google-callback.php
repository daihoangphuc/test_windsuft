<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/google_config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/vendor/autoload.php';

session_start();
$db = Database::getInstance()->getConnection();
$auth = new Auth();

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

try {
    // Get token from code
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (!isset($token['error'])) {
        // Get user info
        $client->setAccessToken($token['access_token']);
        $google_oauth = new Google\Service\Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $picture = $google_account_info->picture;
        
        // Check if user exists and try to login
        if ($auth->loginWithGoogle($email)) {
            header('Location: index.php');
            exit;
        }
        
        // If user doesn't exist, create new account
        $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999);
        $password_hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO nguoidung (TenDangNhap, MatKhauHash, HoTen, Email, AnhDaiDien, ChucVuId, VaiTroId, TrangThai) VALUES (?, ?, ?, ?, ?, 4, 2, 1)");
        $stmt->bind_param("sssss", $username, $password_hash, $name, $email, $picture);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role_id'] = 2;
            $_SESSION['position_id'] = 4;
            $_SESSION['email'] = $email;
            $_SESSION['avatar'] = $picture;
            $_SESSION['name'] = $name;
            
            header('Location: profile.php');
            exit;
        } else {
            throw new Exception("Error creating new user");
        }
    } else {
        throw new Exception("Error getting token: " . $token['error']);
    }
} catch (Exception $e) {
    error_log("Google Login Error: " . $e->getMessage());
    $_SESSION['flash_message'] = 'Đăng nhập bằng Google thất bại. Vui lòng thử lại sau!';
    $_SESSION['flash_type'] = 'error';
    header('Location: login.php');
    exit;
}
