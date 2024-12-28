<?php
require_once __DIR__ . '/../../config/database.php';

class News {
    private $db;
    private $uploadDir = '../../uploads/news/';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Tạo thư mục uploads nếu chưa tồn tại
        if (!file_exists(__DIR__ . '/../../uploads')) {
            mkdir(__DIR__ . '/../../uploads', 0777, true);
        }
        
        // Tạo thư mục uploads/news nếu chưa tồn tại
        if (!file_exists(__DIR__ . '/../../uploads/news')) {
            mkdir(__DIR__ . '/../../uploads/news', 0777, true);
        }
    }

    public function add($data, $file = null) {
        $filePath = '';
        if ($file && $file['error'] == 0) {
            $fileName = time() . '_' . basename($file['name']);
            $targetFile = __DIR__ . '/' . $this->uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $filePath = 'uploads/news/' . $fileName;
            }
        }

        $query = "INSERT INTO tintuc (TieuDe, NoiDung, FileDinhKem, NgayTao, NguoiTaoId) 
                  VALUES (?, ?, ?, NOW(), ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sssi", 
            $data['TieuDe'],
            $data['NoiDung'],
            $filePath,
            $data['NguoiTaoId']
        );
        
        return $stmt->execute();
    }

    public function update($id, $data, $file = null) {
        $currentNews = $this->get($id);
        $filePath = $currentNews['FileDinhKem'];

        if ($file && $file['error'] == 0) {
            // Xóa file cũ nếu tồn tại
            if ($currentNews['FileDinhKem'] && file_exists(__DIR__ . '/../../' . $currentNews['FileDinhKem'])) {
                unlink(__DIR__ . '/../../' . $currentNews['FileDinhKem']);
            }

            // Upload file mới
            $fileName = time() . '_' . basename($file['name']);
            $targetFile = __DIR__ . '/' . $this->uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $filePath = 'uploads/news/' . $fileName;
            }
        }

        $query = "UPDATE tintuc 
                 SET TieuDe = ?, NoiDung = ?, FileDinhKem = ?
                 WHERE Id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sssi", 
            $data['TieuDe'],
            $data['NoiDung'],
            $filePath,
            $id
        );
        
        return $stmt->execute();
    }

    public function delete($id) {
        // Xóa file đính kèm trước
        $news = $this->get($id);
        if ($news['FileDinhKem'] && file_exists(__DIR__ . '/../../' . $news['FileDinhKem'])) {
            unlink(__DIR__ . '/../../' . $news['FileDinhKem']);
        }

        // Xóa bản ghi trong database
        $query = "DELETE FROM tintuc WHERE Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function get($id) {
        $query = "SELECT t.*, n.HoTen as NguoiDang 
                 FROM tintuc t 
                 LEFT JOIN nguoidung n ON t.NguoiTaoId = n.Id 
                 WHERE t.Id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getAll($search = '', $limit = 10, $offset = 0) {
        $query = "SELECT t.*, n.HoTen as NguoiDang 
                 FROM tintuc t 
                 LEFT JOIN nguoidung n ON t.NguoiTaoId = n.Id";
        
        if ($search) {
            $search_term = "%$search%";
            $query .= " WHERE t.TieuDe LIKE ? OR t.NoiDung LIKE ?";
            $stmt = $this->db->prepare($query . " ORDER BY t.NgayTao DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("ssii", $search_term, $search_term, $limit, $offset);
        } else {
            $stmt = $this->db->prepare($query . " ORDER BY t.NgayTao DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalCount($search = '') {
        $query = "SELECT COUNT(*) as total FROM tintuc";
        
        if ($search) {
            $search_term = "%$search%";
            $query .= " WHERE TieuDe LIKE ? OR NoiDung LIKE ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ss", $search_term, $search_term);
        } else {
            $stmt = $this->db->prepare($query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }

    public function getRelated($id, $limit = 3) {
        $query = "SELECT t.*, n.HoTen as NguoiDang 
                 FROM tintuc t 
                 LEFT JOIN nguoidung n ON t.NguoiTaoId = n.Id 
                 WHERE t.Id != ? 
                 ORDER BY t.NgayTao DESC 
                 LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
