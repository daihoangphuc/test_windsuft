<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Get all past activities that haven't been processed for absences
    $query = "SELECT h.Id, h.TenHoatDong, h.NgayKetThuc
              FROM hoatdong h
              WHERE h.NgayKetThuc < NOW()
              AND NOT EXISTS (
                  SELECT 1 FROM danhsachthamgia dt 
                  WHERE dt.HoatDongId = h.Id 
                  AND dt.TrangThai = 0
              )";
    
    $activities = $db->query($query)->fetch_all(MYSQLI_ASSOC);

    foreach ($activities as $activity) {
        // Mark absent for registered users who didn't attend
        $markAbsentQuery = "
            INSERT INTO danhsachthamgia (NguoiDungId, HoatDongId, TrangThai, NgayDiemDanh)
            SELECT 
                dk.NguoiDungId, 
                dk.HoatDongId, 
                0, -- Vắng mặt
                NOW()
            FROM danhsachdangky dk
            LEFT JOIN danhsachthamgia dt 
                ON dt.NguoiDungId = dk.NguoiDungId 
                AND dt.HoatDongId = dk.HoatDongId
            WHERE dk.HoatDongId = ?
            AND dk.TrangThai = 1
            AND dt.Id IS NULL";

        $stmt = $db->prepare($markAbsentQuery);
        $stmt->bind_param("i", $activity['Id']);
        $stmt->execute();

        // Update activity as processed
        $updateQuery = "UPDATE hoatdong SET DaXuLyVang = 1 WHERE Id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->bind_param("i", $activity['Id']);
        $stmt->execute();

        // Log the action
        if ($stmt->affected_rows > 0) {
            $logQuery = "INSERT INTO nhatky (IP, NguoiDungId, HanhDong, TrangThai, MoTa) 
                        VALUES (?, 0, ?, ?, ?)";
            $stmt = $db->prepare($logQuery);
            $action = "Đánh dấu vắng tự động";
            $status = "Thành công";
            $description = "Đã đánh dấu vắng " . $stmt->affected_rows . " người cho hoạt động: " . $activity['TenHoatDong'];
            $ip = "SYSTEM";
            $stmt->bind_param("ssss", $ip, $action, $status, $description);
            $stmt->execute();
        }
    }

    echo "Successfully processed " . count($activities) . " activities.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
