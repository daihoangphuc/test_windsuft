<?php
$pageTitle = "Quản lý tài chính";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/functions.php';

$auth = new Auth();
$auth->requireAdmin();

// Khởi tạo kết nối
$db = Database::getInstance();
$conn = $db->getConnection();

// Xử lý phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý lọc
$search = isset($_GET['search']) ? $_GET['search'] : '';
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$type = isset($_GET['type']) ? (int)$_GET['type'] : -1; // -1: Tất cả, 0: Chi, 1: Thu

// Tạo điều kiện WHERE
$whereConditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $whereConditions[] = "(tc.MoTa LIKE ? OR tc.MoTa LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

if ($type !== -1) {
    $whereConditions[] = "tc.LoaiGiaoDich = ?";
    $params[] = $type;
    $types .= 'i';
}

if (!empty($startDate)) {
    $whereConditions[] = "tc.NgayGiaoDich >= ?";
    $params[] = $startDate . ' 00:00:00';
    $types .= 's';
}

if (!empty($endDate)) {
    $whereConditions[] = "tc.NgayGiaoDich <= ?";
    $params[] = $endDate . ' 23:59:59';
    $types .= 's';
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
}

// Lấy tổng số giao dịch và thống kê
$total_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN tc.LoaiGiaoDich = 0 THEN tc.SoTien ELSE 0 END) as total_income,
                    SUM(CASE WHEN tc.LoaiGiaoDich = 1 THEN tc.SoTien ELSE 0 END) as total_expense
                FROM taichinh tc" . $whereClause;

if (!empty($params)) {
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_result = $stmt->get_result();
} else {
    $total_result = $conn->query($total_query);
}

$total_row = $total_result->fetch_assoc();
$total_transactions = $total_row['total'];
$total_pages = ceil($total_transactions / $limit);
$total_income = $total_row['total_income'] ?? 0;
$total_expense = $total_row['total_expense'] ?? 0;
$balance = $total_income - $total_expense;

// Lấy danh sách giao dịch
$query = "SELECT tc.*, nd.HoTen as NguoiTao 
          FROM taichinh tc 
          LEFT JOIN nguoidung nd ON tc.NguoiDungId = nd.Id" . 
          $whereClause;

if (!empty($whereConditions)) {
    $query .= " ORDER BY tc.NgayGiaoDich DESC, tc.Id DESC LIMIT ? OFFSET ?";
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query .= " ORDER BY tc.NgayGiaoDich DESC, tc.Id DESC LIMIT $limit OFFSET $offset";
    $result = $conn->query($query);
}

// Bắt đầu output buffering cho nội dung trang
ob_start();
?>

