<?php
require_once __DIR__ . '/auth.php';
if (!empty($_SESSION['deliveryman_id'])) {
    header('Location: index.php');
    exit;
}
$error = '';
if (empty($_SESSION['delivery_login_csrf'])) {
    $_SESSION['delivery_login_csrf'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $login = trim((string)($_POST['login'] ?? ''));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (!hash_equals((string)$_SESSION['delivery_login_csrf'], $token)) {
        http_response_code(400);
        $error = 'Invalid request. Reload and try again.';
    } elseif ($login === '' || $password === '') {
        $error = 'Enter your phone or email and password.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, login_password FROM deliveryman
            WHERE status = 'active' AND (phone = :phone OR email = :email)
            ORDER BY id ASC LIMIT 2
        ");
        $stmt->execute([':phone' => $login, ':email' => $login]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rider = count($matches) === 1 ? $matches[0] : null;
        if ($rider && is_string($rider['login_password']) && password_verify($password, $rider['login_password'])) {
            session_regenerate_id(true);
            $_SESSION['deliveryman_id'] = (int)$rider['id'];
            unset($_SESSION['delivery_login_csrf']);
            header('Location: index.php');
            exit;
        }
        $error = 'Login failed. Check your credentials or contact an administrator.';
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Deliveryman Login · AmarShop</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><div class="card shadow-sm mx-auto" style="max-width:420px">
<div class="card-body p-4"><h1 class="h4 mb-3">Deliveryman login</h1>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['delivery_login_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
<label class="form-label" for="login">Phone or email</label><input class="form-control mb-3" id="login" name="login" autocomplete="username" required>
<label class="form-label" for="password">Password</label><input class="form-control mb-3" id="password" name="password" type="password" autocomplete="current-password" required>
<button class="btn btn-success w-100" type="submit">Log in</button></form></div></div></main></body></html>
