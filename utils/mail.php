<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

class Mailer {
    private static $instance = null;
    private $mail;
    
    private function __construct() {
        $this->initMailer();
    }
    
    private function initMailer() {
        $this->mail = new PHPMailer(true);
        
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com'; 
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'clbhtsvtvu@gmail.com'; 
        $this->mail->Password = 'rkkarxwvyluwgsbe'; 
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        $this->mail->CharSet = 'UTF-8';
        
        // Disable SSL verification (chỉ dùng trong môi trường development)
        $this->mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Default settings
        $this->mail->isHTML(false); // Set to false for plain text
        $this->mail->setFrom('clbhtsvtvu@gmail.com', 'CLB HTSV TVU');
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function resetMailer() {
        // Clear all addresses and attachments
        $this->mail->clearAddresses();
        $this->mail->clearAttachments();
        $this->mail->clearCCs();
        $this->mail->clearBCCs();
        $this->mail->clearReplyTos();
        
        // Reset other properties
        $this->mail->Subject = '';
        $this->mail->Body = '';
    }
    
    public function sendPasswordReset($email, $token) {
        try {
            $this->resetMailer();
            $this->mail->addAddress($email);
            $this->mail->Subject = 'Khôi phục mật khẩu';
            $this->mail->Body = "Bạn đã yêu cầu khôi phục mật khẩu. Vui lòng click vào link sau để đặt lại mật khẩu: http://{$_SERVER['HTTP_HOST']}/manage-htsv/reset-password.php?token=" . $token;
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Error sending password reset email: " . $e->getMessage());
            return false;
        }
    }

    public function sendNewActivityNotification($email, $activityName, $startDate, $location) {
        try {
            $this->resetMailer();
            $this->mail->addAddress($email);
            $this->mail->Subject = 'Thông báo hoạt động mới';
            $this->mail->Body = "Xin chào,\n\n";
            $this->mail->Body .= "Một hoạt động mới vừa được tạo:\n\n";
            $this->mail->Body .= "Tên hoạt động: {$activityName}\n";
            $this->mail->Body .= "Thời gian bắt đầu: {$startDate}\n";
            $this->mail->Body .= "Địa điểm: {$location}\n\n";
            $this->mail->Body .= "Vui lòng đăng nhập vào hệ thống để xem chi tiết và đăng ký tham gia.\n";
            $this->mail->Body .= "http://{$_SERVER['HTTP_HOST']}/manage-htsv/activities/";
            
            $result = $this->mail->send();
            return $result;
        } catch (Exception $e) {
            error_log("Error sending activity notification email: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendWelcome($email, $name) {
        try {
            $this->resetMailer();
            $this->mail->isHTML(true);
            $this->mail->addAddress($email);
            $this->mail->Subject = 'Chào mừng đến với CLB HTSV';
            
            $this->mail->Body = "
                <h2>Chào mừng {$name} đến với CLB HTSV!</h2>
                <p>Cảm ơn bạn đã tham gia câu lạc bộ của chúng tôi.</p>
                <p>Hãy cập nhật thông tin cá nhân của bạn và bắt đầu tham gia các hoạt động của CLB.</p>
            ";
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Error sending welcome email: " . $e->getMessage());
            return false;
        }
    }
}
