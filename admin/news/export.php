<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /manage-htsv/login.php');
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get all news with creator info
    $query = "SELECT 
                tt.Id,
                tt.TieuDe,
                tt.NoiDung,
                tt.FileDinhKem,
                tt.NgayTao,
                nd.HoTen as NguoiTao
            FROM tintuc tt 
            LEFT JOIN nguoidung nd ON tt.NguoiTaoId = nd.Id
            ORDER BY tt.NgayTao DESC";

    $result = $conn->query($query);
    
    // Prepare data for Excel export
    $data = [];
    $data[] = ['ID', 'Tiêu đề', 'Nội dung', 'File đính kèm', 'Ngày tạo', 'Người tạo'];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            $row['Id'],
            $row['TieuDe'],
            $row['NoiDung'],
            $row['FileDinhKem'] ? 'Có' : 'Không',
            format_datetime($row['NgayTao']),
            $row['NguoiTao']
        ];
    }

    // Export to Excel
    $filename = 'danh_sach_tin_tuc_' . date('Y-m-d_H-i-s') . '.xlsx';
    export_excel($data, $filename);

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Xuất danh sách tin tức',
        'Thành công',
        "Đã xuất file Excel: $filename"
    );

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
