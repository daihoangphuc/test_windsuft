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
    $documentId = (int)$_POST['documentId'];
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    
    // Validate input
    if (empty($documentId) || empty($title) || empty($description)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
    }

    // Start transaction
    $conn->begin_transaction();

    // Get current document data
    $query = "SELECT * FROM tailieu WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $documentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentDocument = $result->fetch_assoc();

    if (!$currentDocument) {
        throw new Exception('Document not found');
    }

    // Handle file upload if new file is provided
    $file = $currentDocument['FileDinhKem'];
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        // Validate file type
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

        // Delete old file if exists
        if ($file) {
            delete_file('../../uploads/documents/' . $file);
        }

        // Upload new file
        $file = upload_file($_FILES['file'], '../../uploads/documents/');
    }

    // Update document
    $query = "UPDATE tailieu SET TenTaiLieu = ?, MoTa = ?, FileDinhKem = ? WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $title, $description, $file, $documentId);
    $stmt->execute();

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Cập nhật tài liệu',
        'Thành công',
        "Đã cập nhật tài liệu: $title"
    );

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Cập nhật tài liệu thành công']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
