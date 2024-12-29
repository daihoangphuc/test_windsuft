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
    $filePath = $document['DuongDan'];
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Delete old file if it exists
        if ($document['FileDinhKem']) {
            delete_file('../../uploads/documents/' . $document['FileDinhKem']);
        }

        // Upload new file
        $uploadedFile = upload_file($_FILES['file'], '../../uploads/documents/');
        $filePath = $uploadedFile;
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
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Không có thay đổi nào được cập nhật');
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
