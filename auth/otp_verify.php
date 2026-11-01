<?php
require_once('../auth/config.php');

if (empty($_SESSION['verify_email']) || empty($_SESSION['verify_user_id'])) {
    header('Location: ' . base_url('auth/signup.php'));
    exit;
}
$csrfToken = $_SESSION['email_verify_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['email_verify_csrf'] = $csrfToken;
?>
<!doctype html>
<html lang="bn">
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
                <h4 class="fw-bold mb-2">ইমেইল যাচাই</h4>
                <p class="text-muted small">আপনার ইমেইলে পাঠানো ৬ সংখ্যার OTP লিখুন:<br><b><?= htmlspecialchars($_SESSION['verify_email'], ENT_QUOTES, 'UTF-8') ?></b></p>

                <?php if (isset($_SESSION['status']) && isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['status'] === 'success' ? 'success' : (($_SESSION['status'] ?? '') === 'warning' ? 'warning' : 'danger') ?> small py-2">
                        <?= htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php unset($_SESSION['status'], $_SESSION['message']); ?>
                <?php endif; ?>

                <form action="verify_process.php" method="POST">
                    <input type="hidden" name="action" value="verify">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <input type="text" name="otp_code" class="form-control text-center fs-4 tracking-wider" placeholder="123456" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold">যাচাই করুন</button>
                </form>
                <form action="verify_process.php" method="POST" class="mt-3">
                    <input type="hidden" name="action" value="resend">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-outline-success w-100">নতুন OTP পাঠান</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>