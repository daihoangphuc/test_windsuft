<?php
require_once __DIR__ . '/../../layouts/admin_header.php';
require_once __DIR__ . '/../../includes/classes/Student.php';

// Lấy ID của sinh viên từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$student = new Student($conn);

// Lấy thông tin chi tiết của sinh viên
$studentInfo = $student->getById($id);

// Nếu không tìm thấy sinh viên, chuyển hướng về trang danh sách
if (!$studentInfo) {
    header('Location: ../class/index.php');
    exit;
}

// Format trạng thái
$trangThai = $studentInfo['TrangThai'] == 1
    ? '<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Hoạt động</span>'
    : '<span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Không hoạt động</span>';

// Format giới tính
$gioiTinh = match ($studentInfo['GioiTinh']) {
    1 => '<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Nam</span>',
    0 => '<span class="bg-pink-100 text-pink-800 text-xs font-medium px-2.5 py-0.5 rounded">Nữ</span>',
    default => '<span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Khác</span>',
};
?>

<div class="p-4 bg-white shadow-lg rounded-lg border border-gray-200">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Thông tin chi tiết thành viên</h1>
        <a href="../class/index.php" class="text-blue-600 hover:underline">Quay lại danh sách</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Thông tin cơ bản -->
        <div class="col-span-1 bg-white rounded-lg shadow p-6">
            <div class="text-center">
                <img class="w-32 h-32 mb-4 mx-auto rounded-full shadow-lg object-cover" 
                     src="<?php echo "../" . $studentInfo['anhdaidien'] ?? DEFAULT_AVATAR; ?>" 
                     alt="<?php echo htmlspecialchars($studentInfo['HoTen']); ?>" />
                <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($studentInfo['HoTen']); ?></h2>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($studentInfo['MaSinhVien'] ?? 'Chưa có mã SV'); ?></p>
                <div class="mt-3">
                    <?php echo $trangThai; ?>
                    <?php echo $gioiTinh; ?>
                </div>
            </div>
        </div>

        <!-- Thông tin chi tiết -->
        <div class="col-span-2 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Thông tin chi tiết</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php
                $fields = [
                    'Email' => $studentInfo['Email'],
                    'Tên đăng nhập' => $studentInfo['TenDangNhap'],
                    'Ngày sinh' => $studentInfo['NgaySinh'] ? date('d/m/Y', strtotime($studentInfo['NgaySinh'])) : 'Chưa cập nhật',
                    'Chức vụ' => $studentInfo['TenChucVu'] ?? 'Chưa có',
                    'Lớp' => $studentInfo['LopHocId'] && $studentInfo['TenLop']
                        ? '<a href="../class/view.php?id=' . $studentInfo['LopHocId'] . '" class="text-blue-600 hover:underline">' . htmlspecialchars($studentInfo['TenLop']) . '</a>'
                        : 'Chưa có lớp',
                    'Khoa/Trường' => $studentInfo['TenKhoaTruong'] ?? 'Chưa có',
                    'Ngày tạo tài khoản' => date('d/m/Y H:i:s', strtotime($studentInfo['NgayTao'])),
                    'Lần truy cập cuối' => $studentInfo['lantruycapcuoi'] ? date('d/m/Y H:i:s', strtotime($studentInfo['lantruycapcuoi'])) : 'Chưa đăng nhập',
                ];

                foreach ($fields as $label => $value) {
                    echo "
                    <div>
                        <p class='text-sm text-gray-600'>$label:</p>
                        <p class='font-medium'>$value</p>
                    </div>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Thống kê hoạt động -->
    <div class="bg-white rounded-lg shadow p-6 mt-6">
        <h3 class="text-lg font-semibold mb-4">Thống kê hoạt động</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php
            $activities = [
                'Bài viết' => 0,
                'Bình luận' => 0,
                'Sự kiện tham gia' => 0,
                'Hoạt động' => 0,
            ];

            foreach ($activities as $activity => $count) {
                echo "
                <div class='bg-blue-50 p-4 rounded-lg text-center'>
                    <div class='text-2xl font-bold text-blue-600'>$count</div>
                    <div class='text-sm text-gray-600'>$activity</div>
                </div>";
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
