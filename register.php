<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/path.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    
    $error = '';
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_STRING);
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    
    // Validate input
    if (!$username || !$password || !$email || !$fullname || !$student_id || !$class_id) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } else {
        // Check if username exists
        $stmt = $db->prepare("SELECT Id FROM nguoidung WHERE TenDangNhap = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Tên đăng nhập đã tồn tại!';
        }
        
        // Check if email exists
        $stmt = $db->prepare("SELECT Id FROM nguoidung WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email đã được sử dụng!';
        }

        // Check if student_id exists
        $stmt = $db->prepare("SELECT Id FROM nguoidung WHERE MaSinhVien = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Mã sinh viên đã được sử dụng!';
        }
        
        if (!$error) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $default_role = 2; // Member role
            $default_position = 4; // Thành viên
            $status = 1; // Active status
            $default_avatar = '../images/Users/tvupng.png';
            
            $stmt = $db->prepare("INSERT INTO nguoidung (MaSinhVien, TenDangNhap, MatKhauHash, HoTen, Email, anhdaidien, ChucVuId, LopHocId, TrangThai, VaiTroId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiiis", $student_id, $username, $password_hash, $fullname, $email, $default_avatar, $default_position, $class_id, $status, $default_role);
            
            if ($stmt->execute()) {
                $_SESSION['flash_message'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
                header('Location: ' . base_url('/login.php'));
                exit();
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại!';
            }
        }
    }
}

// Lấy danh sách lớp học
$db = Database::getInstance()->getConnection();
$classes = [];
$query = "SELECT Id, TenLop FROM lophoc ORDER BY TenLop";
$result = $db->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

$pageTitle = 'Đăng ký';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                Tạo tài khoản mới
            </h2>
            <p class="text-gray-600 text-sm">
                Điền thông tin của bạn để bắt đầu
            </p>
        </div>

        <?php if (isset($error) && $error): ?>
            <div class="mt-4 bg-red-50 text-red-700 p-4 rounded-lg text-sm flex items-center" role="alert">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="<?php echo base_url('/register.php'); ?>" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Mã sinh viên
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-mkdir -p d:/1XAMP/htdocs/test_windsuft/uploads/users-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <input id="student_id" name="student_id" type="text" required 
                            class="appearance-none rounded-lg relative block w-full pl-10 pr-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Nhập mã sinh viên">
                    </div>
                </div>

                <div>
                    <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">
                        Họ và tên
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <input id="fullname" name="fullname" type="text" required 
                            class="appearance-none rounded-lg relative block w-full pl-10 pr-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Nhập họ và tên">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                        </div>
                        <input id="email" name="email" type="email" required 
                            class="appearance-none rounded-lg relative block w-full pl-10 pr-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Nhập địa chỉ email">
                    </div>
                </div>

                <div>
                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Lớp
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                            </svg>
                        </div>
                        <select id="class_id" name="class_id" required 
                            class="appearance-none rounded-lg relative block w-full pl-10 pr-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                            <option value="">Chọn lớp</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['Id']; ?>"><?php echo htmlspecialchars($class['TenLop']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                        Tên đăng nhập
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <input id="username" name="username" type="text" required 
                            class="appearance-none rounded-lg relative block w-full pl-10 pr-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Chọn tên đăng nhập">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Mật khẩu
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <input id="password" name="password" type="password" required 
                            class="appearance-none rounded-lg relative block w-full pl-10 pr-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Tạo mật khẩu">
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                        Xác nhận mật khẩu
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                            class="appearance-none rounded-lg relative block w-full pl-10 pr-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Nhập lại mật khẩu">
                    </div>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent 
                               text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 
                               transition duration-150 ease-in-out">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    Đăng ký
                </button>
            </div>
        </form>

        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">
                        Đã có tài khoản?
                    </span>
                </div>
            </div>
            <div class="mt-2 text-center">
                <a href="<?php echo base_url('/login.php'); ?>" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Đăng nhập ngay
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
