<?php
require_once __DIR__ . '/../../config/database.php';

class Task {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function get($id) {
        $query = "SELECT nv.*, n.HoTen as NguoiPhanCong 
                 FROM nhiemvu nv 
                 LEFT JOIN phancongnhiemvu pc ON nv.Id = pc.NhiemVuId
                 LEFT JOIN nguoidung n ON pc.NguoiPhanCong = n.TenDangNhap
                 WHERE nv.Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getMyTasks($userId) {
        $query = "SELECT 
                    nv.*,
                    pc.NgayPhanCong,
                    n.HoTen as NguoiPhanCong,
                    CASE 
                        WHEN nv.TrangThai = 0 THEN 'Chưa bắt đầu'
                        WHEN nv.TrangThai = 1 THEN 'Đang thực hiện'
                        WHEN nv.TrangThai = 2 THEN 'Hoàn thành'
                        WHEN nv.TrangThai = 3 THEN 'Quá hạn'
                    END as TrangThaiText
                 FROM nhiemvu nv 
                 JOIN phancongnhiemvu pc ON nv.Id = pc.NhiemVuId
                 LEFT JOIN nguoidung n ON pc.NguoiPhanCong = n.Id
                 WHERE pc.NguoiDungId = ?
                 ORDER BY nv.NgayKetThuc ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTaskDetail($taskId, $userId) {
        $query = "SELECT nv.*, 
                    nv.TrangThai,
                    pc.NgayPhanCong,
                    pc.NgayPhanCong,
                    nv.MoTa as MoTaPhanCong,
                    n.HoTen as NguoiPhanCong,
                    CASE 
                        WHEN nv.TrangThai = 0 THEN 'Chưa bắt đầu'
                        WHEN nv.TrangThai = 1 THEN 'Đang thực hiện'
                        WHEN nv.TrangThai = 2 THEN 'Đã hoàn thành'
                        WHEN nv.TrangThai = 3 THEN 'Quá hạn'
                    END as TrangThaiText
                 FROM nhiemvu nv 
                 JOIN phancongnhiemvu pc ON nv.Id = pc.NhiemVuId
                 LEFT JOIN nguoidung n ON pc.NguoiPhanCong = n.Id
                 WHERE nv.Id = ? AND pc.NguoiDungId = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $taskId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateTaskStatus($taskId, $userId, $status) {
        $query = "UPDATE phancongnhiemvu 
                 SET TrangThai = ?, 
                     NgayPhanCong = CASE WHEN ? = 1 THEN NOW() ELSE NULL END
                 WHERE NhiemVuId = ? AND NguoiDungId = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iiii", $status, $status, $taskId, $userId);
        return $stmt->execute();
    }

    public function updateStatus($taskId, $userId, $status) {
        // Verify user is assigned to this task
        $query = "SELECT 1 FROM phancongnhiemvu WHERE NhiemVuId = ? AND NguoiDungId = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $taskId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            return false;
        }

        // Update task status
        $query = "UPDATE nhiemvu SET TrangThai = ? WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $status, $taskId);
        return $stmt->execute();
    }

    public function updateOverdueTasks() {
        $query = "UPDATE nhiemvu 
                 SET TrangThai = 3 
                 WHERE TrangThai != 2 
                 AND NgayKetThuc < NOW()";
        return $this->db->query($query);
    }

    public function getTaskStatistics($userId = null) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN TrangThai = 0 THEN 1 ELSE 0 END) as notStarted,
                    SUM(CASE WHEN TrangThai = 1 THEN 1 ELSE 0 END) as inProgress,
                    SUM(CASE WHEN TrangThai = 2 THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN TrangThai = 3 THEN 1 ELSE 0 END) as overdue
                 FROM nhiemvu nv";
        
        if ($userId) {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN nv.TrangThai = 0 THEN 1 ELSE 0 END) as notStarted,
                        SUM(CASE WHEN nv.TrangThai = 1 THEN 1 ELSE 0 END) as inProgress,
                        SUM(CASE WHEN nv.TrangThai = 2 THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN nv.TrangThai = 3 THEN 1 ELSE 0 END) as overdue
                     FROM nhiemvu nv
                     JOIN phancongnhiemvu pc ON nv.Id = pc.NhiemVuId
                     WHERE pc.NguoiDungId = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
        }
        
        return $stmt->get_result()->fetch_assoc();
    }
}
