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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['type']) && isset($_POST['amount']) && isset($_POST['description'])) {
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];

        try {
            $query = "INSERT INTO `taichinh` (`LoaiGiaoDich`, `SoTien`, `MoTa`, `NguoiDungId`, `NgayTao`) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $currentDate = date('Y-m-d H:i:s');
            $stmt->bind_param("iissi", $type, $amount, $description, $_SESSION['user_id'], $currentDate);
            
            if ($stmt->execute()) {
                echo "<script>
                    window.onload = function() {
                        alert('Thêm giao dịch thành công!');
                        window.location.href = 'index.php';
                    }
                </script>";
            } else {
                echo "<script>alert('Lỗi: Không thể thêm giao dịch!');</script>";
            }
        } catch (Exception $e) {
            echo "<script>alert('Lỗi: " . $e->getMessage() . "');</script>";
        }
    }
}

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
                            <div class="flex items-center space-x-2">
                                <button 
                                    class="edit-transaction bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600"
                                    data-transaction-id="<?= $transaction['Id'] ?>"
                                    data-type="<?= $transaction['LoaiGiaoDich'] ?>"
                                    data-amount="<?= $transaction['SoTien'] ?>"
                                    data-description="<?= htmlspecialchars($transaction['MoTa']) ?>"
                                    data-date="<?= date('Y-m-d\TH:i', strtotime($transaction['NgayGiaoDich'])) ?>"
                                >
                                    Sửa
                                </button>
                                <button 
                                    class="delete-transaction bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600"
                                    data-transaction-id="<?= $transaction['Id'] ?>"
                                >
                                    Xóa
                                </button>
                            </div>
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
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    Thêm giao dịch mới
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="transactionModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <form method="POST" id="addTransactionForm" action="" class="p-4 md:p-5">
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
                </div>
                <div class="flex items-center space-x-4">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Thêm
                    </button>
                    <button type="button" class="text-red-600 inline-flex items-center hover:text-white border border-red-600 hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center" data-modal-hide="transactionModal">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa giao dịch -->
<div id="editTransactionModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    Chỉnh sửa giao dịch
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="editTransactionModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <form id="editTransactionForm" class="p-4 md:p-5">
                <input type="hidden" id="edit_transaction_id" name="id">
                <div class="grid gap-4 mb-4">
                    <div>
                        <label for="edit_type" class="block mb-2 text-sm font-medium text-gray-900">Loại giao dịch</label>
                        <select id="edit_type" name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                            <option value="0">Thu</option>
                            <option value="1">Chi</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_amount" class="block mb-2 text-sm font-medium text-gray-900">Số tiền</label>
                        <input type="number" name="amount" id="edit_amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required>
                    </div>
                    <div>
                        <label for="edit_description" class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                        <textarea id="edit_description" name="description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500" required></textarea>
                    </div>
                    <div class="hidden">
                        <label for="edit_date" class="block mb-2 text-sm font-medium text-gray-900">Ngày giao dịch</label>
                        <input type="datetime-local" name="date" id="edit_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Cập nhật
                    </button>
                    <button type="button" class="text-red-600 inline-flex items-center hover:text-white border border-red-600 hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center" data-modal-hide="editTransactionModal">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default date for new transaction
    // document.getElementById('date').value = new Date().toISOString().slice(0, 16);

    // Handle add transaction form
    document.getElementById('addTransactionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('add_transaction.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: data.message
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Đã xảy ra lỗi khi thêm giao dịch'
            });
        });
    });

    // Handle edit transaction buttons
    document.querySelectorAll('.edit-transaction').forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('editTransactionModal');
            const form = document.getElementById('editTransactionForm');
            
            // Fill form with transaction data
            document.getElementById('edit_transaction_id').value = this.dataset.transactionId;
            document.getElementById('edit_type').value = this.dataset.type;
            document.getElementById('edit_amount').value = this.dataset.amount;
            document.getElementById('edit_description').value = this.dataset.description;
            document.getElementById('edit_date').value = this.dataset.date;
            
            // Show modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    // Handle edit form submission
    document.getElementById('editTransactionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('update_transaction.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: data.message
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Đã xảy ra lỗi khi cập nhật giao dịch'
            });
        });
    });

    // Handle delete transaction
    document.querySelectorAll('.delete-transaction').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.transactionId;
            
            Swal.fire({
                title: 'Xác nhận xóa?',
                text: "Bạn không thể hoàn tác hành động này!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', id);
                    
                    fetch('delete_transaction.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi!',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: 'Đã xảy ra lỗi khi xóa giao dịch'
                        });
                    });
                }
            });
        });
    });
    
    // Modal close buttons
    document.querySelectorAll('[data-modal-hide]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal-hide');
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    });
});
</script>

<!-- Add SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin_header.php';
echo $content;
require_once __DIR__ . '/../../layouts/admin_footer.php';
?>