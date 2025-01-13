<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance()->getConnection();

$id = $_GET['id'] ?? 0;

// Get activity details
$stmt = $db->prepare("
    SELECT h.*, 
           COUNT(DISTINCT dk.Id) as TongDangKy,
           COUNT(DISTINCT dt.Id) as TongThamGia,
           SUM(CASE WHEN dt.TrangThai = 0 OR (dt.Id IS NULL AND h.NgayKetThuc < DATE_SUB(NOW(), INTERVAL 1 DAY)) THEN 1 ELSE 0 END) as TongVang
    FROM hoatdong h
    LEFT JOIN danhsachdangky dk ON h.Id = dk.HoatDongId AND dk.TrangThai = 1
    LEFT JOIN danhsachthamgia dt ON h.Id = dt.HoatDongId
    WHERE h.Id = ?
    GROUP BY h.Id");
$stmt->bind_param("i", $id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();

if (!$activity) {
    header('Location: index.php');
    exit;
}

// Hàm lấy dữ liệu thống kê tham gia
function get_attendance_stats($db, $activityId) {
    $activity = $db->query("SELECT TrangThai FROM hoatdong WHERE Id = $activityId")->fetch_assoc();
    
    if ($activity['TrangThai'] == 0) { // Sắp diễn ra
        $query = "
            SELECT 
                COUNT(*) as total_registrations
            FROM danhsachdangky
            WHERE HoatDongId = ? AND TrangThai = 1
        ";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $activityId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'type' => 'registration',
            'data' => [
                'labels' => ['Đã đăng ký'],
                'values' => [$result['total_registrations']]
            ]
        ];
    } else { // Đang diễn ra hoặc đã kết thúc
        $query = "
            SELECT 
                COUNT(CASE WHEN TrangThai = 1 THEN 1 END) as attended,
                COUNT(CASE WHEN TrangThai = 0 THEN 1 END) as absent
            FROM danhsachthamgia
            WHERE HoatDongId = ?
        ";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $activityId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'type' => 'attendance',
            'data' => [
                'labels' => ['Đã tham gia', 'Vắng mặt'],
                'values' => [
                    (int)$result['attended'],
                    (int)$result['absent']
                ]
            ]
        ];
    }
}

$attendanceStats = get_attendance_stats($db, $id);

$pageTitle = "Chi tiết hoạt động: " . $activity['TenHoatDong'];
require_once '../../layouts/admin_header.php';
?>

<div class="container mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
        <div class="w-full lg:w-auto">
            <h2 class="text-xl sm:text-2xl font-bold">
                <span class="text-black">Chi tiết hoạt động:</span>
                <span class="text-[#4a90e2] block mt-2"><?php echo htmlspecialchars($activity['TenHoatDong']); ?></span>
            </h2>
        </div>
        <div class="flex flex-row gap-2 w-full lg:w-auto">
            <a href="export_members.php?id=<?php echo $id; ?>&type=registered" 
               class="flex-1 lg:flex-none bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-center">
                <i class="fas fa-file-excel mr-2"></i>Xuất DS đăng ký
            </a>
            <a href="export_members.php?id=<?php echo $id; ?>&type=attended" 
               class="flex-1 lg:flex-none bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg text-center">
                <i class="fas fa-file-excel mr-2"></i>Xuất DS tham gia
            </a>
        </div>
    </div>

    <!-- Activity Details -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Thông tin chung: chiếm 2 phần trên màn hình lớn -->
        <div class="bg-white rounded-lg shadow p-6 md:col-span-2">
            <h3 class="text-lg font-semibold mb-4">Thông tin chung</h3>
            <div class="space-y-3">
                <p><span class="font-medium">Thời gian:</span> <?php echo formatDateTime($activity['NgayBatDau']) . ' - ' . formatDateTime($activity['NgayKetThuc']); ?></p>
                <p><span class="font-medium">Địa điểm:</span> <?php echo htmlspecialchars($activity['DiaDiem']); ?></p>
                <p><span class="font-medium">Số lượng:</span> <?php echo number_format($activity['SoLuong']); ?></p>
                <p><span class="font-medium">Trạng thái:</span> <?php echo getStatusText($activity['TrangThai']); ?></p>
                <p class="whitespace-pre-line"><span class="font-medium">Mô tả:</span> <?php echo nl2br(htmlspecialchars($activity['MoTa'])); ?></p>
            </div>
        </div>

        <!-- Thống kê tham gia: chiếm 1 phần trên màn hình lớn -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <?php echo $activity['TrangThai'] == 0 ? 'Thống kê đăng ký' : 'Thống kê tham gia'; ?>
            </h3>
            <div class="relative" style="height: 300px;">
                <canvas id="attendanceChart"></canvas>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                    <div class="text-3xl font-bold text-gray-700" style="margin-top: -30px;"> 
                        <?php 
                        $total = array_sum($attendanceStats['data']['values']);
                        if ($activity['TrangThai'] == 0) {
                            echo number_format($total); // Hiển thị tổng số đăng ký cho hoạt động sắp diễn ra
                        } else {
                            // Tính tỷ lệ tham gia
                            $attended = $attendanceStats['data']['values'][0]; // Số người tham gia
                            $rate = $total > 0 ? round(($attended / $total) * 100) : 0;
                            echo $rate . '%';
                        }
                        ?>
                    </div>
                    <div class="text-sm text-gray-500">
                        <?php echo $activity['TrangThai'] == 0 ? 'Tổng đăng ký' : 'Tỷ lệ tham gia'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registered Members List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-semibold">Danh sách đăng ký tham gia</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-6 py-3">Họ tên</th>
                        <th class="px-6 py-3">MSSV</th>
                        <th class="px-6 py-3">Thời gian đăng ký</th>
                        <th class="px-6 py-3">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $db->prepare("
                        SELECT n.HoTen, n.MaSinhVien, dk.ThoiGianDangKy,
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
                        JOIN hoatdong h ON dk.HoatDongId = h.Id
                        LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id AND dt.NguoiDungId = dk.NguoiDungId
                        WHERE dk.HoatDongId = ? AND dk.TrangThai = 1
                        ORDER BY dk.ThoiGianDangKy DESC");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($member = $result->fetch_assoc()):
                    ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <?php echo htmlspecialchars($member['HoTen']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($member['MaSinhVien']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo formatDateTime($member['ThoiGianDangKy']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $statusClass = match($member['TrangThai']) {
                                'Đã tham gia' => 'text-green-600',
                                'Vắng mặt' => 'text-red-600',
                                default => 'text-blue-600'
                            };
                            ?>
                            <span class="<?php echo $statusClass; ?> font-medium">
                                <?php echo htmlspecialchars($member['TrangThai']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Biểu đồ thống kê tham gia
const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
new Chart(attendanceCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($attendanceStats['data']['labels']); ?>,
        datasets: [{
            data: <?php echo json_encode($attendanceStats['data']['values']); ?>,
            backgroundColor: <?php echo $activity['TrangThai'] == 0 
                ? json_encode(['#3b82f6']) 
                : json_encode(['#22c55e', '#ef4444']); ?>,
            borderWidth: 0,
            cutout: '70%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const value = context.raw;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                        return `${context.label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../../layouts/admin_footer.php'; ?>
