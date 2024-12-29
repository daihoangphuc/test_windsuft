<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireAdmin();

$activity = new Activity();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$activities = $activity->getAll($search, $limit, $offset);
$total_records = $activity->getTotalCount($search);
$total_pages = ceil($total_records / $limit);

$pageTitle = 'Quản lý hoạt động';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<div class="p-4">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Quản lý hoạt động</h2>
        <div class="flex gap-2">
            <a href="export.php" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Xuất Excel
            </a>
            <a href="statistics.php" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Thống kê
            </a>
            <button type="button" onclick="openAddModal()" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Thêm hoạt động
            </button>
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

    <!-- Thanh tìm kiếm -->
    <div class="mb-4">
        <form class="flex items-center">   
            <label for="simple-search" class="sr-only">Search</label>
            <div class="relative w-full">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5" placeholder="Tìm kiếm hoạt động...">
            </div>
            <button type="submit" class="p-2.5 ml-2 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
                <span class="sr-only">Search</span>
            </button>
        </form>
    </div>

    <!-- Bảng danh sách hoạt động -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Tên hoạt động</th>
                    <th scope="col" class="px-6 py-3">Thời gian</th>
                    <th scope="col" class="px-6 py-3">Địa điểm</th>
                    <th scope="col" class="px-6 py-3">Số lượng</th>
                    <th scope="col" class="px-6 py-3">Người tạo</th>
                    <th scope="col" class="px-6 py-3">Trạng thái</th>
                    <th scope="col" class="px-6 py-3">Minh chứng</th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">Thao tác</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                <tr class="bg-white border-b hover:bg-gray-50">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                        <?php echo htmlspecialchars($activity['TenHoatDong']); ?>
                    </th>
                    <td class="px-6 py-4">
                        <?php 
                        echo date('d/m/Y H:i', strtotime($activity['NgayBatDau'])) . ' - ';
                        echo date('d/m/Y H:i', strtotime($activity['NgayKetThuc']));
                        ?>
                    </td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($activity['DiaDiem']); ?></td>
                    <td class="px-6 py-4"><?php echo $activity['SoLuong']; ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($activity['NguoiTao']); ?></td>
                    <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs font-semibold rounded 
                        <?php 
                            echo $activity['TrangThai'] === 0 ? 'bg-blue-100 text-blue-800' : 
                                ($activity['TrangThai'] === 1 ? 'bg-green-100 text-green-800' : 
                                'bg-gray-100 text-gray-800'); 
                        ?>">
                        <?php 
                            echo $activity['TrangThai'] === 0 ? 'Sắp diễn ra' : 
                                ($activity['TrangThai'] === 1 ? 'Đang diễn ra' : 
                                'Đã kết thúc'); 
                        ?>
                    </span>
                </td>

                    <td class="px-6 py-4">
                        <?php if (!empty($activity['DuongDanMinhChung'])): ?>
                            <a href="<?php echo '../../' . $activity['DuongDanMinhChung']; ?>" target="_blank" 
                               class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-file-alt"></i> Xem
                            </a>
                        <?php endif; ?>
                        <button onclick="openEvidenceModal(<?php echo $activity['Id']; ?>)" 
                                class="text-blue-600 hover:text-blue-900 ml-2">
                            <i class="fas fa-upload"></i> Cập nhật
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="manage_attendance.php?id=<?php echo $activity['Id']; ?>" class="font-medium text-blue-600 hover:underline">
                            <i class="fas fa-clipboard-check"></i> Điểm danh
                        </a>
                        <button type="button" onclick="openEditModal(<?php echo $activity['Id']; ?>)" class="font-medium text-yellow-600 hover:underline mr-3">
                            Sửa
                        </button>
                        <button type="button" onclick="deleteActivity(<?php echo $activity['Id']; ?>)" class="font-medium text-red-600 hover:underline">
                            Xóa
                        </button>
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
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                   class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Previous
                </a>
            <?php endif; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                   class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Next
                </a>
            <?php endif; ?>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Hiển thị từ 
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
                        <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>';
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $is_current = $i == $page;
                        ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="relative inline-flex items-center px-4 py-2 text-sm font-semibold <?php echo $is_current ? 'bg-blue-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php
                    }
                    
                    if ($end_page < $total_pages) {
                        echo '<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">...</span>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L8.832 10l3.938-3.71a.75.75 0 11-1.04-1.08l-4.5 4.25a.75.75 0 010 1.08l4.5 4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal thêm hoạt động -->
<div id="addModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Thêm hoạt động mới
                </h3>
                <button type="button" onclick="closeAddModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <form id="addForm">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-6 gap-6">
                        <div class="col-span-6">
                            <label for="TenHoatDong" class="block mb-2 text-sm font-medium text-gray-900">Tên hoạt động</label>
                            <input type="text" name="TenHoatDong" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                        </div>
                        <div class="col-span-6">
                            <label for="MoTa" class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                            <textarea name="MoTa" rows="3" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5"></textarea>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="NgayBatDau" class="block mb-2 text-sm font-medium text-gray-900">Ngày bắt đầu</label>
                            <input type="datetime-local" name="NgayBatDau" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="NgayKetThuc" class="block mb-2 text-sm font-medium text-gray-900">Ngày kết thúc</label>
                            <input type="datetime-local" name="NgayKetThuc" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                        </div>
                        <div class="col-span-6">
                            <label for="DiaDiem" class="block mb-2 text-sm font-medium text-gray-900">Địa điểm</label>
                            <input type="text" name="DiaDiem" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                        </div>
                        <div class="col-span-6">
                            <label for="ToaDo" class="block mb-2 text-sm font-medium text-gray-900">Tọa độ</label>
                            <div class="flex">
                                <input type="text" name="ToaDo" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5">
                                <button type="button" onclick="getCurrentLocation()" class="ml-2 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 text-center">
                                    <i class="fas fa-location-arrow"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="SoLuong" class="block mb-2 text-sm font-medium text-gray-900">Số lượng</label>
                            <input type="number" name="SoLuong" min="0" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5">
                        </div>
                    </div>
                </div>
                <!-- Modal footer -->
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Thêm hoạt động</button>
                    <button type="button" onclick="closeAddModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                    <button type="button" onclick="closeAddModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa hoạt động -->
<div id="editModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Chỉnh sửa hoạt động
                </h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <form id="editForm">
                <input type="hidden" name="Id" id="editId">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-6 gap-6">
                        <div class="col-span-6">
                            <label for="editTenHoatDong" class="block mb-2 text-sm font-medium text-gray-900">Tên hoạt động</label>
                            <input type="text" name="TenHoatDong" id="editTenHoatDong" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                        </div>
                        <div class="col-span-6">
                            <label for="editMoTa" class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                            <textarea name="MoTa" id="editMoTa" rows="3" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5"></textarea>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="editNgayBatDau" class="block mb-2 text-sm font-medium text-gray-900">Ngày bắt đầu</label>
                            <input type="datetime-local" name="NgayBatDau" id="editNgayBatDau" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="editNgayKetThuc" class="block mb-2 text-sm font-medium text-gray-900">Ngày kết thúc</label>
                            <input type="datetime-local" name="NgayKetThuc" id="editNgayKetThuc" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                        </div>
                        <div class="col-span-6">
                            <label for="editDiaDiem" class="block mb-2 text-sm font-medium text-gray-900">Địa điểm</label>
                            <input type="text" name="DiaDiem" id="editDiaDiem" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                        </div>
                        <div class="col-span-6">
                            <label for="editToaDo" class="block mb-2 text-sm font-medium text-gray-900">Tọa độ</label>
                            <div class="flex">
                                <input type="text" name="ToaDo" id="editToaDo" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5">
                                <button type="button" onclick="getCurrentLocation('edit')" class="ml-2 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 text-center">
                                    <i class="fas fa-location-arrow"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="editSoLuong" class="block mb-2 text-sm font-medium text-gray-900">Số lượng</label>
                            <input type="number" name="SoLuong" id="editSoLuong" min="0" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5">
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="editTrangThai" class="block mb-2 text-sm font-medium text-gray-900">Trạng thái</label>
                            <select name="TrangThai" id="editTrangThai" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" required>
                                <option value="0">Sắp diễn ra</option>
                                <option value="1">Đang diễn ra</option>
                                <option value="2">Đã kết thúc</option>
                                <option value="3">Đã hủy</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Modal footer -->
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Lưu thay đổi</button>
                    <button type="button" onclick="closeEditModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Thêm Modal cập nhật minh chứng -->
<div id="evidenceModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">
                    Cập nhật minh chứng
                </h3>
                <button type="button" onclick="closeEvidenceModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <form id="evidenceForm" enctype="multipart/form-data">
                <input type="hidden" name="activity_id" id="evidenceActivityId">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">
                            Tải lên file minh chứng
                        </label>
                        <input type="file" name="evidence" accept=".pdf,.doc,.docx" required
                               class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                        <p class="mt-1 text-sm text-gray-500">Chấp nhận file PDF, DOC hoặc DOCX (Tối đa 10MB)</p>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Cập nhật
                    </button>
                    <button type="button" onclick="closeEvidenceModal()" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteActivity(id) {
    if (confirm('Bạn có chắc chắn muốn xóa hoạt động này?')) {
        fetch('delete_activity.php?id=' + id, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra');
        });
    }
}

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.getElementById('addForm').reset();
}

