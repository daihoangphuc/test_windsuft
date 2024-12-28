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
$valid_token = false;
$token = $_GET['token'] ?? '';

if (!empty($token)) {
    $db = Database::getInstance()->getConnection();
    
    // Verify token and check expiration
    $stmt = $db->prepare("
        SELECT r.NguoiDungId, n.Email, n.HoTen
        FROM reset_password r
        JOIN nguoidung n ON r.NguoiDungId = n.Id
        WHERE r.Token = ? 
        AND r.NgayHetHan > NOW()
        AND r.DaSuDung = 0
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $valid_token = true;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (strlen($password) < 8) {
                $error = 'Mật khẩu phải có ít nhất 8 ký tự.';
            } elseif ($password !== $confirm_password) {
                $error = 'Mật khẩu xác nhận không khớp.';
            } else {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE nguoidung SET MatKhau = ? WHERE Id = ?");
                $stmt->bind_param("si", $hashed_password, $user['NguoiDungId']);
                
                if ($stmt->execute()) {
                    // Mark token as used
                    $stmt = $db->prepare("UPDATE reset_password SET DaSuDung = 1 WHERE Token = ?");
                    $stmt->bind_param("s", $token);
                    $stmt->execute();
                    
                    $success = 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập.';
                    
                    // Log activity
                    log_activity(
                        $_SERVER['REMOTE_ADDR'],
                        $user['NguoiDungId'],
                        'Đặt lại mật khẩu',
                        'Thành công',
                        "Đã đặt lại mật khẩu thành công"
                    );
                } else {
                    $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
                }
            }
        }
    } else {
        $error = 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
    }
} else {
    $error = 'Token không hợp lệ.';
}

$pageTitle = "Đặt lại mật khẩu";
ob_start();
?>

<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">
            Đặt lại mật khẩu
        </h2>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <?php if ($error): ?>
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php if (!$valid_token): ?>
                <p class="mt-4 text-center text-sm text-gray-500">
                    <a href="forgot_password.php" class="font-semibold leading-6 text-indigo-600 hover:text-indigo-500">
                        Yêu cầu link mới
                    </a>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <p class="mt-4 text-center text-sm text-gray-500">
                <a href="login.php" class="font-semibold leading-6 text-indigo-600 hover:text-indigo-500">
                    Đăng nhập
                </a>
            </p>
        <?php elseif ($valid_token): ?>
            <form class="space-y-6" method="POST">
                <div>
                    <label for="password" class="block text-sm font-medium leading-6 text-gray-900">
                        Mật khẩu mới
                    </label>
                    <div class="mt-2">
                        <input id="password" name="password" type="password" required minlength="8"
                               class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium leading-6 text-gray-900">
                        Xác nhận mật khẩu
                    </label>
                    <div class="mt-2">
                        <input id="confirm_password" name="confirm_password" type="password" required minlength="8"
                               class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        Đặt lại mật khẩu
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
require_once 'layouts/auth_layout.php';
?>
