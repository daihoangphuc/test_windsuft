<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/classes/User.php';

$db = Database::getInstance()->getConnection();
$user = new User();
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

<!-- Modal thêm người dùng -->
<div id="addUserModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <div class="flex items-start justify-between p-4 border-b rounded-t dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Thêm người dùng mới
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="addUserModal">
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
                            <label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tên đăng nhập</label>
                            <input type="text" name="username" id="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                            <input type="email" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="fullname" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Họ và tên</label>
                            <input type="text" name="fullname" id="fullname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="student_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mã sinh viên</label>
                            <input type="text" name="student_id" id="student_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mật khẩu</label>
                            <input type="password" name="password" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        </div>
                        <div>
                            <label for="role" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Vai trò</label>
                            <select name="role" id="role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                                <option value="2">Thành viên</option>
                                <option value="1">Admin</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-600">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Thêm</button>
                    <button data-modal-hide="addUserModal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="p-4">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Quản lý người dùng</h2>
        <button data-modal-target="addUserModal" data-modal-toggle="addUserModal" class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
            Thêm người dùng
        </button>
    </div>

    <!-- Thanh tìm kiếm -->
    <div class="mb-4">
        <form class="flex items-center">   
            <label for="simple-search" class="sr-only">Search</label>
            <div class="relative w-full">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5" placeholder="Tìm kiếm theo tên, email, mã sinh viên...">
            </div>
            <button type="submit" class="p-2.5 ml-2 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300">
                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
                <span class="sr-only">Search</span>
            </button>
        </form>
    </div>

    <!-- Bảng danh sách người dùng -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Họ tên</th>
                    <th scope="col" class="px-6 py-3">Email</th>
                    <th scope="col" class="px-6 py-3">Mã SV</th>
                    <th scope="col" class="px-6 py-3">Chức vụ</th>
                    <th scope="col" class="px-6 py-3">Lớp</th>
                    <th scope="col" class="px-6 py-3">Khoa</th>
                    <th scope="col" class="px-6 py-3">Trạng thái</th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">Thao tác</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        <?php echo htmlspecialchars($user['HoTen']); ?>
                    </th>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($user['Email']); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($user['MaSinhVien']); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($user['TenChucVu'] ?? 'Chưa có'); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($user['TenLop'] ?? 'Chưa có'); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($user['TenKhoaTruong'] ?? 'Chưa có'); ?></td>
                    <td class="px-6 py-4">
                        <?php if ($user['TrangThai'] == 1): ?>
                            <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                                Hoạt động
                            </span>
                        <?php else: ?>
                            <span class="px-2 py-1 font-semibold leading-tight text-red-700 bg-red-100 rounded-full dark:bg-red-700 dark:text-red-100">
                                Khóa
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="edit.php?id=<?php echo $user['Id']; ?>" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Sửa</a>
                        <?php if ($user['TrangThai'] == 1): ?>
                            <a href="toggle-status.php?id=<?php echo $user['Id']; ?>&status=0" class="font-medium text-red-600 dark:text-red-500 hover:underline ml-3">Khóa</a>
                        <?php else: ?>
                            <a href="toggle-status.php?id=<?php echo $user['Id']; ?>&status=1" class="font-medium text-green-600 dark:text-green-500 hover:underline ml-3">Mở khóa</a>
                        <?php endif; ?>
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

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
