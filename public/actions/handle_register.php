<?php 
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $display_name = $username;
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Kontrollera att alla fält är ifyllda (utöver HTML-valideringen)
    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['reg_error'] = "All fields are required.";
        header("Location: ../register.php");
        exit;
    }

    // Kontrollera lösenordets längd (minst 8 tecken)
    if (strlen($password) < 8) {
        $_SESSION['reg_error'] = "Password must be at least 8 characters long.";
        header("Location: ../register.php");
        exit;
    }

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
        // Code 23000 betyder "Integrity constraint violation" (oftast UNIQUE-krock)
        if ($e->getCode() == 23000) {
            $_SESSION['reg_error'] = "Username or email already exists.";
        } else {
            $_SESSION['reg_error'] = "An error occurred. Please try again.";
        }
        header("Location: ../register.php");
        exit;
    }   
} else {
    header("Location: ../register.php");
    exit;
}
