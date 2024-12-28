<?php
require_once 'config/database.php';
require_once 'utils/functions.php';

session_start();

// If user is already logged in, redirect to home page
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        $db = Database::getInstance()->getConnection();
        
        // Check if email exists
        $stmt = $db->prepare("SELECT Id, HoTen FROM nguoidung WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token to database
            $stmt = $db->prepare("
                INSERT INTO reset_password (NguoiDungId, Token, NgayHetHan) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iss", $user['Id'], $token, $expires);
            $stmt->execute();
            
            // Send reset email
            $reset_link = "http://{$_SERVER['HTTP_HOST']}/test_windsuft/reset_password.php?token=" . $token;
            $to = $email;
            $subject = "Khôi phục mật khẩu - CLB HSTV";
            $message = "
                <html>
                <head>
                    <title>Khôi phục mật khẩu</title>
                </head>
                <body>
                    <h2>Xin chào {$user['HoTen']},</h2>
                    <p>Chúng tôi nhận được yêu cầu khôi phục mật khẩu từ bạn.</p>
                    <p>Vui lòng click vào link bên dưới để đặt lại mật khẩu:</p>
                    <p><a href='{$reset_link}'>{$reset_link}</a></p>
                    <p>Link này sẽ hết hạn sau 1 giờ.</p>
                    <p>Nếu bạn không yêu cầu khôi phục mật khẩu, vui lòng bỏ qua email này.</p>
                    <br>
                    <p>Trân trọng,</p>
                    <p>CLB HSTV</p>
                </body>
                </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: CLB HSTV <noreply@clbhstv.com>" . "\r\n";
            
            if (mail($to, $subject, $message, $headers)) {
                $success = 'Hướng dẫn khôi phục mật khẩu đã được gửi đến email của bạn.';
                
                // Log activity
                log_activity(
                    $_SERVER['REMOTE_ADDR'],
                    $user['Id'],
                    'Yêu cầu khôi phục mật khẩu',
                    'Thành công',
                    "Đã gửi email khôi phục mật khẩu"
                );
            } else {
                $error = 'Không thể gửi email. Vui lòng thử lại sau.';
            }
        } else {
            $error = 'Email không tồn tại trong hệ thống.';
        }
    } else {
        $error = 'Email không hợp lệ.';
    }
}

$pageTitle = "Quên mật khẩu";
ob_start();
?>

<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">
            Quên mật khẩu
        </h2>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <?php if ($error): ?>
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php else: ?>
            <form class="space-y-6" method="POST">
                <div>
                    <label for="email" class="block text-sm font-medium leading-6 text-gray-900">
                        Email
                    </label>
                    <div class="mt-2">
                        <input id="email" name="email" type="email" required 
                               class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        Gửi yêu cầu khôi phục
                    </button>
                </div>
            </form>

            <p class="mt-10 text-center text-sm text-gray-500">
                Đã nhớ mật khẩu?
                <a href="login.php" class="font-semibold leading-6 text-indigo-600 hover:text-indigo-500">
                    Đăng nhập
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
require_once 'layouts/auth_layout.php';
?>
