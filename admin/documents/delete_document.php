<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../utils/functions.php';

header('Content-Type: application/json');

// Kiểm tra quyền admin
$auth = new Auth();
if (!$auth->isAdmin()) {
    echo json_encode([
        'success' => false, 
        'message' => 'Bạn không có quyền thực hiện thao tác này'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Phương thức không hợp lệ'
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['documentId'])) {
        throw new Exception('ID tài liệu không được để trống');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    $documentId = (int)$data['documentId'];

    // Start transaction
    $conn->begin_transaction();

    // Get document info before deleting
    $query = "SELECT t.*, nd.HoTen as NguoiTao 
              FROM tailieu t 
              LEFT JOIN nguoidung nd ON t.NguoiTaoId = nd.Id 
              WHERE t.Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $documentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();

    if (!$document) {
        throw new Exception('Không tìm thấy tài liệu');
    }

    // Kiểm tra và xóa file vật lý
    $filePath = '../../uploads/documents/' . $document['DuongDan'];
    $fileExists = file_exists($filePath);
    $fileDeleted = false;
    
    if ($fileExists) {
        $fileDeleted = unlink($filePath);
        if (!$fileDeleted) {
            throw new Exception('Không thể xóa file tài liệu');
        }
    }

    // Delete document permissions
    $deletePermissionsQuery = "DELETE FROM phanquyentailieu WHERE TaiLieuId = ?";
    $stmt = $conn->prepare($deletePermissionsQuery);
    $stmt->bind_param("i", $documentId);
    $stmt->execute();

    // Delete the document
    $deleteQuery = "DELETE FROM tailieu WHERE Id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $documentId);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Log hoạt động
    $logDetails = json_encode([
        'document_id' => $documentId,
        'document_name' => $document['TenTaiLieu'],
        'created_by' => $document['NguoiTao'],
        'file_exists' => $fileExists,
        'file_deleted' => $fileDeleted
    ]);
    
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Xóa tài liệu',
        'Thành công',
        $logDetails
    );

    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa tài liệu thành công',
        'data' => [
            'documentName' => $document['TenTaiLieu'],
            'fileDeleted' => $fileDeleted
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction if error occurs
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }

    // Log lỗi
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'] ?? null,
        'Xóa tài liệu',
        'Thất bại',
        $e->getMessage()
    );

    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
