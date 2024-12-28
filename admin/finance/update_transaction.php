<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get and sanitize input
    $transactionId = (int)$_POST['transactionId'];
    $type = (int)$_POST['type'];
    $amount = (float)$_POST['amount'];
    $description = sanitize_input($_POST['description']);
    $transactionDate = $_POST['transactionDate'];
    
    // Validate input
    if (!in_array($type, [0, 1]) || $amount <= 0 || empty($description) || empty($transactionDate)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin hợp lệ');
    }

    // Start transaction
    $conn->begin_transaction();

    // Get old transaction data for logging
    $query = "SELECT * FROM taichinh WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldTransaction = $result->fetch_assoc();

    if (!$oldTransaction) {
        throw new Exception('Transaction not found');
    }

    // Update transaction
    $query = "UPDATE taichinh SET LoaiGiaoDich = ?, SoTien = ?, MoTa = ?, NgayGiaoDich = ? WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("idssi", $type, $amount, $description, $transactionDate, $transactionId);
    $stmt->execute();

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Cập nhật giao dịch tài chính',
        'Thành công',
        "Đã cập nhật giao dịch từ [" . ($oldTransaction['LoaiGiaoDich'] == 1 ? "thu" : "chi") . ": " . format_money($oldTransaction['SoTien']) . 
        "] thành [" . ($type == 1 ? "thu" : "chi") . ": " . format_money($amount) . "]"
    );

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Cập nhật giao dịch thành công']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
