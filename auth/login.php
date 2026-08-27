<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log In - AmarShop</title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .login-card {
            max-width: 420px;
            width: 100%;
            border: none;
            border-radius: 12px;
        }

        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }

        .input-group-text {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .btn-brand {
            background-color: #198754;
            border-color: #198754;
            color: #fff;
            padding: 10px;
            font-weight: 600;
        }

        .btn-brand:hover {
            background-color: #157347;
            border-color: #157347;
            color: #fff;
        }

        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }
    </style>
</head>

<body>

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-5">
        <div class="card login-card shadow-lg p-3 p-md-4 bg-white">
            <div class="card-body">
                
                <!-- হেডার ও লোগো -->
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark mb-1">Welcome Back!</h3>
                    <p class="text-muted small">Please enter your credentials to log in.</p>
                </div>

                <!-- Session Alert Messages (Success, Warning, Danger) -->
                <?php if (isset($_SESSION['status']) && isset($_SESSION['message'])): ?>
                    <?php 
                        $alert_type = 'danger';
                        if ($_SESSION['status'] === 'success') {
                            $alert_type = 'success';
                        } elseif ($_SESSION['status'] === 'warning') {
                            $alert_type = 'warning';
                        }
                    ?>
                    <div class="alert alert-<?= $alert_type; ?> alert-dismissible fade show small py-2 px-3 mb-3" role="alert">
                        <?= htmlspecialchars($_SESSION['message']); ?>
                        <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php 
                        unset($_SESSION['status']);
                        unset($_SESSION['message']);
                    ?>
                <?php endif; ?>

                <!-- লগইন ফর্ম -->
                <form action="../core/login_process.php" method="POST">
                    
                    <!-- Phone or Email -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Phone or Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="login_identity" class="form-control" placeholder="017XXXXXXXX or ex@gmail.com" required>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye-slash" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                            <label class="form-check-label text-secondary small" for="rememberMe">
                                Remember me
                            </label>
                        </div>
                        <a href="forgot_password.php" class="text-success small fw-semibold text-decoration-none">Forgot Password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-brand w-100 rounded-2 mb-3">
                        Log In
                    </button>

                </form>

                <!-- সাইনআপ লিঙ্ক -->
                <div class="text-center mt-3">
                    <p class="text-secondary small mb-0">Don't have an account? 
                        <a href="signup.php" class="text-success fw-bold text-decoration-none">Sign Up</a>
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript (পাসওয়ার্ড হাইড/শো ফিচার) -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                eyeIcon.classList.toggle('bi-eye');
                eyeIcon.classList.toggle('bi-eye-slash');
            });
        });
    </script>
</body>

</html>