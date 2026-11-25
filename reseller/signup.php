<?php
require_once __DIR__ . '/config.php';
if (!empty($_SESSION['reseller_id'])) {
    header('Location: home.php');
    exit;
}

$csrf_token = $_SESSION['reseller_signup_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['reseller_signup_csrf'] = $csrf_token;
$notice = is_array($_SESSION['reseller_flash'] ?? null) ? $_SESSION['reseller_flash'] : null;
unset($_SESSION['reseller_flash']);
$old = is_array($_SESSION['reseller_signup_old'] ?? null) ? $_SESSION['reseller_signup_old'] : [];
unset($_SESSION['reseller_signup_old']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals($csrf_token, $token)) {
            throw new InvalidArgumentException('Request verification failed. Reload the page and try again.');
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirm_password = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
        $digits = preg_replace('/\D/', '', $phone);
        if ($name === '' || strlen($name) > 180 || $phone === '' || strlen($phone) > 40 ||
            strlen((string)$digits) < 8 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 191 || strlen($password) < 8 || $password !== $confirm_password) {
            throw new InvalidArgumentException('Enter a name, valid phone and email, and matching passwords of at least 8 characters.');
        }

        $duplicate = $pdo->prepare('SELECT id, status FROM reseller_accounts WHERE email = :email OR phone = :phone LIMIT 1');
        $duplicate->execute([':email' => $email, ':phone' => $phone]);
        $existing = $duplicate->fetch(PDO::FETCH_ASSOC);
        if ($existing && $existing['status'] !== 'rejected') {
            throw new InvalidArgumentException('A reseller application already exists for this email or phone.');
        }

        $referral_code = strtoupper(bin2hex(random_bytes(6)));
        if ($existing) {
            $stmt = $pdo->prepare("
                UPDATE reseller_accounts
                SET name = :name, phone = :phone, email = :email, password_hash = :password,
                    referral_code = :referral, status = 'pending', rejection_reason = NULL,
                    reviewed_by_admin_id = NULL, reviewed_at = NULL
                WHERE id = :id
            ");
            $stmt->execute([
                ':name' => $name, ':phone' => $phone, ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':referral' => $referral_code, ':id' => (int)$existing['id'],
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO reseller_accounts (name, phone, email, password_hash, referral_code, status)
                VALUES (:name, :phone, :email, :password, :referral, 'pending')
            ");
            $stmt->execute([
                ':name' => $name, ':phone' => $phone, ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':referral' => $referral_code,
            ]);
        }
        $_SESSION['reseller_flash'] = ['type' => 'success', 'message' => 'Signup request submitted. Please wait for admin approval before logging in.'];
        header('Location: login.php');
        exit;
    } catch (InvalidArgumentException $e) {
        $notice = ['type' => 'warning', 'message' => $e->getMessage()];
        $old = ['name' => $name ?? '', 'phone' => $phone ?? '', 'email' => $email ?? ''];
    } catch (Throwable $e) {
        error_log('Reseller signup failed: ' . $e->getMessage());
        $notice = ['type' => 'danger', 'message' => 'Your signup request could not be saved. Please try again.'];
        $old = ['name' => $name ?? '', 'phone' => $phone ?? '', 'email' => $email ?? ''];
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Become a Reseller · AmarShop</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
<div class="card shadow-sm border-0 w-100" style="max-width:520px"><div class="card-body p-4 p-md-5">
<h1 class="h3 fw-bold">Become a Reseller</h1><p class="text-muted">Submit your details. You can log in after admin approval.</p>
<?php if ($notice): ?><div class="alert alert-<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
<label class="form-label" for="resellerName">Full name</label><input class="form-control mb-3" id="resellerName" name="name" maxlength="180" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
<label class="form-label" for="resellerPhone">Phone</label><input class="form-control mb-3" id="resellerPhone" name="phone" type="tel" maxlength="40" value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
<label class="form-label" for="resellerEmail">Email</label><input class="form-control mb-3" id="resellerEmail" name="email" type="email" maxlength="191" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
<label class="form-label" for="resellerPassword">Password (minimum 8 characters)</label><input class="form-control mb-3" id="resellerPassword" name="password" type="password" minlength="8" autocomplete="new-password" required>
<label class="form-label" for="resellerConfirmPassword">Confirm password</label><input class="form-control mb-4" id="resellerConfirmPassword" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
<button class="btn btn-success w-100" type="submit">Submit Signup Request</button>
</form><p class="text-center small mt-3 mb-0">Already applied? <a href="login.php">Reseller login</a></p>
</div></div></main></body></html>
