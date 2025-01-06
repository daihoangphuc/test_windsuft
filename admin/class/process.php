<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/classes/ClassRoom.php';

session_start();
$classroom = new ClassRoom($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tenLop = $_POST['tenLop'] ?? '';
    $khoaTruongId = $_POST['khoaTruongId'] ?? '';

    if ($action === 'create' && !empty($tenLop) && !empty($khoaTruongId)) {
        if ($classroom->create($tenLop, $khoaTruongId)) {
            header('Location: index.php');
            exit;
        }
        header('Location: index.php');
        exit;
    } elseif ($action === 'update' && !empty($tenLop) && !empty($khoaTruongId)) {
        $id = $_POST['id'] ?? '';
        if ($classroom->update($id, $tenLop, $khoaTruongId)) {
            header('Location: index.php');
            exit;
        }
        header('Location: index.php');
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'delete') {
        $id = $_GET['id'] ?? '';
        if ($classroom->delete($id)) {
            $_SESSION['success'] = "Xóa lớp thành công!";
            header('Location: index.php');
            exit;
        }
        $_SESSION['error'] = "Có lỗi xảy ra khi xóa lớp!";
        header('Location: index.php');
        exit;
    }
}

$_SESSION['error'] = "Có lỗi xảy ra!";
header('Location: index.php');
exit;
?>
