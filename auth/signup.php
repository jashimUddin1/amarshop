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
    <title>Create Account - AmarShop</title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .signup-card {
            max-width: 480px;
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
    </style>
</head>

<body>

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-3">
        <div class="card signup-card shadow-lg p-2 p-md-4 bg-white">
            <div class="card-body">

                <!-- হেডার ও লোগো -->
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark mb-1">Create Account</h3>
                    <p class="text-muted small">Join us today! Please enter your details.</p>
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
                    // মেসেজ একবার দেখানোর পর Session মুছে ফেলা হচ্ছে
                    unset($_SESSION['status']);
                    unset($_SESSION['message']);
                    ?>
                <?php endif; ?>

                <!-- রেজিস্ট্রেশন ফর্ম -->
                <form action="../core/signup_process.php" method="POST" id="signupForm">

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="customar_name" class="form-control" placeholder="Enter your full name" required>
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" name="phone" class="form-control" placeholder="017XXXXXXXX" required>
                        </div>
                    </div>

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="example@gmail.com" required>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye-slash" id="eyeIcon1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                <i class="bi bi-eye-slash" id="eyeIcon2"></i>
                            </button>
                        </div>
                        <div id="passwordError" class="text-danger small mt-1 d-none">
                            Passwords do not match!
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-brand w-100 rounded-2 mb-3">
                        Sign Up
                    </button>

                </form>

                <!-- লগইন লিঙ্ক -->
                <div class="text-center mt-3">
                    <p class="text-secondary small mb-0">Already have an account?
                        <a href="login.php" class="text-success fw-bold text-decoration-none">Log In</a>
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript (পাসওয়ার্ড ভিজিবিলিটি এবং ম্যাচ চেকিং) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ১. পাসওয়ার্ড হাইড/শো ফিচার
            function setupToggle(buttonId, inputId, iconId) {
                const button = document.getElementById(buttonId);
                const input = document.getElementById(inputId);
                const icon = document.getElementById(iconId);

                button.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    icon.classList.toggle('bi-eye');
                    icon.classList.toggle('bi-eye-slash');
                });
            }

            setupToggle('togglePassword', 'password', 'eyeIcon1');
            setupToggle('toggleConfirmPassword', 'confirmPassword', 'eyeIcon2');

            // ২. পাসওয়ার্ড মিলছে কিনা চেক করা (Form Submit Validation)
            const form = document.getElementById('signupForm');
            const pass = document.getElementById('password');
            const confirmPass = document.getElementById('confirmPassword');
            const errorDiv = document.getElementById('passwordError');

            form.addEventListener('submit', function(e) {
                if (pass.value !== confirmPass.value) {
                    e.preventDefault();
                    errorDiv.classList.remove('d-none');
                    confirmPass.classList.add('is-invalid');
                } else {
                    errorDiv.classList.add('d-none');
                    confirmPass.classList.remove('is-invalid');
                }
            });
        });
    </script>
</body>

</html>