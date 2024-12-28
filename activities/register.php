<?php
require_once '../config/database.php';
require_once '../config/auth.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance()->getConnection();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $activityId = $input['activityId'] ?? 0;
    $action = $input['action'] ?? 'register';
    
    if (!$activityId) {
        echo json_encode(['success' => false, 'message' => 'ID hoạt động không hợp lệ']);
        exit;
    }
    
    // Check if activity exists and is active
    $stmt = $db->prepare("SELECT * FROM hoatdong WHERE Id = ? AND TrangThai = 1");
    $stmt->bind_param("i", $activityId);
    $stmt->execute();
    $activity = $stmt->get_result()->fetch_assoc();
    
    if (!$activity) {
        echo json_encode(['success' => false, 'message' => 'Hoạt động không tồn tại hoặc đã kết thúc']);
        exit;
    }
    
    // Check registration count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM danhsachdangky WHERE HoatDongId = ? AND TrangThai = 1");
    $stmt->bind_param("i", $activityId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($action === 'register') {
        // Check if user already registered
        $stmt = $db->prepare("SELECT Id FROM danhsachdangky WHERE HoatDongId = ? AND NguoiDungId = ? AND TrangThai = 1");
        $stmt->bind_param("ii", $activityId, $_SESSION['user_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã đăng ký hoạt động này']);
            exit;
        }
        
        // Check if activity is full
        if ($result['count'] >= $activity['SoLuong']) {
            echo json_encode(['success' => false, 'message' => 'Hoạt động đã đủ số lượng đăng ký']);
            exit;
        }
        
        // Register
        $stmt = $db->prepare("INSERT INTO danhsachdangky (HoatDongId, NguoiDungId, ThoiGianDangKy, TrangThai) VALUES (?, ?, NOW(), 1)");
        $stmt->bind_param("ii", $activityId, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể đăng ký. Vui lòng thử lại sau']);
        }
    } else {
        // Cancel registration
        $stmt = $db->prepare("UPDATE danhsachdangky SET TrangThai = 0 WHERE HoatDongId = ? AND NguoiDungId = ? AND TrangThai = 1");
        $stmt->bind_param("ii", $activityId, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể hủy đăng ký. Vui lòng thử lại sau']);
        }
    }
    exit;
}

// Handle normal page request
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$activityId) {
    header('Location: index.php');
    exit;
}

// Get activity details
$stmt = $db->prepare("
    SELECT h.*, 
           COUNT(DISTINCT dk.Id) as TongDangKy,
           COUNT(DISTINCT tg.Id) as TongThamGia
    FROM hoatdong h
    LEFT JOIN danhsachdangky dk ON dk.HoatDongId = h.Id AND dk.TrangThai = 1
    LEFT JOIN danhsachthamgia tg ON tg.HoatDongId = h.Id AND tg.TrangThai = 1
    WHERE h.Id = ? AND h.TrangThai = 1
    GROUP BY h.Id
");
$stmt->bind_param("i", $activityId);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();

if (!$activity) {
    $_SESSION['flash_error'] = 'Hoạt động không tồn tại hoặc đã kết thúc';
    header('Location: index.php');
    exit;
}

// Check if user already registered
$stmt = $db->prepare("SELECT Id FROM danhsachdangky WHERE HoatDongId = ? AND NguoiDungId = ? AND TrangThai = 1");
$stmt->bind_param("ii", $activityId, $_SESSION['user_id']);
$stmt->execute();
$isRegistered = $stmt->get_result()->num_rows > 0;

$pageTitle = htmlspecialchars($activity['TenHoatDong']);
require_once '../layouts/header.php';
?>

<div class="p-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($activity['TenHoatDong']); ?></h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold mb-2">Thông tin chi tiết</h3>
                    <div class="space-y-3">
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($activity['MoTa'])); ?></p>
                        
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-calendar-alt w-5"></i>
                            <span>Bắt đầu: <?php echo date('d/m/Y H:i', strtotime($activity['NgayBatDau'])); ?></span>
                        </div>
                        
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-calendar-check w-5"></i>
                            <span>Kết thúc: <?php echo date('d/m/Y H:i', strtotime($activity['NgayKetThuc'])); ?></span>
                        </div>
                        
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-map-marker-alt w-5"></i>
                            <span><?php echo htmlspecialchars($activity['DiaDiem']); ?></span>
                        </div>
                        
                        <?php if ($activity['ToaDo']): ?>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-location-arrow w-5"></i>
                            <a href="https://www.google.com/maps?q=<?php echo urlencode($activity['ToaDo']); ?>" 
                               target="_blank" 
                               class="text-blue-600 hover:text-blue-800">
                                Xem trên bản đồ
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-users w-5"></i>
                            <span><?php echo $activity['TongDangKy']; ?>/<?php echo $activity['SoLuong']; ?> đã đăng ký</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-2">Đăng ký tham gia</h3>
                    <?php if ($activity['TongDangKy'] >= $activity['SoLuong']): ?>
                        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
                            <p>Hoạt động đã đủ số lượng đăng ký</p>
                        </div>
                    <?php elseif ($isRegistered): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                            <p>Bạn đã đăng ký tham gia hoạt động này</p>
                        </div>
                        <button onclick="cancelRegistration(<?php echo $activity['Id']; ?>)"
                                class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors">
                            Hủy đăng ký
                        </button>
                    <?php else: ?>
                        <button onclick="register(<?php echo $activity['Id']; ?>)"
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                            Đăng ký ngay
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showFlashMessage(message, type = 'success') {
    const flashMessage = document.getElementById('flashMessage');
    const flashContent = document.getElementById('flashContent');
    const alertDiv = flashMessage.querySelector('div');
    
    // Remove existing classes
    alertDiv.className = 'px-4 py-3 rounded relative';
    
    // Add new classes based on type
    if (type === 'success') {
        alertDiv.classList.add('bg-green-100', 'border', 'border-green-400', 'text-green-700');
    } else {
        alertDiv.classList.add('bg-red-100', 'border', 'border-red-400', 'text-red-700');
    }
    
    flashContent.textContent = message;
    flashMessage.classList.remove('hidden');
    
    setTimeout(() => {
        flashMessage.classList.add('hidden');
    }, 3000);
}

function register(activityId) {
    if (confirm('Bạn có chắc chắn muốn đăng ký tham gia hoạt động này?')) {
        fetch('register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ activityId: activityId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFlashMessage('Đăng ký thành công');
                setTimeout(() => location.reload(), 1000);
            } else {
                showFlashMessage(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(error => {
            showFlashMessage('Có lỗi xảy ra', 'error');
        });
    }
}

function cancelRegistration(activityId) {
    if (confirm('Bạn có chắc chắn muốn hủy đăng ký tham gia hoạt động này?')) {
        fetch('register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                activityId: activityId,
                action: 'cancel'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFlashMessage('Hủy đăng ký thành công');
                setTimeout(() => location.reload(), 1000);
            } else {
                showFlashMessage(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(error => {
            showFlashMessage('Có lỗi xảy ra', 'error');
        });
    }
}
</script>

<?php require_once '../layouts/footer.php'; ?>
