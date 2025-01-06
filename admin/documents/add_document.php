<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../utils/functions.php';

header('Content-Type: application/json');

// Kiểm tra quyền admin
$auth = new Auth();
try {
    $auth->requireAdmin();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

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
    $documentType = sanitize_input($_POST['documentType']);
    
    // Debug
    error_log("Title: " . $title);
    error_log("Description: " . $description);
    error_log("Document Type: " . $documentType);
    error_log("Files: " . print_r($_FILES, true));

    // Validate input
    if (empty($title) || empty($description) || empty($documentType)) {
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
    $uploadDir = '../../uploads/documents/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($_FILES['file']['name']);
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        throw new Exception('Không thể tải file lên');
    }

    // Start transaction
    $conn->begin_transaction();

    // Insert document
    $query = "INSERT INTO tailieu (TenTaiLieu, MoTa, DuongDan, LoaiTaiLieu, NguoiTaoId) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $nguoiTaoId = $_SESSION['user_id'];
    $fileUrl = '/manage-htsv/uploads/documents/' . $fileName;
    $stmt->bind_param("ssssi", $title, $description, $fileUrl, $documentType, $nguoiTaoId);
    $stmt->execute();
    
    // Get the ID of the newly inserted document
    $documentId = $conn->insert_id;
    
    // Insert document permissions for both admin and member roles
    $permissionQuery = "INSERT INTO phanquyentailieu (TaiLieuId, VaiTroId, Quyen) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($permissionQuery);
    
    // Admin role (VaiTroId = 1) gets full access (Quyen = 1)
    $vaiTroAdmin = 1;
    $quyenAdmin = 1;
    $stmt->bind_param("iii", $documentId, $vaiTroAdmin, $quyenAdmin);
    $stmt->execute();
    
    // Member role (VaiTroId = 2) gets read access (Quyen = 1)
    $vaiTroMember = 2;
    $quyenMember = 1;
    $stmt->bind_param("iii", $documentId, $vaiTroMember, $quyenMember);
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
