<?php
require_once __DIR__ . '/config.php';
if (!empty($_SESSION['reseller_id'])) {
    header('Location: home.php');
    exit;
}
$notice = is_array($_SESSION['reseller_flash'] ?? null) ? $_SESSION['reseller_flash'] : null;
unset($_SESSION['reseller_flash']);
$csrf_token = $_SESSION['reseller_login_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['reseller_login_csrf'] = $csrf_token;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $identity = trim((string)($_POST['identity'] ?? ''));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (!hash_equals($csrf_token, $token)) {
        $notice = ['type' => 'warning', 'message' => 'Session expired. Reload and try again.'];
    } else {
        $stmt = $pdo->prepare('SELECT id, name, password_hash, status, rejection_reason FROM reseller_accounts WHERE email = :email OR phone = :phone LIMIT 1');
        $stmt->execute([':email' => $identity, ':phone' => $identity]);
        $reseller = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reseller && password_verify($password, $reseller['password_hash'])) {
            if ($reseller['status'] === 'pending') {
                $notice = ['type' => 'warning', 'message' => 'You are not approved. Please contact admin.'];
            } elseif ($reseller['status'] === 'hold') {
                $notice = ['type' => 'warning', 'message' => 'Your reseller account is on hold. Please contact admin.'];
            } elseif ($reseller['status'] === 'rejected') {
                $reason = trim((string)($reseller['rejection_reason'] ?? ''));
                $notice = ['type' => 'danger', 'message' => 'Your reseller application was rejected.' . ($reason !== '' ? ' Reason: ' . $reason : ' Please contact admin.')];
            } else {
                session_regenerate_id(true);
                $_SESSION['reseller_id'] = (int)$reseller['id'];
                $_SESSION['reseller_name'] = $reseller['name'];
                header('Location: home.php');
                exit;
            }
        } else {
            $notice = ['type' => 'danger', 'message' => 'Invalid email/phone or password.'];
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reseller Login · AmarShop</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
<div class="card shadow-sm border-0 w-100" style="max-width:440px"><div class="card-body p-4 p-md-5">
<h1 class="h3 fw-bold">Reseller Login</h1><p class="text-muted">Sign in to your approved reseller account.</p>
<?php if ($notice): ?><div class="alert alert-<?= htmlspecialchars($notice['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
<label class="form-label" for="resellerIdentity">Email or phone</label><input class="form-control mb-3" id="resellerIdentity" name="identity" autocomplete="username" required>
<label class="form-label" for="resellerLoginPassword">Password</label><input class="form-control mb-4" id="resellerLoginPassword" name="password" type="password" autocomplete="current-password" required>
<button class="btn btn-success w-100" type="submit">Log In</button></form>
<p class="text-center small mt-3 mb-0">Not a reseller yet? <a href="signup.php">Apply here</a></p>
</div></div></main></body></html>
