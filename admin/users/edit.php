<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

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
$stmt = $db->prepare("SELECT n.*, v.VaiTroId 
                      FROM nguoidung n 
                      LEFT JOIN vaitronguoidung v ON n.Id = v.NguoiDungId 
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_STRING);
    $position_id = filter_input(INPUT_POST, 'position_id', FILTER_VALIDATE_INT);
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $role = filter_input(INPUT_POST, 'role', FILTER_VALIDATE_INT);
    $new_password = $_POST['new_password'] ?? '';
    
    try {
        $db->begin_transaction();
        
        // Kiểm tra email đã tồn tại
        if ($email !== $user['Email']) {
            $stmt = $db->prepare("SELECT Id FROM nguoidung WHERE Email = ? AND Id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Email đã được sử dụng!");
            }
        }
        
        // Cập nhật thông tin người dùng
        $query = "UPDATE nguoidung SET Email = ?, HoTen = ?, MaSinhVien = ?, ChucVuId = ?, LopHocId = ?";
        $params = [$email, $fullname, $student_id, $position_id, $class_id];
        $types = "sssii";
        
        if ($new_password) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $query .= ", MatKhauHash = ?";
            $params[] = $password_hash;
            $types .= "s";
        }
        
        $query .= " WHERE Id = ?";
        $params[] = $user_id;
        $types .= "i";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            throw new Exception("Không thể cập nhật thông tin người dùng!");
        }
        
        // Cập nhật vai trò
        $stmt = $db->prepare("UPDATE vaitronguoidung SET VaiTroId = ? WHERE NguoiDungId = ?");
        $stmt->bind_param("ii", $role, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Không thể cập nhật vai trò người dùng!");
        }
        
        // Log hoạt động
        $ip = $_SERVER['REMOTE_ADDR'];
        $admin_username = $_SESSION['username'];
        $action = "Cập nhật thông tin người dùng";
        $result = "Thành công";
        $details = "Cập nhật thông tin cho người dùng ID: $user_id";
        
        $stmt = $db->prepare("INSERT INTO nhatkyhoatdong (IP, NguoiDung, HanhDong, KetQua, ChiTiet) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $ip, $admin_username, $action, $result, $details);
        $stmt->execute();
        
        $db->commit();
        $_SESSION['flash_message'] = "Cập nhật thông tin người dùng thành công!";
        header('Location: index.php');
        exit();
        
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['flash_error'] = $e->getMessage();
    }
}

$pageTitle = 'Chỉnh sửa người dùng';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="p-4">
    <div class="mb-4">
        <h2 class="text-2xl font-bold">Chỉnh sửa thông tin người dùng</h2>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
            <?php 
            echo $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
            ?>
        </div>
    <?php endif; ?>

    <form action="edit.php?id=<?php echo $user_id; ?>" method="POST" class="max-w-2xl">
        <div class="grid gap-4 mb-4 sm:grid-cols-2">
            <div>
                <label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tên đăng nhập</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($user['TenDangNhap']); ?>" class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" disabled>
            </div>
            <div>
                <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['Email']); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
            </div>
            <div>
                <label for="fullname" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Họ và tên</label>
                <input type="text" name="fullname" id="fullname" value="<?php echo htmlspecialchars($user['HoTen']); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
            </div>
            <div>
                <label for="student_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mã sinh viên</label>
                <input type="text" name="student_id" id="student_id" value="<?php echo htmlspecialchars($user['MaSinhVien']); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="position_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Chức vụ</label>
                <select name="position_id" id="position_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="">Chọn chức vụ</option>
                    <?php foreach ($positions as $position): ?>
                        <option value="<?php echo $position['Id']; ?>" <?php echo $position['Id'] == $user['ChucVuId'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($position['TenChucVu']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="class_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Lớp</label>
                <select name="class_id" id="class_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="">Chọn lớp</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['Id']; ?>" <?php echo $class['Id'] == $user['LopHocId'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['TenLop'] . ' - ' . $class['TenKhoaTruong']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="role" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Vai trò</label>
                <select name="role" id="role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    <option value="2" <?php echo $user['VaiTroId'] == 2 ? 'selected' : ''; ?>>Thành viên</option>
                    <option value="1" <?php echo $user['VaiTroId'] == 1 ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <div>
                <label for="new_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mật khẩu mới (để trống nếu không đổi)</label>
                <input type="password" name="new_password" id="new_password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
        </div>
        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
            Cập nhật
        </button>
        <a href="index.php" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
            Hủy
        </a>
    </form>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
