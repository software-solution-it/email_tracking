<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../models/EmailAccount.php';

class EmailService {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function sendEmail($user_id, $recipientEmail, $subject, $htmlBody, $plainBody = '') {
        $smtpConfigModel = new EmailAccount($this->db);
        $smtpConfig = $smtpConfigModel->getByUserId($user_id);
    
        if (!$smtpConfig || !isset($smtpConfig['smtp_host'], $smtpConfig['smtp_username'], $smtpConfig['smtp_password'], $smtpConfig['smtp_port'])) {
            error_log("Configurações de SMTP não encontradas para o usuário: $user_id");
            return false; 
        }
    
        $mail = new PHPMailer(true);
    
        try {
            $mail->isSMTP();
            $mail->Host       = $smtpConfig['smtp_host']; 
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpConfig['smtp_username'];
            $mail->Password   = $smtpConfig['smtp_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port       = $smtpConfig['smtp_port'];  
    
            $mail->setFrom($smtpConfig['smtp_username'], 'Seu Nome'); 
            $mail->addAddress($recipientEmail); 
    

            $mail->isHTML(true);                               
            $mail->Subject = $subject;                  
            $mail->Body    = $htmlBody;                        
            $mail->AltBody = $plainBody ?: strip_tags($htmlBody);
    
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: " . $e->getMessage());
            return false;
        }
    }


    public function sendEmailWithTemplate($user_id, $email_account_id, $name, $recipientEmail, $subject, $htmlTemplate) {
        $query = "
            SELECT ea.email, ea.password, p.smtp_host, p.smtp_port, p.encryption
            FROM email_accounts ea
            INNER JOIN providers p ON ea.provider_id = p.id
            WHERE ea.user_id = :user_id AND ea.id = :email_account_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':email_account_id', $email_account_id);
        $stmt->execute();

        $smtpConfig = $stmt->fetch(PDO::FETCH_ASSOC);

        $encryptedPassword = EncryptionHelper::decrypt($smtpConfig['password']);

        if (!$smtpConfig || !isset($smtpConfig['smtp_host'], $smtpConfig['smtp_port'], $smtpConfig['email'], $encryptedPassword)) {
            error_log("Configurações de SMTP não encontradas para o user_id: $user_id e email_account_id: $email_account_id");
            return false; 
        }

        $mail = new PHPMailer(true);

        
        try {
            $mail->isSMTP();
            $mail->Host       = $smtpConfig['smtp_host']; 
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpConfig['email'];    
            $mail->Password   = $encryptedPassword;   
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port       = $smtpConfig['smtp_port'];  


            $mail->setFrom($smtpConfig['email'],  $name);  
            $mail->addAddress($recipientEmail);        

            $mail->isHTML(true);                               
            $mail->Subject = $subject;       
            $mail->Body    = $htmlTemplate;                      
            $mail->AltBody = strip_tags($htmlTemplate);        

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: " . $e->getMessage());
            return false;
        }
    }

    public function sendVerificationEmail($user_id, $email, $code) {
        $subject = 'Your Verification Code';
        $htmlBody = 'Your verification code is: <b>' . $code . '</b>';
        $plainBody = 'Your verification code is: ' . $code;

        return $this->sendEmail($user_id, $email, $subject, $htmlBody, $plainBody);
    }
}
