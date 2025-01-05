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
                if(!$auth->isAdmin()) {
                    sendResponse(false, 'Không có quyền truy cập');
                }

                $pagination = getPaginationInfo();
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $searchCondition = buildSearchCondition(['HanhDong', 'NoiDung', 'IP'], $search);
                $fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : '';
                $toDate = isset($_GET['toDate']) ? $_GET['toDate'] : '';
                $userId = isset($_GET['userId']) ? (int)$_GET['userId'] : null;

                $conditions = [];
                if($searchCondition) $conditions[] = $searchCondition;
                if($fromDate) $conditions[] = "ThoiGian >= '$fromDate'";
                if($toDate) $conditions[] = "ThoiGian <= '$toDate'";
                if($userId) $conditions[] = "NguoiDungId = $userId";

                $whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

                $query = "SELECT l.*, nd.HoTen as TenNguoiDung
                         FROM log l
                         LEFT JOIN nguoidung nd ON l.NguoiDungId = nd.Id
                         $whereClause
                         ORDER BY l.ThoiGian DESC
                         LIMIT ? OFFSET ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $pagination['limit'], $pagination['offset']);
                $stmt->execute();
                $logs = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $logs[] = $row;
                }

                $countQuery = "SELECT COUNT(*) as total FROM log l $whereClause";
                $total = $con->query($countQuery)->fetch_assoc()['total'];

                sendPaginationResponse(true, 'Danh sách log', $logs, $total, 
                                    $pagination['page'], $pagination['limit']);
                break;

            case 'stats':
                if(!$auth->isAdmin()) {
                    sendResponse(false, 'Không có quyền truy cập');
                }

                // Thống kê số lượng log theo hành động
                $query = "SELECT HanhDong, COUNT(*) as SoLuong
                         FROM log
                         GROUP BY HanhDong
                         ORDER BY SoLuong DESC";
                $result = $con->query($query);
                $actionStats = [];
                while($row = $result->fetch_assoc()) {
                    $actionStats[] = $row;
                }

                // Thống kê số lượng log theo người dùng (top 10)
                $query = "SELECT nd.HoTen, COUNT(*) as SoLuong
                         FROM log l
                         JOIN nguoidung nd ON l.NguoiDungId = nd.Id
                         GROUP BY l.NguoiDungId
                         ORDER BY SoLuong DESC
                         LIMIT 10";
                $result = $con->query($query);
                $userStats = [];
                while($row = $result->fetch_assoc()) {
                    $userStats[] = $row;
                }

                // Thống kê số lượng log theo ngày (7 ngày gần nhất)
                $query = "SELECT DATE(ThoiGian) as Ngay, COUNT(*) as SoLuong
                         FROM log
                         WHERE ThoiGian >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                         GROUP BY DATE(ThoiGian)
                         ORDER BY Ngay DESC";
                $result = $con->query($query);
                $dateStats = [];
                while($row = $result->fetch_assoc()) {
                    $dateStats[] = $row;
                }

                sendResponse(true, 'Thống kê log', [
                    'actionStats' => $actionStats,
                    'userStats' => $userStats,
                    'dateStats' => $dateStats
                ]);
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
                $data = json_decode(file_get_contents('php://input'), true);
                $hanhDong = $data['hanhDong'] ?? '';
                $noiDung = $data['noiDung'] ?? '';
                $ip = $_SERVER['REMOTE_ADDR'];
                $nguoiDungId = $_SESSION['user_id'];

                if(empty($hanhDong) || empty($noiDung)) {
                    sendResponse(false, 'Thông tin không hợp lệ');
                }

                $query = "INSERT INTO log (HanhDong, NoiDung, IP, NguoiDungId) 
                         VALUES (?, ?, ?, ?)";
                $stmt = $con->prepare($query);
                $stmt->bind_param('sssi', $hanhDong, $noiDung, $ip, $nguoiDungId);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Tạo log thành công', ['id' => $stmt->insert_id]);
                }
                sendResponse(false, 'Tạo log thất bại');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
