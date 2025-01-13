<?php
class Logger {
    private $db;

    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::getInstance()->getConnection();
    }

    public function log($userId, $action, $result, $details = '') {
        $ip = $this->getClientIP();
        $stmt = $this->db->prepare("INSERT INTO log (IP, NguoiDung, HanhDong, KetQua, ChiTiet) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $ip, $userId, $action, $result, $details);
        return $stmt->execute();
    }

    private function getClientIP() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    // Các phương thức truy vấn log
    public function getLogsByUser($userId, $limit = 10) {
        $stmt = $this->db->prepare("SELECT * FROM log WHERE NguoiDung = ? ORDER BY NgayTao DESC LIMIT ?");
        $stmt->bind_param("si", $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLogsByAction($action, $limit = 10) {
        $stmt = $this->db->prepare("SELECT * FROM log WHERE HanhDong LIKE ? ORDER BY NgayTao DESC LIMIT ?");
        $actionPattern = "%$action%";
        $stmt->bind_param("si", $actionPattern, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLogsByDateRange($startDate, $endDate) {
        $stmt = $this->db->prepare("SELECT * FROM log WHERE NgayTao BETWEEN ? AND ? ORDER BY NgayTao DESC");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
