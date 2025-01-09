<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/User.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance()->getConnection();
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id) {
    $_SESSION['flash_error'] = "ID người dùng không hợp lệ!";
    header('Location: index.php');
    exit();
}

// Lấy thông tin người dùng
$stmt = $db->prepare("SELECT n.*
                      FROM nguoidung n  
                      WHERE n.Id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $_SESSION['flash_error'] = "Không tìm thấy người dùng!";
    header('Location: index.php');
    exit();
}

// Lấy danh sách chức vụ
$stmt = $db->query("SELECT * FROM chucvu ORDER BY Id");
$positions = $stmt->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách khoa
$stmt = $db->query("SELECT * FROM khoatruong ORDER BY TenKhoaTruong");
$faculties = $stmt->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách lớp
$stmt = $db->query("SELECT l.*, k.TenKhoaTruong 
                    FROM lophoc l 
                    JOIN khoatruong k ON l.KhoaTruongId = k.Id 
                    ORDER BY k.TenKhoaTruong, l.TenLop");
$classes = $stmt->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách vai trò
$stmt = $db->query("SELECT * FROM vaitro ORDER BY Id");
$roles = $stmt->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_STRING);
    $position_id = filter_input(INPUT_POST, 'position_id', FILTER_VALIDATE_INT);
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_VALIDATE_INT);
    $birthdate = filter_input(INPUT_POST, 'birthdate', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    // Xử lý upload ảnh đại diện
    $avatar = $user['anhdaidien']; // Giữ nguyên ảnh cũ nếu không upload mới
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/users/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExtension;
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFile)) {
            $avatar = '../uploads/users/' . $fileName;
            // Xóa ảnh cũ nếu có
            if (!empty($user['anhdaidien']) && file_exists('../../' . $user['anhdaidien'])) {
                unlink('../../' . $user['anhdaidien']);
            }
        }
    }

    if ($email && $fullname) {
        try {
            $db->begin_transaction();

            // Validate required fields
            if (empty($fullname) || empty($email)) {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc!");
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email không hợp lệ!");
            }

            // Kiểm tra email đã tồn tại với người dùng khác
            $stmt = $db->prepare("SELECT Id FROM nguoidung WHERE Email = ? AND Id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Email đã được sử dụng bởi người dùng khác!");
            }

            // Kiểm tra mã sinh viên đã tồn tại với người dùng khác
            if (!empty($student_id)) {
                $stmt = $db->prepare("SELECT Id FROM nguoidung WHERE MaSinhVien = ? AND Id != ?");
                $stmt->bind_param("si", $student_id, $user_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Mã sinh viên đã tồn tại với người dùng khác!");
                }
            }

            // Validate password if changed
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    throw new Exception("Mật khẩu phải có ít nhất 8 ký tự!");
                }
            }

            $userObj = new User();
            $data = [
                'username' => $user['TenDangNhap'], // Giữ nguyên username
                'fullname' => $fullname,
                'email' => $email,
                'student_id' => $student_id,
                'gender' => $gender,
                'birthdate' => $birthdate,
                'role' => $role_id,
                'position' => $position_id,
                'class' => $class_id,
                'status' => $status,
                'avatar' => $avatar
            ];

            if (!empty($password)) {
                $data['password'] = $password;
            }

            if ($userObj->update($user_id, $data)) {
                $db->commit();
                $_SESSION['flash_success'] = "Cập nhật thông tin thành công!";
                header('Location: index.php');
                exit();
            } else {
                $db->rollBack();
                $_SESSION['flash_error'] = "Có lỗi xảy ra khi cập nhật thông tin!";
            }
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = "Vui lòng điền đầy đủ thông tin bắt buộc!";
    }
}

