<?php
class ClassRoom {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($page = 1, $limit = 10, $search = '', $facultyId = null) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT l.*, k.TenKhoaTruong 
                FROM lophoc l 
                LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id 
                WHERE 1=1";
        
        if (!empty($search)) {
            $sql .= " AND (l.TenLop LIKE ? OR k.TenKhoaTruong LIKE ?)";
        }
        if ($facultyId) {
            $sql .= " AND l.KhoaTruongId = ?";
        }
        
        $sql .= " ORDER BY l.Id DESC LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        
        if (!empty($search) && $facultyId) {
            $searchParam = "%$search%";
            $stmt->bind_param("ssiii", $searchParam, $searchParam, $facultyId, $limit, $offset);
        } elseif (!empty($search)) {
            $searchParam = "%$search%";
            $stmt->bind_param("ssii", $searchParam, $searchParam, $limit, $offset);
        } elseif ($facultyId) {
            $stmt->bind_param("iii", $facultyId, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalRecords($search = '', $facultyId = null) {
        $sql = "SELECT COUNT(*) as total 
                FROM lophoc l 
                LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id 
                WHERE 1=1";
                
        if (!empty($search)) {
            $sql .= " AND (l.TenLop LIKE ? OR k.TenKhoaTruong LIKE ?)";
        }
        if ($facultyId) {
            $sql .= " AND l.KhoaTruongId = ?";
        }

        $stmt = $this->conn->prepare($sql);
        
        if (!empty($search) && $facultyId) {
            $searchParam = "%$search%";
            $stmt->bind_param("ssi", $searchParam, $searchParam, $facultyId);
        } elseif (!empty($search)) {
            $searchParam = "%$search%";
            $stmt->bind_param("ss", $searchParam, $searchParam);
        } elseif ($facultyId) {
            $stmt->bind_param("i", $facultyId);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT l.*, k.TenKhoaTruong 
                                    FROM lophoc l 
                                    LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id 
                                    WHERE l.Id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    private function checkDuplicateName($tenLop, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM lophoc WHERE TenLop = ?";
        $params = [$tenLop];
        $types = "s";

        if ($excludeId !== null) {
            $sql .= " AND Id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }

    public function create($tenLop, $khoaTruongId) {
        // Kiểm tra tên lớp trùng
        if ($this->checkDuplicateName($tenLop)) {
            $_SESSION['error'] = "Tên lớp '$tenLop' đã tồn tại!";
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO lophoc (TenLop, KhoaTruongId) VALUES (?, ?)");
        $stmt->bind_param("si", $tenLop, $khoaTruongId);
        $success = $stmt->execute();
        
        if ($success) {
            $_SESSION['success'] = "Thêm lớp thành công!";
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra khi thêm lớp!";
        }
        
        return $success;
    }

    public function update($id, $tenLop, $khoaTruongId) {
        // Kiểm tra tên lớp trùng, loại trừ ID hiện tại
        if ($this->checkDuplicateName($tenLop, $id)) {
            $_SESSION['error'] = "Tên lớp '$tenLop' đã tồn tại!";
            return false;
        }

        $stmt = $this->conn->prepare("UPDATE lophoc SET TenLop = ?, KhoaTruongId = ? WHERE Id = ?");
        $stmt->bind_param("sii", $tenLop, $khoaTruongId, $id);
        $success = $stmt->execute();
        
        if ($success) {
            $_SESSION['success'] = "Cập nhật lớp thành công!";
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra khi cập nhật lớp!";
        }
        
        return $success;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM lophoc WHERE Id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getStatsByFaculty() {
        $sql = "SELECT k.TenKhoaTruong, COUNT(l.Id) as SoLop
                FROM khoatruong k
                LEFT JOIN lophoc l ON k.Id = l.KhoaTruongId
                GROUP BY k.Id, k.TenKhoaTruong
                ORDER BY SoLop DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function exportToExcel() {
        $sql = "SELECT l.Id, l.TenLop, k.TenKhoaTruong, DATE_FORMAT(l.NgayTao, '%d/%m/%Y') as NgayTao 
                FROM lophoc l
                LEFT JOIN khoatruong k ON l.KhoaTruongId = k.Id
                ORDER BY l.Id DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
