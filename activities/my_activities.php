<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance()->getConnection();

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'attendance') {
    header('Content-Type: application/json');
    
    $hoatDongId = $_POST['hoatDongId'] ?? 0;
    $userLat = $_POST['latitude'] ?? 0;
    $userLng = $_POST['longitude'] ?? 0;
    
    // Get activity details
    $stmt = $db->prepare("SELECT ToaDo, NgayKetThuc FROM hoatdong WHERE Id = ?");
    $stmt->bind_param("i", $hoatDongId);
    $stmt->execute();
    $activity = $stmt->get_result()->fetch_assoc();
    
    if (!$activity) {
        echo json_encode(['success' => false, 'message' => 'Hoạt động không tồn tại']);
        exit;
    }
    
    // Check if within time window (15 minutes before end time)
    $endTime = new DateTime($activity['NgayKetThuc']);
    $currentTime = new DateTime();
    $timeWindow = clone $endTime;
    $timeWindow->modify('-15 minutes');
    
    if ($currentTime > $endTime || $currentTime < $timeWindow) {
        echo json_encode(['success' => false, 'message' => 'Ngoài thời gian điểm danh']);
        exit;
    }
    
    // Calculate distance
    $activityCoords = explode(',', $activity['ToaDo']);
    if (count($activityCoords) !== 2) {
        echo json_encode(['success' => false, 'message' => 'Tọa độ hoạt động không hợp lệ']);
        exit;
    }
    
    $distance = calculateDistance($userLat, $userLng, trim($activityCoords[0]), trim($activityCoords[1]));
    
    if ($distance > 200) { // 200 meters
        echo json_encode(['success' => false, 'message' => 'Bạn không ở trong pham vi diễn ra hoạt động']);
        exit;
    }
    
    // Check if already attended
    $stmt = $db->prepare("SELECT Id FROM danhsachthamgia WHERE NguoiDungId = ? AND HoatDongId = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $hoatDongId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Bạn đã điểm danh hoạt động này rồi']);
        exit;
    }
    
    // Record attendance
    $stmt = $db->prepare("INSERT INTO danhsachthamgia (NguoiDungId, HoatDongId, TrangThai) VALUES (?, ?, 1)");
    $stmt->bind_param("ii", $_SESSION['user_id'], $hoatDongId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Điểm danh thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi điểm danh']);
    }
    exit;
}

