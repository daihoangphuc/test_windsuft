<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance()->getConnection();

$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? 'registered'; // 'registered' or 'attended'

// Get activity details
$stmt = $db->prepare("SELECT TenHoatDong FROM hoatdong WHERE Id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();

if (!$activity) {
    die('Hoạt động không tồn tại');
}

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator('HTSV')
    ->setLastModifiedBy('HTSV')
    ->setTitle('Danh sách thành viên - ' . $activity['TenHoatDong']);

// Set column headers
$sheet->setCellValue('A1', 'STT');
$sheet->setCellValue('B1', 'Họ và tên');
$sheet->setCellValue('C1', 'MSSV');
$sheet->setCellValue('D1', 'Lớp');
$sheet->setCellValue('E1', 'Thời gian');
$sheet->setCellValue('F1', 'Trạng thái');

// Style the header
$headerStyle = [
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => 'E2EFDA',
        ],
    ],
];
$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

// Get member list
if ($type === 'attended') {
    $query = "
        SELECT n.HoTen, n.MaSinhVien, l.TenLop, dt.DiemDanhLuc as ThoiGian,
               CASE WHEN dt.TrangThai = 1 THEN 'Đã tham gia' ELSE 'Vắng mặt' END as TrangThai
        FROM danhsachthamgia dt
        JOIN nguoidung n ON dt.NguoiDungId = n.Id
        LEFT JOIN lophoc l ON n.LopHocId = l.Id
        WHERE dt.HoatDongId = ?
        ORDER BY dt.DiemDanhLuc DESC";
} else {
    $query = "
        SELECT n.HoTen, n.MaSinhVien, l.TenLop, dk.ThoiGianDangKy as ThoiGian,
               CASE 
                   WHEN dt.Id IS NOT NULL THEN 
                       CASE 
                           WHEN dt.TrangThai = 1 THEN 'Đã tham gia'
                           ELSE 'Vắng mặt'
                       END
                   WHEN h.NgayKetThuc < NOW() THEN 'Vắng mặt'
                   ELSE 'Đã đăng ký'
               END as TrangThai
        FROM danhsachdangky dk
        JOIN nguoidung n ON dk.NguoiDungId = n.Id
        JOIN hoatdong h ON dk.HoatDongId = h.Id
        LEFT JOIN lophoc l ON n.LopHocId = l.Id
        LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id AND dt.NguoiDungId = dk.NguoiDungId
        WHERE dk.HoatDongId = ? AND dk.TrangThai = 1
        ORDER BY dk.ThoiGianDangKy DESC";
}

$stmt = $db->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$row = 2;
$stt = 1;
while ($member = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $stt);
    $sheet->setCellValue('B' . $row, $member['HoTen']);
    $sheet->setCellValue('C' . $row, $member['MaSinhVien']);
    $sheet->setCellValue('D' . $row, $member['TenLop']);
    $sheet->setCellValue('E' . $row, date('d/m/Y H:i', strtotime($member['ThoiGian'])));
    $sheet->setCellValue('F' . $row, $member['TrangThai']);
    
    // Style for data rows
    $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ]);
    
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $row++;
    $stt++;
}

// Auto size columns
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set title
$sheet->insertNewRowBefore(1, 2);
$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1', mb_strtoupper($activity['TenHoatDong'], 'UTF-8'));
$sheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 14,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
]);

// Set filename
$filename = 'DS_' . ($type === 'attended' ? 'tham_gia' : 'dang_ky') . '_' . date('Ymd_His') . '.xlsx';

// Redirect output to client browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
