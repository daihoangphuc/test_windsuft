<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';

$auth = new Auth();
$auth->logout();

$_SESSION['flash_message'] = 'Đăng xuất thành công!';
$_SESSION['flash_type'] = 'success';
header('Location: ' . base_url('/login.php'));
exit();
?>
