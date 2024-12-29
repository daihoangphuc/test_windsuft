<?php
// Database configuration
define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quanlyclb');

// Base URL configuration
define('BASE_URL', 'http://localhost:81/test_windsuft');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Include required files
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/functions.php';
