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
                $searchCondition = buildSearchCondition(['TenChucVu'], $search);
                
                $whereClause = $searchCondition ? "WHERE $searchCondition" : "";
                
                $query = "SELECT * FROM chucvu 
                         $whereClause
                         ORDER BY NgayTao DESC
                         LIMIT ? OFFSET ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $pagination['limit'], $pagination['offset']);
                $stmt->execute();
                $positions = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $positions[] = $row;
                }

                $countQuery = "SELECT COUNT(*) as total FROM chucvu $whereClause";
                $total = $con->query($countQuery)->fetch_assoc()['total'];

                sendPaginationResponse(true, 'Danh sách chức vụ', $positions, $total, 
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
                $tenChucVu = $data['tenChucVu'] ?? '';

                if(empty($tenChucVu)) {
                    sendResponse(false, 'Vui lòng điền tên chức vụ');
                }

                $query = "INSERT INTO chucvu (TenChucVu) VALUES (?)";
                $stmt = $con->prepare($query);
                $stmt->bind_param('s', $tenChucVu);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Tạo chức vụ thành công', ['id' => $stmt->insert_id]);
                }
                sendResponse(false, 'Tạo chức vụ thất bại');
                break;

            case 'update':
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;
                $tenChucVu = $data['tenChucVu'] ?? '';

                if(!$id || empty($tenChucVu)) {
                    sendResponse(false, 'Thông tin không hợp lệ');
                }

                $query = "UPDATE chucvu SET TenChucVu = ? WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('si', $tenChucVu, $id);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Cập nhật chức vụ thành công');
                }
                sendResponse(false, 'Cập nhật chức vụ thất bại');
                break;

            case 'delete':
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;

                if(!$id) {
                    sendResponse(false, 'ID chức vụ không hợp lệ');
                }

                // Kiểm tra xem có người dùng nào đang giữ chức vụ này không
                $query = "SELECT COUNT(*) as total FROM nguoidung WHERE ChucVuId = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                if($stmt->get_result()->fetch_assoc()['total'] > 0) {
                    sendResponse(false, 'Không thể xóa chức vụ đang được sử dụng');
                }

                $query = "DELETE FROM chucvu WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Xóa chức vụ thành công');
                }
                sendResponse(false, 'Xóa chức vụ thất bại');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