// Xử lý xuất Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_once '../vendor/autoload.php';
    
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Đặt tiêu đề
    $sheet->setCellValue('A1', 'Tên hoạt động');
    $sheet->setCellValue('B1', 'Thời gian bắt đầu');
    $sheet->setCellValue('C1', 'Thời gian kết thúc');
    $sheet->setCellValue('D1', 'Địa điểm');
    $sheet->setCellValue('E1', 'Ngày đăng ký');
    $sheet->setCellValue('F1', 'Trạng thái');
    
    // Lấy dữ liệu từ database (sử dụng các filter hiện tại)
    $activities = getActivities($db, $_SESSION['user_id'], $_GET);
    
    // Đổ dữ liệu vào file Excel
    $row = 2;
    foreach ($activities as $activity) {
        $sheet->setCellValue('A'.$row, $activity['TenHoatDong']);
        $sheet->setCellValue('B'.$row, formatDateTime($activity['NgayBatDau']));
        $sheet->setCellValue('C'.$row, formatDateTime($activity['NgayKetThuc']));
        $sheet->setCellValue('D'.$row, $activity['DiaDiem']);
        $sheet->setCellValue('E'.$row, formatDateTime($activity['ThoiGianDangKy']));
        $sheet->setCellValue('F'.$row, $activity['TrangThai']);
        $row++;
    }
    
    // Tự động điều chỉnh độ rộng cột
    foreach(range('A','F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Tạo writer và output file
    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="hoat_dong_cua_toi.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}

// Xử lý phân trang và filter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5; // Thay đổi limit thành 5
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Base query for activities
$baseQuery = "
    FROM danhsachdangky dk
    JOIN hoatdong h ON dk.HoatDongId = h.Id
    LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id AND dt.NguoiDungId = dk.NguoiDungId
    JOIN nguoidung n ON dk.NguoiDungId = n.Id
    WHERE dk.NguoiDungId = ? AND dk.TrangThai = 1";

// Add search and date filters
$params = [$_SESSION['user_id']];
$types = "i";

if (!empty($search)) {
    $baseQuery .= " AND h.TenHoatDong LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if (!empty($startDate)) {
    $baseQuery .= " AND h.NgayBatDau >= ?";
    $params[] = $startDate;
    $types .= "s";
}

if (!empty($endDate)) {
    $baseQuery .= " AND h.NgayKetThuc <= ?";
    $params[] = $endDate;
    $types .= "s";
}

// Count total records
$countQuery = "SELECT COUNT(DISTINCT dk.Id) as total " . $baseQuery;
$countStmt = $db->prepare($countQuery);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Main query for fetching activities
$mainQuery = "
    SELECT DISTINCT
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
            WHEN h.NgayKetThuc < NOW() THEN 
                CASE 
                    WHEN h.NgayKetThuc < DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'Vắng mặt'
                    ELSE 'Chờ điểm danh'
                END
            ELSE 'Đã đăng ký'
        END as TrangThai,
        h.TrangThai as HoatDongTrangThai,
        h.DuongDanMinhChung,
        CASE 
            WHEN dt.Id IS NOT NULL THEN dt.TrangThai
            WHEN h.NgayKetThuc < DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 0
            ELSE NULL
        END as ThamGiaTrangThai " . 
    $baseQuery . "
    ORDER BY h.NgayBatDau DESC 
    LIMIT ? OFFSET ?";

// Add pagination parameters
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Execute main query
$stmt = $db->prepare($mainQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(DISTINCT dk.Id) as TongDangKy,
        SUM(CASE WHEN dt.TrangThai = 1 THEN 1 ELSE 0 END) as TongThamGia,
        SUM(CASE 
            WHEN dt.TrangThai = 0 OR 
                 (dt.Id IS NULL AND h.NgayKetThuc < DATE_SUB(NOW(), INTERVAL 1 DAY)) 
            THEN 1 
            ELSE 0 
        END) as TongVang
    FROM danhsachdangky dk
    JOIN hoatdong h ON dk.HoatDongId = h.Id
    LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id AND dt.NguoiDungId = dk.NguoiDungId
    WHERE dk.NguoiDungId = ? AND dk.TrangThai = 1";

$statsStmt = $db->prepare($statsQuery);
$statsStmt->bind_param("i", $_SESSION['user_id']);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

$pageTitle = "Hoạt động của tôi";
require_once '../layouts/header.php';
?>

<div class="p-4">
    <div class="mb-4">
        <h2 class="text-2xl font-bold text-[#4a90e2]">Hoạt động của tôi</h2>
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

    <!-- Search and Filter Form -->
    <div class="bg-white p-4 rounded-lg shadow mb-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tên hoạt động..." 
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" 
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" 
                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </button>
                <a href="?export=excel<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($startDate) ? '&start_date='.urlencode($startDate) : ''; ?><?php echo !empty($endDate) ? '&end_date='.urlencode($endDate) : ''; ?>" 
                   class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                </a>
            </div>
        </form>
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
                            $start = new DateTime($activity['NgayBatDau']);
                            $end = new DateTime($activity['NgayKetThuc']);
                            echo $start->format('d/m/Y H:i') . ' - ' . $end->format('d/m/Y H:i');
                            ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($activity['DiaDiem']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $regDate = new DateTime($activity['ThoiGianDangKy']);
                            echo $regDate->format('d/m/Y H:i'); 
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $statusClass = '';
                            switch ($activity['TrangThai']) {
                                case 'Đã tham gia':
                                    $statusClass = 'text-green-600';
                                    break;
                                case 'Vắng mặt':
                                    $statusClass = 'text-red-600';
                                    break;
                                default:
                                    $statusClass = 'text-blue-600';
                            }
                            ?>
                            <span class="<?php echo $statusClass; ?> font-medium">
                                <?php echo htmlspecialchars($activity['TrangThai']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $now = new DateTime();
                            $endTime = new DateTime($activity['NgayKetThuc']);
                            $timeWindow = clone $endTime;
                            $timeWindow->modify('-15 minutes');
                            
                            if ($activity['HoatDongTrangThai'] == 1 && $now >= $timeWindow && $now <= $endTime && $activity['ThamGiaTrangThai'] === null):
                            ?>
                                <button onclick="submitAttendance(<?php echo $activity['Id']; ?>)" 
                                        class="text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-4 py-2">
                                    Điểm danh
                                </button>
                            <?php else: ?>
                                <div class="flex items-center gap-2">
                                    <a href="view.php?id=<?php echo $activity['Id']; ?>" 
                                       class="text-blue-600 hover:underline">
                                        Xem chi tiết
                                    </a>
                                    <?php if ($activity['HoatDongTrangThai'] == 2 && !empty($activity['DuongDanMinhChung'])): ?>
                                        <a href="/manage-htsv/uploads/activities/minhchung/<?php echo basename($activity['DuongDanMinhChung']); ?>" 
                                           download
                                           class="inline-flex items-center text-green-600 hover:underline">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Tải minh chứng
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-4 flex justify-center">
        <nav class="inline-flex rounded-md shadow">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo ($page-1); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($startDate) ? '&start_date='.urlencode($startDate) : ''; ?><?php echo !empty($endDate) ? '&end_date='.urlencode($endDate) : ''; ?>" 
               class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                Trước
            </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($startDate) ? '&start_date='.urlencode($startDate) : ''; ?><?php echo !empty($endDate) ? '&end_date='.urlencode($endDate) : ''; ?>" 
               class="px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-700 bg-white border-gray-300'; ?> border hover:bg-gray-50">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo ($page+1); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($startDate) ? '&start_date='.urlencode($startDate) : ''; ?><?php echo !empty($endDate) ? '&end_date='.urlencode($endDate) : ''; ?>" 
               class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                Sau
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
function submitAttendance(activityId) {
    if (!navigator.geolocation) {
        alert('Trình duyệt của bạn không hỗ trợ định vị GPS');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const data = new FormData();
            data.append('action', 'attendance');
            data.append('hoatDongId', activityId);
            data.append('latitude', position.coords.latitude);
            data.append('longitude', position.coords.longitude);

            fetch('my_activities.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi điểm danh');
            });
        },
        function(error) {
            let message = 'Lỗi khi lấy vị trí: ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message += 'Bạn đã từ chối cho phép truy cập vị trí';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message += 'Không thể lấy được vị trí';
                    break;
                case error.TIMEOUT:
                    message += 'Hết thởi gian chờ lấy vị trí';
                    break;
                default:
                    message += 'Lỗi không xác định';
            }
            alert(message);
        }
    );
}
</script>

<?php require_once '../layouts/footer.php'; ?>
