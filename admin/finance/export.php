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

    // Get filter parameters
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');
    $type = isset($_GET['type']) ? (int)$_GET['type'] : -1;

    // Create WHERE clause
    $whereClause = " WHERE NgayGiaoDich BETWEEN ? AND ?";
    if ($type != -1) {
        $whereClause .= " AND LoaiGiaoDich = " . $type;
    }

    // Get transactions with creator info
    $query = "SELECT 
                tc.Id,
                tc.LoaiGiaoDich,
                tc.SoTien,
                tc.MoTa,
                tc.NgayGiaoDich,
                nd.HoTen as NguoiTao
            FROM taichinh tc 
            LEFT JOIN nguoidung nd ON tc.NguoiDungId = nd.Id" . 
            $whereClause . 
            " ORDER BY tc.NgayGiaoDich DESC, tc.Id DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Prepare data for Excel export
    $data = [];
    $data[] = ['ID', 'Loại giao dịch', 'Số tiền', 'Mô tả', 'Ngày giao dịch', 'Người tạo'];
    
    $totalIncome = 0;
    $totalExpense = 0;

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            $row['Id'],
            $row['LoaiGiaoDich'] == 1 ? 'Thu' : 'Chi',
            $row['SoTien'],
            $row['MoTa'],
            format_datetime($row['NgayGiaoDich']),
            $row['NguoiTao']
        ];

        if ($row['LoaiGiaoDich'] == 1) {
            $totalIncome += $row['SoTien'];
        } else {
            $totalExpense += $row['SoTien'];
        }
    }

    // Add summary rows
    $data[] = ['', '', '', '', '', ''];
    $data[] = ['Tổng thu:', format_money($totalIncome), '', '', '', ''];
    $data[] = ['Tổng chi:', format_money($totalExpense), '', '', '', ''];
    $data[] = ['Số dư:', format_money($totalIncome - $totalExpense), '', '', '', ''];

    // Export to Excel
    $filename = 'bao_cao_tai_chinh_' . date('Y-m-d_H-i-s') . '.xlsx';
    export_excel($data, $filename);

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Xuất báo cáo tài chính',
        'Thành công',
        "Đã xuất file Excel: $filename"
    );

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
