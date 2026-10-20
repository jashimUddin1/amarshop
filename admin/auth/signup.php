<?php
require_once 'config.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - User Registration</title>
  <!-- Bootstrap 5 CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-dark d-flex align-items-center min-vh-100 py-1">

  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6">
        
        <div class="card border-0 shadow-lg rounded-4">
          <div class="card-body p-4 p-sm-5">
            
            <!-- Header -->
            <div class="text-center mb-4">
              <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                <i class="bi bi-person-plus-fill fs-3"></i>
              </div>
              <h3 class="fw-bold">Create an Account</h3>
              <p class="text-muted small">Please fill in the information below to register</p>
            </div>

            <form action="signup_core.php" method="POST">
              
              <!-- First Name & Last Name (Row) -->
              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-lg fs-6" id="first_name" name="first_name" placeholder="John" required>
                </div>
                <div class="col-6">
                  <label for="last_name" class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-lg fs-6" id="last_name" name="last_name" placeholder="Doe" required>
                </div>
              </div>

              <!-- Email Field -->
              <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                  <input type="email" class="form-control form-control-lg fs-6" id="email" name="email" placeholder="example@domain.com" required>
                </div>
              </div>

              <!-- Phone Field -->
              <div class="mb-3">
                <label for="phone" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                  <input type="tel" class="form-control form-control-lg fs-6" id="phone" name="phone" placeholder="01700000000" required>
                </div>
              </div>

              <!-- Password & Confirm Password (Row) -->
              <div class="row g-3 mb-4">
                <!-- Password Field -->
                <div class="col-sm-6">
                  <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="password" class="form-control form-control-lg fs-6" id="password" name="password" placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', 'togglePasswordIcon')">
                      <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                  </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="col-sm-6">
                  <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="password" class="form-control form-control-lg fs-6" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', 'toggleConfirmPasswordIcon')">
                      <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg fs-6 fw-bold shadow-sm">
                  Sign Up <i class="bi bi-arrow-right ms-1"></i>
                </button>
              </div>

              <!-- Login Link -->
              <div class="text-center">
                <p class="text-muted small mb-0">
                  Already have an account? <a href="login.php" class="text-primary text-decoration-none fw-semibold">Sign In</a>
                </p>
              </div>

            </form>

          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- JavaScript for Toggle Password Visibility -->
  <script>
    function togglePassword(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);

      if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
      } else {
        input.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
      }
    }
  </script>

</body>
</html>