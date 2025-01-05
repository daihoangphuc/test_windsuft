<?php
require_once '../config/database.php';
require_once '../config/auth.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance()->getConnection();

// Function to calculate distance between two coordinates using Haversine formula
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Earth's radius in meters
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);
    
    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;
    
    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}

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
        echo json_encode(['success' => false, 'message' => 'Bạn đang ở quá xa địa điểm hoạt động']);
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
        echo json_encode(['success' => false, 'message' => 'Lỗi khi điểm danh']);
    }
    exit;
}

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
                            $startDate = new DateTime($activity['NgayBatDau']);
                            $endDate = new DateTime($activity['NgayKetThuc']);
                            echo $startDate->format('d/m/Y H:i') . ' - ' . $endDate->format('d/m/Y H:i'); 
                            ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($activity['DiaDiem']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                            $registerDate = new DateTime($activity['ThoiGianDangKy']);
                            echo $registerDate->format('d/m/Y H:i'); 
                            ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="<?php echo $activity['TrangThai'] === 'Đã tham gia' ? 'text-green-600' : ($activity['TrangThai'] === 'Vắng mặt' ? 'text-red-600' : 'text-blue-600'); ?>">
                                <?php echo $activity['TrangThai']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $now = new DateTime();
                            $endTime = new DateTime($activity['NgayKetThuc']);
                            $timeWindow = clone $endTime;
                            $timeWindow->modify('-15 minutes');
                            
                            if ($activity['TrangThai'] === 'Đã đăng ký' && $now >= $timeWindow && $now <= $endTime):
                            ?>
                            <button onclick="submitAttendance(<?php echo $activity['Id']; ?>)" 
                                    class="text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-4 py-2">
                                Điểm danh
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function submitAttendance(activityId) {
    if (!navigator.geolocation) {
        alert('Trình duyệt của bạn không hỗ trợ định vị');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const data = new FormData();
            data.append('action', 'attendance');
            data.append('hoatDongId', activityId);
            data.append('latitude', position.coords.latitude);
            data.append('longitude', position.coords.longitude);

            fetch(window.location.href, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    window.location.reload();
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
                    message += 'Hết thời gian chờ lấy vị trí';
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
