<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new Auth();
$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenChucVu = trim($_POST['tenChucVu']);
    
    if (!empty($tenChucVu)) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO chucvu (TenChucVu) VALUES (?)");
        $stmt->bind_param("s", $tenChucVu);
        
        if ($stmt->execute()) {
            header('Location: index.php?success=add');
        } else {
            header('Location: index.php?error=add');
        }
    } else {
        header('Location: index.php?error=empty');
    }
} else {
    header('Location: index.php');
}
exit();
