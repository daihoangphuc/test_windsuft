<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new Auth();
$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $tenChucVu = trim($_POST['tenChucVu']);
    
    if (!empty($tenChucVu) && $id > 0) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("UPDATE chucvu SET TenChucVu = ? WHERE Id = ?");
        $stmt->bind_param("si", $tenChucVu, $id);
        
        if ($stmt->execute()) {
            header('Location: index.php?success=edit');
        } else {
            header('Location: index.php?error=edit');
        }
    } else {
        header('Location: index.php?error=empty');
    }
} else {
    header('Location: index.php');
}
exit();
