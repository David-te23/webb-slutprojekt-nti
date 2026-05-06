<?php 
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $display_name = $username;
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Kontrollera om lösenorden matchar
    if ($password !== $confirm_password) {
        $_SESSION['reg_error'] = "Passwords do not match.";
        header("Location: ../register.php");
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO users (username, display_name, email, password, is_admin)
                VALUES (:username, :display_name, :email, :password, :is_admin)";

        $stmt = $dbconn->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':display_name' => $display_name,
            ':email' => $email,
            ':password' => $hashed_password,
            ':is_admin' => 0
        ]);

        // Sätt success meddelande och skicka till login
        $_SESSION['reg_success'] = "Account created! You can now log in.";
        header("Location: ../login.php");
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['reg_error'] = "Username or email already exists.";
        } else {
            $_SESSION['reg_error'] = "An error occurred. Please try again.";
        }
        header("Location: ../register.php");
        exit;
    }   
}
?>
