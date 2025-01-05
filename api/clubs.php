<?php
require_once 'config.php';
require_once '../config/auth.php';

$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Kiểm tra đăng nhập
if (!$auth->isLoggedIn()) {
    sendResponse(false, 'Unauthorized');
}

switch($method) {
    case 'GET':
        switch($action) {
            case 'list':
                $query = "SELECT c.*, COUNT(tv.Id) as SoThanhVien
                         FROM caulacbo c
                         LEFT JOIN thanhvienclb tv ON c.Id = tv.CauLacBoId
                         GROUP BY c.Id
                         ORDER BY c.TenCLB";
                $result = $con->query($query);
                $clubs = [];
                
                while ($row = $result->fetch_assoc()) {
                    $clubs[] = $row;
                }
                
                sendResponse(true, 'Danh sách câu lạc bộ', $clubs);
                break;

            case 'detail':
                $id = isset($_GET['id']) ? $_GET['id'] : null;
                if (!$id) {
                    sendResponse(false, 'ID câu lạc bộ không được để trống');
                }

                // Thông tin CLB
                $query = "SELECT * FROM caulacbo WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $club = $stmt->get_result()->fetch_assoc();

                if (!$club) {
                    sendResponse(false, 'Không tìm thấy câu lạc bộ');
                }

                // Danh sách thành viên
                $query = "SELECT tv.*, nd.HoTen, nd.Email, cv.TenChucVu
                         FROM thanhvienclb tv
                         JOIN nguoidung nd ON tv.NguoiDungId = nd.Id
                         LEFT JOIN chucvu cv ON tv.ChucVuId = cv.Id
                         WHERE tv.CauLacBoId = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $members = [];
                while ($row = $result->fetch_assoc()) {
                    $members[] = $row;
                }

                $club['members'] = $members;
                sendResponse(true, 'Chi tiết câu lạc bộ', $club);
                break;

            case 'my-clubs':
                $user_id = $_SESSION['user_id'];
                $query = "SELECT c.*, tv.ChucVuId, cv.TenChucVu
                         FROM caulacbo c
                         JOIN thanhvienclb tv ON c.Id = tv.CauLacBoId
                         LEFT JOIN chucvu cv ON tv.ChucVuId = cv.Id
                         WHERE tv.NguoiDungId = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $clubs = [];
                while ($row = $result->fetch_assoc()) {
                    $clubs[] = $row;
                }
                
                sendResponse(true, 'Câu lạc bộ của tôi', $clubs);
                break;
        }
        break;

    case 'POST':
        switch($action) {
            case 'join':
                $data = json_decode(file_get_contents('php://input'), true);
                $club_id = $data['club_id'] ?? null;
                
                if (!$club_id) {
                    sendResponse(false, 'ID câu lạc bộ không được để trống');
                }

                $user_id = $_SESSION['user_id'];
                
                // Kiểm tra đã là thành viên chưa
                $check_query = "SELECT * FROM thanhvienclb WHERE NguoiDungId = ? AND CauLacBoId = ?";
                $stmt = $con->prepare($check_query);
                $stmt->bind_param('ii', $user_id, $club_id);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows > 0) {
                    sendResponse(false, 'Bạn đã là thành viên của câu lạc bộ này');
                }

                // Thêm thành viên mới
                $query = "INSERT INTO thanhvienclb (NguoiDungId, CauLacBoId, NgayGiaNhap) VALUES (?, ?, NOW())";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $user_id, $club_id);
                
                if ($stmt->execute()) {
                    sendResponse(true, 'Tham gia câu lạc bộ thành công');
                }
                
                sendResponse(false, 'Tham gia câu lạc bộ thất bại');
                break;

            case 'leave':
                $data = json_decode(file_get_contents('php://input'), true);
                $club_id = $data['club_id'] ?? null;
                
                if (!$club_id) {
                    sendResponse(false, 'ID câu lạc bộ không được để trống');
                }

                $user_id = $_SESSION['user_id'];
                
                $query = "DELETE FROM thanhvienclb WHERE NguoiDungId = ? AND CauLacBoId = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $user_id, $club_id);
                
                if ($stmt->execute()) {
                    sendResponse(true, 'Rời câu lạc bộ thành công');
                }
                
                sendResponse(false, 'Rời câu lạc bộ thất bại');
                break;
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
