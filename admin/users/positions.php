<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/classes/Position.php';

$position = new Position();
$positions = $position->getAll();

$pageTitle = 'Quản lý chức vụ';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="p-4">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Quản lý chức vụ</h2>
        <button data-modal-target="addPositionModal" data-modal-toggle="addPositionModal" class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
            Thêm chức vụ
        </button>
    </div>

    <!-- Bảng danh sách chức vụ -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Tên chức vụ</th>
                    <th scope="col" class="px-6 py-3">Ngày tạo</th>
                    <th scope="col" class="px-6 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($positions as $pos): ?>
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($pos['TenChucVu']); ?></td>
                    <td class="px-6 py-4"><?php echo date('d/m/Y H:i', strtotime($pos['NgayTao'])); ?></td>
                    <td class="px-6 py-4">
                        <button data-modal-target="editPositionModal<?php echo $pos['Id']; ?>" 
                                data-modal-toggle="editPositionModal<?php echo $pos['Id']; ?>"
                                class="font-medium text-blue-600 hover:underline mr-3">Sửa</button>
                        <button onclick="deletePosition(<?php echo $pos['Id']; ?>)" 
                                class="font-medium text-red-600 hover:underline">Xóa</button>
                    </td>
                </tr>

                <!-- Modal sửa chức vụ -->
                <div id="editPositionModal<?php echo $pos['Id']; ?>" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
                    <div class="relative w-full max-w-md max-h-full">
                        <div class="relative bg-white rounded-lg shadow">
                            <div class="flex items-start justify-between p-4 border-b rounded-t">
                                <h3 class="text-xl font-semibold">
                                    Sửa chức vụ
                                </h3>
                                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="editPositionModal<?php echo $pos['Id']; ?>">
                                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                    </svg>
                                </button>
                            </div>
                            <form action="position-edit.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $pos['Id']; ?>">
                                <div class="p-6 space-y-6">
                                    <div>
                                        <label for="position_name<?php echo $pos['Id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Tên chức vụ</label>
                                        <input type="text" name="position_name" id="position_name<?php echo $pos['Id']; ?>" 
                                               value="<?php echo htmlspecialchars($pos['TenChucVu']); ?>" 
                                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                                    </div>
                                </div>
                                <div class="flex items-center p-6 space-x-2 border-t">
                                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Lưu</button>
                                    <button type="button" data-modal-hide="editPositionModal<?php echo $pos['Id']; ?>" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900">Hủy</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal thêm chức vụ -->
<div id="addPositionModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-start justify-between p-4 border-b rounded-t">
                <h3 class="text-xl font-semibold">
                    Thêm chức vụ mới
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="addPositionModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <form action="position-add.php" method="POST">
                <div class="p-6 space-y-6">
                    <div>
                        <label for="new_position_name" class="block mb-2 text-sm font-medium text-gray-900">Tên chức vụ</label>
                        <input type="text" name="position_name" id="new_position_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    </div>
                </div>
                <div class="flex items-center p-6 space-x-2 border-t">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Thêm</button>
                    <button type="button" data-modal-hide="addPositionModal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deletePosition(id) {
    if (confirm('Bạn có chắc chắn muốn xóa chức vụ này?')) {
        fetch('position-delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
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
            console.error('Error:', error);
            alert('Có lỗi xảy ra');
        });
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
