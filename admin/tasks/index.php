<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/classes/Task.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance()->getConnection();
$task = new Task();

// Update overdue tasks automatically
$task->updateOverdueTasks();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $taskId = (int)$_POST['task_id'];
    $newStatus = (int)$_POST['new_status'];
    
    // Kiểm tra nếu task đã hoàn thành thì không cho cập nhật sang trạng thái khác
    $currentTask = $task->get($taskId);
    if ($currentTask && $currentTask['TrangThai'] == 2 && $newStatus != 2) {
        $_SESSION['flash_error'] = "Không thể thay đổi trạng thái của nhiệm vụ đã hoàn thành!";
        header('Location: index.php');
        exit;
    }
    
    // Nếu đang cập nhật sang trạng thái hoàn thành, kiểm tra ngày kết thúc
    if ($newStatus == 2 && strtotime($currentTask['NgayKetThuc']) < time()) {
        $newStatus = 3; // Chuyển sang trạng thái quá hạn
    }
    
    // Cập nhật trạng thái
    $query = "UPDATE nhiemvu SET TrangThai = ? WHERE Id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $newStatus, $taskId);
    
    if ($stmt->execute()) {
        // After updating the status, update overdue tasks again
        $task->updateOverdueTasks();
        $_SESSION['flash_message'] = "Cập nhật trạng thái thành công!";
    } else {
        $_SESSION['flash_error'] = "Không thể cập nhật trạng thái!";
    }
    
    header('Location: index.php');
    exit;
}

// Xử lý phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Lấy tổng số nhiệm vụ
$total_query = "SELECT COUNT(*) as total FROM nhiemvu";
$total_result = $db->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_tasks = $total_row['total'];
$total_pages = ceil($total_tasks / $limit);

// Lấy danh sách người dùng cho dropdown
$users_query = "SELECT Id, HoTen FROM nguoidung WHERE TrangThai = 1 AND VaiTroId = 2";
$users_result = $db->query($users_query);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách nhiệm vụ có phân trang
$query = "SELECT 
            nv.*, 
            nd.HoTen as NguoiThucHien,
            pc.NguoiPhanCong,
            pc.NgayPhanCong,
            CASE 
                WHEN nv.TrangThai = 0 THEN 'Chưa bắt đầu'
                WHEN nv.TrangThai = 1 THEN 'Đang thực hiện'
                WHEN nv.TrangThai = 2 THEN 'Hoàn thành'
                WHEN nv.TrangThai = 3 THEN 'Quá hạn'
            END as TrangThaiText
          FROM nhiemvu nv 
          LEFT JOIN phancongnhiemvu pc ON nv.Id = pc.NhiemVuId 
          LEFT JOIN nguoidung nd ON pc.NguoiDungId = nd.Id 
          ORDER BY nv.NgayTao DESC LIMIT $limit OFFSET $offset";
$result = $db->query($query);
$tasks = $result->fetch_all(MYSQLI_ASSOC);

$pageTitle = "Quản lý nhiệm vụ";
require_once __DIR__ . '/../../layouts/admin_header.php';

// Get task statistics
$stats = $task->getTaskStatistics();

?>

