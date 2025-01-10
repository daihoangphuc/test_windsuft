<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';
require_once '../../config/auth.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
$auth = new Auth();
$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get form data
    $documentId = isset($_POST['documentId']) ? (int)$_POST['documentId'] : 0;
    $title = isset($_POST['title']) ? sanitize_input($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
    $documentType = isset($_POST['documentType']) ? sanitize_input($_POST['documentType']) : '';

    // Validate required fields
    if (!$documentId || !$title || !$description || !$documentType) {
        throw new Exception('Vui lòng điền đầy đủ thông tin');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Start transaction
    $conn->begin_transaction();

    // Get current document info
    $query = "SELECT * FROM tailieu WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $documentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();

    if (!$document) {
        throw new Exception('Không tìm thấy tài liệu');
    }

    // Handle file upload if a new file is provided
    $filePath = $document['DuongDan']; // Keep existing file path by default
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $file_type = $_FILES['file']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Loại file không được hỗ trợ. Chỉ chấp nhận PDF, DOC, DOCX, XLS, XLSX');
        }
        
        if ($_FILES['file']['size'] > 10 * 1024 * 1024) { // 10MB limit
            throw new Exception('File không được vượt quá 10MB');
        }
        
        $upload_dir = '../../uploads/documents/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_path)) {
            // Delete old file if it exists and is different from the new file
            if (!empty($document['DuongDan']) && file_exists($document['DuongDan']) && $document['DuongDan'] !== $upload_path) {
                unlink($document['DuongDan']);
            }
            $filePath = $upload_path;
        } else {
            throw new Exception('Không thể tải file lên');
        }
    }

    // Update document
    $updateQuery = "UPDATE tailieu SET 
                   TenTaiLieu = ?, 
                   MoTa = ?, 
                   LoaiTaiLieu = ?, 
                   DuongDan = ?
                   WHERE Id = ?";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssssi", $title, $description, $documentType, $filePath, $documentId);
    
    if (!$stmt->execute()) {
        throw new Exception('Lỗi khi cập nhật tài liệu: ' . $stmt->error);
    }

    // Log the activity
    $ip = $_SERVER['REMOTE_ADDR'];
    $user = $_SESSION['user_id'];
    log_activity($ip, $user, 'Cập nhật tài liệu', 'Thành công', "Đã cập nhật tài liệu ID: $documentId");

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Tài liệu đã được cập nhật thành công']);

} catch (Exception $e) {
    // Rollback transaction if there's an error
    if (isset($conn)) {
        $conn->rollback();
    }

    $error_message = $e->getMessage();
    error_log("Error in edit_document.php: " . $error_message);
    echo json_encode(['success' => false, 'message' => $error_message]);
}
?>
