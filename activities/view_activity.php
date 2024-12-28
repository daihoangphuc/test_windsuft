<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireLogin();

$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isAdmin = $auth->isAdmin();

if (!$activityId) {
    header('Location: ' . ($isAdmin ? '../admin/activities' : 'my_activities.php'));
    exit;
}

$activity = new Activity();
$activityData = $activity->get($activityId);

if (!$activityData) {
    header('Location: ' . ($isAdmin ? '../admin/activities' : 'my_activities.php'));
    exit;
}

// Get registrations and attendance
$db = Database::getInstance()->getConnection();
$query = "SELECT 
            n.Id as NguoiDungId,
            n.HoTen,
            n.MaSinhVien,
            n.Email,
            cv.TenChucVu,
            dk.ThoiGianDangKy,
            dt.DiemDanhLuc,
            CASE 
                WHEN dt.Id IS NOT NULL THEN 
                    CASE 
                        WHEN dt.TrangThai = 1 THEN 'Đã tham gia'
                        WHEN dt.TrangThai = 0 THEN 'Vắng mặt'
                    END
                WHEN NOW() > ? THEN 'Vắng mặt'
                ELSE 'Đã đăng ký'
            END as TrangThai
          FROM danhsachdangky dk
          JOIN nguoidung n ON dk.NguoiDungId = n.Id
          LEFT JOIN chucvu cv ON n.ChucVuId = cv.Id
          LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = dk.HoatDongId AND dt.NguoiDungId = n.Id
          WHERE dk.HoatDongId = ? AND dk.TrangThai = 1
          ORDER BY n.HoTen";

$stmt = $db->prepare($query);
$stmt->bind_param("si", $activityData['NgayKetThuc'], $activityId);
$stmt->execute();
$registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'total' => count($registrations),
    'attended' => 0,
    'absent' => 0,
    'registered' => 0
];

foreach ($registrations as $reg) {
    if ($reg['TrangThai'] === 'Đã tham gia') {
        $stats['attended']++;
    } elseif ($reg['TrangThai'] === 'Vắng mặt') {
        $stats['absent']++;
    } else {
        $stats['registered']++;
    }
}

$pageTitle = "Chi tiết hoạt động: " . $activityData['TenHoatDong'];
require_once '../layouts/' . ($isAdmin ? 'admin_header.php' : 'header.php');
?>

<div class="p-4">
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-900">Chi tiết hoạt động</h2>
            <a href="<?php echo $isAdmin ? '../admin/activities' : 'my_activities.php'; ?>" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
        
        <!-- Activity Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4"><?php echo htmlspecialchars($activityData['TenHoatDong']); ?></h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-calendar mr-2"></i>
                        <strong>Thời gian:</strong><br>
                        Bắt đầu: <?php echo date('d/m/Y H:i', strtotime($activityData['NgayBatDau'])); ?><br>
                        Kết thúc: <?php echo date('d/m/Y H:i', strtotime($activityData['NgayKetThuc'])); ?>
                    </p>
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <strong>Địa điểm:</strong> <?php echo htmlspecialchars($activityData['DiaDiem']); ?>
                    </p>
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-user mr-2"></i>
                        <strong>Người tạo:</strong> <?php echo htmlspecialchars($activityData['NguoiTao']); ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-users mr-2"></i>
                        <strong>Số lượng tối đa:</strong> <?php echo number_format($activityData['SoLuong']); ?> người
                    </p>
                    <p class="text-gray-600">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Mô tả:</strong><br>
                        <?php echo nl2br(htmlspecialchars($activityData['MoTa'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Tổng đăng ký</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo number_format($stats['total']); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Đã tham gia</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo number_format($stats['attended']); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Vắng mặt</h3>
                <p class="text-3xl font-bold text-red-600"><?php echo number_format($stats['absent']); ?></p>
            </div>
        </div>

        <!-- Registrations List -->
        <?php if ($isAdmin || count($registrations) > 0): ?>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">Danh sách đăng ký tham gia</h3>
            </div>
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-6 py-3">Họ tên</th>
                        <th class="px-6 py-3">MSSV</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Chức vụ</th>
                        <th class="px-6 py-3">Thời gian đăng ký</th>
                        <th class="px-6 py-3">Trạng thái</th>
                        <?php if ($isAdmin): ?>
                        <th class="px-6 py-3">Thời gian điểm danh</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <?php echo htmlspecialchars($reg['HoTen']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($reg['MaSinhVien']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($reg['Email']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($reg['TenChucVu']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo date('d/m/Y H:i', strtotime($reg['ThoiGianDangKy'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $status_class = match($reg['TrangThai']) {
                                    'Đã tham gia' => 'text-green-600',
                                    'Vắng mặt' => 'text-red-600',
                                    default => 'text-blue-600'
                                };
                                ?>
                                <span class="font-medium <?php echo $status_class; ?>">
                                    <?php echo $reg['TrangThai']; ?>
                                </span>
                            </td>
                            <?php if ($isAdmin): ?>
                            <td class="px-6 py-4">
                                <?php 
                                if ($reg['ThoiGianDiemDanh']) {
                                    echo date('d/m/Y H:i', strtotime($reg['ThoiGianDiemDanh']));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (count($registrations) === 0): ?>
                        <tr>
                            <td colspan="<?php echo $isAdmin ? 7 : 6; ?>" class="px-6 py-4 text-center text-gray-500">
                                Chưa có người đăng ký tham gia hoạt động này
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../layouts/' . ($isAdmin ? 'admin_footer.php' : 'footer.php');
?>
