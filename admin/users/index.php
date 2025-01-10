<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/classes/User.php';

$db = Database::getInstance()->getConnection();
$user = new User();

// Lấy danh sách lớp học
$stmt = $db->query("SELECT l.*, k.TenKhoaTruong 
                    FROM lophoc l 
                    JOIN khoatruong k ON l.KhoaTruongId = k.Id 
                    ORDER BY k.TenKhoaTruong, l.TenLop");
$classes = $stmt->fetch_all(MYSQLI_ASSOC);

$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$users = $user->getAll($search, $limit, $offset);
$total_records = $user->getTotalCount($search);
$total_pages = ceil($total_records / $limit);

$pageTitle = 'Quản lý người dùng';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Quản lý người dùng</h1>
        <div class="flex gap-4">
            <button data-modal-target="importUserModal" data-modal-toggle="importUserModal" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Import Excel
            </button>
            <button data-modal-target="addUserModal" data-modal-toggle="addUserModal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Thêm mới
            </button>
        </div>
    </div>

    <!-- Hiển thị thông báo -->
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Lỗi!</strong>
            <span class="block sm:inline"><?php echo $_SESSION['flash_error']; ?></span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-red-500" role="button" onclick="this.parentElement.parentElement.remove()" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <title>Đóng</title>
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </span>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Thành công!</strong>
            <span class="block sm:inline"><?php echo $_SESSION['flash_message']; ?></span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-green-500" role="button" onclick="this.parentElement.parentElement.remove()" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <title>Đóng</title>
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </span>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <!-- Search form -->
    <div class="mb-4">
        <form method="GET" class="flex gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm theo tên, email, mã sinh viên..." class="w-full px-4 py-2 border rounded-lg">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Tìm kiếm
            </button>
        </form>
    </div>

    <!-- Bảng danh sách người dùng -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Ảnh</th>
                    <th scope="col" class="px-6 py-3">Thông tin</th>
                    <th scope="col" class="px-6 py-3">Vai trò/Chức vụ</th>
                    <th scope="col" class="px-6 py-3">Lớp/Khoa</th>
                    <th scope="col" class="px-6 py-3">Trạng thái</th>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <img class="w-10 h-10 rounded-full" 
                                 src="<?php echo !empty($user['anhdaidien']) ? "../" . $user['anhdaidien'] : DEFAULT_AVATAR; ?>" 
                                 alt="<?php echo htmlspecialchars($user['HoTen']); ?>">
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-base font-semibold">
                                <?php echo htmlspecialchars($user['HoTen']); ?>
                            </div>
                            <div class="font-normal text-gray-500 mb-2">
                                <?php echo htmlspecialchars($user['Email']); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php if ($user['MaSinhVien']): ?>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                        <?php echo "MSSV: " . htmlspecialchars($user['MaSinhVien']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php
                                $gioiTinhText = match ($user['GioiTinh']) {
                                    1 => '<span class="ml-2 bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">GT: Nam</span>',
                                    0 => '<span class="ml-2 bg-pink-100 text-pink-800 text-xs font-medium px-2.5 py-0.5 rounded">GT: Nữ</span>',
                                    default => '<span class="ml-2 bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">GT: Khác</span>',
                                };
                                echo $gioiTinhText;
                                ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium">
                                <?php echo htmlspecialchars($user['TenChucVu'] ?? 'Chưa có'); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php
                                $vaiTroText = match ($user['VaiTroId']) {
                                    1 => '<span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Admin</span>',
                                    2 => '<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Thành viên</span>',
                                    default => '<span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Khác</span>',
                                };
                                echo $vaiTroText;
                                ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($user['TenLop']): ?>
                                <div class="font-medium">
                                    <?php echo htmlspecialchars($user['TenLop']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($user['TenKhoaTruong'] ?? ''); ?>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-500">Chưa có lớp</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-2.5 w-2.5 rounded-full <?php echo $user['TrangThai'] ? 'bg-green-500' : 'bg-red-500'; ?> mr-2"></div>
                                <button onclick="toggleUserStatus(<?php echo $user['Id']; ?>)" 
                                        class="toggle-status font-medium <?php echo $user['TrangThai'] ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?>">
                                    <?php echo $user['TrangThai'] ? 'Vô hiệu hóa' : 'Kích hoạt'; ?>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="edit.php?id=<?php echo $user['Id']; ?>" 
                               class="font-medium text-yellow-600 dark:text-yellow-500 hover:underline">
                                Sửa
                            </a>
                            <a href="/manage-htsv/admin/student/view.php?id=<?php echo $user['Id']; ?>" 
                               class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                                Chi tiết
                            </a>
                            <form action="delete.php" method="POST" class="inline ml-2" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');">
                                <input type="hidden" name="id" value="<?php echo $user['Id']; ?>">
                                <button type="submit" class="font-medium text-red-600 hover:underline">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 mt-4">
        <div class="flex flex-1 justify-between sm:hidden">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
            <?php endif; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Hiển thị
                    <span class="font-medium"><?php echo $offset + 1; ?></span>
                    đến
                    <span class="font-medium"><?php echo min($offset + $limit, $total_records); ?></span>
                    trong số
                    <span class="font-medium"><?php echo $total_records; ?></span>
                    kết quả
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <a href="#" aria-current="page" class="relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"><?php echo $i; ?></a>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
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
</div>

<!-- Modal thêm người dùng -->
<div id="addUserModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Thêm người dùng mới
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="addUserModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Đóng</span>
                </button>
            </div>
            <form id="addUserForm" action="add.php" method="POST">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="block mb-2 text-sm font-medium text-gray-900">Tên đăng nhập</label>
                            <input type="text" name="username" id="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                            <input type="email" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="fullname" class="block mb-2 text-sm font-medium text-gray-900 ">Họ và tên</label>
                            <input type="text" name="fullname" id="fullname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="student_id" class="block mb-2 text-sm font-medium text-gray-900 ">Mã sinh viên</label>
                            <input type="text" name="student_id" id="student_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-900 ">Mật khẩu</label>
                            <input type="password" name="password" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="role" class="block mb-2 text-sm font-medium text-gray-900 ">Vai trò</label>
                            <select name="role" id="role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                                <option value="2">Thành viên</option>
                                <option value="1">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label for="class_id" class="block mb-2 text-sm font-medium text-gray-900 ">Lớp</label>
                            <select name="class_id" id="class_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                <option value="">Chọn lớp</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['Id']; ?>">
                                        <?php echo htmlspecialchars($class['TenLop'] . ' - ' . $class['TenKhoaTruong']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Thêm</button>
                    <button data-modal-hide="addUserModal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import User Modal -->
<div id="importUserModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Import người dùng từ Excel
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="importUserModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Đóng</span>
                </button>
            </div>
            <form action="process_import.php" method="POST" enctype="multipart/form-data">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900" for="file_input">Chọn file Excel</label>
                        <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" id="file_input" type="file" name="excel_file" accept=".xls,.xlsx" required>
                        <p class="mt-1 text-sm text-gray-500">Chỉ chấp nhận file Excel (.xls, .xlsx)</p>
                    </div>
                    <div class="text-sm text-gray-500">
                        <p>Cấu trúc file Excel cần có các cột:</p>
                        <ul class="list-disc ml-5 mt-1">
                            <li>Mã sinh viên</li>
                            <li>Họ tên</li>
                            <li>Ngày sinh</li>
                            <li>Giới tính</li>
                            <li>Tên đăng nhập</li>
                            <li>Email</li>
                            <li>Lớp</li>
                        </ul>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Import</button>
                    <button data-modal-hide="importUserModal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo modal
    const modal = new Modal(document.getElementById('addUserModal'));
    
    // Xử lý nút đóng modal
    const closeButtons = document.querySelectorAll('[data-modal-hide="addUserModal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            modal.hide();
        });
    });
    
    // Xử lý nút mở modal
    const openButton = document.querySelector('[data-modal-target="addUserModal"]');
    openButton.addEventListener('click', () => {
        modal.show();
    });
    
    // Xử lý submit form
    const form = document.getElementById('addUserForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Kiểm tra validation
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Gửi form bằng AJAX
        fetch('add.php', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(response => response.text())
        .then(data => {
            try {
                // Thử parse JSON response
                const jsonData = JSON.parse(data);
                if (jsonData.success) {
                    alert('Thêm người dùng thành công!');
                    window.location.reload();
                } else {
                    alert(jsonData.message || 'Có lỗi xảy ra khi thêm người dùng');
                }
            } catch (e) {
                // Nếu response không phải JSON, reload trang để hiển thị kết quả
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi thêm người dùng');
        });
    });
});

function toggleUserStatus(userId) {
    if (!confirm('Bạn có chắc chắn muốn thay đổi trạng thái người dùng này?')) {
        return;
    }

    fetch('toggle_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật UI
            const button = document.querySelector(`button[onclick="toggleUserStatus(${userId})"]`);
            const statusDot = button.previousElementSibling;
            
            if (data.new_status === 1) {
                button.textContent = 'Vô hiệu hóa';
                button.classList.remove('text-green-600', 'hover:text-green-900');
                button.classList.add('text-red-600', 'hover:text-red-900');
                statusDot.classList.remove('bg-red-500');
                statusDot.classList.add('bg-green-500');
            } else {
                button.textContent = 'Kích hoạt';
                button.classList.remove('text-red-600', 'hover:text-red-900');
                button.classList.add('text-green-600', 'hover:text-green-900');
                statusDot.classList.remove('bg-green-500');
                statusDot.classList.add('bg-red-500');
            }
            
            // Hiển thị thông báo
            alert('Cập nhật trạng thái thành công!');
        } else {
            alert('Có lỗi xảy ra: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi cập nhật trạng thái');
    });
}
</script>
