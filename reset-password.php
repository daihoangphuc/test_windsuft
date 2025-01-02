<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/layouts/header.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (!$token) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Verify token
$stmt = $db->prepare("SELECT Id FROM nguoidung WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $_SESSION['flash_error'] = "Link khôi phục mật khẩu không hợp lệ hoặc đã hết hạn!";
    header('Location: forgot-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự!";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp!";
    } else {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("UPDATE nguoidung SET MatKhauHash = ?, reset_token = NULL, reset_expires = NULL WHERE Id = ?");
            $stmt->bind_param("si", $password_hash, $user['Id']);
            
            if ($stmt->execute()) {
                $_SESSION['flash_message'] = "Mật khẩu đã được đặt lại thành công!";
                header('Location: login.php');
                exit;
            } else {
                $error = "Không thể cập nhật mật khẩu. Vui lòng thử lại!";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - CLB HSTV</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.css" rel="stylesheet" />
</head>
<body class="h-full">
<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">Đặt lại mật khẩu</h2>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <?php if ($error): ?>
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form class="space-y-6" action="" method="POST">
            <div>
                <label for="password" class="block text-sm font-medium leading-6 text-gray-900">Mật khẩu mới</label>
                <div class="mt-2">
                    <input id="password" name="password" type="password" required minlength="6"
                           class="block w-full rounded-lg border-gray-300 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                </div>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium leading-6 text-gray-900">Xác nhận mật khẩu</label>
                <div class="mt-2">
                    <input id="confirm_password" name="confirm_password" type="password" required minlength="6"
                           class="block w-full rounded-lg border-gray-300 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                </div>
            </div>

            <div>
                <button type="submit" class="flex w-full justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                    Đặt lại mật khẩu
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