function openEditModal(id) {
    fetch('get_activity.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const activity = data.activity;
                document.getElementById('editId').value = activity.Id;
                document.getElementById('editTenHoatDong').value = activity.TenHoatDong;
                document.getElementById('editMoTa').value = activity.MoTa;
                document.getElementById('editNgayBatDau').value = activity.NgayBatDau;
                document.getElementById('editNgayKetThuc').value = activity.NgayKetThuc;
                document.getElementById('editDiaDiem').value = activity.DiaDiem;
                document.getElementById('editToaDo').value = activity.ToaDo;
                document.getElementById('editSoLuong').value = activity.SoLuong;
                document.getElementById('editTrangThai').value = activity.TrangThai;
                document.getElementById('editModal').classList.remove('hidden');
            } else {
                alert(data.message || 'Không thể tải thông tin hoạt động');
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra');
        });
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editForm').reset();
}

function getCurrentLocation(type = 'add') {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const coords = position.coords.latitude + ',' + position.coords.longitude;
                if (type === 'edit') {
                    document.getElementById('editToaDo').value = coords;
                } else {
                    document.querySelector('input[name="ToaDo"]').value = coords;
                }
            },
            (error) => {
                alert('Không thể lấy vị trí: ' + error.message);
            }
        );
    } else {
        alert('Trình duyệt không hỗ trợ định vị');
    }
}

// Xử lý form thêm hoạt động
document.getElementById('addForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('add_activity.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        alert('Có lỗi xảy ra');
    });
});

// Xử lý form chỉnh sửa hoạt động
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const id = document.getElementById('editId').value;
    
    fetch('edit_activity.php?id=' + id, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        alert('Có lỗi xảy ra');
    });
});

// Thêm các hàm xử lý modal minh chứng
function openEvidenceModal(activityId) {
    document.getElementById('evidenceActivityId').value = activityId;
    document.getElementById('evidenceModal').classList.remove('hidden');
}

function closeEvidenceModal() {
    document.getElementById('evidenceModal').classList.add('hidden');
    document.getElementById('evidenceForm').reset();
}

// Xử lý submit form minh chứng
document.getElementById('evidenceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('update_evidence.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi cập nhật minh chứng!');
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
