<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/classes/ClassRoom.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$classroom = new ClassRoom($conn);
$items = $classroom->exportToExcel();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Tên Lớp');
$sheet->setCellValue('C1', 'Khoa/Trường');
$sheet->setCellValue('D1', 'Ngày tạo');

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
$sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

// Add data
$row = 2;
foreach ($items as $item) {
    $sheet->setCellValue('A' . $row, $item['Id']);
    $sheet->setCellValue('B' . $row, $item['TenLop']);
    $sheet->setCellValue('C' . $row, $item['TenKhoaTruong']);
    $sheet->setCellValue('D' . $row, $item['NgayTao']);
    $row++;
}

// Auto size columns
foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="danh_sach_lop_hoc.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
