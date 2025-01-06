<?php
require_once 'config/database.php';
require_once 'config/auth.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Lấy danh sách lớp học
$stmtLopHoc = $db->prepare("SELECT Id, TenLop FROM lophoc ORDER BY TenLop ASC");
$stmtLopHoc->execute();
$danhSachLop = $stmtLopHoc->get_result();

// Lấy thông tin người dùng
$stmt = $db->prepare("SELECT n.*, l.TenLop, cv.TenChucVu 
                      FROM nguoidung n 
                      LEFT JOIN lophoc l ON n.LopHocId = l.Id
                      LEFT JOIN chucvu cv ON n.ChucVuId = cv.Id 
                      WHERE n.Id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Thống kê hoạt động
$statsQuery = $db->prepare("
    SELECT 
        (SELECT COUNT(*) 
         FROM danhsachdangky 
         WHERE NguoiDungId = ? AND TrangThai = 1) as TongDangKy,
        (SELECT COUNT(*) 
         FROM danhsachthamgia 
         WHERE NguoiDungId = ? AND TrangThai = 1) as TongThamGia
");
$statsQuery->bind_param("ii", $userId, $userId);
$statsQuery->execute();
$stats = $statsQuery->get_result()->fetch_assoc();

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $hoTen = $_POST['ho_ten'];
        $email = $_POST['email'];
        $ngaySinh = $_POST['ngay_sinh'];
        $gioiTinh = $_POST['gioi_tinh'];
        $maSinhVien = $_POST['ma_sinh_vien'];
        $lopHocId = $_POST['lop_hoc'];

        // Kiểm tra email trùng lặp
        $checkEmail = $db->prepare("SELECT Id FROM nguoidung WHERE Email = ? AND Id != ?");
        $checkEmail->bind_param("si", $email, $userId);
        $checkEmail->execute();
        $emailResult = $checkEmail->get_result();

        // Kiểm tra mã sinh viên trùng lặp
        $checkMaSV = $db->prepare("SELECT Id FROM nguoidung WHERE MaSinhVien = ? AND Id != ?");
        $checkMaSV->bind_param("si", $maSinhVien, $userId);
        $checkMaSV->execute();
        $maSVResult = $checkMaSV->get_result();

        if ($emailResult->num_rows > 0) {
            $_SESSION['flash_error'] = "Email này đã được sử dụng bởi người dùng khác!";
        } elseif ($maSVResult->num_rows > 0) {
            $_SESSION['flash_error'] = "Mã sinh viên này đã được sử dụng bởi người dùng khác!";
        } else {
            $stmt = $db->prepare("UPDATE nguoidung SET HoTen = ?, Email = ?, NgaySinh = ?, GioiTinh = ?, MaSinhVien = ?, LopHocId = ? WHERE Id = ?");
            $stmt->bind_param("ssssisi", $hoTen, $email, $ngaySinh, $gioiTinh, $maSinhVien, $lopHocId, $userId);
            
            if ($stmt->execute()) {
                $_SESSION['flash_message'] = "Cập nhật thông tin thành công!";
            } else {
                $_SESSION['flash_error'] = "Lỗi khi cập nhật thông tin!";
            }
        }
    }
    
    // Xử lý đổi mật khẩu
    else if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($newPassword !== $confirmPassword) {
            $_SESSION['flash_error'] = "Mật khẩu mới không khớp!";
        } else {
            $stmt = $db->prepare("SELECT MatKhauHash FROM nguoidung WHERE Id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if (password_verify($currentPassword, $result['MatKhauHash'])) {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE nguoidung SET MatKhauHash = ? WHERE Id = ?");
                $stmt->bind_param("si", $newPasswordHash, $userId);
                
                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = "Đổi mật khẩu thành công!";
                } else {
                    $_SESSION['flash_error'] = "Lỗi khi đổi mật khẩu!";
                }
            } else {
                $_SESSION['flash_error'] = "Mật khẩu hiện tại không đúng!";
            }
        }
    }
    
    // Xử lý cập nhật ảnh đại diện
    else if (isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];
        $fileName = $file['name'];
        $fileType = $file['type'];
        $fileTmpName = $file['tmp_name'];
        $fileError = $file['error'];
        $fileSize = $file['size'];
        
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png');

        if (in_array($fileExt, $allowed)) {
            if ($fileError === 0) {
                if ($fileSize < 5000000) { // 5MB max
                    $fileNameNew = uniqid('', true) . "." . $fileExt;
                    $fileDestination = 'uploads/users/' . $fileNameNew;
                    
                    if (!file_exists('uploads/users/')) {
                        mkdir('uploads/users/', 0777, true);
                    }
                    
                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        // Xóa ảnh cũ nếu có
                        if ($user['anhdaidien'] && file_exists($user['anhdaidien'])) {
                            unlink($user['anhdaidien']);
                        }
                        
                        $avatarPath = '../' . $fileDestination; // Định dạng đường dẫn như yêu cầu
                        $stmt = $db->prepare("UPDATE nguoidung SET anhdaidien = ? WHERE Id = ?");
                        $stmt->bind_param("si", $avatarPath, $userId);
                        
                        if ($stmt->execute()) {
                            $_SESSION['flash_message'] = "Cập nhật ảnh đại diện thành công!";
                            $user['anhdaidien'] = $avatarPath;
                        } else {
                            $_SESSION['flash_error'] = "Lỗi khi cập nhật ảnh đại diện trong CSDL!";
                        }
                    } else {
                        $_SESSION['flash_error'] = "Lỗi khi tải ảnh lên!";
                    }
                } else {
                    $_SESSION['flash_error'] = "File quá lớn! Vui lòng chọn file nhỏ hơn 5MB";
                }
            } else {
                $_SESSION['flash_error'] = "Có lỗi xảy ra khi tải file!";
            }
        } else {
            $_SESSION['flash_error'] = "Chỉ chấp nhận file ảnh jpg, jpeg hoặc png!";
        }
    }
    
    header("Location: profile.php");
    exit;
}

