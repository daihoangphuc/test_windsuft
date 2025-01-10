<?php
session_start();
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function writeLog($db, $action, $result, $detail) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'];
        $user = $_SESSION['username'] ?? 'Unknown';
        
        $stmt = $db->prepare("INSERT INTO log (IP, NguoiDung, HanhDong, KetQua, ChiTiet) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            return;
        }
        $stmt->bind_param("sssss", $ip, $user, $action, $result, $detail);
        $stmt->execute();
    } catch (Exception $e) {
        // Ignore logging errors
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = "Phương thức không hợp lệ";
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    $error = "Lỗi khi tải file: " . ($_FILES['excel_file']['error'] ?? 'Không có file được tải lên');
    writeLog($db, 'Import Users', 'Error', $error);
    $_SESSION['flash_error'] = $error;
    header('Location: index.php');
    exit;
}

$allowedFileTypes = [
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/octet-stream'
];

$file = $_FILES['excel_file'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedFileTypes) && !preg_match('/\.xlsx?$/', strtolower($file['name']))) {
    $error = "File không đúng định dạng. Vui lòng sử dụng file Excel (.xls, .xlsx). Định dạng hiện tại: " . $fileType;
    writeLog($db, 'Import Users', 'Error', $error);
    $_SESSION['flash_error'] = $error;
    header('Location: index.php');
    exit;
}

try {
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Remove header row
    array_shift($rows);

    $db->begin_transaction();

    $errors = [];
    $successCount = 0;
    $processedRows = [];

    foreach ($rows as $index => $row) {
        $rowNum = $index + 2;
        
        // Validate required fields
        if (empty($row[0]) || empty($row[1]) || empty($row[4]) || empty($row[5])) {
            $error = "Dòng $rowNum: Thiếu thông tin bắt buộc (Mã SV, Họ tên, Tên đăng nhập, Email)";
            $errors[] = $error;
            continue;
        }

        // Extract and trim data from row
        $maSinhVien = trim($row[0]);
        $hoTen = trim($row[1]);
        $ngaySinh = !empty($row[2]) ? date('Y-m-d', strtotime(str_replace('/', '-', $row[2]))) : null;
        $gioiTinh = isset($row[3]) ? (int)$row[3] : 1;
        $tenDangNhap = trim($row[4]);
        $email = trim($row[5]);
        $lopHoc = trim($row[6]);

        $processedRows[] = "Dòng $rowNum: MaSV=$maSinhVien, HoTen=$hoTen, Email=$email, Lop=$lopHoc";

        // Validate if user already exists
        $stmt = $db->prepare("SELECT Id FROM nguoidung WHERE TenDangNhap = ? OR Email = ? OR MaSinhVien = ?");
        $stmt->bind_param("sss", $tenDangNhap, $email, $maSinhVien);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Dòng $rowNum: Người dùng đã tồn tại (trùng tên đăng nhập, email hoặc mã sinh viên)";
            $errors[] = $error;
            continue;
        }

        // Get LopHocId
        $stmt = $db->prepare("SELECT Id FROM lophoc WHERE TenLop = ?");
        $stmt->bind_param("s", $lopHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $error = "Dòng $rowNum: Lớp '$lopHoc' không tồn tại trong hệ thống";
            $errors[] = $error;
            continue;
        }
        $lopHocId = $result->fetch_assoc()['Id'];

        // Default values
        $matKhau = password_hash($maSinhVien, PASSWORD_DEFAULT);
        $anhDaiDien = '../uploads/users/tvupng.png';
        $chucVuId = 4;
        $vaiTroId = 2;
        $trangThai = 1;
        $ngayTao = date('Y-m-d H:i:s');

        // Insert user
        $stmt = $db->prepare("
            INSERT INTO nguoidung (
                MaSinhVien, TenDangNhap, MatKhauHash, HoTen, 
                Email, anhdaidien, GioiTinh, NgaySinh, 
                ChucVuId, LopHocId, NgayTao, TrangThai, VaiTroId
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            $error = "Database prepare failed: " . $db->error;
            $errors[] = $error;
            continue;
        }

        $stmt->bind_param("ssssssissisii", 
            $maSinhVien, 
            $tenDangNhap, 
            $matKhau, 
            $hoTen, 
            $email, 
            $anhDaiDien, 
            $gioiTinh, 
            $ngaySinh, 
            $chucVuId, 
            $lopHocId, 
            $ngayTao, 
            $trangThai, 
            $vaiTroId
        );

        if (!$stmt->execute()) {
            $error = "Dòng $rowNum: Lỗi khi thêm người dùng - " . $stmt->error;
            $errors[] = $error;
            continue;
        }

        $successCount++;
    }

    $logDetail = "Processed rows:\n" . implode("\n", $processedRows);
    if (!empty($errors)) {
        $logDetail .= "\n\nErrors:\n" . implode("\n", $errors);
    }

    if (empty($errors)) {
        $db->commit();
        writeLog($db, 'Import Users', 'Success', "Import thành công $successCount người dùng\n\n" . $logDetail);
        $_SESSION['flash_message'] = "Import thành công $successCount người dùng";
    } else {
        if ($successCount > 0) {
            $db->commit();
            writeLog($db, 'Import Users', 'Partial Success', "Import thành công $successCount người dùng, có lỗi với một số dòng\n\n" . $logDetail);
            $_SESSION['flash_message'] = "Import thành công $successCount người dùng. Có một số lỗi:<br>" . implode("<br>", $errors);
        } else {
            $db->rollback();
            writeLog($db, 'Import Users', 'Error', "Không import được người dùng nào\n\n" . $logDetail);
            $_SESSION['flash_error'] = "Có lỗi xảy ra trong quá trình import:<br>" . implode("<br>", $errors);
        }
    }

} catch (Exception $e) {
    if (isset($db) && $db->connect_errno === 0) {
        $db->rollback();
    }
    $error = "Lỗi xử lý file Excel: " . $e->getMessage();
    writeLog($db, 'Import Users', 'Error', $error);
    $_SESSION['flash_error'] = $error;
}

header('Location: index.php');
exit;
