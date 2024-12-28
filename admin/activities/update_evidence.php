<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance()->getConnection();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activity_id']) && isset($_FILES['evidence'])) {
    $activityId = (int)$_POST['activity_id'];
    $file = $_FILES['evidence'];
    
    // Kiểm tra file
    $fileName = $file['name'];
    $fileType = $file['type'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];
    $fileSize = $file['size'];
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = array('pdf', 'doc', 'docx');
    
    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 10000000) { // 10MB max
                // Tạo thư mục nếu chưa tồn tại
                $uploadDir = __DIR__ . '/../../uploads/activities/minhchung/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Tạo tên file mới
                $fileNameNew = uniqid('evidence_', true) . '.' . $fileExt;
                $fileDestination = $uploadDir . $fileNameNew;
                
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    // Lấy đường dẫn cũ để xóa file
                    $stmt = $db->prepare("SELECT DuongDanMinhChung FROM hoatdong WHERE Id = ?");
                    $stmt->bind_param("i", $activityId);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    
                    if ($result && $result['DuongDanMinhChung']) {
                        $oldFile = __DIR__ . '/../../' . $result['DuongDanMinhChung'];
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    
                    // Cập nhật đường dẫn mới trong CSDL
                    $relativePath = 'uploads/activities/minhchung/' . $fileNameNew;
                    $stmt = $db->prepare("UPDATE hoatdong SET DuongDanMinhChung = ? WHERE Id = ?");
                    $stmt->bind_param("si", $relativePath, $activityId);
                    
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = "Cập nhật minh chứng thành công!";
                        $response['file_path'] = $relativePath;
                    } else {
                        $response['message'] = "Lỗi khi cập nhật CSDL!";
                    }
                } else {
                    $response['message'] = "Lỗi khi tải file lên!";
                }
            } else {
                $response['message'] = "File quá lớn! Vui lòng chọn file nhỏ hơn 10MB";
            }
        } else {
            $response['message'] = "Có lỗi xảy ra khi tải file!";
        }
    } else {
        $response['message'] = "Chỉ chấp nhận file PDF, DOC hoặc DOCX!";
    }
} else {
    $response['message'] = "Yêu cầu không hợp lệ!";
}

header('Content-Type: application/json');
echo json_encode($response);
