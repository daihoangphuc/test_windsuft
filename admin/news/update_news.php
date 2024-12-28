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
    $newsId = (int)$_POST['newsId'];
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content']);
    
    // Validate input
    if (empty($newsId) || empty($title) || empty($content)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
    }

    // Start transaction
    $conn->begin_transaction();

    // Get current news data
    $query = "SELECT FileDinhKem FROM tintuc WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $newsId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentNews = $result->fetch_assoc();

    // Handle file upload
    $attachment = $currentNews['FileDinhKem'];
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        // Delete old file if exists
        if ($attachment) {
            delete_file('../../uploads/news/' . $attachment);
        }
        $attachment = upload_file($_FILES['attachment'], '../../uploads/news/');
    }

    // Update news
    $query = "UPDATE tintuc SET TieuDe = ?, NoiDung = ?, FileDinhKem = ? WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $title, $content, $attachment, $newsId);
    $stmt->execute();

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Cập nhật tin tức',
        'Thành công',
        "Đã cập nhật tin tức: $title"
    );

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Cập nhật tin tức thành công']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
