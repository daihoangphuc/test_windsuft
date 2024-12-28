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

            <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($activity['TenHoatDong']); ?></h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-gray-600">Thời gian bắt đầu</p>
                    <p class="font-medium"><?php echo format_datetime($activity['NgayBatDau']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Thời gian kết thúc</p>
                    <p class="font-medium"><?php echo format_datetime($activity['NgayKetThuc']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Địa điểm</p>
                    <p class="font-medium"><?php echo htmlspecialchars($activity['DiaDiem']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Số lượng đăng ký</p>
                    <p class="font-medium">
                        <?php echo $activity['TongDangKy']; ?>
                        <?php echo $activity['SoLuong'] > 0 ? '/' . $activity['SoLuong'] : ''; ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-600">Số lượng tham gia</p>
                    <p class="font-medium"><?php echo $activity['TongThamGia']; ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Người tạo</p>
                    <p class="font-medium"><?php echo htmlspecialchars($activity['NguoiTao']); ?></p>
                </div>
                <?php if ($activity['ToaDo']): ?>
                <div class="col-span-2">
                    <p class="text-gray-600">Bản đồ</p>
                    <div id="map" class="h-64 w-full rounded-lg mt-2"></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <p class="text-gray-600">Mô tả</p>
                <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($activity['MoTa'])); ?></p>
            </div>

            <div class="flex gap-4">
                <?php if (!$registration && !$participation): ?>
                    <?php if (strtotime($activity['NgayKetThuc']) > time()): ?>
                        <a href="register.php?id=<?php echo $activity['Id']; ?>" 
                           class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Đăng ký tham gia
                        </a>
                    <?php endif; ?>
                <?php elseif ($registration && !$participation): ?>
                    <form method="post" action="register.php?id=<?php echo $activity['Id']; ?>">
                        <button type="submit" name="unregister" 
                                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            Hủy đăng ký
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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
require_once '../layout.php';
?>
