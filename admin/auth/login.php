<?php
// Include your PDO database & configuration setup
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInput = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = is_string($emailInput) ? trim($emailInput) : '';
    $password = is_string($password) ? $password : '';

    if (!empty($email) && !empty($password)) {
        try {
            // Prepared statement using PDO
            $stmt = $pdo->prepare("SELECT id, customar_name, email, password, role FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Supports both hashed passwords and plain text
                if (password_verify($password, $user['password']) && in_array(strtolower((string)$user['role']), ['admin', 'manager'], true)) {
                    
                    // Set session variables
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email']   = $user['email'];
                    $_SESSION['user_name'] = $user['customar_name'] ?? $user['email'];
                    $_SESSION['user_role'] = strtolower((string)$user['role']);

                    // Redirect after login
                    header("Location: " . base_url('index.php'));
                    exit;
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            error_log('Admin login failed: ' . $e->getMessage());
            $error = "Unable to log in right now. Please try again.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AmarShop Admin</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container min-vh-100 d-flex justify-content-center align-items-center">
        <div class="card shadow border-0 p-4 col-12 col-sm-8 col-md-6 col-lg-4">
            <div class="card-body">
                
                <h3 class="card-title text-center fw-bold mb-1">AmarShop</h3>
                <p class="text-center text-muted small mb-4">Sign in to your admin panel</p>

                <!-- Error Message Alert -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger p-2 small text-center" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    
                    <!-- Email Field -->
                    <div class="mb-3">
                        <label for="email" class="form-label text-secondary">Email Address</label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            placeholder="name@example.com" 
                            required
                        >
                    </div>

                    <!-- Password Field -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label text-secondary mb-0">Password</label>
                            <a href="#" class="text-decoration-none small">Forgot?</a>
                        </div>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="Password" 
                            required
                        >
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label text-secondary small" for="remember">Remember me</label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Login</button>

                </form>

                <!-- Footer -->
                <div class="text-center mt-4">
                    <p class="small text-muted mb-0">
                        Don't have an account? <a href="#" class="text-decoration-none fw-semibold">Sign Up</a>
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>