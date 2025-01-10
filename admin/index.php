<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/classes/Task.php';

function getTimeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) {
        return $diff->y . ' năm trước';
    }
    if ($diff->m > 0) {
        return $diff->m . ' tháng trước';
    }
    if ($diff->d > 0) {
        return $diff->d . ' ngày trước';
    }
    if ($diff->h > 0) {
        return $diff->h . ' giờ trước';
    }
    if ($diff->i > 0) {
        return $diff->i . ' phút trước';
    }
    return 'vài giây trước';
}

try {
    $db = Database::getInstance()->getConnection();
    $task = new Task();

    // Fetch total members
    $stmt = $db->query("SELECT COUNT(*) as total FROM nguoidung");
    $total_members = $stmt->fetch_assoc()['total'];

    // Fetch total activities
    $stmt = $db->query("SELECT COUNT(*) as total FROM hoatdong");
    $total_activities = $stmt->fetch_assoc()['total'];

    // Get task statistics
    $task_stats = $task->getTaskStatistics();

    // Fetch total balance
    $stmt = $db->query("SELECT 
        (SUM(CASE WHEN LoaiGiaoDich = 0 THEN SoTien ELSE 0 END) - 
         SUM(CASE WHEN LoaiGiaoDich = 1 THEN SoTien ELSE 0 END)) AS TongSoTien
    FROM 
        TaiChinh;");
    $total_balance = $stmt->fetch_assoc()['TongSoTien'] ?? 0;

    // Fetch recent visitors
    $stmt = $db->query("SELECT Id, HoTen, lantruycapcuoi, anhdaidien 
                        FROM nguoidung 
                        WHERE lantruycapcuoi IS NOT NULL 
                        AND DATE(lantruycapcuoi) = CURDATE()
                        ORDER BY lantruycapcuoi DESC 
                        LIMIT 5");
    if (!$stmt) {
        throw new Exception("Query error: " . $db->error);
    }
    $recent_visitors = $stmt->fetch_all(MYSQLI_ASSOC);

    // Fetch recent activities
    $stmt = $db->query("SELECT * FROM hoatdong ORDER BY NgayTao DESC LIMIT 5");
    $recent_activities = $stmt->fetch_all(MYSQLI_ASSOC);

    // Fetch recent tasks with assigned users
    $stmt = $db->query("SELECT nv.*, 
                               GROUP_CONCAT(nd.HoTen) as NguoiThucHien,
                               CASE 
                                   WHEN nv.TrangThai = 0 THEN 'Chưa bắt đầu'
                                   WHEN nv.TrangThai = 1 THEN 'Đang thực hiện'
                                   WHEN nv.TrangThai = 2 THEN 'Hoàn thành'
                                   WHEN nv.TrangThai = 3 THEN 'Quá hạn'
                               END as TrangThaiText
                        FROM nhiemvu nv 
                        LEFT JOIN phancongnhiemvu pc ON nv.Id = pc.NhiemVuId 
                        LEFT JOIN nguoidung nd ON pc.NguoiDungId = nd.Id 
                        GROUP BY nv.Id 
                        ORDER BY nv.NgayTao DESC LIMIT 5");
    $recent_tasks = $stmt->fetch_all(MYSQLI_ASSOC);

    $pageTitle = "Dashboard";
    require_once __DIR__ . '/../layouts/admin_header.php';
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
    <!-- Thống kê thành viên -->
    <div class="min-w-0 rounded-lg shadow-xs overflow-hidden bg-orange-100">
        <div class="p-4 flex items-center">
            <div class="p-3 rounded-full text-orange-500 bg-orange-100 mr-4">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                </svg>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600">
                    Tổng thành viên
                </p>
                <p class="text-lg font-semibold text-gray-700">
                    <?php echo number_format($total_members); ?>
                </p>
            </div>
        </div>
    </div>
    <!-- Thống kê hoạt động -->
    <div class="min-w-0 rounded-lg shadow-xs overflow-hidden bg-pink-100">
        <div class="p-4 flex items-center">
            <div class="p-3 rounded-full text-pink-500 bg-pink-100 mr-4">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600">
                    Tổng hoạt động
                </p>
                <p class="text-lg font-semibold text-gray-700">
                    <?php echo number_format($total_activities); ?>
                </p>
            </div>
        </div>
    </div>
    <!-- Thống kê nhiệm vụ -->
    <div class="min-w-0 rounded-lg shadow-xs overflow-hidden bg-yellow-100">
        <div class="p-4 flex items-center">
            <div class="p-3 rounded-full text-blue-500 bg-blue-100 mr-4">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600">
                    Tổng nhiệm vụ
                </p>
                <p class="text-lg font-semibold text-gray-700">
                    <?php echo number_format($task_stats['total']); ?>
                </p>
            </div>
        </div>
    </div>
    <!-- Thống kê quỹ -->
    <div class="min-w-0 rounded-lg shadow-xs overflow-hidden bg-green-100">
        <div class="p-4 flex items-center">
            <div class="p-3 rounded-full text-green-500 bg-green-100 mr-4">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600">
                    Tổng quỹ
                </p>
                <p class="text-lg font-semibold text-gray-700">
                    <?php echo number_format($total_balance); ?> VNĐ
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Truy cập gần đây -->
<div class="min-w-0 p-4 bg-white rounded-lg shadow-xs mb-8">
    <h4 class="mb-4 font-semibold text-gray-800">
        Truy cập gần đây
    </h4>
    <div class="w-full">
        <?php foreach ($recent_visitors as $visitor): ?>
        <div class="border-b border-gray-200 last:border-b-0">
            <div class="p-4 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="mr-4">
                        <img class="h-10 w-10 rounded-full object-cover" 
                             src="<?php echo htmlspecialchars($visitor['anhdaidien']); ?>" 
                             alt="<?php echo htmlspecialchars($visitor['HoTen']); ?>"
                             onerror="this.src='../assets/img/avatar.jpg';">
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700">
                            <?php echo htmlspecialchars($visitor['HoTen']); ?>
                        </p>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    <?php 
                    echo getTimeAgo($visitor['lantruycapcuoi']);
                    ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="grid gap-6 mb-8 md:grid-cols-2">
    <!-- Hoạt động gần đây -->
    <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs">
        <h4 class="mb-4 font-semibold text-gray-800">
            Hoạt động gần đây
        </h4>
        <div class="w-full overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-4 py-3">Tên hoạt động</th>
                            <th class="px-4 py-3">Thời gian</th>
                            <th class="px-4 py-3">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y">
                        <?php foreach ($recent_activities as $activity): ?>
                            <tr class="text-gray-700">
                                <td class="px-4 py-3">
                                    <div class="flex items-center text-sm">
                                        <div>
                                            <p class="font-semibold">
                                                <a href="">
                                                    
                                                <?php echo $activity['TenHoatDong']; ?>
                                                </a>
                                            </p>
                                            <p class="text-xs text-gray-600"><?php echo $activity['DiaDiem']; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo date('d/m/Y H:i', strtotime($activity['NgayBatDau'])); ?>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <?php
                                    $statusClass = '';
                                    switch ($activity['TrangThai']) {
                                        case 0:
                                            $statusClass = 'text-blue-700 bg-blue-100';
                                            $statusText = 'Sắp diễn ra';
                                            break;
                                        case 1:
                                            $statusClass = 'text-green-700 bg-green-100';
                                            $statusText = 'Đang diễn ra';
                                            break;
                                        case 2:
                                            $statusClass = 'text-gray-700 bg-gray-100';
                                            $statusText = 'Đã kết thúc';
                                            break;
                                        default:
                                            $statusClass = 'text-gray-700 bg-gray-100';
                                            $statusText = 'Không xác định';
                                    }
                                    ?>
                                    <span class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Nhiệm vụ gần đây -->
    <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs">
        <h4 class="mb-4 font-semibold text-gray-800">
            Nhiệm vụ gần đây
        </h4>
        <div class="w-full overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-4 py-3">Tên nhiệm vụ</th>
                            <th class="px-4 py-3">Người thực hiện</th>
                            <th class="px-4 py-3">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y">
                        <?php foreach ($recent_tasks as $task): ?>
                            <tr class="text-gray-700">
                                <td class="px-4 py-3">
                                    <div class="flex items-center text-sm">
                                        <div>
                                            <p class="font-semibold"><?php echo $task['TenNhiemVu']; ?></p>
                                            <p class="text-xs text-gray-600">
                                                <?php echo date('d/m/Y H:i', strtotime($task['NgayKetThuc'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo $task['NguoiThucHien'] ?? 'Chưa phân công'; ?>
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap">
                                    <?php
                                    $statusClass = '';
                                    switch ($task['TrangThai']) {
                                        case 0:
                                            $statusClass = 'text-gray-700 bg-gray-100';
                                            break;
                                        case 1:
                                            $statusClass = 'text-yellow-700 bg-yellow-100';
                                            break;
                                        case 2:
                                            $statusClass = 'text-green-700 bg-green-100';
                                            break;
                                        case 3:
                                            $statusClass = 'text-red-700 bg-red-100';
                                            break;
                                        default:
                                            $statusClass = 'text-gray-700 bg-gray-100';
                                    }
                                    ?>
                                    <span class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo $statusClass; ?>">
                                        <?php echo $task['TrangThaiText']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
