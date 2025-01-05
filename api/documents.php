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
                $searchCondition = buildSearchCondition(['TenTaiLieu', 'MoTa'], $search);
                $loaiTaiLieu = isset($_GET['loaiTaiLieu']) ? $_GET['loaiTaiLieu'] : '';

                $conditions = [];
                if($searchCondition) $conditions[] = $searchCondition;
                if($loaiTaiLieu) $conditions[] = "LoaiTaiLieu = '$loaiTaiLieu'";

                // Kiểm tra quyền xem tài liệu
                if(!$auth->isAdmin()) {
                    $vaiTroId = $_SESSION['role_id'];
                    $conditions[] = "EXISTS (
                        SELECT 1 FROM phanquyentailieu pq 
                        WHERE pq.TaiLieuId = t.Id 
                        AND pq.VaiTroId = $vaiTroId
                        AND pq.Quyen = 1
                    )";
                }

                $whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

                $query = "SELECT t.*, nd.HoTen as NguoiTao
                         FROM tailieu t
                         LEFT JOIN nguoidung nd ON t.NguoiTaoId = nd.Id
                         $whereClause
                         ORDER BY t.NgayTao DESC
                         LIMIT ? OFFSET ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $pagination['limit'], $pagination['offset']);
                $stmt->execute();
                $documents = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $documents[] = $row;
                }

                $countQuery = "SELECT COUNT(*) as total FROM tailieu t $whereClause";
                $total = $con->query($countQuery)->fetch_assoc()['total'];

                sendPaginationResponse(true, 'Danh sách tài liệu', $documents, $total, 
                                    $pagination['page'], $pagination['limit']);
                break;

            case 'detail':
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }

                $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                if(!$id) {
                    sendResponse(false, 'ID tài liệu không hợp lệ');
                }

                // Kiểm tra quyền xem tài liệu
                if(!$auth->isAdmin()) {
                    $vaiTroId = $_SESSION['role_id'];
                    $query = "SELECT 1 FROM phanquyentailieu 
                             WHERE TaiLieuId = ? AND VaiTroId = ? AND Quyen = 1";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param('ii', $id, $vaiTroId);
                    $stmt->execute();
                    if($stmt->get_result()->num_rows === 0) {
                        sendResponse(false, 'Không có quyền xem tài liệu này');
                    }
                }

                $query = "SELECT t.*, nd.HoTen as NguoiTao
                         FROM tailieu t
                         LEFT JOIN nguoidung nd ON t.NguoiTaoId = nd.Id
                         WHERE t.Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $document = $stmt->get_result()->fetch_assoc();

                if($document) {
                    // Lấy danh sách phân quyền
                    $query = "SELECT pq.*, v.TenVaiTro
                             FROM phanquyentailieu pq
                             JOIN vaitro v ON pq.VaiTroId = v.Id
                             WHERE pq.TaiLieuId = ?";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $permissions = [];
                    $result = $stmt->get_result();
                    while($row = $result->fetch_assoc()) {
                        $permissions[] = $row;
                    }
                    $document['permissions'] = $permissions;

                    sendResponse(true, 'Chi tiết tài liệu', $document);
                }
                sendResponse(false, 'Không tìm thấy tài liệu');
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
                $tenTaiLieu = $data['tenTaiLieu'] ?? '';
                $moTa = $data['moTa'] ?? '';
                $duongDan = $data['duongDan'] ?? '';
                $loaiTaiLieu = $data['loaiTaiLieu'] ?? '';
                $phanQuyen = $data['phanQuyen'] ?? [];

                if(empty($tenTaiLieu) || empty($duongDan)) {
                    sendResponse(false, 'Vui lòng điền đầy đủ thông tin bắt buộc');
                }

                $con->begin_transaction();
                try {
                    // Thêm tài liệu
                    $query = "INSERT INTO tailieu (TenTaiLieu, MoTa, DuongDan, LoaiTaiLieu, NguoiTaoId) 
                             VALUES (?, ?, ?, ?, ?)";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param('ssssi', $tenTaiLieu, $moTa, $duongDan, $loaiTaiLieu, $_SESSION['user_id']);
                    $stmt->execute();
                    $taiLieuId = $stmt->insert_id;

                    // Thêm phân quyền
                    if(!empty($phanQuyen)) {
                        $query = "INSERT INTO phanquyentailieu (TaiLieuId, VaiTroId, Quyen) VALUES (?, ?, ?)";
                        $stmt = $con->prepare($query);
                        foreach($phanQuyen as $vaiTro) {
                            $stmt->bind_param('iii', $taiLieuId, $vaiTro['vaiTroId'], $vaiTro['quyen']);
                            $stmt->execute();
                        }
                    }

                    $con->commit();
                    sendResponse(true, 'Tạo tài liệu thành công', ['id' => $taiLieuId]);
                } catch(Exception $e) {
                    $con->rollback();
                    sendResponse(false, 'Tạo tài liệu thất bại: ' . $e->getMessage());
                }
                break;

            case 'update-permissions':
                if(!$auth->isAdmin()) {
                    sendResponse(false, 'Không có quyền thực hiện');
                }

                $data = json_decode(file_get_contents('php://input'), true);
                $taiLieuId = $data['taiLieuId'] ?? 0;
                $phanQuyen = $data['phanQuyen'] ?? [];

                if(!$taiLieuId) {
                    sendResponse(false, 'ID tài liệu không hợp lệ');
                }

                $con->begin_transaction();
                try {
                    // Xóa phân quyền cũ
                    $query = "DELETE FROM phanquyentailieu WHERE TaiLieuId = ?";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param('i', $taiLieuId);
                    $stmt->execute();

                    // Thêm phân quyền mới
                    if(!empty($phanQuyen)) {
                        $query = "INSERT INTO phanquyentailieu (TaiLieuId, VaiTroId, Quyen) VALUES (?, ?, ?)";
                        $stmt = $con->prepare($query);
                        foreach($phanQuyen as $vaiTro) {
                            $stmt->bind_param('iii', $taiLieuId, $vaiTro['vaiTroId'], $vaiTro['quyen']);
                            $stmt->execute();
                        }
                    }

                    $con->commit();
                    sendResponse(true, 'Cập nhật phân quyền thành công');
                } catch(Exception $e) {
                    $con->rollback();
                    sendResponse(false, 'Cập nhật phân quyền thất bại: ' . $e->getMessage());
                }
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
