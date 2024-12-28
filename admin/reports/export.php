<?php
require_once '../../config/database.php';
require_once '../../utils/functions.php';
require_once '../../vendor/autoload.php'; // Thêm autoload cho PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set default font
    $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);

    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(20);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('C')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(40);
    $sheet->getColumnDimension('E')->setWidth(20);

    // Add title
    $sheet->setCellValue('A1', 'BÁO CÁO TỔNG HỢP');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Add date range
    $sheet->setCellValue('A2', 'Từ ngày: ' . format_date($startDate));
    $sheet->setCellValue('A3', 'Đến ngày: ' . format_date($endDate));
    $sheet->mergeCells('A2:E2');
    $sheet->mergeCells('A3:E3');

    $currentRow = 5;

    // 1. Member Statistics
    $memberStats = $conn->query("
        SELECT 
            COUNT(*) as total_members,
            COUNT(CASE WHEN DATEDIFF(NOW(), NgayTao) <= 30 THEN 1 END) as new_members
        FROM nguoidung
        WHERE NgayTao <= '$endDate'
    ")->fetch_assoc();

    $sheet->setCellValue('A' . $currentRow, 'THỐNG KÊ THÀNH VIÊN');
    $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
    $currentRow++;

    $sheet->setCellValue('A' . $currentRow, 'Tổng số thành viên');
    $sheet->setCellValue('B' . $currentRow, number_format($memberStats['total_members']));
    $currentRow++;

    $sheet->setCellValue('A' . $currentRow, 'Thành viên mới (30 ngày)');
    $sheet->setCellValue('B' . $currentRow, number_format($memberStats['new_members']));
    $currentRow += 2;

    // 2. Activity Statistics
    $activityStats = $conn->query("
        SELECT 
            COUNT(*) as total_activities,
            COUNT(CASE WHEN NOW() < NgayBatDau THEN 1 END) as upcoming_activities,
            COUNT(CASE WHEN NOW() BETWEEN NgayBatDau AND NgayKetThuc THEN 1 END) as ongoing_activities,
            COUNT(CASE WHEN NOW() > NgayKetThuc THEN 1 END) as completed_activities
        FROM hoatdong
        WHERE NgayTao BETWEEN '$startDate' AND '$endDate'
    ")->fetch_assoc();

    $sheet->setCellValue('A' . $currentRow, 'THỐNG KÊ HOẠT ĐỘNG');
    $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
    $currentRow++;

    $sheet->setCellValue('A' . $currentRow, 'Tổng số hoạt động');
    $sheet->setCellValue('B' . $currentRow, number_format($activityStats['total_activities']));
    $currentRow++;

    $sheet->setCellValue('A' . $currentRow, 'Sắp diễn ra');
    $sheet->setCellValue('B' . $currentRow, number_format($activityStats['upcoming_activities']));
    $currentRow++;

    $sheet->setCellValue('A' . $currentRow, 'Đang diễn ra');
    $sheet->setCellValue('B' . $currentRow, number_format($activityStats['ongoing_activities']));
    $currentRow++;

    $sheet->setCellValue('A' . $currentRow, 'Đã kết thúc');
    $sheet->setCellValue('B' . $currentRow, number_format($activityStats['completed_activities']));
    $currentRow += 2;

    // 3. Detailed Activity List
    $sheet->setCellValue('A' . $currentRow, 'CHI TIẾT HOẠT ĐỘNG');
    $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
    $currentRow++;

    // Headers
    $headers = ['Tên hoạt động', 'Thời gian', 'Địa điểm', 'Số lượng đăng ký', 'Trạng thái'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $currentRow, $header);
        $sheet->getStyle($col . $currentRow)->getFont()->setBold(true);
        $sheet->getStyle($col . $currentRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');
        $col++;
    }
    $currentRow++;

    // Activity data
    $activities = $conn->query("
        SELECT 
            h.TenHoatDong,
            h.NgayBatDau,
            h.NgayKetThuc,
            h.DiaDiem,
            COUNT(DISTINCT dk.Id) as total_registrations,
            CASE 
                WHEN NOW() < h.NgayBatDau THEN 'Sắp diễn ra'
                WHEN NOW() BETWEEN h.NgayBatDau AND h.NgayKetThuc THEN 'Đang diễn ra'
                ELSE 'Đã kết thúc'
            END as status
        FROM hoatdong h
        LEFT JOIN danhsachdangky dk ON h.Id = dk.HoatDongId AND dk.TrangThai = 1
        WHERE h.NgayTao BETWEEN '$startDate' AND '$endDate'
        GROUP BY h.Id
        ORDER BY h.NgayBatDau DESC
    ");

    while ($activity = $activities->fetch_assoc()) {
        $sheet->setCellValue('A' . $currentRow, $activity['TenHoatDong']);
        $sheet->setCellValue('B' . $currentRow, date('d/m/Y H:i', strtotime($activity['NgayBatDau'])) . ' - ' . 
                                               date('d/m/Y H:i', strtotime($activity['NgayKetThuc'])));
        $sheet->setCellValue('C' . $currentRow, $activity['DiaDiem']);
        $sheet->setCellValue('D' . $currentRow, $activity['total_registrations']);
        $sheet->setCellValue('E' . $currentRow, $activity['status']);
        $currentRow++;
    }

    // Style the entire table
    $tableRange = 'A' . ($currentRow - 1) . ':E' . ($currentRow - 1);
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // Set the content type and headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="bao-cao-hoat-dong.xlsx"');
    header('Cache-Control: max-age=0');

    // Save the spreadsheet to PHP output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
