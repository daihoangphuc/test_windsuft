<?php
require_once __DIR__ . '/../../layouts/admin_header.php';
require_once __DIR__ . '/../../includes/classes/Faculty.php';

$faculty = new Faculty($conn);
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;

$items = $faculty->getAll($page, $limit, $search);
$totalRecords = $faculty->getTotalRecords($search);
$totalPages = ceil($totalRecords / $limit);
?>

<div class="p-4 bg-white rounded-lg shadow-sm">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-[#4a90e2]">Quản lý Khoa/Trường</h2>
        <button data-modal-target="createModal" data-modal-toggle="createModal" class="bg-[#4a90e2] hover:bg-[#357abd] text-white px-4 py-2 rounded-lg transition duration-300">
            <i class="fas fa-plus mr-2"></i>Thêm mới
        </button>
    </div>

    <!-- Search and Export -->
    <div class="flex justify-between items-center mb-4">
        <form class="flex items-center">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm..." class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#4a90e2]">
            <button type="submit" class="bg-[#4a90e2] hover:bg-[#357abd] text-white px-4 py-2 rounded-lg ml-2 transition duration-300">
                <i class="fas fa-search"></i>
            </button>
        </form>
        <a href="export.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-300">
            <i class="fas fa-file-excel mr-2"></i>Xuất Excel
        </a>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Tên Khoa/Trường</th>
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
                        <button onclick="editFaculty(<?php echo $item['Id']; ?>, '<?php echo htmlspecialchars($item['TenKhoaTruong']); ?>')" class="text-blue-600 hover:text-blue-900 mr-3">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteFaculty(<?php echo $item['Id']; ?>)" class="text-red-600 hover:text-red-900">
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
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
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
                <h3 class="text-xl font-semibold text-gray-900">Thêm Khoa/Trường mới</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="createModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="createForm" action="process.php" method="POST">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tên Khoa/Trường</label>
                        <input type="text" name="tenKhoaTruong" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-[#4a90e2] focus:border-[#4a90e2] block w-full p-2.5">
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
                <h3 class="text-xl font-semibold text-gray-900">Chỉnh sửa Khoa/Trường</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="editModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editForm" action="process.php" method="POST">
                <input type="hidden" name="id" id="editId">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tên Khoa/Trường</label>
                        <input type="text" name="tenKhoaTruong" id="editTenKhoaTruong" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-[#4a90e2] focus:border-[#4a90e2] block w-full p-2.5">
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
function editFaculty(id, tenKhoaTruong) {
    document.getElementById('editId').value = id;
    document.getElementById('editTenKhoaTruong').value = tenKhoaTruong;
    const editModal = document.getElementById('editModal');
    const modal = new Modal(editModal);
    modal.show();
}

function deleteFaculty(id) {
    if (confirm('Bạn có chắc chắn muốn xóa khoa/trường này?')) {
        window.location.href = `process.php?action=delete&id=${id}`;
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
