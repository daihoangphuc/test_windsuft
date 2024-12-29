<?php
// Include database configuration
require_once __DIR__ . '/database.php';

// Base URL configuration
define('BASE_URL', 'http://localhost:81/test_windsuft');

// Other configurations
define('SITE_NAME', 'Quản lý CLB');
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('DEFAULT_AVATAR', BASE_URL . '/images/Users/default-avatar.png');

// Time zone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration before starting
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Initialize database connection
$database = Database::getInstance();
$conn = $database->getConnection();

// Function to get base URL
function base_url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

// Function to redirect
function redirect($path = '') {
    header('Location: ' . base_url($path));
    exit;
}
