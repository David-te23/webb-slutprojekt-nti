<?php
$pageTitle = "Login - Quacker";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="login-page-wrapper">
<h1 class="visually-hidden">Quacker - Login page</h1>

    <div class="container d-flex justify-content-center align-items-center min-vh-70">
        <div class="login-card shadow-sm border-0 p-5">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold">Welcome to Quacker!</h2>
                <p class="login-subtitle">Please login</p>
            </div>

            <!-- FELMEDDELANDE -->
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-danger border-0 shadow-sm text-center mb-4">
                    <?= htmlspecialchars($_SESSION['login_error']); ?>
                    <?php unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>

            <!-- FRAMGÅNGSMEDDELANDE (från registrering) -->
            <?php if (isset($_SESSION['reg_success'])): ?>
                <div class="alert alert-success border-0 shadow-sm text-center mb-4">
                    <?= htmlspecialchars($_SESSION['reg_success']); ?>
                    <?php unset($_SESSION['reg_success']); ?>
                </div>
            <?php endif; ?>

            <form action="actions/handle_login.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="login-label">Username:</label>
                    <input type="text" name="username" id="username" class="form-control login-input" required>
                </div>

                <div class="mb-4">
                    <label for="password" class="login-label">Password:</label>
                    <input type="password" name="password" id="password" class="form-control login-input" required>
                </div>

                <button type="submit" class="btn btn-quack-large w-100 fw-bold shadow-sm mb-3">
                    Login
                </button>
            </form>

            <div class="text-center mt-2">
                <p class="mb-0">Don't have an account?</p>
                <a href="register.php" class="login-link-bold">Register here</a>
                <div class="mt-3">
                    <a href="forgot_password.php" class="login-link-small">Forgot your password?</a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
