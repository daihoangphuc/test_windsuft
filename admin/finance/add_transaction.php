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

    // Insert transaction
    $query = "INSERT INTO taichinh (LoaiGiaoDich, SoTien, MoTa, NgayGiaoDich, NguoiDungId) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $nguoiDungId = $_SESSION['user_id'];
    $stmt->bind_param("idssi", $type, $amount, $description, $transactionDate, $nguoiDungId);
    $stmt->execute();

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Thêm giao dịch tài chính',
        'Thành công',
        "Đã thêm giao dịch " . ($type == 1 ? "thu" : "chi") . ": " . format_money($amount)
    );

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Thêm giao dịch thành công']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