<?php require_once __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="p-4">
    <div class="bg-white shadow rounded-lg p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Quản lý tài chính</h2>
            <div class="flex space-x-2">
                <?php 
                // Xây dựng URL cho nút xuất Excel với các tham số lọc hiện tại
                $exportUrl = 'export.php?';
                if (!empty($search)) $exportUrl .= '&search=' . urlencode($search);
                if ($type !== -1) $exportUrl .= '&type=' . $type;
                if (!empty($startDate)) $exportUrl .= '&startDate=' . urlencode($startDate);
                if (!empty($endDate)) $exportUrl .= '&endDate=' . urlencode($endDate);
                ?>
                <a href="<?php echo $exportUrl; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex items-center">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                </a>
                <button type="button" data-modal-target="transactionModal" data-modal-toggle="transactionModal" 
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                    Thêm giao dịch  
                </button>
            </div>
        </div>

        <!-- Form tìm kiếm -->
        <form class="mb-4 bg-white p-4 rounded-lg shadow grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block mb-2 text-sm font-medium text-gray-900">Tìm kiếm</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Mô tả...">
            </div>
            <div>
                <label for="type" class="block mb-2 text-sm font-medium text-gray-900">Loại giao dịch</label>
                <select name="type" id="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="-1" <?php echo $type == -1 ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="0" <?php echo $type == 0 ? 'selected' : ''; ?>>Thu</option>
                    <option value="1" <?php echo $type == 1 ? 'selected' : ''; ?>>Chi</option>
                </select>
            </div>
            <div>
                <label for="startDate" class="block mb-2 text-sm font-medium text-gray-900">Từ ngày</label>
                <input type="date" name="startDate" value="<?php echo $startDate; ?>" 
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="endDate" class="block mb-2 text-sm font-medium text-gray-900">Đến ngày</label>
                <input type="date" name="endDate" value="<?php echo $endDate; ?>"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div class="flex items-end">
                <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                    <i class="fas fa-search mr-2"></i>Lọc
                </button>
            </div>
        </form>

        <!-- Hiển thị tổng thu chi -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Tổng thu</h3>
                <p class="text-2xl font-bold text-green-600"><?php echo number_format($total_income, 0, ',', '.'); ?> đ</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Tổng chi</h3>
                <p class="text-2xl font-bold text-red-600"><?php echo number_format($total_expense, 0, ',', '.'); ?> đ</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Số dư</h3>
                <p class="text-2xl font-bold <?php echo ($total_income - $total_expense) >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo number_format($total_income - $total_expense, 0, ',', '.'); ?> đ
                </p>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Ngày</th>
                        <th scope="col" class="px-6 py-3">Loại</th>
                        <th scope="col" class="px-6 py-3">Số tiền</th>
                        <th scope="col" class="px-6 py-3">Mô tả</th>
                        <th scope="col" class="px-6 py-3">Người tạo</th>
                        <th scope="col" class="px-6 py-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($transaction = $result->fetch_assoc()): ?>
                    <tr class="bg-white border-b">
                        <td class="px-6 py-4"><?php echo date('d/m/Y', strtotime($transaction['NgayGiaoDich'])); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 font-semibold leading-tight <?php echo $transaction['LoaiGiaoDich'] == 0 ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'; ?> rounded-full">
                                <?php echo $transaction['LoaiGiaoDich'] == 0 ? 'Thu' : 'Chi'; ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 font-medium <?php echo $transaction['LoaiGiaoDich'] ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo number_format($transaction['SoTien']); ?> VNĐ
                        </td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($transaction['MoTa']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($transaction['NguoiTao']); ?></td>
                        <td class="px-6 py-4">
                            <button data-transaction-id="<?php echo $transaction['Id']; ?>" 
                                    class="edit-transaction font-medium text-blue-600 hover:underline mr-3">Sửa</button>
                            <button data-transaction-id="<?php echo $transaction['Id']; ?>" 
                                    class="delete-transaction font-medium text-red-600 hover:underline">Xóa</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-4">
            <nav aria-label="Page navigation">
                <ul class="inline-flex items-center -space-x-px">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&startDate=<?php echo urlencode($startDate); ?>&endDate=<?php echo urlencode($endDate); ?>&type=<?php echo $type; ?>" 
                           class="<?php echo $page === $i ? 'bg-blue-50 text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal thêm giao dịch -->
<div id="transactionModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    Thêm giao dịch mới
                </h3>
                <button type="button" data-modal-close="transactionModal" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Đóng</span>
                </button>
            </div>
            <!-- Modal body -->
            <form id="addTransactionForm" class="p-4 md:p-5">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="type" class="block mb-2 text-sm font-medium text-gray-900">Loại giao dịch</label>
                        <select id="type" name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                            <option value="0">Thu</option>
                            <option value="1">Chi</option>
                        </select>
                    </div>
                    <div>
                        <label for="amount" class="block mb-2 text-sm font-medium text-gray-900">Số tiền</label>
                        <input type="number" name="amount" id="amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="description" class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                        <textarea id="description" name="description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500" required></textarea>
                    </div>
                    <div>
                        <label for="date" class="block mb-2 text-sm font-medium text-gray-900">Ngày giao dịch</label>
                        <input type="datetime-local" name="date" id="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-4">
                    <button type="button" data-modal-close="transactionModal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Hủy
                    </button>
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Thêm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal sửa giao dịch -->
<div id="editTransactionModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    Sửa giao dịch
                </h3>
                <button type="button" data-modal-close="editTransactionModal" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Đóng</span>
                </button>
            </div>
            <!-- Modal body -->
            <form id="editTransactionForm" class="p-4 md:p-5">
                <input type="hidden" id="editTransactionId" name="id">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="editType" class="block mb-2 text-sm font-medium text-gray-900">Loại giao dịch</label>
                        <select id="editType" name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                            <option value="0">Thu</option>
                            <option value="1">Chi</option>
                        </select>
                    </div>
                    <div>
                        <label for="editAmount" class="block mb-2 text-sm font-medium text-gray-900">Số tiền</label>
                        <input type="number" name="amount" id="editAmount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="editDescription" class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                        <textarea id="editDescription" name="description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500" required></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-4">
                    <button type="button" data-modal-close="editTransactionModal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Hủy
                    </button>
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default date for new transaction
    document.getElementById('date').value = new Date().toISOString().slice(0, 16);

    // Handle add transaction form
    document.getElementById('addTransactionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('type', document.getElementById('type').value);
        formData.append('amount', document.getElementById('amount').value);
        formData.append('description', document.getElementById('description').value);
        formData.append('date', document.getElementById('date').value);

        fetch('process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Đã xảy ra lỗi khi thêm giao dịch');
        });
    });

    // Handle edit transaction
    document.querySelectorAll('.edit-transaction').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const id = this.dataset.transactionId;
            const type = row.querySelector('td:nth-child(2) span').textContent.trim() === 'Thu' ? '0' : '1';
            const amount = row.querySelector('td:nth-child(3)').textContent.replace(/[^\d]/g, '');
            const description = row.querySelector('td:nth-child(4)').textContent;

            document.getElementById('editTransactionId').value = id;
            document.getElementById('editType').value = type;
            document.getElementById('editAmount').value = amount;
            document.getElementById('editDescription').value = description;

            openModal('editTransactionModal');
        });
    });

    // Handle edit transaction form
    document.getElementById('editTransactionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'edit');
        formData.append('id', document.getElementById('editTransactionId').value);
        formData.append('type', document.getElementById('editType').value);
        formData.append('amount', document.getElementById('editAmount').value);
        formData.append('description', document.getElementById('editDescription').value);

        fetch('process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Đã xảy ra lỗi khi cập nhật giao dịch');
        });
    });

    // Handle delete transaction
    document.querySelectorAll('.delete-transaction').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Bạn có chắc chắn muốn xóa giao dịch này?')) {
                const id = this.dataset.transactionId;
                
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Đã xảy ra lỗi khi xóa giao dịch');
                });
            }
        });
    });

    // Handle modal close buttons
    document.querySelectorAll('[data-modal-close]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.dataset.modalClose;
            document.getElementById(modalId).classList.add('hidden');
            document.getElementById(modalId).classList.remove('flex');
        });
    });
});

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin_header.php';
echo $content;
require_once __DIR__ . '/../../layouts/admin_footer.php';
?>
