<?php
require_once __DIR__ . '/Logger.php';

function logActivity($action, $result, $details = '') {
    $logger = new Logger();
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Khách';
    return $logger->log($userId, $action, $result, $details);
}
?>
