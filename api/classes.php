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
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }

                $pagination = getPaginationInfo();
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $khoaTruongId = isset($_GET['khoaTruongId']) ? (int)$_GET['khoaTruongId'] : null;
                
                $conditions = [];
                if($search) {
                    $conditions[] = buildSearchCondition(['TenLop'], $search);
                }
                if($khoaTruongId) {
                    $conditions[] = "l.KhoaTruongId = $khoaTruongId";
                }
                
                $whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
                
                $query = "SELECT l.*, k.TenKhoaTruong,
                         (SELECT COUNT(*) FROM nguoidung WHERE LopHocId = l.Id) as SoLuongSinhVien
                         FROM lophoc l
                         LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id
                         $whereClause
                         ORDER BY l.NgayTao DESC
                         LIMIT ? OFFSET ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $pagination['limit'], $pagination['offset']);
                $stmt->execute();
                $classes = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $classes[] = $row;
                }

                $countQuery = "SELECT COUNT(*) as total FROM lophoc l $whereClause";
                $total = $con->query($countQuery)->fetch_assoc()['total'];

                sendPaginationResponse(true, 'Danh sách lớp học', $classes, $total, 
                                    $pagination['page'], $pagination['limit']);
                break;

            case 'detail':
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }

                $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                if(!$id) {
                    sendResponse(false, 'ID lớp học không hợp lệ');
                }

                $query = "SELECT l.*, k.TenKhoaTruong,
                         (SELECT COUNT(*) FROM nguoidung WHERE LopHocId = l.Id) as SoLuongSinhVien
                         FROM lophoc l
                         LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id
                         WHERE l.Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $class = $stmt->get_result()->fetch_assoc();

                if($class) {
                    // Lấy danh sách sinh viên của lớp
                    $query = "SELECT Id, MaSinhVien, HoTen, Email, GioiTinh, NgaySinh, 
                             ChucVuId, VaiTroId, TrangThai
                             FROM nguoidung 
                             WHERE LopHocId = ?
                             ORDER BY HoTen";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $students = [];
                    $result = $stmt->get_result();
                    while($row = $result->fetch_assoc()) {
                        $students[] = $row;
                    }
                    $class['students'] = $students;

                    sendResponse(true, 'Chi tiết lớp học', $class);
                }
                sendResponse(false, 'Không tìm thấy lớp học');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    case 'POST':
        if(!$auth->isAdmin()) {
            sendResponse(false, 'Không có quyền thực hiện');
        }

        switch($action) {
            case 'create':
                $data = json_decode(file_get_contents('php://input'), true);
                $tenLop = $data['tenLop'] ?? '';
                $khoaTruongId = $data['khoaTruongId'] ?? null;

                if(empty($tenLop) || !$khoaTruongId) {
                    sendResponse(false, 'Vui lòng điền đầy đủ thông tin');
                }

                // Kiểm tra khoa/trường tồn tại
                $query = "SELECT 1 FROM khoatruong WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $khoaTruongId);
                $stmt->execute();
                if($stmt->get_result()->num_rows === 0) {
                    sendResponse(false, 'Khoa/trường không tồn tại');
                }

                $query = "INSERT INTO lophoc (TenLop, KhoaTruongId) VALUES (?, ?)";
                $stmt = $con->prepare($query);
                $stmt->bind_param('si', $tenLop, $khoaTruongId);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Tạo lớp học thành công', ['id' => $stmt->insert_id]);
                }
                sendResponse(false, 'Tạo lớp học thất bại');
                break;

            case 'update':
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;
                $tenLop = $data['tenLop'] ?? '';
                $khoaTruongId = $data['khoaTruongId'] ?? null;

                if(!$id || empty($tenLop) || !$khoaTruongId) {
                    sendResponse(false, 'Thông tin không hợp lệ');
                }

                // Kiểm tra khoa/trường tồn tại
                $query = "SELECT 1 FROM khoatruong WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $khoaTruongId);
                $stmt->execute();
                if($stmt->get_result()->num_rows === 0) {
                    sendResponse(false, 'Khoa/trường không tồn tại');
                }

                $query = "UPDATE lophoc SET TenLop = ?, KhoaTruongId = ? WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('sii', $tenLop, $khoaTruongId, $id);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Cập nhật lớp học thành công');
                }
                sendResponse(false, 'Cập nhật lớp học thất bại');
                break;

            case 'delete':
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;

                if(!$id) {
                    sendResponse(false, 'ID lớp học không hợp lệ');
                }

                // Kiểm tra xem có sinh viên nào trong lớp không
                $query = "SELECT COUNT(*) as total FROM nguoidung WHERE LopHocId = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                if($stmt->get_result()->fetch_assoc()['total'] > 0) {
                    sendResponse(false, 'Không thể xóa lớp học đang có sinh viên');
                }

                $query = "DELETE FROM lophoc WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Xóa lớp học thành công');
                }
                sendResponse(false, 'Xóa lớp học thất bại');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
