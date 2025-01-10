<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/classes/Task.php';

$auth = new Auth();
$auth->requireLogin();

$task = new Task();
// Update overdue tasks automatically
$task->updateOverdueTasks();

// Get user's tasks with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$tasks = $task->getMyTasks($_SESSION['user_id'], $limit, $offset);
$totalTasks = $task->countMyTasks($_SESSION['user_id']);
$totalPages = ceil($totalTasks / $limit);

$stats = $task->getTaskStatistics($_SESSION['user_id']);

// Handle task completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
    $taskId = (int)$_POST['task_id'];
    if ($task->updateStatus($taskId, $_SESSION['user_id'], 2)) {
        $_SESSION['flash_message'] = "Nhiệm vụ đã được đánh dấu là hoàn thành!";
        header('Location: my_tasks.php');
        exit;
    } else {
        $_SESSION['flash_error'] = "Không thể cập nhật trạng thái nhiệm vụ!";
    }
}

$pageTitle = "Nhiệm vụ của tôi";
require_once '../layouts/header.php';
?>

<div class="p-4">
    <div class="mb-4">
        <h2 class="text-2xl font-bold">Nhiệm vụ của tôi</h2>
        <p class="text-gray-600">Quản lý và theo dõi các nhiệm vụ được giao</p>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold mb-2">Tổng nhiệm vụ</h3>
            <p class="text-3xl font-bold text-blue-600"><?php echo number_format($stats['total']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold mb-2">Đang thực hiện</h3>
            <p class="text-3xl font-bold text-yellow-600"><?php echo number_format($stats['inProgress']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold mb-2">Hoàn thành</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo number_format($stats['completed']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold mb-2">Quá hạn</h3>
            <p class="text-3xl font-bold text-red-600"><?php echo number_format($stats['overdue']); ?></p>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-semibold">Danh sách nhiệm vụ</h3>
        </div>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                <?php 
                echo $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                <?php 
                echo $_SESSION['flash_error'];
                unset($_SESSION['flash_error']);
                ?>
            </div>
        <?php endif; ?>

        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Tên nhiệm vụ</th>
                    <th class="px-6 py-3">Thời hạn</th>
                    <th class="px-6 py-3">Người giao</th>
                    <th class="px-6 py-3">Ngày giao</th>
                    <th class="px-6 py-3">Trạng thái</th>
                    <th class="px-6 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($tasks) > 0): ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <?php echo htmlspecialchars($task['TenNhiemVu']); ?>
                                <?php if ($task['MoTa']): ?>
                                    <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($task['MoTa']); ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo date('d/m/Y H:i', strtotime($task['NgayKetThuc'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($task['NguoiPhanCong']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo date('d/m/Y H:i', strtotime($task['NgayPhanCong'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $status_class = match($task['TrangThai']) {
                                    0 => 'text-gray-600',   // Chưa bắt đầu
                                    1 => 'text-yellow-600', // Đang thực hiện
                                    2 => 'text-green-600',  // Hoàn thành
                                    3 => 'text-red-600',    // Quá hạn
                                    default => 'text-gray-600'
                                };
                                ?>
                                <span class="font-medium <?php echo $status_class; ?>">
                                    <?php echo $task['TrangThaiText']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($task['TrangThai'] == 1): // Chỉ hiển thị nút Hoàn thành khi đang thực hiện ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="task_id" value="<?php echo $task['Id']; ?>">
                                        <button type="submit" name="complete_task" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-check"></i> Hoàn thành
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="view_task.php?id=<?php echo $task['Id']; ?>" class="text-blue-600 hover:text-blue-900 ml-2">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Bạn chưa được giao nhiệm vụ nào
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Trước
                    </a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Sau
                    </a>
                <?php endif; ?>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Hiển thị
                        <span class="font-medium"><?php echo ($offset + 1); ?></span>
                        đến
                        <span class="font-medium"><?php echo min($offset + $limit, $totalTasks); ?></span>
                        trong số
                        <span class="font-medium"><?php echo $totalTasks; ?></span>
                        kết quả
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Đầu tiên</span>
                                <span>««</span>
                            </a>
                            <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Trước</span>
                                <span>«</span>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo ($i === $page) ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Sau</span>
                                <span>»</span>
                            </a>
                            <a href="?page=<?php echo $totalPages; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Cuối cùng</span>
                                <span>»»</span>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../layouts/footer.php'; ?>
