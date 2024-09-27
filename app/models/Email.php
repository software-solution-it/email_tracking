<?php
class Email {
    private $conn;
    private $table = "emails";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function saveEmail(
        $email_account_id, 
        $email_id, 
        $subject, 
        $sender, 
        $recipient, 
        $body, 
        $headers, 
        $date_received, 
        $folder, 
        $message_id, // Novos campos extras, como o message_id
        $references, // Referências de e-mails anteriores
        $in_reply_to, // ID de e-mails aos quais este responde
        $content_type, // Tipo de conteúdo do e-mail
    ) {
        $query = "INSERT INTO " . $this->table . " 
                  (email_account_id, email_id, subject, sender, recipient, body, headers, date_received, folder, message_id,  `references`, in_reply_to, content_type) 
                  VALUES 
                  (:email_account_id, :email_id, :subject, :sender, :recipient, :body, :headers, :date_received, :folder, :message_id, :references, :in_reply_to, :content_type)";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email_account_id', $email_account_id);
        $stmt->bindParam(':email_id', $email_id);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':sender', $sender);
        $stmt->bindParam(':recipient', $recipient);
        $stmt->bindParam(':body', $body);
        $stmt->bindParam(':headers', $headers);
        $stmt->bindParam(':date_received', $date_received);
        $stmt->bindParam(':folder', $folder);
        $stmt->bindParam(':message_id', $message_id);
        $stmt->bindParam(':references', $references);
        $stmt->bindParam(':in_reply_to', $in_reply_to);
        $stmt->bindParam(':content_type', $content_type);
    
        return $stmt->execute();
    }

    public function emailExistsByMessageId($message_id) {
        $query = "SELECT COUNT(*) as count FROM emails WHERE message_id = :message_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':message_id', $message_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        return $result['count'] > 0;
    }

    public function getLastEmailDate() {
        $query = "SELECT MAX(date_received) as last_date FROM emails";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['last_date'] ?? null;
    }
    
    public function getEmailsByAccountId($email_account_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE email_account_id = :email_account_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email_account_id', $email_account_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
