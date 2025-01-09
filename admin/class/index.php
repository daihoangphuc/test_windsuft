<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/ClassRoom.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance()->getConnection();

// Xử lý xóa
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Kiểm tra xem có người dùng nào thuộc lớp này không
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM nguoidung WHERE LopHocId = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $_SESSION['flash_error'] = "Không thể xóa lớp học này vì đang có " . $count . " thành viên thuộc lớp!";
    } else {
        // Thực hiện xóa nếu không có ràng buộc
        $stmt = $db->prepare("DELETE FROM lophoc WHERE Id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Đã xóa lớp học thành công!";
        } else {
            $_SESSION['flash_error'] = "Có lỗi xảy ra khi xóa lớp học!";
        }
    }
    header("Location: index.php");
    exit;
}

$classRoom = new ClassRoom($db);
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Lấy danh sách lớp học và tổng số lượng
$classes = $classRoom->getClasses($search, $limit, $offset);
$totalClasses = $classRoom->getTotalClasses($search);
$totalPages = ceil($totalClasses / $limit);

$pageTitle = "Quản lý Lớp học";
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container px-6 mx-auto grid">
    <h2 class="my-6 text-2xl font-semibold text-gray-700">
        Quản lý Lớp học
    </h2>

    <!-- Search and Add button -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex-1 max-w-md">
            <form class="flex items-center gap-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tìm kiếm lớp học..." 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" 
                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            <i class="fas fa-plus"></i> Thêm mới
        </button>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
            <?php 
            echo $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
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

    <!-- Table -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Tên Lớp</th>
                    <th scope="col" class="px-6 py-3">Khoa/Trường</th>
                    <th scope="col" class="px-6 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $item): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <?php echo $item['Id']; ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php echo htmlspecialchars($item['TenLop']); ?>
                    </td>
                    <td class="px-6 py-4">
                        <a href="../faculty/view.php?id=<?php echo $item['KhoaTruongId']; ?>" class="text-blue-600 hover:text-blue-900">
                            <?php echo htmlspecialchars($item['TenKhoaTruong']); ?>
                        </a>
                    </td>
                    <td class="px-6 py-4 space-x-2">
                        <a href="view.php?id=<?php echo $item['Id']; ?>" 
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800">
                            <i class="fas fa-eye mr-2"></i>
                            Xem
                        </a>
                        <button onclick="editClass(<?php echo $item['Id']; ?>, '<?php echo htmlspecialchars($item['TenLop']); ?>', <?php echo $item['KhoaTruongId']; ?>)" 
                                class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?action=delete&id=<?php echo $item['Id']; ?>" 
                           onclick="return confirm('Bạn có chắc chắn muốn xóa lớp học này?')" 
                           class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </a>
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
            <ul class="flex items-center -space-x-px h-8 text-sm">
                <?php if ($page > 1): ?>
                <li>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 <?php echo $i === $page ? 'text-blue-600 bg-blue-50' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
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

<!-- Add Modal -->
<div id="addModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-md">
        <!-- Thêm overlay nền tối -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">Thêm Lớp học mới</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" onclick="document.getElementById('addModal').classList.add('hidden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="process.php" method="POST">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tên Lớp</label>
                        <input type="text" name="tenLop" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Khoa/Trường</label>
                        <select name="khoaTruongId" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">Chọn Khoa/Trường</option>
                            <?php
                            $stmt = $db->query("SELECT * FROM khoatruong ORDER BY TenKhoaTruong");
                            while ($faculty = $stmt->fetch_assoc()):
                            ?>
                            <option value="<?php echo $faculty['Id']; ?>">
                                <?php echo htmlspecialchars($faculty['TenKhoaTruong']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" name="action" value="create" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Thêm mới</button>
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center">
    <div class="relative w-full max-w-md">
        <!-- Thêm overlay nền tối -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">Chỉnh sửa Lớp học</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" onclick="document.getElementById('editModal').classList.add('hidden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="process.php" method="POST">
                <input type="hidden" name="id" id="editId">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Tên Lớp</label>
                        <input type="text" name="tenLop" id="editTenLop" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Khoa/Trường</label>
                        <select name="khoaTruongId" id="editKhoaTruongId" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">Chọn Khoa/Trường</option>
                            <?php
                            $stmt = $db->query("SELECT * FROM khoatruong ORDER BY TenKhoaTruong");
                            while ($faculty = $stmt->fetch_assoc()):
                            ?>
                            <option value="<?php echo $faculty['Id']; ?>">
                                <?php echo htmlspecialchars($faculty['TenKhoaTruong']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
                    <button type="submit" name="action" value="update" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Cập nhật</button>
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editClass(id, tenLop, khoaTruongId) {
    document.getElementById('editId').value = id;
    document.getElementById('editTenLop').value = tenLop;
    document.getElementById('editKhoaTruongId').value = khoaTruongId;
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
