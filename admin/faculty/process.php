<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/classes/Faculty.php';

$faculty = new Faculty($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tenKhoaTruong = $_POST['tenKhoaTruong'] ?? '';

    if ($action === 'create' && !empty($tenKhoaTruong)) {
        if ($faculty->create($tenKhoaTruong)) {
            header('Location: index.php?success=create');
            exit;
        }
    } elseif ($action === 'update' && !empty($tenKhoaTruong)) {
        $id = $_POST['id'] ?? '';
        if ($faculty->update($id, $tenKhoaTruong)) {
            header('Location: index.php?success=update');
            exit;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'delete') {
        $id = $_GET['id'] ?? '';
        if ($faculty->delete($id)) {
            header('Location: index.php?success=delete');
            exit;
        } else {
            header('Location: index.php?error=delete');
            exit;
        }
    }
}

header('Location: index.php?error=general');
exit;
?>
