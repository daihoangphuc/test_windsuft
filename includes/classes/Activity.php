<?php
require_once __DIR__ . '/../../config/database.php';

class Activity {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function add($data) {
        // Tự động xác định trạng thái dựa trên ngày
        $now = new DateTime();
        $start = new DateTime($data['NgayBatDau']);
        $end = new DateTime($data['NgayKetThuc']);
        
        if ($now < $start) {
            $data['TrangThai'] = 0; // Sắp diễn ra
        } elseif ($now >= $start && $now <= $end) {
            $data['TrangThai'] = 1; // Đang diễn ra
        } else {
            $data['TrangThai'] = 2; // Đã kết thúc
        }

        $query = "INSERT INTO hoatdong (TenHoatDong, MoTa, NgayBatDau, NgayKetThuc, DiaDiem, ToaDo, SoLuong, TrangThai, NguoiTaoId) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssssssiis", 
            $data['TenHoatDong'],
            $data['MoTa'],
            $data['NgayBatDau'],
            $data['NgayKetThuc'],
            $data['DiaDiem'],
            $data['ToaDo'],
            $data['SoLuong'],
            $data['TrangThai'],
            $data['NguoiTaoId']
        );
        
        return $stmt->execute();
    }

    public function update($id, $data) {
        // Tự động xác định trạng thái dựa trên ngày nếu không có override
        if (!isset($data['TrangThai'])) {
            $now = new DateTime();
            $start = new DateTime($data['NgayBatDau']);
            $end = new DateTime($data['NgayKetThuc']);
            
            if ($now < $start) {
                $data['TrangThai'] = 0; // Sắp diễn ra
            } elseif ($now >= $start && $now <= $end) {
                $data['TrangThai'] = 1; // Đang diễn ra
            } else {
                $data['TrangThai'] = 2; // Đã kết thúc
            }
        }

        $query = "UPDATE hoatdong 
                 SET TenHoatDong = ?, MoTa = ?, NgayBatDau = ?, NgayKetThuc = ?, 
                     DiaDiem = ?, ToaDo = ?, SoLuong = ?, TrangThai = ? 
                 WHERE Id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssssssiii", 
            $data['TenHoatDong'],
            $data['MoTa'],
            $data['NgayBatDau'],
            $data['NgayKetThuc'],
            $data['DiaDiem'],
            $data['ToaDo'],
            $data['SoLuong'],
            $data['TrangThai'],
            $id
        );
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM hoatdong WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function get($id) {
        $query = "SELECT h.*, n.HoTen as NguoiTao 
                 FROM hoatdong h 
                 LEFT JOIN nguoidung n ON h.NguoiTaoId = n.Id 
                 WHERE h.Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getAll($search = '', $limit = 10, $offset = 0) {
        // Tự động cập nhật trạng thái của tất cả hoạt động
        $this->updateAllStatus();

        $query = "SELECT h.*, n.HoTen as NguoiTao,
                  COUNT(DISTINCT dk.Id) as SoLuongDangKy,
                  COUNT(DISTINCT dt.Id) as SoLuongThamGia
                  FROM hoatdong h 
                  LEFT JOIN nguoidung n ON h.NguoiTaoId = n.Id
                  LEFT JOIN danhsachdangky dk ON dk.HoatDongId = h.Id AND dk.TrangThai = 1
                  LEFT JOIN danhsachthamgia dt ON dt.HoatDongId = h.Id";
        
        if ($search) {
            $search_term = "%$search%";
            $query .= " WHERE h.TenHoatDong LIKE ? OR h.MoTa LIKE ? OR h.DiaDiem LIKE ?";
            $stmt = $this->db->prepare($query . " GROUP BY h.Id ORDER BY h.NgayTao DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("sssii", $search_term, $search_term, $search_term, $limit, $offset);
        } else {
            $stmt = $this->db->prepare($query . " GROUP BY h.Id ORDER BY h.NgayTao DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalCount($search = '') {
        $query = "SELECT COUNT(*) as total FROM hoatdong";
        
        if ($search) {
            $search_term = "%$search%";
            $query .= " WHERE TenHoatDong LIKE ? OR MoTa LIKE ? OR DiaDiem LIKE ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sss", $search_term, $search_term, $search_term);
        } else {
            $stmt = $this->db->prepare($query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }

    private function updateAllStatus() {
        $query = "UPDATE hoatdong 
                 SET TrangThai = CASE 
                     WHEN NOW() < NgayBatDau THEN 0
                     WHEN NOW() >= NgayBatDau AND NOW() <= NgayKetThuc THEN 1
                     ELSE 2
                 END";
        $this->db->query($query);
    }

    public function updateStatus($activityId) {
        $now = new DateTime();
        $query = "SELECT NgayBatDau, NgayKetThuc FROM hoatdong WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $activityId);
        $stmt->execute();
        $result = $stmt->get_result();
        $activity = $result->fetch_assoc();

        if ($activity) {
            $start = new DateTime($activity['NgayBatDau']);
            $end = new DateTime($activity['NgayKetThuc']);
            
            if ($now < $start) {
                $status = 0; // Sắp diễn ra
            } elseif ($now >= $start && $now <= $end) {
                $status = 1; // Đang diễn ra
            } else {
                $status = 2; // Đã kết thúc
            }

            $updateQuery = "UPDATE hoatdong SET TrangThai = ? WHERE Id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bind_param("ii", $status, $activityId);
            return $updateStmt->execute();
        }
        return false;
    }

    public function getRemainingSlots($activityId) {
        $query = "SELECT h.SoLuong, COUNT(dk.Id) as registered 
                 FROM hoatdong h 
                 LEFT JOIN danhsachdangky dk ON dk.HoatDongId = h.Id AND dk.TrangThai = 1
                 WHERE h.Id = ?
                 GROUP BY h.Id";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $activityId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if ($data) {
            return $data['SoLuong'] - $data['registered'];
        }
        return 0;
    }

    public function getStatusText($status) {
        switch ($status) {
            case 0:
                return 'Sắp diễn ra';
            case 1:
                return 'Đang diễn ra';
            case 2:
                return 'Đã kết thúc';
            default:
                return 'Không xác định';
        }
    }

    public function getStatusClass($status) {
        switch ($status) {
            case 0:
                return 'bg-blue-100 text-blue-800';
            case 1:
                return 'bg-green-100 text-green-800';
            case 2:
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }
}
