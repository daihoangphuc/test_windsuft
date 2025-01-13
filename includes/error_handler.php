<?php
function handleError($errno, $errstr, $errfile, $errline) {
    // Log lỗi
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    
    // Chuyển hướng đến trang 500 cho lỗi nghiêm trọng
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header("HTTP/1.1 500 Internal Server Error");
        include __DIR__ . '/../errors/500.php';
        exit();
    }
    
    // Trả về false để cho phép PHP tiếp tục xử lý lỗi
    return false;
}

function handleException($exception) {
    // Log exception
    error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    
    // Chuyển hướng đến trang 500
    header("HTTP/1.1 500 Internal Server Error");
    include __DIR__ . '/../errors/500.php';
    exit();
}

function handle404() {
    header("HTTP/1.0 404 Not Found");
    include __DIR__ . '/../errors/404.php';
    exit();
}

// Đăng ký các handler
set_error_handler("handleError");
set_exception_handler("handleException");

// Kiểm tra và xử lý 404
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$relative_path = substr($request_path, strlen($script_name));

if (!empty($relative_path) && !file_exists($_SERVER['DOCUMENT_ROOT'] . $request_path)) {
    handle404();
}
