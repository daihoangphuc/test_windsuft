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
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    
    // Validate input
    if (empty($title) || empty($description)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
    }

    // Validate file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
        throw new Exception('Vui lòng chọn file tài liệu');
    }

    // Check file type
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                     'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $fileType = $_FILES['file']['type'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Loại file không được hỗ trợ. Chỉ chấp nhận PDF, DOC, DOCX, XLS, XLSX');
    }

    // Check file size (10MB limit)
    if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
        throw new Exception('File không được vượt quá 10MB');
    }

    // Upload file
    $file = upload_file($_FILES['file'], '../../uploads/documents/');

    // Start transaction
    $conn->begin_transaction();

    // Insert document
    $query = "INSERT INTO tailieu (TenTaiLieu, MoTa, FileDinhKem, NguoiTaoId) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $nguoiTaoId = $_SESSION['user_id'];
    $stmt->bind_param("sssi", $title, $description, $file, $nguoiTaoId);
    $stmt->execute();

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Thêm tài liệu',
        'Thành công',
        "Đã thêm tài liệu: $title"
    );

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Thêm tài liệu thành công']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
