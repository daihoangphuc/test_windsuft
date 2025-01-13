<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../layouts/admin_header.php';

// Get task ID from URL
$taskId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$taskId) {
    header('Location: index.php');
    exit;
}

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch task details with member information
$query = "SELECT nv.*, m.hoten, m.masinhvien, pc.NgayPhanCong, pc.NguoiPhanCong
          FROM nhiemvu nv 
          LEFT JOIN phancongnhiemvu pc ON nv.Id = pc.NhiemVuId
          LEFT JOIN nguoidung m ON pc.NguoiDungId = m.id 
          WHERE nv.Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $taskId);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    header('Location: index.php');
    exit;
}

// Function to get status badge
function getStatusBadge($status) {
    switch ($status) {
        case 0:
            return '<span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Chưa bắt đầu</span>';
        case 1:
            return '<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Đang thực hiện</span>';
        case 2:
            return '<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Hoàn thành</span>';
        case 3:
            return '<span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Quá hạn</span>';
        default:
            return '<span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Không xác định</span>';
    }
}
?>

<div class="p-4 bg-white block sm:flex items-center justify-between border-b border-gray-200">
    <div class="mb-1 w-full">
        <div class="mb-4">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Chi tiết nhiệm vụ</h1>
        </div>
    </div>
</div>

<div class="flex flex-col md:flex-row gap-4 p-4">
    <div class="w-full md:w-2/3 bg-white rounded-lg shadow-sm p-4">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($task['TenNhiemVu']); ?></h2>
            <div class="flex items-center gap-4 text-gray-500 text-sm">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <?php 
                    echo 'Bắt đầu: ' . date('d/m/Y', strtotime($task['NgayBatDau'])) . 
                         ' - Kết thúc: ' . date('d/m/Y', strtotime($task['NgayKetThuc'])); 
                    ?>
                </span>
                <span><?php echo getStatusBadge($task['TrangThai']); ?></span>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2">Mô tả nhiệm vụ</h3>
            <div class="text-gray-700 whitespace-pre-wrap">
                <?php echo nl2br(htmlspecialchars($task['MoTa'])); ?>
            </div>
        </div>

        <?php if ($task['TenNhiemVu']): ?>
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2">Thông tin phân công</h3>
            <div class="flex items-center gap-2">
                <div class="flex-1">
                    <p class="text-sm text-gray-500">
                        Người được phân công: <?php echo htmlspecialchars($task['hoten']); ?> - <?php echo htmlspecialchars($task['masinhvien']); ?></br>
                        Ngày phân công: <?php echo date('d/m/Y H:i', strtotime($task['NgayPhanCong'])); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="flex justify-between items-center mb-4">
            <a href="/manage-htsv/admin/tasks/index.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<script>
function confirmDelete(taskId) {
    if (confirm('Bạn có chắc chắn muốn xóa nhiệm vụ này không?')) {
        window.location.href = 'delete.php?id=' + taskId;
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>