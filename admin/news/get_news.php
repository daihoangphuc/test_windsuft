<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/News.php';

$auth = new Auth();
$auth->requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID tin tức là bắt buộc');
    }

    $news = new News();
    $newsData = $news->get($_GET['id']);

    if (!$newsData) {
        throw new Exception('Không tìm thấy tin tức');
    }

    echo json_encode(['success' => true, 'news' => $newsData]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
