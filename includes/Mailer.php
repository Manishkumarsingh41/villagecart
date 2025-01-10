<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mailer;
    
    public function __construct() {
        require 'vendor/autoload.php';
        
        $this->mailer = new PHPMailer(true);
        
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com'; // Change this to your SMTP host
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'your-email@gmail.com'; // Change to your email
        $this->mailer->Password = 'your-app-password'; // Change to your app password
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        
        // Default settings
        $this->mailer->isHTML(true);
        $this->mailer->setFrom('noreply@villagecart.com', 'VillageCart');
    }
    
    public function sendVerificationEmail($email, $token) {
        try {
            $verification_link = "http://localhost/villagecart/verify-email.php?token=" . $token;
            
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'Verify Your Email - VillageCart';
            $this->mailer->Body = $this->getVerificationEmailTemplate($verification_link);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendPasswordResetEmail($email, $token) {
        try {
            $reset_link = "http://localhost/villagecart/reset-password.php?token=" . $token;
            
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'Reset Your Password - VillageCart';
            $this->mailer->Body = $this->getPasswordResetEmailTemplate($reset_link);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendTwoFactorCode($email, $code) {
        try {
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'Your Two-Factor Authentication Code - VillageCart';
            $this->mailer->Body = $this->getTwoFactorEmailTemplate($code);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function getVerificationEmailTemplate($link) {
        return '
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #4CAF50; text-align: center;">Welcome to VillageCart!</h1>
            <p>Thank you for registering with VillageCart. Please click the button below to verify your email address:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $link . '" style="background-color: #4CAF50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px;">
                    Verify Email
                </a>
            </div>
            <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
            <p>' . $link . '</p>
            <p>This link will expire in 24 hours.</p>
        </div>';
    }
    
    private function getPasswordResetEmailTemplate($link) {
        return '
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #4CAF50; text-align: center;">Reset Your Password</h1>
            <p>We received a request to reset your password. Click the button below to create a new password:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $link . '" style="background-color: #4CAF50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px;">
                    Reset Password
                </a>
            </div>
            <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
            <p>' . $link . '</p>
            <p>This link will expire in 1 hour. If you didn\'t request this reset, you can safely ignore this email.</p>
        </div>';
    }
    
    private function getTwoFactorEmailTemplate($code) {
        return '
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #4CAF50; text-align: center;">Your Authentication Code</h1>
            <p>Here is your two-factor authentication code:</p>
            <div style="text-align: center; margin: 30px 0;">
                <div style="font-size: 32px; letter-spacing: 5px; font-weight: bold; color: #4CAF50;">' . $code . '</div>
            </div>
            <p>This code will expire in 10 minutes. Do not share this code with anyone.</p>
        </div>';
    }
}
?>
