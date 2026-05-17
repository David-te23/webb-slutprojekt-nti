<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Kontrollera lösenordets längd
    if (strlen($newPassword) < 8) {
        $_SESSION['login_error'] = "Password must be at least 8 characters.";
        header("Location: ../reset_password.php?token=" . urlencode($token));
        exit;
    }

    // Kontrollera matchning
    if ($newPassword !== $confirmPassword) {
        $_SESSION['login_error'] = "The passwords do not match.";
        header("Location: ../reset_password.php?token=" . urlencode($token));
        exit;
    }

    // Dubbelkolla token och utgångsdatum
    $stmt = $dbconn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $update = $dbconn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->execute([$hashedPassword, $user['id']]);

        $_SESSION['reg_success'] = "Success! Your password has been updated.";
        header("Location: ../login.php");
        exit;
    } else {
        $_SESSION['login_error'] = "Invalid or expired link.";
        header("Location: ../forgot_password.php");
        exit;
    }
}
