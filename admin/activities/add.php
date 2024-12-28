<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity = new Activity();
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
            'TrangThai' => 1,
            'NguoiTaoId' => $_SESSION['user_id']
        ];

        if ($activity->add($data)) {
            // Log hoạt động
            $ip = $_SERVER['REMOTE_ADDR'];
            $admin_username = $_SESSION['username'];
            $action = "Thêm hoạt động mới";
            $result = "Thành công";
            $details = "Thêm hoạt động: " . $data['TenHoatDong'];
            
            $stmt = $db->prepare("INSERT INTO log (IP, NguoiDung, HanhDong, KetQua, ChiTiet) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $ip, $admin_username, $action, $result, $details);
            $stmt->execute();

            $_SESSION['flash_message'] = "Thêm hoạt động thành công!";
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Lỗi: " . $e->getMessage();
    }
}

$pageTitle = "Thêm hoạt động mới";
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="p-4">
    <div class="mb-4">
        <h2 class="text-2xl font-bold">Thêm hoạt động mới</h2>
    </div>

    <form action="" method="POST" class="max-w-2xl">
        <div class="grid gap-4 mb-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="block mb-2 text-sm font-medium text-gray-900">Tên hoạt động</label>
                <input type="text" name="name" id="name" required
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="block mb-2 text-sm font-medium text-gray-900">Mô tả</label>
                <textarea name="description" id="description" rows="4"
                          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"></textarea>
            </div>
            <div>
                <label for="start_date" class="block mb-2 text-sm font-medium text-gray-900">Thời gian bắt đầu</label>
                <input type="datetime-local" name="start_date" id="start_date" required
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="end_date" class="block mb-2 text-sm font-medium text-gray-900">Thời gian kết thúc</label>
                <input type="datetime-local" name="end_date" id="end_date" required
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="location" class="block mb-2 text-sm font-medium text-gray-900">Địa điểm</label>
                <input type="text" name="location" id="location" required
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="coordinates" class="block mb-2 text-sm font-medium text-gray-900">Tọa độ</label>
                <input type="text" name="coordinates" id="coordinates" placeholder="Ví dụ: 10.762622, 106.660172"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="max_participants" class="block mb-2 text-sm font-medium text-gray-900">Số lượng tối đa</label>
                <input type="number" name="max_participants" id="max_participants" min="0" value="0"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                Thêm hoạt động
            </button>
            <a href="index.php" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5">
                Hủy
            </a>
        </div>
    </form>
</div>