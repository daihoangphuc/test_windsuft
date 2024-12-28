<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$auth = new Auth();
$auth->requireAdmin();

try {
    $db = Database::getInstance()->getConnection();

    // Get filter parameters
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');

    // Create new Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $data = [];
    
    // Add report header
    $data[] = ['BÁO CÁO THỐNG KÊ HOẠT ĐỘNG'];
    $data[] = ['Từ ngày: ' . date('d/m/Y', strtotime($startDate))];
    $data[] = ['Đến ngày: ' . date('d/m/Y', strtotime($endDate))];
    $data[] = [''];

    // 1. Overall Statistics
    $query = "
        SELECT 
            COUNT(*) as TongHoatDong,
            SUM(CASE WHEN NgayKetThuc < NOW() THEN 1 ELSE 0 END) as HoatDongDaKetThuc,
            SUM(CASE WHEN NgayBatDau > NOW() THEN 1 ELSE 0 END) as HoatDongSapDienRa,
            SUM(CASE WHEN NgayBatDau <= NOW() AND NgayKetThuc >= NOW() THEN 1 ELSE 0 END) as HoatDongDangDienRa
        FROM hoatdong
        WHERE NgayBatDau BETWEEN ? AND ?";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $overall_stats = $stmt->get_result()->fetch_assoc();

    $data[] = ['THỐNG KÊ TỔNG QUAN'];
    $data[] = ['Tổng số hoạt động', number_format($overall_stats['TongHoatDong'])];
    $data[] = ['Hoạt động đã kết thúc', number_format($overall_stats['HoatDongDaKetThuc'])];
    $data[] = ['Hoạt động đang diễn ra', number_format($overall_stats['HoatDongDangDienRa'])];
    $data[] = ['Hoạt động sắp diễn ra', number_format($overall_stats['HoatDongSapDienRa'])];
    $data[] = [''];

    // 2. Monthly Statistics
    $query = "
        SELECT 
            DATE_FORMAT(h.NgayBatDau, '%Y-%m') as Thang,
            COUNT(DISTINCT h.Id) as SoHoatDong,
            COUNT(DISTINCT dk.Id) as TongDangKy,
            COUNT(DISTINCT dt.Id) as TongThamGia
        FROM hoatdong h
        LEFT JOIN danhsachdangky dk ON dk.HoatDongId = h.Id AND dk.TrangThai = 1
        LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id
        WHERE h.NgayBatDau BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(h.NgayBatDau, '%Y-%m')
        ORDER BY Thang";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $monthly_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $data[] = ['THỐNG KÊ THEO THÁNG'];
    $data[] = ['Tháng', 'Số hoạt động', 'Tổng đăng ký', 'Tổng tham gia'];
    foreach ($monthly_stats as $stat) {
        $data[] = [
            date('m/Y', strtotime($stat['Thang'] . '-01')),
            number_format($stat['SoHoatDong']),
            number_format($stat['TongDangKy']),
            number_format($stat['TongThamGia'])
        ];
    }
    $data[] = [''];

    // 3. Detailed Activity Statistics
    $query = "
        SELECT 
            h.TenHoatDong,
            h.NgayBatDau,
            h.NgayKetThuc,
            n.HoTen as NguoiTao,
            COUNT(DISTINCT dk.Id) as TongDangKy,
            COUNT(DISTINCT dt.Id) as TongThamGia,
            COUNT(DISTINCT CASE WHEN dt.TrangThai = 0 THEN dt.Id END) as TongVang
        FROM hoatdong h
        LEFT JOIN nguoidung n ON h.NguoiTaoId = n.Id
        LEFT JOIN danhsachdangky dk ON dk.HoatDongId = h.Id AND dk.TrangThai = 1
        LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id
        WHERE h.NgayBatDau BETWEEN ? AND ?
        GROUP BY h.Id
        ORDER BY h.NgayBatDau DESC";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $data[] = ['CHI TIẾT HOẠT ĐỘNG'];
    $data[] = ['Tên hoạt động', 'Thời gian bắt đầu', 'Thời gian kết thúc', 'Người tạo', 'Tổng đăng ký', 'Tổng tham gia', 'Tổng vắng', 'Tỷ lệ tham gia'];
    foreach ($activities as $activity) {
        $rate = $activity['TongDangKy'] > 0 
            ? round(($activity['TongThamGia'] / $activity['TongDangKy']) * 100, 1) 
            : 0;
        $data[] = [
            $activity['TenHoatDong'],
            date('d/m/Y H:i', strtotime($activity['NgayBatDau'])),
            date('d/m/Y H:i', strtotime($activity['NgayKetThuc'])),
            $activity['NguoiTao'],
            number_format($activity['TongDangKy']),
            number_format($activity['TongThamGia']),
            number_format($activity['TongVang']),
            $rate . '%'
        ];
    }

    // Write data to Excel
    $row = 1;
    foreach ($data as $rowData) {
        $col = 'A';
        foreach ($rowData as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }

    // Style the Excel file
    $sheet->getStyle('A1:H1')->getFont()->setBold(true);
    $sheet->getStyle('A5:B9')->getFont()->setBold(true);
    $sheet->getStyle('A11:D11')->getFont()->setBold(true);
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="thong_ke_hoat_dong_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    $_SESSION['flash_error'] = "Lỗi: " . $e->getMessage();
    header('Location: statistics.php');
    exit;
}
