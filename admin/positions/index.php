<?php
$pageTitle = "Quản lý chức vụ";
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Xử lý xuất Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Tên chức vụ');
    $sheet->setCellValue('C1', 'Ngày tạo');
    
    // Style headers
    $sheet->getStyle('A1:C1')->getFont()->setBold(true);
    
    // Get data
    $positions = $conn->query("SELECT * FROM chucvu ORDER BY NgayTao DESC");
    $row = 2;
    while ($position = $positions->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $position['Id']);
        $sheet->setCellValue('B' . $row, $position['TenChucVu']);
        $sheet->setCellValue('C' . $row, date('d/m/Y H:i', strtotime($position['NgayTao'])));
        $row++;
    }
    
    // Auto size columns
    foreach (range('A', 'C') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="danh-sach-chuc-vu.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Xử lý xóa chức vụ
if (isset($_POST['delete'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM chucvu WHERE Id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Xử lý tìm kiếm và phân trang
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $whereClause = "WHERE TenChucVu LIKE ?";
    $searchParam = "%$search%";
    $params[] = &$searchParam;
    $types .= 's';
}

// Get total records for pagination
$countQuery = "SELECT COUNT(*) as total FROM chucvu $whereClause";
if (!empty($params)) {
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total = $conn->query($countQuery)->fetch_assoc()['total'];
}

$totalPages = ceil($total / $limit);

// Get positions with pagination
$query = "SELECT * FROM chucvu $whereClause ORDER BY NgayTao DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = &$limit;
$params[] = &$offset;

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$positions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="p-4">
    <div class="bg-white shadow rounded-lg p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Danh sách chức vụ</h2>
            <div class="flex items-center gap-2">
                <a href="?export=excel<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-file-excel mr-2"></i>
                    Xuất Excel
                </a>
                <button onclick="openAddModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Thêm chức vụ
                </button>
            </div>
        </div>

        <!-- Search Form -->
        <div class="mb-4">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tìm kiếm theo tên chức vụ..."
                       class="flex-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                <button type="submit" 
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                    <i class="fas fa-search mr-2"></i>
                    Tìm kiếm
                </button>
                <?php if (!empty($search)): ?>
                <a href="index.php" 
                   class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                    <i class="fas fa-times mr-2"></i>
                    Xóa bộ lọc
                </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'empty'): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Lỗi!</strong>
                    <span class="block sm:inline">Vui lòng điền đầy đủ thông tin.</span>
                </div>
            <?php elseif ($_GET['error'] === 'duplicate'): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Lỗi!</strong>
                    <span class="block sm:inline">Tên chức vụ này đã tồn tại.</span>
                </div>
            <?php elseif ($_GET['error'] === 'add' || $_GET['error'] === 'edit'): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Lỗi!</strong>
                    <span class="block sm:inline">Đã có lỗi xảy ra. Vui lòng thử lại.</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên chức vụ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($positions as $position): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $position['Id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($position['TenChucVu']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($position['NgayTao'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal(<?php echo $position['Id']; ?>, '<?php echo htmlspecialchars($position['TenChucVu']); ?>')" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                                Sửa
                            </button>
                            <button onclick="deletePosition(<?php echo $position['Id']; ?>)" 
                                    class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                                Xóa
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-4">
            <nav class="inline-flex rounded-md shadow">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white hover:bg-gray-50 rounded-l-md border border-gray-300">
                    Trước
                </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'text-blue-600 bg-blue-50' : 'text-gray-500 bg-white hover:bg-gray-50'; ?> border border-gray-300 -ml-px">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white hover:bg-gray-50 rounded-r-md border border-gray-300 -ml-px">
                    Tiếp
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="positionModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900" id="modalTitle">
                    Thêm chức vụ mới
                </h3>
                <button type="button" onclick="closeModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <form id="positionForm" method="POST">
                <input type="hidden" name="id" id="positionId">
                <div class="p-6 space-y-6">
                    <div>
                        <label for="tenChucVu" class="block mb-2 text-sm font-medium text-gray-900">
                            Tên chức vụ
                        </label>
                        <input type="text" name="tenChucVu" id="tenChucVu" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                        Lưu
                    </button>
                    <button type="button" onclick="closeModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <button type="button" class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" onclick="closeDeleteModal()">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
            <div class="p-6 text-center">
                <svg class="mx-auto mb-4 text-gray-400 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <h3 class="mb-5 text-lg font-normal text-gray-500">Bạn có chắc chắn muốn xóa chức vụ này không?</h3>
                <form id="deleteForm" method="POST" class="inline-flex">
                    <input type="hidden" name="position_id" id="deletePositionId">
                    <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center mr-2">
                        Xóa
                    </button>
                    <button type="button" onclick="closeDeleteModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Hủy
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('positionModal');
const form = document.getElementById('positionForm');
const modalTitle = document.getElementById('modalTitle');

function openAddModal() {
    modalTitle.textContent = 'Thêm chức vụ mới';
    document.getElementById('positionId').value = '';
    document.getElementById('tenChucVu').value = '';
    form.action = 'add.php';
    modal.classList.remove('hidden');
}

function openEditModal(id, name) {
    modalTitle.textContent = 'Chỉnh sửa chức vụ';
    document.getElementById('positionId').value = id;
    document.getElementById('tenChucVu').value = name;
    form.action = 'edit.php';
    modal.classList.remove('hidden');
}

function closeModal() {
    modal.classList.add('hidden');
    form.reset();
}

function deletePosition(id) {
    document.getElementById('deletePositionId').value = id;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Xử lý form xóa
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('position_id', document.getElementById('deletePositionId').value);
    
    fetch('delete_position.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hiển thị thông báo thành công
            showNotification(data.message, 'success');
            // Đóng modal
            closeDeleteModal();
            // Reload trang sau 1 giây
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Hiển thị thông báo lỗi
            showNotification(data.message, 'error');
            closeDeleteModal();
        }
    })
    .catch(error => {
        showNotification('Đã xảy ra lỗi khi xóa chức vụ', 'error');
        closeDeleteModal();
    });
});

// Hàm hiển thị thông báo
function showNotification(message, type = 'success') {
    // Kiểm tra xem đã có notification container chưa
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        container.className = 'fixed top-0 left-0 right-0 z-50 flex justify-center transform -translate-y-full transition-transform duration-300';
        document.body.appendChild(container);
    }

    // Tạo notification mới
    const notification = document.createElement('div');
    notification.className = `m-4 px-6 py-3 rounded shadow-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    notification.textContent = message;

    // Xóa thông báo cũ nếu có
    container.innerHTML = '';
    
    // Thêm thông báo mới
    container.appendChild(notification);
    
    // Hiển thị container
    requestAnimationFrame(() => {
        container.style.transform = 'translateY(0)';
    });

    // Ẩn sau 3 giây
    setTimeout(() => {
        container.style.transform = 'translateY(-100%)';
        setTimeout(() => {
            container.remove();
        }, 300);
    }, 3000);
}
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
