<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance()->getConnection();
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$activityId) {
    header('Location: index.php');
    exit;
}

// Get activity details
$stmt = $db->prepare("SELECT * FROM hoatdong WHERE Id = ?");
$stmt->bind_param("i", $activityId);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();

if (!$activity) {
    header('Location: index.php');
    exit;
}

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $userId = (int)$_POST['user_id'];
    $action = $_POST['mark_attendance']; // 'mark' or 'unmark'
    
    if ($action === 'mark') {
        $stmt = $db->prepare("INSERT INTO danhsachthamgia (NguoiDungId, HoatDongId, TrangThai) VALUES (?, ?, 1)");
        $stmt->bind_param("ii", $userId, $activityId);
    } else {
        $stmt = $db->prepare("DELETE FROM danhsachthamgia WHERE NguoiDungId = ? AND HoatDongId = ?");
        $stmt->bind_param("ii", $userId, $activityId);
    }
    
    $stmt->execute();
    header("Location: manage_attendance.php?id=" . $activityId);
    exit;
}

// Get registrations and attendance
$query = "SELECT 
            n.Id as NguoiDungId,
            n.HoTen,
            n.MaSinhVien,
            n.Email,
            cv.TenChucVu,
            dk.ThoiGianDangKy,
            CASE 
                WHEN dt.Id IS NOT NULL THEN 
                    CASE 
                        WHEN dt.TrangThai = 1 THEN 'Đã tham gia'
                        WHEN dt.TrangThai = 0 THEN 'Vắng mặt'
                    END
                WHEN h.NgayKetThuc < NOW() THEN 'Vắng mặt'
                ELSE 'Đã đăng ký'
            END as TrangThai
          FROM danhsachdangky dk
          JOIN nguoidung n ON dk.NguoiDungId = n.Id
          LEFT JOIN chucvu cv ON n.ChucVuId = cv.Id
          LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = dk.HoatDongId AND dt.NguoiDungId = n.Id
          JOIN hoatdong h ON dk.HoatDongId = h.Id
          WHERE dk.HoatDongId = ? AND dk.TrangThai = 1
          ORDER BY n.HoTen";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $activityId);
$stmt->execute();
$registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get activity status
$activity_ended = strtotime($activity['NgayKetThuc']) < time();

// Get statistics
$stats = [
    'total' => count($registrations),
    'attended' => 0,
    'absent' => 0
];

foreach ($registrations as $reg) {
    if ($reg['TrangThai'] === 'Đã tham gia') {
        $stats['attended']++;
    } elseif ($reg['TrangThai'] === 'Vắng mặt') {
        $stats['absent']++;
    }
}

function format_datetime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

$pageTitle = "Quản lý điểm danh";
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="p-4">
    <div class="mb-4">
        <h2 class="text-2xl font-bold">Điểm danh hoạt động</h2>
        <p class="text-gray-600"><?php echo htmlspecialchars($activity['TenHoatDong']); ?></p>
        <p class="text-gray-600">
            Thời gian: <?php echo format_datetime($activity['NgayBatDau']); ?> - <?php echo format_datetime($activity['NgayKetThuc']); ?>
        </p>
        <p class="text-gray-600">
            Địa điểm: <?php echo htmlspecialchars($activity['DiaDiem']); ?>
        </p>
    </div>

    <!-- Thống kê -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xl font-bold text-blue-600"><?php echo $stats['total']; ?></div>
            <div class="text-gray-500">Tổng số đăng ký</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xl font-bold text-green-600"><?php echo $stats['attended']; ?></div>
            <div class="text-gray-500">Đã tham gia</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xl font-bold text-red-600"><?php echo $stats['absent']; ?></div>
            <div class="text-gray-500">Vắng mặt</div>
        </div>
    </div>

    <!-- Danh sách đăng ký -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Họ tên</th>
                    <th class="px-6 py-3">MSSV</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Chức vụ</th>
                    <th class="px-6 py-3">Ngày đăng ký</th>
                    <th class="px-6 py-3">Trạng thái</th>
                    <th class="px-6 py-3">
                        <span class="sr-only">Thao tác</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $reg): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($reg['HoTen']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($reg['MaSinhVien']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($reg['Email']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($reg['TenChucVu']); ?></td>
                        <td class="px-6 py-4"><?php echo format_datetime($reg['ThoiGianDangKy']); ?></td>
                        <td class="px-6 py-4">
                            <?php
                            $status_class = '';
                            switch ($reg['TrangThai']) {
                                case 'Đã tham gia':
                                    $status_class = 'text-green-500';
                                    break;
                                case 'Vắng mặt':
                                    $status_class = 'text-red-500';
                                    break;
                                default:
                                    $status_class = 'text-blue-500';
                            }
                            ?>
                            <span class="<?php echo $status_class; ?>"><?php echo $reg['TrangThai']; ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if (!$activity_ended): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $reg['NguoiDungId']; ?>">
                                    <?php if ($reg['TrangThai'] !== 'Đã tham gia'): ?>
                                        <button type="submit" name="mark_attendance" value="mark" class="text-green-600 hover:underline">
                                            Điểm danh
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="mark_attendance" value="unmark" class="text-red-600 hover:underline">
                                            Hủy điểm danh
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
