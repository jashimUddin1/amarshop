<?php
require_once('../auth/config.php');

if (empty($_SESSION['verify_email'])) {
    header('Location: ' . base_url('auth/signup.php'));
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify OTP - AmarShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-lg p-4 bg-white" style="max-width: 400px; width: 100%; border-radius: 12px;">
            <div class="card-body text-center">
                <h4 class="fw-bold mb-2">Email Verification</h4>
                <p class="text-muted small">Enter the 6-digit OTP sent to <br><b><?= htmlspecialchars($_SESSION['verify_email']) ?></b></p>

                <?php if (isset($_SESSION['status']) && isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['status'] === 'success' ? 'success' : 'danger' ?> small py-2">
                        <?= htmlspecialchars($_SESSION['message']) ?>
                    </div>
                    <?php unset($_SESSION['status'], $_SESSION['message']); ?>
                <?php endif; ?>

                <form action="verify_process.php" method="POST">
                    <div class="mb-3">
                        <input type="text" name="otp_code" class="form-control text-center fs-4 tracking-wider" placeholder="123456" maxlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold">Verify Email</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>