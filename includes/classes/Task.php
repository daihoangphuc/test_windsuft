<?php
require_once __DIR__ . '/../../config/database.php';

class Task {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getStatusText($status) {
        switch ($status) {
            case 0:
                return 'Chưa bắt đầu';
            case 1:
                return 'Đang thực hiện';
            case 2:
                return 'Hoàn thành';
            case 3:
                return 'Quá hạn';
            default:
                return 'Không xác định';
        }
    }

    public function getStatusClass($status) {
        switch ($status) {
            case 0:
                return 'bg-gray-100 text-gray-800';
            case 1:
                return 'bg-blue-100 text-blue-800';
            case 2:
                return 'bg-green-100 text-green-800';
            case 3:
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
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

    public function getMyTasks($userId, $limit = 5, $offset = 0) {
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
                 LEFT JOIN nguoidung n ON pc.NguoiDungId = n.Id
                 WHERE pc.NguoiDungId = ?
                 ORDER BY nv.NgayKetThuc ASC
                 LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iii", $userId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countMyTasks($userId) {
        $query = "SELECT COUNT(*) as total
                 FROM nhiemvu nv 
                 JOIN phancongnhiemvu pc ON nv.Id = pc.NhiemVuId
                 WHERE pc.NguoiDungId = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
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

    public function delete($id) {
        // Kiểm tra xem có ai được phân công không
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM phancongnhiemvu WHERE NhiemVuId = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            return [
                'success' => false,
                'message' => "Không thể xóa nhiệm vụ này vì đã có {$count} người được phân công!"
            ];
        }

        // Nếu không có ràng buộc, tiến hành xóa
        $stmt = $this->db->prepare("DELETE FROM nhiemvu WHERE Id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Xóa nhiệm vụ thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa nhiệm vụ!'
            ];
        }
    }

    public function update($id, $data) {
        $query = "UPDATE nhiemvu SET 
                 TenNhiemVu = ?,
                 MoTa = ?,
                 NgayBatDau = ?,
                 NgayKetThuc = ?,
                 TrangThai = ?
                 WHERE Id = ?";
                 
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssssii", 
            $data['TenNhiemVu'],
            $data['MoTa'],
            $data['NgayBatDau'],
            $data['NgayKetThuc'],
            $data['TrangThai'],
            $id
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Cập nhật nhiệm vụ thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật nhiệm vụ!'
            ];
        }
    }

    public function create($data) {
        $query = "INSERT INTO nhiemvu (TenNhiemVu, MoTa, NgayBatDau, NgayKetThuc, TrangThai) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssssi", 
            $data['TenNhiemVu'],
            $data['MoTa'],
            $data['NgayBatDau'],
            $data['NgayKetThuc'],
            $data['TrangThai']
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Thêm nhiệm vụ mới thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm nhiệm vụ mới!'
            ];
        }
    }

    public function getAll($search = '', $limit = 10, $offset = 0) {
        $query = "SELECT nv.*, 
                        GROUP_CONCAT(DISTINCT n.HoTen) as NguoiDuocPhanCong,
                        GROUP_CONCAT(DISTINCT pc.NguoiDungId) as NguoiDungIds
                 FROM nhiemvu nv 
                 LEFT JOIN phancongnhiemvu pc ON nv.Id = pc.NhiemVuId
                 LEFT JOIN nguoidung n ON pc.NguoiDungId = n.Id
                 WHERE 1=1";
        
        if (!empty($search)) {
            $search = "%$search%";
            $query .= " AND (nv.TenNhiemVu LIKE ? OR nv.MoTa LIKE ?)";
        }
        
        $query .= " GROUP BY nv.Id ORDER BY nv.NgayTao DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($query);
        
        if (!empty($search)) {
            $stmt->bind_param("ssii", $search, $search, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalCount($search = '') {
        $query = "SELECT COUNT(*) as total FROM nhiemvu WHERE 1=1";
        if (!empty($search)) {
            $search = "%{$search}%";
            $query .= " AND (TenNhiemVu LIKE ? OR MoTa LIKE ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ss", $search, $search);
        } else {
            $stmt = $this->db->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }
}
