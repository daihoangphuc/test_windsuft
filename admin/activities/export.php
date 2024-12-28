<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$auth = new Auth();
$auth->requireAdmin();

$activity = new Activity();
$activities = $activity->getAll('', 1000, 0); // Lấy tối đa 1000 hoạt động

// Tạo spreadsheet mới
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thiết lập style cho header
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4A90E2'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];

// Thiết lập headers
$headers = [
    'A1' => 'Tên hoạt động',
    'B1' => 'Mô tả',
    'C1' => 'Ngày bắt đầu',
    'D1' => 'Ngày kết thúc',
    'E1' => 'Địa điểm',
    'F1' => 'Tọa độ',
    'G1' => 'Số lượng giới hạn',
    'H1' => 'Số lượng đăng ký',
    'I1' => 'Số lượng tham gia',
    'J1' => 'Trạng thái',
    'K1' => 'Người tạo',
    'L1' => 'Ngày tạo'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}
$sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

// Thêm dữ liệu
$row = 2;
foreach ($activities as $activity) {
    $status = '';
    if ($activity['TrangThai'] == 0) {
        $status = 'Đã hủy';
    } else {
        if (strtotime($activity['NgayKetThuc']) < time()) {
            $status = 'Đã kết thúc';
        } elseif (strtotime($activity['NgayBatDau']) > time()) {
            $status = 'Sắp diễn ra';
        } else {
            $status = 'Đang diễn ra';
        }
    }

    $sheet->setCellValue('A' . $row, $activity['TenHoatDong']);
    $sheet->setCellValue('B' . $row, $activity['MoTa']);
    $sheet->setCellValue('C' . $row, date('d/m/Y H:i', strtotime($activity['NgayBatDau'])));
    $sheet->setCellValue('D' . $row, date('d/m/Y H:i', strtotime($activity['NgayKetThuc'])));
    $sheet->setCellValue('E' . $row, $activity['DiaDiem']);
    $sheet->setCellValue('F' . $row, $activity['ToaDo']);
    $sheet->setCellValue('G' . $row, $activity['SoLuong']);
    $sheet->setCellValue('H' . $row, $activity['SoLuongDangKy']);
    $sheet->setCellValue('I' . $row, $activity['SoLuongThamGia']);
    $sheet->setCellValue('J' . $row, $status);
    $sheet->setCellValue('K' . $row, $activity['NguoiTao']);
    $sheet->setCellValue('L' . $row, date('d/m/Y H:i', strtotime($activity['NgayTao'])));

    $row++;
}

// Auto-size columns
foreach (range('A', 'L') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Thiết lập style cho data
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];
$sheet->getStyle('A2:L' . ($row - 1))->applyFromArray($dataStyle);

// Tạo file Excel
$writer = new Xlsx($spreadsheet);
$filename = 'danh_sach_hoat_dong_' . date('Y-m-d_H-i-s') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
