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
                $lopHocId = isset($_GET['lopHocId']) ? (int)$_GET['lopHocId'] : null;
                $chucVuId = isset($_GET['chucVuId']) ? (int)$_GET['chucVuId'] : null;
                $vaiTroId = isset($_GET['vaiTroId']) ? (int)$_GET['vaiTroId'] : null;
                $trangThai = isset($_GET['trangThai']) ? (int)$_GET['trangThai'] : null;

                $conditions = [];
                if($search) {
                    $conditions[] = buildSearchCondition(['HoTen', 'Email', 'MaSinhVien', 'TenDangNhap'], $search);
                }
                if($lopHocId) $conditions[] = "nd.LopHocId = $lopHocId";
                if($chucVuId) $conditions[] = "nd.ChucVuId = $chucVuId";
                if($vaiTroId) $conditions[] = "nd.VaiTroId = $vaiTroId";
                if($trangThai !== null) $conditions[] = "nd.TrangThai = $trangThai";

                $whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

                $query = "SELECT nd.*, 
                         cv.TenChucVu, 
                         lh.TenLop,
                         vt.TenVaiTro,
                         kt.TenKhoaTruong
                         FROM nguoidung nd
                         LEFT JOIN chucvu cv ON nd.ChucVuId = cv.Id
                         LEFT JOIN lophoc lh ON nd.LopHocId = lh.Id
                         LEFT JOIN vaitro vt ON nd.VaiTroId = vt.Id
                         LEFT JOIN khoatruong kt ON lh.KhoaTruongId = kt.Id
                         $whereClause
                         ORDER BY nd.NgayTao DESC
                         LIMIT ? OFFSET ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $pagination['limit'], $pagination['offset']);
                $stmt->execute();
                $users = [];
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    // Ẩn thông tin nhạy cảm
                    unset($row['MatKhauHash']);
                    unset($row['reset_token']);
                    unset($row['reset_expires']);
                    $users[] = $row;
                }

                $countQuery = "SELECT COUNT(*) as total FROM nguoidung nd $whereClause";
                $total = $con->query($countQuery)->fetch_assoc()['total'];

                sendPaginationResponse(true, 'Danh sách người dùng', $users, $total, 
                                    $pagination['page'], $pagination['limit']);
                break;

            case 'profile':
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }

                $userId = $_SESSION['user_id'];
                $query = "SELECT nd.*, 
                         cv.TenChucVu, 
                         lh.TenLop,
                         vt.TenVaiTro,
                         kt.TenKhoaTruong
                         FROM nguoidung nd
                         LEFT JOIN chucvu cv ON nd.ChucVuId = cv.Id
                         LEFT JOIN lophoc lh ON nd.LopHocId = lh.Id
                         LEFT JOIN vaitro vt ON nd.VaiTroId = vt.Id
                         LEFT JOIN khoatruong kt ON lh.KhoaTruongId = kt.Id
                         WHERE nd.Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();

                if($user) {
                    // Ẩn thông tin nhạy cảm
                    unset($user['MatKhauHash']);
                    unset($user['reset_token']);
                    unset($user['reset_expires']);
                    sendResponse(true, 'Thông tin cá nhân', $user);
                }
                sendResponse(false, 'Không tìm thấy người dùng');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    case 'POST':
        switch($action) {
            case 'create':
                if(!$auth->isAdmin()) {
                    sendResponse(false, 'Không có quyền thực hiện');
                }

                $data = json_decode(file_get_contents('php://input'), true);
                $maSinhVien = $data['maSinhVien'] ?? '';
                $tenDangNhap = $data['tenDangNhap'] ?? '';
                $matKhau = $data['matKhau'] ?? '';
                $hoTen = $data['hoTen'] ?? '';
                $email = $data['email'] ?? '';
                $anhDaiDien = $data['anhDaiDien'] ?? '';
                $gioiTinh = $data['gioiTinh'] ?? 1;
                $ngaySinh = $data['ngaySinh'] ?? null;
                $chucVuId = $data['chucVuId'] ?? null;
                $lopHocId = $data['lopHocId'] ?? null;
                $vaiTroId = $data['vaiTroId'] ?? 2; // Mặc định là member

                if(empty($tenDangNhap) || empty($matKhau) || empty($hoTen) || empty($email)) {
                    sendResponse(false, 'Vui lòng điền đầy đủ thông tin bắt buộc');
                }

                // Kiểm tra username và email đã tồn tại chưa
                $query = "SELECT 1 FROM nguoidung WHERE TenDangNhap = ? OR Email = ? OR MaSinhVien = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('sss', $tenDangNhap, $email, $maSinhVien);
                $stmt->execute();
                if($stmt->get_result()->num_rows > 0) {
                    sendResponse(false, 'Tên đăng nhập, email hoặc mã sinh viên đã tồn tại');
                }

                $matKhauHash = password_hash($matKhau, PASSWORD_DEFAULT);

                $query = "INSERT INTO nguoidung (MaSinhVien, TenDangNhap, MatKhauHash, HoTen, Email, 
                         anhDaiDien, GioiTinh, NgaySinh, ChucVuId, LopHocId, VaiTroId) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ssssssissii', $maSinhVien, $tenDangNhap, $matKhauHash, $hoTen, 
                                $email, $anhDaiDien, $gioiTinh, $ngaySinh, $chucVuId, $lopHocId, $vaiTroId);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Tạo người dùng thành công', ['id' => $stmt->insert_id]);
                }
                sendResponse(false, 'Tạo người dùng thất bại');
                break;

            case 'update-profile':
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }

                $userId = $_SESSION['user_id'];
                $data = json_decode(file_get_contents('php://input'), true);
                $hoTen = $data['hoTen'] ?? '';
                $email = $data['email'] ?? '';
                $anhDaiDien = $data['anhDaiDien'] ?? '';
                $gioiTinh = $data['gioiTinh'] ?? 1;
                $ngaySinh = $data['ngaySinh'] ?? null;

                if(empty($hoTen) || empty($email)) {
                    sendResponse(false, 'Vui lòng điền đầy đủ thông tin bắt buộc');
                }

                // Kiểm tra email đã tồn tại chưa (trừ email hiện tại của user)
                $query = "SELECT 1 FROM nguoidung WHERE Email = ? AND Id != ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('si', $email, $userId);
                $stmt->execute();
                if($stmt->get_result()->num_rows > 0) {
                    sendResponse(false, 'Email đã tồn tại');
                }

                $query = "UPDATE nguoidung 
                         SET HoTen = ?, Email = ?, anhDaiDien = ?, GioiTinh = ?, NgaySinh = ?
                         WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('sssisi', $hoTen, $email, $anhDaiDien, $gioiTinh, $ngaySinh, $userId);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Cập nhật thông tin thành công');
                }
                sendResponse(false, 'Cập nhật thông tin thất bại');
                break;

            case 'change-password':
                if(!$auth->isLoggedIn()) {
                    sendResponse(false, 'Unauthorized');
                }

                $userId = $_SESSION['user_id'];
                $data = json_decode(file_get_contents('php://input'), true);
                $matKhauCu = $data['matKhauCu'] ?? '';
                $matKhauMoi = $data['matKhauMoi'] ?? '';

                if(empty($matKhauCu) || empty($matKhauMoi)) {
                    sendResponse(false, 'Vui lòng điền đầy đủ thông tin');
                }

                // Kiểm tra mật khẩu cũ
                $query = "SELECT MatKhauHash FROM nguoidung WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();

                if(!password_verify($matKhauCu, $user['MatKhauHash'])) {
                    sendResponse(false, 'Mật khẩu cũ không đúng');
                }

                $matKhauHash = password_hash($matKhauMoi, PASSWORD_DEFAULT);

                $query = "UPDATE nguoidung SET MatKhauHash = ? WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('si', $matKhauHash, $userId);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Đổi mật khẩu thành công');
                }
                sendResponse(false, 'Đổi mật khẩu thất bại');
                break;

            case 'reset-password':
                $data = json_decode(file_get_contents('php://input'), true);
                $email = $data['email'] ?? '';

                if(empty($email)) {
                    sendResponse(false, 'Vui lòng nhập email');
                }

                // Kiểm tra email tồn tại
                $query = "SELECT Id FROM nguoidung WHERE Email = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();

                if(!$user) {
                    sendResponse(false, 'Email không tồn tại trong hệ thống');
                }

                // Tạo token reset password
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $query = "UPDATE nguoidung SET reset_token = ?, reset_expires = ? WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ssi', $token, $expires, $user['Id']);
                
                if($stmt->execute()) {
                    // TODO: Gửi email chứa link reset password
                    // Link có dạng: /reset-password.php?token={token}
                    sendResponse(true, 'Vui lòng kiểm tra email để reset mật khẩu');
                }
                sendResponse(false, 'Không thể gửi email reset mật khẩu');
                break;

            case 'update-status':
                if(!$auth->isAdmin()) {
                    sendResponse(false, 'Không có quyền thực hiện');
                }

                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;
                $trangThai = $data['trangThai'] ?? 1;

                if(!$id) {
                    sendResponse(false, 'ID người dùng không hợp lệ');
                }

                $query = "UPDATE nguoidung SET TrangThai = ? WHERE Id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param('ii', $trangThai, $id);
                
                if($stmt->execute()) {
                    sendResponse(true, 'Cập nhật trạng thái thành công');
                }
                sendResponse(false, 'Cập nhật trạng thái thất bại');
                break;

            default:
                sendResponse(false, 'Action không hợp lệ');
        }
        break;

    default:
        sendResponse(false, 'Method không được hỗ trợ');
}
