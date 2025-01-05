<?php
// Cấu hình CORS để cho phép gọi API từ các domain khác
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Kết nối database
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance();
$con = $db->getConnection();

// Hàm response JSON
function sendResponse($status, $message, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message,
        'data' => $data
    ];
    echo json_encode($response);
    exit();
}

// Helper function cho phân trang
function getPaginationInfo() {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

// Helper function cho search conditions
function buildSearchCondition($searchFields, $searchTerm) {
    if(empty($searchTerm)) return '';
    
    $conditions = [];
    foreach($searchFields as $field) {
        $conditions[] = "$field LIKE '%$searchTerm%'";
    }
    return "(" . implode(" OR ", $conditions) . ")";
}

// Helper function cho response phân trang
function sendPaginationResponse($status, $message, $items, $total, $page, $limit) {
    sendResponse($status, $message, [
        'items' => $items,
        'total' => (int)$total,
        'page' => (int)$page,
        'limit' => (int)$limit,
        'totalPages' => ceil($total / $limit)
    ]);
}
