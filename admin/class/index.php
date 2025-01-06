<?php
require_once __DIR__ . '/../../layouts/admin_header.php';
require_once __DIR__ . '/../../includes/classes/ClassRoom.php';
require_once __DIR__ . '/../../includes/classes/Faculty.php';

$classroom = new ClassRoom($conn);
$faculty = new Faculty($conn);

$search = $_GET['search'] ?? '';
$facultyId = $_GET['faculty_id'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;

$items = $classroom->getAll($page, $limit, $search, $facultyId);
$totalRecords = $classroom->getTotalRecords($search, $facultyId);
$totalPages = ceil($totalRecords / $limit);

// Lấy danh sách khoa/trường cho dropdown
$faculties = $faculty->getAll(1, 100);

// Lấy thống kê
$stats = $classroom->getStatsByFaculty();
?>

<div class="p-4 bg-white rounded-lg shadow-sm">
    <!-- Hiển thị thông báo -->
    <?php if (isset($_SESSION['success'])): ?>
        <div id="successAlert" class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div id="errorAlert" class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-[#4a90e2]">Quản lý Lớp học</h2>
        <button data-modal-target="createModal" data-modal-toggle="createModal" class="bg-[#4a90e2] hover:bg-[#357abd] text-white px-4 py-2 rounded-lg transition duration-300">
            <i class="fas fa-plus mr-2"></i>Thêm mới
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <?php foreach ($stats as $stat): ?>
        <div class="bg-white p-4 rounded-lg border border-[#e3f2fd] shadow-sm">
            <h3 class="text-lg font-semibold text-[#4a90e2]"><?php echo htmlspecialchars($stat['TenKhoaTruong']); ?></h3>
            <p class="text-gray-600">Số lớp: <?php echo $stat['SoLop']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Search and Filter -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
        <form class="flex items-center gap-2 w-full md:w-auto">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm..." class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#4a90e2] w-full md:w-auto">
            <select name="faculty_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#4a90e2]">
                <option value="">Tất cả Khoa/Trường</option>
                <?php foreach ($faculties as $f): ?>
                    <option value="<?php echo $f['Id']; ?>" <?php echo $facultyId == $f['Id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($f['TenKhoaTruong']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-[#4a90e2] hover:bg-[#357abd] text-white px-4 py-2 rounded-lg transition duration-300">
                <i class="fas fa-search"></i>
            </button>
        </form>
        <a href="export.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-300 whitespace-nowrap">
            <i class="fas fa-file-excel mr-2"></i>Xuất Excel
        </a>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Tên Lớp</th>
                    <th scope="col" class="px-6 py-3">Khoa/Trường</th>
                    <th scope="col" class="px-6 py-3">Ngày tạo</th>
                    <th scope="col" class="px-6 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="p-4 text-sm font-normal text-gray-500">
                        <?php echo htmlspecialchars($item['Id']); ?>
                    </td>
                    <td class="p-4 text-sm font-normal text-gray-500">
                        <a href="view.php?id=<?php echo $item['Id']; ?>" class="text-blue-600 hover:underline">
                            <?php echo htmlspecialchars($item['TenLop']); ?>
                        </a>
                    </td>
                    <td class="p-4 text-sm font-normal text-gray-500">
                        <a href="../faculty/view.php?id=<?php echo $item['KhoaTruongId']; ?>" class="text-blue-600 hover:underline">
                            <?php echo htmlspecialchars($item['TenKhoaTruong']); ?>
                        </a>
                    </td>
                    <td class="p-4 text-sm font-normal text-gray-500">
                        <?php echo date('d/m/Y', strtotime($item['NgayTao'])); ?>
                    </td>
                    <td class="p-4 space-x-2 whitespace-nowrap">
                        <a href="view.php?id=<?php echo $item['Id']; ?>" 
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800">
                            <i class="fas fa-eye mr-2"></i>
                            Xem
                        </a>
                        <button onclick="editClass(<?php echo $item['Id']; ?>, '<?php echo htmlspecialchars($item['TenLop']); ?>', <?php echo $item['KhoaTruongId']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteClass(<?php echo $item['Id']; ?>)" class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
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
        <nav aria-label="Page navigation">
            <ul class="inline-flex items-center -space-x-px">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&faculty_id=<?php echo $facultyId; ?>" 
                           class="<?php echo $page === $i ? 'bg-[#4a90e2] text-white' : 'bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-700'; ?> px-3 py-2 leading-tight text-gray-500 border border-gray-300">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Create Modal -->
<div id="createModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">Thêm Lớp học mới</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="createModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="createForm" action="process.php" method="POST">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tên Lớp</label>
                        <input type="text" name="tenLop" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-[#4a90e2] focus:border-[#4a90e2] block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Khoa/Trường</label>
                        <select name="khoaTruongId" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-[#4a90e2] focus:border-[#4a90e2] block w-full p-2.5">
                            <option value="">Chọn Khoa/Trường</option>
                            <?php foreach ($faculties as $f): ?>
                                <option value="<?php echo $f['Id']; ?>"><?php echo htmlspecialchars($f['TenKhoaTruong']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" name="action" value="create" class="text-white bg-[#4a90e2] hover:bg-[#357abd] focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Thêm mới</button>
                    <button type="button" data-modal-hide="createModal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">Chỉnh sửa Lớp học</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="editModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editForm" action="process.php" method="POST">
                <input type="hidden" name="id" id="editId">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tên Lớp</label>
                        <input type="text" name="tenLop" id="editTenLop" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-[#4a90e2] focus:border-[#4a90e2] block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Khoa/Trường</label>
                        <select name="khoaTruongId" id="editKhoaTruongId" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-[#4a90e2] focus:border-[#4a90e2] block w-full p-2.5">
                            <option value="">Chọn Khoa/Trường</option>
                            <?php foreach ($faculties as $f): ?>
                                <option value="<?php echo $f['Id']; ?>"><?php echo htmlspecialchars($f['TenKhoaTruong']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" name="action" value="update" class="text-white bg-[#4a90e2] hover:bg-[#357abd] focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Cập nhật</button>
                    <button type="button" data-modal-hide="editModal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Hàm ẩn thông báo
    function hideAlert(elementId) {
        const alert = document.getElementById(elementId);
        if (alert) {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 3000);
        }
    }

    // Gọi hàm khi trang tải xong
    document.addEventListener('DOMContentLoaded', function() {
        hideAlert('successAlert');
        hideAlert('errorAlert');
    });

    function editClass(id, tenLop, khoaTruongId) {
        document.getElementById('editId').value = id;
        document.getElementById('editTenLop').value = tenLop;
        document.getElementById('editKhoaTruongId').value = khoaTruongId;
        const editModal = document.getElementById('editModal');
        const modal = new Modal(editModal);
        modal.show();
    }

    function deleteClass(id) {
        if (confirm('Bạn có chắc chắn muốn xóa lớp học này?')) {
            window.location.href = `process.php?action=delete&id=${id}`;
        }
    }
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
