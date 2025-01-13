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
    
    if (isset($_POST['id'])) {
        $id = $_POST['id'];

        try {
            $query = "DELETE FROM `taichinh` WHERE `Id` = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Xóa giao dịch thành công!';
            } else {
                $response['message'] = 'Lỗi: Không thể xóa giao dịch!';
            }
        } catch (Exception $e) {
            $response['message'] = 'Lỗi: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Thiếu ID giao dịch!';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