$pageTitle = 'Chỉnh sửa thông tin người dùng';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="p-4">
    <div class="mb-4">
        <h2 class="text-2xl font-bold">Chỉnh sửa thông tin người dùng</h2>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
            <?php 
            echo $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
            ?>
        </div>
    <?php endif; ?>

    <form action="edit.php?id=<?php echo $user_id; ?>" method="POST" enctype="multipart/form-data" class="max-w-2xl">
        <!-- Thông tin cơ bản -->
        <div class="p-4 mb-4 bg-white rounded-lg border">
            <h3 class="text-lg font-medium mb-4">Thông tin cơ bản</h3>
            <div class="grid gap-4 mb-4 sm:grid-cols-2">
                <div>
                    <label for="username" class="block mb-2 text-sm font-medium text-gray-900">Tên đăng nhập</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['TenDangNhap']); ?>" class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" disabled>
                </div>
                <div>
                    <label for="fullname" class="block mb-2 text-sm font-medium text-gray-900">Họ tên <span class="text-red-500">*</span></label>
                    <input type="text" name="fullname" id="fullname" value="<?php echo htmlspecialchars($user['HoTen']); ?>" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-900">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['Email']); ?>" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="student_id" class="block mb-2 text-sm font-medium text-gray-900">Mã sinh viên</label>
                    <input type="text" name="student_id" id="student_id" value="<?php echo htmlspecialchars($user['MaSinhVien']); ?>" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                </div>
                <div>
                    <label for="birthdate" class="block mb-2 text-sm font-medium text-gray-900">Ngày sinh</label>
                    <input type="date" name="birthdate" id="birthdate" value="<?php echo $user['NgaySinh']; ?>" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900">Giới tính</label>
                    <div class="flex gap-4">
                        <div class="flex items-center">
                            <input type="radio" name="gender" id="gender_male" value="1" <?php echo $user['GioiTinh'] == 1 ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                            <label for="gender_male" class="ml-2 text-sm font-medium text-gray-900">Nam</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="gender" id="gender_female" value="0" <?php echo $user['GioiTinh'] == 0 ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                            <label for="gender_female" class="ml-2 text-sm font-medium text-gray-900">Nữ</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ảnh đại diện -->
        <div class="p-4 mb-4 bg-white rounded-lg border">
            <h3 class="text-lg font-medium mb-4">Ảnh đại diện</h3>
            <div class="flex items-center gap-4">
                <img class="w-20 h-20 rounded-full" 
                     src="<?php echo !empty($user['anhdaidien']) ? "../" . $user['anhdaidien'] : DEFAULT_AVATAR; ?>" 
                     alt="<?php echo htmlspecialchars($user['HoTen']); ?>">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900" for="avatar">Tải lên ảnh mới</label>
                    <input type="file" name="avatar" id="avatar" accept="image/*" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Phân quyền và trạng thái -->
        <div class="p-4 mb-4 bg-white rounded-lg border">
            <h3 class="text-lg font-medium mb-4">Phân quyền và trạng thái</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="role_id" class="block mb-2 text-sm font-medium text-gray-900">Vai trò <span class="text-red-500">*</span></label>
                    <select name="role_id" id="role_id" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['Id']; ?>" <?php echo $user['VaiTroId'] == $role['Id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['TenVaiTro']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="position_id" class="block mb-2 text-sm font-medium text-gray-900">Chức vụ</label>
                    <select name="position_id" id="position_id" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        <option value="">Chọn chức vụ</option>
                        <?php foreach ($positions as $position): ?>
                            <option value="<?php echo $position['Id']; ?>" <?php echo $user['ChucVuId'] == $position['Id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($position['TenChucVu']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="class_id" class="block mb-2 text-sm font-medium text-gray-900">Lớp</label>
                    <select name="class_id" id="class_id" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        <option value="">Chọn lớp</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['Id']; ?>" <?php echo $user['LopHocId'] == $class['Id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['TenLop'] . ' - ' . $class['TenKhoaTruong']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="status" class="block mb-2 text-sm font-medium text-gray-900">Trạng thái</label>
                    <select name="status" id="status" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        <option value="1" <?php echo $user['TrangThai'] == 1 ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="0" <?php echo $user['TrangThai'] == 0 ? 'selected' : ''; ?>>Không hoạt động</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Mật khẩu -->
        <div class="p-4 mb-4 bg-white rounded-lg border">
            <h3 class="text-lg font-medium mb-4">Đổi mật khẩu</h3>
            <div>
                <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Mật khẩu mới</label>
                <input type="password" name="password" id="password" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Để trống nếu không muốn đổi mật khẩu">
            </div>
        </div>

        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2">
            Lưu thay đổi
        </button>
        <a href="index.php" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
            Hủy
        </a>
    </form>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
