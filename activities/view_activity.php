<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireLogin();

$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isAdmin = $auth->isAdmin();
$userId = $_SESSION['user_id'];

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

if ($isAdmin) {
    // Admin có thể xem tất cả thông tin đăng ký
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
} else {
    // Member chỉ xem thông tin của mình
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
              WHERE dk.HoatDongId = ? AND dk.TrangThai = 1 AND dk.NguoiDungId = ?
              ORDER BY n.HoTen";
    $stmt = $db->prepare($query);
    $stmt->bind_param("sii", $activityData['NgayKetThuc'], $activityId, $userId);
}

$stmt->execute();
$registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Kiểm tra người dùng hiện tại đã tham gia hoạt động chưa
$hasParticipated = false;
foreach ($registrations as $reg) {
    if ($reg['NguoiDungId'] == $userId && $reg['TrangThai'] === 'Đã tham gia') {
        $hasParticipated = true;
        break;
    }
}

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
                    <?php if (!empty($activityData['DuongDanMinhChung']) && $hasParticipated): ?>
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-file-alt mr-2"></i>
                        <strong>Minh chứng:</strong>
                        <a href="<?php echo '../' . $activityData['DuongDanMinhChung']; ?>" 
                           target="_blank"
                           class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                            <i class="fas fa-download mr-1"></i> Tải xuống
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-users mr-2"></i>
                        <strong>Số lượng:</strong> <?php echo $activityData['SoLuong']; ?> người
                    </p>
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Trạng thái:</strong>
                        <?php
                        $now = time();
                        $startTime = strtotime($activityData['NgayBatDau']);
                        $endTime = strtotime($activityData['NgayKetThuc']);
                        
                        if ($now < $startTime) {
                            echo '<span class="text-blue-600">Sắp diễn ra</span>';
                        } elseif ($now >= $startTime && $now <= $endTime) {
                            echo '<span class="text-green-600">Đang diễn ra</span>';
                        } else {
                            echo '<span class="text-gray-600">Đã kết thúc</span>';
                        }
                        ?>
                    </p>
                    <?php if (!$isAdmin): ?>
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-user-check mr-2"></i>
                        <strong>Trạng thái tham gia:</strong>
                        <?php
                        if (!empty($registrations)) {
                            $reg = $registrations[0]; // Chỉ có 1 bản ghi của user hiện tại
                            echo '<span class="font-semibold ';
                            switch ($reg['TrangThai']) {
                                case 'Đã tham gia':
                                    echo 'text-green-600">Đã tham gia';
                                    break;
                                case 'Vắng mặt':
                                    echo 'text-red-600">Vắng mặt';
                                    break;
                                default:
                                    echo 'text-blue-600">Đã đăng ký';
                            }
                            echo '</span>';
                        } else {
                            echo '<span class="text-gray-600">Chưa đăng ký</span>';
                        }
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-gray-600">
                    <i class="fas fa-align-left mr-2"></i>
                    <strong>Mô tả:</strong><br>
                    <?php echo nl2br(htmlspecialchars($activityData['MoTa'])); ?>
                </p>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-md bg-blue-100 p-3">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-gray-500">Tổng số đăng ký</h4>
                        <p class="text-lg font-semibold text-gray-900"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-md bg-green-100 p-3">
                            <i class="fas fa-user-check text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-gray-500">Đã tham gia</h4>
                        <p class="text-lg font-semibold text-gray-900"><?php echo $stats['attended']; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-md bg-red-100 p-3">
                            <i class="fas fa-user-times text-red-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-gray-500">Vắng mặt</h4>
                        <p class="text-lg font-semibold text-gray-900"><?php echo $stats['absent']; ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-md bg-yellow-100 p-3">
                            <i class="fas fa-user-clock text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-gray-500">Chờ tham gia</h4>
                        <p class="text-lg font-semibold text-gray-900"><?php echo $stats['registered']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registrations Table -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">Danh sách đăng ký tham gia</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">Họ tên</th>
                            <th scope="col" class="px-6 py-3">MSSV</th>
                            <th scope="col" class="px-6 py-3">Email</th>
                            <th scope="col" class="px-6 py-3">Chức vụ</th>
                            <th scope="col" class="px-6 py-3">Thời gian đăng ký</th>
                            <th scope="col" class="px-6 py-3">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
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
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    <?php
                                    switch ($reg['TrangThai']) {
                                        case 'Đã tham gia':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'Vắng mặt':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-blue-100 text-blue-800';
                                    }
                                    ?>">
                                    <?php echo $reg['TrangThai']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../layouts/' . ($isAdmin ? 'admin_footer.php' : 'footer.php'); ?>
