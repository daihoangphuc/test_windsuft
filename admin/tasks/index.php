<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Task.php';

$auth = new Auth();
$auth->requireAdmin();

$task = new Task();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$tasks = $task->getAll($search, $limit, $offset);
$total_records = $task->getTotalCount($search);
$total_pages = ceil($total_records / $limit);

$pageTitle = "Quản lý Nhiệm vụ";
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container px-6 mx-auto grid">
    <h2 class="my-6 text-2xl font-semibold text-gray-700">
        Quản lý Nhiệm vụ
    </h2>

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

    <!-- Search and Add button -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex-1 max-w-md">
            <form class="flex items-center gap-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tìm kiếm nhiệm vụ..." 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        <button onclick="openAddModal()" 
                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            <i class="fas fa-plus"></i> Thêm mới
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Tên nhiệm vụ</th>
                    <th scope="col" class="px-6 py-3">Thời gian</th>
                    <th scope="col" class="px-6 py-3">Trạng thái</th>
                    <th scope="col" class="px-6 py-3">Người được phân công</th>
                    <th scope="col" class="px-6 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $item): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900">
                        <a href="view.php?id=<?php echo $item['Id']; ?>" class="text-blue-600 hover:underline">
                            <?php echo htmlspecialchars($item['TenNhiemVu']); ?>
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php 
                        echo date('d/m/Y H:i', strtotime($item['NgayBatDau'])) . ' - <br>' . 
                             date('d/m/Y H:i', strtotime($item['NgayKetThuc'])); 
                        ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 rounded text-xs font-medium <?php echo $task->getStatusClass($item['TrangThai']); ?>">
                            <?php echo $task->getStatusText($item['TrangThai']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <?php echo $item['NguoiDuocPhanCong'] ? htmlspecialchars($item['NguoiDuocPhanCong']) : '<span class="text-gray-400">Chưa phân công</span>'; ?>
                    </td>
                    <td class="px-6 py-4 space-x-2 flex whitespace-nowrap">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                                class="text-blue-600 hover:underline mr-2">
                            <i class="fas fa-edit"></i> Sửa
                        </button>
                        <button onclick="openUpdateStatusModal(<?php echo $item['Id']; ?>, <?php echo $item['TrangThai']; ?>)"
                                class="text-green-600 hover:underline mr-2">
                            <i class="fas fa-sync-alt"></i> Cập nhật TT
                        </button>
                        <button onclick="openAssignModal(<?php echo $item['Id']; ?>, '<?php echo $item['NguoiDungId'] ?? ''; ?>')"
                                class="text-purple-600 hover:underline mr-2">
                            <i class="fas fa-user-plus"></i> Phân công
                        </button>
                        <button onclick="deleteTask(<?php echo $item['Id']; ?>)"
                                class="text-red-600 hover:underline">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="flex justify-center mt-4">
        <nav aria-label="Page navigation">
            <ul class="flex items-center -space-x-px h-8 text-sm">
                <?php if ($page > 1): ?>
                <li>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 <?php echo $i === $page ? 'text-blue-600 bg-blue-50' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-2xl">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Chỉnh sửa nhiệm vụ
                </h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editForm" action="process.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tên nhiệm vụ</label>
                        <input type="text" name="tenNhiemVu" id="editTenNhiemVu" required 
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                        <textarea name="moTa" id="editMoTa" rows="4" 
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Ngày bắt đầu</label>
                            <input type="datetime-local" name="ngayBatDau" id="editNgayBatDau" required 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Ngày kết thúc</label>
                            <input type="datetime-local" name="ngayKetThuc" id="editNgayKetThuc" required 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Trạng thái</label>
                        <select name="trangThai" id="editTrangThai" required 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="0">Chưa bắt đầu</option>
                            <option value="1">Đang thực hiện</option>
                            <option value="2">Hoàn thành</option>
                            <option value="3">Quá hạn</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Lưu thay đổi
                    </button>
                    <button type="button" onclick="closeEditModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-2xl">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Thêm nhiệm vụ mới
                </h3>
                <button type="button" onclick="closeAddModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addForm" action="process.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tên nhiệm vụ</label>
                        <input type="text" name="tenNhiemVu" required 
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                        <textarea name="moTa" rows="4" 
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Ngày bắt đầu</label>
                            <input type="datetime-local" name="ngayBatDau" required 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Ngày kết thúc</label>
                            <input type="datetime-local" name="ngayKetThuc" required 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Thêm mới</button>
                    <button type="button" onclick="closeAddModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="updateStatusModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-md">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Cập nhật trạng thái
                </h3>
                <button type="button" onclick="closeUpdateStatusModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="updateStatusForm" action="process.php" method="POST">
                <input type="hidden" name="action" value="updateStatus">
                <input type="hidden" name="taskId" id="updateStatusTaskId">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Trạng thái</label>
                        <select name="trangThai" id="updateStatusSelect" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="0">Chưa bắt đầu</option>
                            <option value="1">Đang thực hiện</option>
                            <option value="2">Hoàn thành</option>
                            <option value="3">Quá hạn</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Cập nhật</button>
                    <button type="button" onclick="closeUpdateStatusModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Task Modal -->
<div id="assignModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-md">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Phân công nhiệm vụ
                </h3>
                <button type="button" onclick="closeAssignModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="assignForm" action="process.php" method="POST">
                <input type="hidden" name="action" value="assign">
                <input type="hidden" name="taskId" id="assignTaskId">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Chọn người thực hiện</label>
                        <select name="nguoiDungId" id="assignNguoiDungId" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <?php
                            $sql = "SELECT Id, HoTen FROM nguoidung WHERE TrangThai = 1";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['Id'] . "'>" . htmlspecialchars($row['HoTen']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Phân công</button>
                    <button type="button" onclick="closeAssignModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteTask(id) {
    if (confirm('Bạn có chắc chắn muốn xóa nhiệm vụ này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function openEditModal(task) {
    document.getElementById('editId').value = task.Id;
    document.getElementById('editTenNhiemVu').value = task.TenNhiemVu;
    document.getElementById('editMoTa').value = task.MoTa || '';
    document.getElementById('editNgayBatDau').value = task.NgayBatDau.slice(0, 16);
    document.getElementById('editNgayKetThuc').value = task.NgayKetThuc.slice(0, 16);
    document.getElementById('editTrangThai').value = task.TrangThai;
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function openUpdateStatusModal(taskId, currentStatus) {
    document.getElementById('updateStatusTaskId').value = taskId;
    document.getElementById('updateStatusSelect').value = currentStatus;
    document.getElementById('updateStatusModal').classList.remove('hidden');
}

function closeUpdateStatusModal() {
    document.getElementById('updateStatusModal').classList.add('hidden');
}

function openAssignModal(taskId, currentUserId) {
    document.getElementById('assignTaskId').value = taskId;
    if (currentUserId) {
        document.getElementById('assignNguoiDungId').value = currentUserId;
    }
    document.getElementById('assignModal').classList.remove('hidden');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
