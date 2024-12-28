<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireAdmin();

$activity = new Activity();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

$activity_data = $activity->get($id);
if (!$activity_data) {
    $_SESSION['flash_error'] = "Không tìm thấy hoạt động!";
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    
    try {
        $data = [
            'TenHoatDong' => $_POST['name'],
            'MoTa' => $_POST['description'],
            'NgayBatDau' => $_POST['start_date'],
            'NgayKetThuc' => $_POST['end_date'],
            'DiaDiem' => $_POST['location'],
            'ToaDo' => $_POST['coordinates'],
            'SoLuong' => (int)$_POST['max_participants'],
            'TrangThai' => (int)$_POST['status']
        ];

        if ($activity->update($id, $data)) {
            // Log hoạt động
            $ip = $_SERVER['REMOTE_ADDR'];
            $admin_username = $_SESSION['username'];
            $action = "Cập nhật hoạt động";
            $result = "Thành công";
            $details = "Cập nhật hoạt động ID: $id";
            
            $stmt = $db->prepare("INSERT INTO log (IP, NguoiDung, HanhDong, KetQua, ChiTiet) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $ip, $admin_username, $action, $result, $details);
            $stmt->execute();

            $_SESSION['flash_message'] = "Cập nhật hoạt động thành công!";
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Lỗi: " . $e->getMessage();
    }
}

$pageTitle = "Chỉnh sửa hoạt động";
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="p-4">
    <div class="mb-4">
        <h2 class="text-2xl font-bold">Chỉnh sửa hoạt động</h2>
    </div>

    <form action="" method="POST" class="max-w-2xl">
        <div class="grid gap-4 mb-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="block mb-2 text-sm font-medium text-gray-900">Tên hoạt động</label>
                <input type="text" name="name" id="name" required
                       value="<?php echo htmlspecialchars($activity_data['TenHoatDong']); ?>"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                <textarea name="description" id="description" rows="4"
                          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"><?php echo htmlspecialchars($activity_data['MoTa']); ?></textarea>
            </div>
            <div>
                <label for="start_date" class="block mb-2 text-sm font-medium text-gray-900">Thời gian bắt đầu</label>
                <input type="datetime-local" name="start_date" id="start_date" required
                       value="<?php echo date('Y-m-d\TH:i', strtotime($activity_data['NgayBatDau'])); ?>"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="end_date" class="block mb-2 text-sm font-medium text-gray-900">Thời gian kết thúc</label>
                <input type="datetime-local" name="end_date" id="end_date" required
                       value="<?php echo date('Y-m-d\TH:i', strtotime($activity_data['NgayKetThuc'])); ?>"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="location" class="block mb-2 text-sm font-medium text-gray-900">Địa điểm</label>
                <input type="text" name="location" id="location" required
                       value="<?php echo htmlspecialchars($activity_data['DiaDiem']); ?>"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="coordinates" class="block mb-2 text-sm font-medium text-gray-900">Tọa độ</label>
                <input type="text" name="coordinates" id="coordinates"
                       value="<?php echo htmlspecialchars($activity_data['ToaDo']); ?>"
                       placeholder="Ví dụ: 10.762622, 106.660172"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="max_participants" class="block mb-2 text-sm font-medium text-gray-900">Số lượng tối đa</label>
                <input type="number" name="max_participants" id="max_participants" min="0"
                       value="<?php echo (int)$activity_data['SoLuong']; ?>"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="status" class="block mb-2 text-sm font-medium text-gray-900">Trạng thái</label>
                <select name="status" id="status" required
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="1" <?php echo $activity_data['TrangThai'] == 1 ? 'selected' : ''; ?>>Hoạt động</option>
                    <option value="0" <?php echo $activity_data['TrangThai'] == 0 ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Cập nhật
            </button>
            <a href="index.php" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5">
                Hủy
            </a>
        </div>
    </form>
</div>
