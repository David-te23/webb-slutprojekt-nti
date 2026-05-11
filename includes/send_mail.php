<?php
require __DIR__ . '/../PHPMailer/Exception.php';
require __DIR__ . '/../PHPMailer/PHPMailer.php';
require __DIR__ . '/../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
/**
 * Send email via SMTP
 *
 * @param string|array $to          Recipient email (string) or multiple [['email','name'], ...]
 * @param string       $subject     Email subject
 * @param string       $body        HTML body
 * @param string       $altBody     Plain text version
 * @param array        $attachments File paths ['file1.pdf', 'img.jpg']
 *
 * @return array [success => bool, message => string]
 */
function sendMail($to, $subject, $body, $altBody = '', $attachments = [])
{
    $mail = new PHPMailer(true);

    try {
        /* ---------- SMTP CONFIG ---------- */
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';        
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ntimailsender@gmail.com';
        $mail->Password   = 'alhw mkng kwdn lgsf';     
        $mail->SMTPSecure = 'tls';                   // tls or ssl
        $mail->Port       = 587;                     // 587 (tls) or 465 (ssl)

        /* ---------- SENDER ---------- */
        $mail->setFrom('ntimailsender@gmail.com', 'Quacker');

        /* ---------- RECIPIENT(S) ---------- */
        if (is_array($to)) {
            foreach ($to as $recipient) {
                if (is_array($recipient)) {
                    $mail->addAddress($recipient[0], $recipient[1] ?? '');
                } else {
                    $mail->addAddress($recipient);
                }
            }
        } else {
            $mail->addAddress($to);
        }

        /* ---------- ATTACHMENTS ---------- */
        foreach ($attachments as $file) {
            if (file_exists($file)) {
                $mail->addAttachment($file);
            }
        }

        /* ---------- CONTENT ---------- */
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}