<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/classes/Task.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

if ($taskId === 0) {
    header('Location: ' . BASE_URL . '/tasks/my_tasks.php');
    exit();
}

$task = new Task();
$taskDetail = $task->getTaskDetail($taskId, $userId);

if (!$taskDetail) {
    header('Location: ' . BASE_URL . '/tasks/my_tasks.php');
    exit();
}

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = (int)$_POST['status'];
    if ($task->updateTaskStatus($taskId, $userId, $newStatus)) {
        $_SESSION['flash_message'] = 'Cập nhật trạng thái thành công!';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $taskId);
        exit();
    }
}

$pageTitle = 'Chi tiết nhiệm vụ';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Chi tiết nhiệm vụ
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Thông tin chi tiết về nhiệm vụ được phân công
            </p>
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Tên nhiệm vụ
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($taskDetail['TenNhiemVu']); ?>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Mô tả
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo nl2br(htmlspecialchars($taskDetail['MoTa'])); ?>
                    </dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Người phân công
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo htmlspecialchars($taskDetail['NguoiPhanCong']); ?>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Ngày phân công
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo date('d/m/Y H:i', strtotime($taskDetail['NgayPhanCong'])); ?>
                    </dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Ngày bắt đầu
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo date('d/m/Y', strtotime($taskDetail['NgayBatDau'])); ?>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Ngày kết thúc
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo date('d/m/Y', strtotime($taskDetail['NgayKetThuc'])); ?>
                    </dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Trạng thái
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo $taskDetail['TrangThai'] == 0 ? 'bg-yellow-100 text-yellow-800' : 
                                   ($taskDetail['TrangThai'] == 1 ? 'bg-blue-100 text-blue-800' : 
                                   ($taskDetail['TrangThai'] == 2 ? 'bg-green-100 text-green-800' : 
                                   'bg-yellow-100 text-yellow-800')); ?>">
                            <?php echo $taskDetail['TrangThaiText']; ?>
                        </span>
                    </dd>
                </div>
                <?php if ($taskDetail['NgayPhanCong']): ?>
                <?php endif; ?>
            </dl>
        </div>
        
        <!-- <?php if ($taskDetail['TrangThai'] == 0): ?>
        <div class="px-4 py-5 sm:px-6">
            <form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $taskId; ?>" method="POST" class="space-y-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Cập nhật trạng thái</label>
                    <select name="status" id="status" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="0" <?php echo $taskDetail['TrangThai'] == 0 ? 'selected' : ''; ?>>Chưa hoàn thành</option>
                        <option value="1" <?php echo $taskDetail['TrangThai'] == 1 ? 'selected' : ''; ?>>Đã hoàn thành</option>
                    </select>
                </div>
                <button type="submit" 
                        name="update_status"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cập nhật trạng thái
                </button>
            </form>
        </div>
        <?php endif; ?> -->
    </div>
    
    <div class="mt-4">
        <a href="<?php echo BASE_URL; ?>/tasks/my_tasks.php" 
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Quay lại danh sách
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
