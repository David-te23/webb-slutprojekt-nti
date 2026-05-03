<?php
$pageTitle = "Login - Quacker";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="login-page-wrapper">
    <div class="container d-flex justify-content-center align-items-center min-vh-70">
        <div class="login-card shadow-sm border-0 p-5">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold">Welcome to Quacker!</h2>
                <p class="login-subtitle">Please login</p>
            </div>

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
                    <a href="#" class="login-link-small">Forgot your password?</a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
