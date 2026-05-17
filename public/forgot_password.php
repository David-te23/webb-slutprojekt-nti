<?php
$pageTitle = "Reset Password - Quacker";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="login-page-wrapper">
<h1 class="visually-hidden">Quacker - Forgot password</h1>

    <div class="container d-flex justify-content-center align-items-center min-vh-70">
        <div class="login-card shadow-sm border-0 p-5">
            <div class="text-center mb-4">
                <h2 class="fw-bold">Forgot Password?</h2>
                <p class="login-subtitle">Enter your email to get a reset link.</p>
            </div>

            <!-- FELMEDDELANDE (t.ex. om mejlen inte hittas) -->
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-danger border-0 shadow-sm text-center mb-4">
                    <?= htmlspecialchars($_SESSION['login_error']); ?>
                    <?php unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>

            <!-- FRAMGÅNGSMEDDELANDE (när mejlet har skickats) -->
            <?php if (isset($_SESSION['reg_success'])): ?>
                <div class="alert alert-success border-0 shadow-sm text-center mb-4">
                    <?= htmlspecialchars($_SESSION['reg_success']); ?>
                    <?php unset($_SESSION['reg_success']); ?>
                </div>
            <?php endif; ?>

            <form action="actions/handle_forgot.php" method="POST">
                <div class="mb-4">
                    <label for="email" class="login-label">Email:</label>
                    <input type="email" name="email" id="email" class="form-control login-input" required>
                </div>

                <button type="submit" class="btn btn-quack-large w-100 fw-bold shadow-sm mb-3">
                    Send Link
                </button>
            </form>

            <div class="text-center mt-2">
                <a href="login.php" class="login-link-small">Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
