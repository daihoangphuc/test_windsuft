<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/News.php';

$auth = new Auth();
$auth->requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $news = new News();
    
    // Get input
    $data = [
        'TieuDe' => $_POST['TieuDe'] ?? '',
        'NoiDung' => $_POST['NoiDung'] ?? '',
        'NguoiTaoId' => $_SESSION['user_id'] // Lấy ID người dùng hiện tại
    ];
    
    // Validate input
    if (empty($data['TieuDe']) || empty($data['NoiDung'])) {
        throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
    }

    // Add news with file if exists
    if (isset($_FILES['FileDinhKem']) && $_FILES['FileDinhKem']['error'] == 0) {
        if ($news->add($data, $_FILES['FileDinhKem'])) {
            echo json_encode(['success' => true, 'message' => 'Thêm tin tức thành công']);
        } else {
            throw new Exception('Không thể thêm tin tức');
        }
    } else {
        if ($news->add($data)) {
            echo json_encode(['success' => true, 'message' => 'Thêm tin tức thành công']);
        } else {
            throw new Exception('Không thể thêm tin tức');
        }
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
