<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/classes/ClassRoom.php';

$classroom = new ClassRoom($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tenLop = $_POST['tenLop'] ?? '';
    $khoaTruongId = $_POST['khoaTruongId'] ?? '';

    if ($action === 'create' && !empty($tenLop) && !empty($khoaTruongId)) {
        if ($classroom->create($tenLop, $khoaTruongId)) {
            header('Location: index.php?success=create');
            exit;
        }
    } elseif ($action === 'update' && !empty($tenLop) && !empty($khoaTruongId)) {
        $id = $_POST['id'] ?? '';
        if ($classroom->update($id, $tenLop, $khoaTruongId)) {
            header('Location: index.php?success=update');
            exit;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'delete') {
        $id = $_GET['id'] ?? '';
        if ($classroom->delete($id)) {
            header('Location: index.php?success=delete');
            exit;
        }
    }
}

header('Location: index.php?error=general');
exit;
?>
