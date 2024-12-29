<?php
class Faculty {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM khoatruong WHERE 1=1";
        if (!empty($search)) {
            $sql .= " AND TenKhoaTruong LIKE ?";
        }
        $sql .= " ORDER BY Id DESC LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        
        if (!empty($search)) {
            $stmt->bind_param("sii", $searchParam, $limit, $offset);
            $searchParam = "%$search%";
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalRecords($search = '') {
        $sql = "SELECT COUNT(*) as total FROM khoatruong";
        if (!empty($search)) {
            $sql .= " WHERE TenKhoaTruong LIKE ?";
            $stmt = $this->conn->prepare($sql);
            $searchParam = "%$search%";
            $stmt->bind_param("s", $searchParam);
        } else {
            $stmt = $this->conn->prepare($sql);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM khoatruong WHERE Id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function create($tenKhoaTruong) {
        $stmt = $this->conn->prepare("INSERT INTO khoatruong (TenKhoaTruong) VALUES (?)");
        $stmt->bind_param("s", $tenKhoaTruong);
        return $stmt->execute();
    }

    public function update($id, $tenKhoaTruong) {
        $stmt = $this->conn->prepare("UPDATE khoatruong SET TenKhoaTruong = ? WHERE Id = ?");
        $stmt->bind_param("si", $tenKhoaTruong, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        // Kiểm tra xem có lớp học nào thuộc khoa/trường này không
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM lophoc WHERE KhoaTruongId = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];

        if ($count > 0) {
            return false; // Không thể xóa vì có lớp học liên quan
        }

        $stmt = $this->conn->prepare("DELETE FROM khoatruong WHERE Id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function exportToExcel() {
        $sql = "SELECT Id, TenKhoaTruong, DATE_FORMAT(NgayTao, '%d/%m/%Y') as NgayTao FROM khoatruong ORDER BY Id DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
