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
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $offset = ($page - 1) * $limit;

                $query = "SELECT t.*, u.HoTen as NguoiTao 
                         FROM tintuc t
                         LEFT JOIN nguoidung u ON t.NguoiTaoId = u.Id
                         ORDER BY t.NgayTao DESC
                         LIMIT ? OFFSET ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $limit, $offset);
                $stmt->execute();
                $news = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $news[] = $row;
                }

                // Đếm tổng số tin tức
                $countQuery = "SELECT COUNT(*) as total FROM tintuc";
                $total = $con->query($countQuery)->fetch_assoc()['total'];

                sendResponse(true, 'Danh sách tin tức', [
                    'items' => $news,
                    'total' => (int)$total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil($total / $limit)
                ]);
                break;

            case 'detail':
                $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                if(!$id) {
                    sendResponse(false, 'ID tin tức không hợp lệ');
                }

                $query = "SELECT t.*, u.HoTen as NguoiTao 
                         FROM tintuc t
                         LEFT JOIN nguoidung u ON t.NguoiTaoId = u.Id
                         WHERE t.Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $news = $stmt->get_result()->fetch_assoc();

                if($news) {
                    sendResponse(true, 'Chi tiết tin tức', $news);
                }
                sendResponse(false, 'Không tìm thấy tin tức');
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
                $tieuDe = $data['tieuDe'] ?? '';
                $noiDung = $data['noiDung'] ?? '';
                $fileDinhKem = $data['fileDinhKem'] ?? '';
                $nguoiTaoId = $_SESSION['user_id'];

                if(empty($tieuDe) || empty($noiDung)) {
                    sendResponse(false, 'Vui lòng điền đầy đủ thông tin bắt buộc');
                }

                $query = "INSERT INTO tintuc (TieuDe, NoiDung, FileDinhKem, NguoiTaoId) VALUES (?, ?, ?, ?)";
                $stmt = $con->prepare($query);
                $stmt->bind_param('sssi', $tieuDe, $noiDung, $fileDinhKem, $nguoiTaoId);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Tạo tin tức thành công', ['id' => $stmt->insert_id]);
                }
                sendResponse(false, 'Tạo tin tức thất bại');
                break;

            case 'update':
                if(!$auth->isAdmin()) {
                    sendResponse(false, 'Không có quyền thực hiện');
                }

                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;
                $tieuDe = $data['tieuDe'] ?? '';
                $noiDung = $data['noiDung'] ?? '';
                $fileDinhKem = $data['fileDinhKem'] ?? '';

                if(!$id || empty($tieuDe) || empty($noiDung)) {
                    sendResponse(false, 'Thông tin không hợp lệ');
                }

                $query = "UPDATE tintuc SET TieuDe = ?, NoiDung = ?, FileDinhKem = ? WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('sssi', $tieuDe, $noiDung, $fileDinhKem, $id);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Cập nhật tin tức thành công');
                }
                sendResponse(false, 'Cập nhật tin tức thất bại');
                break;

            case 'delete':
                if(!$auth->isAdmin()) {
                    sendResponse(false, 'Không có quyền thực hiện');
                }

                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;

                if(!$id) {
                    sendResponse(false, 'ID tin tức không hợp lệ');
                }

                $query = "DELETE FROM tintuc WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Xóa tin tức thành công');
                }
                sendResponse(false, 'Xóa tin tức thất bại');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
