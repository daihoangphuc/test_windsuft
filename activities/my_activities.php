<?php
require_once '../config/database.php';
require_once '../config/auth.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance()->getConnection();

// Get user's activities
$query = "
    SELECT 
        h.Id,
        h.TenHoatDong,
        h.MoTa,
        h.DiaDiem,
        h.NgayBatDau,
        h.NgayKetThuc,
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
    JOIN hoatdong h ON dk.HoatDongId = h.Id
    LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id AND dt.NguoiDungId = dk.NguoiDungId
    WHERE dk.NguoiDungId = ? AND dk.TrangThai = 1
    ORDER BY h.NgayBatDau DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as TongDangKy,
        COUNT(CASE WHEN dt.TrangThai = 1 THEN 1 END) as TongThamGia,
        COUNT(CASE WHEN dt.TrangThai = 0 OR (h.NgayKetThuc < NOW() AND dt.Id IS NULL) THEN 1 END) as TongVang
    FROM danhsachdangky dk
    JOIN hoatdong h ON dk.HoatDongId = h.Id
    LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id AND dt.NguoiDungId = dk.NguoiDungId
    WHERE dk.NguoiDungId = ? AND dk.TrangThai = 1";

$stmt = $db->prepare($stats_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$pageTitle = "Hoạt động của tôi";
require_once '../layouts/header.php';
?>

<div class="p-4">
    <div class="mb-4">
        <h2 class="text-2xl font-bold dark:text-white">Hoạt động của tôi</h2>
        <p class="text-gray-600">Danh sách các hoạt động bạn đã đăng ký tham gia</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold mb-2">Tổng đăng ký</h3>
            <p class="text-3xl font-bold text-blue-600"><?php echo number_format($stats['TongDangKy']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold mb-2">Đã tham gia</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo number_format($stats['TongThamGia']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold mb-2">Vắng mặt</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo number_format($stats['TongVang']); ?></p>
        </div>
    </div>

    <!-- Activities List -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Tên hoạt động</th>
                    <th class="px-6 py-3">Thời gian</th>
                    <th class="px-6 py-3">Địa điểm</th>
                    <th class="px-6 py-3">Ngày đăng ký</th>
                    <th class="px-6 py-3">Trạng thái</th>
                    <th class="px-6 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <?php echo htmlspecialchars($activity['TenHoatDong']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                            echo date('d/m/Y H:i', strtotime($activity['NgayBatDau'])) . ' - ' . 
                                 date('d/m/Y H:i', strtotime($activity['NgayKetThuc'])); 
                            ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($activity['DiaDiem']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo date('d/m/Y H:i', strtotime($activity['ThoiGianDangKy'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $status_class = '';
                            switch ($activity['TrangThai']) {
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
                            <span class="<?php echo $status_class; ?>"><?php echo $activity['TrangThai']; ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="view_activity.php?id=<?php echo $activity['Id']; ?>" class="font-medium text-blue-600 hover:underline">
                                Xem chi tiết
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once '../layouts/footer.php';
?>
