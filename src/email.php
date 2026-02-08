<?php
/**
 * Email Functions
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

/**
 * Send email via ProtonMail SMTP
 */
function send_email($to, $subject, $body, $reply_to = null) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)$_ENV['SMTP_PORT'];
        
        // Recipients
        $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($to);
        
        if ($reply_to) {
            $mail->addReplyTo($reply_to);
        }
        
        // Content
        $mail->isHTML(false); // Plain text
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        error_log('Email send failed: ' . $mail->ErrorInfo);
        return ['success' => false, 'error' => 'Failed to send email: ' . $mail->ErrorInfo];
    }
}

/**
 * Send contact form notification to admin
 */
function send_contact_notification($name, $email, $subject, $message) {
    $body = "New contact form submission:\n\n";
    $body .= "From: {$name} <{$email}>\n";
    $body .= "Subject: {$subject}\n\n";
    $body .= "Message:\n";
    $body .= str_repeat('-', 60) . "\n";
    $body .= $message . "\n";
    $body .= str_repeat('-', 60) . "\n\n";
    $body .= "Sent from: " . SITE_URL . "\n";
    $body .= "IP: " . get_client_ip() . "\n";
    $body .= "Time: " . date('Y-m-d H:i:s') . "\n";
    
    return send_email(
        $_ENV['SMTP_USER'], // Send to yourself
        "[" . SITE_NAME . "] Contact: {$subject}",
        $body,
        $email // Reply-to is the sender
    );
}

/**
 * Save contact message to database
 */
function save_contact_message($name, $email, $subject, $message, $user_id = null) {
    $db = get_db();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO contact_messages (name, email, subject, message, user_id, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $name,
            $email,
            $subject,
            $message,
            $user_id,
            get_client_ip()
        ]);
        
        return ['success' => true, 'id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        error_log('Save contact message failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to save message'];
    }
}
