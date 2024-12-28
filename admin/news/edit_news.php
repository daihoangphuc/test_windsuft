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
    if (!isset($_GET['id'])) {
        throw new Exception('ID tin tức là bắt buộc');
    }

    $news = new News();
    
    // Get input
    $data = [
        'TieuDe' => $_POST['TieuDe'] ?? '',
        'NoiDung' => $_POST['NoiDung'] ?? ''
    ];
    
    // Validate input
    if (empty($data['TieuDe']) || empty($data['NoiDung'])) {
        throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
    }

    // Update news with file if exists
    if (isset($_FILES['FileDinhKem']) && $_FILES['FileDinhKem']['error'] == 0) {
        if ($news->update($_GET['id'], $data, $_FILES['FileDinhKem'])) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật tin tức thành công']);
        } else {
            throw new Exception('Không thể cập nhật tin tức');
        }
    } else {
        if ($news->update($_GET['id'], $data)) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật tin tức thành công']);
        } else {
            throw new Exception('Không thể cập nhật tin tức');
        }
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
