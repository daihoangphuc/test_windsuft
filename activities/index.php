<?php
require_once '../config/database.php';
require_once '../config/auth.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance()->getConnection();

// Xử lý tìm kiếm và phân trang
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng câu query
$query = "SELECT h.*, 
          COUNT(DISTINCT dk.Id) as TongDangKy,
          COUNT(DISTINCT dt.Id) as TongThamGia,
          CASE WHEN EXISTS (
              SELECT 1 FROM danhsachdangky dk2 
              WHERE dk2.HoatDongId = h.Id 
              AND dk2.NguoiDungId = ? 
              AND dk2.TrangThai = 1
          ) THEN 1 ELSE 0 END as DaDangKy
          FROM hoatdong h 
          LEFT JOIN danhsachdangky dk ON dk.HoatDongId = h.Id AND dk.TrangThai = 1
          LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id
          WHERE h.NgayKetThuc >= CURDATE()";

$count_query = "SELECT COUNT(*) as total FROM hoatdong h WHERE h.NgayKetThuc >= CURDATE()";

if ($search) {
    $search_term = "%$search%";
    $query .= " AND (h.TenHoatDong LIKE ? OR h.MoTa LIKE ? OR h.DiaDiem LIKE ?)";
    $count_query .= " AND (h.TenHoatDong LIKE ? OR h.MoTa LIKE ? OR h.DiaDiem LIKE ?)";
}

$query .= " GROUP BY h.Id ORDER BY h.NgayBatDau ASC LIMIT ? OFFSET ?";

// Đếm tổng số bản ghi
if ($search) {
    $stmt = $db->prepare($count_query);
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
} else {
    $stmt = $db->prepare($count_query);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Lấy danh sách hoạt động
if ($search) {
    $stmt = $db->prepare($query);
    $stmt->bind_param("isssii", $_SESSION['user_id'], $search_term, $search_term, $search_term, $limit, $offset);
} else {
    $stmt = $db->prepare($query);
    $stmt->bind_param("iii", $_SESSION['user_id'], $limit, $offset);
}
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Danh sách hoạt động';
require_once '../layouts/header.php';
?>

<!-- Flash Message -->
<div id="flashMessage" class="fixed top-4 right-4 z-50 hidden">
    <div class="px-4 py-3 rounded relative" role="alert">
        <span id="flashContent" class="block sm:inline"></span>
    </div>
</div>

<div class="p-4">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Danh sách hoạt động</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Khám phá và đăng ký tham gia các hoạt động sắp diễn ra</p>
    </div>

    <!-- Search Form -->
    <form method="GET" class="mb-6">
        <div class="flex gap-4">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm kiếm hoạt động..." 
                   class="flex-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
            <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Tìm kiếm
            </button>
        </div>
    </form>

    <!-- Activities Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($activities as $activity): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold text-gray-900">
                        <?php echo htmlspecialchars($activity['TenHoatDong']); ?>
                    </h3>
                    <span class="px-2 py-1 text-xs font-semibold rounded 
                    <?php 
                        if ($activity['TrangThai'] == 0) {
                            echo 'bg-blue-100 text-blue-800'; // Sắp diễn ra
                        } elseif ($activity['TrangThai'] == 1) {
                            echo 'bg-green-100 text-green-800'; // Đang diễn ra
                        } else {
                            echo 'bg-gray-100 text-gray-800'; // Đã kết thúc
                        }
                    ?>">
                    <?php 
                        if ($activity['TrangThai'] == 0) {
                            echo 'Sắp diễn ra';
                        } elseif ($activity['TrangThai'] == 1) {
                            echo 'Đang diễn ra';
                        } else {
                            echo 'Đã kết thúc';
                        }
                    ?>
                </span>

                </div>
                
                <div class="space-y-3 text-gray-600 text-sm">
                    <p class="line-clamp-2"><?php echo htmlspecialchars($activity['MoTa']); ?></p>
                    
                    <div class="flex items-center">
                        <i class="fas fa-calendar-alt w-5"></i>
                        <span>Bắt đầu: <?php echo date('d/m/Y H:i', strtotime($activity['NgayBatDau'])); ?></span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-calendar-check w-5"></i>
                        <span>Kết thúc: <?php echo date('d/m/Y H:i', strtotime($activity['NgayKetThuc'])); ?></span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt w-5"></i>
                        <span><?php echo htmlspecialchars($activity['DiaDiem']); ?></span>
                    </div>

                    <?php if ($activity['ToaDo']): ?>
                    <div class="flex items-center">
                        <i class="fas fa-location-arrow w-5"></i>
                        <a href="https://www.google.com/maps?q=<?php echo urlencode($activity['ToaDo']); ?>" 
                           target="_blank" 
                           class="text-blue-600 hover:text-blue-800">
                            Xem bản đồ
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center">
                        <i class="fas fa-users w-5"></i>
                        <span>
                            <?php echo $activity['TongDangKy']; ?>/<?php echo $activity['SoLuong']; ?> đã đăng ký
                        </span>
                    </div>
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <a href="view.php?id=<?php echo $activity['Id']; ?>" 
                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Xem chi tiết
                    </a>
                    
                    <?php if ($activity['TrangThai'] && $activity['TongDangKy'] < $activity['SoLuong']): ?>
                        <?php if ($activity['DaDangKy']): ?>
                            <button onclick="cancelRegistration(<?php echo $activity['Id']; ?>)"
                                    class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                                Hủy đăng ký
                            </button>
                        <?php else: ?>
                            <button onclick="register(<?php echo $activity['Id']; ?>)"
                                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                                Đăng ký
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-6">
        <nav class="flex justify-center">
            <ul class="flex space-x-2">
                <?php if ($page > 1): ?>
                <li>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Trước
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li>
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'text-blue-600 bg-blue-50' : 'text-gray-700 bg-white'; ?> border border-gray-300 rounded-lg hover:bg-gray-50">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Sau
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
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
