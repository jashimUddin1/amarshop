<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$field_errors = $_SESSION['signup_errors'] ?? [];
$old_input = $_SESSION['signup_old'] ?? [];
unset($_SESSION['signup_errors'], $_SESSION['signup_old']);
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
                <div class="text-center mb-3">
                    <h3 class="fw-bold text-dark mb-1">Create Account</h3>
                    <p class="text-muted small mb-0">Join us today! Please enter your details.</p>
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
                        <?= $_SESSION['message']; ?>
                        <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php
                    unset($_SESSION['status']);
                    unset($_SESSION['message']);
                    ?>
                <?php endif; ?>

                <!-- রেজিস্ট্রেশন ফর্ম -->
                <form action="../core/signup_process.php" method="POST" id="signupForm" novalidate>

                    <!-- Full Name -->
                    <div class="mb-2">
                        <label class="form-label text-secondary small fw-bold">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="customar_name" class="form-control <?= isset($field_errors['customar_name']) ? 'is-invalid' : ''; ?>" placeholder="Enter your full name" value="<?= htmlspecialchars($old_input['customar_name'] ?? ''); ?>" required autofocus>
                        </div>
                        <div class="invalid-feedback <?= isset($field_errors['customar_name']) ? 'd-block' : ''; ?>" data-error-for="customar_name"><?= htmlspecialchars($field_errors['customar_name'] ?? 'Please enter your full name.'); ?></div>
                    </div>

                    <!-- Phone Number (Required) -->
                    <div class="mb-2">
                        <label class="form-label text-secondary small fw-bold">Phone Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" name="phone" class="form-control <?= isset($field_errors['phone']) ? 'is-invalid' : ''; ?>" placeholder="017XXXXXXXX" value="<?= htmlspecialchars($old_input['phone'] ?? ''); ?>" inputmode="tel" required>
                        </div>
                        <div class="invalid-feedback <?= isset($field_errors['phone']) ? 'd-block' : ''; ?>" data-error-for="phone">
                            <?= $field_errors['phone'] ?? 'Phone number must contain at least 8 digits.'; ?>
                        </div>
                    </div>

                    <!-- Email Address (Optional) -->
                    <div class="mb-2">
                        <label class="form-label text-secondary small fw-bold">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control <?= isset($field_errors['email']) ? 'is-invalid' : ''; ?>" placeholder="example@gmail.com (Optional)" value="<?= htmlspecialchars($old_input['email'] ?? ''); ?>">
                        </div>
                        <div class="invalid-feedback <?= isset($field_errors['email']) ? 'd-block' : ''; ?>" data-error-for="email"><?= htmlspecialchars($field_errors['email'] ?? 'Please enter a valid email address.'); ?></div>
                    </div>

                    <!-- Password -->
                    <div class="mb-2">
                        <label class="form-label text-secondary small fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control <?= isset($field_errors['password']) ? 'is-invalid' : ''; ?>" placeholder="••••••••" minlength="5" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye-slash" id="eyeIcon1"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback <?= isset($field_errors['password']) ? 'd-block' : ''; ?>" data-error-for="password"><?= htmlspecialchars($field_errors['password'] ?? 'Password must be at least 5 characters long.'); ?></div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control <?= isset($field_errors['confirm_password']) ? 'is-invalid' : ''; ?>" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                <i class="bi bi-eye-slash" id="eyeIcon2"></i>
                            </button>
                        </div>
                        <div id="passwordError" class="invalid-feedback <?= isset($field_errors['confirm_password']) ? 'd-block' : ''; ?>" data-error-for="confirm_password"><?= htmlspecialchars($field_errors['confirm_password'] ?? 'Passwords do not match.'); ?></div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-brand w-100 rounded-2 mb-3" id="submitBtn">
                        Sign Up
                    </button>

                </form>

                <!-- লগইন লিঙ্ক -->
                <div class="text-center mt-2">
                    <p class="text-secondary small mb-0">Already have an account?
                        <a href="login.php" class="text-success fw-bold text-decoration-none">Log In</a>
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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

            const form = document.getElementById('signupForm');
            const nameInput = form.elements.namedItem('customar_name');
            const phoneInput = form.elements.namedItem('phone');
            const emailInput = form.elements.namedItem('email');
            const pass = document.getElementById('password');
            const confirmPass = document.getElementById('confirmPassword');

            // Form inputs in order for Enter key navigation
            const formInputs = [nameInput, phoneInput, emailInput, pass, confirmPass];

            // Enter key presses move to next input; on last field it submits
            formInputs.forEach((input, index) => {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        if (index < formInputs.length - 1) {
                            e.preventDefault();
                            formInputs[index + 1].focus();
                        }
                    }
                });
            });

            function setFieldError(input, message) {
                const feedback = document.querySelector(`[data-error-for="${input.name}"]`);
                input.classList.toggle('is-invalid', Boolean(message));
                if (feedback) {
                    feedback.textContent = message;
                    feedback.classList.toggle('d-block', Boolean(message));
                }
            }

            [nameInput, phoneInput, emailInput, pass, confirmPass].forEach(input => {
                input.addEventListener('input', function() {
                    setFieldError(input, '');
                });
            });

            form.addEventListener('submit', function(e) {
                const phoneDigits = phoneInput.value.replace(/\D/g, '');
                const emailVal = emailInput.value.trim();
                // Email is optional: valid if empty OR if valid email syntax
                const isEmailValid = (emailVal === '') || emailInput.validity.valid;

                const errors = [
                    [nameInput, nameInput.value.trim() ? '' : 'Please enter your full name.'],
                    [phoneInput, phoneDigits.length >= 8 && /^[0-9+\s().-]+$/.test(phoneInput.value) ? '' : 'Phone number must contain at least 8 digits.'],
                    [emailInput, isEmailValid ? '' : 'Please enter a valid email address.'],
                    [pass, pass.value.length >= 5 ? '' : 'Password must be at least 5 characters long.'],
                    [confirmPass, confirmPass.value === pass.value && confirmPass.value !== '' ? '' : (confirmPass.value === '' ? 'Please confirm your password.' : 'Passwords do not match.')]
                ];
                let hasErrors = false;
                errors.forEach(([input, message]) => {
                    setFieldError(input, message);
                    if (message) hasErrors = true;
                });

                if (hasErrors) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>

</html>