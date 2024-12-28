<?php
class News {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getNewsDetail($id) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT t.*, nd.HoTen as NguoiTao 
            FROM tintuc t
            LEFT JOIN nguoidung nd ON t.NguoiTaoId = nd.Id
            WHERE t.Id = ? AND t.TrangThai = 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        // Cập nhật lượt xem
        $updateStmt = $this->db->prepare("UPDATE tintuc SET LuotXem = LuotXem + 1 WHERE Id = ?");
        $updateStmt->bind_param("i", $id);
        $updateStmt->execute();

        return $result->fetch_assoc();
    }

    public function getRelatedNews($id, $limit = 3) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT Id, TieuDe, MoTaNgan, AnhDaiDien, NgayTao
            FROM tintuc 
            WHERE Id != ? AND TrangThai = 1
            ORDER BY NgayTao DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
