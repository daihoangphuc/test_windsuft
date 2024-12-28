<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance()->getConnection();

// Get filter parameters
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');

// 1. Overall Statistics
$query = "
    SELECT 
        COUNT(*) as TongHoatDong,
        SUM(CASE WHEN NgayKetThuc < NOW() THEN 1 ELSE 0 END) as HoatDongDaKetThuc,
        SUM(CASE WHEN NgayBatDau > NOW() THEN 1 ELSE 0 END) as HoatDongSapDienRa,
        SUM(CASE WHEN NgayBatDau <= NOW() AND NgayKetThuc >= NOW() THEN 1 ELSE 0 END) as HoatDongDangDienRa
    FROM hoatdong
    WHERE NgayBatDau BETWEEN ? AND ?";

$stmt = $db->prepare($query);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$overall_stats = $stmt->get_result()->fetch_assoc();

// 2. Monthly Statistics
$query = "
    SELECT 
        DATE_FORMAT(h.NgayBatDau, '%Y-%m') as Thang,
        COUNT(DISTINCT h.Id) as SoHoatDong,
        COUNT(DISTINCT dk.Id) as TongDangKy,
        COUNT(DISTINCT dt.Id) as TongThamGia
    FROM hoatdong h
    LEFT JOIN danhsachdangky dk ON dk.HoatDongId = h.Id AND dk.TrangThai = 1
    LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id
    WHERE h.NgayBatDau BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(h.NgayBatDau, '%Y-%m')
    ORDER BY Thang";

$stmt = $db->prepare($query);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$monthly_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Detailed Activity Statistics
$query = "
    SELECT 
        h.TenHoatDong,
        h.NgayBatDau,
        h.NgayKetThuc,
        n.HoTen as NguoiTao,
        COUNT(DISTINCT dk.Id) as TongDangKy,
        COUNT(DISTINCT dt.Id) as TongThamGia,
        COUNT(DISTINCT CASE WHEN dt.TrangThai = 0 THEN dt.Id END) as TongVang
    FROM hoatdong h
    LEFT JOIN nguoidung n ON h.NguoiTaoId = n.Id
    LEFT JOIN danhsachdangky dk ON dk.HoatDongId = h.Id AND dk.TrangThai = 1
    LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id
    WHERE h.NgayBatDau BETWEEN ? AND ?
    GROUP BY h.Id
    ORDER BY h.NgayBatDau DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = "Thống kê hoạt động";
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="p-4">
    <div class="mb-4">
        <h2 class="text-2xl font-bold">Thống kê hoạt động</h2>
    </div>

    <!-- Filter Form -->
    <div class="mb-6 bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex items-center gap-4">
            <div>
                <label for="startDate" class="block text-sm font-medium text-gray-700">Từ ngày</label>
                <input type="date" name="startDate" id="startDate" value="<?php echo $startDate; ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="endDate" class="block text-sm font-medium text-gray-700">Đến ngày</label>
                <input type="date" name="endDate" id="endDate" value="<?php echo $endDate; ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Lọc
                </button>
                <a href="export_statistics.php?startDate=<?php echo $startDate; ?>&endDate=<?php echo $endDate; ?>" 
                   class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    Xuất Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Overall Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-blue-600"><?php echo number_format($overall_stats['TongHoatDong']); ?></div>
            <div class="text-gray-500">Tổng số hoạt động</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-green-600"><?php echo number_format($overall_stats['HoatDongDangDienRa']); ?></div>
            <div class="text-gray-500">Đang diễn ra</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-yellow-600"><?php echo number_format($overall_stats['HoatDongSapDienRa']); ?></div>
            <div class="text-gray-500">Sắp diễn ra</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-gray-600"><?php echo number_format($overall_stats['HoatDongDaKetThuc']); ?></div>
            <div class="text-gray-500">Đã kết thúc</div>
        </div>
    </div>

    <!-- Monthly Statistics -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Thống kê theo tháng</h3>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tháng</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số hoạt động</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng đăng ký</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng tham gia</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tỷ lệ tham gia</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($monthly_stats as $stat): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo date('m/Y', strtotime($stat['Thang'] . '-01')); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo number_format($stat['SoHoatDong']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo number_format($stat['TongDangKy']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo number_format($stat['TongThamGia']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $rate = $stat['TongDangKy'] > 0 
                                    ? round(($stat['TongThamGia'] / $stat['TongDangKy']) * 100, 1) 
                                    : 0;
                                echo $rate . '%';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Activity Details -->
    <div>
        <h3 class="text-lg font-semibold mb-4">Chi tiết hoạt động</h3>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên hoạt động</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người tạo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đăng ký</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tham gia</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vắng</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tỷ lệ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($activity['TenHoatDong']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                echo date('d/m/Y H:i', strtotime($activity['NgayBatDau'])) . ' - ' . 
                                     date('d/m/Y H:i', strtotime($activity['NgayKetThuc']));
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo htmlspecialchars($activity['NguoiTao']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo number_format($activity['TongDangKy']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo number_format($activity['TongThamGia']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo number_format($activity['TongVang']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $rate = $activity['TongDangKy'] > 0 
                                    ? round(($activity['TongThamGia'] / $activity['TongDangKy']) * 100, 1) 
                                    : 0;
                                echo $rate . '%';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>