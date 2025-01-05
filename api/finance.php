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
                $searchCondition = buildSearchCondition(['MoTa'], $search);
                $fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : '';
                $toDate = isset($_GET['toDate']) ? $_GET['toDate'] : '';
                $loaiGiaoDich = isset($_GET['loaiGiaoDich']) ? (int)$_GET['loaiGiaoDich'] : null;

                $conditions = [];
                if($searchCondition) $conditions[] = $searchCondition;
                if($fromDate) $conditions[] = "NgayGiaoDich >= '$fromDate'";
                if($toDate) $conditions[] = "NgayGiaoDich <= '$toDate'";
                if($loaiGiaoDich !== null) $conditions[] = "LoaiGiaoDich = $loaiGiaoDich";

                $whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

                $query = "SELECT t.*, nd.HoTen as NguoiThucHien
                         FROM taichinh t
                         LEFT JOIN nguoidung nd ON t.NguoiDungId = nd.Id
                         $whereClause
                         ORDER BY t.NgayGiaoDich DESC
                         LIMIT ? OFFSET ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $pagination['limit'], $pagination['offset']);
                $stmt->execute();
                $transactions = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $transactions[] = $row;
                }

                // Tính tổng thu/chi
                $statsQuery = "SELECT 
                    SUM(CASE WHEN LoaiGiaoDich = 0 THEN SoTien ELSE 0 END) as TongThu,
                    SUM(CASE WHEN LoaiGiaoDich = 1 THEN SoTien ELSE 0 END) as TongChi
                    FROM taichinh $whereClause";
                $stats = $con->query($statsQuery)->fetch_assoc();

                $countQuery = "SELECT COUNT(*) as total FROM taichinh $whereClause";
                $total = $con->query($countQuery)->fetch_assoc()['total'];

                sendResponse(true, 'Danh sách giao dịch', [
                    'items' => $transactions,
                    'total' => (int)$total,
                    'page' => $pagination['page'],
                    'limit' => $pagination['limit'],
                    'totalPages' => ceil($total / $pagination['limit']),
                    'stats' => [
                        'tongThu' => (int)$stats['TongThu'],
                        'tongChi' => (int)$stats['TongChi'],
                        'soDu' => (int)($stats['TongThu'] - $stats['TongChi'])
                    ]
                ]);
                break;

            case 'stats':
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }

                $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
                $query = "SELECT 
                    MONTH(NgayGiaoDich) as Thang,
                    SUM(CASE WHEN LoaiGiaoDich = 0 THEN SoTien ELSE 0 END) as TongThu,
                    SUM(CASE WHEN LoaiGiaoDich = 1 THEN SoTien ELSE 0 END) as TongChi
                    FROM taichinh 
                    WHERE YEAR(NgayGiaoDich) = ?
                    GROUP BY MONTH(NgayGiaoDich)
                    ORDER BY Thang";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $year);
                $stmt->execute();
                $monthlyStats = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $monthlyStats[] = [
                        'thang' => (int)$row['Thang'],
                        'tongThu' => (int)$row['TongThu'],
                        'tongChi' => (int)$row['TongChi'],
                        'soDu' => (int)($row['TongThu'] - $row['TongChi'])
                    ];
                }

                sendResponse(true, 'Thống kê theo tháng', $monthlyStats);
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
                $loaiGiaoDich = $data['loaiGiaoDich'] ?? null;
                $soTien = $data['soTien'] ?? 0;
                $moTa = $data['moTa'] ?? '';
                $ngayGiaoDich = $data['ngayGiaoDich'] ?? date('Y-m-d H:i:s');
                $nguoiDungId = $_SESSION['user_id'];

                if($loaiGiaoDich === null || $soTien <= 0) {
                    sendResponse(false, 'Thông tin không hợp lệ');
                }

                $query = "INSERT INTO taichinh (LoaiGiaoDich, SoTien, MoTa, NgayGiaoDich, NguoiDungId) 
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $con->prepare($query);
                $stmt->bind_param('iissi', $loaiGiaoDich, $soTien, $moTa, $ngayGiaoDich, $nguoiDungId);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Tạo giao dịch thành công', ['id' => $stmt->insert_id]);
                }
                sendResponse(false, 'Tạo giao dịch thất bại');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
