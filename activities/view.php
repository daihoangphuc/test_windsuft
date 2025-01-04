<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance()->getConnection();
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$activityId) {
    header('Location: index.php');
    exit;
}

// Get activity details with registration and participation counts
$stmt = $db->prepare("
    SELECT h.*, 
           COUNT(DISTINCT dk.Id) as TongDangKy,
           COUNT(DISTINCT tg.Id) as TongThamGia,
           u.HoTen as NguoiTao
    FROM hoatdong h
    LEFT JOIN danhsachdangky dk ON dk.HoatDongId = h.Id AND dk.TrangThai = 1
    LEFT JOIN danhsachthamgia tg ON tg.HoatDongId = h.Id AND tg.TrangThai = 1
    LEFT JOIN nguoidung u ON h.NguoiTaoId = u.Id
    WHERE h.Id = ?
    GROUP BY h.Id
");
$stmt->bind_param("i", $activityId);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();

if (!$activity) {
    $_SESSION['error'] = "Hoạt động không tồn tại";
    header('Location: index.php');
    exit;
}

// Check if user is registered
$stmt = $db->prepare("
    SELECT * FROM danhsachdangky 
    WHERE NguoiDungId = ? AND HoatDongId = ? AND TrangThai = 1
");
$stmt->bind_param("ii", $_SESSION['user_id'], $activityId);
$stmt->execute();
$registration = $stmt->get_result()->fetch_assoc();

// Check if user has participated
$stmt = $db->prepare("
    SELECT * FROM danhsachthamgia 
    WHERE NguoiDungId = ? AND HoatDongId = ? AND TrangThai = 1
");
$stmt->bind_param("ii", $_SESSION['user_id'], $activityId);
$stmt->execute();
$participation = $stmt->get_result()->fetch_assoc();

$isRegistered = !empty($registration);
$remainingSlots = getRemainingSlots($activity['SoLuong'], $activity['TongDangKy']);
$percentage = getRegistrationPercentage($activity['SoLuong'], $activity['TongDangKy']);

$pageTitle = $activity['TenHoatDong'];
ob_start();
?>

<div class="p-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="flex justify-between items-start mb-6">
                <h1 class="text-2xl font-bold text-gray-900">
                    <?php echo htmlspecialchars($activity['TenHoatDong']); ?>
                </h1>
                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo getStatusClass($activity['TrangThai']); ?>">
                    <?php echo getStatusText($activity['TrangThai']); ?>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Thông tin chung</h3>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <i class="fas fa-calendar-alt w-5 mt-1 text-gray-500"></i>
                                <div class="ml-3">
                                    <p class="text-gray-600">Thời gian bắt đầu</p>
                                    <p class="font-medium"><?php echo formatDateTime($activity['NgayBatDau']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-calendar-check w-5 mt-1 text-gray-500"></i>
                                <div class="ml-3">
                                    <p class="text-gray-600">Thời gian kết thúc</p>
                                    <p class="font-medium"><?php echo formatDateTime($activity['NgayKetThuc']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-map-marker-alt w-5 mt-1 text-gray-500"></i>
                                <div class="ml-3">
                                    <p class="text-gray-600">Địa điểm</p>
                                    <p class="font-medium">
                                        <?php echo htmlspecialchars($activity['DiaDiem']); ?>
                                        <?php if ($activity['ToaDo']): ?>
                                            <a href="https://www.google.com/maps?q=<?php echo $activity['ToaDo']; ?>" 
                                               target="_blank" 
                                               class="text-blue-600 hover:underline ml-2">
                                                <i class="fas fa-external-link-alt"></i> Xem bản đồ
                                            </a>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($activity['MoTa'])): ?>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Mô tả</h3>
                        <div class="prose max-w-none">
                            <?php echo nl2br(htmlspecialchars($activity['MoTa'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Thống kê tham gia</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-600">Số lượng đăng ký</span>
                                        <span class="font-medium">
                                            <?php echo "{$activity['TongDangKy']}/{$activity['SoLuong']}"; ?>
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full <?php echo $percentage >= 90 ? 'bg-red-600' : 'bg-blue-600'; ?>"
                                             style="width: <?php echo $percentage; ?>%">
                                        </div>
                                    </div>
                                    <?php if ($remainingSlots <= 5 && $remainingSlots > 0): ?>
                                        <p class="text-sm text-red-600 mt-1">
                                            Chỉ còn <?php echo $remainingSlots; ?> slot!
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center justify-between py-2 border-t border-gray-200">
                                    <span class="text-gray-600">Đã tham gia</span>
                                    <span class="font-medium"><?php echo $activity['TongThamGia']; ?> người</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <?php if (!$isRegistered): ?>
                            <?php if (canRegister($activity)): ?>
                                <button onclick="registerActivity(<?php echo $activity['Id']; ?>)"
                                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition duration-200">
                                    Đăng ký tham gia
                                </button>
                            <?php elseif ($remainingSlots <= 0): ?>
                                <button disabled class="w-full bg-gray-300 text-gray-500 py-3 px-4 rounded-lg cursor-not-allowed">
                                    Đã đủ số lượng
                                </button>
                            <?php elseif ($activity['TrangThai'] != 0): ?>
                                <button disabled class="w-full bg-gray-300 text-gray-500 py-3 px-4 rounded-lg cursor-not-allowed">
                                    Không trong thời gian đăng ký
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                                <p class="text-green-800">Bạn đã đăng ký tham gia hoạt động này</p>
                                <button onclick="unregisterActivity(<?php echo $activity['Id']; ?>)"
                                        class="mt-2 text-red-600 hover:text-red-800">
                                    Hủy đăng ký
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function registerActivity(activityId) {
    if (confirm('Bạn có chắc chắn muốn đăng ký tham gia hoạt động này?')) {
        fetch('register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                activityId: activityId,
                action: 'register'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFlash(data.message || 'Đăng ký thành công!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showFlash(data.message || 'Có lỗi xảy ra!', 'error');
            }
        })
        .catch(error => {
            showFlash('Có lỗi xảy ra!', 'error');
        });
    }
}

function unregisterActivity(activityId) {
    if (confirm('Bạn có chắc chắn muốn hủy đăng ký hoạt động này?')) {
        fetch('register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                activityId: activityId,
                action: 'unregister'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFlash(data.message || 'Hủy đăng ký thành công!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showFlash(data.message || 'Có lỗi xảy ra!', 'error');
            }
        })
        .catch(error => {
            showFlash('Có lỗi xảy ra!', 'error');
        });
    }
}
</script>

<?php if ($activity['ToaDo']): ?>
<script>
function initMap() {
    const coordinates = '<?php echo $activity['ToaDo']; ?>'.split(',');
    const lat = parseFloat(coordinates[0]);
    const lng = parseFloat(coordinates[1]);
    
    const map = new google.maps.Map(document.getElementById('map'), {
        center: { lat, lng },
        zoom: 15
    });
    
    new google.maps.Marker({
        position: { lat, lng },
        map: map,
        title: '<?php echo htmlspecialchars($activity['DiaDiem']); ?>'
    });
}
</script>
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap">
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once '../layouts/header.php';
echo $content;
require_once '../layouts/footer.php';
?>
