<?php
require_once 'config.php';
require_once '../config/auth.php';

$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($method) {
    case 'GET':
        switch($action) {
            case 'list':
                $query = "SELECT h.*, u.HoTen as NguoiTao 
                         FROM hoatdong h
                         LEFT JOIN nguoidung u ON h.NguoiTaoId = u.Id
                         ORDER BY h.NgayTao DESC";
                $result = $con->query($query);
                $activities = [];
                while($row = $result->fetch_assoc()) {
                    $activities[] = $row;
                }
                sendResponse(true, 'Danh sách hoạt động', $activities);
                break;

            case 'detail':
                $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                if(!$id) {
                    sendResponse(false, 'ID hoạt động không hợp lệ');
                }

                $query = "SELECT h.*, u.HoTen as NguoiTao 
                         FROM hoatdong h
                         LEFT JOIN nguoidung u ON h.NguoiTaoId = u.Id
                         WHERE h.Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $activity = $stmt->get_result()->fetch_assoc();

                if($activity) {
                    // Lấy danh sách đăng ký
                    $query = "SELECT dk.*, u.HoTen, u.MaSinhVien
                             FROM danhsachdangky dk
                             LEFT JOIN nguoidung u ON dk.NguoiDungId = u.Id
                             WHERE dk.HoatDongId = ?";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $registrations = [];
                    $result = $stmt->get_result();
                    while($row = $result->fetch_assoc()) {
                        $registrations[] = $row;
                    }
                    $activity['registrations'] = $registrations;

                    // Lấy danh sách tham gia
                    $query = "SELECT tg.*, u.HoTen, u.MaSinhVien
                             FROM danhsachthamgia tg
                             LEFT JOIN nguoidung u ON tg.NguoiDungId = u.Id
                             WHERE tg.HoatDongId = ?";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $participants = [];
                    $result = $stmt->get_result();
                    while($row = $result->fetch_assoc()) {
                        $participants[] = $row;
                    }
                    $activity['participants'] = $participants;

                    sendResponse(true, 'Chi tiết hoạt động', $activity);
                }
                sendResponse(false, 'Không tìm thấy hoạt động');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    case 'POST':
        if(!$auth->isLoggedIn()) {
            sendResponse(false, 'Unauthorized');
        }

        switch($action) {
            case 'create':
                if(!$auth->isAdmin()) {
                    sendResponse(false, 'Không có quyền thực hiện');
                }

                $data = json_decode(file_get_contents('php://input'), true);
                $tenHoatDong = $data['tenHoatDong'] ?? '';
                $moTa = $data['moTa'] ?? '';
                $ngayBatDau = $data['ngayBatDau'] ?? '';
                $ngayKetThuc = $data['ngayKetThuc'] ?? '';
                $diaDiem = $data['diaDiem'] ?? '';
                $toaDo = $data['toaDo'] ?? '';
                $soLuong = $data['soLuong'] ?? 0;
                $nguoiTaoId = $_SESSION['user_id'];

                if(empty($tenHoatDong) || empty($ngayBatDau) || empty($ngayKetThuc)) {
                    sendResponse(false, 'Vui lòng điền đầy đủ thông tin bắt buộc');
                }

                $query = "INSERT INTO hoatdong (TenHoatDong, MoTa, NgayBatDau, NgayKetThuc, DiaDiem, ToaDo, SoLuong, NguoiTaoId) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ssssssii', $tenHoatDong, $moTa, $ngayBatDau, $ngayKetThuc, $diaDiem, $toaDo, $soLuong, $nguoiTaoId);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Tạo hoạt động thành công', ['id' => $stmt->insert_id]);
                }
                sendResponse(false, 'Tạo hoạt động thất bại');
                break;

            case 'register':
                $data = json_decode(file_get_contents('php://input'), true);
                $hoatDongId = $data['hoatDongId'] ?? 0;

                if(!$hoatDongId) {
                    sendResponse(false, 'ID hoạt động không hợp lệ');
                }

                // Kiểm tra đã đăng ký chưa
                $query = "SELECT Id FROM danhsachdangky WHERE NguoiDungId = ? AND HoatDongId = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $_SESSION['user_id'], $hoatDongId);
                $stmt->execute();
                if($stmt->get_result()->num_rows > 0) {
                    sendResponse(false, 'Bạn đã đăng ký hoạt động này');
                }

                // Thêm đăng ký mới
                $query = "INSERT INTO danhsachdangky (NguoiDungId, HoatDongId) VALUES (?, ?)";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $_SESSION['user_id'], $hoatDongId);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Đăng ký hoạt động thành công');
                }
                sendResponse(false, 'Đăng ký hoạt động thất bại');
                break;

            case 'attendance':
                if(!$auth->isAdmin()) {
                    sendResponse(false, 'Không có quyền thực hiện');
                }

                $data = json_decode(file_get_contents('php://input'), true);
                $hoatDongId = $data['hoatDongId'] ?? 0;
                $nguoiDungId = $data['nguoiDungId'] ?? 0;
                $trangThai = $data['trangThai'] ?? 1;

                if(!$hoatDongId || !$nguoiDungId) {
                    sendResponse(false, 'Thông tin không hợp lệ');
                }

                $query = "INSERT INTO danhsachthamgia (NguoiDungId, HoatDongId, TrangThai) VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE TrangThai = VALUES(TrangThai)";
                $stmt = $con->prepare($query);
                $stmt->bind_param('iii', $nguoiDungId, $hoatDongId, $trangThai);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Cập nhật điểm danh thành công');
                }
                sendResponse(false, 'Cập nhật điểm danh thất bại');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
