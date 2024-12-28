<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';
require_once '../../utils/auth.php';

// Ensure user is logged in and is admin
session_start();
ensure_admin_logged_in();

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                
                if (empty($name)) {
                    $error = 'Tên chức vụ không được để trống.';
                } else {
                    $stmt = $db->prepare("INSERT INTO chucvu (TenChucVu, MoTa) VALUES (?, ?)");
                    $stmt->bind_param("ss", $name, $description);
                    
                    if ($stmt->execute()) {
                        $success = 'Thêm chức vụ thành công.';
                        log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['user_id'], 'Thêm chức vụ', 'Thành công', "Đã thêm chức vụ: $name");
                    } else {
                        $error = 'Có lỗi xảy ra khi thêm chức vụ.';
                    }
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                
                if (empty($name)) {
                    $error = 'Tên chức vụ không được để trống.';
                } else {
                    $stmt = $db->prepare("UPDATE chucvu SET TenChucVu = ?, MoTa = ? WHERE Id = ?");
                    $stmt->bind_param("ssi", $name, $description, $id);
                    
                    if ($stmt->execute()) {
                        $success = 'Cập nhật chức vụ thành công.';
                        log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['user_id'], 'Cập nhật chức vụ', 'Thành công', "Đã cập nhật chức vụ ID: $id");
                    } else {
                        $error = 'Có lỗi xảy ra khi cập nhật chức vụ.';
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Check if position is being used
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM nguoidung WHERE ChucVuId = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result['count'] > 0) {
                    $error = 'Không thể xóa chức vụ đang được sử dụng.';
                } else {
                    $stmt = $db->prepare("DELETE FROM chucvu WHERE Id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $success = 'Xóa chức vụ thành công.';
                        log_activity($_SERVER['REMOTE_ADDR'], $_SESSION['user_id'], 'Xóa chức vụ', 'Thành công', "Đã xóa chức vụ ID: $id");
                    } else {
                        $error = 'Có lỗi xảy ra khi xóa chức vụ.';
                    }
                }
                break;
        }
    }
}

// Get all positions
$positions = $db->query("SELECT * FROM chucvu ORDER BY Id")->fetch_all(MYSQLI_ASSOC);

$pageTitle = "Quản lý chức vụ";
ob_start();
?>

<div class="p-4">
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Quản lý chức vụ</h2>
    </div>

    <?php if ($error): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Add Position Button -->
    <div class="mb-4">
        <button data-modal-target="addPositionModal" data-modal-toggle="addPositionModal" 
                class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5">
            Thêm chức vụ
        </button>
    </div>

    <!-- Positions Table -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Tên chức vụ</th>
                    <th scope="col" class="px-6 py-3">Mô tả</th>
                    <th scope="col" class="px-6 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($positions as $position): ?>
                    <tr class="bg-white border-b">
                        <td class="px-6 py-4"><?php echo htmlspecialchars($position['Id']); ?></td>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <?php echo htmlspecialchars($position['TenChucVu']); ?>
                        </td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($position['MoTa'] ?? ''); ?></td>
                        <td class="px-6 py-4">
                            <button data-modal-target="editPositionModal<?php echo $position['Id']; ?>" 
                                    data-modal-toggle="editPositionModal<?php echo $position['Id']; ?>"
                                    class="font-medium text-blue-600 hover:underline mr-3">
                                Sửa
                            </button>
                            <button data-modal-target="deletePositionModal<?php echo $position['Id']; ?>" 
                                    data-modal-toggle="deletePositionModal<?php echo $position['Id']; ?>"
                                    class="font-medium text-red-600 hover:underline">
                                Xóa
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($positions)): ?>
                    <tr class="bg-white border-b">
                        <td colspan="4" class="px-6 py-4 text-center">Chưa có chức vụ nào</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Position Modal -->
    <div id="addPositionModal" tabindex="-1" aria-hidden="true" 
         class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <div class="flex items-start justify-between p-4 border-b rounded-t">
                    <h3 class="text-xl font-semibold text-gray-900">
                        Thêm chức vụ mới
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="addPositionModal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="action" value="add">
                    <div>
                        <label for="name" class="block mb-2 text-sm font-medium text-gray-900">
                            Tên chức vụ
                        </label>
                        <input type="text" name="name" id="name" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label for="description" class="block mb-2 text-sm font-medium text-gray-900">
                            Mô tả
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"></textarea>
                    </div>
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Thêm chức vụ
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Position Modals -->
    <?php foreach ($positions as $position): ?>
        <div id="editPositionModal<?php echo $position['Id']; ?>" tabindex="-1" aria-hidden="true" 
             class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative w-full max-w-md max-h-full">
                <div class="relative bg-white rounded-lg shadow">
                    <div class="flex items-start justify-between p-4 border-b rounded-t">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Sửa chức vụ
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" 
                                data-modal-hide="editPositionModal<?php echo $position['Id']; ?>">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                        </button>
                    </div>
                    <form method="POST" class="p-6 space-y-6">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $position['Id']; ?>">
                        <div>
                            <label for="name<?php echo $position['Id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">
                                Tên chức vụ
                            </label>
                            <input type="text" name="name" id="name<?php echo $position['Id']; ?>" 
                                   value="<?php echo htmlspecialchars($position['TenChucVu']); ?>" required
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label for="description<?php echo $position['Id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">
                                Mô tả
                            </label>
                            <textarea name="description" id="description<?php echo $position['Id']; ?>" rows="3"
                                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"><?php echo htmlspecialchars($position['MoTa'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Cập nhật
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Position Modal -->
        <div id="deletePositionModal<?php echo $position['Id']; ?>" tabindex="-1" aria-hidden="true" 
             class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative w-full max-w-md max-h-full">
                <div class="relative bg-white rounded-lg shadow">
                    <div class="flex items-start justify-between p-4 border-b rounded-t">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Xác nhận xóa
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" 
                                data-modal-hide="deletePositionModal<?php echo $position['Id']; ?>">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 text-center">
                        <svg class="mx-auto mb-4 text-gray-400 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <h3 class="mb-5 text-lg font-normal text-gray-500">
                            Bạn có chắc chắn muốn xóa chức vụ này không?
                        </h3>
                        <form method="POST" class="inline-flex">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $position['Id']; ?>">
                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center mr-2">
                                Xóa
                            </button>
                            <button type="button" data-modal-hide="deletePositionModal<?php echo $position['Id']; ?>"
                                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                                Hủy
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
$pageContent = ob_get_clean();
require_once '../../layouts/admin_header.php';
?>
