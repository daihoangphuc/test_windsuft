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
                $searchCondition = buildSearchCondition(['TenKhoaTruong'], $search);
                
                $whereClause = $searchCondition ? "WHERE $searchCondition" : "";
                
                $query = "SELECT k.*, 
                         (SELECT COUNT(*) FROM nguoidung WHERE KhoaTruongId = k.Id) as SoLuongSinhVien
                         FROM khoatruong k
                         $whereClause
                         ORDER BY k.NgayTao DESC
                         LIMIT ? OFFSET ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $pagination['limit'], $pagination['offset']);
                $stmt->execute();
                $faculties = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $faculties[] = $row;
                }

                $countQuery = "SELECT COUNT(*) as total FROM khoatruong $whereClause";
                $total = $con->query($countQuery)->fetch_assoc()['total'];

                sendPaginationResponse(true, 'Danh sách khoa/trường', $faculties, $total, 
                                    $pagination['page'], $pagination['limit']);
                break;

            case 'detail':
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }

                $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                if(!$id) {
                    sendResponse(false, 'ID khoa/trường không hợp lệ');
                }

                $query = "SELECT k.*, 
                         (SELECT COUNT(*) FROM nguoidung WHERE KhoaTruongId = k.Id) as SoLuongSinhVien
                         FROM khoatruong k
                         WHERE k.Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $faculty = $stmt->get_result()->fetch_assoc();

                if($faculty) {
                    // Lấy danh sách sinh viên của khoa/trường
                    $query = "SELECT Id, HoTen, Email, MaSinhVien 
                             FROM nguoidung 
                             WHERE KhoaTruongId = ?
                             ORDER BY HoTen
                             LIMIT 10"; // Chỉ lấy 10 sinh viên đầu tiên
                    $stmt = $con->prepare($query);
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $students = [];
                    $result = $stmt->get_result();
                    while($row = $result->fetch_assoc()) {
                        $students[] = $row;
                    }
                    $faculty['students'] = $students;

                    sendResponse(true, 'Chi tiết khoa/trường', $faculty);
                }
                sendResponse(false, 'Không tìm thấy khoa/trường');
                break;

            case 'students':
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }

                $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                if(!$id) {
                    sendResponse(false, 'ID khoa/trường không hợp lệ');
                }

                $pagination = getPaginationInfo();
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $searchCondition = buildSearchCondition(['nd.HoTen', 'nd.Email', 'nd.MaSinhVien'], $search);
                
                $whereClause = "WHERE lh.KhoaTruongId = $id";
                if($searchCondition) {
                    $whereClause .= " AND $searchCondition";
                }
                
                $query = "SELECT nd.Id, nd.HoTen, nd.Email, nd.MaSinhVien, lh.TenLop 
                         FROM nguoidung nd
                         INNER JOIN lophoc lh ON nd.LopHocId = lh.Id 
                         $whereClause
                         ORDER BY nd.HoTen
                         LIMIT ? OFFSET ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $pagination['limit'], $pagination['offset']);
                $stmt->execute();
                $students = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $students[] = $row;
                }

                $countQuery = "SELECT COUNT(*) as total 
                              FROM nguoidung nd
                              INNER JOIN lophoc lh ON nd.LopHocId = lh.Id 
                              $whereClause";
                $total = $con->query($countQuery)->fetch_assoc()['total'];

                sendPaginationResponse(true, 'Danh sách sinh viên', $students, $total, 
                                    $pagination['page'], $pagination['limit']);
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
                $tenKhoaTruong = $data['tenKhoaTruong'] ?? '';

                if(empty($tenKhoaTruong)) {
                    sendResponse(false, 'Vui lòng điền tên khoa/trường');
                }

                $query = "INSERT INTO khoatruong (TenKhoaTruong) VALUES (?)";
                $stmt = $con->prepare($query);
                $stmt->bind_param('s', $tenKhoaTruong);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Tạo khoa/trường thành công', ['id' => $stmt->insert_id]);
                }
                sendResponse(false, 'Tạo khoa/trường thất bại');
                break;

            case 'update':
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;
                $tenKhoaTruong = $data['tenKhoaTruong'] ?? '';

                if(!$id || empty($tenKhoaTruong)) {
                    sendResponse(false, 'Thông tin không hợp lệ');
                }

                $query = "UPDATE khoatruong SET TenKhoaTruong = ? WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('si', $tenKhoaTruong, $id);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Cập nhật khoa/trường thành công');
                }
                sendResponse(false, 'Cập nhật khoa/trường thất bại');
                break;

            case 'delete':
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;

                if(!$id) {
                    sendResponse(false, 'ID khoa/trường không hợp lệ');
                }

                // Kiểm tra xem có sinh viên nào thuộc khoa/trường này không
                $query = "SELECT COUNT(*) as total FROM nguoidung WHERE KhoaTruongId = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                if($stmt->get_result()->fetch_assoc()['total'] > 0) {
                    sendResponse(false, 'Không thể xóa khoa/trường đang có sinh viên');
                }

                $query = "DELETE FROM khoatruong WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Xóa khoa/trường thành công');
                }
                sendResponse(false, 'Xóa khoa/trường thất bại');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
