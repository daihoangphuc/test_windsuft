<?php
require_once __DIR__ . '/../../config/database.php';

class Activity {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function add($data) {
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
}
