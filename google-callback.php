<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/google_config.php';
require_once __DIR__ . '/vendor/autoload.php';

session_start();
$db = Database::getInstance()->getConnection();

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

try {
    // Get token from code
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (!isset($token['error'])) {
        // Get user info
        $client->setAccessToken($token['access_token']);
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $picture = $google_account_info->picture;
        
        // Check if user exists
        $stmt = $db->prepare("SELECT * FROM nguoidung WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            // Update existing user
            $stmt = $db->prepare("UPDATE nguoidung SET LanTruyCapCuoi = NOW() WHERE Id = ?");
            $stmt->bind_param("i", $user['Id']);
            $stmt->execute();
            
            $_SESSION['user_id'] = $user['Id'];
            $_SESSION['username'] = $user['TenDangNhap'];
            $_SESSION['role'] = $user['VaiTro'];
            
            header('Location: index.php');
            exit;
        } else {
            // Create new user
            $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999);
            $password_hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO nguoidung (TenDangNhap, MatKhauHash, HoTen, Email, AnhDaiDien, ChucVuId) VALUES (?, ?, ?, ?, ?, 4)");
            $stmt->bind_param("sssss", $username, $password_hash, $name, $email, $picture);
            
            if ($stmt->execute()) {
                $user_id = $db->insert_id;
                
                // Add default member role
                $stmt = $db->prepare("INSERT INTO vaitronguoidung (VaiTroId, NguoiDungId) VALUES (2, ?)");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'member';
                
                // Redirect to profile completion
                header('Location: profile.php?new=1');
                exit;
            } else {
                throw new Exception("Không thể tạo tài khoản mới");
            }
        }
    } else {
        throw new Exception("Lỗi xác thực Google");
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: login.php');
    exit;
}
