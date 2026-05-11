<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../includes/send_mail.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Kolla om mejladressen finns i databasen
    $stmt = $dbconn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Skapa en säker token och ett utgångsdatum (1 timme)
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Spara token i databasen
        $update = $dbconn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $update->execute([$token, $expires, $email]);

        // Lokal URL till reset-sidan
        $resetLink = "http://localhost/Webbkurser/WEUWEB02/slutprojekt-David-te23/public/reset_password.php?token=$token";
        
        $subject = "Reset your Quacker password";
        $body = "
            <h2>Hi {$user['username']}!</h2>
            <p>You requested a password reset for your Quacker account.</p>
            <p>Click the link below to set a new password:</p>
            <p><a href='$resetLink' style='background: #46704B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
            <br>
            <p>This link will expire in 1 hour.</p>
        ";

        // Skicka mejlet
        $result = sendMail($email, $subject, $body);

        if ($result['success']) {
            $_SESSION['reg_success'] = "A reset link has been sent to your email!";
        } else {
            // Om något går fel med SMTP-servern
            $_SESSION['login_error'] = "Mail error: " . $result['message'];
        }
    } else {
        $_SESSION['login_error'] = "No account found with that email.";
    }

    header("Location: ../forgot_password.php");
    exit;
} else {
    header("Location: ../forgot_password.php");
    exit;
}
