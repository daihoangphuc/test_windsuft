<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

$response = array(
    'status' => 'error',
    'message' => 'Unknown error occurred'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Validate input
                if (!isset($_POST['type']) || !isset($_POST['amount']) || !isset($_POST['description']) || !isset($_POST['date'])) {
                    $response['message'] = 'Missing required fields';
                    break;
                }

                $type = (int)$_POST['type'];
                $amount = (int)$_POST['amount'];
                $description = trim($_POST['description']);
                $date = $_POST['date'];
                $userId = $_SESSION['user_id'];

                if ($amount <= 0) {
                    $response['message'] = 'Số tiền phải lớn hơn 0';
                    break;
                }

                try {
                    $stmt = $conn->prepare("INSERT INTO taichinh (LoaiGiaoDich, SoTien, MoTa, NgayGiaoDich, NguoiDungId) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissi", $type, $amount, $description, $date, $userId);
                    
                    if ($stmt->execute()) {
                        $response['status'] = 'success';
                        $response['message'] = 'Thêm giao dịch thành công';
                    } else {
                        $response['message'] = 'Không thể thêm giao dịch';
                    }
                } catch (Exception $e) {
                    $response['message'] = 'Lỗi: ' . $e->getMessage();
                }
                break;

            case 'edit':
                // Validate input
                if (!isset($_POST['id']) || !isset($_POST['type']) || !isset($_POST['amount']) || !isset($_POST['description'])) {
                    $response['message'] = 'Missing required fields';
                    break;
                }

                $id = (int)$_POST['id'];
                $type = (int)$_POST['type'];
                $amount = (int)$_POST['amount'];
                $description = trim($_POST['description']);

                if ($amount <= 0) {
                    $response['message'] = 'Số tiền phải lớn hơn 0';
                    break;
                }

                try {
                    $stmt = $conn->prepare("UPDATE taichinh SET LoaiGiaoDich = ?, SoTien = ?, MoTa = ? WHERE Id = ?");
                    $stmt->bind_param("iisi", $type, $amount, $description, $id);
                    
                    if ($stmt->execute()) {
                        $response['status'] = 'success';
                        $response['message'] = 'Cập nhật giao dịch thành công';
                    } else {
                        $response['message'] = 'Không thể cập nhật giao dịch';
                    }
                } catch (Exception $e) {
                    $response['message'] = 'Lỗi: ' . $e->getMessage();
                }
                break;

            case 'delete':
                if (!isset($_POST['id'])) {
                    $response['message'] = 'Missing transaction ID';
                    break;
                }

                $id = (int)$_POST['id'];

                try {
                    $stmt = $conn->prepare("DELETE FROM taichinh WHERE Id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $response['status'] = 'success';
                        $response['message'] = 'Xóa giao dịch thành công';
                    } else {
                        $response['message'] = 'Không thể xóa giao dịch';
                    }
                } catch (Exception $e) {
                    $response['message'] = 'Lỗi: ' . $e->getMessage();
                }
                break;

            default:
                $response['message'] = 'Invalid action';
                break;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
