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
    if ($news->delete($_GET['id'])) {
        echo json_encode(['success' => true, 'message' => 'Xóa tin tức thành công']);
    } else {
        throw new Exception('Không thể xóa tin tức');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
