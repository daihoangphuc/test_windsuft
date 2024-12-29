<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/classes/Student.php';
require_once __DIR__ . '/../../includes/classes/ClassRoom.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

$student = new Student($conn);
$classroom = new ClassRoom($conn);

// Lấy thông tin lớp học
$classInfo = $classroom->getById($classId);
if (!$classInfo) {
    header('Location: index.php');
    exit;
}

// Lấy danh sách sinh viên để xuất Excel
$items = $student->exportClassStudents($classId);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'Mã SV');
$sheet->setCellValue('B1', 'Họ tên');
$sheet->setCellValue('C1', 'Email');
$sheet->setCellValue('D1', 'Ngày sinh');
$sheet->setCellValue('E1', 'Giới tính');
$sheet->setCellValue('F1', 'Chức vụ');
$sheet->setCellValue('G1', 'Lớp');
$sheet->setCellValue('H1', 'Khoa/Trường');

// Style headers
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => '4a90e2'],
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'e3f2fd'],
    ],
];
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

// Add data
$row = 2;
foreach ($items as $item) {
    $sheet->setCellValue('A' . $row, $item['MaSinhVien']);
    $sheet->setCellValue('B' . $row, $item['HoTen']);
    $sheet->setCellValue('C' . $row, $item['Email']);
    $sheet->setCellValue('D' . $row, $item['NgaySinh']);
    $sheet->setCellValue('E' . $row, $item['GioiTinh']);
    $sheet->setCellValue('F' . $row, $item['TenChucVu'] ?? 'Chưa có');
    $sheet->setCellValue('G' . $row, $item['TenLop']);
    $sheet->setCellValue('H' . $row, $item['TenKhoaTruong']);
    $row++;
}

// Auto size columns
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="danh_sach_sinh_vien_' . $classInfo['TenLop'] . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
