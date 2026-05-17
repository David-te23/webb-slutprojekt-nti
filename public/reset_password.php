<?php
$pageTitle = "Reset Password - Quacker";
require_once __DIR__ . '/../includes/header.php';

$token = $_GET['token'] ?? '';
$isValid = false;

if ($token) {
    // Kontrollera om token finns och inte har gått ut
    $stmt = $dbconn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $isValid = true;
    }
}
?>

<div class="login-page-wrapper">
<h1 class="visually-hidden">Quacker - Reset password</h1>
    <div class="container d-flex justify-content-center align-items-center min-vh-70">
        <div class="login-card shadow-sm border-0 p-5">
            
            <?php if ($isValid): ?>
                <div class="text-center mb-4">
                    <h2 class="fw-bold">New Password</h2>
                    <p class="login-subtitle">Enter your new password below.</p>
                </div>

                <form action="actions/handle_reset.php" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="login-label">New Password:</label>
                        <input type="password" name="password" id="password" class="form-control login-input" required minlength="8">
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="login-label">Confirm New Password:</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control login-input" required minlength="8">
                    </div>

                    <button type="submit" class="btn btn-quack-large w-100 fw-bold shadow-sm mb-3">
                        Update Password
                    </button>
                </form>

            <?php else: ?>
                <div class="text-center">
                    <div class="alert alert-danger">
                        Invalid or expired link. Please request a new reset link.
                    </div>
                    <a href="forgot_password.php" class="login-link-bold">Try again</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
