<?php
require_once __DIR__ . '/../../layouts/admin_header.php';
require_once __DIR__ . '/../../includes/classes/Faculty.php';
require_once __DIR__ . '/../../includes/classes/ClassRoom.php';

// Lấy ID của khoa từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$faculty = new Faculty($conn);
$classroom = new ClassRoom($conn);

// Lấy thông tin chi tiết của khoa
$facultyInfo = $faculty->getById($id);

// Nếu không tìm thấy khoa, chuyển hướng về trang danh sách
if (!$facultyInfo) {
    header('Location: index.php');
    exit;
}

// Lấy danh sách lớp học thuộc khoa này
$classes = $classroom->getAll(1, 100, '', $id);
?>

<div class="p-4 bg-white block sm:flex items-center justify-between border-b border-gray-200 lg:mt-1.5">
    <div class="mb-1 w-full">
        <div class="mb-4">
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl">Chi tiết Khoa/Trường</h1>
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
                            <p class="font-medium"><?php echo htmlspecialchars($facultyInfo['Id']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Tên Khoa/Trường:</p>
                            <p class="font-medium"><?php echo htmlspecialchars($facultyInfo['TenKhoaTruong']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Ngày tạo:</p>
                            <p class="font-medium"><?php echo date('d/m/Y', strtotime($facultyInfo['NgayTao'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-2">Danh sách lớp học (<?php echo count($classes); ?> lớp)</h3>
                    <?php if (!empty($classes)): ?>
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase">ID</th>
                                        <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase">Tên Lớp</th>
                                        <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase">Ngày tạo</th>
                                        <th scope="col" class="p-4 text-xs font-medium text-left text-gray-500 uppercase">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($classes as $class): ?>
                                        <tr class="hover:bg-gray-100">
                                            <td class="p-4 text-sm font-normal text-gray-500">
                                                <?php echo htmlspecialchars($class['Id']); ?>
                                            </td>
                                            <td class="p-4 text-sm font-normal text-gray-500">
                                                <a href="../class/view.php?id=<?php echo $class['Id']; ?>" class="text-blue-600 hover:underline">
                                                    <?php echo htmlspecialchars($class['TenLop']); ?>
                                                </a>
                                            </td>
                                            <td class="p-4 text-sm font-normal text-gray-500">
                                                <?php echo date('d/m/Y', strtotime($class['NgayTao'])); ?>
                                            </td>
                                            <td class="p-4 space-x-2 whitespace-nowrap">
                                                <a href="../class/view.php?id=<?php echo $class['Id']; ?>" 
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
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">Chưa có lớp học nào thuộc khoa này</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- <div class="fixed bottom-4 right-4">
    <a href="index.php" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-gray-700 hover:bg-gray-800">
        <i class="fas fa-arrow-left mr-2"></i>
        Quay lại
    </a>
</div> -->

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
