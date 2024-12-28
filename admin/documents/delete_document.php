<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['documentId'])) {
        throw new Exception('Document ID is required');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    $documentId = (int)$data['documentId'];

    // Start transaction
    $conn->begin_transaction();

    // Get document info before deleting
    $query = "SELECT * FROM tailieu WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $documentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();

    if (!$document) {
        throw new Exception('Document not found');
    }

    // Delete the document
    $deleteQuery = "DELETE FROM tailieu WHERE Id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $documentId);
    $stmt->execute();

    // Delete file
    if ($document['FileDinhKem']) {
        delete_file('../../uploads/documents/' . $document['FileDinhKem']);
    }

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Xóa tài liệu',
        'Thành công',
        "Đã xóa tài liệu: " . $document['TenTaiLieu']
    );

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Xóa tài liệu thành công']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
