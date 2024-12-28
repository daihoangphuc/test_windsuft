<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /test_windsuft/login.php');
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get all tasks with assignees
    $query = "SELECT 
                nv.Id,
                nv.TenNhiemVu,
                nv.MoTa,
                nv.NgayBatDau,
                nv.NgayKetThuc,
                nv.TrangThai,
                GROUP_CONCAT(nd.HoTen SEPARATOR ', ') as NguoiThucHien
            FROM nhiemvu nv 
            LEFT JOIN phancongnhiemvu pcnv ON nv.Id = pcnv.NhiemVuId
            LEFT JOIN nguoidung nd ON pcnv.NguoiDungId = nd.Id
            GROUP BY nv.Id
            ORDER BY nv.NgayTao DESC";

    $result = $conn->query($query);
    
    // Prepare data for Excel export
    $data = [];
    $data[] = ['ID', 'Tên nhiệm vụ', 'Mô tả', 'Ngày bắt đầu', 'Ngày kết thúc', 'Trạng thái', 'Người thực hiện'];
    
    while ($row = $result->fetch_assoc()) {
        $status = '';
        switch($row['TrangThai']) {
            case 0:
                $status = 'Chưa bắt đầu';
                break;
            case 1:
                $status = 'Đang thực hiện';
                break;
            case 2:
                $status = 'Đã hoàn thành';
                break;
        }
        
        $data[] = [
            $row['Id'],
            $row['TenNhiemVu'],
            $row['MoTa'],
            $row['NgayBatDau'],
            $row['NgayKetThuc'],
            $status,
            $row['NguoiThucHien']
        ];
    }

    // Export to Excel
    $filename = 'danh_sach_nhiem_vu_' . date('Y-m-d_H-i-s') . '.xlsx';
    export_excel($data, $filename);

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Xuất danh sách nhiệm vụ',
        'Thành công',
        "Đã xuất file Excel: $filename"
    );

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
