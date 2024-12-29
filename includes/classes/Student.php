<?php
class Student {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getStudentsByClass($classId, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT nd.*, l.TenLop, k.TenKhoaTruong, cv.TenChucVu
                FROM nguoidung nd 
                LEFT JOIN lophoc l ON nd.LopHocId = l.Id 
                LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id
                LEFT JOIN chucvu cv ON nd.ChucVuId = cv.Id
                WHERE nd.LopHocId = ? AND nd.VaiTroId = 2";
                
        if (!empty($search)) {
            $sql .= " AND (nd.HoTen LIKE ? OR nd.MaSinhVien LIKE ?)";
        }
        
        $sql .= " ORDER BY nd.Id DESC LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        
        if (!empty($search)) {
            $searchParam = "%$search%";
            $stmt->bind_param("issii", $classId, $searchParam, $searchParam, $limit, $offset);
        } else {
            $stmt->bind_param("iii", $classId, $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalStudentsInClass($classId, $search = '') {
        $sql = "SELECT COUNT(*) as total FROM nguoidung WHERE LopHocId = ? AND VaiTroId = 2";
        
        if (!empty($search)) {
            $sql .= " AND (HoTen LIKE ? OR MaSinhVien LIKE ?)";
            $stmt = $this->conn->prepare($sql);
            $searchParam = "%$search%";
            $stmt->bind_param("iss", $classId, $searchParam, $searchParam);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $classId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT nd.*, l.TenLop, k.TenKhoaTruong, cv.TenChucVu
                                    FROM nguoidung nd 
                                    LEFT JOIN lophoc l ON nd.LopHocId = l.Id 
                                    LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id 
                                    LEFT JOIN chucvu cv ON nd.ChucVuId = cv.Id
                                    WHERE nd.Id = ? AND nd.VaiTroId = 2");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function exportClassStudents($classId) {
        $sql = "SELECT nd.MaSinhVien, nd.HoTen, nd.Email, 
                       DATE_FORMAT(nd.NgaySinh, '%d/%m/%Y') as NgaySinh,
                       CASE nd.GioiTinh 
                           WHEN 1 THEN 'Nam' 
                           WHEN 0 THEN 'Nữ' 
                           ELSE 'Khác' 
                       END as GioiTinh,
                       cv.TenChucVu,
                       l.TenLop, k.TenKhoaTruong
                FROM nguoidung nd
                LEFT JOIN lophoc l ON nd.LopHocId = l.Id
                LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id
                LEFT JOIN chucvu cv ON nd.ChucVuId = cv.Id
                WHERE nd.LopHocId = ? AND nd.VaiTroId = 2
                ORDER BY nd.Id DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
