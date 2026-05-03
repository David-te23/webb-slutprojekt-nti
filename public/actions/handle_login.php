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

        //kontrollera om användare finns och om lösenordet matchar den hashade versionen
        if ($user && password_verify($password, $user['password'])) {

            //logga in användaren genom att spara info i sessionen
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];

            header("Location: ../../public/index.php");
            exit;
        } else {
            $error = "Incorrect username or password.";
        }
    } catch (PDOException $e){
        $error = "An error occured: " . $e->getMessage();
    }
}
?>

<!-- enkel visning av error i html -->
<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>