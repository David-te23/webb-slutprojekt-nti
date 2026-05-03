<?php
$pageTitle = "Register - Quacker";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="login-page-wrapper">
    <div class="container d-flex justify-content-center align-items-center min-vh-70">
        <div class="login-card shadow-sm border-0 p-5">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold">Join Quacker!</h2>
                <p class="login-subtitle">Create your account</p>
            </div>

            <form action="../actions/handle_register.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="login-label">Username:</label>
                    <input type="text" name="username" id="username" class="form-control login-input" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="login-label">Email:</label>
                    <input type="email" name="email" id="email" class="form-control login-input" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="login-label">Password:</label>
                    <input type="password" name="password" id="password" class="form-control login-input" required>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="login-label">Confirm Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control login-input" required>
                </div>

                <button type="submit" class="btn btn-quack-large w-100 fw-bold shadow-sm mb-3">
                    Register
                </button>
            </form>

            <div class="text-center mt-2">
                <p class="mb-0">Already have an account?</p>
                <a href="login.php" class="login-link-bold">Login here</a>
            </div>

        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
