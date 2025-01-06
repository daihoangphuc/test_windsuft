<?php
/**
 * Các hàm tiện ích cho toàn bộ ứng dụng
 */


/**
 * Lấy trạng thái hoạt động dưới dạng text
 */
function getStatusText($status) {
    switch ($status) {
        case 0:
            return 'Sắp diễn ra';
        case 1:
            return 'Đang diễn ra';
        case 2:
            return 'Đã kết thúc';
        default:
            return 'Không xác định';
    }
}

/**
 * Lấy class CSS cho trạng thái
 */
function getStatusClass($status) {
    switch ($status) {
        case 0:
            return 'bg-blue-100 text-blue-800';
        case 1:
            return 'bg-green-100 text-green-800';
        case 2:
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

/**
 * Format datetime từ MySQL sang định dạng hiển thị
 */
function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Tính số slot còn lại của hoạt động
 */
function getRemainingSlots($total, $registered) {
    return max(0, $total - $registered);
}

/**
 * Tính phần trăm đăng ký
 */
function getRegistrationPercentage($total, $registered) {
    if ($total <= 0) return 0;
    return min(100, ($registered / $total) * 100);
}

/**
 * Kiểm tra xem hoạt động có còn nhận đăng ký không
 */
function canRegister($activity) {
    $now = new DateTime();
    $start = new DateTime($activity['NgayBatDau']);
    $remaining = getRemainingSlots($activity['SoLuong'], $activity['TongDangKy']);
    
    return $activity['TrangThai'] == 0 && $remaining > 0 && $now <= $start;
}

/**
 * Tính khoảng cách giữa hai điểm dựa trên tọa độ
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Earth's radius in meters

    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);

    $latDelta = $lat2 - $lat1;
    $lonDelta = $lon2 - $lon1;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($lat1) * cos($lat2) * pow(sin($lonDelta / 2), 2)));
    
    return $angle * $earthRadius;
}
