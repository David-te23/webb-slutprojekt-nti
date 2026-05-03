<?php 
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $display_name = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

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

        header("Location: ../../public/login.php");
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            //echo "Username or email already exists.";
        } else {
            //echo "An error occured: " . $e->getMessage();
        }
    }   
}
?>