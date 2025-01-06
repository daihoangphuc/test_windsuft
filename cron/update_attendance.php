<?php
require_once '../config/database.php';

function updateAttendanceStatus() {
    $db = Database::getInstance()->getConnection();
    
    // Tìm các hoạt động đã kết thúc 1 ngày và chưa được cập nhật trong danhsachthamgia
    $query = "
        INSERT INTO danhsachthamgia (NguoiDungId, HoatDongId, TrangThai)
        SELECT 
            dk.NguoiDungId,
            dk.HoatDongId,
            0 as TrangThai -- Mặc định là vắng mặt
        FROM danhsachdangky dk
        JOIN hoatdong h ON dk.HoatDongId = h.Id
        WHERE 
            dk.TrangThai = 1 -- Chỉ lấy những người đã đăng ký và chưa hủy
            AND h.NgayKetThuc < DATE_SUB(NOW(), INTERVAL 1 DAY) -- Đã kết thúc hơn 1 ngày
            AND NOT EXISTS (
                SELECT 1 
                FROM danhsachthamgia dt 
                WHERE dt.NguoiDungId = dk.NguoiDungId 
                AND dt.HoatDongId = dk.HoatDongId
            ) -- Chưa có trong bảng danhsachthamgia
    ";
    
    // Thực hiện cập nhật
    if ($db->query($query)) {
        $affected_rows = $db->affected_rows;
        echo date('Y-m-d H:i:s') . " - Đã cập nhật $affected_rows bản ghi.\n";
        
        // Cập nhật trạng thái hoạt động thành đã kết thúc
        $update_activity_status = "
            UPDATE hoatdong 
            SET TrangThai = 2 
            WHERE NgayKetThuc < NOW() 
            AND TrangThai != 2
        ";
        $db->query($update_activity_status);
    } else {
        echo date('Y-m-d H:i:s') . " - Lỗi khi cập nhật: " . $db->error . "\n";
    }
}

// Thực thi cập nhật
updateAttendanceStatus();
