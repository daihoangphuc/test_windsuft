<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($search = '', $limit = 10, $offset = 0) {
        $query = "SELECT n.*, c.TenChucVu, l.TenLop, k.TenKhoaTruong 
                 FROM nguoidung n 
                 LEFT JOIN chucvu c ON n.ChucVuId = c.Id 
                 LEFT JOIN lophoc l ON n.LopHocId = l.Id 
                 LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id";
                 
        if ($search) {
            $search_term = "%$search%";
            $query .= " WHERE n.HoTen LIKE ? OR n.Email LIKE ? OR n.MaSinhVien LIKE ?";
            $stmt = $this->db->prepare($query . " ORDER BY n.NgayTao DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("sssii", $search_term, $search_term, $search_term, $limit, $offset);
        } else {
            $stmt = $this->db->prepare($query . " ORDER BY n.NgayTao DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getTotalCount($search = '') {
        $query = "SELECT COUNT(*) as total FROM nguoidung n";
        
        if ($search) {
            $search_term = "%$search%";
            $query .= " WHERE n.HoTen LIKE ? OR n.Email LIKE ? OR n.MaSinhVien LIKE ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sss", $search_term, $search_term, $search_term);
        } else {
            $stmt = $this->db->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }
    
    public function add($data) {
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $query = "INSERT INTO nguoidung (TenDangNhap, MatKhauHash, HoTen, Email, MaSinhVien, VaiTroId, ChucVuId, LopHocId) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                 
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sssssiis", 
            $data['username'],
            $password_hash,
            $data['fullname'],
            $data['email'],
            $data['student_id'],
            $data['role'],
            $data['position'],
            $data['class']
        );
        
        return $stmt->execute();
    }
    
    public function update($id, $data) {
        $query = "UPDATE nguoidung SET 
                 HoTen = ?, 
                 Email = ?, 
                 MaSinhVien = ?,
                 ChucVuId = ?,
                 LopHocId = ?,
                 VaiTroId = ?
                 WHERE Id = ?";
                 
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sssiiis",
            $data['fullname'],
            $data['email'],
            $data['student_id'],
            $data['position'],
            $data['class'],
            $data['role'],
            $id
        );
        
        return $stmt->execute();
    }
    
    public function toggleStatus($id) {
        $query = "UPDATE nguoidung SET TrangThai = NOT TrangThai WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function changePassword($id, $new_password) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $query = "UPDATE nguoidung SET MatKhauHash = ? WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $password_hash, $id);
        return $stmt->execute();
    }
    
    public function getById($id) {
        $query = "SELECT * FROM nguoidung WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