<div class="p-4">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-semibold">Quản lý nhiệm vụ</h2>
        <button data-modal-target="taskModal" data-modal-toggle="taskModal" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
            Thêm nhiệm vụ mới
        </button>
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

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg cursor-grab" id="tableContainer">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">Tên nhiệm vụ</th>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">Mô tả</th>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">Người thực hiện</th>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">Trạng thái</th>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">Ngày bắt đầu</th>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">Ngày kết thúc</th>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <?php echo htmlspecialchars($task['TenNhiemVu']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo htmlspecialchars($task['MoTa']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo $task['NguoiThucHien'] ?? 'Chưa phân công'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            switch ($task['TrangThai']) {
                                case 0:
                                    $status_class = 'text-gray-700 bg-gray-100';
                                    break;
                                case 1:
                                    $status_class = 'text-yellow-700 bg-yellow-100';
                                    break;
                                case 2:
                                    $status_class = 'text-green-700 bg-green-100';
                                    break;
                                case 3:
                                    $status_class = 'text-red-700 bg-red-100';
                                    break;
                                default:
                                    $status_class = 'text-gray-700 bg-gray-100';
                            }
                            ?>
                            <span class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo $status_class; ?>">
                                <?php echo $task['TrangThaiText']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo date('d/m/Y H:i', strtotime($task['NgayBatDau'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo date('d/m/Y H:i', strtotime($task['NgayKetThuc'])); ?>
                        </td>
                        
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <button data-modal-target="statusModal-<?php echo $task['Id']; ?>" 
                                        data-modal-toggle="statusModal-<?php echo $task['Id']; ?>" 
                                        class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-edit"></i> Cập nhật trạng thái
                                </button>
                                <button data-modal-target="assignModal-<?php echo $task['Id']; ?>" 
                                        data-modal-toggle="assignModal-<?php echo $task['Id']; ?>" 
                                        class="text-yellow-600 hover:text-yellow-900">
                                    <i class="fas fa-user-plus"></i> Phân công
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal cập nhật trạng thái -->
                    <div id="statusModal-<?php echo $task['Id']; ?>" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
                        <div class="relative w-full max-w-md max-h-full">
                            <div class="relative bg-white rounded-lg shadow">
                                <div class="flex items-start justify-between p-4 border-b rounded-t">
                                    <h3 class="text-xl font-semibold text-gray-900">
                                        Cập nhật trạng thái nhiệm vụ
                                    </h3>
                                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="statusModal-<?php echo $task['Id']; ?>">
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                        </svg>
                                    </button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="task_id" value="<?php echo $task['Id']; ?>">
                                    <div class="p-6 space-y-6">
                                        <div>
                                            <label class="block mb-2 text-sm font-medium text-gray-900">Trạng thái mới</label>
                                            <select name="new_status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                                <option value="0" <?php echo $task['TrangThai'] == 0 ? 'selected' : ''; ?>>Chưa bắt đầu</option>
                                                <option value="1" <?php echo $task['TrangThai'] == 1 ? 'selected' : ''; ?>>Đang thực hiện</option>
                                                <option value="2" <?php echo $task['TrangThai'] == 2 ? 'selected' : ''; ?>>Hoàn thành</option>
                                                <option value="3" <?php echo $task['TrangThai'] == 3 ? 'selected' : ''; ?>>Quá hạn</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                                        <button type="submit" name="update_status" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                            Cập nhật
                                        </button>
                                        <button type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10" data-modal-hide="statusModal-<?php echo $task['Id']; ?>">
                                            Hủy
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal phân công -->
                    <?php include 'assign_modal.php'; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    // Thêm tính năng kéo ngang bằng chuột
    const tableContainer = document.getElementById('tableContainer');
    let isDown = false;
    let startX;
    let scrollLeft;

    tableContainer.addEventListener('mousedown', (e) => {
        isDown = true;
        tableContainer.classList.remove('cursor-grab');
        tableContainer.classList.add('cursor-grabbing');
        startX = e.pageX - tableContainer.offsetLeft;
        scrollLeft = tableContainer.scrollLeft;
    });

    tableContainer.addEventListener('mouseleave', () => {
        isDown = false;
        tableContainer.classList.remove('cursor-grabbing');
        tableContainer.classList.add('cursor-grab');
    });

    tableContainer.addEventListener('mouseup', () => {
        isDown = false;
        tableContainer.classList.remove('cursor-grabbing');
        tableContainer.classList.add('cursor-grab');
    });

    tableContainer.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - tableContainer.offsetLeft;
        const walk = (x - startX) * 2;
        tableContainer.scrollLeft = scrollLeft - walk;
    });
    </script>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 mt-4">
        <div class="flex flex-1 justify-between sm:hidden">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Previous
                </a>
            <?php endif; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Next
                </a>
            <?php endif; ?>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium"><?php echo $offset + 1; ?></span>
                    to
                    <span class="font-medium"><?php echo min($offset + $limit, $total_tasks); ?></span>
                    of
                    <span class="font-medium"><?php echo $total_tasks; ?></span>
                    results
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if($i == $page): ?>
                            <a href="?page=<?php echo $i; ?>" aria-current="page" class="relative z-10 inline-flex items-center bg-blue-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                <?php echo $i; ?>
                            </a>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal thêm nhiệm vụ mới -->
    <?php include 'add_task_modal.php'; ?>

    <?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
