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

    // Get filter parameters
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');

    // Prepare data for Excel export
    $data = [];
    
    // Add report header
    $data[] = ['BÁO CÁO TỔNG HỢP'];
    $data[] = ['Từ ngày: ' . format_date($startDate)];
    $data[] = ['Đến ngày: ' . format_date($endDate)];
    $data[] = [''];

    // 1. Member Statistics
    $memberStats = $conn->query("
        SELECT 
            COUNT(*) as total_members,
            COUNT(CASE WHEN DATEDIFF(NOW(), NgayTao) <= 30 THEN 1 END) as new_members
        FROM nguoidung
        WHERE NgayTao <= '$endDate'
    ")->fetch_assoc();

    $data[] = ['THỐNG KÊ THÀNH VIÊN'];
    $data[] = ['Tổng số thành viên', number_format($memberStats['total_members'])];
    $data[] = ['Thành viên mới (30 ngày)', number_format($memberStats['new_members'])];
    $data[] = [''];

    // 2. Task Statistics
    $taskStats = $conn->query("
        SELECT 
            COUNT(*) as total_tasks,
            COUNT(CASE WHEN TrangThai = 0 THEN 1 END) as pending_tasks,
            COUNT(CASE WHEN TrangThai = 1 THEN 1 END) as in_progress_tasks,
            COUNT(CASE WHEN TrangThai = 2 THEN 1 END) as completed_tasks
        FROM nhiemvu
        WHERE NgayTao BETWEEN '$startDate' AND '$endDate'
    ")->fetch_assoc();

    $data[] = ['THỐNG KÊ NHIỆM VỤ'];
    $data[] = ['Tổng số nhiệm vụ', number_format($taskStats['total_tasks'])];
    $data[] = ['Chờ xử lý', number_format($taskStats['pending_tasks'])];
    $data[] = ['Đang thực hiện', number_format($taskStats['in_progress_tasks'])];
    $data[] = ['Hoàn thành', number_format($taskStats['completed_tasks'])];
    $data[] = [''];

    // 3. Financial Statistics
    $financeStats = $conn->query("
        SELECT 
            SUM(CASE WHEN LoaiGiaoDich = 1 THEN SoTien ELSE 0 END) as total_income,
            SUM(CASE WHEN LoaiGiaoDich = 0 THEN SoTien ELSE 0 END) as total_expense
        FROM taichinh
        WHERE NgayGiaoDich BETWEEN '$startDate' AND '$endDate'
    ")->fetch_assoc();

    $data[] = ['THỐNG KÊ TÀI CHÍNH'];
    $data[] = ['Tổng thu', format_money($financeStats['total_income'])];
    $data[] = ['Tổng chi', format_money($financeStats['total_expense'])];
    $data[] = ['Số dư', format_money($financeStats['total_income'] - $financeStats['total_expense'])];
    $data[] = [''];

    // 4. Document Statistics
    $documentStats = $conn->query("
        SELECT COUNT(*) as total_documents
        FROM tailieu
        WHERE NgayTao BETWEEN '$startDate' AND '$endDate'
    ")->fetch_assoc();

    $data[] = ['THỐNG KÊ TÀI LIỆU'];
    $data[] = ['Tổng số tài liệu', number_format($documentStats['total_documents'])];
    $data[] = [''];

    // 5. News Statistics
    $newsStats = $conn->query("
        SELECT COUNT(*) as total_news
        FROM tintuc
        WHERE NgayTao BETWEEN '$startDate' AND '$endDate'
    ")->fetch_assoc();

    $data[] = ['THỐNG KÊ TIN TỨC'];
    $data[] = ['Tổng số tin tức', number_format($newsStats['total_news'])];
    $data[] = [''];

    // 6. Detailed Financial Transactions
    $data[] = ['CHI TIẾT GIAO DỊCH TÀI CHÍNH'];
    $data[] = ['Ngày', 'Loại', 'Số tiền', 'Mô tả', 'Người tạo'];

    $financeQuery = "
        SELECT 
            tc.NgayGiaoDich,
            tc.LoaiGiaoDich,
            tc.SoTien,
            tc.MoTa,
            nd.HoTen as NguoiTao
        FROM taichinh tc
        LEFT JOIN nguoidung nd ON tc.NguoiDungId = nd.Id
        WHERE tc.NgayGiaoDich BETWEEN ? AND ?
        ORDER BY tc.NgayGiaoDich DESC
    ";
    
    $stmt = $conn->prepare($financeQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            format_date($row['NgayGiaoDich']),
            $row['LoaiGiaoDich'] == 1 ? 'Thu' : 'Chi',
            format_money($row['SoTien']),
            $row['MoTa'],
            $row['NguoiTao']
        ];
    }

    // Export to Excel
    $filename = 'bao_cao_tong_hop_' . date('Y-m-d_H-i-s') . '.xlsx';
    export_excel($data, $filename);

    // Log activity
    log_activity(
        $_SERVER['REMOTE_ADDR'],
        $_SESSION['user_id'],
        'Xuất báo cáo tổng hợp',
        'Thành công',
        "Đã xuất file Excel: $filename"
    );

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
