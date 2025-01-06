<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/classes/Activity.php';

$auth = new Auth();
$auth->requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $activity = new Activity();
        
        // Validate required fields
        $requiredFields = ['TenHoatDong', 'NgayBatDau', 'NgayKetThuc', 'DiaDiem', 'SoLuong'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Trường {$field} không được để trống");
            }
        }
        
        $data = [
            'TenHoatDong' => $_POST['TenHoatDong'],
            'MoTa' => $_POST['MoTa'] ?? '',
            'NgayBatDau' => $_POST['NgayBatDau'],
            'NgayKetThuc' => $_POST['NgayKetThuc'],
            'DiaDiem' => $_POST['DiaDiem'],
            'ToaDo' => $_POST['ToaDo'] ?? '',
            'SoLuong' => intval($_POST['SoLuong']),
            'NguoiTaoId' => $_SESSION['user_id']
        ];

        $db = Database::getInstance()->getConnection();
        
        // Bắt đầu transaction để đảm bảo tính nhất quán
        $db->begin_transaction();
        
        try {
            if ($activity->add($data)) {
                // Lấy ID của hoạt động vừa thêm
                $activityId = $db->insert_id;
                
                // Gửi email thông báo cho các thành viên
                $query = "SELECT DISTINCT Email FROM nguoidung WHERE TrangThai = 1 AND VaiTroId = 2";
                $result = $db->query($query);
                
                if ($result && $result->num_rows > 0) {
                    require_once __DIR__ . '/../../utils/mail.php';
                    require_once __DIR__ . '/../../vendor/autoload.php'; // For PHPMailer
                    
                    $mailer = Mailer::getInstance();
                    $emailsSent = [];
                    
                    while ($row = $result->fetch_assoc()) {
                        $email = $row['Email'];
                        // Kiểm tra xem email đã được gửi chưa
                        if (!in_array($email, $emailsSent)) {
                            try {
                                $mailer->sendNewActivityNotification(
                                    $email,
                                    $data['TenHoatDong'],
                                    $data['NgayBatDau'],
                                    $data['DiaDiem']
                                );
                                $emailsSent[] = $email;
                            } catch (Exception $e) {
                                error_log("Không thể gửi email đến {$email}: " . $e->getMessage());
                                continue;
                            }
                        }
                    }
                }
                
                // Commit transaction nếu mọi thứ OK
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Thêm hoạt động thành công']);
            } else {
                throw new Exception("Không thể thêm hoạt động vào cơ sở dữ liệu");
            }
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $db->rollback();
            throw $e;
        }
    } catch (Exception $e) {
        error_log("Lỗi khi thêm hoạt động: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Phương thức không được hỗ trợ'
    ]);
}
