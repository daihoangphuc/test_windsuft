<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

$auth = new Auth();
$auth->requireAdmin();

// Khởi tạo kết nối
$db = Database::getInstance();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['id']) && isset($_POST['type']) && isset($_POST['amount']) && isset($_POST['description']) && isset($_POST['date'])) {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $date = $_POST['date'];

        try {
            $query = "UPDATE `taichinh` SET `LoaiGiaoDich` = ?, `SoTien` = ?, `MoTa` = ?, `NgayGiaoDich` = ? WHERE `Id` = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iissi", $type, $amount, $description, $date, $id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Cập nhật giao dịch thành công!';
            } else {
                $response['message'] = 'Lỗi: Không thể cập nhật giao dịch!';
            }
        } catch (Exception $e) {
            $response['message'] = 'Lỗi: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Thiếu thông tin cần thiết!';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
