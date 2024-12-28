<?php
class Position {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll() {
        $query = "SELECT * FROM chucvu ORDER BY NgayTao DESC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function add($name) {
        $query = "INSERT INTO chucvu (TenChucVu) VALUES (?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $name);
        return $stmt->execute();
    }
    
    public function update($id, $name) {
        $query = "UPDATE chucvu SET TenChucVu = ? WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $name, $id);
        return $stmt->execute();
    }
    
    public function delete($id) {
        // Kiểm tra xem có người dùng nào đang giữ chức vụ này không
        $check_query = "SELECT COUNT(*) as count FROM nguoidung WHERE ChucVuId = ?";
        $stmt = $this->db->prepare($check_query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return false;
        }
        
        $query = "DELETE FROM chucvu WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getById($id) {
        $query = "SELECT * FROM chucvu WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
