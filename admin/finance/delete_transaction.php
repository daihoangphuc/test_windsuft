<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../utils/functions.php';

$auth = new Auth();
$auth->requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['transactionId'])) {
        throw new Exception('ID giao dịch là bắt buộc');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    $transactionId = (int)$data['transactionId'];

    // Lấy thông tin giao dịch trước khi xóa
    $query = "SELECT * FROM taichinh WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();

    if (!$transaction) {
        throw new Exception('Không tìm thấy giao dịch');
    }

    // Xóa giao dịch
    $deleteQuery = "DELETE FROM taichinh WHERE Id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $transactionId);
    
    if ($stmt->execute()) {
        // Ghi log
        log_activity(
            $_SERVER['REMOTE_ADDR'],
            $_SESSION['user_id'],
            'Xóa giao dịch tài chính',
            'Thành công',
            "Đã xóa giao dịch " . ($transaction['LoaiGiaoDich'] == 1 ? "thu" : "chi") . ": " . number_format($transaction['SoTien']) . " VNĐ"
        );

        echo json_encode(['success' => true, 'message' => 'Xóa giao dịch thành công']);
    } else {
        throw new Exception('Không thể xóa giao dịch');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
