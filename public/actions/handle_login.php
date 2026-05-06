<?php 
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $dbconn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kontrollera om användare finns och om lösenordet matchar
        if ($user && password_verify($password, $user['password'])) {

            // Spara info i sessionen
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];

            // Lyckad inloggning - skicka till index
            header("Location: ../index.php");
            exit;
        } else {
            // Felaktiga uppgifter - spara felet i sessionen
            $_SESSION['login_error'] = "Incorrect username or password.";
            header("Location: ../login.php");
            exit;
        }
    } catch (PDOException $e){
        $_SESSION['login_error'] = "A database error occurred. Please try again.";
        header("Location: ../login.php");
        exit;
    }
} else {
    header("Location: ../login.php");
    exit;
}
