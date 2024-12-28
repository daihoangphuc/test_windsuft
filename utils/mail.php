<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

class Mailer {
    private static $instance = null;
    private $mail;
    
    private function __construct() {
        $this->mail = new PHPMailer(true);
        
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com'; // TODO: Update with your SMTP host
        $this->mail->SMTPAuth = true;
        $this->mail->Username = ''; // TODO: Add your email
        $this->mail->Password = ''; // TODO: Add your app password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        $this->mail->CharSet = 'UTF-8';
        
        // Default settings
        $this->mail->isHTML(true);
        $this->mail->setFrom('your-email@example.com', 'CLB HSTV'); // TODO: Update with your email
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function sendPasswordReset($email, $token) {
        try {
            $reset_link = "http://localhost/test_windsuft/reset-password.php?token=" . $token;
            
            $this->mail->addAddress($email);
            $this->mail->Subject = 'Khôi phục mật khẩu - CLB HSTV';
            
            $this->mail->Body = "
                <h2>Yêu cầu khôi phục mật khẩu</h2>
                <p>Chúng tôi nhận được yêu cầu khôi phục mật khẩu cho tài khoản của bạn.</p>
                <p>Vui lòng click vào link bên dưới để đặt lại mật khẩu:</p>
                <p><a href='{$reset_link}'>{$reset_link}</a></p>
                <p>Link này sẽ hết hạn sau 1 giờ.</p>
                <p>Nếu bạn không yêu cầu khôi phục mật khẩu, vui lòng bỏ qua email này.</p>
            ";
            
            return $this->mail->send();
        } catch (Exception $e) {
            throw new Exception("Không thể gửi email: {$this->mail->ErrorInfo}");
        }
    }
    
    public function sendWelcome($email, $name) {
        try {
            $this->mail->addAddress($email);
            $this->mail->Subject = 'Chào mừng đến với CLB HSTV';
            
            $this->mail->Body = "
                <h2>Chào mừng {$name} đến với CLB HSTV!</h2>
                <p>Cảm ơn bạn đã tham gia câu lạc bộ của chúng tôi.</p>
                <p>Hãy cập nhật thông tin cá nhân của bạn và bắt đầu tham gia các hoạt động của CLB.</p>
            ";
            
            return $this->mail->send();
        } catch (Exception $e) {
            throw new Exception("Không thể gửi email: {$this->mail->ErrorInfo}");
        }
    }
}