$pageTitle = "Thông tin cá nhân";
require_once 'layouts/header.php';
?>

<div class="container mx-auto py-8 px-4 lg:px-8">
    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash_message']) || isset($_SESSION['flash_error'])): ?>
    <div id="flashMessage" class="mb-4 px-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <?php unset($_SESSION['flash_message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <?php echo $_SESSION['flash_error']; ?>
            <?php unset($_SESSION['flash_error']); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg overflow-hidden"  style="margin-top: -50px">
        <div class="flex flex-col md:flex-row">
            <!-- Sidebar với ảnh đại diện -->
            <div class="w-full md:w-1/3 p-4 bg-gray-50 md:mt-[90px]">
                <div class="text-center">
                    <img src="<?php echo str_replace('../', BASE_URL . '/', $user['anhdaidien']); ?>" alt="user photo"
                         class="w-32 h-32 md:w-48 md:h-48 rounded-full mx-auto mb-4 object-cover border-4 border-blue-500">
                    
                    <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900">
                            Thay đổi ảnh đại diện
                        </label>
                        <input type="file" name="avatar" accept="image/*" class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100">
                        <button type="submit" class="mt-2 text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 w-full md:w-auto">
                            Cập nhật ảnh
                        </button>
                    </form>
                </div>
            </div>

            <!-- Main content -->
            <div class="w-full md:w-2/3 p-4 md:p-6">
                <h2 class="text-2xl font-bold mb-6">Thông tin cá nhân</h2>
                
                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 rounded-t-lg" id="profile-tab" data-tabs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                                Thông tin chung
                            </button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="password-tab" data-tabs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                                Đổi mật khẩu
                            </button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="activities-tab" data-tabs-target="#activities" type="button" role="tab" aria-controls="activities" aria-selected="false">
                                Hoạt động
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Tab Thông tin chung -->
                <div id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Mã sinh viên</label>
                                <input type="text" readonly name="ma_sinh_vien" value="<?php echo htmlspecialchars($user['MaSinhVien']); ?>" 
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            </div>
                            
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Họ tên</label>
                                <input type="text" name="ho_ten" value="<?php echo htmlspecialchars($user['HoTen']); ?>" required
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            </div>
                            
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            </div>
                            
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Ngày sinh</label>
                                <input type="date" name="ngay_sinh" value="<?php echo $user['NgaySinh']; ?>"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            </div>
                            
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Giới tính</label>
                                <select name="gioi_tinh" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                    <option value="1" <?php echo $user['GioiTinh'] == 1 ? 'selected' : ''; ?>>Nam</option>
                                    <option value="0" <?php echo $user['GioiTinh'] == 0 ? 'selected' : ''; ?>>Nữ</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Lớp</label>
                                <select name="lop_hoc" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                    <option value="">Chọn lớp</option>
                                    <?php while ($lop = $danhSachLop->fetch_assoc()): ?>
                                        <option value="<?php echo $lop['Id']; ?>" <?php echo ($user['LopHocId'] == $lop['Id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lop['TenLop']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900">Chức vụ</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['TenChucVu']); ?>" disabled
                                       class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                            </div>
                        </div>

                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                            Cập nhật thông tin
                        </button>
                    </form>
                </div>

                <!-- Tab Đổi mật khẩu -->
                <div id="password" class="hidden" role="tabpanel" aria-labelledby="password-tab">
                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" required
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Mật khẩu mới</label>
                            <input type="password" name="new_password" required minlength="6"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_password" required minlength="6"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>

                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                            Đổi mật khẩu
                        </button>
                    </form>
                </div>

                <!-- Tab Hoạt động -->
                <div id="activities" class="hidden" role="tabpanel" aria-labelledby="activities-tab">
                    <div class="bg-white p-4 md:p-6 rounded-lg">
                        <h3 class="text-xl md:text-2xl font-bold mb-6 text-gray-800">Thống kê hoạt động</h3>
                        <div class="w-full">
                            <!-- Thông tin phụ -->
                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                        <p class="text-sm text-gray-600">Tỷ lệ tham gia</p>
                                        <p class="text-xl md:text-2xl font-bold text-blue-600">
                                            <?php 
                                            $ratio = $stats['TongDangKy'] > 0 
                                                ? round(($stats['TongThamGia'] / $stats['TongDangKy']) * 100, 1) 
                                                : 0; 
                                            echo $ratio . '%';
                                            ?>
                                        </p>
                                    </div>
                                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                        <p class="text-sm text-gray-600">Hoạt động tích cực</p>
                                        <p class="text-xl md:text-2xl font-bold text-green-600">
                                            <?php echo $stats['TongThamGia']; ?> lần
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- Progress bars -->
                            <div class="space-y-4">
                                <div class="flex flex-col md:flex-row md:items-center">
                                    <span class="text-sm font-medium text-gray-600 mb-2 md:mb-0 md:mr-2 md:w-32">Tổng đăng ký:</span>
                                    <div class="relative w-full md:w-[300px] h-9 bg-blue-100 rounded-lg">
                                        <?php 
                                        $dangKyPercent = ($stats['TongDangKy'] / max($stats['TongDangKy'], $stats['TongThamGia'])) * 100;
                                        $isFullDangKy = $dangKyPercent >= 100;
                                        ?>
                                        <div class="absolute top-0 h-full bg-blue-600 rounded-lg transition-all duration-1000" 
                                             style="width: <?php echo $dangKyPercent; ?>%">
                                        </div>
                                        <div class="absolute inset-0 flex items-center justify-end pr-2">
                                            <span class="text-sm font-semibold <?php echo $isFullDangKy ? 'text-white' : 'text-blue-600'; ?>">
                                                <?php echo $stats['TongDangKy']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center">
                                    <span class="text-sm font-medium text-gray-600 mb-2 md:mb-0 md:mr-2 md:w-32">Tổng tham gia:</span>
                                    <div class="relative w-full md:w-[300px] h-9 bg-green-100 rounded-lg">
                                        <?php 
                                        $thamGiaPercent = ($stats['TongThamGia'] / max($stats['TongDangKy'], $stats['TongThamGia'])) * 100;
                                        $isFullThamGia = $thamGiaPercent >= 100;
                                        ?>
                                        <div class="absolute top-0 h-full bg-green-600 rounded-lg transition-all duration-1000" 
                                             style="width: <?php echo $thamGiaPercent; ?>%">
                                        </div>
                                        <div class="absolute inset-0 flex items-center justify-end pr-2">
                                            <span class="text-sm font-semibold <?php echo $isFullThamGia ? 'text-white' : 'text-green-600'; ?>">
                                                <?php echo $stats['TongThamGia']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tab switching
const tabElements = [
    {
        id: 'profile-tab',
        triggerEl: document.querySelector('#profile-tab'),
        targetEl: document.querySelector('#profile')
    },
    {
        id: 'password-tab',
        triggerEl: document.querySelector('#password-tab'),
        targetEl: document.querySelector('#password')
    },
    {
        id: 'activities-tab',
        triggerEl: document.querySelector('#activities-tab'),
        targetEl: document.querySelector('#activities')
    }
];

// Add click event to tabs
tabElements.forEach(tab => {
    tab.triggerEl.addEventListener('click', e => {
        e.preventDefault();
        
        // Hide all tabs
        tabElements.forEach(t => {
            t.targetEl.classList.add('hidden');
            t.triggerEl.classList.remove('border-blue-600', 'text-blue-600');
            t.triggerEl.classList.add('border-transparent');
        });
        
        // Show active tab
        tab.targetEl.classList.remove('hidden');
        tab.triggerEl.classList.add('border-blue-600', 'text-blue-600');
    });
});

// Animation cho các thanh tiến trình
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('[role="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            if (tab.id === 'activities-tab') {
                // Reset và chạy animation khi tab được chọn
                const bars = document.querySelectorAll('.bg-blue-600, .bg-green-600');
                bars.forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 50);
                });
            }
        });
    });
});
</script>

<?php require_once 'layouts/footer.php'; ?>