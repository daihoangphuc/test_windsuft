<?php
require_once __DIR__ . '/../../layouts/admin_header.php';
require_once __DIR__ . '/../../includes/classes/ClassRoom.php';
require_once __DIR__ . '/../../includes/classes/Student.php';

// Lấy ID của lớp từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;

$classroom = new ClassRoom($conn);
$student = new Student($conn);

// Lấy thông tin chi tiết của lớp
$classInfo = $classroom->getById($id);

// Nếu không tìm thấy lớp, chuyển hướng về trang danh sách
if (!$classInfo) {
    header('Location: index.php');
    exit;
}

// Lấy danh sách sinh viên của lớp
$students = $student->getStudentsByClass($id, $page, $limit, $search);
$totalStudents = $student->getTotalStudentsInClass($id, $search);
$totalPages = ceil($totalStudents / $limit);
?>

<div class="p-4 bg-white block sm:flex items-center justify-between border-b border-gray-200 lg:mt-1.5">
    <div class="mb-1 w-full">
        <div class="mb-4">
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl">Chi tiết Lớp học</h1>
        </div>
    </div>
</div>

<div class="flex flex-col">
    <div class="overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div class="p-4">
                <div class="mb-4 bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-2">Thông tin cơ bản</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">ID:</p>
                            <p class="font-medium"><?php echo htmlspecialchars($classInfo['Id']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Tên Lớp:</p>
                            <p class="font-medium"><?php echo htmlspecialchars($classInfo['TenLop']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Khoa/Trường:</p>
                            <p class="font-medium">
                                <a href="../faculty/view.php?id=<?php echo $classInfo['KhoaTruongId']; ?>" class="text-blue-600 hover:underline">
                                    <?php echo htmlspecialchars($classInfo['TenKhoaTruong']); ?>
                                </a>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Ngày tạo:</p>
                            <p class="font-medium"><?php echo date('d/m/Y', strtotime($classInfo['NgayTao'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Danh sách sinh viên (<?php echo $totalStudents; ?> sinh viên)</h3>
                        <div class="flex items-center space-x-2">
                            <a href="export_students.php?class_id=<?php echo $id; ?>" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-green-700 hover:bg-green-800">
                                <i class="fas fa-file-excel mr-2"></i>
                                Xuất Excel
                            </a>
                        </div>
                    </div>

                    <!-- Thanh tìm kiếm -->
                    <div class="mb-4">
                        <form action="" method="GET" class="flex items-center space-x-2">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                                   placeholder="Tìm kiếm theo tên hoặc mã sinh viên...">
                            <button type="submit" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800">
                                <i class="fas fa-search mr-2"></i>
                                Tìm kiếm
                            </button>
                        </form>
                    </div>

                    <?php if (!empty($students)): ?>
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase">Mã SV</th>
                                        <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase">Họ tên</th>
                                        <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase">Email</th>
                                        <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase">Chức vụ</th>
                                        <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($students as $student): ?>
                                        <tr class="hover:bg-gray-100">
                                            <td class="p-4 text-sm font-normal text-gray-500">
                                                <?php echo htmlspecialchars($student['MaSinhVien']); ?>
                                            </td>
                                            <td class="p-4 text-sm font-normal text-gray-500">
                                                <a href="../student/view.php?id=<?php echo $student['Id']; ?>" class="text-blue-600 hover:underline">
                                                    <?php echo htmlspecialchars($student['HoTen']); ?>
                                                </a>
                                            </td>
                                            <td class="p-4 text-sm font-normal text-gray-500">
                                                <?php echo htmlspecialchars($student['Email']); ?>
                                            </td>
                                            <td class="p-4 text-sm font-normal text-gray-500">
                                                <?php echo htmlspecialchars($student['TenChucVu'] ?? 'Chưa có'); ?>
                                            </td>
                                            <td class="p-4 space-x-2 whitespace-nowrap">
                                                <a href="../student/view.php?id=<?php echo $student['Id']; ?>" 
                                                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800">
                                                    <i class="fas fa-eye mr-2"></i>
                                                    Xem
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang -->
                        <?php if ($totalPages > 1): ?>
                            <div class="flex justify-center mt-4">
                                <nav class="flex items-center space-x-2">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?id=<?php echo $id; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="px-3 py-2 text-sm font-medium text-center <?php echo $page === $i ? 'text-white bg-blue-700' : 'text-gray-500 bg-white hover:bg-gray-100'; ?> rounded-lg">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">Chưa có sinh viên nào trong lớp này</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
