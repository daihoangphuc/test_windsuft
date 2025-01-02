<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/mail.php';
require_once __DIR__ . '/layouts/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $_SESSION['flash_error'] = "Email không hợp lệ!";
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if email exists
            $stmt = $db->prepare("SELECT Id, HoTen FROM nguoidung WHERE Email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save token to database
                $stmt = $db->prepare("UPDATE nguoidung SET reset_token = ?, reset_expires = ? WHERE Email = ?");
                $stmt->bind_param("sss", $token, $expires, $email);
                
                if ($stmt->execute()) {
                    // Send reset email
                    $reset_link = "http://{$_SERVER['HTTP_HOST']}/test_windsuft/reset-password.php?token=" . $token;
                    $mailer = Mailer::getInstance();
                    if ($mailer->sendPasswordReset($email, $token)) {
                        // Log activity
                        require_once __DIR__ . '/utils/functions.php';
                        log_activity(
                            $_SERVER['REMOTE_ADDR'],
                            $user['Id'],
                            'Yêu cầu khôi phục mật khẩu',
                            'Thành công',
                            "Đã gửi email khôi phục mật khẩu"
                        );
                        
                        $_SESSION['flash_message'] = "Hướng dẫn khôi phục mật khẩu đã được gửi đến email {$email} của bạn. Vui lòng kiểm tra hòm thư trong vòng 1 giờ.";
                    } else {
                        throw new Exception("Không thể gửi email khôi phục mật khẩu");
                    }
                } else {
                    throw new Exception("Không thể tạo yêu cầu khôi phục mật khẩu");
                }
            } else {
                // Don't reveal if email exists or not
                $_SESSION['flash_message'] = "Nếu email tồn tại trong hệ thống, bạn sẽ nhận được hướng dẫn khôi phục mật khẩu.";
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
    }
    
    header('Location: forgot-password.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - CLB HSTV</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.css" rel="stylesheet" />
</head>
<body class="h-full">
<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">Quên mật khẩu</h2>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['flash_error']);
                unset($_SESSION['flash_error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['flash_message']);
                unset($_SESSION['flash_message']);
                ?>
            </div>
        <?php endif; ?>

        <form class="space-y-6" action="" method="POST">
            <div>
                <label for="email" class="block text-sm font-medium leading-6 text-gray-900">Email</label>
                <div class="mt-2">
                    <input id="email" name="email" type="email" required class="block w-full rounded-lg border-gray-300 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                </div>
            </div>

            <div>
                <button type="submit" class="flex w-full justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                    Gửi yêu cầu khôi phục
                </button>
            </div>
        </form>

        <p class="mt-10 text-center text-sm text-gray-500">
            <a href="login.php" class="font-semibold leading-6 text-primary-600 hover:text-primary-500">Quay lại đăng nhập</a>
        </p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>
</body>
</html>
