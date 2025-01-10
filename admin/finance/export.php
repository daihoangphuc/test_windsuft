<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$db = Database::getInstance()->getConnection();

// Xử lý tham số tìm kiếm
$search = isset($_GET['search']) ? $_GET['search'] : '';
$type = isset($_GET['type']) ? (int)$_GET['type'] : -1;
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';

// Xây dựng câu truy vấn
$whereConditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $whereConditions[] = "(tc.MoTa LIKE ? OR tc.GhiChu LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

if ($type !== -1) {
    $whereConditions[] = "tc.LoaiGiaoDich = ?";
    $params[] = $type;
    $types .= 'i';
}

if (!empty($startDate)) {
    $whereConditions[] = "tc.NgayGiaoDich >= ?";
    $params[] = $startDate . ' 00:00:00';
    $types .= 's';
}

if (!empty($endDate)) {
    $whereConditions[] = "tc.NgayGiaoDich <= ?";
    $params[] = $endDate . ' 23:59:59';
    $types .= 's';
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

$sql = "SELECT tc.*, nd.HoTen as NguoiTao 
        FROM taichinh tc 
        LEFT JOIN nguoidung nd ON tc.NguoiDungId = nd.Id 
        $whereClause 
        ORDER BY tc.NgayGiaoDich DESC";

if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->query($sql);
}

$transactions = $result->fetch_all(MYSQLI_ASSOC);

// Tạo file Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set tiêu đề
$sheet->setCellValue('A1', 'DANH SÁCH GIAO DỊCH TÀI CHÍNH');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Set header
$headers = ['STT', 'Ngày giao dịch', 'Loại', 'Nội dung', 'Số tiền', 'Người tạo', 'Ghi chú'];
foreach ($headers as $idx => $header) {
    $sheet->setCellValueByColumnAndRow($idx + 1, 2, $header);
}
$sheet->getStyle('A2:G2')->getFont()->setBold(true);

// Set data
$row = 3;
$stt = 1;
$totalIncome = 0;
$totalExpense = 0;

foreach ($transactions as $transaction) {
    $sheet->setCellValueByColumnAndRow(1, $row, $stt++);
    $sheet->setCellValueByColumnAndRow(2, $row, date('d/m/Y H:i', strtotime($transaction['NgayGiaoDich'])));
    $sheet->setCellValueByColumnAndRow(3, $row, $transaction['LoaiGiaoDich'] == 0 ? 'Thu' : 'Chi');
    $sheet->setCellValueByColumnAndRow(4, $row, $transaction['MoTa']);
    $sheet->setCellValueByColumnAndRow(5, $row, number_format($transaction['SoTien'], 0, ',', '.'));
    $sheet->setCellValueByColumnAndRow(6, $row, $transaction['NguoiTao']);
    $sheet->setCellValueByColumnAndRow(7, $row, $transaction['GhiChu'] ?? '');
    
    if ($transaction['LoaiGiaoDich'] == 0) {
        $totalIncome += $transaction['SoTien'];
    } else {
        $totalExpense += $transaction['SoTien'];
    }
    
    $row++;
}

// Set tổng thu chi
$row += 1;
$sheet->setCellValue('A' . $row, 'TỔNG KẾT');
$sheet->mergeCells('A' . $row . ':G' . $row);
$sheet->getStyle('A' . $row)->getFont()->setBold(true);

$row++;
$sheet->setCellValue('A' . $row, 'Tổng thu:');
$sheet->setCellValue('B' . $row, number_format($totalIncome, 0, ',', '.') . ' đ');
$sheet->getStyle('A' . $row)->getFont()->setBold(true);

$row++;
$sheet->setCellValue('A' . $row, 'Tổng chi:');
$sheet->setCellValue('B' . $row, number_format($totalExpense, 0, ',', '.') . ' đ');
$sheet->getStyle('A' . $row)->getFont()->setBold(true);

$row++;
$sheet->setCellValue('A' . $row, 'Số dư:');
$sheet->setCellValue('B' . $row, number_format($totalIncome - $totalExpense, 0, ',', '.') . ' đ');
$sheet->getStyle('A' . $row)->getFont()->setBold(true);

// Auto size columns
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
$time_now = date('d/m/Y H:i');
// Set header cho file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="danh_sach_giao_dich_'.$time_now.'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
