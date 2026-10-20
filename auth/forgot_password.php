<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$status = '';
$statusType = 'info';
$token = $_GET['token'] ?? '';
$tokenIsValidFormat = is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;
$csrfToken = $_SESSION['forgot_password_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['forgot_password_csrf'] = $csrfToken;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedCsrfToken) || !hash_equals($csrfToken, $submittedCsrfToken)) {
        http_response_code(400);
        $status = 'This request could not be verified. Please reload the page and try again.';
        $statusType = 'danger';
    } elseif (($_POST['action'] ?? '') === 'request_reset') {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $status = 'Enter a valid email address.';
            $statusType = 'danger';
        } elseif (SMTP_PASS === '') {
            error_log('Password reset unavailable because AMARSHOP_SMTP_PASS is not configured.');
            $status = 'Password reset email is temporarily unavailable. Please contact the site administrator.';
            $statusType = 'danger';
        } else {
            try {
                $userStmt = $pdo->prepare('SELECT id, customar_name, email FROM users WHERE email = :email LIMIT 1');
                $userStmt->execute([':email' => $email]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $plainToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $plainToken);
                    $saveToken = $pdo->prepare(
                        'UPDATE users SET reset_token = :reset_token, reset_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = :id'
                    );
                    $saveToken->execute([
                        ':reset_token' => $tokenHash,
                        ':id' => $user['id'],
                    ]);

                    try {
                        $appUrl = rtrim(getenv('AMARSHOP_BASE_URL') ?: 'http://localhost/amarshop', '/');
                        if (!filter_var($appUrl, FILTER_VALIDATE_URL)) {
                            throw new RuntimeException('AMARSHOP_BASE_URL must be a valid absolute URL.');
                        }

                        $resetUrl = $appUrl . '/auth/forgot_password.php?token=' . rawurlencode($plainToken);
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = SMTP_HOST;
                        $mail->SMTPAuth = true;
                        $mail->Username = SMTP_USER;
                        $mail->Password = SMTP_PASS;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = SMTP_PORT;
                        $mail->setFrom(SMTP_FROM, SMTP_NAME);
                        $mail->addAddress($user['email'], $user['customar_name']);
                        $mail->isHTML(true);
                        $mail->Subject = 'Reset your AmarShop password';
                        $mail->Body = '<p>Hello ' . htmlspecialchars($user['customar_name'], ENT_QUOTES, 'UTF-8') . ',</p>'
                            . '<p>Use the link below to reset your password. It expires in 30 minutes and can only be used once.</p>'
                            . '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Reset password</a></p>'
                            . '<p>If you did not request this, you can ignore this email.</p>';
                        $mail->AltBody = "Reset your AmarShop password using this link (expires in 30 minutes):\n" . $resetUrl;
                        $mail->send();
                    } catch (Throwable $e) {
                        $clearToken = $pdo->prepare(
                            'UPDATE users SET reset_token = NULL, reset_at = NULL WHERE id = :id AND reset_token = :reset_token'
                        );
                        $clearToken->execute([':id' => $user['id'], ':reset_token' => $tokenHash]);
                        error_log('Password reset email delivery failed: ' . $e->getMessage());
                    }
                }

                $status = 'If an account matches that email, password reset instructions have been sent.';
                $statusType = 'success';
            } catch (Throwable $e) {
                error_log('Password reset request failed: ' . $e->getMessage());
                $status = 'Password reset could not be completed. Please try again later.';
                $statusType = 'danger';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'reset_password') {
        $postedToken = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!is_string($postedToken) || preg_match('/\A[a-f0-9]{64}\z/', $postedToken) !== 1) {
            $status = 'This password reset link is invalid or has expired.';
            $statusType = 'danger';
        } elseif (!is_string($password) || strlen($password) < 8) {
            $token = $postedToken;
            $tokenIsValidFormat = true;
            $status = 'Choose a password with at least 8 characters.';
            $statusType = 'danger';
        } elseif (!is_string($confirmPassword) || !hash_equals($password, $confirmPassword)) {
            $token = $postedToken;
            $tokenIsValidFormat = true;
            $status = 'The passwords do not match.';
            $statusType = 'danger';
        } else {
            try {
                $tokenHash = hash('sha256', $postedToken);
                $updatePassword = $pdo->prepare(
                    'UPDATE users
                     SET password = :password, reset_token = NULL, reset_at = NULL, remember_token = NULL
                     WHERE reset_token = :reset_token AND reset_at >= NOW()'
                );
                $updatePassword->execute([
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':reset_token' => $tokenHash,
                ]);

                if ($updatePassword->rowCount() === 1) {
                    $status = 'Your password has been reset. You can now log in.';
                    $statusType = 'success';
                    $tokenIsValidFormat = false;
                } else {
                    $status = 'This password reset link is invalid or has expired.';
                    $statusType = 'danger';
                    $tokenIsValidFormat = false;
                }
            } catch (Throwable $e) {
                error_log('Password reset failed: ' . $e->getMessage());
                $status = 'Password reset could not be completed. Please try again later.';
                $statusType = 'danger';
            }
        }
    } else {
        http_response_code(400);
        $status = 'Invalid request. Please try again.';
        $statusType = 'danger';
    }
}

if ($tokenIsValidFormat) {
    $checkToken = $pdo->prepare('SELECT 1 FROM users WHERE reset_token = :reset_token AND reset_at >= NOW() LIMIT 1');
    $checkToken->execute([':reset_token' => hash('sha256', $token)]);
    if (!$checkToken->fetchColumn()) {
        $tokenIsValidFormat = false;
        if ($status === '') {
            $status = 'This password reset link is invalid or has expired.';
            $statusType = 'danger';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - AmarShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container d-flex justify-content-center align-items-center min-vh-100 py-5">
        <section class="card shadow-sm border-0 w-100" style="max-width: 440px;">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 fw-bold text-center mb-2"><?= $tokenIsValidFormat ? 'Reset Password' : 'Forgot Password'; ?></h1>
                <p class="text-muted text-center mb-4">
                    <?= $tokenIsValidFormat
                        ? 'Choose a new password for your account.'
                        : 'Enter your account email and we will send you a reset link.'; ?>
                </p>

                <?php if ($status !== ''): ?>
                    <div class="alert alert-<?= htmlspecialchars($statusType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($tokenIsValidFormat): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">New password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm new password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Reset password</button>
                    </form>
                <?php elseif ($status !== 'Your password has been reset. You can now log in.'): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="request_reset">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Send reset link</button>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <a href="login.php" class="link-success text-decoration-none">Back to login</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
