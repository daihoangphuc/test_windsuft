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
        
        $query = "INSERT INTO nguoidung (TenDangNhap, MatKhauHash, HoTen, Email, MaSinhVien, 
                                       GioiTinh, NgaySinh, anhdaidien, VaiTroId, ChucVuId, 
                                       LopHocId, TrangThai) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                 
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sssssissiiis", 
            $data['username'],
            $password_hash,
            $data['fullname'],
            $data['email'],
            $data['student_id'],
            $data['gender'],
            $data['birthdate'],
            $data['avatar'],
            $data['role'],
            $data['position'],
            $data['class'],
            $data['status']
        );
        
        return $stmt->execute();
    }
    
    public function update($id, $data) {
        $fields = [];
        $types = "";
        $params = [];
        
        // Xây dựng câu query dựa trên dữ liệu được cung cấp
        if (isset($data['username'])) {
            $fields[] = "TenDangNhap = ?";
            $types .= "s";
            $params[] = $data['username'];
        }
        
        if (isset($data['fullname'])) {
            $fields[] = "HoTen = ?";
            $types .= "s";
            $params[] = $data['fullname'];
        }
        
        if (isset($data['email'])) {
            $fields[] = "Email = ?";
            $types .= "s";
            $params[] = $data['email'];
        }
        
        if (isset($data['student_id'])) {
            $fields[] = "MaSinhVien = ?";
            $types .= "s";
            $params[] = $data['student_id'];
        }
        
        if (isset($data['gender'])) {
            $fields[] = "GioiTinh = ?";
            $types .= "i";
            $params[] = $data['gender'];
        }
        
        if (isset($data['birthdate'])) {
            $fields[] = "NgaySinh = ?";
            $types .= "s";
            $params[] = $data['birthdate'];
        }
        
        if (isset($data['role'])) {
            $fields[] = "VaiTroId = ?";
            $types .= "i";
            $params[] = $data['role'];
        }
        
        if (isset($data['position'])) {
            $fields[] = "ChucVuId = ?";
            $types .= "i";
            $params[] = $data['position'];
        }
        
        if (isset($data['class'])) {
            $fields[] = "LopHocId = ?";
            $types .= "i";
            $params[] = $data['class'];
        }
        
        if (isset($data['status'])) {
            $fields[] = "TrangThai = ?";
            $types .= "i";
            $params[] = $data['status'];
        }
        
        if (!empty($data['password'])) {
            $fields[] = "MatKhauHash = ?";
            $types .= "s";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($data['avatar'])) {
            $fields[] = "anhdaidien = ?";
            $types .= "s";
            $params[] = $data['avatar'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $query = "UPDATE nguoidung SET " . implode(", ", $fields) . " WHERE Id = ?";
        $types .= "i";
        $params[] = $id;
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
    
    public function updateStatus($id, $status) {
        $query = "UPDATE nguoidung SET TrangThai = ? WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $status, $id);
        return $stmt->execute();
    }
    
    public function getById($id) {
        $query = "SELECT n.*, c.TenChucVu, l.TenLop, k.TenKhoaTruong 
                 FROM nguoidung n 
                 LEFT JOIN chucvu c ON n.ChucVuId = c.Id 
                 LEFT JOIN lophoc l ON n.LopHocId = l.Id 
                 LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id 
                 WHERE n.Id = ?";
                 
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM nguoidung WHERE Id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function toggleStatus($id) {
        $query = "UPDATE nguoidung SET TrangThai = CASE WHEN TrangThai = 1 THEN 0 ELSE 1 END WHERE Id = ?";
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
}
