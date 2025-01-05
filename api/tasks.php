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
                $searchCondition = buildSearchCondition(['TenNhiemVu', 'MoTa'], $search);
                
                $whereClause = $searchCondition ? "WHERE $searchCondition" : "";
                
                $query = "SELECT n.*, 
                         (SELECT GROUP_CONCAT(CONCAT(nd.HoTen, '|', nd.Id) SEPARATOR ',')
                          FROM phancongnhiemvu pc 
                          JOIN nguoidung nd ON pc.NguoiDungId = nd.Id 
                          WHERE pc.NhiemVuId = n.Id) as NguoiThucHien
                         FROM nhiemvu n 
                         $whereClause
                         ORDER BY n.NgayTao DESC
                         LIMIT ? OFFSET ?";
                         
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $pagination['limit'], $pagination['offset']);
                $stmt->execute();
                $tasks = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    if($row['NguoiThucHien']) {
                        $nguoiThucHien = [];
                        foreach(explode(',', $row['NguoiThucHien']) as $user) {
                            list($name, $id) = explode('|', $user);
                            $nguoiThucHien[] = ['id' => $id, 'name' => $name];
                        }
                        $row['NguoiThucHien'] = $nguoiThucHien;
                    } else {
                        $row['NguoiThucHien'] = [];
                    }
                    $tasks[] = $row;
                }

                $countQuery = "SELECT COUNT(*) as total FROM nhiemvu $whereClause";
                $total = $con->query($countQuery)->fetch_assoc()['total'];

                sendPaginationResponse(true, 'Danh sách nhiệm vụ', $tasks, $total, 
                                    $pagination['page'], $pagination['limit']);
                break;

            case 'detail':
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }

                $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                if(!$id) {
                    sendResponse(false, 'ID nhiệm vụ không hợp lệ');
                }

                $query = "SELECT n.*, 
                         (SELECT GROUP_CONCAT(CONCAT(nd.HoTen, '|', nd.Id) SEPARATOR ',')
                          FROM phancongnhiemvu pc 
                          JOIN nguoidung nd ON pc.NguoiDungId = nd.Id 
                          WHERE pc.NhiemVuId = n.Id) as NguoiThucHien
                         FROM nhiemvu n
                         WHERE n.Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $task = $stmt->get_result()->fetch_assoc();

                if($task) {
                    if($task['NguoiThucHien']) {
                        $nguoiThucHien = [];
                        foreach(explode(',', $task['NguoiThucHien']) as $user) {
                            list($name, $id) = explode('|', $user);
                            $nguoiThucHien[] = ['id' => $id, 'name' => $name];
                        }
                        $task['NguoiThucHien'] = $nguoiThucHien;
                    } else {
                        $task['NguoiThucHien'] = [];
                    }
                    sendResponse(true, 'Chi tiết nhiệm vụ', $task);
                }
                sendResponse(false, 'Không tìm thấy nhiệm vụ');
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
                $tenNhiemVu = $data['tenNhiemVu'] ?? '';
                $moTa = $data['moTa'] ?? '';
                $ngayBatDau = $data['ngayBatDau'] ?? '';
                $ngayKetThuc = $data['ngayKetThuc'] ?? '';
                $nguoiThucHien = $data['nguoiThucHien'] ?? [];

                if(empty($tenNhiemVu) || empty($ngayBatDau) || empty($ngayKetThuc)) {
                    sendResponse(false, 'Vui lòng điền đầy đủ thông tin bắt buộc');
                }

                $con->begin_transaction();
                try {
                    // Thêm nhiệm vụ
                    $query = "INSERT INTO nhiemvu (TenNhiemVu, MoTa, NgayBatDau, NgayKetThuc) 
                             VALUES (?, ?, ?, ?)";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param('ssss', $tenNhiemVu, $moTa, $ngayBatDau, $ngayKetThuc);
                    $stmt->execute();
                    $nhiemVuId = $stmt->insert_id;

                    // Phân công người thực hiện
                    if(!empty($nguoiThucHien)) {
                        $query = "INSERT INTO phancongnhiemvu (NhiemVuId, NguoiDungId, NguoiPhanCong) 
                                 VALUES (?, ?, ?)";
                        $stmt = $con->prepare($query);
                        foreach($nguoiThucHien as $userId) {
                            $stmt->bind_param('iis', $nhiemVuId, $userId, $_SESSION['username']);
                            $stmt->execute();
                        }
                    }

                    $con->commit();
                    sendResponse(true, 'Tạo nhiệm vụ thành công', ['id' => $nhiemVuId]);
                } catch(Exception $e) {
                    $con->rollback();
                    sendResponse(false, 'Tạo nhiệm vụ thất bại: ' . $e->getMessage());
                }
                break;

            case 'update-status':
                if(!$auth->isAdmin()) {
                    sendResponse(false, 'Không có quyền thực hiện');
                }

                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;
                $trangThai = $data['trangThai'] ?? 0;

                if(!$id) {
                    sendResponse(false, 'ID nhiệm vụ không hợp lệ');
                }

                $query = "UPDATE nhiemvu SET TrangThai = ? WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $trangThai, $id);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Cập nhật trạng thái nhiệm vụ thành công');
                }
                sendResponse(false, 'Cập nhật trạng thái nhiệm vụ thất bại');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
